<?php
namespace MyPaymentGateway\Middleware;

class RequestLogger {
    private $logPath;
    
    /**
     * Initialize request logger
     * 
     * @param string $logPath Path to log directory
     */
    public function __construct(string $logPath) {
        $this->logPath = $logPath;
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Log an API request
     * 
     * @param string $endpoint Request endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     */
    public function logRequest(string $endpoint, string $method, array $data): void {
        $logFile = $this->logPath . '/api_' . date('Y-m-d') . '.log';
        
        // Sanitize sensitive data
        $sanitizedData = $this->sanitizeData($data);
        
        // Create log entry
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $this->getClientIP(),
            'endpoint' => $endpoint,
            'method' => $method,
            'data' => $sanitizedData,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        // Write to log file
        file_put_contents(
            $logFile, 
            json_encode($logEntry) . PHP_EOL, 
            FILE_APPEND
        );
    }
    
    /**
     * Log an error
     * 
     * @param string $message Error message
     * @param array $trace Error trace
     */
    public function logError(string $message, array $trace): void {
        $logFile = $this->logPath . '/error_' . date('Y-m-d') . '.log';
        
        // Create log entry
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $this->getClientIP(),
            'message' => $message,
            'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'trace' => $this->formatTrace($trace)
        ];
        
        // Write to log file
        file_put_contents(
            $logFile, 
            json_encode($logEntry) . PHP_EOL, 
            FILE_APPEND
        );
    }
    
    /**
     * Sanitize sensitive data
     * 
     * @param array $data Request data
     * @return array Sanitized data
     */
    private function sanitizeData(array $data): array {
        $sensitiveFields = [
            'password', 'card_number', 'cvv', 'secret', 
            'api_key', 'consumer_secret', 'token'
        ];
        
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Format error trace
     * 
     * @param array $trace Error trace
     * @return array Formatted trace
     */
    private function formatTrace(array $trace): array {
        $formatted = [];
        
        foreach ($trace as $entry) {
            $formatted[] = [
                'file' => $entry['file'] ?? 'Unknown',
                'line' => $entry['line'] ?? 'Unknown',
                'function' => $entry['function'] ?? 'Unknown',
                'class' => $entry['class'] ?? 'Unknown'
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function getClientIP(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
    }
}