-- ============================================================================
-- PowerMagz Gym — schema.sql
-- Full schema + seed data.
-- Requires: MySQL 8.0+ / MariaDB 10.2+
-- Charset:  utf8mb4 / utf8mb4_unicode_ci
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `powermagz_gym`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `powermagz_gym`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop order: children before parents.
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `enrollment_events`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `payout_batches`;
DROP TABLE IF EXISTS `system_settings`;

DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `b2b_clients`;
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `member_profiles`;
DROP TABLE IF EXISTS `partners`;
DROP TABLE IF EXISTS `partner_profiles`;
DROP TABLE IF EXISTS `partner_sales`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `payout_requests`;
DROP TABLE IF EXISTS `programs`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================================
-- 1. users
-- ============================================================================
CREATE TABLE `users` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(190) DEFAULT NULL,
  `role` enum('admin','staff','user','partner') NOT NULL DEFAULT 'user',
  `avatar_url` varchar(500) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,

  -- Inclusive profile fields
  `title` enum('mr','ms','mx','dr','none') NOT NULL DEFAULT 'none',
  `gender` enum('male','female','non_binary','self_describe','prefer_not_to_say')
              NOT NULL DEFAULT 'prefer_not_to_say',
  `gender_self_describe` varchar(100) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,

  -- MFA
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mfa_secret` varchar(64) DEFAULT NULL,
  `mfa_recovery_codes` json DEFAULT NULL,
  `mfa_verified_at` datetime DEFAULT NULL,

  -- Session hygiene
  `last_login_at` datetime DEFAULT NULL,
  `idle_timeout_minutes` int(11) NOT NULL DEFAULT 30,

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. sessions
-- ============================================================================
CREATE TABLE `sessions` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) NOT NULL,
  `token` char(64) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `expires_at` datetime NOT NULL,

  `last_activity_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reauth_until` datetime DEFAULT NULL,

  `created_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. member_profiles
-- ============================================================================
CREATE TABLE `member_profiles` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `emergency_contact` varchar(190) DEFAULT NULL,
  `fitness_goal` varchar(190) DEFAULT NULL,
  `sessions_left` int(11) NOT NULL DEFAULT 12,

  `emergency_relationship` varchar(80) DEFAULT NULL,
  `pref_contact_method` enum('email','sms','phone','any') NOT NULL DEFAULT 'any',

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. partner_profiles
-- ============================================================================
CREATE TABLE `partner_profiles` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) NOT NULL,
  `business_name` varchar(190) NOT NULL,
  `partner_type` enum('coach','supplier') NOT NULL DEFAULT 'coach',
  `phone` varchar(40) DEFAULT NULL,
  `referral_code` varchar(40) NOT NULL,

  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `contact_email` varchar(190) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `acquisition_source` varchar(80) DEFAULT NULL,

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. partners  (legacy)
-- ============================================================================
CREATE TABLE `partners` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `name` varchar(190) NOT NULL,
  `type` enum('coach','supplier') NOT NULL,
  `contact` varchar(190) DEFAULT NULL,
  `sales_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `referrals_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. b2b_clients
-- ============================================================================
CREATE TABLE `b2b_clients` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `partner_user_id` char(36) NOT NULL,
  `client_user_id` char(36) DEFAULT NULL,
  `client_name` varchar(190) NOT NULL,
  `client_email` varchar(190) NOT NULL,
  `membership_status` enum('linked','pending','inactive') NOT NULL DEFAULT 'pending',
  `last_check_in` date DEFAULT NULL,

  `contact_phone` varchar(40) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `invited_at` datetime DEFAULT NULL,
  `linked_at` datetime DEFAULT NULL,

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. programs
-- ============================================================================
CREATE TABLE `programs` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `name` varchar(190) NOT NULL,
  `tier` enum('digital','standard','premium','b2b') NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `duration_days` int(11) NOT NULL DEFAULT 30,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,

  `icon` varchar(20) NOT NULL DEFAULT '🏋️',
  `features` json DEFAULT NULL,
  `operational_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `portal_visible` tinyint(1) NOT NULL DEFAULT 1,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `grace_period_days` int(11) NOT NULL DEFAULT 3,
  `sort_order` int(11) NOT NULL DEFAULT 0,

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. enrollments (🔐 FIXED: Added 'b2b' to tier enum)
-- ============================================================================
CREATE TABLE `enrollments` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) NOT NULL,
  `user_name` varchar(190) NOT NULL,
  `program_id` char(36) DEFAULT NULL,
  `program_name` varchar(190) DEFAULT NULL,
  `tier` enum('digital','standard','premium','b2b') NOT NULL,
  `status` enum('active','pending','expired','paused','cancelled') NOT NULL DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,

  `cancel_reason` enum('member_request','non_payment','relocation','other') DEFAULT NULL,
  `cancelled_date` datetime DEFAULT NULL,
  `cancelled_by` char(36) DEFAULT NULL,
  `paused_date` datetime DEFAULT NULL,
  `paused_by` char(36) DEFAULT NULL,
  `resume_date` datetime DEFAULT NULL,
  `previous_tier` enum('digital','standard','premium','b2b') DEFAULT NULL,
  `renewed_from_id` char(36) DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `grace_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. payments
