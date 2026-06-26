<?php

namespace PicShare\Controllers;

use PicShare\Core\Database;
use PicShare\Core\Session;
use PicShare\Services\ImageService;
use PicShare\Services\QRCodeService;
use PicShare\Services\ZipService;
use PicShare\Services\MailService;

class AlbumController
{
    public function create(array $params): void
    {
        requireLogin();
        $eventId = (int) ($params['event_id'] ?? $_POST['event_id'] ?? 0);
        $event = Database::fetch('SELECT * FROM events WHERE id = ?', [$eventId]);
        if (!$event) abort(404);
        if (!$this->canManageEvent($event)) abort(403);

        // Check max albums per event
        $count = Database::fetch('SELECT COUNT(*) as cnt FROM albums WHERE event_id = ?', [$eventId]);
        $max = (int) setting('max_albums_per_event', 10);
        if ($count['cnt'] >= $max) {
            flash('error', "Nombre maximum d'albums atteint ({$max}) pour cet événement.");
            redirect('/events/' . $eventId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store($event);
        } else {
            view('albums.create', ['title' => 'Nouvel album', 'event' => $event]);
        }
    }

    private function store(array $event): void
    {
        $title         = trim($_POST['title'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $uploadStart   = $_POST['upload_start'] ?: null;
        $uploadEnd     = $_POST['upload_end'] ?: null;
        $approvalMode  = in_array($_POST['approval_mode'] ?? '', ['auto', 'manual']) ? $_POST['approval_mode'] : 'auto';
        $qrColor       = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['qr_color'] ?? '') ? $_POST['qr_color'] : '#1a1a2e';

        if (!$title) {
            flash('error', 'Le titre est requis.');
            redirect('/events/' . $event['id'] . '/albums/create');
        }

        $accessToken = generateToken(16);

        $albumId = Database::insert(
            'INSERT INTO albums (event_id, title, description, access_token, upload_start, upload_end, approval_mode, qr_color)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$event['id'], $title, $description, $accessToken, $uploadStart, $uploadEnd, $approvalMode, $qrColor]
        );

        // Handle QR logo upload
        $qrLogo = null;
        if (!empty($_FILES['qr_logo']['tmp_name'])) {
            $qrLogo = $this->uploadLogo($_FILES['qr_logo'], 'qr_' . $albumId);
            if ($qrLogo) {
                Database::execute('UPDATE albums SET qr_logo = ? WHERE id = ?', [$qrLogo, $albumId]);
            }
        }

        // Generate QR code
        $album = Database::fetch('SELECT * FROM albums WHERE id = ?', [$albumId]);
        $this->regenerateQR($album);

        flash('success', 'Album créé avec succès !');
        redirect('/albums/' . $albumId . '/manage');
    }

    public function show(array $params): void
    {
        requireLogin();
        $album = $this->getAlbumOrAbort($params['id']);
        if (!isAlbumManager($album)) abort(403);

        $photos = Database::fetchAll(
            'SELECT p.*, COUNT(c.id) as comment_count, COUNT(r.id) as reaction_count
             FROM photos p
             LEFT JOIN comments c ON c.photo_id = p.id
             LEFT JOIN reactions r ON r.photo_id = p.id
             WHERE p.album_id = ?
             GROUP BY p.id
             ORDER BY p.created_at DESC',
            [$album['id']]
        );

        $event = Database::fetch('SELECT * FROM events WHERE id = ?', [$album['event_id']]);

        view('albums.manage', [
            'title'  => $album['title'],
            'album'  => $album,
            'event'  => $event,
            'photos' => $photos,
        ]);
    }

    public function publicShow(array $params): void
    {
        $album = Database::fetch('SELECT * FROM albums WHERE access_token = ?', [$params['token']]);
        if (!$album) abort(404);

        $guestName = Session::get('guest_name_' . $album['id']);
        $isOpen    = albumUploadOpen($album);

        $photos = Database::fetchAll(
            'SELECT p.*, COUNT(c.id) as comment_count
             FROM photos p
             LEFT JOIN comments c ON c.photo_id = p.id
             WHERE p.album_id = ? AND p.status = ?
             GROUP BY p.id
             ORDER BY p.created_at DESC',
            [$album['id'], 'approved']
        );

        // Best-of: photos with most reactions
        $bestOf = Database::fetchAll(
            'SELECT p.*, COUNT(r.id) as reaction_count
             FROM photos p
             JOIN reactions r ON r.photo_id = p.id
             WHERE p.album_id = ? AND p.status = ?
             GROUP BY p.id
             HAVING reaction_count >= 3
             ORDER BY reaction_count DESC
             LIMIT 12',
            [$album['id'], 'approved']
        );

        view('public.album', [
            'title'     => $album['title'],
            'album'     => $album,
            'photos'    => $photos,
            'bestOf'    => $bestOf,
            'guestName' => $guestName,
            'isOpen'    => $isOpen,
        ]);
    }

    public function slideshow(array $params): void
    {
        $album = Database::fetch('SELECT * FROM albums WHERE access_token = ?', [$params['token']]);
        if (!$album || !$album['slideshow_active']) abort(404);

        view('public.slideshow', ['title' => $album['title'] . ' — Diaporama', 'album' => $album]);
    }

    public function slideshowFeed(array $params): void
    {
        $album = Database::fetch('SELECT * FROM albums WHERE access_token = ?', [$params['token']]);
        if (!$album) { json(['error' => 'not found'], 404); }

        $since = (int) ($_GET['since'] ?? 0);
        $photos = Database::fetchAll(
            'SELECT p.id, p.stored_filename, p.compressed_filename, p.uploader_name, p.created_at,
                    COUNT(c.id) as comment_count, COUNT(r.id) as reaction_count
             FROM photos p
             LEFT JOIN comments c ON c.photo_id = p.id
             LEFT JOIN reactions r ON r.photo_id = p.id
             WHERE p.album_id = ? AND p.status = ? AND p.id > ?
             GROUP BY p.id
             ORDER BY p.created_at DESC',
            [$album['id'], 'approved', $since]
        );

        foreach ($photos as &$photo) {
            $photo['url'] = BASE_URL . '/photo/' . $photo['id'] . '/view';
            $photo['time_ago'] = timeAgo($photo['created_at']);
        }

        json(['photos' => $photos, 'last_id' => !empty($photos) ? $photos[0]['id'] : $since]);
    }

    public function endEvent(array $params): void
    {
        requireLogin();
        $album = $this->getAlbumOrAbort($params['id']);
        if (!isAlbumManager($album)) abort(403);

        Database::execute('UPDATE albums SET is_ended = 1 WHERE id = ?', [$album['id']]);

        // Notify participants who provided email
        $participants = Database::fetchAll(
            'SELECT DISTINCT uploader_name, uploader_email FROM photos WHERE album_id = ? AND uploader_email IS NOT NULL AND status = ?',
            [$album['id'], 'approved']
        );
        $albumUrl = BASE_URL . '/a/' . $album['access_token'];
        foreach ($participants as $p) {
            MailService::sendEventClosed($p['uploader_email'], $p['uploader_name'], $album['title'], $albumUrl);
        }

        flash('success', 'Événement clôturé. Les envois sont maintenant désactivés.');
        redirect('/albums/' . $album['id'] . '/manage');
    }

    public function approvePhoto(array $params): void
    {
        requireLogin();
        $photo = Database::fetch('SELECT * FROM photos WHERE id = ?', [$params['photo_id']]);
        if (!$photo) abort(404);
        $album = Database::fetch('SELECT * FROM albums WHERE id = ?', [$photo['album_id']]);
        if (!isAlbumManager($album)) abort(403);

        Database::execute('UPDATE photos SET status = ? WHERE id = ?', [$params['status'], $photo['id']]);
        json(['success' => true]);
    }

    public function deletePhoto(array $params): void
    {
        requireLogin();
        $photo = Database::fetch('SELECT * FROM photos WHERE id = ?', [$params['photo_id']]);
        if (!$photo) abort(404);
        $album = Database::fetch('SELECT * FROM albums WHERE id = ?', [$photo['album_id']]);
        if (!isAlbumManager($album)) abort(403);

        @unlink(UPLOAD_PATH . '/photos/' . $photo['stored_filename']);
        if ($photo['compressed_filename']) @unlink(UPLOAD_PATH . '/compressed/' . $photo['compressed_filename']);

        Database::execute('DELETE FROM photos WHERE id = ?', [$photo['id']]);
        json(['success' => true]);
    }

    public function updateSettings(array $params): void
    {
        requireLogin();
        $album = $this->getAlbumOrAbort($params['id']);
        if (!isAlbumManager($album)) abort(403);

        $fields = [
            'title'           => trim($_POST['title'] ?? $album['title']),
            'description'     => trim($_POST['description'] ?? ''),
            'upload_start'    => $_POST['upload_start'] ?: null,
            'upload_end'      => $_POST['upload_end'] ?: null,
            'approval_mode'   => in_array($_POST['approval_mode'] ?? '', ['auto', 'manual']) ? $_POST['approval_mode'] : $album['approval_mode'],
            'slideshow_active'=> isset($_POST['slideshow_active']) ? 1 : 0,
            'qr_color'        => preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['qr_color'] ?? '') ? $_POST['qr_color'] : $album['qr_color'],
        ];

        // Handle QR logo upload
        if (!empty($_FILES['qr_logo']['tmp_name'])) {
            $logo = $this->uploadLogo($_FILES['qr_logo'], 'qr_' . $album['id']);
            if ($logo) $fields['qr_logo'] = $logo;
        }

        $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($fields)));
        Database::execute(
            "UPDATE albums SET {$sets} WHERE id = ?",
            [...array_values($fields), $album['id']]
        );

