<?php
require_once 'config/config.php';  // For database connection
require_once 'api_config.php';     // For API configurations

class CompilerService {
    
    /**
     * Execute code using Piston API
     * 
     * @param string $language Programming language (python, java, cpp)
     * @param string $code Code to execute
     * @param string $stdin Standard input for the code
     * @return array Execution result
     */
    public function executeCode($language, $code, $stdin = "") {
        // Map our language names to Piston API language values
        $languageMap = [
            'python' => [
                'language' => 'python',
                'version' => '3.10.0'
            ],
            'java' => [
                'language' => 'java',
                'version' => '15.0.2'
            ],
            'cpp' => [
                'language' => 'c++',
                'version' => '10.2.0'
            ]
        ];
        
        if (!isset($languageMap[$language])) {
            return [
                'success' => false,
                'error' => 'Unsupported language'
            ];
        }
        
        $data = [
            'language' => $languageMap[$language]['language'],
            'version' => $languageMap[$language]['version'],
            'files' => [
                [
                    'content' => $code
                ]
            ],
            'stdin' => $stdin
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents(PISTON_API_URL, false, $context);
        
        if ($result === FALSE) {
            return [
                'success' => false,
                'error' => 'Failed to execute code'
            ];
        }
        
        $response = json_decode($result, true);
        
        return [
            'success' => true,
            'output' => $response['run']['output'] ?? '',
            'stderr' => $response['run']['stderr'] ?? '',
            'code' => $response['run']['code'] ?? 0,
            'signal' => $response['run']['signal'] ?? null,
            'execution_time' => $response['run']['time'] ?? 0
        ];
    }
    
    /**
     * Save code submission to database
     * 
     * @param int $userId User ID
     * @param string $language Programming language
     * @param string $code Code content
     * @param string $stdin Standard input
     * @param string $output Execution output
     * @param float $executionTime Execution time
     * @return int Inserted ID
     */
    public function saveSubmission($userId, $language, $code, $stdin, $output, $executionTime) {
        global $conn;
        
        $stmt = $conn->prepare("INSERT INTO code_submissions (user_id, language, code_content, stdin, actual_output, execution_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssd", $userId, $language, $code, $stdin, $output, $executionTime);
        $stmt->execute();
        
        $id = $conn->insert_id;
        
        $stmt->close();
        
        return $id;
    }

    /**
     * Execute code in interactive mode
     * 
     * @param string $language Programming language
     * @param string $code Code content
     * @return array Result with token for interactive session
     */
    public function startInteractiveSession($language, $code) {
        // Similar to executeCode but returns a session ID or token
        // that can be used for subsequent input
        
        // This is a simplified implementation - actual implementation
        // would depend on the API capabilities
        
        $languageMap = [
            'python' => [
                'language' => 'python',
                'version' => '3.10.0'
            ],
            'java' => [
                'language' => 'java',
                'version' => '15.0.2'
            ],
            'cpp' => [
                'language' => 'c++',
                'version' => '10.2.0'
            ]
        ];
        
        // For this example, we'll simulate the interactive behavior with JavaScript
        return [
            'success' => true,
            'language' => $languageMap[$language]
        ];
    }
}
?> 