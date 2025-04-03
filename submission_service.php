<?php
require_once 'config/config.php';  // For database connection
require_once 'api_config.php';     // For API configurations

class SubmissionService {
    
    /**
     * Submit code to Judge0 for checking
     * 
     * @param string $language Programming language
     * @param string $code Code content
     * @param string $stdin Standard input
     * @param string $expectedOutput Expected output
     * @return array Submission result
     */
    public function submitCode($language, $code, $stdin, $expectedOutput) {
        // Map language to Judge0 language IDs
        $languageIdMap = [
            'python' => LANGUAGE_PYTHON,
            'java' => LANGUAGE_JAVA,
            'cpp' => LANGUAGE_CPP
        ];
        
        if (!isset($languageIdMap[$language])) {
            return [
                'success' => false,
                'error' => 'Unsupported language'
            ];
        }
        
        $data = [
            'language_id' => $languageIdMap[$language],
            'source_code' => base64_encode($code),
            'stdin' => base64_encode($stdin),
            'expected_output' => base64_encode($expectedOutput)
        ];
        
        $options = [
            'http' => [
                'header' => [
                    "Content-type: application/json\r\n",
                    "X-RapidAPI-Host: judge0-ce.p.rapidapi.com\r\n",
                    "X-RapidAPI-Key: " . JUDGE0_API_KEY . "\r\n"
                ],
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents(JUDGE0_API_URL . '/submissions?base64_encoded=true', false, $context);
        
        if ($result === FALSE) {
            return [
                'success' => false,
                'error' => 'Failed to submit code'
            ];
        }
        
        $response = json_decode($result, true);
        
        return [
            'success' => true,
            'token' => $response['token']
        ];
    }
    
    /**
     * Get submission result from Judge0
     * 
     * @param string $token Submission token
     * @return array Submission result
     */
    public function getSubmissionResult($token) {
        $options = [
            'http' => [
                'header' => [
                    "X-RapidAPI-Host: judge0-ce.p.rapidapi.com\r\n",
                    "X-RapidAPI-Key: " . JUDGE0_API_KEY . "\r\n"
                ],
                'method' => 'GET'
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents(JUDGE0_API_URL . '/submissions/' . $token . '?base64_encoded=true', false, $context);
        
        if ($result === FALSE) {
            return [
                'success' => false,
                'error' => 'Failed to get submission result'
            ];
        }
        
        $response = json_decode($result, true);
        
        // Decode base64 content
        $output = isset($response['stdout']) ? base64_decode($response['stdout']) : '';
        $stderr = isset($response['stderr']) ? base64_decode($response['stderr']) : '';
        $compileOutput = isset($response['compile_output']) ? base64_decode($response['compile_output']) : '';
        
        return [
            'success' => true,
            'status' => [
                'id' => $response['status']['id'],
                'description' => $response['status']['description']
            ],
            'output' => $output,
            'stderr' => $stderr,
            'compile_output' => $compileOutput,
            'time' => $response['time'],
            'memory' => $response['memory']
        ];
    }
    
    /**
     * Update submission in database with Judge0 results
     * 
     * @param int $submissionId Submission ID
     * @param array $result Judge0 result
     * @return bool Success status
     */
    public function updateSubmissionResult($submissionId, $result) {
        global $conn;
        
        $status = $result['status']['description'];
        $output = $result['output'];
        $time = $result['time'];
        $memory = $result['memory'];
        $score = ($result['status']['id'] == 3) ? 100 : 0; // 3 = Accepted
        
        $stmt = $conn->prepare("UPDATE code_submissions SET status = ?, actual_output = ?, execution_time = ?, memory_used = ?, score = ? WHERE id = ?");
        $stmt->bind_param("ssddii", $status, $output, $time, $memory, $score, $submissionId);
        $success = $stmt->execute();
        
        $stmt->close();
        
        return $success;
    }
}
?> 