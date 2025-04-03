<?php

// Make sure API keys are loaded. Adjust path if needed.
require_once __DIR__ . '/../config/api_keys.php'; 

// Update language ID mapping with latest Judge0 CE language IDs
const JUDGE0_LANGUAGES = [
    'python' => 71,    // Python (3.8.1)
    'cpp' => 54,       // C++ (GCC 9.2.0)
    'java' => 62,      // Java (OpenJDK 13.0.1)
];

/**
 * Executes the given code against test cases fetched for a programming question
 * using the Judge0 API.
 *
 * @param mysqli $conn Database connection object.
 * @param string $code The source code to execute.
 * @param string $language The programming language ('python', 'java', 'cpp').
 * @param int $programmingId The ID of the programming question to fetch test cases for.
 * @return array An array containing the results for each test case.
 * @throws Exception If database errors, cURL errors, API errors, or configuration errors occur.
 */
function executeCodeAgainstTestCases(mysqli $conn, string $code, string $language, int $programmingId): array 
{
    // Validate language support
    if (!isset(JUDGE0_LANGUAGES[$language])) {
        throw new Exception("Unsupported language: $language. Supported languages are: " . implode(', ', array_keys(JUDGE0_LANGUAGES)));
    }
    $languageId = JUDGE0_LANGUAGES[$language];

    // Add debug logging
    error_log("Executing code with language: $language (ID: $languageId)");
    error_log("Code to execute: " . substr($code, 0, 100) . "...");

    // Validate API configuration
    if (!defined('JUDGE0_API_KEY') || empty(JUDGE0_API_KEY)) {
        throw new Exception("Judge0 API key is not configured");
    }

    // --- Fetch Test Cases ---
    $stmt = $conn->prepare("SELECT test_case_id, input, expected_output FROM test_cases WHERE programming_id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare error fetching test cases: ' . $conn->error);
    }
    $stmt->bind_param("i", $programmingId);
    if (!$stmt->execute()) {
        throw new Exception('Database execution error fetching test cases: ' . $stmt->error);
    }
    $result = $stmt->get_result();
    $testCases = $result->fetch_all(MYSQLI_ASSOC); // Fetch all at once
    $stmt->close();

    if (empty($testCases)) {
        // Decide if this is an error or just means no tests to run
        error_log("No test cases found for programming_id: $programmingId. Returning empty results array.");
        return []; 
    }

    // --- Execute Code Against Each Test Case ---
    $results = [];
    $judge0Url = JUDGE0_API_URL . '/submissions?base64_encoded=true&wait=true';

    foreach ($testCases as $test) {
        error_log("Processing test case ID: " . $test['test_case_id'] . " for programming_id: " . $programmingId);

        // Fix: Base64 encode the source code
        $submissionData = [
            'source_code' => base64_encode($code),
            'language_id' => $languageId,
            'stdin' => base64_encode($test['input']),
            'expected_output' => base64_encode($test['expected_output']),
            'cpu_time_limit' => 5,    // Increased time limit
            'memory_limit' => 128000,
            'base64_encoded' => true
        ];

        // Debug log the submission
        error_log("Submitting to Judge0 with data: " . json_encode($submissionData));

        $ch = curl_init(JUDGE0_API_URL . '/submissions?base64_encoded=true&wait=true');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($submissionData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-RapidAPI-Host: ' . JUDGE0_HOST,
                'X-RapidAPI-Key: ' . JUDGE0_API_KEY
            ]
        ]);

        // Execute and get response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Debug log the response
        error_log("Judge0 Response (HTTP $httpCode): $response");

        if (curl_errno($ch)) {
            error_log("Curl Error: " . curl_error($ch));
            throw new Exception("Connection error: " . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Judge0 API Error: HTTP $httpCode - $response");
            throw new Exception("Judge0 API Error: " . ($response ?: "HTTP $httpCode"));
        }

        $executionResult = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
             error_log("Failed to parse Judge0 JSON response for test case " . $test['test_case_id'] . ". Error: " . json_last_error_msg() . ". Response: " . $response);
            throw new Exception('Failed to parse code execution result: ' . json_last_error_msg());
        }
        
        // --- Process Individual Test Case Result ---
         if (!isset($executionResult['status']['id'])) {
             error_log("Judge0 response missing status information for test case " . $test['test_case_id'] . ": " . $response);
            // Assign a default error status or throw? Let's assign a status.
            $executionResult['status'] = ['id' => -1, 'description' => 'Error: Malformed API Response'];
        }

        $passed = ($executionResult['status']['id'] === 3); // 3 = Accepted

        $results[] = [
            'test_case_id' => $test['test_case_id'], // Good for debugging
            'input' => $test['input'],
            'expected' => $test['expected_output'],
            // Use nullish coalescing operator for safer access
            'actual' => base64_decode($executionResult['stdout'] ?? ''),
            'error' => base64_decode($executionResult['stderr'] ?? ''),
            'compile_output' => base64_decode($executionResult['compile_output'] ?? ''),
            'passed' => $passed,
            'status' => $executionResult['status'], // Already checked/defaulted above
            'time' => $executionResult['time'] ?? null,
            'memory' => $executionResult['memory'] ?? null // Memory in KB
        ];
    }

    return $results;
} 