-- ============================================================================
CREATE TABLE `payments` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) DEFAULT NULL,
  `user_name` varchar(190) NOT NULL,
  `source` enum('b2c','b2b') NOT NULL DEFAULT 'b2c',
  `tier` enum('digital','standard','premium','b2b') DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('gcash','cash','credit_card','bank_transfer') NOT NULL DEFAULT 'gcash',
  `status` enum('paid','pending','failed','refunded','partial_refund') NOT NULL DEFAULT 'pending',
  `paid_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,

  `reference_no` varchar(80) DEFAULT NULL,
  `payment_token` varchar(120) DEFAULT NULL,
  `receipt_no` varchar(40) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `confirmed_by` char(36) DEFAULT NULL,
  `confirmed_date` datetime DEFAULT NULL,
  `failed_reason` varchar(255) DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `refund_date` datetime DEFAULT NULL,
  `refunded_by` char(36) DEFAULT NULL,
  `notes` text DEFAULT NULL,

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. attendance
-- ============================================================================
CREATE TABLE `attendance` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) DEFAULT NULL,
  `user_name` varchar(190) NOT NULL,
  `date` date NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `hours` decimal(5,1) NOT NULL DEFAULT 0.0,

  `tier` enum('digital','standard','premium','b2b') DEFAULT NULL,
  `source` enum('qr','manual') NOT NULL DEFAULT 'qr',
  `manual_override_by` char(36) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,

  `created_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. partner_sales
-- ============================================================================
CREATE TABLE `partner_sales` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `partner_user_id` char(36) NOT NULL,
  `type` enum('product','client_payment') NOT NULL DEFAULT 'product',
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `sale_date` date NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',

  `commission_rate` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `approved_by` char(36) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `rejected_reason` varchar(255) DEFAULT NULL,
  `client_id` char(36) DEFAULT NULL,

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. payout_requests
-- ============================================================================
CREATE TABLE `payout_requests` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `partner_user_id` char(36) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_date` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,

  `method` enum('gcash','bank_transfer','cash') NOT NULL DEFAULT 'gcash',
  `method_details` varchar(255) DEFAULT NULL,
  `batch_id` char(36) DEFAULT NULL,
  `processed_date` datetime DEFAULT NULL,
  `processed_by` char(36) DEFAULT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,

  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. password_resets
-- ============================================================================
CREATE TABLE `password_resets` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. audit_logs
-- ============================================================================
CREATE TABLE `audit_logs` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) DEFAULT NULL,
  `user_role` enum('admin','staff','partner','user','system') NOT NULL DEFAULT 'system',
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `before_data` json DEFAULT NULL,
  `after_data` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 15. notifications
-- ============================================================================
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `user_id` char(36) NOT NULL,
  `tone` enum('info','warn','success','error') NOT NULL DEFAULT 'info',
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 16. payout_batches
-- ============================================================================
CREATE TABLE `payout_batches` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `processed_by` char(36) DEFAULT NULL,
  `method` enum('gcash','bank_transfer','cash','mixed') NOT NULL DEFAULT 'mixed',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `request_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `notes` varchar(255) DEFAULT NULL,
  `processed_date` datetime DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 17. system_settings
-- ============================================================================
CREATE TABLE `system_settings` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','int','bool','json') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` char(36) DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
                                ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 18. enrollment_events
