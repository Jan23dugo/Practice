<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // Keep errors hidden from API consumers
error_reporting(E_ALL);
// Log errors to a file is recommended for production/debugging
ini_set('log_errors', 1);
// Ensure this path exists and is writable by the web server
ini_set('error_log', __DIR__ . '/php-error.log'); 

// Required Files
require_once('config/config.php');          // Provides $conn (mysqli connection)
require_once('config/api_keys.php');        // Provides Judge0 API configuration
require_once('includes/judge0_service.php'); // Provides executeCodeAgainstTestCases()

$response = []; // Initialize response array

function debugLog($message, $data = null) {
    $logFile = __DIR__ . '/judge0_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    if ($data !== null) {
        $logMessage .= "Data: " . print_r($data, true) . "\n";
    }
    $logMessage .= str_repeat('-', 80) . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function executeCode($code, $input = '') {
    // Create submission
    $submission_url = JUDGE0_API_URL . '/submissions?base64_encoded=true&wait=false';
    
    $submission_data = [
        'source_code' => base64_encode($code),
        'language_id' => 71,  // Python 3
        'stdin' => '',
        'expected_output' => ''
    ];

    debugLog("Submission data", $submission_data);
    debugLog("Submission URL", $submission_url);

    // Initialize cURL for submission
    $ch = curl_init($submission_url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($submission_data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-RapidAPI-Host: ' . JUDGE0_HOST,
            'X-RapidAPI-Key: ' . JUDGE0_API_KEY
        ]
    ]);

    // Log curl info before execution
    debugLog("CURL Options", curl_getinfo($ch));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    debugLog("CURL Response", [
        "http_code" => $httpCode,
        "response" => $response,
        "curl_error" => curl_error($ch)
    ]);

    if (curl_errno($ch)) {
        throw new Exception('CURL Error: ' . curl_error($ch));
    }

    if ($httpCode !== 200) {
        throw new Exception('API Error: HTTP ' . $httpCode . ' - ' . $response);
    }

    $submission = json_decode($response, true);
    if (!isset($submission['token'])) {
        throw new Exception('Invalid response: No token received - ' . $response);
    }

    // Get submission result
    $token = $submission['token'];
    debugLog("Received token", $token);

    // Wait for result
    $result = null;
    $attempts = 0;
    $maxAttempts = 10;

    while ($attempts < $maxAttempts) {
        $result_url = JUDGE0_API_URL . '/submissions/' . $token . '?base64_encoded=true';
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $result_url,
            CURLOPT_HTTPGET => true
        ]);

        $response = curl_exec($ch);
        debugLog("Result attempt " . ($attempts + 1), $response);

        $result = json_decode($response, true);
        
        if (isset($result['status']['id']) && $result['status']['id'] > 2) {
            break;
        }

        $attempts++;
        sleep(1);
    }

    curl_close($ch);

    if (!$result) {
        throw new Exception('Failed to get submission result');
    }

    return $result;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $code = $_POST['code'] ?? '';
    $language = $_POST['language'] ?? 'python';
    $questionId = $_POST['question_id'] ?? '';
    $programmingId = $_POST['programming_id'] ?? '';

    if (empty($code)) {
        throw new Exception('No code provided');
    }

    $results = executeCodeAgainstTestCases($conn, $code, $language, $programmingId);

    echo json_encode([
        'success' => true,
        'result' => $results
    ]);

} catch (Exception $e) {
    error_log("Error in test_exam.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// No trailing PHP tag needed if it's the end of the file