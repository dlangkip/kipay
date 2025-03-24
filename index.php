<?php
/**
 * Kipay Gateway Entry Point
 * 
 * Redirects to the public directory or installation page if not installed
 */

// Check if installation is needed
if (!file_exists('config/env.php')) {
    // Not installed yet, redirect to install script
    header('Location: install.php');
    exit();
}

// Redirect to public directory
header('Location: public/');
exit();