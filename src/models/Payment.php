<?php
namespace MyPaymentGateway\Models;

class Payment {
    private $amount;
    private $description;
    private $type;
    private $currency;
    private $metadata;
    
    /**
     * Create a new payment model
     * 
     * @param float $amount Payment amount
     * @param string $description Payment description
     * @param string $type Payment type (MERCHANT/ORDER)
     * @param string $currency Currency code (default: KES)
     * @param array $metadata Additional payment metadata
     */
    public function __construct(
        float $amount, 
        string $description, 
        string $type = 'MERCHANT', 
        string $currency = 'KES',
        array $metadata = []
    ) {
        $this->amount = $amount;
        $this->description = $description;
        $this->type = $type;
        $this->currency = $currency;
        $this->metadata = $metadata;
    }
    
    /**
     * Get payment amount
     * 
     * @return float Payment amount
     */
    public function getAmount(): float {
        return $this->amount;
    }
    
    /**
     * Get payment description
     * 
     * @return string Payment description
     */
    public function getDescription(): string {
        return $this->description;
    }
    
    /**
     * Get payment type
     * 
     * @return string Payment type
     */
    public function getType(): string {
        return $this->type;
    }
    
    /**
     * Get payment currency
     * 
     * @return string Currency code
     */
    public function getCurrency(): string {
        return $this->currency;
    }
    
    /**
     * Get payment metadata
     * 
     * @return array Metadata
     */
    public function getMetadata(): array {
        return $this->metadata;
    }
    
    /**
     * Set payment metadata
     * 
     * @param array $metadata Metadata
     * @return self
     */
    public function setMetadata(array $metadata): self {
        $this->metadata = $metadata;
        return $this;
    }
    
    /**
     * Add a metadata item
     * 
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return self
     */
    public function addMetadata(string $key, $value): self {
        $this->metadata[$key] = $value;
        return $this;
    }
}