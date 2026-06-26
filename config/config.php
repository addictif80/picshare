<?php

define('APP_NAME', 'PicShare');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/'));

// Paths
define('UPLOAD_PATH', BASE_PATH . '/public/uploads');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('VIEWS_PATH', BASE_PATH . '/views');

// Session
define('SESSION_LIFETIME', 86400 * 30); // 30 days
define('OTP_LIFETIME', 600); // 10 minutes

// Image processing
define('COMPRESSED_MAX_WIDTH', 1920);
define('COMPRESSED_MAX_HEIGHT', 1920);
define('COMPRESSED_QUALITY', 85);

// Cleanup
define('ARCHIVE_LIFETIME_DAYS', 90); // 3 months

// Security
define('CSRF_TOKEN_NAME', '_csrf_token');

// Timezone
date_default_timezone_set('Europe/Paris');

// Load environment or database settings will override at runtime
