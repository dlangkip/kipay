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

// Get the API path from REQUEST_URI
$request_uri = $_SERVER['REQUEST_URI'];
$api_path = '';

// Extract the path after /api/
if (preg_match('#^/api/(.*)#', $request_uri, $matches)) {
    $api_path = $matches[1];
    
    // Remove query string if present
    if (($pos = strpos($api_path, '?')) !== false) {
        $api_path = substr($api_path, 0, $pos);
    }
    
    // Remove trailing slash
    $api_path = rtrim($api_path, '/');
}

// Parse request data
$request_method = $_SERVER['REQUEST_METHOD'];
$request_data = [];

if ($request_method === 'POST') {
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($content_type, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $request_data = json_decode($json, true) ?: [];
    } else {
        $request_data = $_POST;
    }
} elseif ($request_method === 'GET') {
    $request_data = $_GET;
}

// Log the request for debugging
error_log("API Request: {$request_uri}, Path: {$api_path}, Method: {$request_method}");

try {
    // API endpoint routing
    switch ($api_path) {
        case '':
            // API root
            echo json_encode([
                'success' => true,
                'message' => 'Kipay Gateway API',
                'version' => '1.0.0'
            ]);
            break;
            
        case 'test-route':
            echo json_encode([
                'success' => true,
                'message' => 'API routing is working!',
                'method' => $request_method,
                'path' => $api_path,
                'uri' => $request_uri
            ]);
            break;
            
        case 'payments':
            if ($request_method === 'POST') {
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment created successfully',
                    'data' => [
                        'reference' => 'PAY-' . time(),
                        'payment_url' => 'https://demo.pesapal.com/payment/' . uniqid(),
                        'status' => 'PENDING',
                        'created_at' => date('Y-m-d H:i:s'),
                        'currency' => $request_data['currency'] ?? 'KES',
                        'amount' => $request_data['amount'] ?? 0
                    ]
                ]);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        case 'payments/methods':
            if ($request_method === 'GET') {
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment methods retrieved successfully',
                    'data' => [
                        'channels' => [
                            'mobile_money' => [
                                'MPESA' => 'M-Pesa',
                                'AIRTEL' => 'Airtel Money'
                            ],
                            'cards' => [
                                'VISA' => 'Visa',
                                'MASTERCARD' => 'Mastercard'
                            ]
                        ]
                    ]
                ]);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        case 'payments/status':
            if ($request_method === 'GET' && isset($request_data['reference'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction status retrieved',
                    'data' => [
                        'reference' => $request_data['reference'],
                        'status' => 'PENDING',
                        'checked_at' => date('Y-m-d H:i:s')
                    ]
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Bad request']);
            }
            break;
            
        case 'webhook/ipn':
            if ($request_method === 'POST') {
                echo json_encode([
                    'success' => true,
                    'message' => 'IPN processed successfully'
                ]);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        default:
            // Check if this might be a file that should be accessed directly
            $possible_php_file = __DIR__ . '/' . $api_path . '.php';
            if (file_exists($possible_php_file)) {
                include $possible_php_file;
                exit;
            }
            
            http_response_code(404);
            echo json_encode([
                'error' => 'Endpoint not found', 
                'path' => $api_path
            ]);
    }
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
