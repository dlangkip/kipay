<?php
/**
 * Payment Gateway Application Configuration Example
 * 
 * Copy this file to app.php and update with your settings
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
        'YOUR_API_KEY_1' => 'client_1',
        'YOUR_API_KEY_2' => 'client_2'
    ],
    
    // Pesapal settings
    'consumer_key' => 'YOUR_PESAPAL_CONSUMER_KEY',
    'consumer_secret' => 'YOUR_PESAPAL_CONSUMER_SECRET',
    'environment' => 'sandbox', // 'sandbox' or 'production'
    'callback_url' => 'https://your-domain.com/payment/callback',
    
    // Database settings
    'db_host' => 'localhost',
    'db_name' => 'payment_gateway',
    'db_user' => 'dbusername',
    'db_pass' => 'dbpassword',
    
    // Logging settings
    'log_path' => __DIR__ . '/../logs',
    'debug' => true,
    
    // Notification settings
    'notification_url' => 'https://your-domain.com/api/webhook/ipn',
    'notification_email' => 'notifications@your-domain.com',
    
    // Application settings
    'app_name' => 'Payment Gateway',
    'app_url' => 'https://your-domain.com',
    'support_email' => 'support@your-domain.com',
    'support_phone' => '+1234567890',
    
    // Transaction settings
    'default_currency' => 'KES',
    'supported_currencies' => ['KES', 'USD', 'EUR', 'TZS', 'UGX'],
    
    // Payment channel settings
    'mpesa_paybill' => '174379', // Pesapal paybill
    'mpesa_till' => '123456',    // Your till number if applicable
    'preferred_channels' => ['MPESA', 'VISA', 'MASTERCARD'], // Default payment channels to highlight
    
    // Features
    'features' => [
        'enable_ipn' => true,
        'enable_webhooks' => true,
        'enable_email_notifications' => true,
        'enable_sms_notifications' => false,
    ],
    
    // Security
    'webhook_secret' => 'your-webhook-secret-key'
];

// Make configuration globally available
define('CONFIG', $CONFIG);