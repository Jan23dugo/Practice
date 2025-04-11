<?php
// Start output buffering at the very beginning
ob_start();
session_start();

// Include necessary files and libraries
include('config/config.php');
require 'send_email.php';

// Session management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['success'])) {
    $_SESSION['success'] = '';
}

function debug_log($message) {
    // Store debug messages in session instead of echoing
    if (!isset($_SESSION['debug_messages'])) {
        $_SESSION['debug_messages'] = [];
    }
    $_SESSION['debug_messages'][] = $message;
}

// Add this near the top of the file after other includes
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

function logExtraction($message, $data = null) {
    $logFile = __DIR__ . '/logs/extraction_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    
    if ($data !== null) {
        $logMessage .= "Data: " . print_r($data, true) . "\n";
    }
    
    $logMessage .= "----------------------------------------\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Add the logging function here, before it's used
function logExtractionResults($methodName, $data = [], $extraInfo = [], $message = '') {
    // Create logs directory if it doesn't exist
    $logsDir = __DIR__ . '/logs';
    if (!file_exists($logsDir)) {
        mkdir($logsDir, 0777, true);
    }
    
    // Create a standardized log filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $logFile = $logsDir . '/extraction_log_' . $timestamp . '.txt';
    
    // Check if this is the first entry in this log file
    $isNewLog = !file_exists($logFile);
    
    // Start log content
    $logContent = '';
    if ($isNewLog) {
        $logContent .= "=== TRANSCRIPT EXTRACTION LOG ===\n";
        $logContent .= "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    // Add method section header
    $logContent .= "=== " . strtoupper($methodName) . " EXTRACTION ===\n";
    
    // Add message if provided
    if (!empty($message)) {
        $logContent .= "Message: $message\n";
    }
    
    // Add extra information if provided
    if (!empty($extraInfo)) {
        $logContent .= "Additional Information:\n";
        foreach ($extraInfo as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $logContent .= "$key:\n" . print_r($value, true) . "\n";
            } else {
                $logContent .= "$key: $value\n";
            }
        }
        $logContent .= "\n";
    }
    
    // Add the data
    if (!empty($data)) {
        if (is_array($data) || is_object($data)) {
            $logContent .= "Results:\n" . print_r($data, true) . "\n";
        } else {
            $logContent .= "Results: $data\n";
        }
    }
    
    $logContent .= "---------------------------------------------\n\n";
    
    // Append to the log file
    file_put_contents($logFile, $logContent, FILE_APPEND);
    
    // Store the current log file path in the session for easy access
    $_SESSION['current_extraction_log'] = $logFile;
    
    return $logFile;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];

// Add this at the top of your file
class TORFormat {
    const STANDARD = 'standard';
    const PERPETUAL = 'perpetual';
    const MAPUA = 'mapua';
    // Add more formats as needed
}

function detectTORFormat($text) {
    // Detect format based on unique identifiers in the text
    if (stripos($text, 'PERPETUAL') !== false || stripos($text, 'DALTA') !== false) {
        return TORFormat::PERPETUAL;
    } elseif (stripos($text, 'MAPUA') !== false) {
        return TORFormat::MAPUA;
    }
    return TORFormat::STANDARD;
}

// Enhanced subject code pattern matching
function validateSubjectCode($code, $format) {
    $patterns = [
        TORFormat::STANDARD => [
            // Standard format (e.g., "COMP 101", "IT 201")
            '/^[A-Z]{2,4}\s*\d{3,4}[A-Z]?$/i',
            // With section (e.g., "CSC101-A")
            '/^[A-Z]{2,4}\d{3,4}-[A-Z]$/i',
            // With type (e.g., "MATH101-LEC", "PHYS101-LAB")
            '/^[A-Z]{2,4}\d{3,4}(?:-(?:LEC|LAB))?$/i'
        ],
        TORFormat::PERPETUAL => [
            // Perpetual format (e.g., "GEC 1000", "FCL 1101")
            '/^(?:GEC|FCL|FIL|PE|NSTP|PSY|GEE)\s*\d{4}[A-Z]?$/i',
            // With L suffix (e.g., "GEE 1000L")
            '/^[A-Z]{2,4}\s*\d{4}L$/i'
        ],
        TORFormat::MAPUA => [
            // Add Mapua-specific patterns
            '/^[A-Z]{2,4}\s*\d{4}[A-Z]?$/i'
        ]
    ];

    foreach ($patterns[$format] as $pattern) {
        if (preg_match($pattern, trim($code))) {
            return true;
        }
    }
    return false;
}

// Enhanced grade and unit validation
function validateGradeAndUnit($grade, $unit, $format) {
    $gradePatterns = [
        TORFormat::STANDARD => [
            'pattern' => '/^[1-5][\.,]\d{2}$/',
            'range' => ['min' => 1.00, 'max' => 5.00]
        ],
        TORFormat::PERPETUAL => [
            'pattern' => '/^[1-5][\.,]\d{2}$/',
            'range' => ['min' => 1.00, 'max' => 5.00]
        ],
        TORFormat::MAPUA => [
            'pattern' => '/^[1-5][\.,]\d{2}$/',
            'range' => ['min' => 1.00, 'max' => 5.00]
        ]
    ];

    $unitPatterns = [
        TORFormat::STANDARD => [
            'pattern' => '/^[1-6](?:[\.,]0)?$/',
            'range' => ['min' => 1.0, 'max' => 6.0]
        ],
        TORFormat::PERPETUAL => [
            'pattern' => '/^[1-6](?:[\.,]0)?$/',
            'range' => ['min' => 1.0, 'max' => 6.0]
        ],
        TORFormat::MAPUA => [
            'pattern' => '/^[1-6](?:[\.,]0)?$/',
            'range' => ['min' => 1.0, 'max' => 6.0]
        ]
    ];

    // Validate grade
    $grade = str_replace(',', '.', $grade);
    if (!preg_match($gradePatterns[$format]['pattern'], $grade)) {
        return false;
    }
    $gradeValue = floatval($grade);
    if ($gradeValue < $gradePatterns[$format]['range']['min'] || 
        $gradeValue > $gradePatterns[$format]['range']['max']) {
        return false;
    }

    // Validate unit
    $unit = str_replace(',', '.', $unit);
    if (!preg_match($unitPatterns[$format]['pattern'], $unit)) {
        return false;
    }
    $unitValue = floatval($unit);
    if ($unitValue < $unitPatterns[$format]['range']['min'] || 
        $unitValue > $unitPatterns[$format]['range']['max']) {
        return false;
    }

    return true;
}

// First method: Generic pattern recognition
function extractSubjectsGeneric($text) {
    $subjects = [];
    
    // Generic patterns that work across most TORs
    $patterns = [
        'subject_code' => '/[A-Z]{2,4}\s*\d{3,4}[A-Z]?/i',
        'grade' => '/[1-5][\.,]\d{2}/',
        'units' => '/\b[1-6](?:[\.,]0)?\b/'
    ];
    
    // Split into lines
    $lines = array_map('trim', explode("\n", $text));
    
    foreach ($lines as $line) {
        // Skip header rows and empty lines
        if (empty($line) || preg_match('/(SUBJECT|COURSE|CODE|TITLE|TERM|SEMESTER)/i', $line)) {
            continue;
        }
        
        // Look for a line with both numbers and text
        if (preg_match($patterns['subject_code'], $line, $code_match) && 
            preg_match($patterns['grade'], $line, $grade_match) && 
            preg_match($patterns['units'], $line, $units_match)) {
            
            // Extract description (text between code and numbers)
            $description = trim(preg_replace(
                [$patterns['subject_code'], $patterns['grade'], $patterns['units'], '/\s+/'],
                ['', '', '', ' '],
                $line
            ));
            
            $subjects[] = [
                'subject_code' => $code_match[0],
                'description' => $description,
                'grade' => str_replace(',', '.', $grade_match[0]),
                'units' => str_replace(',', '.', $units_match[0])
            ];
        }
    }
    
    return $subjects;
}

// Second method: Position-based extraction
function extractSubjectsByPosition($text) {
    $subjects = [];
    $lines = array_map('trim', explode("\n", $text));
    
    foreach ($lines as $line) {
            // Split line into segments
            $parts = preg_split('/\s{2,}|\t/', $line);
            
        if (count($parts) >= 4) {
            // Get numeric values (last two columns)
                $numeric_values = array_filter($parts, function($part) {
                return preg_match('/^[0-9.,]+$/', str_replace(' ', '', $part));
                });
                
                if (count($numeric_values) >= 2) {
                    $numeric_values = array_values($numeric_values);
                // SWAP the order: now units is second-to-last, grade is last
                    $subjects[] = [
                    'subject_code' => $parts[0],
                    'description' => implode(' ', array_slice($parts, 1, -2)),
                    'units' => str_replace(',', '.', $numeric_values[1]), // Changed from [0]
                    'grade' => str_replace(',', '.', $numeric_values[0])  // Changed from [1]
                ];
            }
        }
    }
    
    return $subjects;
}

// Validation function
function validateSubjectData($subject) {
    // Basic structure check
    if (empty($subject['subject_code']) || empty($subject['description'])) {
        return false;
    }
    
    // Grade validation (1.00 to 5.00)
    $grade = floatval(str_replace(',', '.', $subject['grade']));
    if ($grade < 1.00 || $grade > 5.00) {
        return false;
    }
    
    // Units validation (1.0 to 6.0)
    $units = floatval(str_replace(',', '.', $subject['units']));
    if ($units < 1.0 || $units > 6.0) {
        return false;
    }
    
    return true;
}

function cleanOCRText($text) {
    // Replace common OCR mistakes
    $replacements = [
        'Chernistry' => 'Chemistry',
        'Sprituality' => 'Spirituality',
        '\s*,\s*(?=\d)' => ' ', // Remove commas before numbers
        '(?<=\d)\s*,\s*(?=\d)' => '.', // Convert comma to decimal in numbers
        '\s+[-—]\s+[MX]\s+' => ' - ', // Standardize separator patterns
        '\s*:\s*' => ' ', // Remove colons
        '(?<=\d)l(?=\s|$)' => '1', // Fix common '1' misread as 'l'
    ];
    
    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace('/' . $pattern . '/', $replacement, $text);
    }
    
    return $text;
}

