<?php

namespace PicShare\Services;

use PicShare\Core\Database;

class ZipService
{
    public static function createAlbumArchive(array $album): string|false
    {
        $archiveDir = STORAGE_PATH . '/archives';
        if (!is_dir($archiveDir)) mkdir($archiveDir, 0755, true);

        $filename = 'album_' . $album['id'] . '_' . time() . '.zip';
        $archivePath = $archiveDir . '/' . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // Get event info
        $event = Database::fetch('SELECT title FROM events WHERE id = ?', [$album['event_id']]);
        $albumTitle = sanitizeFilename($album['title']);

        // Get approved photos
        $photos = Database::fetchAll(
            'SELECT * FROM photos WHERE album_id = ? AND status = ? ORDER BY created_at ASC',
            [$album['id'], 'approved']
        );

        $participants = [];
        $photoCount = 0;

        foreach ($photos as $photo) {
            $originalPath = UPLOAD_PATH . '/photos/' . $photo['stored_filename'];
            if (!file_exists($originalPath)) continue;

            $ext = pathinfo($photo['original_filename'], PATHINFO_EXTENSION);
            $safeFilename = sprintf('%04d_%s_%s.%s',
                ++$photoCount,
                sanitizeFilename($photo['uploader_name']),
                date('Ymd_His', strtotime($photo['created_at'])),
                $ext
            );

            $zip->addFile($originalPath, $albumTitle . '/photos/' . $safeFilename);

            if (!in_array($photo['uploader_name'], $participants)) {
                $participants[] = $photo['uploader_name'];
            }
        }

        // participants.txt
        $participantsContent = "Album : {$album['title']}\n";
        $participantsContent .= "Événement : " . ($event['title'] ?? '') . "\n";
        $participantsContent .= "Date de génération : " . date('d/m/Y H:i') . "\n";
        $participantsContent .= "Nombre de photos : {$photoCount}\n";
        $participantsContent .= "\n--- PARTICIPANTS ---\n\n";
        foreach ($participants as $name) {
            $participantsContent .= "• {$name}\n";
        }
        $zip->addFromString($albumTitle . '/participants.txt', $participantsContent);

        // Comments/reactions HTML page
        $commentsHtml = self::generateCommentsPage($album, $photos);
        if ($commentsHtml) {
            $zip->addFromString($albumTitle . '/commentaires.html', $commentsHtml);
        }

        $zip->close();
        return $archivePath;
    }

    private static function generateCommentsPage(array $album, array $photos): string
    {
        $hasContent = false;
        $rows = '';

        foreach ($photos as $photo) {
            $comments = Database::fetchAll(
                'SELECT * FROM comments WHERE photo_id = ? ORDER BY created_at ASC',
                [$photo['id']]
            );
            $reactions = Database::fetchAll(
                'SELECT type, COUNT(*) as cnt FROM reactions WHERE photo_id = ? GROUP BY type',
                [$photo['id']]
            );

            if (empty($comments) && empty($reactions)) continue;
            $hasContent = true;

            $photoName = e($photo['original_filename']);
            $uploaderName = e($photo['uploader_name']);
            $rows .= "<div class='photo-block'>";
            $rows .= "<h3>📷 {$photoName} <span class='by'>par {$uploaderName}</span></h3>";

            if (!empty($reactions)) {
                $rows .= "<div class='reactions'>";
                foreach ($reactions as $r) {
                    $rows .= "<span class='reaction'>{$r['type']} × {$r['cnt']}</span>";
                }
                $rows .= "</div>";
            }

            foreach ($comments as $c) {
                $rows .= "<div class='comment'><strong>" . e($c['author_name']) . "</strong> <em>" . date('d/m/Y H:i', strtotime($c['created_at'])) . "</em><p>" . nl2br(e($c['content'])) . "</p></div>";
            }
            $rows .= "</div>";
        }

        if (!$hasContent) return '';

        $albumTitle = e($album['title']);
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Commentaires — {$albumTitle}</title>
<style>
  @page { size: A4; margin: 20mm; }
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.5; }
  h1 { font-size: 20px; color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 8px; margin-bottom: 24px; }
  h3 { font-size: 13px; margin: 0 0 6px; color: #374151; }
  .by { font-weight: normal; color: #9ca3af; font-size: 11px; }
  .photo-block { margin-bottom: 20px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; page-break-inside: avoid; }
  .reactions { margin: 6px 0; }
  .reaction { display: inline-block; background: #f3f4f6; border-radius: 12px; padding: 2px 8px; margin-right: 6px; font-size: 12px; }
  .comment { margin-top: 8px; padding: 8px; background: #f9fafb; border-left: 3px solid #6366f1; border-radius: 4px; }
  .comment strong { color: #6366f1; }
  .comment em { color: #9ca3af; font-size: 10px; margin-left: 8px; }
  .comment p { margin: 4px 0 0; }
</style>
</head>
<body>
<h1>💬 Commentaires &amp; Réactions — {$albumTitle}</h1>
{$rows}
</body>
</html>
HTML;
    }
}
