<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// For debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/config.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the JSON data from the request body
        $jsonData = file_get_contents('php://input');
        $requestData = json_decode($jsonData, true) ?: [];
        
        // Generate a unique reference
        $reference = 'PAY-' . time();
        
        // Create a fake payment URL (in production this would come from Pesapal)
        $paymentUrl = 'https://demo.pesapal.com/payment/' . uniqid();
        
        // Connect to the database
        $db = new PDO(
            "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
            $CONFIG['db_user'],
            $CONFIG['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Save the transaction to the database
        $stmt = $db->prepare("
            INSERT INTO transactions (
                reference, 
                amount, 
                currency, 
                description, 
                status, 
                customer_name, 
                customer_email, 
                customer_phone, 
                metadata, 
                created_at, 
                updated_at
            ) VALUES (
                :reference, 
                :amount, 
                :currency, 
                :description, 
                'PENDING', 
                :customer_name, 
                :customer_email, 
                :customer_phone, 
                :metadata, 
                NOW(), 
                NOW()
            )
        ");
        
        $customerName = $requestData['first_name'] . ' ' . $requestData['last_name'];
        $metadata = json_encode([
            'payment_url' => $paymentUrl,
            'first_name' => $requestData['first_name'],
            'last_name' => $requestData['last_name']
        ]);
        
        $stmt->execute([
            ':reference' => $reference,
            ':amount' => $requestData['amount'],
            ':currency' => $requestData['currency'] ?? 'KES',
            ':description' => $requestData['description'],
            ':customer_name' => $customerName,
            ':customer_email' => $requestData['email'],
            ':customer_phone' => $requestData['phone'] ?? '',
            ':metadata' => $metadata
        ]);
        
        // Log success
        error_log("Payment saved to database: {$reference}");
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => [
                'reference' => $reference,
                'payment_url' => $paymentUrl,
                'status' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'currency' => $requestData['currency'] ?? 'KES',
                'amount' => $requestData['amount']
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
        error_log("Error creating payment: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error creating payment',
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
