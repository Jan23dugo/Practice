<?php
// Set REQUEST_METHOD to bypass check
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', __DIR__ . '/logs/test_errors.log');

// Log function
function test_log($message) {
    file_put_contents(__DIR__ . '/logs/test_output.log', $message . "\n", FILE_APPEND);
}

test_log("Test script starting at " . date('Y-m-d H:i:s'));

require_once 'qualiexam_registerBack.php';

// Sample TOR text with different formats
$testCases = [
    "Single line format" => "COMP 20023 Computer Programming 1 1.50 3",
    
    "Multi-line format" => "
ACCO 20213
Accounting Principles
3
1.25",
    
    "Hyphenated code" => "CS-101 Introduction to Computing 2.25 3",
    
    "Grade before units" => "MATH-123 Calculus 1 1.75 4",
    
    "With lab component" => "CHEMGEN1-M General Chemistry 1 2.00 3",
    
    "With section" => "PHYS101-A Physics for Engineers 1.50 3",
    
    "With spaces in code" => "GE 101 Understanding the Self 1.25 3",
    
    "Complex format" => "
INFORMATIVE COPY OF GRADES
Student Name: John Doe
Student Number: 2021-12345
======================
First Semester 2021-2022
----------------------

BIO-101 Cellular and Molecular Biology-Introduction 2.00 4
CHEMGEN1-M
General Chemistry 1
3
2.00
GE 101
Understanding the Self
3
1.25"
];

try {
    foreach ($testCases as $caseName => $testText) {
        $output = "\nTesting: $caseName\n";
        $output .= "Input text:\n" . $testText . "\n";
        $output .= "----------------------------------------\n";
        
        test_log($output);
        
        $subjects = extractSubjectsFromText($testText);
        
        $output = "Extracted " . count($subjects) . " subjects:\n";
        foreach ($subjects as $subject) {
            $output .= "\nSubject Code: " . $subject['subject_code'] . "\n";
            $output .= "Description: " . $subject['description'] . "\n";
            $output .= "Units: " . $subject['units'] . "\n";
            $output .= "Grade: " . $subject['grade'] . "\n";
            $output .= "------------------------\n";
        }
        $output .= "\n=====================================\n";
        
        test_log($output);
        echo $output;
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage() . "\n";
    $error .= "Stack trace:\n" . $e->getTraceAsString() . "\n";
    test_log($error);
    echo $error;
} 