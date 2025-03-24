<?php
namespace MyPaymentGateway;

use MyPaymentGateway\Models\Payment;
use MyPaymentGateway\Models\Customer;

// Include Pesapal library manually
require_once __DIR__ . '/../lib/pesapal-php/autoload.php';

class Gateway {
    private $pesapal;
    private $config;
    private $debug;
    
    /**
     * Initialize the payment gateway
     * 
     * @param array $config Configuration parameters
     * @param bool $debug Enable debug mode
     */
    public function __construct(array $config, bool $debug = false) {
        $this->config = $config;
        $this->debug = $debug;
        
        // Initialize the Pesapal library
        $this->pesapal = new \Pesapal\Pesapal([
            'consumer_key' => $config['consumer_key'],
            'consumer_secret' => $config['consumer_secret'],
            'testing' => $config['environment'] === 'sandbox',
            'callback_url' => $config['callback_url']
        ]);
    }
    
    /**
     * Create a new payment
     * 
     * @param Customer $customer Customer information
     * @param Payment $payment Payment details
     * @return array Payment information including redirect URL
     */
    public function createPayment(Customer $customer, Payment $payment): array {
        // Generate a unique transaction reference
        $reference = $this->generateReference();
        
        // Set payment details
        $paymentData = [
            'amount' => $payment->getAmount(),
            'description' => $payment->getDescription(),
            'type' => $payment->getType(),
            'reference' => $reference,
            'first_name' => $customer->getFirstName(),
            'last_name' => $customer->getLastName(),
            'email' => $customer->getEmail(),
            'phone' => $customer->getPhone(),
        ];
        
        // Log the payment request if debug is enabled
        if ($this->debug) {
            $this->logDebug('Payment Request', $paymentData);
        }
        
        // Get the payment URL from Pesapal
        $paymentURL = $this->pesapal->getPaymentURL($paymentData);
        
        // Save the transaction in our database
        $this->saveTransaction($reference, $paymentData);
        
        return [
            'reference' => $reference,
            'payment_url' => $paymentURL,
            'status' => 'PENDING',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Check the status of a payment
     * 
     * @param string $reference Transaction reference
     * @return array Transaction status details
     */
    public function checkPaymentStatus(string $reference): array {
        // Query Pesapal for the payment status
        $status = $this->pesapal->getTransactionStatus($reference);
        
        // Log the status response if debug is enabled
        if ($this->debug) {
            $this->logDebug('Status Response', ['reference' => $reference, 'status' => $status]);
        }
        
        // Update our local transaction record
        $this->updateTransactionStatus($reference, $status);
        
        return [
            'reference' => $reference,
            'status' => $status,
            'checked_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Process IPN (Instant Payment Notification) callback
     * 
     * @param array $params IPN parameters
     * @return array Processed IPN details
     */
    public function processIPN(array $params): array {
        // Validate the IPN parameters
        if (!isset($params['pesapal_notification_type']) || 
            !isset($params['pesapal_transaction_tracking_id']) ||
            !isset($params['pesapal_merchant_reference'])) {
            throw new \Exception('Invalid IPN parameters');
        }
        
        $reference = $params['pesapal_merchant_reference'];
        $trackingId = $params['pesapal_transaction_tracking_id'];
        
        // Query the transaction status from Pesapal
        $status = $this->pesapal->getTransactionDetails($reference, $trackingId);
        
        // Log the IPN if debug is enabled
        if ($this->debug) {
            $this->logDebug('IPN Received', [
                'params' => $params,
                'status' => $status
            ]);
        }
        
        // Update our local transaction record
        $this->updateTransactionStatus($reference, $status['status'], $trackingId);
        
        return [
            'reference' => $reference,
            'tracking_id' => $trackingId,
            'status' => $status['status'],
            'processed_at' => date('Y-m-d H:i:s')
        ];
    }    
    /**
     * Generate a unique transaction reference
     * 
     * @return string Unique reference
     */
    private function generateReference(): string {
        return uniqid('PAY-') . '-' . time();
    }
    
    /**
     * Save transaction details to the database
     * 
     * @param string $reference Transaction reference
     * @param array $data Transaction data
     * @return bool Success status
     */
    private function saveTransaction(string $reference, array $data): bool {
        // Implementation depends on your database structure
        // This is a placeholder for your actual database logic
        
        // Example using PDO
        try {
            $db = new \PDO(
                "mysql:host={$this->config['db_host']};dbname={$this->config['db_name']}",
                $this->config['db_user'],
                $this->config['db_pass']
            );
            
            $stmt = $db->prepare("
                INSERT INTO transactions 
                (reference, amount, description, customer_email, customer_name, status, created_at) 
                VALUES 
                (:reference, :amount, :description, :email, :name, 'PENDING', NOW())
            ");
            
            $stmt->execute([
                ':reference' => $reference,
                ':amount' => $data['amount'],
                ':description' => $data['description'],
                ':email' => $data['email'],
                ':name' => $data['first_name'] . ' ' . $data['last_name']
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Database Error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update transaction status in the database
     * 
     * @param string $reference Transaction reference
     * @param string $status Transaction status
     * @param string|null $trackingId Pesapal tracking ID
     * @return bool Success status
     */
    private function updateTransactionStatus(string $reference, string $status, ?string $trackingId = null): bool {
        // Implementation depends on your database structure
        // This is a placeholder for your actual database logic
        
        // Example using PDO
        try {
            $db = new \PDO(
                "mysql:host={$this->config['db_host']};dbname={$this->config['db_name']}",
                $this->config['db_user'],
                $this->config['db_pass']
            );
            
            $sql = "
                UPDATE transactions 
                SET status = :status, updated_at = NOW()
            ";
            
            $params = [
                ':reference' => $reference,
                ':status' => $status
            ];
            
            if ($trackingId) {
                $sql .= ", tracking_id = :tracking_id";
                $params[':tracking_id'] = $trackingId;
            }
            
            $sql .= " WHERE reference = :reference";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Database Error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log debug information
     * 
     * @param string $title Log entry title
     * @param mixed $data Data to log
     */
    private function logDebug(string $title, $data): void {
        $logFile = $this->config['log_path'] . '/debug_' . date('Y-m-d') . '.log';
        $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . $title . ': ' . json_encode($data) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Log error information
     * 
     * @param string $title Error title
     * @param string $message Error message
     */
    private function logError(string $title, string $message): void {
        $logFile = $this->config['log_path'] . '/error_' . date('Y-m-d') . '.log';
        $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . $title . ': ' . $message . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}