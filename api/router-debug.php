<?php
header('Content-Type: application/json');

$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$php_self = $_SERVER['PHP_SELF'];
$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null;

echo json_encode([
    'success' => true,
    'server_vars' => [
        'REQUEST_URI' => $request_uri,
        'SCRIPT_NAME' => $script_name,
        'PHP_SELF' => $php_self,
        'PATH_INFO' => $path_info,
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
        'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME']
    ]
]);
