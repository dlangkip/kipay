<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

echo json_encode([
    'success' => true,
    'message' => 'Payment methods retrieved successfully',
    'data' => [
        'channels' => [
            'mobile_money' => [
                'MPESA' => 'M-Pesa',
                'AIRTEL' => 'Airtel Money'
            ],
            'cards' => [
                'VISA' => 'Visa',
                'MASTERCARD' => 'Mastercard'
            ]
        ]
    ]
]);
