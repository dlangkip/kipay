-- Database Schema for Kipay Payment Gateway

-- Transactions Table
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) NOT NULL,
  `tracking_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'KES',
  `description` text,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `payment_method` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_id` varchar(50) DEFAULT NULL,
  `invoice_id` varchar(50) DEFAULT NULL,
  `metadata` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `status` (`status`),
  KEY `customer_email` (`customer_email`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Webhooks Table
CREATE TABLE `webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `secret` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `events` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Keys Table
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `permissions` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `last_used` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications Table
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text,
  `reference` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reference` (`reference`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings Table
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Logs Table
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `is_public`, `created_at`, `updated_at`)
VALUES
  ('gateway_name', 'Kipay Gateway', 'Name of the payment gateway', 1, NOW(), NULL),
  ('gateway_description', 'Kipay payment gateway using Pesapal API', 'Description of the payment gateway', 1, NOW(), NULL),
  ('support_email', 'support@benfex.net', 'Support email address', 1, NOW(), NULL),
  ('support_phone', '+254700760386', 'Support phone number', 1, NOW(), NULL),
  ('default_currency', 'KES', 'Default currency code', 1, NOW(), NULL),
  ('pesapal_consumer_key', '', 'Pesapal Consumer Key', 0, NOW(), NULL),
  ('pesapal_consumer_secret', '', 'Pesapal Consumer Secret', 0, NOW(), NULL),
  ('pesapal_environment', 'sandbox', 'Pesapal Environment (sandbox/production)', 0, NOW(), NULL),
  ('notification_email', 'noreply@benfex.net', 'Notification email sender', 0, NOW(), NULL),
  ('enable_ipn', '1', 'Enable Instant Payment Notifications', 0, NOW(), NULL),
  ('enable_webhooks', '1', 'Enable Webhooks', 0, NOW(), NULL),
  ('enable_email_notifications', '1', 'Enable Email Notifications', 0, NOW(), NULL),
  ('enable_sms_notifications', '0', 'Enable SMS Notifications', 0, NOW(), NULL);

-- Create initial API key
INSERT INTO `api_keys` (`api_key`, `name`, `permissions`, `active`, `created_at`)
VALUES
  ('MY_SECRET_API_KEY_1', 'Default API Key', '["payments.create", "payments.read", "webhooks.manage"]', 1, NOW());