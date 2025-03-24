<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data from the request body
    $jsonData = file_get_contents('php://input');
    $requestData = json_decode($jsonData, true) ?: [];
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment created successfully',
        'data' => [
            'reference' => 'PAY-' . time(),
            'payment_url' => 'https://demo.pesapal.com/payment/' . uniqid(),
            'status' => 'PENDING',
            'created_at' => date('Y-m-d H:i:s'),
            'currency' => $requestData['currency'] ?? 'KES',
            'amount' => $requestData['amount'] ?? 0
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