function extractSubjects($text) {
    if (!is_string($text)) {
        error_log("Warning: extractSubjects received non-string input: " . print_r($text, true));
        return [];
    }
    
    $subjects = [];
    
    // Remove code block markers if present
    $text = preg_replace('/```json\s*|\s*```/', '', $text);
    
    // Check if the text is JSON and contains subjects
    $data = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($data['subjects'])) {
        logExtraction("Successfully parsed JSON subjects", [
            'count' => count($data['subjects'])
        ]);
        
        foreach ($data['subjects'] as $subject) {
            $subjects[] = [
                'subject_code' => $subject['subject_code'],
                'subject_description' => $subject['subject_description'],
                'units' => floatval($subject['units']),
                'grade' => floatval($subject['grade'])
            ];
        }
        return $subjects;
    }

    logExtraction("Falling back to text parsing", [
        'text_length' => strlen($text)
    ]);

    // If not JSON, try parsing as plain text
    $lines = array_map('trim', explode("\n", $text));
    
    foreach ($lines as $line) {
        if (empty($line)) continue;
        
        // Skip header rows and footers
        if (preg_match('/(SUBJECT|COURSE|CODE|TITLE|TERM|SEMESTER|NOTHING FOLLOWS)/i', $line)) {
            continue;
        }
        
        // Try to match subject pattern
        if (preg_match('/([A-Z0-9]+[-]?[A-Z0-9]*)\s*[-]?\s*([^0-9]+?)\s+([\d.]+)\s+([\d.]+)/i', $line, $matches)) {
            $subjects[] = [
                'subject_code' => trim($matches[1]),
                'subject_description' => trim($matches[2]),
                'units' => floatval($matches[3]),
                'grade' => floatval($matches[4])
            ];
        }
    }
    
    logExtraction("Extraction complete", [
        'subjects_found' => count($subjects)
    ]);
    
    return $subjects;
}

