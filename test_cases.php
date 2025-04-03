<?php
// Function to get test cases from the database
function getTestCases($questionId) {
    global $conn; // Database connection
    
    // If database connection exists, fetch from database
    if (isset($conn)) {
        $sql = "SELECT * FROM test_cases WHERE question_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $testCases = [];
        while ($row = $result->fetch_assoc()) {
            $testCases[] = [
                'input' => $row['input'],
                'expected_output' => $row['expected_output'],
                'is_hidden' => $row['is_hidden']
            ];
        }
        
        return $testCases;
    }
    
    // Fallback to sample test cases if no database or test cases not found
    return [];
}

// Function to run test cases
function runTestCases($code, $testCases, $language) {
    $passed = 0;
    $total = count($testCases);
    $details = [];
    
    foreach ($testCases as $index => $testCase) {
        // Inject test case input into the code
        $testCode = injectTestInput($code, $testCase['input'], $language);
        
        // Execute the code
        $output = executeUserCode($testCode, $language);
        
        // Normalize output for comparison (trim whitespace)
        $normalizedOutput = trim($output);
        $normalizedExpected = trim($testCase['expected_output']);
        
        // Compare result
        $isPassed = $normalizedOutput === $normalizedExpected;
        if ($isPassed) {
            $passed++;
        }
        
        // Store details (only show non-hidden test cases to students)
        if (!$testCase['is_hidden']) {
            $details[] = [
                'input' => $testCase['input'],
                'expected' => $testCase['expected_output'],
                'actual' => $output,
                'passed' => $isPassed
            ];
        }
    }
    
    return [
        'passed' => $passed,
        'total' => $total,
        'details' => $details
    ];
}

// Function to execute user code (used by runTestCases)
function executeUserCode($code, $language) {
    // Call the appropriate execution function based on language
    if ($language === 'php') {
        return executePHPCode($code);
    } else if ($language === 'javascript') {
        return executeJSCode($code);
    } else if ($language === 'cpp' || $language === 'c++') {
        return executeCPPCode($code);
    }
    
    return "Unsupported language: $language";
}
?> 