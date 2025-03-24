<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Load configuration
require_once __DIR__ . '/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get IPN parameters
        $notificationType = $_POST['pesapal_notification_type'] ?? '';
        $trackingId = $_POST['pesapal_transaction_tracking_id'] ?? '';
        $reference = $_POST['pesapal_merchant_reference'] ?? '';
        
        // Log the IPN
        error_log("IPN received: Type={$notificationType}, TrackingID={$trackingId}, Reference={$reference}");
        
        if (empty($reference)) {
            throw new Exception("Missing merchant reference");
        }
        
        // Connect to the database
        $db = new PDO(
            "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
            $CONFIG['db_user'],
            $CONFIG['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Update transaction status
        $stmt = $db->prepare("
            UPDATE transactions 
            SET 
                tracking_id = :tracking_id,
                status = 'COMPLETED',
                updated_at = NOW()
            WHERE reference = :reference
        ");
        
        $stmt->execute([
            ':tracking_id' => $trackingId,
            ':reference' => $reference
        ]);
        
        // Check if the transaction was updated
        if ($stmt->rowCount() === 0) {
            error_log("Warning: Transaction not found for reference: {$reference}");
        } else {
            error_log("Transaction {$reference} updated to COMPLETED");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'IPN processed successfully',
            'data' => [
                'reference' => $reference,
                'tracking_id' => $trackingId,
                'status' => 'COMPLETED',
                'processed_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
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
    error_log("Error processing IPN: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing IPN',
        'error' => $e->getMessage()
    ]);
}
