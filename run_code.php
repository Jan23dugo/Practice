<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once('config/config.php');

// Add logging function
function logDebug($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data) {
        $log .= "\nData: " . print_r($data, true);
    }
    file_put_contents('debug_log.txt', $log . "\n\n", FILE_APPEND);
}

// Judge0 API configuration
$JUDGE0_API = "https://judge0-ce.p.rapidapi.com";
$JUDGE0_KEY = "4d7aa240c4msh4e6c61ed564b368p16fa93jsn2b6933cea7fc";

// Headers for Judge0 API
$headers = [
    "X-RapidAPI-Host: judge0-ce.p.rapidapi.com",
    "X-RapidAPI-Key: " . $JUDGE0_KEY,
    "Content-Type: application/json"
];

// Function to submit code to Judge0
function submitToJudge0($code, $input) {
    global $JUDGE0_API, $headers;
    
    // Clean up the input and code
    $code = trim($code);
    $input = trim($input);
    
    logDebug("Original code received", ['code' => $code]);
    
    // Create the complete program with proper input handling
    $complete_code = <<<EOT
{$code}

# Get input and process it
try:
    input_str = input().strip()
    logDebug("Input received:", input_str)
    a, b = map(int, input_str.split())
    result = sum_numbers(a, b)
    print(str(result))  # Explicitly convert to string
except Exception as e:
    import sys
    print(f"Error: {str(e)}", file=sys.stderr)
EOT;

    logDebug("Complete code to be submitted", ['complete_code' => $complete_code]);

    $data = [
        "language_id" => 71, // Python (3.8.1)
        "source_code" => $complete_code,
        "stdin" => $input,
        "wait" => true,
        "memory_limit" => 262144,
        "cpu_time_limit" => 5
    ];

    logDebug("Submitting request to Judge0", [
        'api_url' => $JUDGE0_API . "/submissions?base64_encoded=false&wait=true",
        'data' => $data
    ]);

    $ch = curl_init($JUDGE0_API . "/submissions?base64_encoded=false&wait=true");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    logDebug("Judge0 Raw Response", [
        'httpCode' => $httpCode,
        'response' => $response,
        'curl_error' => curl_error($ch),
        'curl_info' => curl_getinfo($ch)
    ]);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        logDebug("Curl Error", ['error' => $error]);
        throw new Exception('Curl error: ' . $error);
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!$result) {
        logDebug("Failed to decode Judge0 response", ['raw_response' => $response]);
        throw new Exception('Failed to decode Judge0 response');
    }

    logDebug("Decoded Judge0 Response", [
        'stdout' => $result['stdout'] ?? 'null',
        'stderr' => $result['stderr'] ?? 'null',
        'status' => $result['status'] ?? 'null',
        'memory' => $result['memory'] ?? 'null',
        'time' => $result['time'] ?? 'null'
    ]);
    
    return $result;
}

// Function to get submission result
function getSubmissionResult($token) {
    global $JUDGE0_API, $headers;
    
    $maxAttempts = 10;
    $attempt = 0;
    
    do {
        $ch = curl_init($JUDGE0_API . "/submissions/" . $token);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false // For testing only
        ]);
        
        $response = curl_exec($ch);
        $result = json_decode($response, true);
        
        logDebug("Checking submission status", [
            'token' => $token,
            'attempt' => $attempt,
            'result' => $result
        ]);
        
        curl_close($ch);
        
        if (isset($result['status']['id']) && $result['status']['id'] >= 3) {
            return $result;
        }
        
        $attempt++;
        sleep(1); // Wait 1 second before checking again
        
    } while ($attempt < $maxAttempts);
    
    throw new Exception('Timeout waiting for code execution result');
}

header('Content-Type: application/json');

function executeCode($code, $input = '') {
    $url = 'https://emkc.org/api/v2/piston/execute';
    
    $data = [
        'language' => 'python',
        'version' => '3.10',
        'files' => [
            [
                'content' => $code
            ]
        ],
        'stdin' => $input
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

try {
    if (!isset($_POST['code'])) {
        throw new Exception('No code provided');
    }

    $code = $_POST['code'];
    $input = $_POST['input'] ?? '';

    $result = executeCode($code, $input);

    echo json_encode([
        'success' => true,
        'output' => $result['output'] ?? '',
        'error' => $result['stderr'] ?? '',
        'compile_error' => $result['compile_output'] ?? ''
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
