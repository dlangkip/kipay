<?php
/**
 * Kipay Gateway Module for benfex
 * 
 * This file should be placed in the system/payments/ directory of your benfex installation
 */

// Define payment gateway information
$gateway_name = 'Kipay Payment Gateway';
$gateway_logo = 'kipay_payment_logo.png';
$gateway_author = 'Amos Kiprotich';
$gateway_version = '1.0';
$gateway_description = 'Kipay payment gateway using Pesapal API';

/**
 * Create payment button function
 * 
 * @param array $invoice Invoice details
 * @param array $config Gateway configuration
 * @return string HTML payment button
 */
function my_payment_gateway_create_payment_button($invoice, $config) {
    // Generate payment form with hidden fields
    $form = '
    <form method="post" action="' . U . 'client/ipay/' . $invoice['id'] . '/my_payment_gateway">
        <input type="hidden" name="invoice_id" value="' . $invoice['id'] . '">
        <input type="hidden" name="amount" value="' . $invoice['total'] . '">
        <input type="hidden" name="currency" value="' . $config['currency_code'] . '">
        <input type="hidden" name="description" value="Payment for Invoice #' . $invoice['id'] . '">
        <button type="submit" class="btn btn-primary">Pay with ' . $gateway_name . '</button>
    </form>';
    
    return $form;
}

/**
 * Payment processing function
 * 
 * @param array $request Request data
 * @param array $config Gateway configuration
 * @return array Processing result
 */
function my_payment_gateway_process_payment($request, $config) {
    global $app;
    
    // Get invoice details
    $invoice_id = $request['invoice_id'];
    $invoice = ORM::for_table('sys_invoices')->find_one($invoice_id);
    
    // Get user details
    $user = ORM::for_table('crm_accounts')->find_one($invoice->userid);
    
    // Prepare API request data
    $data = [
        'amount' => $invoice->total,
        'description' => 'Payment for Invoice #' . $invoice_id,
        'first_name' => $user->account,
        'last_name' => $user->lname ?? '',
        'email' => $user->email,
        'phone' => $user->phone ?? '',
        'currency' => $config['currency_code'],
        'payment_metadata' => [
            'invoice_id' => $invoice_id,
            'user_id' => $invoice->userid,
            'client_ip' => $_SERVER['REMOTE_ADDR']
        ]
    ];
    
    // Make API request to your custom payment gateway
    $payment = my_payment_gateway_api_request('/payments', 'POST', $data, $config);
    
    if (!isset($payment['success']) || !$payment['success']) {
        // Handle error
        $app->log('Payment Gateway Error: ' . ($payment['message'] ?? 'Unknown error'));
        return [
            'success' => false,
            'message' => 'Payment gateway error: ' . ($payment['message'] ?? 'Unknown error'),
            'redirect' => U . 'client/iview/' . $invoice_id . '/error/'
        ];
    }
    
    // Store payment reference in the transaction log
    $trans = ORM::for_table('sys_transactions')->create();
    $trans->userid = $invoice->userid;
    $trans->invoice = $invoice_id;
    $trans->datetime = date('Y-m-d H:i:s');
    $trans->amount = $invoice->total;
    $trans->currency = $config['currency_code'];
    $trans->pmethod = 'My Payment Gateway';
    $trans->description = 'Payment for Invoice #' . $invoice_id;
    $trans->ref = $payment['data']['reference'];
    $trans->status = 'Pending';
    $trans->save();
    
    // Update invoice status to Processing
    $invoice->status = 'Processing';
    $invoice->save();
    
    // Redirect to payment URL
    return [
        'success' => true,
        'message' => 'Redirecting to payment gateway',
        'redirect' => $payment['data']['payment_url']
    ];
}

/**
 * IPN callback function
 * 
 * @param array $request Request data
 * @param array $config Gateway configuration
 * @return array Processing result
 */
