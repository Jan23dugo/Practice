<?php
// This handles both running code and evaluating submissions
header('Content-Type: application/json');

// Get the code and mode (run or submit)
$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'];
$mode = $data['mode'];
$language = $data['language'];
$questionId = $data['questionId'];

// Sanitize inputs
$code = htmlspecialchars($code);

if ($mode === 'run') {
    // Just execute the code for the student to test
    $result = executeUserCode($code, $language);
    echo json_encode(['output' => $result]);
} else if ($mode === 'submit') {
    // Run against test cases
    $testCases = getTestCases($questionId);
    $results = runTestCases($code, $testCases, $language);
    echo json_encode([
        'passed' => $results['passed'],
        'total' => $results['total'],
        'details' => $results['details']
    ]);
    
    // Store the submission in the database
    storeSubmission($code, $questionId, $_SESSION['user_id'], $results);
}

function executeUserCode($code, $language) {
    // Sandbox execution based on language
    if ($language === 'php') {
        return executePHPCode($code);
    } else if ($language === 'javascript') {
        return executeJSCode($code);
    }
    // Add more language support as needed
}
?> 