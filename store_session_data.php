<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON response headers
header('Content-Type: application/json');

// Function to log registration activity
function logRegistrationActivity($message, $data = null) {
    $log_file = 'logs/registration_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $student_id = isset($_SESSION['stud_id']) ? $_SESSION['stud_id'] : 'No ID';
    $log_message = "[{$timestamp}] Student ID: {$student_id}\n";
    $log_message .= "Step: {$message}\n";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_message .= "Data: " . print_r($data, true) . "\n";
        } else {
            $log_message .= "Data: {$data}\n";
        }
    }
    
    $log_message .= str_repeat('-', 80) . "\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

try {
    // Log the start of session storage
    logRegistrationActivity('Starting session data storage');

    // Get the JSON data from the request
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data received');
    }

    // Validate required data
    if (!isset($data['student']) || !isset($data['subjects'])) {
        throw new Exception('Missing required data fields');
    }

    // Store data in session
    $_SESSION['registration_data'] = [
        'student' => $data['student'],
        'subjects' => $data['subjects'],
        'timestamp' => time()
    ];

    // Log successful storage
    logRegistrationActivity('Session data stored successfully', [
        'session_keys' => array_keys($_SESSION),
        'student_data_keys' => array_keys($_SESSION['registration_data']),
        'subjects_count' => count($_SESSION['registration_data']['subjects'])
    ]);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Session data stored successfully'
    ]);

} catch (Exception $e) {
    // Log error
    logRegistrationActivity('Error storing session data', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 