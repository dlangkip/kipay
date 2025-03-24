<?php
// /api/payments/process.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration
require_once __DIR__ . '/../config.php';

// Authentication function
function authenticateMerchant() {
    global $CONFIG;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $apiKey = $matches[1];
    
    $db = new PDO(
        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $db->prepare("SELECT id FROM merchants WHERE api_key = :api_key AND status = 'ACTIVE'");
    $stmt->execute([':api_key' => $apiKey]);
    $merchant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$merchant) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    
    return $merchant['id'];
}

// Get merchant credentials
function getMerchantCredentials($merchantId, $paymentMethod) {
    global $CONFIG;
    
    $db = new PDO(
        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $db->prepare("
        SELECT * FROM merchant_credentials 
        WHERE merchant_id = :merchant_id 
        AND payment_method = :payment_method 
        AND is_active = TRUE
    ");
    
    $stmt->execute([
        ':merchant_id' => $merchantId,
        ':payment_method' => $paymentMethod
    ]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Process M-Pesa STK Push
function processMpesaSTKPush($credentials, $paymentData, $merchantId) {
    global $CONFIG;
    
    // Generate a unique reference
    $reference = 'STK-' . $merchantId . '-' . time();
    
    // Format phone number
    $phoneNumber = preg_replace('/[^0-9]/', '', $paymentData['phone_number']);
    if (substr($phoneNumber, 0, 1) === '0') {
        $phoneNumber = '254' . substr($phoneNumber, 1);
    } elseif (substr($phoneNumber, 0, 3) !== '254') {
        $phoneNumber = '254' . $phoneNumber;
    }
    
    // Set API URLs based on environment
    $baseUrl = ($credentials['environment'] === 'production') 
        ? 'https://api.safaricom.co.ke' 
        : 'https://sandbox.safaricom.co.ke';
    
    $tokenUrl = $baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
    $stkUrl = $baseUrl . '/mpesa/stkpush/v1/processrequest';
    $callbackUrl = $CONFIG['app_url'] . '/api/callbacks/mpesa-stk?merchant_id=' . $merchantId;
    
    // Generate access token
    $auth = base64_encode($credentials['consumer_key'] . ':' . $credentials['consumer_secret']);
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth
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
    $shortcode = $credentials['shortcode'] ?? $credentials['account_number'];
    $password = base64_encode($shortcode . $credentials['passkey'] . $timestamp);
    
    // Prepare STK Push request
    $stkPushData = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => $credentials['payment_method'] === 'MPESA_PAYBILL' ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline',
        'Amount' => intval($paymentData['amount']),
        'PartyA' => $phoneNumber,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phoneNumber,
        'CallBackURL' => $callbackUrl,
        'AccountReference' => $reference,
        'TransactionDesc' => $paymentData['description'] ?? 'Payment'
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
            merchant_id,
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
            :merchant_id,
            :reference, 
            :amount, 
            'KES', 
            :description, 
            'PENDING', 
            :phone,
            :payment_method,
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
        ':merchant_id' => $merchantId,
        ':reference' => $reference,
        ':amount' => $paymentData['amount'],
        ':description' => $paymentData['description'] ?? 'STK Push Payment',
        ':phone' => $phoneNumber,
        ':payment_method' => 'MPESA_STK',
        ':metadata' => $metadata
    ]);
    
    return [
        'reference' => $reference,
        'phone' => $phoneNumber,
        'amount' => $paymentData['amount'],
        'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
        'status' => 'PENDING',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Authenticate merchant
        $merchantId = authenticateMerchant();
        
        // Get request data
        $jsonData = file_get_contents('php://input');
        $requestData = json_decode($jsonData, true) ?: [];
        
        // Validate required fields
        if (empty($requestData['payment_method'])) {
            throw new Exception("Payment method is required");
        }
        
        if (empty($requestData['amount']) || !is_numeric($requestData['amount'])) {
            throw new Exception("Valid amount is required");
        }
        
        // Get merchant credentials for the requested payment method
        $credentials = getMerchantCredentials($merchantId, $requestData['payment_method']);
        
        if (!$credentials) {
            throw new Exception("No active credentials found for this payment method");
        }
        
        // Process payment based on method
        $result = null;
        
        switch ($requestData['payment_method']) {
            case 'MPESA_PAYBILL':
            case 'MPESA_TILL':
                // Validate phone number for M-Pesa
                if (empty($requestData['phone_number'])) {
                    throw new Exception("Phone number is required for M-Pesa payments");
                }
                
                $result = processMpesaSTKPush($credentials, $requestData, $merchantId);
                break;
                
            // Add other payment methods as needed
                
            default:
                throw new Exception("Unsupported payment method");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment initiated successfully',
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        error_log("Payment process error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error processing payment',
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}