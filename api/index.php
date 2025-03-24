<?php
/**
 * Payment Gateway API Router
 * 
 * This file handles all API requests and routes them to the appropriate controllers
 */

// Set headers for REST API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration
require_once __DIR__ . '/config.php';

// Include Pesapal library
require_once __DIR__ . '/../lib/pesapal-php/autoload.php';

// Custom autoloader for our classes
spl_autoload_register(function ($class) {
    $prefix = 'MyPaymentGateway\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Parse the URL to determine the endpoint
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api'; // Adjust this based on your setup
$endpoint = '';

if (strpos($requestUri, $basePath) === 0) {
    $endpoint = substr($requestUri, strlen($basePath));
}

// Remove query string if present
if (($pos = strpos($endpoint, '?')) !== false) {
    $endpoint = substr($endpoint, 0, $pos);
}

// Remove trailing slash if present
$endpoint = rtrim($endpoint, '/');

// Parse request data
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestData = [];

if ($requestMethod === 'POST') {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $requestData = json_decode($json, true) ?: [];
    } else {
        $requestData = $_POST;
    }
} elseif ($requestMethod === 'GET') {
    $requestData = $_GET;
}

// Initialize request logger middleware
$logger = new \MyPaymentGateway\Middleware\RequestLogger(CONFIG['log_path']);
$logger->logRequest($endpoint, $requestMethod, $requestData);

// Authentication middleware
$auth = new \MyPaymentGateway\Middleware\Authentication(CONFIG['api_keys']);

try {
    // Authenticate the request
    if (!$auth->authenticate(getAuthorizationHeader())) {
        sendResponse(['error' => 'Unauthorized'], 401);
    }
    
    // Route the request to the appropriate controller
    switch ($endpoint) {
        case '/payments':
            if ($requestMethod === 'POST') {
                $controller = new \MyPaymentGateway\Endpoints\PaymentController(CONFIG);
                $response = $controller->createPayment($requestData);
                sendResponse($response);
            } else {
                sendResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        case '/payments/status':
            if ($requestMethod === 'GET' && isset($requestData['reference'])) {
                $controller = new \MyPaymentGateway\Endpoints\StatusController(CONFIG);
                $response = $controller->checkStatus($requestData['reference']);
                sendResponse($response);
            } else {
                sendResponse(['error' => 'Bad request'], 400);
            }
            break;
            
        case '/webhook/ipn':
            if ($requestMethod === 'POST') {
                $controller = new \MyPaymentGateway\Endpoints\IPNController(CONFIG);
                $response = $controller->processIPN($requestData);
                sendResponse($response);
            } else {
                sendResponse(['error' => 'Method not allowed'], 405);
            }
            break;
            
        default:
            sendResponse(['error' => 'Endpoint not found'], 404);
    }
} catch (\Exception $e) {
    // Log the error
    $logger->logError($e->getMessage(), $e->getTrace());
    
    // Send error response
    sendResponse(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
}

/**
 * Send JSON response
 * 
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 */
function sendResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Get authorization header
 * 
 * @return string|null Authorization header
 */
function getAuthorizationHeader(): ?string {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    return null;
}