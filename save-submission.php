<?php
// Set headers
header('Content-Type: application/json');

// Get the request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$data || !isset($data['code']) || !isset($data['language'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// In a real application, you would save this to your database
// For now, we'll just write it to a file for demonstration
$logFile = 'submissions.log';
$timestamp = date('Y-m-d H:i:s');
$submissionId = uniqid();

file_put_contents($logFile, "=== SUBMISSION $submissionId ($timestamp) ===\n", FILE_APPEND);
file_put_contents($logFile, "Language: " . $data['language'] . "\n", FILE_APPEND);
file_put_contents($logFile, "QuestionID: " . ($data['questionId'] ?? 'unknown') . "\n", FILE_APPEND);
file_put_contents($logFile, "StudentID: " . ($data['studentId'] ?? 'unknown') . "\n", FILE_APPEND);
file_put_contents($logFile, "Code:\n" . $data['code'] . "\n\n", FILE_APPEND);

// Log test results
if (isset($data['results']) && is_array($data['results'])) {
    $passedCount = 0;
    $totalCount = count($data['results']);
    
    file_put_contents($logFile, "Test Results:\n", FILE_APPEND);
    
    foreach ($data['results'] as $index => $result) {
        $status = $result['passed'] ? 'PASSED' : 'FAILED';
        if ($result['passed']) $passedCount++;
        
        file_put_contents($logFile, "Test Case " . ($index + 1) . ": $status\n", FILE_APPEND);
        file_put_contents($logFile, "  Input: " . $result['input'] . "\n", FILE_APPEND);
        file_put_contents($logFile, "  Expected: " . $result['expected'] . "\n", FILE_APPEND);
        file_put_contents($logFile, "  Actual: " . $result['actual'] . "\n\n", FILE_APPEND);
    }
    
    $score = ($passedCount / $totalCount) * 100;
    file_put_contents($logFile, "Score: $score%\n", FILE_APPEND);
}

file_put_contents($logFile, "==============================\n\n", FILE_APPEND);

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Submission saved successfully',
    'submissionId' => $submissionId
]);
?> 