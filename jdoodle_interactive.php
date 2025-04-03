<?php
header('Content-Type: application/json');

// Get the request payload
$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

// Ensure input is properly formatted
if (isset($data['stdin']) && $data['stdin'] !== '') {
    // Make sure input ends with newline
    if (!str_ends_with($data['stdin'], "\n")) {
        $data['stdin'] .= "\n";
    }
    
    // Update the post data
    $postData = json_encode($data);
}

// Log API usage for debugging
$logFile = 'jdoodle_interactive_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Interactive API Call\n", FILE_APPEND);
file_put_contents($logFile, "Language: " . $data['language'] . "\n", FILE_APPEND);
file_put_contents($logFile, "Input: " . ($data['stdin'] ?? 'none') . "\n", FILE_APPEND);

// Forward to JDoodle
$ch = curl_init('https://api.jdoodle.com/v1/execute');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Log response for debugging
file_put_contents($logFile, "Status: $httpCode\n", FILE_APPEND);
file_put_contents($logFile, "Response: $response\n\n", FILE_APPEND);

curl_close($ch);

// Return the response
http_response_code($httpCode);
echo $response;

// Helper function for PHP versions < 8.0
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
?> 