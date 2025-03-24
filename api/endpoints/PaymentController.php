<?php
namespace MyPaymentGateway\Endpoints;

use MyPaymentGateway\Gateway;
use MyPaymentGateway\Models\Payment;
use MyPaymentGateway\Models\Customer;

class PaymentController {
    private $config;
    private $gateway;
    
    /**
     * Initialize the payment controller
     * 
     * @param array $config Configuration settings
     */
    public function __construct(array $config) {
        $this->config = $config;
        $this->gateway = new Gateway($config, $config['debug']);
    }
    
    /**
     * Create a new payment
     * 
     * @param array $data Payment data
     * @return array Payment response
     */
    public function createPayment(array $data): array {
        // Validate required fields
        $requiredFields = [
            'amount', 'description', 'first_name', 'last_name', 'email'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Validate amount
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception("Invalid amount");
        }
        
        // Create customer model
        $customer = new Customer(
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'] ?? '',
            isset($data['customer_metadata']) ? $data['customer_metadata'] : []
        );
        
        // Create payment model
        $payment = new Payment(
            (float) $data['amount'],
            $data['description'],
            $data['type'] ?? 'MERCHANT',
            $data['currency'] ?? $this->config['default_currency'],
            isset($data['payment_metadata']) ? $data['payment_metadata'] : []
        );
        
        // Process the payment
        $result = $this->gateway->createPayment($customer, $payment);
        
        // Enrich the response
        $result['currency'] = $payment->getCurrency();
        $result['amount'] = $payment->getAmount();
        $result['description'] = $payment->getDescription();
        
        return [
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => $result
        ];
    }
}