-- ============================================================================
CREATE TABLE `enrollment_events` (
  `id` char(36) NOT NULL DEFAULT UUID(),
  `enrollment_id` char(36) NOT NULL,
  `event_type` enum(
      'created','approved','extended','paused','resumed',
      'upgraded','downgraded','cancelled','expired','renewed'
  ) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) DEFAULT NULL,
  `from_tier` varchar(30) DEFAULT NULL,
  `to_tier` varchar(30) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `performed_by` char(36) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
INSERT INTO `users`
  (`id`,`email`,`password_hash`,`full_name`,`role`,`avatar_url`,
   `email_verified`,`otp_code`,`otp_expires_at`,
   `title`,`gender`,`gender_self_describe`,`phone`,
   `mfa_enabled`,`mfa_secret`,`mfa_recovery_codes`,`mfa_verified_at`,
   `last_login_at`,`idle_timeout_minutes`,
   `created_date`,`updated_date`)
VALUES
('01211b4d-1be2-4045-be2c-2010441f13ea','partner@test.com',
 '$2y$10$7aixACkzdwM/wmDn/PtovuPTWeO9FtlLIxQQMXsBVJFKDMhGL6tqW',
 'Test Partner','partner',NULL,1,NULL,NULL,
 'none','prefer_not_to_say',NULL,NULL,
 0,NULL,NULL,NULL,NULL,30,
 '2026-09-17 17:34:25','2026-09-17 17:34:25'),

('7f3e4643-47ff-469e-9bdb-91ceaaa1b01e','member@test.com',
 '$2y$10$vwvpd6GvHIXHAIK7n5SIc.YT2VR0FnSFvLxYWSnzVj2eO4ynBxvfm',
 'Test Member','user',NULL,1,NULL,NULL,
 'none','prefer_not_to_say',NULL,NULL,
 0,NULL,NULL,NULL,NULL,30,
 '2026-09-17 17:17:42','2026-09-17 17:34:25'),

('94b25041-b263-11f1-a728-40c2bab22b88','admin@powermagz.ph',
 '$2y$10$rWHzFqBP3wASVLXsoPYmJuNqXahf/VZN48N8jGD56bXSRsR9zVnqS',
 'PowerMagz Admin','admin',NULL,1,NULL,NULL,
 'none','prefer_not_to_say',NULL,NULL,
 0,NULL,NULL,NULL,NULL,30,
 '2026-09-17 14:46:59','2026-09-17 17:07:04');

-- ---------------------------------------------------------------------------
-- sessions
-- ---------------------------------------------------------------------------
INSERT INTO `sessions`
  (`id`,`user_id`,`token`,`user_agent`,`ip_address`,`expires_at`,
   `last_activity_at`,`reauth_until`,`created_date`)
VALUES
('105132bc-ad4b-4296-b44f-3a9361e38f13','7f3e4643-47ff-469e-9bdb-91ceaaa1b01e',
 '4768b0cc83f751b72564de7345b77aa735edf03619abd81acfb7fd5c31a3a396',
 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36',
 '::1','2026-10-20 23:17:08','2026-09-20 23:17:08',NULL,'2026-09-20 23:17:08');

-- ---------------------------------------------------------------------------
-- member_profiles
-- ---------------------------------------------------------------------------
INSERT INTO `member_profiles`
  (`id`,`user_id`,`phone`,`address`,`birth_date`,`emergency_contact`,
   `fitness_goal`,`sessions_left`,
   `emergency_relationship`,`pref_contact_method`,
   `created_date`,`updated_date`)
VALUES
('63389267-b19a-410a-bcfa-5d63a80898c7','7f3e4643-47ff-469e-9bdb-91ceaaa1b01e',
 NULL,NULL,NULL,NULL,NULL,12,
 NULL,'any',
 '2026-09-17 17:30:07','2026-09-17 17:30:07');

-- ---------------------------------------------------------------------------
-- partner_profiles
-- ---------------------------------------------------------------------------
INSERT INTO `partner_profiles`
  (`id`,`user_id`,`business_name`,`partner_type`,`phone`,`referral_code`,
   `status`,`commission_rate`,`contact_email`,`address`,`notes`,`acquisition_source`,
   `created_date`,`updated_date`)
