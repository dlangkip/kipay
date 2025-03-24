<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Direct test-route file is working!',
    'time' => date('Y-m-d H:i:s')
]);
