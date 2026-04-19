-- ============================================
-- SPACEX Trading Academy — Migration 001
-- Add video_access_tokens and payment_webhooks
-- ============================================

USE `spacex_trading`;

-- ---- Payment Webhooks Audit Log ----
CREATE TABLE IF NOT EXISTS `payment_webhooks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(100) NOT NULL,
  `event_id` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT,
  `processed` TINYINT(1) DEFAULT 1,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_event_id` (`event_id`),
  INDEX `idx_received` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---- Video Access Tokens ----
CREATE TABLE IF NOT EXISTS `video_access_tokens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used_count` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  INDEX `idx_token` (`token_hash`),
  INDEX `idx_expires` (`expires_at`),
  INDEX `idx_user_lesson` (`user_id`, `lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---- Add coupon support for future use ----
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percent', 'fixed') DEFAULT 'percent',
  `discount_value` DECIMAL(10, 2) NOT NULL,
  `max_uses` INT UNSIGNED DEFAULT NULL,
  `used_count` INT UNSIGNED DEFAULT 0,
  `valid_from` TIMESTAMP NULL DEFAULT NULL,
  `valid_to` TIMESTAMP NULL DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_code` (`code`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---- Add coupon_id to purchases ----
ALTER TABLE `purchases`
  ADD COLUMN `coupon_id` INT UNSIGNED DEFAULT NULL AFTER `notes`,
  ADD COLUMN `discount_amount` DECIMAL(10, 2) DEFAULT 0 AFTER `coupon_id`;


-- ---- Add video_provider to lessons for CDN/platform flexibility ----
ALTER TABLE `lessons`
  ADD COLUMN `video_provider` ENUM('local', 'youtube', 'vimeo', 'bunny', 'custom') DEFAULT 'local' AFTER `video_url`;