function standardizeText($text) {
    // Convert to lowercase
    $text = strtolower($text);
    // Remove extra spaces and standardize spaces around common words
    $text = preg_replace('/\s+/', ' ', trim($text));
    // Standardize common words (The, And, In, etc.)
    $text = str_replace(' the ', ' ', $text);
    $text = str_replace(' and ', ' ', $text);
    $text = str_replace(' in ', ' ', $text);
    $text = str_replace(' of ', ' ', $text);
    $text = str_replace(' for ', ' ', $text);
    // Remove special characters except spaces
    $text = preg_replace('/[^\w\s]/', '', $text);
    // Final trim and space standardization
    return trim(preg_replace('/\s+/', ' ', $text));
}

function matchCreditedSubjects($conn, $subjects, $student_id) {
    // Get the student's desired program
    $stmt = $conn->prepare("SELECT desired_program FROM register_studentsqe WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $desired_program = $student['desired_program'];

    logExtraction("Starting Subject Matching Process", [
        'student_id' => $student_id,
        'total_subjects' => count($subjects),
        'desired_program' => $desired_program
    ]);
    
    $_SESSION['matches'] = [];
    
    if (empty($student_id)) {
        logExtraction("Error: No student_id provided");
        return;
    }

    if (empty($subjects)) {
        logExtraction("Error: No subjects provided for matching");
        return;
    }

    if (empty($desired_program)) {
        logExtraction("Error: No desired program specified");
        return;
    }

    logExtraction("Processing subjects", [
        'first_subject' => $subjects[0],
        'total_subjects' => count($subjects),
        'program' => $desired_program
    ]);
    
    foreach ($subjects as $subject) {
        // Clean and standardize the subject text
        $originalText = $subject['subject_description'];
        $subjectText = standardizeText($originalText);
        $units = floatval($subject['units']);
        
        logExtraction("Processing Subject", [
            'original_text' => $originalText,
            'standardized_text' => $subjectText,
            'units' => $units,
            'grade' => $subject['grade']
        ]);

        try {
            // Get all courses with matching units and program
            $sql = "SELECT * FROM coded_courses WHERE ABS(units - ?) < 0.1 AND program = ?";
            
            logExtraction("Executing SQL Query", [
                'query' => $sql,
                'units' => $units,
                'program' => $desired_program
            ]);

        $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                logExtraction("Error preparing statement", ['error' => $conn->error]);
                continue;
            }

            $stmt->bind_param("ds", $units, $desired_program);
            $success = $stmt->execute();
            
            if (!$success) {
                logExtraction("Error executing statement", ['error' => $stmt->error]);
                continue;
            }

        $result = $stmt->get_result();
            logExtraction("Query Results", ['num_rows' => $result->num_rows]);

            $found_match = false;
            while ($row = $result->fetch_assoc()) {
                $dbStandardized = standardizeText($row['subject_description']);
                
                // Try exact match first
                if ($dbStandardized === $subjectText) {
                    $found_match = true;
                } else {
                    // Try partial match if no exact match
                    if (strpos($dbStandardized, $subjectText) !== false || 
                        strpos($subjectText, $dbStandardized) !== false) {
                        $found_match = true;
                    }
                }
                
                if ($found_match) {
                    logExtraction("Match Found", [
                        'original_subject' => $originalText,
                        'database_subject' => $row['subject_description'],
                        'standardized_database' => $dbStandardized,
                        'program' => $row['program']
                    ]);
                    
                    try {
                $insert_sql = "INSERT INTO matched_courses 
                             (subject_code, subject_description, units, student_id, matched_at, original_code, grade) 
                             VALUES (?, ?, ?, ?, NOW(), ?, ?)";
                             
                        logExtraction("Attempting Insert", [
                            'sql' => $insert_sql,
                            'values' => [
                                'subject_code' => $row['subject_code'],
                                'subject_description' => $row['subject_description'],
                                'units' => $row['units'],
                                'student_id' => $student_id,
                                'original_code' => $subject['subject_code'],
                                'grade' => $subject['grade']
                            ]
                        ]);
                        
                $insert_stmt = $conn->prepare($insert_sql);
                        if ($insert_stmt === false) {
                            logExtraction("Error preparing insert", ['error' => $conn->error]);
                            continue;
                        }
                        
                $insert_stmt->bind_param(
                    "ssdiss", 
                            $row['subject_code'],
                            $row['subject_description'],
                            $row['units'],
                            $student_id,
                            $subject['subject_code'],
                            $subject['grade']
                        );
                        
                        $success = $insert_stmt->execute();
                        if (!$success) {
                            logExtraction("Error inserting match", ['error' => $insert_stmt->error]);
            } else {
                            logExtraction("Successfully inserted match", ['matched_id' => $conn->insert_id]);
                            $_SESSION['matches'][] = "✓ Matched: {$originalText} ({$subject['units']} units) with {$row['subject_description']} ({$row['program']})";
                            break; // Found and inserted a match, move to next subject
                        }
                        
                    } catch (Exception $e) {
                        logExtraction("Exception during insert", ['error' => $e->getMessage()]);
                    }
                }
            }
            
            if (!$found_match) {
                logExtraction("No Match Found", [
                    'subject' => $originalText,
                    'program' => $desired_program
                ]);
                $_SESSION['matches'][] = "✗ No match for: {$originalText} ({$subject['units']} units) in {$desired_program}";
            }
        } catch (Exception $e) {
            logExtraction("Exception during matching", [
                'error' => $e->getMessage(),
                'subject' => $originalText
            ]);
        }
    }
    
    logExtraction("Matching Process Complete", [
        'total_matches' => count($_SESSION['matches']),
        'student_id' => $student_id,
        'program' => $desired_program
    ]);
}

