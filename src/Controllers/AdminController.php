<?php

namespace PicShare\Controllers;

use PicShare\Core\Database;

class AdminController
{
    public function dashboard(array $params): void
    {
        requireAdmin();
        $stats = [
            'users'    => Database::fetch('SELECT COUNT(*) as cnt FROM users')['cnt'],
            'events'   => Database::fetch('SELECT COUNT(*) as cnt FROM events')['cnt'],
            'albums'   => Database::fetch('SELECT COUNT(*) as cnt FROM albums')['cnt'],
            'photos'   => Database::fetch('SELECT COUNT(*) as cnt FROM photos WHERE status = ?', ['approved'])['cnt'],
            'revenue'  => Database::fetch('SELECT COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE status = ?', ['completed'])['total'],
            'pending_photos' => Database::fetch('SELECT COUNT(*) as cnt FROM photos WHERE status = ?', ['pending'])['cnt'],
        ];
        view('admin.dashboard', ['title' => 'Administration', 'stats' => $stats]);
    }

    public function settings(array $params): void
    {
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveSettings();
        }
        $settings = Database::fetchAll('SELECT `key`, value FROM settings ORDER BY `key`');
        $settingsMap = array_column($settings, 'value', 'key');
        view('admin.settings', ['title' => 'Paramètres système', 'settings' => $settingsMap]);
    }

    private function saveSettings(): void
    {
        $allowed = [
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_email',
            'smtp_from_name', 'smtp_encryption', 'stripe_public_key', 'stripe_secret_key',
            'stripe_webhook_secret', 'album_price', 'watermark_type', 'watermark_text',
            'watermark_opacity', 'watermark_size', 'site_tagline', 'max_file_size_mb',
            'max_albums_per_event', 'maintenance_mode',
        ];

        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                Database::execute(
                    'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?',
                    [$key, $_POST[$key], $_POST[$key]]
                );
            }
        }

        // Handle logo upload
        if (!empty($_FILES['site_logo']['tmp_name']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $logo = $this->uploadLogo($_FILES['site_logo'], 'site_logo');
            if ($logo) {
                Database::execute("INSERT INTO settings (`key`, value) VALUES ('site_logo', ?) ON DUPLICATE KEY UPDATE value = ?", [$logo, $logo]);
            }
        }

        // Handle watermark image upload
        if (!empty($_FILES['watermark_image']['tmp_name']) && $_FILES['watermark_image']['error'] === UPLOAD_ERR_OK) {
            $wm = $this->uploadLogo($_FILES['watermark_image'], 'watermark');
            if ($wm) {
                Database::execute("INSERT INTO settings (`key`, value) VALUES ('watermark_image', ?) ON DUPLICATE KEY UPDATE value = ?", [$wm, $wm]);
                // Invalidate watermark cache
                array_map('unlink', glob(UPLOAD_PATH . '/watermarked/*'));
            }
        }

        flash('success', 'Paramètres sauvegardés.');
        redirect('/admin/settings');
    }

    public function users(array $params): void
    {
        requireAdmin();
        $users = Database::fetchAll(
            'SELECT u.*, (SELECT COUNT(*) FROM events e WHERE e.owner_id = u.id) as event_count FROM users u ORDER BY u.created_at DESC'
        );
        view('admin.users', ['title' => 'Utilisateurs', 'users' => $users]);
    }

    public function toggleUser(array $params): void
    {
        requireAdmin();
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [(int) $params['id']]);
        if (!$user) { json(['error' => 'Introuvable'], 404); }
        if ($user['id'] == currentUser()['id']) { json(['error' => 'Impossible de se désactiver soi-même'], 400); }

        $new = $user['is_active'] ? 0 : 1;
        Database::execute('UPDATE users SET is_active = ? WHERE id = ?', [$new, $user['id']]);
        json(['success' => true, 'active' => $new]);
    }

    public function promoCodes(array $params): void
    {
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->createPromo();
        }
        $promos = Database::fetchAll('SELECT * FROM promo_codes ORDER BY created_at DESC');
        view('admin.promo-codes', ['title' => 'Codes promo', 'promos' => $promos]);
    }

    private function createPromo(): void
    {
        $code           = strtoupper(trim($_POST['code'] ?? ''));
        $discountPct    = $_POST['discount_percent'] ? (int) $_POST['discount_percent'] : null;
        $discountFixed  = $_POST['discount_fixed'] ? (float) $_POST['discount_fixed'] : null;
        $maxUses        = $_POST['max_uses'] ? (int) $_POST['max_uses'] : null;
        $expiresAt      = $_POST['expires_at'] ?: null;

        if (!$code || (!$discountPct && !$discountFixed)) {
            flash('error', 'Code et remise requis.');
            redirect('/admin/promo-codes');
        }

        try {
            Database::insert(
                'INSERT INTO promo_codes (code, discount_percent, discount_fixed, max_uses, expires_at) VALUES (?, ?, ?, ?, ?)',
                [$code, $discountPct, $discountFixed, $maxUses, $expiresAt]
            );
            flash('success', 'Code promo créé.');
        } catch (\Exception $e) {
            flash('error', 'Ce code existe déjà.');
        }
        redirect('/admin/promo-codes');
    }

    public function deletePromo(array $params): void
    {
        requireAdmin();
        Database::execute('DELETE FROM promo_codes WHERE id = ?', [(int) $params['id']]);
        json(['success' => true]);
    }

    public function togglePromo(array $params): void
    {
        requireAdmin();
        $promo = Database::fetch('SELECT * FROM promo_codes WHERE id = ?', [(int) $params['id']]);
        if (!$promo) { json(['error' => 'Introuvable'], 404); }
        $new = $promo['is_active'] ? 0 : 1;
        Database::execute('UPDATE promo_codes SET is_active = ? WHERE id = ?', [$new, $promo['id']]);
        json(['success' => true, 'active' => $new]);
    }

    public function cleanup(array $params): void
    {
        requireAdmin();

        // Delete expired albums
        $expired = Database::fetchAll(
            'SELECT * FROM albums WHERE delete_scheduled_at IS NOT NULL AND delete_scheduled_at < NOW()'
        );

        $deleted = 0;
        foreach ($expired as $album) {
            // Delete photos from disk
            $photos = Database::fetchAll('SELECT * FROM photos WHERE album_id = ?', [$album['id']]);
            foreach ($photos as $photo) {
                @unlink(UPLOAD_PATH . '/photos/' . $photo['stored_filename']);
                if ($photo['compressed_filename']) {
                    @unlink(UPLOAD_PATH . '/compressed/' . $photo['compressed_filename']);
                    @unlink(UPLOAD_PATH . '/watermarked/wm_' . $photo['compressed_filename']);
                }
            }

            // Delete archive
            foreach (glob(STORAGE_PATH . '/archives/album_' . $album['id'] . '_*.zip') as $f) {
                @unlink($f);
            }

            // Delete QR code
            @unlink(STORAGE_PATH . '/qrcodes/' . $album['access_token'] . '.png');

            Database::execute('DELETE FROM albums WHERE id = ?', [$album['id']]);
            $deleted++;
        }

        json(['success' => true, 'deleted' => $deleted]);
    }

    private function uploadLogo(array $file, string $prefix): ?string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) return null;

        $ext = match($mime) {
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/webp'    => 'webp',
            'image/svg+xml' => 'svg',
            default         => 'png',
        };
        $filename = $prefix . '_' . time() . '.' . $ext;
        $dest = UPLOAD_PATH . '/logos/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) return $filename;
        return null;
    }
}
