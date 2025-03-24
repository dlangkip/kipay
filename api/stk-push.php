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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get request data
        $jsonData = file_get_contents('php://input');
        $requestData = json_decode($jsonData, true) ?: [];
        
        // Validate required fields
        if (empty($requestData['phone_number']) || empty($requestData['amount'])) {
            throw new Exception("Phone number and amount are required");
        }
        
        // Format phone number (remove leading + and ensure it starts with 254)
        $phoneNumber = preg_replace('/[^0-9]/', '', $requestData['phone_number']);
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '254' . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 3) !== '254') {
            $phoneNumber = '254' . $phoneNumber;
        }
        
        // Generate a unique reference
        $reference = 'STK-' . time();
        
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
        $stkUrl = $baseUrl . '/mpesa/stkpush/v1/processrequest';
        $callbackUrl = $CONFIG['app_url'] . '/api/stk-callback';
        
        if (empty($consumerKey) || empty($consumerSecret) || empty($shortcode) || empty($passkey)) {
            throw new Exception("M-Pesa API credentials not configured");
        }
        
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
        
        // Prepare STK Push request
        $stkPushData = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => intval($requestData['amount']), // Amount must be an integer
            'PartyA' => $phoneNumber,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $reference,
            'TransactionDesc' => $requestData['description'] ?? 'Payment'
        ];
        
        // Send STK Push request
        $ch = curl_init($stkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkPushData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception("Error sending STK Push: " . curl_error($ch));
        }
        
        $result = json_decode($response, true);
        error_log("STK Push response: " . json_encode($result));
        
        if (isset($result['errorCode'])) {
            throw new Exception("STK Push failed: " . ($result['errorMessage'] ?? 'Unknown error'));
        }
        
        // Connect to database
        $db = new PDO(
            "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
            $CONFIG['db_user'],
            $CONFIG['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Save transaction to database
        $stmt = $db->prepare("
            INSERT INTO transactions (
                reference, 
                amount, 
                currency, 
                description, 
                status, 
                customer_phone,
                payment_method,
                metadata, 
                created_at, 
                updated_at
            ) VALUES (
                :reference, 
                :amount, 
                'KES', 
                :description, 
                'PENDING', 
                :phone,
                'MPESA_STK',
                :metadata, 
                NOW(), 
                NOW()
            )
        ");
        
        $metadata = json_encode([
            'stk_response' => $result,
            'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $result['MerchantRequestID'] ?? null
        ]);
        
        $stmt->execute([
            ':reference' => $reference,
            ':amount' => $requestData['amount'],
            ':description' => $requestData['description'] ?? 'STK Push Payment',
            ':phone' => $phoneNumber,
            ':metadata' => $metadata
        ]);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'STK Push initiated',
            'data' => [
                'reference' => $reference,
                'phone' => $phoneNumber,
                'amount' => $requestData['amount'],
                'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
                'status' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (PDOException $e) {
        // Log the database error
        error_log("Database error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred',
            'error' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        // Log other errors
        error_log("STK Push error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error initiating STK Push',
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