        // Regenerate QR code
        $updatedAlbum = Database::fetch('SELECT * FROM albums WHERE id = ?', [$album['id']]);
        $this->regenerateQR($updatedAlbum);

        flash('success', 'Paramètres mis à jour.');
        redirect('/albums/' . $album['id'] . '/manage');
    }

    public function download(array $params): void
    {
        $token  = $params['token'];
        $album  = Database::fetch('SELECT * FROM albums WHERE download_token = ?', [$token]);

        if (!$album) abort(404);
        if ($album['payment_status'] !== 'paid') abort(403);
        if ($album['download_expires_at'] && strtotime($album['download_expires_at']) < time()) {
            abort(410); // Gone
        }

        // Generate archive if not exists
        $archiveDir = STORAGE_PATH . '/archives';
        $pattern = $archiveDir . '/album_' . $album['id'] . '_*.zip';
        $files = glob($pattern);
        $archivePath = $files ? $files[0] : ZipService::createAlbumArchive($album);

        if (!$archivePath || !file_exists($archivePath)) {
            flash('error', 'Erreur lors de la génération de l\'archive.');
            abort(500);
        }

        $filename = sanitizeFilename($album['title']) . '_photos.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($archivePath));
        header('Cache-Control: no-cache');
        readfile($archivePath);
        exit;
    }

    public function servePhoto(array $params): void
    {
        $photo = Database::fetch('SELECT * FROM photos WHERE id = ?', [$params['id']]);
        if (!$photo) abort(404);

        $album = Database::fetch('SELECT * FROM albums WHERE id = ?', [$photo['album_id']]);
        if (!$album) abort(404);

        $isManager = isAlbumManager($album);
        $useWatermark = !$isManager; // Managers see originals, guests see watermarked

        if ($useWatermark) {
            $compressed = UPLOAD_PATH . '/compressed/' . $photo['compressed_filename'];
            $watermarked = UPLOAD_PATH . '/watermarked/wm_' . $photo['compressed_filename'];

            if ($photo['compressed_filename'] && file_exists($compressed)) {
                if (!file_exists($watermarked)) {
                    ImageService::applyWatermark($compressed, $watermarked);
                }
                $servePath = $watermarked;
            } else {
                $original = UPLOAD_PATH . '/photos/' . $photo['stored_filename'];
                $wmOriginal = UPLOAD_PATH . '/watermarked/wm_' . $photo['stored_filename'];
                if (!file_exists($wmOriginal)) {
                    ImageService::applyWatermark($original, $wmOriginal);
                }
                $servePath = $wmOriginal;
            }
        } else {
            $servePath = UPLOAD_PATH . '/photos/' . $photo['stored_filename'];
        }

        if (!file_exists($servePath)) abort(404);

        $mime = ImageService::getMimeType($servePath);
        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=3600');
        header('X-Robots-Tag: noindex');
        readfile($servePath);
        exit;
    }

    public function regenerateQRAction(array $params): void
    {
        requireLogin();
        $album = $this->getAlbumOrAbort($params['id']);
        if (!isAlbumManager($album)) abort(403);
        $this->regenerateQR($album);
        json(['success' => true, 'url' => BASE_URL . '/qr/' . $album['access_token'] . '.png']);
    }

    private function regenerateQR(array $album): void
    {
        $url = BASE_URL . '/a/' . $album['access_token'];
        QRCodeService::generate($url, $album['access_token'], [
            'color' => $album['qr_color'],
            'logo'  => $album['qr_logo'],
        ]);
    }

    private function getAlbumOrAbort(mixed $id): array
    {
        $album = Database::fetch('SELECT * FROM albums WHERE id = ?', [(int) $id]);
        if (!$album) abort(404);
        return $album;
    }

    private function canManageEvent(array $event): bool
    {
        $user = currentUser();
        if (!$user) return false;
        if ($user['role'] === 'admin') return true;
        if ($event['owner_id'] == $user['id']) return true;
        $m = Database::fetch('SELECT id FROM event_managers WHERE event_id = ? AND user_id = ?', [$event['id'], $user['id']]);
        return (bool) $m;
    }

    private function uploadLogo(array $file, string $prefix): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) return null;

        $ext = match($mime) {
            'image/jpeg'   => 'jpg',
            'image/png'    => 'png',
            'image/webp'   => 'webp',
            'image/svg+xml'=> 'svg',
            default        => 'png',
        };
        $filename = $prefix . '_' . time() . '.' . $ext;
        $dest = UPLOAD_PATH . '/logos/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) return $filename;
        return null;
    }
}
