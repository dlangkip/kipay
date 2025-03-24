<?php
namespace MyPaymentGateway\Endpoints;

class WebhookController {
    private $config;
    
    /**
     * Initialize the webhook controller
     * 
     * @param array $config Configuration settings
     */
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    /**
     * Process custom webhook
     * 
     * @param string $event Event type
     * @param array $data Webhook data
     * @return array Response
     */
    public function processWebhook(string $event, array $data): array {
        // Validate the signature if provided
        if (isset($_SERVER['HTTP_X_SIGNATURE'])) {
            $signature = $_SERVER['HTTP_X_SIGNATURE'];
            $payload = file_get_contents('php://input');
            
            if (!$this->validateSignature($payload, $signature)) {
                return [
                    'success' => false,
                    'message' => 'Invalid signature'
                ];
            }
        }
        
        // Log the webhook
        $this->logWebhook($event, $data);
        
        // Process based on event type
        switch ($event) {
            case 'payment.created':
                return $this->handlePaymentCreated($data);
                
            case 'payment.completed':
                return $this->handlePaymentCompleted($data);
                
            case 'payment.failed':
                return $this->handlePaymentFailed($data);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Unsupported event type: ' . $event
                ];
        }
    }
    
    /**
     * Validate webhook signature
     * 
     * @param string $payload Raw request payload
     * @param string $signature Provided signature
     * @return bool Validation result
     */
    private function validateSignature(string $payload, string $signature): bool {
        // Get the webhook secret from database or config
        $secret = $this->getWebhookSecret();
        
        if (!$secret) {
            // If no secret is configured, skip validation
            return true;
        }
        
        // Calculate expected signature
        $expected = hash_hmac('sha256', $payload, $secret);
        
        // Compare signatures
        return hash_equals($expected, $signature);
    }
    
    /**
     * Get webhook secret
     * 
     * @return string|null Webhook secret
     */
    private function getWebhookSecret(): ?string {
        // Implementation depends on your secret storage method
        // This is a placeholder for your actual logic
        
        // Example: Get from config
        return $this->config['webhook_secret'] ?? null;
    }
    
    /**
     * Log webhook
     * 
     * @param string $event Event type
     * @param array $data Webhook data
     */
    private function logWebhook(string $event, array $data): void {
        if (!$this->config['debug']) {
            return;
        }
        
        $logFile = $this->config['log_path'] . '/webhook_' . date('Y-m-d') . '.log';
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'data' => $data
        ];
        
        file_put_contents(
            $logFile, 
            json_encode($logEntry) . PHP_EOL, 
            FILE_APPEND
        );
    }
    
    /**
     * Handle payment.created event
     * 
     * @param array $data Event data
     * @return array Response
     */
    private function handlePaymentCreated(array $data): array {
        // Implementation depends on your business logic
        // This is a placeholder for your actual logic
        
        return [
            'success' => true,
            'message' => 'Webhook processed: payment.created'
        ];
    }
    
    /**
     * Handle payment.completed event
     * 
     * @param array $data Event data
     * @return array Response
     */
    private function handlePaymentCompleted(array $data): array {
        // Implementation depends on your business logic
        // This is a placeholder for your actual logic
        
        return [
            'success' => true,
            'message' => 'Webhook processed: payment.completed'
        ];
    }
    
    /**
     * Handle payment.failed event
     * 
     * @param array $data Event data
     * @return array Response
     */
    private function handlePaymentFailed(array $data): array {
        // Implementation depends on your business logic
        // This is a placeholder for your actual logic
        
        return [
            'success' => true,
            'message' => 'Webhook processed: payment.failed'
        ];
    }
}