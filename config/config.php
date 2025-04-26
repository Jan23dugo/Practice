<?php
// Prevent direct access to this file
defined('BASEPATH') or define('BASEPATH', true);
if (!defined('BASEPATH')) {
    header('HTTP/1.1 403 Forbidden');
    exit('No direct script access allowed');
}

// Error reporting based on environment
$environment = 'development'; // Change to 'production' when deploying
if ($environment === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Security headers - Compatible with shared hosting
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Database configuration - Update these with Hostinger credentials
$db_config = [
    'hostname' => 'localhost', // Usually provided by Hostinger
    'username' => 'root',      // Change to your Hostinger database username
    'password' => '',          // Change to your Hostinger database password
    'database' => 'exam',      // Change to your Hostinger database name
    'charset'  => 'utf8mb4',
    'port'     => 3306
];

// Custom error handler that works on shared hosting
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('Y-m-d H:i:s') . " Error: [$errno] $errstr in $errfile on line $errline\n";
    
    // Use the hosting-provided logs directory or create in allowed location
    $log_dir = __DIR__ . '/../logs';
    if (is_writable($log_dir)) {
        error_log($error_message, 3, $log_dir . '/error.log');
    }
    
    if ($GLOBALS['environment'] === 'production') {
        return true;
    }
    return false;
}
set_error_handler('customErrorHandler');

try {
    // Create connection with error handling
    $conn = new mysqli(
        $db_config['hostname'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database'],
        $db_config['port']
    );

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset
    $conn->set_charset($db_config['charset']);
    
    // Set SQL mode - Compatible with shared hosting
    $conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
    // Set session timeout - Compatible with shared hosting
    $conn->query("SET SESSION wait_timeout = 300");
    
    // Prepare statement function to prevent SQL injection
    function prepareAndExecute($conn, $sql, $params = [], $types = '') {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt;
    }

} catch (Exception $e) {
    // Log the error if possible
    if (is_writable(__DIR__ . '/../logs')) {
        error_log("Database Error: " . $e->getMessage(), 0);
    }
    
    // Handle the error based on request type
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(500);
        if ($environment === 'development') {
            echo json_encode(['error' => $e->getMessage()]);
        } else {
            echo json_encode(['error' => 'A database error occurred']);
        }
    } else {
        if ($environment === 'development') {
            die("Database Error: " . $e->getMessage());
        } else {
            die("A database error occurred. Please try again later.");
        }
    }
    exit;
}

// Input sanitization function
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Email validation function
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// SQL injection pattern detection
function containsSQLInjection($string) {
    $sql_patterns = [
        '/\bUNION\b/i',
        '/\bSELECT\b/i',
        '/\bINSERT\b/i',
        '/\bUPDATE\b/i',
        '/\bDELETE\b/i',
        '/\bDROP\b/i',
        '/\bTRUNCATE\b/i',
        '/\bOR\b[\s\d]*?[\'"\)=\d]/',
        '/\bAND\b[\s\d]*?[\'"\)=\d]/'
    ];
    
    foreach ($sql_patterns as $pattern) {
        if (preg_match($pattern, $string)) {
            return true;
        }
    }
    return false;
}

// Create logs directory if writable
$log_dir = __DIR__ . '/../logs';
if (!file_exists($log_dir) && is_writable(__DIR__ . '/..')) {
    mkdir($log_dir, 0755, true);
}
?>