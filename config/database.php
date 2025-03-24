<?php
/**
 * Database Configuration
 * 
 * This file contains database connection settings and helper functions
 */

// Get database settings from main config
$db_host = CONFIG['db_host'];
$db_name = CONFIG['db_name'];
$db_user = CONFIG['db_user'];
$db_pass = CONFIG['db_pass'];

// Database connection
$db = null;

/**
 * Get database connection
 * 
 * @return PDO Database connection
 */
function getDB() {
    global $db, $db_host, $db_name, $db_user, $db_pass;
    
    if ($db === null) {
        try {
            $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $db = new \PDO($dsn, $db_user, $db_pass, $options);
        } catch (\PDOException $e) {
            // Log the error
            error_log("Database connection error: " . $e->getMessage());
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $db;
}

/**
 * Execute a SELECT query
 * 
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @param bool $single Return a single row
 * @return array|null Query results
 */
function dbSelect($sql, $params = [], $single = false) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $single ? $stmt->fetch() : $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return null;
    }
}

/**
 * Execute an INSERT query
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|false Last insert ID or false on failure
 */
function dbInsert($table, $data) {
    try {
        $db = getDB();
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $db->lastInsertId();
    } catch (\PDOException $e) {
        error_log("Database insert error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute an UPDATE query
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $where WHERE clause
 * @param array $whereParams WHERE parameters
 * @return int|false Number of affected rows or false on failure
 */
function dbUpdate($table, $data, $where, $whereParams = []) {
    try {
        $db = getDB();
        
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $whereParams));
        
        return $stmt->rowCount();
    } catch (\PDOException $e) {
        error_log("Database update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a DELETE query
 * 
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params WHERE parameters
 * @return int|false Number of affected rows or false on failure
 */
function dbDelete($table, $where, $params = []) {
    try {
        $db = getDB();
        
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    } catch (\PDOException $e) {
        error_log("Database delete error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if database exists
 *
 * @return bool True if database exists
 */
function dbExists() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    try {
        $dsn = "mysql:host={$db_host}";
        $db = new \PDO($dsn, $db_user, $db_pass);
        
        $stmt = $db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$db_name}'");
        $result = $stmt->fetchAll();
        
        return count($result) > 0;
    } catch (\PDOException $e) {
        return false;
    }
}

/**
 * Check if database is properly set up
 * 
 * @return bool True if database is properly set up
 */
function dbIsSetUp() {
    if (!dbExists()) {
        return false;
    }
    
    // Check if required tables exist
    $requiredTables = [
        'transactions', 'webhooks', 'api_keys', 
        'notifications', 'settings', 'audit_logs'
    ];
    
    $existingTables = dbSelect("SHOW TABLES");
    $existingTableNames = [];
    
    foreach ($existingTables as $table) {
        $existingTableNames[] = current($table);
    }
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTableNames)) {
            return false;
        }
    }
    
    return true;
}