<?php
/**
 * Simple autoloader for Pesapal library
 */

// Define the Pesapal class paths
$pesapal_classes = [
    'Pesapal\\Pesapal' => __DIR__ . '/lib/Pesapal/Pesapal.php',
    'Pesapal\\OAuth' => __DIR__ . '/lib/Pesapal/OAuth.php',
    'Pesapal\\OAuthConsumer' => __DIR__ . '/lib/Pesapal/OAuthConsumer.php',
    'Pesapal\\OAuthDataStore' => __DIR__ . '/lib/Pesapal/OAuthDataStore.php',
    'Pesapal\\OAuthException' => __DIR__ . '/lib/Pesapal/OAuthException.php',
    'Pesapal\\OAuthRequest' => __DIR__ . '/lib/Pesapal/OAuthRequest.php',
    'Pesapal\\OAuthServer' => __DIR__ . '/lib/Pesapal/OAuthServer.php',
    'Pesapal\\OAuthSignatureMethod' => __DIR__ . '/lib/Pesapal/OAuthSignatureMethod.php',
    'Pesapal\\OAuthSignatureMethod_HMAC_SHA1' => __DIR__ . '/lib/Pesapal/OAuthSignatureMethod_HMAC_SHA1.php',
    'Pesapal\\OAuthSignatureMethod_PLAINTEXT' => __DIR__ . '/lib/Pesapal/OAuthSignatureMethod_PLAINTEXT.php',
    'Pesapal\\OAuthSignatureMethod_RSA_SHA1' => __DIR__ . '/lib/Pesapal/OAuthSignatureMethod_RSA_SHA1.php',
    'Pesapal\\OAuthToken' => __DIR__ . '/lib/Pesapal/OAuthToken.php',
    'Pesapal\\OAuthUtil' => __DIR__ . '/lib/Pesapal/OAuthUtil.php'
];

// Register autoloader
spl_autoload_register(function ($class) use ($pesapal_classes) {
    if (isset($pesapal_classes[$class])) {
        require $pesapal_classes[$class];
    }
});
