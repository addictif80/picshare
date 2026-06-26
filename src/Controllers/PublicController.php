<?php

namespace PicShare\Controllers;

use PicShare\Core\Database;
use PicShare\Core\Session;

class PublicController
{
    public function landing(array $params): void
    {
        if (isLoggedIn()) redirect('/dashboard');
        require VIEWS_PATH . '/landing/index.php';
    }

    public function setGuestName(array $params): void
    {
        $album = Database::fetch('SELECT * FROM albums WHERE access_token = ?', [$params['token']]);
        if (!$album) { json(['error' => 'Album introuvable'], 404); }

        $name = trim($_POST['name'] ?? '');
        if (!$name || strlen($name) > 100) { json(['error' => 'Prénom invalide'], 422); }

        Session::set('guest_name_' . $album['id'], $name);
        json(['success' => true]);
    }

    public function photoDetails(array $params): void
    {
        $photo = Database::fetch('SELECT * FROM photos WHERE id = ? AND status = ?', [$params['id'], 'approved']);
        if (!$photo) { json(['error' => 'Photo introuvable'], 404); }

        $comments = Database::fetchAll('SELECT * FROM comments WHERE photo_id = ? ORDER BY created_at ASC', [$photo['id']]);
        $reactions = Database::fetchAll('SELECT type, COUNT(*) as cnt FROM reactions WHERE photo_id = ? GROUP BY type', [$photo['id']]);

        foreach ($comments as &$c) {
            $c['time_ago'] = timeAgo($c['created_at']);
        }

        json([
            'uploader_name' => $photo['uploader_name'],
            'time_ago'      => timeAgo($photo['created_at']),
            'comments'      => $comments,
            'reactions'     => $reactions,
        ]);
    }

    public function serveQR(array $params): void
    {
        $file = STORAGE_PATH . '/qrcodes/' . preg_replace('/[^a-f0-9]/', '', $params['token']) . '.png';
        if (!file_exists($file)) abort(404);

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
}
