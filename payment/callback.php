<?php
// Load configuration
require_once __DIR__ . '/../api/config.php';

// Get parameters from Pesapal
$reference = $_GET['pesapal_merchant_reference'] ?? null;
$trackingId = $_GET['pesapal_transaction_tracking_id'] ?? null;

// Check if parameters are present
if (!$reference) {
    die("Error: Missing transaction reference");
}

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Update transaction with tracking ID if available
    if ($trackingId) {
        $stmt = $db->prepare("
            UPDATE transactions 
            SET tracking_id = :tracking_id, updated_at = NOW()
            WHERE reference = :reference
        ");
        $stmt->execute([
            ':tracking_id' => $trackingId,
            ':reference' => $reference
        ]);
    }
    
    // Fetch transaction details
    $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = :reference");
    $stmt->execute([':reference' => $reference]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check transaction exists
    if (!$transaction) {
        throw new Exception("Transaction not found: " . $reference);
    }
    
    // Extract metadata
    $metadata = json_decode($transaction['metadata'], true) ?? [];
    $returnUrl = $metadata['return_url'] ?? null;
    
    // If we received a tracking ID, check payment status
    $status = 'PENDING';
    if ($trackingId) {
        // In a real implementation, we would make a request to Pesapal to verify the status
        // For now, we'll just assume it's completed if we got a tracking ID
        $status = 'COMPLETED';
        
        // Update the transaction status
        $stmt = $db->prepare("
            UPDATE transactions 
            SET status = :status, updated_at = NOW()
            WHERE reference = :reference
        ");
        $stmt->execute([
            ':status' => $status,
            ':reference' => $reference
        ]);
    }
    
    // If we have a return URL, redirect back to the originating app
    if ($returnUrl) {
        header("Location: {$returnUrl}?reference={$reference}&status={$status}");
        exit;
    }
    
} catch (Exception $e) {
    error_log("Callback error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Callback - Kipay Gateway</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .status {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .details {
            margin-top: 20px;
        }
        .detail-row {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }
        .detail-label {
            font-weight: bold;
            width: 180px;
        }
        .success {
            color: #28a745;
        }
        .pending {
            color: #ffc107;
        }
        .failed {
            color: #dc3545;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kipay Payment Gateway</h1>
    </div>
    
    <div class="card">
        <?php if (isset($transaction)): ?>
            <?php 
                $statusClass = '';
                switch($status) {
                    case 'COMPLETED':
                        $statusClass = 'success';
                        break;
                    case 'PENDING':
                        $statusClass = 'pending';
                        break;
                    default:
                        $statusClass = 'failed';
                }
            ?>
            
            <div class="status <?= $statusClass ?>">
                Payment <?= $status ?>
            </div>
            
            <div class="details">
                <div class="detail-row">
                    <div class="detail-label">Reference:</div>
                    <div><?= htmlspecialchars($reference) ?></div>
                </div>
                
                <?php if ($trackingId): ?>
                <div class="detail-row">
                    <div class="detail-label">Tracking ID:</div>
                    <div><?= htmlspecialchars($trackingId) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <div class="detail-label">Amount:</div>
                    <div><?= htmlspecialchars($transaction['amount']) ?> <?= htmlspecialchars($transaction['currency']) ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div><?= htmlspecialchars($transaction['description']) ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date:</div>
                    <div><?= htmlspecialchars($transaction['created_at']) ?></div>
                </div>
            </div>
            
            <?php if ($status === 'COMPLETED'): ?>
                <p>Your payment has been successfully processed. Thank you for your payment.</p>
            <?php elseif ($status === 'PENDING'): ?>
                <p>Your payment is being processed. We will notify you once it's complete.</p>
            <?php else: ?>
                <p>There was an issue with your payment. Please try again or contact support.</p>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="status failed">Transaction Not Found</div>
            <p>We couldn't find information for this transaction. Please contact support if you believe this is an error.</p>
        <?php endif; ?>
        
        <a href="<?= $CONFIG['app_url'] ?>" class="button">Return to Homepage</a>
    </div>
</body>
</html>
