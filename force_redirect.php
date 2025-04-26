<?php
session_start();

function writeLog($message) {
    $log_file = 'logs/redirect_log.txt';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

if (isset($_POST['redirect'])) {
    writeLog("Force redirect requested");
    
    // Clear any output that might have been sent
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    // Force redirect
    header("Location: registration_success.php");
    writeLog("Redirect headers sent");
    exit();
} else {
    writeLog("Invalid redirect request");
    echo json_encode(['error' => 'Invalid request']);
}
?> 