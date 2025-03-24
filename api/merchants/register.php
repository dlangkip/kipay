<?php
// /api/merchants/register.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get request data
        $jsonData = file_get_contents('php://input');
        $requestData = json_decode($jsonData, true) ?: [];
        
        // Validate required fields
        $requiredFields = ['business_name', 'contact_name', 'email', 'phone', 'business_id', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($requestData[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Check if email already exists
        $db = new PDO(
            "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
            $CONFIG['db_user'],
            $CONFIG['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $db->prepare("SELECT id FROM merchants WHERE email = :email");
        $stmt->execute([':email' => $requestData['email']]);
        
        if ($stmt->fetch()) {
            throw new Exception("Email already registered");
        }
        
        // Generate API key
        $apiKey = bin2hex(random_bytes(32));
        
        // Hash password
        $passwordHash = password_hash($requestData['password'], PASSWORD_DEFAULT);
        
        // Insert new merchant
        $stmt = $db->prepare("
            INSERT INTO merchants (
                business_name, contact_name, email, phone, business_id, 
                logo_url, api_key, password_hash, status, created_at, updated_at
            ) VALUES (
                :business_name, :contact_name, :email, :phone, :business_id,
                :logo_url, :api_key, :password_hash, 'PENDING', NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            ':business_name' => $requestData['business_name'],
            ':contact_name' => $requestData['contact_name'],
            ':email' => $requestData['email'],
            ':phone' => $requestData['phone'],
            ':business_id' => $requestData['business_id'],
            ':logo_url' => $requestData['logo_url'] ?? null,
            ':api_key' => $apiKey,
            ':password_hash' => $passwordHash
        ]);
        
        $merchantId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Merchant registered successfully',
            'data' => [
                'merchant_id' => $merchantId,
                'api_key' => $apiKey
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error registering merchant',
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}