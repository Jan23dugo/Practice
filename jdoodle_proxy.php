<?php
header('Content-Type: application/json');

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the request payload
$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

// Check if JSON was valid
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// Log the request for debugging
$logFile = 'jdoodle_requests.log';
file_put_contents($logFile, "==== " . date('Y-m-d H:i:s') . " ====\n", FILE_APPEND);
file_put_contents($logFile, "REQUEST: " . $postData . "\n", FILE_APPEND);

// Make sure we send the stdin in the correct format
if (isset($data['stdin']) && !empty($data['stdin'])) {
    // Ensure each line ends with a newline, including the last one
    if (!str_ends_with($data['stdin'], "\n")) {
        $data['stdin'] .= "\n";
    }
    
    // Update the post data with properly formatted stdin
    $postData = json_encode($data);
}

// Check if curl is available
if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: cURL is not available']);
    file_put_contents($logFile, "ERROR: cURL not available\n\n", FILE_APPEND);
    exit;
}

// Forward to JDoodle API
$ch = curl_init('https://api.jdoodle.com/v1/execute');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Log the response
file_put_contents($logFile, "STATUS: $httpCode\n", FILE_APPEND);
file_put_contents($logFile, "RESPONSE: $response\n\n", FILE_APPEND);

// Check for errors
if (curl_errno($ch)) {
    $error = curl_error($ch);
    file_put_contents($logFile, "CURL ERROR: $error\n\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['error' => "API connection error: $error"]);
    exit;
}

curl_close($ch);

// Return the response to the client
http_response_code($httpCode);
echo $response;

// Helper function for PHP versions < 8.0
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}
?> 