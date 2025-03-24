<?php
/**
 * Kipay Gateway Callback Handler for Benfex integration
 * 
 * This file handles the callback from Pesapal after a payment attempt
 */

// Include Benfex common files
require_once '../common.php';

// Initialize the application
$app = new Benfex();

// Log all parameters received
$app->logActivity('Kipay Payment Callback: ' . json_encode($_GET));

// Get parameters from Pesapal
$reference = null;
$tracking_id = null;

if (isset($_GET['pesapal_merchant_reference'])) {
    $reference = $_GET['pesapal_merchant_reference'];
}

if (isset($_GET['pesapal_transaction_tracking_id'])) {
    $tracking_id = $_GET['pesapal_transaction_tracking_id'];
}

// If we don't have a reference, redirect to dashboard
if (!$reference) {
    $app->redirect('dashboard', 'e', 'Missing reference in callback');
}

// Find the transaction in the database
$transaction = ORM::for_table('sys_transactions')
    ->where('ref', $reference)
    ->find_one();

if (!$transaction) {
    $app->redirect('dashboard', 'e', 'Transaction not found');
}

// Find the related invoice
$invoice = ORM::for_table('sys_invoices')
    ->find_one($transaction->invoice);

if (!$invoice) {
    $app->redirect('dashboard', 'e', 'Invoice not found');
}

// Update transaction with tracking ID if provided
if ($tracking_id) {
    $transaction->description = $transaction->description . ' (Tracking ID: ' . $tracking_id . ')';
    $transaction->save();
}

// Get payment gateway settings
$gateway_settings = ORM::for_table('tbl_payment_gateways')
    ->where('name', 'Kipay Payment Gateway')
    ->find_one();

$config = [
    'api_url' => $gateway_settings->value,
    'api_key' => $gateway_settings->gateway_key,
    'currency_code' => $gateway_settings->currency_code,
    'environment' => $gateway_settings->mode
];

// Check payment status
$status_check = my_payment_gateway_api_request(
    '/payments/status?reference=' . $reference,
    'GET',
    [],
    $config
);

// If payment is already completed, update status
if (isset($status_check['success']) && $status_check['success'] && 
    isset($status_check['data']['status']) && $status_check['data']['status'] === 'COMPLETED') {
    
    // Update transaction
    $transaction->status = 'Success';
    $transaction->save();
    
    // Update invoice
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
    
    // Redirect to invoice with success message
    $app->redirect('client/iview/' . $invoice->id, 's', 'Payment successful');
} else {
    // Redirect to invoice (payment still pending or failed)
    $app->redirect('client/iview/' . $invoice->id, 'i', 'Payment is being processed');
}