<?php
// /api/merchants/payment-methods.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration
require_once __DIR__ . '/../config.php';

// Authentication middleware
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

try {
    $merchantId = authenticateMerchant();
    
    $db = new PDO(
        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get merchant's payment methods
            $stmt = $db->prepare("
                SELECT id, payment_method, account_number, account_name, environment, is_active 
                FROM merchant_credentials 
                WHERE merchant_id = :merchant_id
            ");
            $stmt->execute([':merchant_id' => $merchantId]);
            $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $methods
            ]);
            break;
            
        case 'POST':
            // Add a new payment method
            $jsonData = file_get_contents('php://input');
            $requestData = json_decode($jsonData, true) ?: [];
            
            // Validate required fields
            $requiredFields = ['payment_method', 'account_number', 'account_name'];
            foreach ($requiredFields as $field) {
                if (empty($requestData[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Additional validation for different payment methods
            switch ($requestData['payment_method']) {
                case 'MPESA_PAYBILL':
                case 'MPESA_TILL':
                    if (empty($requestData['consumer_key']) || empty($requestData['consumer_secret']) || empty($requestData['passkey'])) {
                        throw new Exception("M-Pesa API credentials are required");
                    }
                    break;
                    
                case 'PESAPAL':
                    if (empty($requestData['consumer_key']) || empty($requestData['consumer_secret'])) {
                        throw new Exception("Pesapal API credentials are required");
                    }
                    break;
            }
            
            // Insert new payment method
            $stmt = $db->prepare("
                INSERT INTO merchant_credentials (
                    merchant_id, payment_method, consumer_key, consumer_secret,
                    account_number, account_name, passkey, shortcode,
                    environment, is_active, created_at, updated_at
                ) VALUES (
                    :merchant_id, :payment_method, :consumer_key, :consumer_secret,
                    :account_number, :account_name, :passkey, :shortcode,
                    :environment, :is_active, NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                ':merchant_id' => $merchantId,
                ':payment_method' => $requestData['payment_method'],
                ':consumer_key' => $requestData['consumer_key'] ?? null,
                ':consumer_secret' => $requestData['consumer_secret'] ?? null,
                ':account_number' => $requestData['account_number'],
                ':account_name' => $requestData['account_name'],
                ':passkey' => $requestData['passkey'] ?? null,
                ':shortcode' => $requestData['shortcode'] ?? null,
                ':environment' => $requestData['environment'] ?? 'sandbox',
                ':is_active' => true
            ]);
            
            $methodId = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment method added successfully',
                'data' => ['id' => $methodId]
            ]);
            break;
            
        case 'PUT':
            // Update a payment method
            $jsonData = file_get_contents('php://input');
            $requestData = json_decode($jsonData, true) ?: [];
            
            if (empty($requestData['id'])) {
                throw new Exception("Payment method ID is required");
            }
            
            // Verify method belongs to merchant
            $stmt = $db->prepare("
                SELECT id FROM merchant_credentials 
                WHERE id = :id AND merchant_id = :merchant_id
            ");
            $stmt->execute([
                ':id' => $requestData['id'],
                ':merchant_id' => $merchantId
            ]);
            
            if (!$stmt->fetch()) {
                throw new Exception("Payment method not found");
            }
            
            // Build update query
            $updateFields = [];
            $params = [
                ':id' => $requestData['id'],
                ':merchant_id' => $merchantId
            ];
            
            $allowedFields = [
                'consumer_key', 'consumer_secret', 'account_number', 
                'account_name', 'passkey', 'shortcode', 'environment', 'is_active'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($requestData[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $requestData[$field];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }
            
            $updateFields[] = "updated_at = NOW()";
            
            $sql = "
                UPDATE merchant_credentials 
                SET " . implode(', ', $updateFields) . "
                WHERE id = :id AND merchant_id = :merchant_id
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment method updated successfully'
            ]);
            break;
            
        case 'DELETE':
            // Delete a payment method
            if (empty($_GET['id'])) {
                throw new Exception("Payment method ID is required");
            }
            
            // Verify method belongs to merchant
            $stmt = $db->prepare("
                DELETE FROM merchant_credentials 
                WHERE id = :id AND merchant_id = :merchant_id
            ");
            $stmt->execute([
                ':id' => $_GET['id'],
                ':merchant_id' => $merchantId
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Payment method not found");
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment method deleted successfully'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing request',
        'error' => $e->getMessage()
    ]);
}