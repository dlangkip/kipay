<?php
/**
 * Payment Gateway Main Entry Point
 * 
 * This file bootstraps the application and routes requests to the API
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Define application path
define('APP_PATH', dirname(__DIR__));

// Load configuration
require_once APP_PATH . '/config/app.php';

// Redirect API requests to the API router
if (strpos($_SERVER['REQUEST_URI'], '/api') === 0) {
    require_once APP_PATH . '/api/index.php';
    exit;
}

// Serve static content or show a default page
if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '') {
    // Show default page
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Kipay Gateway</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
            .container { max-width: 800px; margin: 0 auto; }
            h1 { color: #333; }
            .box { background: #f5f5f5; border-radius: 5px; padding: 20px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Kipay Payment Gateway</h1>
            <div class="box">
                <h2>API Status: Active</h2>
                <p>The Kipay Payment Gateway API is running. For documentation on how to use the API, please contact support.</p>
                <p>Current version: ' . API_VERSION . '</p>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// If no route matches, return 404
header('HTTP/1.0 404 Not Found');
echo 'Page not found';