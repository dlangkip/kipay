<?php
/**
 * Kipay Gateway Entry Point
 * 
 * Redirects to the public directory or installation page if not installed
 */

// Define application path
define('APP_PATH', __DIR__);

// Redirect API requests to the API router
if (strpos($_SERVER['REQUEST_URI'], '/api') === 0) {
    require_once APP_PATH . '/api/index.php';
    exit;
}

// Rest of your code...


// Check if installation is needed
if (!file_exists('config/env.php')) {
    // Not installed yet, redirect to install script
    header('Location: install.php');
    exit();
}

// Redirect to public directory
header('Location: public/');
exit();