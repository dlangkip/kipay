<?php
/**
 * Environment-specific configuration
 * 
 * Copy this file to env.php and update with your settings
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kipay_gateway');
define('DB_USER', 'dbusername');
define('DB_PASS', 'dbpassword');

// Pesapal configuration
define('PESAPAL_CONSUMER_KEY', 'YOUR_PESAPAL_CONSUMER_KEY');
define('PESAPAL_CONSUMER_SECRET', 'YOUR_PESAPAL_CONSUMER_SECRET');
define('PESAPAL_ENVIRONMENT', 'sandbox'); // 'sandbox' or 'production'
define('PESAPAL_CALLBACK_URL', 'https://your-domain.com/payment/callback');

// Kenyan payment channels configuration
define('MPESA_PAYBILL', '174379'); // Your M-Pesa paybill number
define('MPESA_TILL', '123456');    // Your Lipa Na M-Pesa till number
define('PREFERRED_CHANNELS', ['MPESA', 'VISA', 'MASTERCARD']);

// API Keys
define('DEFAULT_API_KEY', 'YOUR_API_KEY');

// Debug mode
define('DEBUG', true);

// Notification settings
define('NOTIFICATION_EMAIL', 'notifications@your-domain.com');
define('NOTIFICATION_URL', 'https://your-domain.com/api/webhook/ipn');

// Application settings
define('APP_NAME', 'Kipay Gateway');
define('APP_URL', 'https://your-domain.com');
define('SUPPORT_EMAIL', 'support@your-domain.com');
define('SUPPORT_PHONE', '+1234567890');

// Feature flags
define('ENABLE_IPN', true);
define('ENABLE_WEBHOOKS', true);
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_SMS_NOTIFICATIONS', false);

// Security settings
define('WEBHOOK_SECRET', 'your-webhook-secret-key');