// 2. Function to determine eligibility based on grades and grading system rules
function determineEligibility($subjects, $gradingRules) {
    $minPassingPercentage = 85.0; // Minimum required percentage
    
   

    foreach ($subjects as $subject) {
        $grade = $subject['grade'];
        $isSubjectEligible = false;
        
       
        
        // Find the matching grade rule for this grade
        $matchingRule = null;
        foreach ($gradingRules as $rule) {
            $gradeValue = floatval($rule['grade_value']);
            $minPercentage = floatval($rule['min_percentage']);
            $maxPercentage = floatval($rule['max_percentage']);
            
           
            
            // Check if the grade falls within this rule's range
            if ($grade == $gradeValue) {
                $matchingRule = $rule;
                break;
            }
        }
        
        // If we found a matching rule, check if the percentage meets our requirement
        if ($matchingRule) {
            $ruleMinPercentage = floatval($matchingRule['min_percentage']);
            if ($ruleMinPercentage >= $minPassingPercentage) {
                $isSubjectEligible = true;
               
            } else {
                $_SESSION['debug_output'] .= "Subject is not eligible: Grade $grade corresponds to percentage $ruleMinPercentage% (< $minPassingPercentage%)<br>";
            }
        } else {
            $_SESSION['debug_output'] .= "No matching grade rule found for grade $grade<br>";
        }

        if (!$isSubjectEligible) {
            $_SESSION['debug_output'] .= "Subject with grade $grade does not meet eligibility criteria<br>";
            $_SESSION['debug_output'] .= "</div>";
            return false;
        }
    }

    $_SESSION['debug_output'] .= "All subjects meet eligibility criteria<br>";
    $_SESSION['debug_output'] .= "</div>";
    return true;
}