VALUES
('ecb53365-58fc-429b-857b-ef1433942ad2','01211b4d-1be2-4045-be2c-2010441f13ea',
 'Test Partner Coaching','coach',NULL,'PMZ-711C21',
 'active',10.00,NULL,NULL,NULL,'seed',
 '2026-09-17 17:34:25','2026-09-17 17:34:25');

-- ---------------------------------------------------------------------------
-- programs (🔐 FIXED: Proper UTF-8 Emojis)
-- ---------------------------------------------------------------------------
INSERT INTO `programs`
  (`id`,`name`,`tier`,`price`,`duration_days`,`description`,`active`,
   `icon`,`features`,`operational_cost`,`portal_visible`,`auto_renew`,
   `grace_period_days`,`sort_order`,
   `created_date`,`updated_date`)
VALUES
('94afe956-b263-11f1-a728-40c2bab22b88','Digital Access','digital',500.00,30,
 'Digital workout system, QR time in/out, attendance calendar, AI workout recommender.',1,
 '📱',
 JSON_ARRAY('Digital workout system','QR time in/out','Attendance calendar','AI workout recommender'),
 0.00,1,0,3,1,
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

('94affcef-b263-11f1-a728-40c2bab22b88','Standard Membership','standard',1000.00,30,
 'Full gym access, QR check-in, attendance tracking, group class booking.',1,
 '⚡',
 JSON_ARRAY('Full gym access','QR check-in','Attendance tracking','Group class booking','GCash payments'),
 0.00,1,0,3,2,
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

('94afff8c-b263-11f1-a728-40c2bab22b88','Premium All-Inclusive','premium',2000.00,30,
 'Everything in Standard + personal coaching + nutrition + 24/7 AI coach.',1,
 '👑',
 JSON_ARRAY('Everything in Standard','Personal coaching','Nutrition guidance','Priority class booking','AI coach 24/7'),
 0.00,1,0,3,3,
 '2026-09-17 14:46:59','2026-09-17 14:46:59');

-- ---------------------------------------------------------------------------
-- enrollments
-- ---------------------------------------------------------------------------
INSERT INTO `enrollments`
  (`id`,`user_id`,`user_name`,`program_id`,`program_name`,`tier`,`status`,
   `start_date`,`end_date`,`balance`,
   `cancel_reason`,`cancelled_date`,`cancelled_by`,
   `paused_date`,`paused_by`,`resume_date`,
   `previous_tier`,`renewed_from_id`,`auto_renew`,`grace_until`,`notes`,
   `created_date`,`updated_date`)
VALUES
('771495c1-fa7d-46f1-aabf-8d92f8607132','7f3e4643-47ff-469e-9bdb-91ceaaa1b01e',
 'Test Member','94afff8c-b263-11f1-a728-40c2bab22b88','Premium All-Inclusive',
 'premium','active','2026-09-20','2026-10-20',0.00,
 NULL,NULL,NULL,
 NULL,NULL,NULL,
 NULL,NULL,0,NULL,NULL,
 '2026-09-20 23:06:53','2026-09-20 23:07:27');

-- ---------------------------------------------------------------------------
-- payments
-- ---------------------------------------------------------------------------
INSERT INTO `payments`
  (`id`,`user_id`,`user_name`,`source`,`tier`,`amount`,`method`,`status`,
   `paid_date`,`description`,
   `reference_no`,`payment_token`,`receipt_no`,`due_date`,
   `confirmed_by`,`confirmed_date`,`failed_reason`,
   `refund_amount`,`refund_reason`,`refund_date`,`refunded_by`,`notes`,
   `created_date`,`updated_date`)
VALUES
('c063afda-4a55-4888-a339-703b552f11d7','7f3e4643-47ff-469e-9bdb-91ceaaa1b01e',
 'Test Member','b2c','premium',2000.00,'gcash','paid',
 '2026-09-20','Premium All-Inclusive enrollment',
 NULL,NULL,NULL,NULL,
 NULL,NULL,NULL,
 NULL,NULL,NULL,NULL,NULL,
 '2026-09-20 23:06:53','2026-09-20 23:07:27');

-- ---------------------------------------------------------------------------
-- audit_logs
-- ---------------------------------------------------------------------------
INSERT INTO `audit_logs`
  (`id`,`user_id`,`user_role`,`action`,`entity_type`,`entity_id`,
   `before_data`,`after_data`,`ip_address`,`user_agent`,`notes`,`created_date`)
VALUES
(UUID(),'94b25041-b263-11f1-a728-40c2bab22b88','admin',
 'seed.system.initialized','system',NULL,
 NULL,JSON_OBJECT('version','1.0','note','schema.sql bootstrap'),
 '::1','seed','Initial database setup','2026-09-17 14:46:59');

-- ---------------------------------------------------------------------------
-- system_settings
-- ---------------------------------------------------------------------------
INSERT INTO `system_settings`
  (`id`,`setting_key`,`setting_value`,`setting_type`,`description`,
   `updated_by`,`created_date`,`updated_date`)
VALUES
(UUID(),'accepted_payment_methods',
 JSON_ARRAY('gcash','cash','credit_card','bank_transfer'),
 'json','Payment methods shown to members at enrollment.',
 '94b25041-b263-11f1-a728-40c2bab22b88',
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

(UUID(),'payment_collection_frequency','monthly','string',
 'How often recurring payments are collected.',
 '94b25041-b263-11f1-a728-40c2bab22b88',
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

(UUID(),'late_payment_grace_days','7','int',
 'Days after due date before a payment is considered late.',
 '94b25041-b263-11f1-a728-40c2bab22b88',
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

(UUID(),'enrollment_grace_period_days','3','int',
 'Days after enrollment end date before account lockout.',
 '94b25041-b263-11f1-a728-40c2bab22b88',
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

(UUID(),'operational_cost_per_member','300','int',
 'Floor warning threshold: prices below this trigger an admin warning.',
 '94b25041-b263-11f1-a728-40c2bab22b88',
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

(UUID(),'default_commission_rate','10','int',
 'Default B2B partner commission percentage (10-15% per spec).',
 '94b25041-b263-11f1-a728-40c2bab22b88',
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

(UUID(),'gym_capacity','50','int',
 'Maximum simultaneous occupancy for utilization calculations.',
 '94b25041-b263-11f1-a728-40c2bab22b88',
 '2026-09-17 14:46:59','2026-09-17 14:46:59'),

(UUID(),'idle_timeout_minutes','30','int',
 'Minutes of inactivity before session is invalidated.',
 '94b25041-b263-11f1-a728-40c2bab22b88',
 '2026-09-17 14:46:59','2026-09-17 14:46:59');

-- ---------------------------------------------------------------------------
-- enrollment_events
-- ---------------------------------------------------------------------------
INSERT INTO `enrollment_events`
  (`id`,`enrollment_id`,`event_type`,`from_status`,`to_status`,
   `from_tier`,`to_tier`,`reason`,`performed_by`,`notes`,`created_date`)
VALUES
(UUID(),'771495c1-fa7d-46f1-aabf-8d92f8607132','created',
 NULL,'pending',NULL,'premium',NULL,
 '7f3e4643-47ff-469e-9bdb-91ceaaa1b01e',
 'Initial enrollment by member','2026-09-20 23:06:53'),

(UUID(),'771495c1-fa7d-46f1-aabf-8d92f8607132','approved',
 'pending','active','premium','premium',NULL,
 '94b25041-b263-11f1-a728-40c2bab22b88',
 'Payment confirmed - activated','2026-09-20 23:07:27');

-- ---------------------------------------------------------------------------
-- notifications
-- ---------------------------------------------------------------------------
INSERT INTO `notifications`
  (`id`,`user_id`,`tone`,`title`,`description`,`link`,`read_at`,`created_date`)
VALUES
(UUID(),'94b25041-b263-11f1-a728-40c2bab22b88','info',
 'Welcome to the Admin Panel',
 'Review the Overview tab for today''s metrics.',
 '/admin-dashboard?tab=overview',NULL,'2026-09-17 14:47:00');

-- ============================================================================
-- INDEXES
-- ============================================================================

-- ---- users ----
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_created` (`created_date`);

-- ---- sessions ----
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sessions_token` (`token`),
  ADD KEY `idx_sessions_user` (`user_id`),
  ADD KEY `idx_sessions_activity` (`last_activity_at`);

-- ---- member_profiles ----
ALTER TABLE `member_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_member_profile_user` (`user_id`);

-- ---- partner_profiles ----
ALTER TABLE `partner_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_partner_profile_user` (`user_id`),
  ADD UNIQUE KEY `uniq_partner_referral` (`referral_code`),
  ADD KEY `idx_partner_status` (`status`);

-- ---- partners (legacy) ----
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`);

-- ---- b2b_clients ----
ALTER TABLE `b2b_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_b2b_partner` (`partner_user_id`),
  ADD KEY `idx_b2b_client` (`client_user_id`),
  ADD KEY `idx_b2b_status` (`membership_status`);

-- ---- programs ----
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_program_name` (`name`),
  ADD KEY `idx_programs_tier` (`tier`),
  ADD KEY `idx_programs_active` (`active`),
  ADD KEY `idx_programs_portal_visible` (`portal_visible`),
  ADD KEY `idx_programs_sort` (`sort_order`);

-- ---- enrollments ----
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_enroll_user` (`user_id`),
  ADD KEY `idx_enroll_status` (`status`),
  ADD KEY `idx_enroll_tier` (`tier`),
  ADD KEY `fk_enroll_program` (`program_id`),
  ADD KEY `idx_enroll_end_date` (`end_date`),
  ADD KEY `idx_enroll_renewed_from` (`renewed_from_id`);

-- ---- payments ----
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_user` (`user_id`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_date` (`paid_date`),
  ADD KEY `idx_payments_tier` (`tier`),
  ADD KEY `idx_payments_due_date` (`due_date`),
  ADD KEY `idx_payments_reference` (`reference_no`);

-- ---- attendance ----
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_att_user` (`user_id`),
  ADD KEY `idx_att_date` (`date`),
  ADD KEY `idx_att_tier` (`tier`),
  ADD KEY `idx_att_source` (`source`);

-- ---- partner_sales ----
ALTER TABLE `partner_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sales_partner` (`partner_user_id`),
  ADD KEY `idx_sales_status` (`status`),
  ADD KEY `idx_sales_client` (`client_id`);

-- ---- payout_requests ----
ALTER TABLE `payout_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payout_partner` (`partner_user_id`),
  ADD KEY `idx_payout_status` (`status`),
  ADD KEY `idx_payout_batch` (`batch_id`),
  ADD KEY `idx_payout_method` (`method`);

-- ---- password_resets ----
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_reset_token` (`token`),
  ADD KEY `idx_reset_user` (`user_id`);

-- ---- audit_logs ----
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_created` (`created_date`);

-- ---- notifications ----
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user_unread` (`user_id`,`read_at`),
  ADD KEY `idx_notif_created` (`created_date`);

-- ---- payout_batches ----
ALTER TABLE `payout_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_batch_status` (`status`),
  ADD KEY `idx_batch_processed_by` (`processed_by`);

-- ---- system_settings ----
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_setting_key` (`setting_key`);

-- ---- enrollment_events ----
ALTER TABLE `enrollment_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eve_enrollment` (`enrollment_id`),
  ADD KEY `idx_eve_type` (`event_type`),
  ADD KEY `idx_eve_created` (`created_date`);

-- ============================================================================
-- FOREIGN KEY CONSTRAINTS
-- ============================================================================

ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `member_profiles`
  ADD CONSTRAINT `fk_member_profile_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `partner_profiles`
  ADD CONSTRAINT `fk_partner_profile_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `b2b_clients`
  ADD CONSTRAINT `fk_b2b_client` FOREIGN KEY (`client_user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_b2b_partner` FOREIGN KEY (`partner_user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_enroll_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enroll_program` FOREIGN KEY (`program_id`)
    REFERENCES `programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enroll_renewed_from` FOREIGN KEY (`renewed_from_id`)
    REFERENCES `enrollments` (`id`) ON DELETE SET NULL;

ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `partner_sales`
  ADD CONSTRAINT `fk_sales_partner` FOREIGN KEY (`partner_user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sales_client` FOREIGN KEY (`client_id`)
    REFERENCES `b2b_clients` (`id`) ON DELETE SET NULL;

ALTER TABLE `payout_requests`
  ADD CONSTRAINT `fk_payout_partner` FOREIGN KEY (`partner_user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payout_batch` FOREIGN KEY (`batch_id`)
    REFERENCES `payout_batches` (`id`) ON DELETE SET NULL;

ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `enrollment_events`
  ADD CONSTRAINT `fk_eve_enrollment` FOREIGN KEY (`enrollment_id`)
    REFERENCES `enrollments` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;