function my_payment_gateway_ipn_callback($request, $config) {
    global $app;
    
    // Log IPN callback
    $app->log('My Payment Gateway IPN: ' . json_encode($request));
    
    // Validate required parameters
    if (!isset($request['pesapal_merchant_reference']) || 
        !isset($request['pesapal_transaction_tracking_id'])) {
        return [
            'success' => false,
            'message' => 'Invalid IPN parameters'
        ];
    }
    
    // Extract reference
    $reference = $request['pesapal_merchant_reference'];
    
    // Find transaction by reference
    $trans = ORM::for_table('sys_transactions')
        ->where('ref', $reference)
        ->find_one();
    
    if (!$trans) {
        return [
            'success' => false,
            'message' => 'Transaction not found'
        ];
    }
    
    // Check payment status
    $status = my_payment_gateway_api_request(
        '/payments/status?reference=' . $reference,
        'GET',
        [],
        $config
    );
    
    if (!isset($status['success']) || !$status['success']) {
        return [
            'success' => false,
            'message' => 'Error checking payment status'
        ];
    }
    
    // Map payment status to benfex status
    $paymentStatus = $status['data']['status'];
    $kipayStatus = 'Pending';
    
    switch ($paymentStatus) {
        case 'COMPLETED':
            $kipayStatus = 'Success';
            break;
        case 'FAILED':
            $kipayStatus = 'Failed';
            break;
        case 'CANCELLED':
            $kipayStatus = 'Cancelled';
            break;
    }
    
    // Update transaction status
    $trans->status = $kipayStatus;
    $trans->save();
    
    // If payment is successful, update invoice
    if ($kipayStatus === 'Success') {
        $invoice = ORM::for_table('sys_invoices')->find_one($trans->invoice);
        
        if ($invoice) {
            $invoice->status = 'Paid';
            $invoice->save();
            
            // Add balance to user account
            $user = ORM::for_table('crm_accounts')->find_one($invoice->userid);
            
            if ($user) {
                $user->balance = $user->balance + $invoice->total;
                $user->save();
            }
            
            // Run after payment scripts
            run_hook('payment_successful', [
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total
            ]);
        }
    }
    
    return [
        'success' => true,
        'message' => 'IPN processed successfully'
    ];
}

/**
 * Admin configuration function
 * 
 * @param array $config Current configuration
 * @return string HTML configuration form
 */
function my_payment_gateway_admin_config($config) {
    $html = '
    <div class="form-group">
        <label for="api_url">API URL</label>
        <input type="text" class="form-control" id="api_url" name="api_url" value="' . ($config['api_url'] ?? '') . '" required>
        <small class="form-text text-muted">The URL of your payment gateway API (e.g., https://yourdomain.com/api)</small>
    </div>
    <div class="form-group">
        <label for="api_key">API Key</label>
        <input type="text" class="form-control" id="api_key" name="api_key" value="' . ($config['api_key'] ?? '') . '" required>
        <small class="form-text text-muted">Your payment gateway API key</small>
    </div>
    <div class="form-group">
        <label for="currency_code">Currency Code</label>
        <input type="text" class="form-control" id="currency_code" name="currency_code" value="' . ($config['currency_code'] ?? 'KES') . '" required>
        <small class="form-text text-muted">Default currency code (e.g., KES, USD)</small>
    </div>
    <div class="form-group">
        <label for="environment">Environment</label>
        <select class="form-control" id="environment" name="environment">
            <option value="sandbox" ' . (($config['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '') . '>Sandbox</option>
            <option value="production" ' . (($config['environment'] ?? 'sandbox') === 'production' ? 'selected' : '') . '>Production</option>
        </select>
        <small class="form-text text-muted">Select the environment to use</small>
    </div>';
    
    return $html;
}

/**
 * Make API request to your payment gateway
 * 
 * @param string $endpoint API endpoint
 * @param string $method HTTP method
 * @param array $data Request data
 * @param array $config Gateway configuration
 * @return array Response data
 */
function my_payment_gateway_api_request($endpoint, $method, $data, $config) {
    // Full API URL
    $url = rtrim($config['api_url'], '/') . $endpoint;
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set common cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['api_key'],
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    // Set method-specific options
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // Close the connection
    curl_close($ch);
    
    // Handle errors
    if ($error) {
        return [
            'success' => false,
            'message' => 'cURL Error: ' . $error,
            'http_code' => $httpCode
        ];
    }
    
    // Parse the response
    $result = json_decode($response, true);
    
    // If JSON parsing fails, return the raw response
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Invalid JSON response',
            'http_code' => $httpCode,
            'raw_response' => $response
        ];
    }
    
    return $result;
}

/**
 * Payment callback function
 * 
 * @param array $request Request data
 * @param array $config Gateway configuration
 * @return array Callback result
 */
function my_payment_gateway_callback($request, $config) {
    global $app;
    
    // Log callback
    $app->log('My Payment Gateway Callback: ' . json_encode($request));
    
    // Extract reference from callback parameters
    $reference = $request['pesapal_merchant_reference'] ?? null;
    
    if (!$reference) {
        return [
            'success' => false,
            'message' => 'Missing reference',
            'redirect' => U . 'client/dashboard/'
        ];
    }
    
    // Find transaction by reference
    $trans = ORM::for_table('sys_transactions')
        ->where('ref', $reference)
        ->find_one();
    
    if (!$trans) {
        return [
            'success' => false,
            'message' => 'Transaction not found',
            'redirect' => U . 'client/dashboard/'
        ];
    }
    
    // Find invoice
    $invoice = ORM::for_table('sys_invoices')->find_one($trans->invoice);
    
    if (!$invoice) {
        return [
            'success' => false,
            'message' => 'Invoice not found',
            'redirect' => U . 'client/dashboard/'
        ];
    }
    
    // Redirect to invoice view
    return [
        'success' => true,
        'message' => 'Payment processed',
        'redirect' => U . 'client/iview/' . $invoice->id . '/'
    ];
}