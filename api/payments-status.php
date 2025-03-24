<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Load configuration
require_once __DIR__ . '/config.php';

try {
    $reference = $_GET['reference'] ?? '';

    if (empty($reference)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing reference parameter']);
        exit;
    }
    
    // Connect to the database
    $db = new PDO(
        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check if transaction exists
    $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = :reference");
    $stmt->execute([':reference' => $reference]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        // Update the checked_at timestamp
        $updateStmt = $db->prepare("
            UPDATE transactions 
            SET checked_at = NOW() 
            WHERE reference = :reference
        ");
        $updateStmt->execute([':reference' => $reference]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction status retrieved',
            'data' => [
                'reference' => $reference,
                'status' => $transaction['status'],
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency'],
                'checked_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Transaction status retrieved',
            'data' => [
                'reference' => $reference,
                'status' => 'PENDING', // Default status if not found
                'checked_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }
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
    error_log("Error checking status: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error checking payment status',
        'error' => $e->getMessage()
    ]);
}
