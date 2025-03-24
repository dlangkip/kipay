<?php
// Set headers
header('Content-Type: application/json');

// For debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create a debug log file specifically for callbacks
$debug_log = '/var/www/kipay.benfex.net/public_html/logs/stk_callbacks.log';
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Callback endpoint accessed\n", FILE_APPEND);

// Get raw post data
$raw_post = file_get_contents('php://input');
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Raw callback data: " . $raw_post . "\n", FILE_APPEND);

// Load configuration
require_once __DIR__ . '/config.php';

try {
    // Get callback data
    $callbackData = json_decode($raw_post, true);
    
    // Log the callback
    error_log("STK Callback received: " . json_encode($callbackData));
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Parsed callback data: " . json_encode($callbackData) . "\n", FILE_APPEND);
    
    // Extract data
    $body = $callbackData['Body']['stkCallback'] ?? null;
    
    if (!$body) {
        throw new Exception("Invalid callback data");
    }
    
    $resultCode = $body['ResultCode'] ?? null;
    $resultDesc = $body['ResultDesc'] ?? '';
    $checkoutRequestID = $body['CheckoutRequestID'] ?? '';
    $merchantRequestID = $body['MerchantRequestID'] ?? '';
    
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Extracted data: ResultCode=$resultCode, ResultDesc=$resultDesc, CheckoutRequestID=$checkoutRequestID\n", FILE_APPEND);
    
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
    $stmt->execute([':checkout_id' => $checkoutRequestID]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Transaction not found for checkout ID: $checkoutRequestID\n", FILE_APPEND);
        throw new Exception("Transaction not found for checkout ID: " . $checkoutRequestID);
    }
    
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Found transaction: " . json_encode($transaction) . "\n", FILE_APPEND);
    
    // Update transaction status
    $status = ($resultCode === 0) ? 'COMPLETED' : 'FAILED';
    
    $stmt = $db->prepare("
        UPDATE transactions 
        SET 
            status = :status,
            metadata = JSON_SET(metadata, '$.stk_callback', :callback_data),
            updated_at = NOW()
        WHERE reference = :reference
    ");
    
    $stmt->execute([
        ':status' => $status,
        ':callback_data' => json_encode($callbackData),
        ':reference' => $transaction['reference']
    ]);
    
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Updated transaction status to: $status\n", FILE_APPEND);
    
    // Return acknowledgement
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("STK Callback error: " . $e->getMessage());
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
