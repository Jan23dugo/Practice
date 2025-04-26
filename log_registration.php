<?php
session_start();

function writeLog($action, $data = []) {
    $log_file = 'logs/registration_log.txt';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $student_id = isset($_SESSION['stud_id']) ? $_SESSION['stud_id'] : 'No ID';
    $client_ip = $_SERVER['REMOTE_ADDR'];
    
    $log_message = sprintf(
        "[%s] Student ID: %s - IP: %s\nAction: %s\n",
        $timestamp,
        $student_id,
        $client_ip,
        $action
    );
    
    if (!empty($data)) {
        $log_message .= "Data: " . print_r($data, true) . "\n";
    }
    
    $log_message .= str_repeat('-', 80) . "\n";
    
    // Append to log file
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Handle incoming log requests
if (isset($_POST['log_request']) && $_POST['log_request'] === '1') {
    $action = $_POST['action'] ?? 'unknown_action';
    $data = json_decode($_POST['data'] ?? '{}', true) ?? [];
    
    writeLog($action, $data);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}
?> 