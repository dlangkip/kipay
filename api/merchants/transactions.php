<?php
// /api/merchants/transactions.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration
require_once __DIR__ . '/../config.php';

// Authentication function
function authenticateMerchant() {
    global $CONFIG;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $apiKey = $matches[1];
    
    $db = new PDO(
        "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $db->prepare("SELECT id FROM merchants WHERE api_key = :api_key AND status = 'ACTIVE'");
    $stmt->execute([':api_key' => $apiKey]);
    $merchant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$merchant) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    
    return $merchant['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Authenticate merchant
        $merchantId = authenticateMerchant();
        
        // Connect to database
        $db = new PDO(
            "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']}",
            $CONFIG['db_user'],
            $CONFIG['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Build query conditions
        $conditions = ['merchant_id = :merchant_id'];
        $params = [':merchant_id' => $merchantId];
        
        // Filter by status if provided
        if (!empty($_GET['status'])) {
            $conditions[] = 'status = :status';
            $params[':status'] = $_GET['status'];
        }
        
        // Filter by date range if provided
        if (!empty($_GET['from_date'])) {
            $conditions[] = 'created_at >= :from_date';
            $params[':from_date'] = $_GET['from_date'] . ' 00:00:00';
        }
        
        if (!empty($_GET['to_date'])) {
            $conditions[] = 'created_at <= :to_date';
            $params[':to_date'] = $_GET['to_date'] . ' 23:59:59';
        }
        
        // Filter by payment method if provided
        if (!empty($_GET['payment_method'])) {
            $conditions[] = 'payment_method = :payment_method';
            $params[':payment_method'] = $_GET['payment_method'];
        }
        
        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        // Count total records
        $countSql = "
            SELECT COUNT(*) 
            FROM transactions 
            WHERE " . implode(' AND ', $conditions);
        
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $totalRecords = $stmt->fetchColumn();
        
        // Get transactions
        $sql = "
            SELECT * 
            FROM transactions 
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $db->prepare($sql);
        
        // Bind all parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate summary statistics
        $summary = [
            'total_transactions' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit),
            'current_page' => $page,
            'transactions_per_page' => $limit
        ];
        
        // Get totals by status
        $statusSql = "
            SELECT status, COUNT(*) as count, SUM(amount) as total_amount
            FROM transactions
            WHERE merchant_id = :merchant_id
            GROUP BY status
        ";
        
        $stmt = $db->prepare($statusSql);
        $stmt->execute([':merchant_id' => $merchantId]);
        $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary['status_summary'] = [];
        foreach ($statusStats as $stat) {
            $summary['status_summary'][$stat['status']] = [
                'count' => (int)$stat['count'],
                'total_amount' => (float)$stat['total_amount']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'summary' => $summary
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving transactions',
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}