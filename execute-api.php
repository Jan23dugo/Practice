<?php
// Execute API proxy script with improved error handling

// Set appropriate headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // For development - restrict in production
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// For preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Create log file
$logFile = 'jdoodle_api.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - API Call\n", FILE_APPEND);
file_put_contents($logFile, "Language: " . ($data['language'] ?? 'unknown') . "\n", FILE_APPEND);

// Validate input
if (!$data || !isset($data['script']) || !isset($data['language'])) {
    file_put_contents($logFile, "ERROR: Invalid request data\n\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// Get API credentials
$clientId = $data['clientId'] ?? '8c686c1b1579a59d4b1757074bb59fd2';
$clientSecret = $data['clientSecret'] ?? '33aff191a34149669be03ad9e1853e67a894f9460fff913952c1e2441ea4ac77';

// Format the stdin properly - ensure every line ends with a newline
$stdin = isset($data['stdin']) ? $data['stdin'] : '';
if (!empty($stdin) && !str_ends_with($stdin, "\n")) {
    $stdin .= "\n";
}

// Prepare JDoodle API request
$apiUrl = 'https://api.jdoodle.com/v1/execute';
$postData = [
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'script' => $data['script'],
    'language' => $data['language'],
    'versionIndex' => $data['versionIndex'] ?? '0',
    'stdin' => $stdin
];

// Log the request (without showing full credentials)
$logPostData = $postData;
$logPostData['clientId'] = substr($clientId, 0, 5) . '...';
$logPostData['clientSecret'] = substr($clientSecret, 0, 5) . '...';
file_put_contents($logFile, "REQUEST: " . json_encode($logPostData) . "\n", FILE_APPEND);
file_put_contents($logFile, "INPUT DATA: '" . $stdin . "'\n", FILE_APPEND);

// Initialize cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: ExamSystem/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30-second timeout
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorMsg = curl_error($ch);
$errorNum = curl_errno($ch);

// Log the response
file_put_contents($logFile, "RESPONSE STATUS: $httpCode\n", FILE_APPEND);
file_put_contents($logFile, "RESPONSE: $response\n", FILE_APPEND);
if ($errorNum) {
    file_put_contents($logFile, "ERROR: cURL Error ($errorNum): $errorMsg\n", FILE_APPEND);
}
file_put_contents($logFile, "===================\n\n", FILE_APPEND);

// Handle cURL errors
if ($errorNum) {
    http_response_code(500);
    echo json_encode([
        'error' => 'API connection failed',
        'details' => $errorMsg,
        'code' => $errorNum
    ]);
    exit;
}

// Handle HTTP errors
if ($httpCode != 200) {
    $statusMessages = [
        400 => 'Bad Request - Check your input parameters',
        401 => 'Unauthorized - Check your API credentials',
        403 => 'Forbidden - You might have exceeded your API limits or have an invalid subscription',
        429 => 'Too Many Requests - You have exceeded your rate limit',
        500 => 'Internal Server Error - JDoodle server issue',
        503 => 'Service Unavailable - JDoodle may be down for maintenance'
    ];
    
    $message = $statusMessages[$httpCode] ?? "HTTP Error $httpCode";
    
    try {
        // Try to parse the error message from JDoodle
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['error'])) {
            $message .= ": " . $responseData['error'];
        }
    } catch (Exception $e) {
        // If we can't parse the response, just use our generic message
    }
    
    http_response_code($httpCode);
    echo json_encode([
        'error' => $message,
        'status' => $httpCode
    ]);
    exit;
}

// Return the JDoodle response
echo $response;

// Helper function for PHP < 8.0
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}
?> 