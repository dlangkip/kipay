<?php
/**
 * Payment Gateway Application Configuration
 */

// Define constants
define('API_VERSION', '1.0.0');
define('API_NAME', 'Kipay Payment Gateway');

// Import environment-specific configuration if exists
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

// Configuration settings
$CONFIG = [
    // API settings
    'api_keys' => [
        'MY_SECRET_API_KEY_1' => 'client_1',
        'MY_SECRET_API_KEY_2' => 'client_2'
    ],
    
    // Pesapal settings
    'consumer_key' => defined('PESAPAL_CONSUMER_KEY') ? PESAPAL_CONSUMER_KEY : 'Y3s2XevykfHuVdtUDC4L0/NwPl/7CVyId',
    'consumer_secret' => defined('PESAPAL_CONSUMER_SECRET') ? PESAPAL_CONSUMER_SECRET : 'PwHtwowedNb6Gz458QdfTI93EHg=',
    'environment' => defined('PESAPAL_ENVIRONMENT') ? PESAPAL_ENVIRONMENT : 'sandbox', // 'sandbox' or 'production'
    'callback_url' => defined('PESAPAL_CALLBACK_URL') ? PESAPAL_CALLBACK_URL : 'https://kipay.benfex.net/payment/callback',
    
    // Database settings
    'db_host' => defined('DB_HOST') ? DB_HOST : 'localhost',
    'db_name' => defined('DB_NAME') ? DB_NAME : 'kipay_gateway',
    'db_user' => defined('DB_USER') ? DB_USER : 'benfex',
    'db_pass' => defined('DB_PASS') ? DB_PASS : 'Benfex@2025',
    
    // Logging settings
    'log_path' => defined('LOG_PATH') ? LOG_PATH : __DIR__ . '/../logs',
    'debug' => defined('DEBUG') ? DEBUG : true,
    
    // Notification settings
    'notification_url' => defined('NOTIFICATION_URL') ? NOTIFICATION_URL : 'https://kipay.benfex.net/api/webhook/ipn',
    'notification_email' => defined('NOTIFICATION_EMAIL') ? NOTIFICATION_EMAIL : 'notifications@benfex.net',
    
    // Application settings
    'app_name' => defined('APP_NAME') ? APP_NAME : 'Kipay Gateway',
    'app_url' => defined('APP_URL') ? APP_URL : 'https://kipay.benfex.net',
    'support_email' => defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : 'support@benfex.net',
    'support_phone' => defined('SUPPORT_PHONE') ? SUPPORT_PHONE : '+254700760386',
    
    // Transaction settings
    'default_currency' => defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'KES',
    'supported_currencies' => defined('SUPPORTED_CURRENCIES') ? SUPPORTED_CURRENCIES : ['KES', 'USD', 'EUR', 'TZS', 'UGX'],
    
    // Payment channel settings
    'mpesa_paybill' => defined('MPESA_PAYBILL') ? MPESA_PAYBILL : '174379',
    'mpesa_till' => defined('MPESA_TILL') ? MPESA_TILL : '123456',
    'preferred_channels' => defined('PREFERRED_CHANNELS') ? PREFERRED_CHANNELS : ['MPESA', 'VISA', 'MASTERCARD'],
    
    // Features
    'features' => [
        'enable_ipn' => defined('ENABLE_IPN') ? ENABLE_IPN : true,
        'enable_webhooks' => defined('ENABLE_WEBHOOKS') ? ENABLE_WEBHOOKS : true,
        'enable_email_notifications' => defined('ENABLE_EMAIL_NOTIFICATIONS') ? ENABLE_EMAIL_NOTIFICATIONS : true,
        'enable_sms_notifications' => defined('ENABLE_SMS_NOTIFICATIONS') ? ENABLE_SMS_NOTIFICATIONS : false,
    ],
    
    // Security 
    'webhook_secret' => defined('WEBHOOK_SECRET') ? WEBHOOK_SECRET : 'your-webhook-secret-key'
];

// Make configuration globally available
define('CONFIG', $CONFIG);