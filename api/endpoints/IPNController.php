<?php
namespace MyPaymentGateway\Endpoints;

use MyPaymentGateway\Gateway;

class IPNController {
    private $config;
    private $gateway;
    
    /**
     * Initialize the IPN controller
     * 
     * @param array $config Configuration settings
     */
    public function __construct(array $config) {
        $this->config = $config;
        $this->gateway = new Gateway($config, $config['debug']);
    }
    
    /**
     * Process IPN (Instant Payment Notification)
     * 
     * @param array $data IPN data
     * @return array Processing result
     */
    public function processIPN(array $data): array {
        // Log the IPN request
        if ($this->config['debug']) {
            $this->logIPN($data);
        }
        
        // Validate required parameters
        if (!isset($data['pesapal_notification_type']) || 
            !isset($data['pesapal_transaction_tracking_id']) ||
            !isset($data['pesapal_merchant_reference'])) {
            
            return [
                'success' => false,
                'message' => 'Invalid IPN parameters',
                'data' => null
            ];
        }
        
        try {
            // Process the IPN
            $result = $this->gateway->processIPN($data);
            
            // Send notifications if enabled
            if ($this->config['features']['enable_email_notifications']) {
                $this->sendEmailNotification($result);
            }
            
            if ($this->config['features']['enable_webhooks']) {
                $this->triggerWebhooks($result);
            }
            
            return [
                'success' => true,
                'message' => 'IPN processed successfully',
                'data' => $result
            ];
        } catch (\Exception $e) {
            // Log the error
            if ($this->config['debug']) {
                error_log("IPN Error: " . $e->getMessage());
            }
            
            return [
                'success' => false,
                'message' => 'Error processing IPN: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Log IPN data
     * 
     * @param array $data IPN data
     */
    private function logIPN(array $data): void {
        $logFile = $this->config['log_path'] . '/ipn_' . date('Y-m-d') . '.log';
        $logEntry = '[' . date('Y-m-d H:i:s') . '] IPN Received: ' . json_encode($data) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Send email notification
     * 
     * @param array $transactionData Transaction data
     */
    private function sendEmailNotification(array $transactionData): void {
        // Get transaction details from database
        $transaction = $this->getTransactionDetails($transactionData['reference']);
        
        if (!$transaction) {
            return;
        }
        
        // Prepare email content
        $subject = "Payment {$transactionData['status']} - {$transactionData['reference']}";
        
        $message = "Dear {$transaction['customer_name']},\n\n";
        $message .= "Your payment with reference {$transactionData['reference']} is now {$transactionData['status']}.\n\n";
        $message .= "Amount: {$transaction['amount']} {$transaction['currency']}\n";
        $message .= "Description: {$transaction['description']}\n";
        $message .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
        $message .= "Thank you for using " . $this->config['app_name'] . ".\n\n";
        $message .= "If you have any questions, please contact our support at {$this->config['support_email']} or {$this->config['support_phone']}.";
        
        // Send the email
        // This is a placeholder - implement your email sending logic
        mail($transaction['customer_email'], $subject, $message, "From: {$this->config['notification_email']}");
    }
    
    /**
     * Trigger webhooks
     * 
     * @param array $transactionData Transaction data
     */
    private function triggerWebhooks(array $transactionData): void {
        // Get webhooks from database
        $webhooks = $this->getWebhooks();
        
        if (empty($webhooks)) {
            return;
        }
        
        // Prepare payload
        $payload = json_encode([
            'event' => 'payment.updated',
            'reference' => $transactionData['reference'],
            'status' => $transactionData['status'],
            'tracking_id' => $transactionData['tracking_id'] ?? null,
            'timestamp' => time()
        ]);
        
        // Send to each webhook URL
        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook['url'], $payload, $webhook['secret']);
        }
    }
    
    /**
     * Send webhook
     * 
     * @param string $url Webhook URL
     * @param string $payload JSON payload
     * @param string $secret Webhook secret
     */
    private function sendWebhook(string $url, string $payload, string $secret): void {
        // Calculate signature
        $signature = hash_hmac('sha256', $payload, $secret);
        
        // Setup cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
            'X-Signature: ' . $signature
        ]);
        
        // Execute request
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log the result
        if ($this->config['debug']) {
            $logFile = $this->config['log_path'] . '/webhook_' . date('Y-m-d') . '.log';
            $logEntry = '[' . date('Y-m-d H:i:s') . '] Webhook sent to ' . $url . ': ' . $status . ' - ' . $result . PHP_EOL;
            file_put_contents($logFile, $logEntry, FILE_APPEND);
        }
    }
    
    /**
     * Get transaction details from database
     * 
     * @param string $reference Transaction reference
     * @return array|null Transaction details
     */
    private function getTransactionDetails(string $reference): ?array {
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
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get webhooks from database
     * 
     * @return array Webhook URLs
     */
    private function getWebhooks(): array {
        try {
            $db = new \PDO(
                "mysql:host={$this->config['db_host']};dbname={$this->config['db_name']}",
                $this->config['db_user'],
                $this->config['db_pass']
            );
            
            $stmt = $db->prepare("SELECT * FROM webhooks WHERE active = 1");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
}