// 3. Function to check if the student is a tech student based on parsed subjects
function isTechStudent($subjects) {
    addDebugOutput("Starting Tech Student Check");
    
    $tech_subjects = [
        'Computer Programming 1', 'Software Engineering', 'Database Systems', 'Operating Systems',
        'Data Structures', 'Algorithms', 'Web Development', 'Computer Programming 2', 'Information Technology',
        'Cybersecurity', 'System Analysis and Design'
    ];

    addDebugOutput("Tech Subject Keywords:", $tech_subjects);

    foreach ($subjects as $subject) {
        foreach ($tech_subjects as $tech_subject) {
            if (stripos($subject['subject'], $tech_subject) !== false) {
                addDebugOutput("Tech Subject Found:", [
                    'subject' => $subject['subject'],
                    'matched_keyword' => $tech_subject
                ]);
                return true;
            }
        }
    }
    
    addDebugOutput("No Tech Subjects Found in Student's Records");
    return false;
}

// Function to register the student with updated fields
// Function to register the student with updated fields and also pass subjects
// Function to register the student with updated fields and also pass subjects
function registerStudent($conn, $studentData, $subjects) {
    // Get current year
    $year = date('Y');
    
    // Generate a unique number (5 digits)
    $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
    // Create reference ID in format CCIS-YEAR-UNIQUE
    $reference_id = "CCIS-{$year}-{$unique}";
    
    // Check if reference ID already exists
    $check_stmt = $conn->prepare("SELECT reference_id FROM register_studentsqe WHERE reference_id = ?");
    $check_stmt->bind_param("s", $reference_id);
    $check_stmt->execute();
    
    // If reference ID exists, generate a new one
    while ($check_stmt->get_result()->num_rows > 0) {
        $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $reference_id = "CCIS-{$year}-{$unique}";
        $check_stmt->execute();
    }
    $studentData['reference_id'] = $reference_id;
    
    // Set year_level to NULL for ladderized students
    if ($studentData['student_type'] === 'ladderized') {
        $studentData['year_level'] = null;
        // Ensure DICT is set as previous program
        $studentData['previous_program'] = 'Diploma in Information and Communication Technology (DICT)';
    }
    
    $stmt = $conn->prepare("INSERT INTO register_studentsqe (
        last_name, first_name, middle_name, gender, dob, email, contact_number, street, 
        student_type, previous_school, year_level, previous_program, desired_program, 
        tor, school_id, reference_id, is_tech, status, stud_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");

    $stmt->bind_param(
        "ssssssssssssssssii", 
        $studentData['last_name'],
        $studentData['first_name'],
        $studentData['middle_name'],
        $studentData['gender'],
        $studentData['dob'],
        $studentData['email'],
        $studentData['contact_number'],
        $studentData['street'],
        $studentData['student_type'],
        $studentData['previous_school'],
        $studentData['year_level'],
        $studentData['previous_program'],
        $studentData['desired_program'],
        $studentData['tor_path'],
        $studentData['school_id_path'],
        $studentData['reference_id'],
        $studentData['is_tech'],
        $_SESSION['stud_id']  // Add the stud_id from session
    );

    if ($stmt->execute()) {
        $student_id = $stmt->insert_id;
        $_SESSION['success'] = "Your reference ID is: " . $reference_id;
        $_SESSION['reference_id'] = $reference_id;
        $_SESSION['student_id'] = $student_id;
        
        // Store debug information in session
        $_SESSION['debug_output'] = ob_get_clean();
        
        sendRegistrationEmail($studentData['email'], $studentData['reference_id']);
        matchCreditedSubjects($conn, $subjects, $student_id);
        
        // Clean output buffer before redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header("Location: registration_success.php");
        exit();
    } else {
        $_SESSION['last_error'] = "Error registering student: " . $stmt->error;
        header("Location: registerFront.php");
        exit();
    }
    $stmt->close();
}

function getGradingSystemRules($conn, $universityName) {
    $query = "SELECT min_percentage, max_percentage, grade_value FROM university_grading_systems WHERE university_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $universityName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt->close();
        $universityName = "%$universityName%";
        $query = "SELECT min_percentage, max_percentage, grade_value FROM university_grading_systems WHERE university_name LIKE ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $universityName);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    $gradingRules = [];
    while ($row = $result->fetch_assoc()) {
        $gradingRules[] = $row;
    }

    $stmt->close();
    return $gradingRules;
}

// Add this function at the top
function addDebugOutput($message, $data = null) {
    if (!isset($_SESSION['debug_output'])) {
        $_SESSION['debug_output'] = '';
    }
    $_SESSION['debug_output'] .= "<div style='background: #f5f5f5; margin: 10px 0; padding: 10px; border-left: 4px solid #008CBA;'>";
    $_SESSION['debug_output'] .= "<strong>$message</strong><br>";
    if ($data !== null) {
        $_SESSION['debug_output'] .= "<pre>" . print_r($data, true) . "</pre>";
    }
    $_SESSION['debug_output'] .= "</div>";
}

// Add this near the top of your file after the includes
define('UNSTRACT_API_KEY', 'dff56a8a-6d02-4089-bd87-996d7be8b1bb');

// Update the performOCR function
function performOCR($filePath) {
    try {
        logExtraction("Starting OCR process for file", ['path' => $filePath]);
        
        $curl = curl_init();
        
        // Prepare the file
        if (!file_exists($filePath)) {
            throw new Exception("File not found: " . $filePath);
        }
        
        logExtraction("File details", [
            'size' => filesize($filePath),
            'type' => mime_content_type($filePath)
        ]);
        
        // Set up the API request
        $postFields = [
            'files' => new CURLFile($filePath),
            'timeout' => '300',
            'include_metadata' => 'false'
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://us-central.unstract.com/deployment/api/org_SBbh31LYckHO5i28/tor-data-extractor/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer dff56a8a-6d02-4089-bd87-996d7be8b1bb'
            ]
        ]);
        
        logExtraction("Sending request to Unstract API");
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        logExtraction("API Response received", [
            'http_code' => $httpCode,
            'error' => $err ?: 'None',
            'response' => $response
        ]);
        
        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("API returned non-200 status code: " . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (!$result) {
            throw new Exception("Failed to decode API response: " . json_last_error_msg());
        }
        
        // Extract the subjects JSON from the response
        if (isset($result['message']['result'][0]['result']['output']['TOR_Extractor_'])) {
            $subjectsJson = $result['message']['result'][0]['result']['output']['TOR_Extractor_'];
            logExtraction("Successfully extracted subjects JSON", ['subjects_json' => $subjectsJson]);
            return $subjectsJson;
        }
        
        throw new Exception("Could not find subjects in API response");
        
    } catch (Exception $e) {
        logExtraction("Error in OCR process", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

function preprocessImage($imagePath) {
    try {
    // Get image info
    $imageInfo = getimagesize($imagePath);
    if ($imageInfo === false) {
        throw new Exception("Invalid image file");
    }
    
        // Log original image details
        logExtractionResults('IMAGE_PREPROCESSING', [], [
            'original_type' => $imageInfo['mime'],
            'original_dimensions' => $imageInfo[0] . 'x' . $imageInfo[1]
        ]);

        // Create image resource based on file type
        $sourceImage = null;
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            default:
                throw new Exception("Unsupported image type: " . $imageInfo['mime']);
        }

        if (!$sourceImage) {
            throw new Exception("Failed to create image resource");
        }

        // Get original dimensions
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Calculate crop dimensions
        // We'll focus on the middle 60% of the image vertically
        // and keep 80% of the width from the center
        $cropStartY = (int)($originalHeight * 0.2); // Start at 20% from top
        $cropHeight = (int)($originalHeight * 0.6); // Take 60% of height
        $cropStartX = (int)($originalWidth * 0.1); // Start at 10% from left
        $cropWidth = (int)($originalWidth * 0.8); // Take 80% of width

        // Create new image with cropped dimensions
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);

        // Preserve transparency for PNG images
        if ($imageInfo['mime'] === 'image/png') {
            imagealphablending($croppedImage, false);
            imagesavealpha($croppedImage, true);
            $transparent = imagecolorallocatealpha($croppedImage, 255, 255, 255, 127);
            imagefilledrectangle($croppedImage, 0, 0, $cropWidth, $cropHeight, $transparent);
        }

        // Perform the crop
        imagecopy(
            $croppedImage, 
            $sourceImage, 
            0, 0,                    // Destination X, Y
            $cropStartX, $cropStartY, // Source X, Y
            $cropWidth, $cropHeight   // Width and Height
        );

        // Create path for cropped image
        $pathInfo = pathinfo($imagePath);
        $croppedPath = $pathInfo['dirname'] . '/cropped_' . $pathInfo['basename'];

        // Save the cropped image
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($croppedImage, $croppedPath, 100); // Maximum quality
                break;
            case 'image/png':
                imagepng($croppedImage, $croppedPath, 0); // No compression
                break;
        }

        // Clean up resources
        imagedestroy($sourceImage);
        imagedestroy($croppedImage);

        // Log preprocessing results
        logExtractionResults('IMAGE_PREPROCESSING', [], [
            'original_dimensions' => "${originalWidth}x${originalHeight}",
            'cropped_dimensions' => "${cropWidth}x${cropHeight}",
            'crop_region' => [
                'start_x' => $cropStartX,
                'start_y' => $cropStartY,
                'width' => $cropWidth,
                'height' => $cropHeight
            ]
        ]);

        return $croppedPath;

    } catch (Exception $e) {
        error_log("Image preprocessing error: " . $e->getMessage());
        // If preprocessing fails, return original image
    return $imagePath;
    }
}

