<?php
// Set headers for REST API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// For debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration
require_once __DIR__ . '/config.php';

try {
    // Get parameters
    $checkoutRequestId = $_GET['checkout_request_id'] ?? '';
    
    if (empty($checkoutRequestId)) {
        throw new Exception("Checkout Request ID is required");
    }
    
    // M-Pesa API credentials
    $consumerKey = $CONFIG['mpesa_consumer_key'] ?? '';
    $consumerSecret = $CONFIG['mpesa_consumer_secret'] ?? '';
    $shortcode = $CONFIG['mpesa_shortcode'] ?? '';
    $passkey = $CONFIG['mpesa_passkey'] ?? '';
    $environment = $CONFIG['mpesa_environment'] ?? 'sandbox';
    
    // Set API URLs based on environment
    $baseUrl = ($environment === 'production') 
        ? 'https://api.safaricom.co.ke' 
        : 'https://sandbox.safaricom.co.ke';
    
    $tokenUrl = $baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
    $queryUrl = $baseUrl . '/mpesa/stkpushquery/v1/query';
    
    // Generate access token
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials
    ]);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception("Error generating access token: " . curl_error($ch));
    }
    
    $result = json_decode($response, true);
    if (!isset($result['access_token'])) {
        throw new Exception("Failed to get access token: " . json_encode($result));
    }
    
    $accessToken = $result['access_token'];
    
    // Generate timestamp
    $timestamp = date('YmdHis');
    
    // Generate password
    $password = base64_encode($shortcode . $passkey . $timestamp);
    
    // Prepare query request
    $queryData = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'CheckoutRequestID' => $checkoutRequestId
    ];
    
    // Send query request
    $ch = curl_init($queryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception("Error querying transaction: " . curl_error($ch));
    }
    
    $result = json_decode($response, true);
    error_log("STK Query response: " . json_encode($result));
    
    // Return the query result
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
    // If the query indicates success, update the transaction status
    if (isset($result['ResultCode']) && $result['ResultCode'] === '0') {
        // Connect to database
        $db = new PDO(
            "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
            $CONFIG['db_user'],
            $CONFIG['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Find transaction by checkout request ID
        $stmt = $db->prepare("
            SELECT * FROM transactions 
            WHERE JSON_EXTRACT(metadata, '$.checkout_request_id') = :checkout_id
        ");
        $stmt->execute([':checkout_id' => $checkoutRequestId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            // Update transaction status
            $stmt = $db->prepare("
                UPDATE transactions 
                SET 
                    status = 'COMPLETED',
                    metadata = JSON_SET(metadata, '$.stk_query', :query_data),
                    updated_at = NOW()
                WHERE reference = :reference
            ");
            
            $stmt->execute([
                ':query_data' => json_encode($result),
                ':reference' => $transaction['reference']
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("STK Query error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error querying STK transaction',
        'error' => $e->getMessage()
    ]);
}
