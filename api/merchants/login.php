<?php
// /api/merchants/login.php
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
        if (empty($requestData['email']) || empty($requestData['password'])) {
            throw new Exception("Email and password are required");
        }
        
        // Connect to database
        $db = new PDO(
            "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
            $CONFIG['db_user'],
            $CONFIG['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Find merchant by email
        $stmt = $db->prepare("
            SELECT id, business_name, email, password_hash, api_key, status 
            FROM merchants 
            WHERE email = :email
        ");
        $stmt->execute([':email' => $requestData['email']]);
        $merchant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$merchant) {
            throw new Exception("Invalid email or password");
        }
        
        // Verify password
        if (!password_verify($requestData['password'], $merchant['password_hash'])) {
            throw new Exception("Invalid email or password");
        }
        
        // Check if merchant is active
        if ($merchant['status'] !== 'ACTIVE') {
            throw new Exception("Account is not active. Please contact support.");
        }
        
        // Generate session token (you might want to use a proper JWT implementation)
        $sessionToken = bin2hex(random_bytes(32));
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'merchant_id' => $merchant['id'],
                'business_name' => $merchant['business_name'],
                'email' => $merchant['email'],
                'api_key' => $merchant['api_key'],
                'session_token' => $sessionToken
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication failed',
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}