// Add this function to help with dynamic crop region detection
function detectTableRegion($imagePath) {
    try {
        // Create GD image resource
        $image = imagecreatefromstring(file_get_contents($imagePath));
        if (!$image) {
            throw new Exception("Failed to create image resource");
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Convert to grayscale and detect edges
        $edges = [];
        $threshold = 30; // Adjust this value based on image contrast

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $rgb);
                
                // Convert to grayscale
                $gray = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;
                
                // Simple edge detection
                if ($x > 0 && $y > 0) {
                    $prevX = imagecolorat($image, $x - 1, $y);
                    $prevY = imagecolorat($image, $x, $y - 1);
                    
                    $prevXColors = imagecolorsforindex($image, $prevX);
                    $prevYColors = imagecolorsforindex($image, $prevY);
                    
                    $prevXGray = ($prevXColors['red'] + $prevXColors['green'] + $prevXColors['blue']) / 3;
                    $prevYGray = ($prevYColors['red'] + $prevYColors['green'] + $prevYColors['blue']) / 3;
                    
                    if (abs($gray - $prevXGray) > $threshold || abs($gray - $prevYGray) > $threshold) {
                        $edges[] = ['x' => $x, 'y' => $y];
                    }
                }
            }
        }

        // Find the region with the highest concentration of edges
        $regionSize = 100; // Size of sliding window
        $maxEdges = 0;
        $bestRegion = [
            'x' => $width * 0.1,  // Default to standard crop if no better region found
            'y' => $height * 0.2,
            'width' => $width * 0.8,
            'height' => $height * 0.6
        ];

        for ($y = 0; $y < $height - $regionSize; $y += $regionSize / 2) {
            for ($x = 0; $x < $width - $regionSize; $x += $regionSize / 2) {
                $edgeCount = count(array_filter($edges, function($edge) use ($x, $y, $regionSize) {
                    return $edge['x'] >= $x && $edge['x'] < $x + $regionSize &&
                           $edge['y'] >= $y && $edge['y'] < $y + $regionSize;
                }));

                if ($edgeCount > $maxEdges) {
                    $maxEdges = $edgeCount;
                    $bestRegion = [
                        'x' => $x,
                        'y' => $y,
                        'width' => $regionSize,
                        'height' => $regionSize
                    ];
                }
            }
        }

        // Clean up
        imagedestroy($image);

        return $bestRegion;

    } catch (Exception $e) {
        error_log("Region detection error: " . $e->getMessage());
        // Return default crop region on error
        return [
            'x' => $width * 0.1,
            'y' => $height * 0.2,
            'width' => $width * 0.8,
            'height' => $height * 0.6
        ];
    }
}

