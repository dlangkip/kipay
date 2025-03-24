<?php
/**
 * Environment-specific configuration
 * 
 * Copy this file to env.php and update with your settings
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kipay_gateway');
define('DB_USER', 'benfex');
define('DB_PASS', 'Benfex@2025');

// Pesapal configuration
define('PESAPAL_CONSUMER_KEY', 'Y3s2XevykfHuVdtUDC4L0/NwPl/7CVyId');
define('PESAPAL_CONSUMER_SECRET', 'PwHtwowedNb6Gz458QdfTI93EHg=');
define('PESAPAL_ENVIRONMENT', 'sandbox'); // 'sandbox' or 'production'
define('PESAPAL_CALLBACK_URL', 'https://benfex.net/payment/callback');

// Kenyan payment channels configuration
define('MPESA_PAYBILL', '174379'); // Your M-Pesa paybill number
define('MPESA_TILL', '123456');    // Your Lipa Na M-Pesa till number
define('PREFERRED_CHANNELS', ['MPESA', 'VISA', 'MASTERCARD']);

// API Keys
define('DEFAULT_API_KEY', 'YOUR_API_KEY');

// Debug mode
define('DEBUG', true);

// Notification settings
define('NOTIFICATION_EMAIL', 'notifications@benfex.net');
define('NOTIFICATION_URL', 'https://benfex.net/api/webhook/ipn');

// Application settings
define('APP_NAME', 'Kipay Gateway');
define('APP_URL', 'https://kipay.benfex.net');
define('SUPPORT_EMAIL', 'support@benfex.net');
define('SUPPORT_PHONE', '+254700760386');

// Feature flags
define('ENABLE_IPN', true);
define('ENABLE_WEBHOOKS', true);
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_SMS_NOTIFICATIONS', false);

// Security settings
define('WEBHOOK_SECRET', 'your-webhook-secret-key');