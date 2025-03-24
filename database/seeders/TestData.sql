-- Test Data for Kipay Payment Gateway
-- This script populates the database with sample data for testing purposes

-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `is_public`, `created_at`, `updated_at`)
VALUES
  ('app_version', '1.0.0', 'Application version', 1, NOW(), NULL),
  ('company_name', 'Demo Company Ltd', 'Company name for emails and reports', 1, NOW(), NULL),
  ('company_address', '123 Test Street, Nairobi, Kenya', 'Company address', 1, NOW(), NULL),
  ('company_phone', '+254700123456', 'Company phone number', 1, NOW(), NULL),
  ('company_email', 'info@example.com', 'Company email', 1, NOW(), NULL),
  ('company_logo', 'https://example.com/logo.png', 'Company logo URL', 1, NOW(), NULL),
  ('sms_enabled', '0', 'Enable SMS notifications', 0, NOW(), NULL),
  ('max_payment_attempts', '3', 'Maximum payment attempts before locking', 0, NOW(), NULL);

-- API Keys
INSERT INTO `api_keys` (`api_key`, `name`, `permissions`, `active`, `last_used`, `created_at`, `updated_at`)
VALUES
  ('test_api_key_1', 'Test API Key', '["payments.create", "payments.read", "webhooks.manage"]', 1, NULL, NOW(), NULL),
  ('demo_api_key_2', 'Demo API Key', '["payments.create", "payments.read"]', 1, NULL, NOW(), NULL),
  ('inactive_key', 'Inactive API Key', '["payments.create"]', 0, NULL, NOW(), NULL);

-- Webhooks
INSERT INTO `webhooks` (`url`, `secret`, `description`, `events`, `active`, `created_at`, `updated_at`)
VALUES
  ('https://example.com/webhook', 'webhook_secret_1', 'Test Webhook', '["payment.completed", "payment.failed"]', 1, NOW(), NULL),
  ('https://demo.benfex.net/api/webhook', 'webhook_secret_2', 'Demo Webhook', '["payment.completed", "payment.failed", "payment.pending"]', 1, NOW(), NULL);

-- Sample Transactions
INSERT INTO `transactions` (`reference`, `tracking_id`, `amount`, `currency`, `description`, `status`, `payment_method`, `customer_name`, `customer_email`, `customer_phone`, `customer_id`, `invoice_id`, `metadata`, `created_at`, `updated_at`)
VALUES
  ('TEST-001-123456789', 'PESAPAL-TRX-TEST1', 1000.00, 'KES', 'Test payment 1', 'COMPLETED', 'MPESA', 'John Doe', 'john@example.com', '+254700111222', 'CUST001', 'INV001', '{"order_id":"ORD001"}', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  ('TEST-002-123456789', 'PESAPAL-TRX-TEST2', 2500.00, 'KES', 'Test payment 2', 'COMPLETED', 'VISA', 'Jane Smith', 'jane@example.com', '+254700333444', 'CUST002', 'INV002', '{"order_id":"ORD002"}', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
  ('TEST-003-123456789', 'PESAPAL-TRX-TEST3', 1500.00, 'KES', 'Test payment 3', 'FAILED', 'MASTERCARD', 'Bob Johnson', 'bob@example.com', '+254700555666', 'CUST003', 'INV003', '{"order_id":"ORD003"}', DATE_SUB(NOW(), INTERVAL 12 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR)),
  ('TEST-004-123456789', NULL, 3000.00, 'KES', 'Test payment 4', 'PENDING', 'BANK', 'Alice Brown', 'alice@example.com', '+254700777888', 'CUST004', 'INV004', '{"order_id":"ORD004"}', DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 6 HOUR)),
  ('TEST-005-123456789', 'PESAPAL-TRX-TEST5', 750.00, 'KES', 'Test payment 5', 'COMPLETED', 'MPESA', 'Charles Maina', 'charles@example.com', '+254700999000', 'CUST005', 'INV005', '{"order_id":"ORD005"}', DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR)),
  ('TEST-006-123456789', NULL, 5000.00, 'KES', 'Test payment 6', 'PENDING', 'MPESA', 'Grace Wangari', 'grace@example.com', '+254711222333', 'CUST006', 'INV006', '{"order_id":"ORD006"}', DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  ('TEST-007-123456789', 'PESAPAL-TRX-TEST7', 12500.00, 'KES', 'Test payment 7', 'CANCELLED', 'EQUITY', 'David Odhiambo', 'david@example.com', '+254722333444', 'CUST007', 'INV007', '{"order_id":"ORD007"}', NOW(), NOW()),
  ('TEST-008-123456789', NULL, 2000.00, 'USD', 'Test payment 8', 'PENDING', 'AIRTEL', 'Patricia Njeri', 'patricia@example.com', '+254733444555', 'CUST008', 'INV008', '{"order_id":"ORD008"}', NOW(), NOW());

-- Sample Notifications
INSERT INTO `notifications` (`type`, `recipient`, `subject`, `content`, `reference`, `status`, `sent_at`, `created_at`)
VALUES
  ('email', 'john@example.com', 'Payment Confirmed', 'Your payment of KES 1,000.00 has been confirmed.', 'TEST-001-123456789', 'SENT', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  ('email', 'jane@example.com', 'Payment Confirmed', 'Your payment of KES 2,500.00 has been confirmed.', 'TEST-002-123456789', 'SENT', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
  ('email', 'bob@example.com', 'Payment Failed', 'Your payment of KES 1,500.00 has failed. Please try again.', 'TEST-003-123456789', 'SENT', DATE_SUB(NOW(), INTERVAL 12 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR)),
  ('email', 'charles@example.com', 'Payment Confirmed', 'Your payment of KES 750.00 has been confirmed.', 'TEST-005-123456789', 'SENT', DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR)),
  ('email', 'grace@example.com', 'Payment Pending', 'Your payment of KES 5,000.00 is pending confirmation.', 'TEST-006-123456789', 'PENDING', NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  ('email', 'david@example.com', 'Payment Cancelled', 'Your payment of KES 12,500.00 has been cancelled.', 'TEST-007-123456789', 'SENT', NOW(), NOW());

-- Sample Audit Logs
INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`)
VALUES
  ('admin', 'LOGIN', 'Administrator logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36', DATE_SUB(NOW(), INTERVAL 3 DAY)),
  ('admin', 'CONFIG_CHANGE', 'Updated system settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36', DATE_SUB(NOW(), INTERVAL 3 DAY)),
  ('admin', 'API_KEY_CREATE', 'Created new API key', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36', DATE_SUB(NOW(), INTERVAL 2 DAY)),
  ('admin', 'WEBHOOK_CREATE', 'Created new webhook', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36', DATE_SUB(NOW(), INTERVAL 2 DAY)),
  ('user1', 'LOGIN', 'User logged in', '192.168.1.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Mobile/15E148 Safari/604.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
  ('user1', 'PAYMENT_CREATE', 'Created payment TEST-006-123456789', '192.168.1.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Mobile/15E148 Safari/604.1', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  ('user2', 'LOGIN', 'User logged in', '192.168.1.2', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36', NOW()),
  ('user2', 'PAYMENT_CREATE', 'Created payment TEST-008-123456789', '192.168.1.2', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36', NOW());