// Add this function to help identify data types
function identifyDataType($value, $context = []) {
    // Convert common OCR mistakes
    $value = str_replace(['O', 'o'], '0', $value);
    $value = str_replace(['l', 'I'], '1', $value);
    
    // Patterns for different data types
    $patterns = [
        'subject_code' => [
            'pattern' => '/^[A-Z]{2,4}\s*\d{3,4}[A-Z]?$/i',
            'weight' => 0.8
        ],
        'grade' => [
            'pattern' => '/^[1-5][\.,]\d{2}$/',
            'weight' => 0.9
        ],
        'units' => [
            'pattern' => '/^[1-6](?:[\.,]0)?$/',
            'weight' => 0.7
        ]
    ];
    
    $scores = [];
    foreach ($patterns as $type => $config) {
        $scores[$type] = 0;
        
        // Pattern matching
        if (preg_match($config['pattern'], $value)) {
            $scores[$type] += $config['weight'];
        }
        
        // Context-based scoring
        if (isset($context['position'])) {
            switch ($type) {
                case 'subject_code':
                    if ($context['position'] === 'start') $scores[$type] += 0.2;
                    break;
                case 'grade':
                case 'units':
                    if ($context['position'] === 'end') $scores[$type] += 0.2;
                    break;
            }
        }
    }
    
    // Return type with highest score
    arsort($scores);
    return key($scores);
}

// Validate the uploaded file
function validateUploadedFile($file) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed with error code: ' . $file['error']);
    }

    // Check if file exists and is valid
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('No file was uploaded or invalid upload attempt.');
    }

    // Check file size (5MB limit)
    $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxFileSize) {
        throw new Exception('File is too large. Maximum size is 5MB.');
    }

    // Check file type
    $allowedTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/pdf'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
    }

    // Create upload directories if they don't exist
    $uploadDirs = [
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/tor',
        __DIR__ . '/uploads/school_id'
    ];

    foreach ($uploadDirs as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception('Failed to create upload directory: ' . $dir);
            }
        }
    }

    return true;
}

