<?php

namespace PicShare\Controllers;

use PicShare\Core\Database;

class DashboardController
{
    public function index(array $params): void
    {
        requireLogin();
        $user = currentUser();

        $stats = [
            'events' => 0,
            'albums' => 0,
            'photos' => 0,
            'pending_photos' => 0,
        ];

        if ($user['role'] === 'admin') {
            $stats['events'] = Database::fetch('SELECT COUNT(*) as cnt FROM events')['cnt'];
            $stats['albums'] = Database::fetch('SELECT COUNT(*) as cnt FROM albums')['cnt'];
            $stats['photos'] = Database::fetch("SELECT COUNT(*) as cnt FROM photos WHERE status = 'approved'")['cnt'];
            $stats['pending_photos'] = Database::fetch("SELECT COUNT(*) as cnt FROM photos WHERE status = 'pending'")['cnt'];
        } else {
            $eventIds = Database::fetchAll(
                'SELECT id FROM events WHERE owner_id = ? UNION SELECT event_id FROM event_managers WHERE user_id = ?',
                [$user['id'], $user['id']]
            );
            if (!empty($eventIds)) {
                $ids = implode(',', array_column($eventIds, 'id'));
                $stats['events'] = count($eventIds);
                $stats['albums'] = Database::fetch("SELECT COUNT(*) as cnt FROM albums WHERE event_id IN ($ids)")['cnt'];
                $stats['photos'] = Database::fetch("SELECT COUNT(*) as cnt FROM photos p JOIN albums a ON a.id = p.album_id WHERE a.event_id IN ($ids) AND p.status = 'approved'")['cnt'];
                $stats['pending_photos'] = Database::fetch("SELECT COUNT(*) as cnt FROM photos p JOIN albums a ON a.id = p.album_id WHERE a.event_id IN ($ids) AND p.status = 'pending'")['cnt'];
            }
        }

        if ($user['role'] === 'admin') {
            $recentEvents = Database::fetchAll(
                'SELECT e.*, (SELECT COUNT(*) FROM albums a WHERE a.event_id = e.id) as album_count
                 FROM events e ORDER BY e.created_at DESC LIMIT 6'
            );
        } else {
            $recentEvents = Database::fetchAll(
                'SELECT e.*, (SELECT COUNT(*) FROM albums a WHERE a.event_id = e.id) as album_count
                 FROM events e
                 WHERE e.owner_id = ? OR e.id IN (SELECT event_id FROM event_managers WHERE user_id = ?)
                 ORDER BY e.created_at DESC LIMIT 6',
                [$user['id'], $user['id']]
            );
        }

        view('dashboard.index', [
            'title'        => 'Tableau de bord',
            'stats'        => $stats,
            'recentEvents' => $recentEvents,
        ]);
    }
}
