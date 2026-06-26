-- PicShare Database Schema

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
  `otp_code` VARCHAR(8) NULL,
  `otp_expires_at` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events (grouping container for albums)
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event co-managers
CREATE TABLE IF NOT EXISTS `event_managers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_event_manager` (`event_id`, `user_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invitations (email invitations to join as co-manager)
CREATE TABLE IF NOT EXISTS `invitations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT UNSIGNED NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `status` ENUM('pending','accepted','expired') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Albums
CREATE TABLE IF NOT EXISTS `albums` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `access_token` VARCHAR(32) NOT NULL UNIQUE,
  `upload_start` DATETIME NULL,
  `upload_end` DATETIME NULL,
  `is_ended` TINYINT(1) NOT NULL DEFAULT 0,
  `approval_mode` ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  `slideshow_active` TINYINT(1) NOT NULL DEFAULT 1,
  `qr_logo` VARCHAR(255) NULL COMMENT 'override logo for QR code',
  `qr_color` VARCHAR(7) NOT NULL DEFAULT '#1a1a2e',
  `payment_status` ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `payment_date` DATETIME NULL,
  `download_token` VARCHAR(64) NULL UNIQUE,
  `download_expires_at` DATETIME NULL,
  `delete_scheduled_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Photos
CREATE TABLE IF NOT EXISTS `photos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `album_id` INT UNSIGNED NOT NULL,
  `uploader_user_id` INT UNSIGNED NULL,
  `uploader_name` VARCHAR(150) NOT NULL,
  `uploader_email` VARCHAR(255) NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `stored_filename` VARCHAR(255) NOT NULL,
  `compressed_filename` VARCHAR(255) NULL,
  `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`album_id`) REFERENCES `albums`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploader_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `photo_id` INT UNSIGNED NOT NULL,
  `author_name` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`photo_id`) REFERENCES `photos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactions
CREATE TABLE IF NOT EXISTS `reactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `photo_id` INT UNSIGNED NOT NULL,
  `author_name` VARCHAR(150) NOT NULL,
  `session_key` VARCHAR(64) NOT NULL,
  `type` ENUM('❤️','😂','😮','👏','🔥') NOT NULL DEFAULT '❤️',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_reaction` (`photo_id`, `session_key`),
  FOREIGN KEY (`photo_id`) REFERENCES `photos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Promo codes
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_percent` TINYINT UNSIGNED NULL,
  `discount_fixed` DECIMAL(8,2) NULL,
  `max_uses` INT UNSIGNED NULL,
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATETIME NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `album_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `promo_code_id` INT UNSIGNED NULL,
  `stripe_payment_intent_id` VARCHAR(255) NULL,
  `stripe_session_id` VARCHAR(255) NULL,
  `amount_original` DECIMAL(8,2) NOT NULL,
  `amount_paid` DECIMAL(8,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'eur',
  `status` ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`album_id`) REFERENCES `albums`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT INTO `settings` (`key`, `value`) VALUES
('smtp_host', ''),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_from_email', ''),
('smtp_from_name', 'PicShare'),
('smtp_encryption', 'tls'),
('stripe_public_key', ''),
('stripe_secret_key', ''),
('stripe_webhook_secret', ''),
('album_price', '9.90'),
('watermark_type', 'text'),
('watermark_text', '© PicShare'),
('watermark_image', ''),
('watermark_opacity', '40'),
('watermark_size', '24'),
('site_logo', ''),
('site_tagline', 'Partagez vos souvenirs'),
('max_file_size_mb', '20'),
('max_albums_per_event', '10'),
('maintenance_mode', '0');