// Main processing code
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Clear any existing output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        if (isset($_FILES['tor']) && $_FILES['tor']['error'] == UPLOAD_ERR_OK &&
            isset($_FILES['school_id']) && $_FILES['school_id']['error'] == UPLOAD_ERR_OK) {
            
            validateUploadedFile($_FILES['tor']);
            $tor_path = 'uploads/tor/' . basename($_FILES['tor']['name']);
            move_uploaded_file($_FILES['tor']['tmp_name'], __DIR__ . '/' . $tor_path);

            validateUploadedFile($_FILES['school_id']);
            $school_id_path = 'uploads/school_id/' . basename($_FILES['school_id']['name']);
            move_uploaded_file($_FILES['school_id']['tmp_name'], __DIR__ . '/' . $school_id_path);

            // Preprocess (crop) the image before OCR
            $preprocessed_path = preprocessImage($tor_path);
            
            // Use the preprocessed image for OCR
            $ocr_output = performOCR($preprocessed_path);
            
            // Clean up preprocessed image if it's different from original
            if ($preprocessed_path !== $tor_path && file_exists($preprocessed_path)) {
                unlink($preprocessed_path);
            }

            // Add this function at the top
            try {
                addDebugOutput("Starting OCR Process");

                // Remove the keyword validation section and go straight to subject extraction
                $subjects = extractSubjects($ocr_output);
                addDebugOutput("Extracted Subjects:", $subjects);

                // Process eligibility
                $isEligible = false;
                $student_type = $_POST['student_type'] ?? '';
                
                addDebugOutput("Student Type:", $student_type);

                if (strtolower($student_type) === 'ladderized') {
                    $isEligible = true;
                    addDebugOutput("Ladderized Student - Automatically Eligible");
                } else {
                    $previous_school = $_POST['previous_school'] ?? '';
                    addDebugOutput("Previous School:", $previous_school);
                    
                    $gradingRules = getGradingSystemRules($conn, $previous_school);
                    addDebugOutput("Retrieved Grading Rules:", $gradingRules);
                    
                    $isEligible = determineEligibility($subjects, $gradingRules);
                }

                // Check if student is tech
                $is_tech = isTechStudent($subjects);
                addDebugOutput("Tech Student Check:", [
                    'is_tech' => $is_tech ? 'Yes' : 'No',
                    'checked_subjects' => $subjects
                ]);

            } catch (Exception $e) {
                // Log the full error for debugging but show a cleaner message to users
                error_log("OCR Error: " . $e->getMessage());
                $_SESSION['ocr_error'] = "There was an error processing your document. Please try uploading again with a clearer image.";
                header("Location: registration_success.php");
                exit();
            }

            // Store eligibility status in session
            $_SESSION['is_eligible'] = $isEligible;

            if ($isEligible) {
                $studentData = [
                    'first_name' => $_POST['first_name'] ?? '',
                    'middle_name' => $_POST['middle_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'gender' => $_POST['gender'] ?? '',
                    'dob' => $_POST['dob'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'contact_number' => $_POST['contact_number'] ?? '',
                    'street' => $_POST['street'] ?? '',
                    'student_type' => $_POST['student_type'] ?? '',
                    'previous_school' => $_POST['previous_school'] ?? '',
                    'year_level' => ($_POST['student_type'] === 'ladderized') ? null : ($_POST['year_level'] ?? ''),
                    'previous_program' => ($_POST['student_type'] === 'ladderized') ? 
                        'Diploma in Information and Communication Technology (DICT)' : 
                        ($_POST['previous_program'] ?? ''),
                    'desired_program' => $_POST['desired_program'] ?? '',
                    'tor_path' => $tor_path,
                    'school_id_path' => $school_id_path,
                    'is_tech' => $is_tech,
                    'status' => 'pending'
                ];
                
                // Clean any output before registration
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                registerStudent($conn, $studentData, $subjects);
            } else {
                $_SESSION['success'] = "Registration completed, but you are not eligible for credit transfer.";
                $_SESSION['eligibility_message'] = "Based on your grades and our criteria, you do not meet the eligibility requirements for credit transfer.";
                header("Location: registration_success.php");
                exit();
            }
        } else {
            $_SESSION['ocr_error'] = "Please upload both TOR and School ID files.";
            header("Location: registration_success.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['ocr_error'] = $e->getMessage();
        header("Location: registration_success.php");
        exit();
    }
}

// If we reach here, something went wrong
$_SESSION['ocr_error'] = "An unexpected error occurred.";
header("Location: registration_success.php");
exit();
?>