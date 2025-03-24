<?php
/**
 * Payment Gateway API Configuration
 */

// Define constants
define('API_VERSION', '1.0.0');
define('API_NAME', 'My Payment Gateway');

// Configuration settings
$CONFIG = [
    // API settings
    'api_keys' => [
        'MY_SECRET_API_KEY_1' => 'client_1',
        'MY_SECRET_API_KEY_2' => 'client_2'
    ],
    
    // Pesapal settings
    'consumer_key' => 'Y3s2XevykfHuVdtUDC4L0/NwPl/7CVyId',
    'consumer_secret' => 'PwHtwowedNb6Gz458QdfTI93EHg=',
    'environment' => 'sandbox', // 'sandbox' or 'production'
    'callback_url' => 'https://kipay.benfex.net/payment/callback',
    
    // Database settings
    'db_host' => 'localhost',
    'db_name' => 'kipay_gateway',
    'db_user' => 'benfex',
    'db_pass' => 'Benfex@2025',
    
    // Logging settings
    'log_path' => __DIR__ . '/../logs',
    'debug' => true,
    
    // Notification settings
    'notification_url' => 'https://kipay.benfex.net/api/webhook/ipn',
    'notification_email' => 'notifications@benfex.net',
    
    // Application settings
    'app_name' => 'Kipay Gateway',
    'app_url' => 'https://kipay.benfex.net',
    'support_email' => 'support@benfex.net',
    'support_phone' => '+254700760386',
    
    // Transaction settings
    'default_currency' => 'KES',
    'supported_currencies' => ['KES', 'USD', 'EUR', 'TZS', 'UGX'],
    
    // Features
    'features' => [
        'enable_ipn' => true,
        'enable_webhooks' => true,
        'enable_email_notifications' => true,
        'enable_sms_notifications' => false,
    ]
];

// Make configuration globally available
define('CONFIG', $CONFIG);