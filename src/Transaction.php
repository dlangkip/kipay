<?php
namespace MyPaymentGateway;

/**
 * Transaction class for managing payment transactions
 */
class Transaction {
    private $reference;
    private $trackingId;
    private $amount;
    private $currency;
    private $status;
    private $description;
    private $paymentMethod;
    private $customerId;
    private $metadata;
    private $createdAt;
    private $updatedAt;
    
    /**
     * Create a new transaction
     * 
     * @param string $reference Transaction reference
     * @param float $amount Transaction amount
     * @param string $currency Currency code
     * @param string $status Transaction status
     * @param string $description Transaction description
     * @param string|null $paymentMethod Payment method
     * @param string|null $customerId Customer ID
     * @param array $metadata Additional metadata
     */
    public function __construct(
        string $reference,
        float $amount,
        string $currency,
        string $status = 'PENDING',
        string $description = '',
        ?string $paymentMethod = null,
        ?string $customerId = null,
        array $metadata = []
    ) {
        $this->reference = $reference;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->description = $description;
        $this->paymentMethod = $paymentMethod;
        $this->customerId = $customerId;
        $this->metadata = $metadata;
        $this->createdAt = date('Y-m-d H:i:s');
        $this->updatedAt = date('Y-m-d H:i:s');
    }
    
    /**
     * Get transaction reference
     * 
     * @return string Reference
     */
    public function getReference(): string {
        return $this->reference;
    }
    
    /**
     * Get tracking ID
     * 
     * @return string|null Tracking ID
     */
    public function getTrackingId(): ?string {
        return $this->trackingId;
    }
    
    /**
     * Set tracking ID
     * 
     * @param string $trackingId Tracking ID
     * @return self
     */
    public function setTrackingId(string $trackingId): self {
        $this->trackingId = $trackingId;
        return $this;
    }
    
    /**
     * Get transaction amount
     * 
     * @return float Amount
     */
    public function getAmount(): float {
        return $this->amount;
    }
    
    /**
     * Get transaction currency
     * 
     * @return string Currency
     */
    public function getCurrency(): string {
        return $this->currency;
    }
    
    /**
     * Get transaction status
     * 
     * @return string Status
     */
    public function getStatus(): string {
        return $this->status;
    }
    
    /**
     * Set transaction status
     * 
     * @param string $status New status
     * @return self
     */
    public function setStatus(string $status): self {
        $this->status = $status;
        $this->updatedAt = date('Y-m-d H:i:s');
        return $this;
    }
    
    /**
     * Get transaction description
     * 
     * @return string Description
     */
    public function getDescription(): string {
        return $this->description;
    }
    
    /**
     * Get payment method
     * 
     * @return string|null Payment method
     */
    public function getPaymentMethod(): ?string {
        return $this->paymentMethod;
    }
    
    /**
     * Set payment method
     * 
     * @param string $paymentMethod Payment method
     * @return self
     */
    public function setPaymentMethod(string $paymentMethod): self {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }
    
    /**
     * Get customer ID
     * 
     * @return string|null Customer ID
     */
    public function getCustomerId(): ?string {
        return $this->customerId;
    }
    
    /**
     * Get transaction metadata
     * 
     * @return array Metadata
     */
    public function getMetadata(): array {
        return $this->metadata;
    }
    
    /**
     * Add metadata item
     * 
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return self
     */
    public function addMetadata(string $key, $value): self {
        $this->metadata[$key] = $value;
        return $this;
    }
    
    /**
     * Get created timestamp
     * 
     * @return string Created timestamp
     */
    public function getCreatedAt(): string {
        return $this->createdAt;
    }
    
    /**
     * Get updated timestamp
     * 
     * @return string Updated timestamp
     */
    public function getUpdatedAt(): string {
        return $this->updatedAt;
    }
    
