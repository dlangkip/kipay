<?php
namespace MyPaymentGateway\Endpoints;

use MyPaymentGateway\Gateway;

class StatusController {
    private $config;
    private $gateway;
    
    /**
     * Initialize the status controller
     * 
     * @param array $config Configuration settings
     */
    public function __construct(array $config) {
        $this->config = $config;
        $this->gateway = new Gateway($config, $config['debug']);
    }
    
    /**
     * Check payment status
     * 
     * @param string $reference Payment reference
     * @return array Status response
     */
    public function checkStatus(string $reference): array {
        // Validate reference
        if (empty($reference)) {
            throw new \Exception("Reference is required");
        }
        
        // Check for transaction in database first
        $transaction = $this->getTransactionFromDB($reference);
        
        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'Transaction not found',
                'data' => [
                    'reference' => $reference,
                    'status' => 'UNKNOWN',
                    'checked_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        // If transaction is already COMPLETED or FAILED, return cached status
        if (in_array($transaction['status'], ['COMPLETED', 'FAILED', 'CANCELLED'])) {
            return [
                'success' => true,
                'message' => 'Transaction status retrieved',
                'data' => [
                    'reference' => $reference,
                    'status' => $transaction['status'],
                    'amount' => $transaction['amount'],
                    'currency' => $transaction['currency'],
                    'payment_method' => $transaction['payment_method'] ?? 'UNKNOWN',
                    'tracking_id' => $transaction['tracking_id'] ?? null,
                    'created_at' => $transaction['created_at'],
                    'updated_at' => $transaction['updated_at'],
                    'checked_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        // For pending transactions, check with Pesapal
        try {
            $status = $this->gateway->checkPaymentStatus($reference);
            
            return [
                'success' => true,
                'message' => 'Transaction status checked',
                'data' => array_merge($status, [
                    'amount' => $transaction['amount'],
                    'currency' => $transaction['currency'],
                    'description' => $transaction['description']
                ])
            ];
        } catch (\Exception $e) {
            // If there's an error checking with Pesapal, return the last known status
            return [
                'success' => false,
                'message' => 'Error checking transaction status: ' . $e->getMessage(),
                'data' => [
                    'reference' => $reference,
                    'status' => $transaction['status'],
                    'amount' => $transaction['amount'],
                    'currency' => $transaction['currency'],
                    'last_checked' => $transaction['updated_at'],
                    'checked_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
    }
    
    /**
     * Get transaction details from database
     * 
     * @param string $reference Transaction reference
     * @return array|null Transaction details
     */
    private function getTransactionFromDB(string $reference): ?array {
        try {
            $db = new \PDO(
                "mysql:host={$this->config['db_host']};dbname={$this->config['db_name']}",
                $this->config['db_user'],
                $this->config['db_pass']
            );
            
            $stmt = $db->prepare("
                SELECT * FROM transactions 
                WHERE reference = :reference 
                LIMIT 1
            ");
            
            $stmt->execute([':reference' => $reference]);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $transaction ?: null;
        } catch (\Exception $e) {
            // Log the error
            if ($this->config['debug']) {
                error_log("Database Error: " . $e->getMessage());
            }
            
            return null;
        }
    }
}