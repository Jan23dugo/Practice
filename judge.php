<?php
header('Content-Type: application/json');

// Get the parameters
$code = $_POST['code'] ?? '';
$language = $_POST['language'] ?? 'php';
$questionId = $_POST['questionId'] ?? 1;

// Language mapping for Judge0
$languageIds = [
    'php' => 68,         // PHP
    'javascript' => 63,  // JavaScript (Node.js)
    'python' => 71       // Python
];

// Get test cases for the question
function getTestCases($questionId) {
    // In a real application, fetch from database
    return [
        [
            'input' => '[1, 2, 3, 4, 5]',
            'expected_output' => '15',
            'is_hidden' => false
        ],
        [
            'input' => '[-1, -2, -3]',
            'expected_output' => '-6',
            'is_hidden' => false
        ],
        [
            'input' => '[10, 20, 30, 40]',
            'expected_output' => '100',
            'is_hidden' => true
        ]
    ];
}

// Submit code to Judge0 for evaluation
function submitToJudge0($code, $languageId, $input) {
    $apiToken = 'your_judge0_api_key'; // Replace with your actual API key
    $apiEndpoint = 'https://judge0-ce.p.rapidapi.com/submissions';
    
    // Create submission
    $ch = curl_init($apiEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'source_code' => base64_encode($code),
        'language_id' => $languageId,
        'stdin' => base64_encode($input)
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-RapidAPI-Host: judge0-ce.p.rapidapi.com',
        'X-RapidAPI-Key: ' . $apiToken
    ]);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (!isset($result['token'])) {
        return ['error' => 'Failed to create submission: ' . json_encode($result)];
    }
    
    $token = $result['token'];
    
    // Wait for processing
    sleep(2);
    
    // Get submission result
    $ch = curl_init("$apiEndpoint/$token?base64_encoded=true");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-RapidAPI-Host: judge0-ce.p.rapidapi.com',
        'X-RapidAPI-Key: ' . $apiToken
    ]);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    // Format the result
    if (isset($result['stdout'])) {
        return [
            'output' => base64_decode($result['stdout']),
            'error' => null
        ];
    } else if (isset($result['stderr'])) {
        return [
            'output' => null,
            'error' => base64_decode($result['stderr'])
        ];
    } else if (isset($result['compile_output'])) {
        return [
            'output' => null,
            'error' => 'Compilation error: ' . base64_decode($result['compile_output'])
        ];
    }
    
    return ['error' => 'Unknown error'];
}

// Prepare code for different languages
function prepareCodeForTesting($code, $input, $language) {
    switch ($language) {
        case 'php':
            return "<?php\n$code\n\n// Test case\necho sumArray($input);";
            
        case 'javascript':
            return "$code\n\n// Test case\nconsole.log(sumArray($input));";
            
        case 'python':
            return "$code\n\n# Test case\nprint(sum_array($input))";
            
        default:
            return $code;
    }
}

// Run all test cases and compile results
function evaluateAllTestCases($code, $language, $languageId) {
    $testCases = getTestCases(1); // Hardcoded question ID
    $results = [
        'passed' => 0,
        'total' => count($testCases),
        'details' => []
    ];
    
    foreach ($testCases as $testCase) {
        $testCode = prepareCodeForTesting($code, $testCase['input'], $language);
        $judgeResult = submitToJudge0($testCode, $languageId, '');
        
        if (isset($judgeResult['error']) && $judgeResult['error']) {
            // Error occurred during execution
            $results['details'][] = [
                'input' => $testCase['input'],
                'expected' => $testCase['expected_output'],
                'actual' => "Error: " . $judgeResult['error'],
                'passed' => false
            ];
        } else {
            // Got output, compare with expected
            $output = trim($judgeResult['output']);
            $expected = trim($testCase['expected_output']);
            $passed = $output === $expected;
            
            if ($passed) {
                $results['passed']++;
            }
            
            // Only include non-hidden test cases in the details
            if (!$testCase['is_hidden']) {
                $results['details'][] = [
                    'input' => $testCase['input'],
                    'expected' => $expected,
                    'actual' => $output,
                    'passed' => $passed
                ];
            }
        }
    }
    
    return $results;
}

// Main execution
if (!isset($languageIds[$language])) {
    echo json_encode(['error' => 'Unsupported language']);
    exit;
}

try {
    $results = evaluateAllTestCases($code, $language, $languageIds[$language]);
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>