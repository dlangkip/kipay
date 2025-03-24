<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Simple test file working',
    'time' => date('Y-m-d H:i:s')
]);
