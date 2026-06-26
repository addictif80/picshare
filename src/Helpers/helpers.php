<?php

use PicShare\Core\Session;
use PicShare\Core\Database;

function setting(string $key, mixed $default = ''): mixed
{
    static $cache = [];
    if (!isset($cache[$key])) {
        $row = Database::fetch('SELECT value FROM settings WHERE `key` = ?', [$key]);
        $cache[$key] = $row ? $row['value'] : $default;
    }
    return $cache[$key];
}

function currentUser(): ?array
{
    static $user = null;
    if ($user === null && Session::has('user_id')) {
        $user = Database::fetch('SELECT * FROM users WHERE id = ? AND is_active = 1', [Session::get('user_id')]);
    }
    return $user;
}

function isLoggedIn(): bool
{
    return Session::has('user_id');
}

function isAdmin(): bool
{
    $user = currentUser();
    return $user && $user['role'] === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        abort(403);
    }
}

function redirect(string $url): never
{
    header('Location: ' . BASE_URL . $url);
    exit;
}

function abort(int $code): never
{
    http_response_code($code);
    $file = VIEWS_PATH . "/errors/{$code}.php";
    if (file_exists($file)) require $file;
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return BASE_URL . '/public/' . ltrim($path, '/');
}

function csrfField(): string
{
    $token = Session::csrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e($token) . '">';
}

function flash(string $key, mixed $value): void
{
    Session::flash($key, $value);
}

function getFlash(string $key, mixed $default = null): mixed
{
    return Session::getFlash($key, $default);
}

function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' Go';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' Mo';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' Ko';
    return $bytes . ' o';
}

function formatDate(string $date, string $format = 'd/m/Y H:i'): string
{
    return (new DateTime($date))->format($format);
}

function timeAgo(string $date): string
{
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'à l\'instant';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'j';
    return formatDate($date, 'd/m/Y');
}

function albumUploadOpen(array $album): bool
{
    if ($album['is_ended']) return false;
    $now = time();
    if ($album['upload_start'] && strtotime($album['upload_start']) > $now) return false;
    if ($album['upload_end'] && strtotime($album['upload_end']) < $now) return false;
    return true;
}

function isAlbumManager(array $album, ?array $user = null): bool
{
    $user = $user ?? currentUser();
    if (!$user) return false;
    if ($user['role'] === 'admin') return true;
    $event = Database::fetch('SELECT owner_id FROM events WHERE id = ?', [$album['event_id']]);
    if ($event && $event['owner_id'] == $user['id']) return true;
    $manager = Database::fetch(
        'SELECT id FROM event_managers WHERE event_id = ? AND user_id = ?',
        [$album['event_id'], $user['id']]
    );
    return (bool) $manager;
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $file = VIEWS_PATH . '/' . str_replace('.', '/', $template) . '.php';
    if (!file_exists($file)) {
        throw new \RuntimeException("View not found: {$template}");
    }
    require $file;
}

function partial(string $template, array $data = []): void
{
    view($template, $data);
}

function json(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeFilename(string $filename): string
{
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return substr($filename, 0, 200);
}
