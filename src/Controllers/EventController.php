<?php

namespace PicShare\Controllers;

use PicShare\Core\Database;
use PicShare\Services\MailService;

class EventController
{
    public function index(array $params): void
    {
        requireLogin();
        $user = currentUser();

        if ($user['role'] === 'admin') {
            $events = Database::fetchAll(
                'SELECT e.*, u.name as owner_name,
                 (SELECT COUNT(*) FROM albums a WHERE a.event_id = e.id) as album_count
                 FROM events e JOIN users u ON u.id = e.owner_id
                 ORDER BY e.created_at DESC'
            );
        } else {
            $events = Database::fetchAll(
                'SELECT e.*, u.name as owner_name,
                 (SELECT COUNT(*) FROM albums a WHERE a.event_id = e.id) as album_count
                 FROM events e
                 JOIN users u ON u.id = e.owner_id
                 WHERE e.owner_id = ?
                    OR e.id IN (SELECT event_id FROM event_managers WHERE user_id = ?)
                 ORDER BY e.created_at DESC',
                [$user['id'], $user['id']]
            );
        }

        view('events.index', ['title' => 'Mes événements', 'events' => $events]);
    }

    public function create(array $params): void
    {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
        } else {
            view('events.create', ['title' => 'Nouvel événement']);
        }
    }

    private function store(): void
    {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$title) {
            flash('error', 'Le titre est requis.');
            redirect('/events/create');
        }

        $user = currentUser();
        $eventId = Database::insert(
            'INSERT INTO events (owner_id, title, description) VALUES (?, ?, ?)',
            [$user['id'], $title, $description]
        );

        flash('success', 'Événement créé ! Créez maintenant votre premier album.');
        redirect('/events/' . $eventId);
    }

    public function show(array $params): void
    {
        requireLogin();
        $event = $this->getEventOrAbort($params['id']);
        if (!$this->canAccess($event)) abort(403);

        $albums = Database::fetchAll(
            'SELECT a.*,
             (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) as photo_count
             FROM albums a WHERE a.event_id = ? ORDER BY a.created_at DESC',
            ['approved', $event['id']]
        );

        $managers = Database::fetchAll(
            'SELECT u.id, u.name, u.email FROM event_managers em JOIN users u ON u.id = em.user_id WHERE em.event_id = ?',
            [$event['id']]
        );

        view('events.show', [
            'title'    => $event['title'],
            'event'    => $event,
            'albums'   => $albums,
            'managers' => $managers,
        ]);
    }

    public function invite(array $params): void
    {
        requireLogin();
        $event = $this->getEventOrAbort($params['id']);
        if (!$this->isOwner($event)) abort(403);

        $email = trim(strtolower($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Email invalide.');
            redirect('/events/' . $event['id']);
        }

        // Check if already manager
        $existing = Database::fetch(
            'SELECT u.id FROM users u WHERE u.email = ?', [$email]
        );
        if ($existing) {
            $already = Database::fetch('SELECT id FROM event_managers WHERE event_id = ? AND user_id = ?', [$event['id'], $existing['id']]);
            if ($already || $existing['id'] == $event['owner_id']) {
                flash('error', 'Cette personne gère déjà cet événement.');
                redirect('/events/' . $event['id']);
            }
        }

        $token   = generateToken(32);
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        Database::insert(
            'INSERT INTO invitations (event_id, email, token, expires_at) VALUES (?, ?, ?, ?)',
            [$event['id'], $email, $token, $expires]
        );

        $user = currentUser();
        MailService::sendInvitation($email, $event['title'], $user['name'], $token);

        flash('success', 'Invitation envoyée à ' . $email);
        redirect('/events/' . $event['id']);
    }

    public function acceptInvitation(array $params): void
    {
        $invitation = Database::fetch(
            'SELECT * FROM invitations WHERE token = ? AND status = ?',
            [$params['token'], 'pending']
        );

        if (!$invitation || strtotime($invitation['expires_at']) < time()) {
            flash('error', 'Cette invitation est invalide ou a expiré.');
            redirect('/');
        }

        if (!isLoggedIn()) {
            Session::set('pending_invitation', $params['token']);
            flash('info', 'Connectez-vous ou créez un compte pour accepter cette invitation.');
            redirect('/login');
        }

        $user = currentUser();
        if ($user['email'] !== $invitation['email']) {
            flash('error', 'Cette invitation est destinée à une autre adresse email.');
            redirect('/dashboard');
        }

        Database::execute(
            'INSERT IGNORE INTO event_managers (event_id, user_id) VALUES (?, ?)',
            [$invitation['event_id'], $user['id']]
        );
        Database::execute("UPDATE invitations SET status = 'accepted' WHERE id = ?", [$invitation['id']]);

        flash('success', 'Vous êtes maintenant co-gestionnaire de cet événement !');
        redirect('/events/' . $invitation['event_id']);
    }

    public function removeManager(array $params): void
    {
        requireLogin();
        $event = $this->getEventOrAbort($params['id']);
        if (!$this->isOwner($event)) abort(403);

        Database::execute(
            'DELETE FROM event_managers WHERE event_id = ? AND user_id = ?',
            [$event['id'], $params['user_id']]
        );

        json(['success' => true]);
    }

    private function getEventOrAbort(mixed $id): array
    {
        $event = Database::fetch('SELECT * FROM events WHERE id = ?', [(int) $id]);
        if (!$event) abort(404);
        return $event;
    }

    private function canAccess(array $event): bool
    {
        $user = currentUser();
        if (!$user) return false;
        if ($user['role'] === 'admin') return true;
        if ($event['owner_id'] == $user['id']) return true;
        return (bool) Database::fetch('SELECT id FROM event_managers WHERE event_id = ? AND user_id = ?', [$event['id'], $user['id']]);
    }

    private function isOwner(array $event): bool
    {
        $user = currentUser();
        return $user && ($user['role'] === 'admin' || $event['owner_id'] == $user['id']);
    }
}
