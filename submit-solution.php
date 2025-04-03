<?php
// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['code']) || !isset($data['language']) || !isset($data['questionId']) || 
    !isset($data['examId']) || !isset($data['studentId'])) {
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Connect to database
$db = new PDO('mysql:host=localhost;dbname=your_exam_db', 'username', 'password');

try {
    // Start transaction
    $db->beginTransaction();
    
    // Save submission to database
    $stmt = $db->prepare('INSERT INTO submissions (student_id, exam_id, question_id, language, code, submitted_at) 
                          VALUES (?, ?, ?, ?, ?, NOW())');
    
    $stmt->execute([
        $data['studentId'],
        $data['examId'],
        $data['questionId'],
        $data['language'],
        $data['code']
    ]);
    
    $submissionId = $db->lastInsertId();
    
    // Evaluate with Judge0 API
    $testResults = evaluateWithJudge0($data['code'], $data['language'], $data['questionId']);
    
    // Save test results
    foreach ($testResults as $result) {
        $stmt = $db->prepare('INSERT INTO test_results (submission_id, test_case_id, passed, output) 
                              VALUES (?, ?, ?, ?)');
        
        $stmt->execute([
            $submissionId,
            $result['testCaseId'],
            $result['passed'] ? 1 : 0,
            $result['output']
        ]);
    }
    
    // Calculate score
    $passedTests = array_filter($testResults, function($result) {
        return $result['passed'];
    });
    
    $score = count($passedTests) / count($testResults) * 100;
    
    // Update submission with score
    $stmt = $db->prepare('UPDATE submissions SET score = ?, evaluated_at = NOW() WHERE id = ?');
    $stmt->execute([$score, $submissionId]);
    
    // Commit transaction
    $db->commit();
    
    // Return success with test results
    echo json_encode([
        'success' => true,
        'message' => 'Solution submitted and evaluated successfully.',
        'submissionId' => $submissionId,
        'score' => $score,
        'testResults' => $testResults
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

// Function to evaluate code with Judge0
function evaluateWithJudge0($code, $language, $questionId) {
    // Map language to Judge0 language ID
    $languageMap = [
        'javascript' => 63,  // Node.js
        'python' => 71,      // Python 3
        'php' => 68          // PHP
    ];
    
    // Get test cases for this question
    global $db;
    $stmt = $db->prepare('SELECT * FROM test_cases WHERE question_id = ?');
    $stmt->execute([$questionId]);
    $testCases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    
    foreach ($testCases as $testCase) {
        // Prepare the submission
        $submissionData = [
            'source_code' => $code,
            'language_id' => $languageMap[$language],
            'stdin' => $testCase['input'],
            'expected_output' => $testCase['expected_output']
        ];
        
        // Call Judge0 API
        $ch = curl_init('https://judge0-ce.p.rapidapi.com/submissions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($submissionData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-RapidAPI-Host: judge0-ce.p.rapidapi.com',
            'X-RapidAPI-Key: YOUR_RAPIDAPI_KEY'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $submission = json_decode($response, true);
        $token = $submission['token'];
        
        // Poll for the result
        $result = null;
        $attempts = 0;
        
        while (!$result && $attempts < 10) {
            sleep(1);
            $attempts++;
            
            $ch = curl_init("https://judge0-ce.p.rapidapi.com/submissions/{$token}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-RapidAPI-Host: judge0-ce.p.rapidapi.com',
                'X-RapidAPI-Key: YOUR_RAPIDAPI_KEY'
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if ($data['status']['id'] > 2) {
                $result = $data;
            }
        }
        
        // Check if the test passed
        $passed = false;
        $output = '';
        
        if ($result) {
            $output = $result['stdout'] ?? $result['stderr'] ?? '';
            $passed = ($result['status']['id'] === 3 && trim($output) === trim($testCase['expected_output']));
        }
        
        $results[] = [
            'testCaseId' => $testCase['id'],
            'input' => $testCase['input'],
            'expected' => $testCase['expected_output'],
            'output' => $output,
            'passed' => $passed
        ];
    }
    
    return $results;
}
?> 