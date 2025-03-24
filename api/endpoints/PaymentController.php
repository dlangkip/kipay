<?php
namespace MyPaymentGateway\Endpoints;

use MyPaymentGateway\Gateway;
use MyPaymentGateway\Models\Payment;
use MyPaymentGateway\Models\Customer;
use MyPaymentGateway\PaymentChannels\KenyanChannels;

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
        
        // Process payment method selection
        $paymentData = [
            'amount' => $payment->getAmount(),
            'description' => $payment->getDescription(),
            'type' => $payment->getType(),
            'first_name' => $customer->getFirstName(),
            'last_name' => $customer->getLastName(),
            'email' => $customer->getEmail(),
            'phone' => $customer->getPhone(),
            'currency' => $payment->getCurrency()
        ];
        
        // Handle specific payment methods
        if (isset($data['payment_method'])) {
            $paymentData['payment_method'] = $data['payment_method'];
            
            // Determine the payment channel based on the method
            $channelType = KenyanChannels::getChannelType($data['payment_method']);
            
            if ($channelType === 'mobile_money') {
                // For mobile money payments, add paybill or till number
                $paymentData['payment_channel'] = 'MOBILE';
                
                if ($data['payment_method'] === 'MPESA') {
                    $paymentData['paybill_number'] = $this->config['mpesa_paybill'] ?? '174379';
                    
                    // If till number is provided, use that instead of paybill
                    if (isset($this->config['mpesa_till']) && !empty($this->config['mpesa_till'])) {
                        $paymentData['till_number'] = $this->config['mpesa_till'];
                    }
                }
            } elseif ($channelType === 'bank') {
                $paymentData['payment_channel'] = 'BANK';
            } elseif ($channelType === 'card') {
                $paymentData['payment_channel'] = 'CARD';
            }
        }
        
        // Process the payment
        $result = $this->gateway->createPayment($paymentData);
        
        // Enrich the response
        $result['currency'] = $payment->getCurrency();
        $result['amount'] = $payment->getAmount();
        $result['description'] = $payment->getDescription();
        
        if (isset($data['payment_method'])) {
            $result['payment_method'] = $data['payment_method'];
            $result['payment_method_name'] = KenyanChannels::getMethodName($data['payment_method']);
        }
        
        return [
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => $result
        ];
    }
    
    /**
     * Get available payment methods
     * 
     * @return array List of payment methods
     */
    public function getPaymentMethods(): array {
        // Get all payment channels from the KenyanChannels class
        $paymentChannels = KenyanChannels::getAllChannels();
        
        // Add information about preferred channels
        $preferredChannels = $this->config['preferred_channels'] ?? ['MPESA', 'VISA', 'MASTERCARD'];
        
        return [
            'success' => true,
            'message' => 'Payment methods retrieved successfully',
            'data' => [
                'channels' => $paymentChannels,
                'preferred_channels' => $preferredChannels
            ]
        ];
    }
}