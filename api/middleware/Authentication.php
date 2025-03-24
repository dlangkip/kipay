<?php
namespace MyPaymentGateway\Middleware;

class Authentication {
    private $apiKeys;
    
    /**
     * Initialize authentication middleware
     * 
     * @param array $apiKeys Valid API keys
     */
    public function __construct(array $apiKeys) {
        $this->apiKeys = $apiKeys;
    }
    
    /**
     * Authenticate the request
     * 
     * @param string|null $authHeader Authorization header
     * @return bool Authentication result
     */
    public function authenticate(?string $authHeader): bool {
        // Skip authentication for IPN endpoint
        if ($this->isIPNEndpoint()) {
            return true;
        }
        
        // Check if Authorization header is present
        if (!$authHeader) {
            return false;
        }
        
        // Extract API key from the header
        $apiKey = $this->extractApiKey($authHeader);
        
        if (!$apiKey) {
            return false;
        }
        
        // Verify API key
        return $this->verifyApiKey($apiKey);
    }
    
    /**
     * Extract API key from Authorization header
     * 
     * @param string $authHeader Authorization header
     * @return string|null API key
     */
    private function extractApiKey(string $authHeader): ?string {
        // Check for Bearer token
        if (strpos($authHeader, 'Bearer ') === 0) {
            return substr($authHeader, 7);
        }
        
        // Check for Basic auth
        if (strpos($authHeader, 'Basic ') === 0) {
            $credentials = base64_decode(substr($authHeader, 6));
            $parts = explode(':', $credentials);
            
            if (count($parts) === 2) {
                return $parts[0]; // Use username as API key
            }
        }
        
        return null;
    }
    
    /**
     * Verify API key
     * 
     * @param string $apiKey API key
     * @return bool Verification result
     */
    private function verifyApiKey(string $apiKey): bool {
        return isset($this->apiKeys[$apiKey]);
    }
    
    /**
     * Check if current request is for IPN endpoint
     * 
     * @return bool True if IPN endpoint
     */
    private function isIPNEndpoint(): bool {
        $requestUri = $_SERVER['REQUEST_URI'];
        return strpos($requestUri, '/webhook/ipn') !== false;
    }
}