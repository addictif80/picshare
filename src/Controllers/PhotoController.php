<?php

namespace PicShare\Controllers;

use PicShare\Core\Database;
use PicShare\Core\Session;
use PicShare\Services\ImageService;

class PhotoController
{
    public function upload(array $params): void
    {
        $album = Database::fetch('SELECT * FROM albums WHERE access_token = ?', [$params['token']]);
        if (!$album) { json(['error' => 'Album introuvable'], 404); }

        $isManager = isLoggedIn() && isAlbumManager($album);

        if (!$isManager && !albumUploadOpen($album)) {
            json(['error' => 'Les envois sont fermés.'], 403);
        }

        $uploaderName = '';
        if ($isManager) {
            $user = currentUser();
            $uploaderName = $user['name'];
        } else {
            $uploaderName = trim($_POST['name'] ?? Session::get('guest_name_' . $album['id'], ''));
            if (!$uploaderName) { json(['error' => 'Veuillez indiquer votre prénom.'], 422); }
            Session::set('guest_name_' . $album['id'], $uploaderName);
        }

        $uploaderEmail = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;

        $files = $_FILES['photos'] ?? null;
        if (!$files || empty($files['tmp_name'])) {
            json(['error' => 'Aucune photo reçue.'], 422);
        }

        // Normalize single/multiple file upload
        if (!is_array($files['tmp_name'])) {
            foreach ($files as $k => $v) $files[$k] = [$v];
        }

        // Ensure upload directories exist and are writable
        foreach (['photos', 'compressed', 'watermarked'] as $dir) {
            $path = UPLOAD_PATH . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0775, true);
            }
        }

        $maxSize   = (int) setting('max_file_size_mb', 20) * 1024 * 1024;
        $uploaded  = [];
        $errors    = [];
        $userId    = isLoggedIn() ? currentUser()['id'] : null;

        for ($i = 0; $i < count($files['tmp_name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) { $errors[] = $files['name'][$i] . ' : erreur upload'; continue; }
            if ($files['size'][$i] > $maxSize) { $errors[] = $files['name'][$i] . ' : fichier trop lourd'; continue; }

            $tmp  = $files['tmp_name'][$i];
            $orig = $files['name'][$i];

            if (!ImageService::isValidImage($tmp)) { $errors[] = $orig . ' : format non supporté'; continue; }

            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION)) ?: 'jpg';
            // HEIC/HEIF from iPhones → convert to JPEG
            $mime = mime_content_type($tmp);
            if (in_array($mime, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'])) {
                $ext = 'jpg';
            }
            $stored   = generateToken(16) . '.' . $ext;
            $destOrig = UPLOAD_PATH . '/photos/' . $stored;

            if ($ext === 'jpg' && in_array($mime, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'])) {
                // Try to convert HEIC using ImageMagick if available
                if (class_exists('Imagick')) {
                    try {
                        $imagick = new \Imagick($tmp);
                        $imagick->setImageFormat('jpeg');
                        $imagick->writeImage($destOrig);
                        $imagick->clear();
                    } catch (\Exception $e) {
                        $errors[] = $orig . ' : format HEIC non convertible sur ce serveur';
                        continue;
                    }
                } else {
                    $errors[] = $orig . ' : format HEIC non supporté (utilisez JPG ou PNG)';
                    continue;
                }
            } elseif (!move_uploaded_file($tmp, $destOrig)) {
                $writable = is_writable(UPLOAD_PATH . '/photos') ? '' : ' (dossier non accessible en écriture)';
                $errors[] = $orig . ' : impossible de sauvegarder' . $writable;
                continue;
            }

            // Compress for display
            $compressed = 'c_' . $stored;
            $destComp   = UPLOAD_PATH . '/compressed/' . $compressed;
            $compOk     = ImageService::compress($destOrig, $destComp);

            $status = ($album['approval_mode'] === 'auto' || $isManager) ? 'approved' : 'pending';

            $photoId = Database::insert(
                'INSERT INTO photos (album_id, uploader_user_id, uploader_name, uploader_email, original_filename, stored_filename, compressed_filename, file_size, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $album['id'], $userId, $uploaderName, $uploaderEmail,
                    $orig, $stored, $compOk ? $compressed : null,
                    filesize($destOrig), $status,
                ]
            );

            $uploaded[] = [
                'id'     => $photoId,
                'name'   => $orig,
                'url'    => BASE_URL . '/photo/' . $photoId . '/view',
                'status' => $status,
            ];
        }

        json(['uploaded' => $uploaded, 'errors' => $errors]);
    }

    public function comment(array $params): void
    {
        $photo = Database::fetch('SELECT * FROM photos WHERE id = ? AND status = ?', [$params['id'], 'approved']);
        if (!$photo) { json(['error' => 'Photo introuvable'], 404); }

        $name    = trim($_POST['name'] ?? Session::get('guest_name_' . $photo['album_id'], ''));
        $content = trim($_POST['content'] ?? '');

        if (!$name || !$content) { json(['error' => 'Nom et commentaire requis.'], 422); }
        if (strlen($content) > 1000) { json(['error' => 'Commentaire trop long (max 1000 car.)'], 422); }

        $id = Database::insert(
            'INSERT INTO comments (photo_id, author_name, content) VALUES (?, ?, ?)',
            [$photo['id'], $name, $content]
        );

        json([
            'success' => true,
            'comment' => [
                'id'          => $id,
                'author_name' => $name,
                'content'     => $content,
                'time_ago'    => 'à l\'instant',
            ],
        ]);
    }

    public function react(array $params): void
    {
        $photo = Database::fetch('SELECT * FROM photos WHERE id = ? AND status = ?', [$params['id'], 'approved']);
        if (!$photo) { json(['error' => 'Photo introuvable'], 404); }

        $type       = $_POST['type'] ?? '❤️';
        $validTypes = ['❤️', '😂', '😮', '👏', '🔥'];
        if (!in_array($type, $validTypes)) { json(['error' => 'Réaction invalide'], 422); }

        $name = trim($_POST['name'] ?? Session::get('guest_name_' . $photo['album_id'], ''));
        if (!$name) { json(['error' => 'Nom requis'], 422); }

        // Session key to prevent duplicate reactions
        $sessionKey = Session::get('reaction_key');
        if (!$sessionKey) {
            $sessionKey = generateToken(16);
            Session::set('reaction_key', $sessionKey);
        }

        // Toggle reaction (upsert)
        $existing = Database::fetch('SELECT * FROM reactions WHERE photo_id = ? AND session_key = ?', [$photo['id'], $sessionKey]);
        if ($existing) {
            if ($existing['type'] === $type) {
                Database::execute('DELETE FROM reactions WHERE id = ?', [$existing['id']]);
                $action = 'removed';
            } else {
                Database::execute('UPDATE reactions SET type = ? WHERE id = ?', [$type, $existing['id']]);
                $action = 'changed';
            }
        } else {
            Database::insert('INSERT INTO reactions (photo_id, author_name, session_key, type) VALUES (?, ?, ?, ?)',
                [$photo['id'], $name, $sessionKey, $type]);
            $action = 'added';
        }

        $counts = Database::fetchAll('SELECT type, COUNT(*) as cnt FROM reactions WHERE photo_id = ? GROUP BY type', [$photo['id']]);
        json(['success' => true, 'action' => $action, 'counts' => $counts]);
    }
}