    /**
     * Save transaction to database
     * 
     * @param \PDO $db Database connection
     * @return bool Success status
     */
    public function save(\PDO $db): bool {
        try {
            // Check if transaction already exists
            $stmt = $db->prepare("SELECT id FROM transactions WHERE reference = :reference");
            $stmt->execute([':reference' => $this->reference]);
            $exists = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($exists) {
                // Update existing transaction
                $sql = "UPDATE transactions SET 
                    tracking_id = :tracking_id,
                    status = :status,
                    payment_method = :payment_method,
                    metadata = :metadata,
                    updated_at = :updated_at
                WHERE reference = :reference";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':tracking_id' => $this->trackingId,
                    ':status' => $this->status,
                    ':payment_method' => $this->paymentMethod,
                    ':metadata' => json_encode($this->metadata),
                    ':updated_at' => $this->updatedAt,
                    ':reference' => $this->reference
                ]);
            } else {
                // Insert new transaction
                $sql = "INSERT INTO transactions (
                    reference, tracking_id, amount, currency, status, description,
                    payment_method, customer_id, metadata, created_at, updated_at
                ) VALUES (
                    :reference, :tracking_id, :amount, :currency, :status, :description,
                    :payment_method, :customer_id, :metadata, :created_at, :updated_at
                )";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':reference' => $this->reference,
                    ':tracking_id' => $this->trackingId,
                    ':amount' => $this->amount,
                    ':currency' => $this->currency,
                    ':status' => $this->status,
                    ':description' => $this->description,
                    ':payment_method' => $this->paymentMethod,
                    ':customer_id' => $this->customerId,
                    ':metadata' => json_encode($this->metadata),
                    ':created_at' => $this->createdAt,
                    ':updated_at' => $this->updatedAt
                ]);
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Transaction save error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load transaction from database
     * 
     * @param \PDO $db Database connection
     * @param string $reference Transaction reference
     * @return Transaction|null Transaction instance
     */
    public static function load(\PDO $db, string $reference): ?Transaction {
        try {
            $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = :reference");
            $stmt->execute([':reference' => $reference]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$data) {
                return null;
            }
            
            $transaction = new Transaction(
                $data['reference'],
                (float) $data['amount'],
                $data['currency'],
                $data['status'],
                $data['description'],
                $data['payment_method'],
                $data['customer_id'],
                !empty($data['metadata']) ? json_decode($data['metadata'], true) : []
            );
            
            if (!empty($data['tracking_id'])) {
                $transaction->setTrackingId($data['tracking_id']);
            }
            
            return $transaction;
        } catch (\PDOException $e) {
            error_log("Transaction load error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find transactions by customer ID
     * 
     * @param \PDO $db Database connection
     * @param string $customerId Customer ID
     * @return array List of transactions
     */
    public static function findByCustomerId(\PDO $db, string $customerId): array {
        try {
            $stmt = $db->prepare("SELECT * FROM transactions WHERE customer_id = :customer_id ORDER BY created_at DESC");
            $stmt->execute([':customer_id' => $customerId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $transactions = [];
            foreach ($rows as $row) {
                $transaction = new Transaction(
                    $row['reference'],
                    (float) $row['amount'],
                    $row['currency'],
                    $row['status'],
                    $row['description'],
                    $row['payment_method'],
                    $row['customer_id'],
                    !empty($row['metadata']) ? json_decode($row['metadata'], true) : []
                );
                
                if (!empty($row['tracking_id'])) {
                    $transaction->setTrackingId($row['tracking_id']);
                }
                
                $transactions[] = $transaction;
            }
            
            return $transactions;
        } catch (\PDOException $e) {
            error_log("Transaction findByCustomerId error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Find transactions by status
     * 
     * @param \PDO $db Database connection
     * @param string $status Transaction status
     * @param int $limit Maximum number of records to return
     * @return array List of transactions
     */
    public static function findByStatus(\PDO $db, string $status, int $limit = 100): array {
        try {
            $stmt = $db->prepare("SELECT * FROM transactions WHERE status = :status ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $transactions = [];
            foreach ($rows as $row) {
                $transaction = new Transaction(
                    $row['reference'],
                    (float) $row['amount'],
                    $row['currency'],
                    $row['status'],
                    $row['description'],
                    $row['payment_method'],
                    $row['customer_id'],
                    !empty($row['metadata']) ? json_decode($row['metadata'], true) : []
                );
                
                if (!empty($row['tracking_id'])) {
                    $transaction->setTrackingId($row['tracking_id']);
                }
                
                $transactions[] = $transaction;
            }
            
            return $transactions;
        } catch (\PDOException $e) {
            error_log("Transaction findByStatus error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Convert transaction to array
     * 
     * @return array Transaction data
     */
    public function toArray(): array {
        return [
            'reference' => $this->reference,
            'tracking_id' => $this->trackingId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'description' => $this->description,
            'payment_method' => $this->paymentMethod,
            'customer_id' => $this->customerId,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}