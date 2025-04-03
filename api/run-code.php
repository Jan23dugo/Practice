<?php
session_start();
header('Content-Type: application/json');

// Update paths to use correct directory traversal
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/api_keys.php');

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify the request is reaching the file
file_put_contents(__DIR__ . '/debug.log', "Request received: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

class Judge0Executor {
    private $apiKey = JUDGE0_API_KEY;
    private $apiHost = 'judge0-ce.p.rapidapi.com';
    private $supportedLanguages = ['python', 'java', 'cpp', 'php', 'javascript'];
    
    // Language IDs for Judge0 API
    private $languageIds = [
        'python' => 71,  // Python 3
        'java' => 62,    // Java
        'cpp' => 54,     // C++
        'php' => 68,     // PHP
        'javascript' => 93 // JavaScript (Node.js)
    ];
    
    public function execute($data) {
        try {
            // Validate input
            $this->validateInput($data);
            
            // Get test cases from database
            $testCases = $this->getTestCases($data['questionId']);
            
            // Execute code against each test case
            $results = [];
            foreach ($testCases as $test) {
                $result = $this->runWithJudge0($data['code'], $data['language'], $test);
                $results[] = $result;
            }
            
            return [
                'success' => true,
                'testCases' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function validateInput($data) {
        if (!isset($data['code'], $data['language'], $data['questionId'])) {
            throw new Exception('Missing required parameters');
        }

        if (!in_array($data['language'], $this->supportedLanguages)) {
            throw new Exception('Unsupported programming language');
        }
    }

    private function getTestCases($questionId) {
        global $conn;
        
        $query = "SELECT * FROM test_cases WHERE question_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $testCases = [];
        while ($row = $result->fetch_assoc()) {
            $testCases[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'input' => $row['input'],
                'expected' => $row['expected'],
                'hidden' => $row['hidden'] ?? false
            ];
        }
        
        if (empty($testCases)) {
            throw new Exception('No test cases found for this question');
        }
        
        return $testCases;
    }

    private function runWithJudge0($code, $language, $test) {
        // Create a submission
        $token = $this->createSubmission($code, $language, $test['input']);
        if (!$token) {
            throw new Exception('Failed to create code execution submission');
        }
        
        // Wait for the result
        sleep(1); // Brief pause to allow processing
        
        // Get the result
        $maxAttempts = 10;
        $attempts = 0;
        $result = null;
        
        do {
            $result = $this->getSubmissionResult($token);
            $attempts++;
            
            // If processing is complete (status 1-2 = processing, 3 = completed)
            if (!isset($result['status']) || $result['status']['id'] >= 3) {
                break;
            }
            
            // Wait between attempts
            sleep(1);
        } while ($attempts < $maxAttempts);
        
        // Process the result
        return $this->processJudge0Result($result, $test);
    }
    
    private function createSubmission($code, $language, $input) {
        $languageId = $this->languageIds[$language] ?? 71; // Default to Python if not found
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://{$this->apiHost}/submissions?base64_encoded=false&wait=false",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'source_code' => $code,
                'language_id' => $languageId,
                'stdin' => $input
            ]),
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: {$this->apiHost}",
                "x-rapidapi-key: {$this->apiKey}",
                "content-type: application/json"
            ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            error_log("Judge0 API Error: $err");
            throw new Exception("API connection error");
        }
        
        $result = json_decode($response, true);
        return $result['token'] ?? null;
    }
    
    private function getSubmissionResult($token) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://{$this->apiHost}/submissions/{$token}?base64_encoded=false",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: {$this->apiHost}",
                "x-rapidapi-key: {$this->apiKey}"
            ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            error_log("Judge0 API Error: $err");
            throw new Exception("API connection error");
        }
        
        return json_decode($response, true);
    }
    
    private function processJudge0Result($result, $test) {
        $statusId = $result['status']['id'] ?? 0;
        $isSuccessful = ($statusId == 3); // Status 3 is "Accepted"
        
        $output = $result['stdout'] ?? '';
        $errors = $result['stderr'] ?? '';
        $compileOutput = $result['compile_output'] ?? '';
        
        // Check if output matches expected result (trim to handle whitespace differences)
        $expectedOutput = trim($test['expected']);
        $actualOutput = trim($output);
        $isPassed = $isSuccessful && ($actualOutput == $expectedOutput);
        
        // Determine error message
        $errorMessage = '';
        if (!$isSuccessful) {
            $errorMessage = $result['status']['description'] ?? 'Execution failed';
            if ($compileOutput) {
                $errorMessage .= ": " . $compileOutput;
            } elseif ($errors) {
                $errorMessage .= ": " . $errors;
            }
        }
        
        return [
            'name' => $test['name'],
            'passed' => $isPassed,
            'actual' => $output,
            'expected' => $test['expected'],
            'error' => $errorMessage,
            'hidden' => $test['hidden']
        ];
    }
}

// Handle the request
try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and decode JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Execute code using Judge0
    $executor = new Judge0Executor();
    $result = $executor->execute($input);

    // Return results
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
