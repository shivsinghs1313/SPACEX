-- ============================================
-- SPACEX Trading Academy — Database Schema
-- MySQL 5.7+ / MariaDB 10.3+
-- ============================================

CREATE DATABASE IF NOT EXISTS `spacex_trading`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `spacex_trading`;

-- ---- Users Table ----
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') DEFAULT 'student',
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---- Courses Table ----
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `discount_price` DECIMAL(10, 2) DEFAULT NULL,
  `currency` VARCHAR(3) DEFAULT 'INR',
  `thumbnail_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `total_duration` VARCHAR(50) DEFAULT NULL,
  `total_lessons` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---- Lessons Table ----
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `module_name` VARCHAR(255) NOT NULL,
  `module_order` INT UNSIGNED DEFAULT 0,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `video_url` VARCHAR(500) DEFAULT NULL,
  `video_duration` VARCHAR(20) DEFAULT NULL,
  `pdf_url` VARCHAR(500) DEFAULT NULL,
  `sort_order` INT UNSIGNED DEFAULT 0,
  `is_preview` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  INDEX `idx_course` (`course_id`),
  INDEX `idx_sort` (`sort_order`),
  INDEX `idx_module` (`module_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---- Purchases Table ----
CREATE TABLE IF NOT EXISTS `purchases` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'INR',
  `payment_gateway` ENUM('razorpay', 'stripe', 'manual') DEFAULT 'razorpay',
  `payment_id` VARCHAR(255) DEFAULT NULL,
  `payment_order_id` VARCHAR(255) DEFAULT NULL,
  `payment_signature` VARCHAR(255) DEFAULT NULL,
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `receipt` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_course` (`course_id`),
  INDEX `idx_status` (`payment_status`),
  INDEX `idx_payment_id` (`payment_id`),
  UNIQUE INDEX `idx_user_course_paid` (`user_id`, `course_id`, `payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---- Progress Table ----
CREATE TABLE IF NOT EXISTS `progress` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NOT NULL,
  `completed` TINYINT(1) DEFAULT 0,
  `watch_time` INT UNSIGNED DEFAULT 0,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  UNIQUE INDEX `idx_user_lesson` (`user_id`, `lesson_id`),
  INDEX `idx_completed` (`completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---- Auth Tokens Table (for JWT/session management) ----
CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_token` (`token_hash`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- Seed Data
-- ============================================

-- Admin user (password: Admin@123)
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
('Admin', 'admin@spacextrading.com', '$2y$12$LJ3m4ysLpT7FUnuUFvRRpOaX.Y7V0.4N3JlJpB8pLTXx6jXGjHxVe', 'admin');

-- Demo course
INSERT INTO `courses` (`title`, `slug`, `description`, `short_description`, `price`, `discount_price`, `currency`, `status`, `total_duration`, `total_lessons`) VALUES
(
  'SPACEX Complete Trading System',
  'spacex-complete-trading-system',
  'The complete SPACEX trading system — from market fundamentals to professional-level execution. 6 comprehensive modules covering price action, risk management, trading psychology, and advanced strategies.',
  'Master the markets with a proven, rules-based trading system.',
  14999.00,
  4999.00,
  'INR',
  'published',
  '40+ Hours',
  30
);

-- Demo lessons
INSERT INTO `lessons` (`course_id`, `module_name`, `module_order`, `title`, `description`, `video_duration`, `sort_order`) VALUES
(1, 'Market Foundations & Structure', 1, 'How Markets Actually Work', 'Understanding market microstructure beyond the basics', '18:30', 1),
(1, 'Market Foundations & Structure', 1, 'Market Structure: Trends & Ranges', 'Identifying trend phases and range-bound markets', '24:15', 2),
(1, 'Market Foundations & Structure', 1, 'Liquidity & Order Flow', 'Reading institutional footprints in the market', '31:42', 3),
(1, 'Market Foundations & Structure', 1, 'Chart Setup & Configuration', 'Professional workspace and tools setup', '15:20', 4),
(1, 'Market Foundations & Structure', 1, 'Reading Price Like a Pro', 'Advanced price reading techniques', '28:10', 5),
(1, 'Price Action Mastery', 2, 'Candlestick Psychology', 'What each pattern truly means', '26:45', 6),
(1, 'Price Action Mastery', 2, 'Support & Resistance Levels', 'Dynamic and static levels', '33:20', 7),
(1, 'Price Action Mastery', 2, 'Chart Patterns That Work', 'Proven patterns for entries', '29:55', 8),
(1, 'Price Action Mastery', 2, 'Multi-Timeframe Analysis', 'Framework for timeframe alignment', '35:10', 9),
(1, 'Price Action Mastery', 2, 'Live Market Analysis Session', 'Real-time chart analysis', '45:30', 10);
