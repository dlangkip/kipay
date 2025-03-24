<?php
// /api/callbacks/mpesa-stk.php
header('Content-Type: application/json');

// Enable debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create a debug log
$debug_log = __DIR__ . '/../../logs/mpesa_callbacks.log';
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Callback received\n", FILE_APPEND);

// Load configuration
require_once __DIR__ . '/../config.php';

try {
    // Get merchant ID from query string
    $merchantId = $_GET['merchant_id'] ?? null;
    
    if (!$merchantId) {
        throw new Exception("Merchant ID not provided");
    }
    
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Merchant ID: {$merchantId}\n", FILE_APPEND);
    
    // Get callback data
    $callbackData = json_decode(file_get_contents('php://input'), true);
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Callback data: " . json_encode($callbackData) . "\n", FILE_APPEND);
    
    // Extract data
    $body = $callbackData['Body']['stkCallback'] ?? null;
    
    if (!$body) {
        throw new Exception("Invalid callback data");
    }
    
    $resultCode = $body['ResultCode'] ?? null;
    $resultDesc = $body['ResultDesc'] ?? '';
    $checkoutRequestID = $body['CheckoutRequestID'] ?? '';
    $merchantRequestID = $body['MerchantRequestID'] ?? '';
    
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - ResultCode: {$resultCode}, Desc: {$resultDesc}\n", FILE_APPEND);
    
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
        AND merchant_id = :merchant_id
    ");
    $stmt->execute([
        ':checkout_id' => $checkoutRequestID,
        ':merchant_id' => $merchantId
    ]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Transaction not found for checkout ID: {$checkoutRequestID}\n", FILE_APPEND);
        throw new Exception("Transaction not found");
    }
    
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Found transaction: {$transaction['reference']}\n", FILE_APPEND);
    
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
    
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Updated transaction status to: {$status}\n", FILE_APPEND);
    
    // Notify merchant about the payment status
    $notifyMerchant = true;
    
    if ($notifyMerchant) {
        // Get merchant's callback URL
        $stmt = $db->prepare("
            SELECT url FROM merchant_callbacks 
            WHERE merchant_id = :merchant_id 
            AND callback_type = :type
        ");
        
        $callbackType = ($status === 'COMPLETED') ? 'SUCCESS' : 'FAILURE';
        $stmt->execute([
            ':merchant_id' => $merchantId,
            ':type' => $callbackType
        ]);
        
        $callbackUrl = $stmt->fetchColumn();
        
        if ($callbackUrl) {
            // Prepare notification data
            $notificationData = [
                'reference' => $transaction['reference'],
                'status' => $status,
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency'],
                'payment_method' => $transaction['payment_method'],
                'customer_phone' => $transaction['customer_phone'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Send notification to merchant
            $ch = curl_init($callbackUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Merchant notification sent to {$callbackUrl}, response code: {$httpCode}\n", FILE_APPEND);
            
            if (curl_errno($ch)) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Error notifying merchant: " . curl_error($ch) . "\n", FILE_APPEND);
            }
            
            curl_close($ch);
        }
    }
    
    // Return acknowledgement
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("M-Pesa Callback error: " . $e->getMessage());
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}