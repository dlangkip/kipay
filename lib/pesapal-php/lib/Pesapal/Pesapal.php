<?php
namespace Pesapal;

class Pesapal {
    private $consumer_key;
    private $consumer_secret;
    private $iframelink;
    private $callback_url;
    private $statusrequestAPI;
    private $callbackrequestAPI;
    private $detailsrequestAPI;
    
    /**
     * Initialize Pesapal API
     * 
     * @param array $config Configuration parameters
     */
    public function __construct(array $config) {
        $this->consumer_key = $config['consumer_key'];
        $this->consumer_secret = $config['consumer_secret'];
        $this->callback_url = $config['callback_url'] ?? null;
        
        // Set API endpoints based on environment
        if (isset($config['testing']) && $config['testing']) {
            $this->iframelink = 'http://demo.pesapal.com/api/PostPesapalDirectOrderV4';
            $this->statusrequestAPI = 'http://demo.pesapal.com/api/QueryPaymentStatus';
            $this->callbackrequestAPI = 'http://demo.pesapal.com/api/QueryPaymentStatusByMerchantRef';
            $this->detailsrequestAPI = 'http://demo.pesapal.com/api/QueryPaymentDetails';
        } else {
            $this->iframelink = 'https://www.pesapal.com/API/PostPesapalDirectOrderV4';
            $this->statusrequestAPI = 'https://www.pesapal.com/API/QueryPaymentStatus';
            $this->callbackrequestAPI = 'https://www.pesapal.com/API/QueryPaymentStatusByMerchantRef';
            $this->detailsrequestAPI = 'https://www.pesapal.com/API/QueryPaymentDetails';
        }
    }
    
    /**
     * Get payment URL for iframe
     * 
     * @param array $data Payment data
     * @return string Payment URL
     */
    public function getPaymentURL(array $data): string {
        // Validate required fields
        $required = ['amount', 'description', 'reference', 'first_name', 'last_name', 'email'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Initialize OAuth
        $token = null;
        $params = null;
        $consumer = new OAuthConsumer($this->consumer_key, $this->consumer_secret);
        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
        
        // Create XML for the post data
        $post_xml = $this->createPostXML($data);
        
        // Construct the OAuth Request URL
        $iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $this->iframelink, $params);
        $iframe_src->set_parameter('oauth_callback', $this->callback_url);
        $iframe_src->set_parameter('pesapal_request_data', $post_xml);
        
        // Add preferred payment channel if provided
        if (isset($data['payment_channel'])) {
            $iframe_src->set_parameter('pesapal_payment_channel', $data['payment_channel']);
        }
        
        $iframe_src->sign_request($signature_method, $consumer, $token);
        
        return $iframe_src->to_url();
    }
    
    /**
     * Get transaction status
     * 
     * @param string $reference Merchant reference
     * @param string|null $tracking_id Pesapal tracking ID
     * @return string Transaction status
     */
    public function getTransactionStatus(string $reference, ?string $tracking_id = null): string {
        $token = null;
        $params = null;
        $consumer = new OAuthConsumer($this->consumer_key, $this->consumer_secret);
        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
        
        if ($tracking_id) {
            // Use QueryPaymentStatus if tracking ID is available
            $request_status = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $this->statusrequestAPI, $params);
            $request_status->set_parameter('pesapal_merchant_reference', $reference);
            $request_status->set_parameter('pesapal_transaction_tracking_id', $tracking_id);
        } else {
            // Use QueryPaymentStatusByMerchantRef if only reference is available
            $request_status = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $this->callbackrequestAPI, $params);
            $request_status->set_parameter('pesapal_merchant_reference', $reference);
        }
        
        $request_status->sign_request($signature_method, $consumer, $token);
        
        // Send request
        $response = $this->sendRequest($request_status->to_url());
        
        // Extract status
        $elements = preg_split('/=/', $response);
        $status = isset($elements[1]) ? trim($elements[1]) : 'UNKNOWN';
        
        return $status;
    }
    
    /**
     * Get transaction details
     * 
     * @param string $reference Merchant reference
     * @param string|null $tracking_id Pesapal tracking ID
     * @return array Transaction details
     */
    public function getTransactionDetails(string $reference, ?string $tracking_id = null): array {
        $token = null;
        $params = null;
        $consumer = new OAuthConsumer($this->consumer_key, $this->consumer_secret);
        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
        
        $request_details = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $this->detailsrequestAPI, $params);
        $request_details->set_parameter('pesapal_merchant_reference', $reference);
        
        if ($tracking_id) {
            $request_details->set_parameter('pesapal_transaction_tracking_id', $tracking_id);
        }
        
        $request_details->sign_request($signature_method, $consumer, $token);
        
        // Send request
        $response = $this->sendRequest($request_details->to_url());
        
        // Parse response
        $responseParams = explode('&', $response);
        $responseData = [];
        
        foreach ($responseParams as $param) {
            list($key, $value) = explode('=', $param);
            $responseData[$key] = $value;
        }
        
        return [
            'status' => isset($responseData['pesapal_status']) ? $responseData['pesapal_status'] : 'UNKNOWN',
            'method' => isset($responseData['pesapal_payment_method']) ? $responseData['pesapal_payment_method'] : '',
            'tracking_id' => isset($responseData['pesapal_transaction_tracking_id']) ? $responseData['pesapal_transaction_tracking_id'] : '',
            'reference' => $reference
        ];
    }
    
    /**
     * Create XML for payment request
     * 
     * @param array $data Payment data
     * @return string XML string
     */
    private function createPostXML(array $data): string {
        $amount = $data['amount'];
        $desc = $data['description'];
        $type = $data['type'] ?? 'MERCHANT';
        $reference = $data['reference'];
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $email = $data['email'];
        $phonenumber = $data['phone'] ?? '';
        
        $post_xml = '<?xml version="1.0" encoding="utf-8"?>';
        $post_xml .= '<PesapalDirectOrderInfo ';
        $post_xml .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        $post_xml .= 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ';
        $post_xml .= 'Amount="'.$amount.'" ';
        $post_xml .= 'Description="'.$desc.'" ';
        $post_xml .= 'Type="'.$type.'" ';
        $post_xml .= 'Reference="'.$reference.'" ';
        $post_xml .= 'FirstName="'.$first_name.'" ';
        $post_xml .= 'LastName="'.$last_name.'" ';
        $post_xml .= 'Email="'.$email.'" ';
        $post_xml .= 'PhoneNumber="'.$phonenumber.'" ';
        
        // Add preferred payment method if specified
        if (isset($data['payment_method'])) {
            $post_xml .= 'PaymentMethod="'.$data['payment_method'].'" ';
        }
        
        // Add paybill or till number if provided
        if (isset($data['paybill_number'])) {
            $post_xml .= 'PayBillNumber="'.$data['paybill_number'].'" ';
        }
        
        if (isset($data['till_number'])) {
            $post_xml .= 'TillNumber="'.$data['till_number'].'" ';
        }
        
        $post_xml .= 'xmlns="http://www.pesapal.com" />';
        
        return htmlentities($post_xml);
    }
    
    /**
     * Send HTTP request
     * 
     * @param string $url Request URL
     * @return string Response
     */
    private function sendRequest(string $url): string {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        $response = curl_exec($ch);
        
        if (curl_error($ch)) {
            throw new \Exception('Curl Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return $response;
    }
}