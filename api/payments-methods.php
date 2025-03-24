<?php
header('Content-Type: application/json');
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
