<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Log file for debugging
$logFile = 'piston_api.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($logFile, "=== $timestamp ===\n", FILE_APPEND);

// Get the request data
$requestData = file_get_contents('php://input');
$data = json_decode($requestData, true);

// Validate the request data
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    file_put_contents($logFile, "ERROR: Invalid JSON in request\n\n", FILE_APPEND);
    exit;
}

// Log the request (sanitized for brevity)
$language = $data['language'] ?? 'unknown';
$code = isset($data['files'][0]['content']) ? substr($data['files'][0]['content'], 0, 200) . '...' : 'No code';
$stdin = $data['stdin'] ?? '';

file_put_contents($logFile, "REQUEST:\n", FILE_APPEND);
file_put_contents($logFile, "Language: $language\n", FILE_APPEND);
file_put_contents($logFile, "Code: $code\n", FILE_APPEND);
file_put_contents($logFile, "Input: $stdin\n", FILE_APPEND);

// Piston API endpoint
$pistonApiUrl = 'https://emkc.org/api/v2/piston/execute';

// Initialize cURL
$ch = curl_init($pistonApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($requestData)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30-second timeout

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Log the response
file_put_contents($logFile, "RESPONSE CODE: $httpCode\n", FILE_APPEND);
if ($error) {
    file_put_contents($logFile, "CURL ERROR: $error\n", FILE_APPEND);
} else {
    // Limit log size for large responses
    $logResponse = strlen($response) > 1000 ? substr($response, 0, 1000) . '...' : $response;
    file_put_contents($logFile, "RESPONSE: $logResponse\n", FILE_APPEND);
}
file_put_contents($logFile, "===================\n\n", FILE_APPEND);

// Handle errors
if ($error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'API request failed',
        'details' => $error
    ]);
    exit;
}

// Return the response from Piston
http_response_code($httpCode);
echo $response;
?> 