<?php

declare(strict_types=1);

// Bootstrap
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(503);
    die('<html><body style="font-family:sans-serif;text-align:center;padding:80px"><h1>⚙️ Configuration requise</h1><p>Veuillez exécuter <code>composer install</code> sur le serveur avant d\'utiliser l\'application.</p></body></html>');
}

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';

use PicShare\Core\Router;
use PicShare\Core\Session;
use PicShare\Controllers\AuthController;
use PicShare\Controllers\DashboardController;
use PicShare\Controllers\EventController;
use PicShare\Controllers\AlbumController;
use PicShare\Controllers\PhotoController;
use PicShare\Controllers\PaymentController;
use PicShare\Controllers\AdminController;
use PicShare\Controllers\PublicController;

// Start session
Session::start();

// Check if DB is initialized (skip for install route)
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (!str_starts_with(trim($uri, '/'), 'install')) {
    try {
        \PicShare\Core\Database::getInstance();
    } catch (\Exception $e) {
        if (file_exists(__DIR__ . '/install/index.php')) {
            header('Location: /install/');
            exit;
        }
        die('Database not configured. Please run the installer.');
    }
}

// Maintenance mode (allow admin through)
if (setting('maintenance_mode', '0') === '1' && !isAdmin()) {
    $allowed = ['/login', '/login/verify', '/register'];
    $path = '/' . trim(strtok($uri, '?'), '/');
    if (!in_array($path, $allowed)) {
        http_response_code(503);
        die('<html><body style="font-family:sans-serif;text-align:center;padding:80px"><h1>🔧 Maintenance</h1><p>Le site est temporairement indisponible. Revenez bientôt.</p></body></html>');
    }
}

// Router
$router = new Router();
$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_GET['_url'] ?? '/';

// ---- Public ----
$router->get('/',                                  [PublicController::class, 'landing']);
$router->get('/login',                             [AuthController::class, 'showLogin']);
$router->post('/login',                            [AuthController::class, 'postLogin']);
$router->get('/login/verify',                      [AuthController::class, 'showVerify']);
$router->post('/login/verify',                     [AuthController::class, 'postVerify']);
$router->get('/register',                          [AuthController::class, 'showRegister']);
$router->post('/register',                         [AuthController::class, 'postRegister']);
$router->get('/logout',                            [AuthController::class, 'logout']);

// ---- Public album ----
$router->get('/a/{token}',                         [AlbumController::class, 'publicShow']);
$router->get('/a/{token}/slideshow',               [AlbumController::class, 'slideshow']);
$router->get('/a/{token}/feed',                    [AlbumController::class, 'slideshowFeed']);
$router->post('/upload/{token}',                   [PhotoController::class, 'upload']);
$router->post('/guest-name/{token}',               [PublicController::class, 'setGuestName']);
$router->get('/photo/{id}/view',                   [AlbumController::class, 'servePhoto']);
$router->get('/photo/{id}/details',                [PublicController::class, 'photoDetails']);
$router->post('/photo/{id}/comment',               [PhotoController::class, 'comment']);
$router->post('/photo/{id}/react',                 [PhotoController::class, 'react']);
$router->get('/qr/{token}',                        [PublicController::class, 'serveQR']);

// ---- Invitations ----
$router->get('/invitation/{token}',                [EventController::class, 'acceptInvitation']);

// ---- Download ----
$router->get('/download/{token}',                  [AlbumController::class, 'download']);

// ---- Dashboard ----
$router->get('/dashboard',                         [DashboardController::class, 'index']);

// ---- Events ----
$router->get('/events',                            [EventController::class, 'index']);
$router->get('/events/create',                     [EventController::class, 'create']);
$router->post('/events/create',                    [EventController::class, 'create']);
$router->get('/events/{id}',                       [EventController::class, 'show']);
$router->post('/events/{id}/invite',               [EventController::class, 'invite']);
$router->delete('/events/{id}/managers/{user_id}', [EventController::class, 'removeManager']);

// ---- Albums ----
$router->get('/events/{event_id}/albums/create',   [AlbumController::class, 'create']);
$router->post('/events/{event_id}/albums/create',  [AlbumController::class, 'create']);
$router->get('/albums/{id}/manage',                [AlbumController::class, 'show']);
$router->post('/albums/{id}/end',                  [AlbumController::class, 'endEvent']);
$router->post('/albums/{id}/settings',             [AlbumController::class, 'updateSettings']);
$router->get('/albums/{id}/checkout',              [PaymentController::class, 'checkout']);
$router->get('/albums/{id}/qr/regenerate',         [AlbumController::class, 'regenerateQRAction']);

// ---- Photos (manager actions) ----
$router->post('/photos/{photo_id}/approve/{status}', [AlbumController::class, 'approvePhoto']);
$router->delete('/photos/{photo_id}',              [AlbumController::class, 'deletePhoto']);

// ---- Payment ----
$router->post('/payment/{id}/session',             [PaymentController::class, 'createSession']);
$router->get('/payment/success',                   [PaymentController::class, 'success']);
$router->post('/webhook/stripe',                   [PaymentController::class, 'webhook']);
$router->post('/payment/promo',                    [PaymentController::class, 'validatePromo']);

// ---- Admin ----
$router->get('/admin',                             [AdminController::class, 'dashboard']);
$router->get('/admin/settings',                    [AdminController::class, 'settings']);
$router->post('/admin/settings',                   [AdminController::class, 'settings']);
$router->get('/admin/users',                       [AdminController::class, 'users']);
$router->post('/admin/users/{id}/toggle',          [AdminController::class, 'toggleUser']);
$router->get('/admin/promo-codes',                 [AdminController::class, 'promoCodes']);
$router->post('/admin/promo-codes',                [AdminController::class, 'promoCodes']);
$router->post('/admin/promo-codes/{id}/toggle',    [AdminController::class, 'togglePromo']);
$router->delete('/admin/promo-codes/{id}',         [AdminController::class, 'deletePromo']);
$router->post('/admin/cleanup',                    [AdminController::class, 'cleanup']);

// Dispatch
$router->dispatch($method, $uri);
