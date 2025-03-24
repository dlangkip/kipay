<?php
/**
 * Simple autoloader for Pesapal library
 */

// Register autoloader
spl_autoload_register(function ($class) {
    // Only handle Pesapal namespace
    if (strpos($class, 'Pesapal\\') !== 0) {
        return;
    }
    
    // Remove namespace prefix
    $relative_class = substr($class, strlen('Pesapal\\'));
    
    // Build file path
    $file = __DIR__ . '/lib/Pesapal/' . str_replace('\\', '/', $relative_class) . '.php';
    
    // Load file if it exists
    if (file_exists($file)) {
        require $file;
    }
});