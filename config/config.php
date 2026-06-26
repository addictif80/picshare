<?php

if (!defined('APP_NAME'))             define('APP_NAME', 'PicShare');
if (!defined('APP_VERSION'))          define('APP_VERSION', '1.0.0');
if (!defined('BASE_PATH'))            define('BASE_PATH', dirname(__DIR__));
if (!defined('BASE_URL'))             define('BASE_URL', rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/'));

// Paths
if (!defined('UPLOAD_PATH'))          define('UPLOAD_PATH', BASE_PATH . '/public/uploads');
if (!defined('STORAGE_PATH'))         define('STORAGE_PATH', BASE_PATH . '/storage');
if (!defined('VIEWS_PATH'))           define('VIEWS_PATH', BASE_PATH . '/views');

// Session
if (!defined('SESSION_LIFETIME'))     define('SESSION_LIFETIME', 86400 * 30);
if (!defined('OTP_LIFETIME'))         define('OTP_LIFETIME', 600);

// Image processing
if (!defined('COMPRESSED_MAX_WIDTH')) define('COMPRESSED_MAX_WIDTH', 1920);
if (!defined('COMPRESSED_MAX_HEIGHT'))define('COMPRESSED_MAX_HEIGHT', 1920);
if (!defined('COMPRESSED_QUALITY'))   define('COMPRESSED_QUALITY', 85);

// Cleanup
if (!defined('ARCHIVE_LIFETIME_DAYS'))define('ARCHIVE_LIFETIME_DAYS', 90);

// Security
if (!defined('CSRF_TOKEN_NAME'))      define('CSRF_TOKEN_NAME', '_csrf_token');

// Timezone
date_default_timezone_set('Europe/Paris');
