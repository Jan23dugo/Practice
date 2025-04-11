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

error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];

// Validate the uploaded file
function validateUploadedFile($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
    }
    if ($file['size'] > 5000000) { // File size check
        throw new Exception('File is too large. Maximum size is 5MB.');
    }
    return true;
}

// Extract subjects from OCR text
function extractSubjects($text) {
    $subjects = [];
    $logFile = __DIR__ . '/logs/subject_extraction_' . date('Y-m-d_H-i-s') . '.txt';
    file_put_contents($logFile, "Raw OCR Text:\n" . $text . "\n\n", FILE_APPEND);
    
    // Split into lines and clean
    $lines = array_map('trim', explode("\n", $text));
    
    $currentSubject = null;
    $inSubjectsSection = false;
    
    // Common OCR corrections
    $corrections = [
        'Nacional' => 'National',
        'Progrm' => 'Program',
        'Werld' => 'World',
        'Modem' => 'Modern',
        'Securey' => 'Security',
        'Spritualty' => 'Spirituality',
        'Enics' => 'Ethics'
    ];
    
    foreach ($lines as $line) {
        if (empty($line)) continue;
        
        // Start capturing subjects after seeing these headers
        if (preg_match('/(SUBJECT CODE|DESCRIPTIVE TITLE|TERM\s+SUBJECT CODE)/i', $line)) {
            $inSubjectsSection = true;
            continue;
        }
        
        if (!$inSubjectsSection) continue;
        
        // Stop processing if we hit the grading system section
        if (preg_match('/GRADING SYSTEM|NOTHING FOLLOWS/i', $line)) {
            break;
        }
        
        // Split line by tabs or multiple spaces
        $parts = preg_split('/\t+|\s{3,}/', $line);
        $parts = array_map('trim', array_filter($parts));
        
        if (empty($parts)) continue;
        
        // Look for subject code pattern
        foreach ($parts as $index => $part) {
            // Match common subject code patterns
            if (preg_match('/^[A-Z]+\d*-?[A-Z]?(?:LEC|LAB)?-[A-Z]$/i', $part) || 
                preg_match('/^(?:CHEM|GEC|HRM|CLT|MGT|NSTP)[A-Z0-9]+(?:-[A-Z])?$/i', $part)) {
                
                // If we have a previous subject with all required fields, save it
                if ($currentSubject && 
                    !empty($currentSubject['description']) && 
                    $currentSubject['grade'] !== null && 
                    $currentSubject['units'] !== null) {
                    $subjects[] = $currentSubject;
                }
                
                // Start new subject
                $currentSubject = [
                    'subject_code' => $part,
                    'description' => '',
                    'grade' => null,
                    'units' => null
                ];
                
                // Try to get description from remaining parts
                $descParts = [];
                for ($i = $index + 1; $i < count($parts); $i++) {
                    $nextPart = trim($parts[$i]);
                    // Stop if we hit a grade or unit
                    if (preg_match('/^[1-5][\.,]\d{2}$/', $nextPart) || 
                        preg_match('/^[1-6](?:[\.,]0)?$/', $nextPart)) {
                        break;
                    }
                    $descParts[] = $nextPart;
                }
                if (!empty($descParts)) {
                    $currentSubject['description'] = implode(' ', $descParts);
                }
                
                // Look for grade and units in remaining parts
                foreach ($parts as $p) {
                    // Match grade (1.00 to 5.00)
                    if (preg_match('/^[1-5][\.,]\d{2}$/', $p)) {
                        $grade = str_replace(',', '.', $p);
                        $currentSubject['grade'] = number_format(floatval($grade), 2);
                    }
                    // Match units (typically 1-6)
                    elseif (preg_match('/^[1-6](?:[\.,]0)?$/', $p)) {
                        $units = str_replace(',', '.', $p);
                        $currentSubject['units'] = number_format(floatval($units), 1);
                    }
                }
                
                break;
            }
        }
    }
    
    // Add the last subject if complete
    if ($currentSubject && 
        !empty($currentSubject['description']) && 
        $currentSubject['grade'] !== null && 
        $currentSubject['units'] !== null) {
        $subjects[] = $currentSubject;
    }
    
    // Filter out invalid subjects
    $subjects = array_filter($subjects, function($subject) {
        return !empty($subject['subject_code']) && 
               !empty($subject['description']) && 
               $subject['grade'] !== null && 
               $subject['units'] !== null &&
               $subject['units'] > 0 && 
               $subject['units'] <= 6.0 &&
               floatval($subject['grade']) >= 1.00 && 
               floatval($subject['grade']) <= 5.00 &&
               !preg_match('/NOTHING FOLLOWS/i', $subject['description']);
    });
    
    // Log the results
    file_put_contents($logFile, "\nProcessed Subjects:\n" . print_r(array_values($subjects), true), FILE_APPEND);
    
    return array_values($subjects);
}

// 1. Function to compare parsed subjects with coded courses in the database and save matches
function matchCreditedSubjects($conn, $subjects, $student_id) {
    addDebugOutput("Starting Subject Matching Process");
    $_SESSION['matches'] = [];
    
    foreach ($subjects as $subject) {
        // Clean and standardize the description
        $description = strtolower(trim($subject['description']));
        // Remove extra spaces and standardize common words
        $description = preg_replace('/\s+/', ' ', $description);
        $units = $subject['units'];
        
        addDebugOutput("Checking Subject:", [
            'description' => $description,
            'units' => $units,
            'grade' => $subject['grade']
        ]);

        // Modified query to match only by description (using LIKE for better matching)
        $sql = "SELECT * FROM coded_courses 
                WHERE LOWER(subject_description) LIKE ? 
                AND units = ?";

        $description_pattern = "%" . $description . "%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sd", $description_pattern, $units);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                addDebugOutput("Match Found:", $row);
                
                // Insert the matched course
                $insert_sql = "INSERT INTO matched_courses 
                             (subject_code, subject_description, units, student_id, matched_at, original_code, grade) 
                             VALUES (?, ?, ?, ?, NOW(), ?, ?)";
                             
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param(
                    "ssdiss", 
                    $row['subject_code'],        // Our database subject code
                    $row['subject_description'], // Our database description
                    $row['units'],              // Units
                    $student_id,                // Student ID
                    $subject['subject_code'],   // Original subject code from TOR
                    $subject['grade']           // Grade from TOR
                );
                $insert_stmt->execute();
                
                $_SESSION['matches'][] = "✓ Matched: {$subject['subject_code']} - {$subject['description']} ({$subject['units']} units) with grade {$subject['grade']}";
            }
        } else {
            addDebugOutput("No Match Found for Subject", [
                'searched_description' => $description,
                'searched_units' => $units
            ]);
            $_SESSION['matches'][] = "✗ No match for: {$subject['subject_code']} - {$subject['description']} ({$subject['units']} units)";
        }
    }
}

// 2. Function to determine eligibility based on grades and grading system rules
function determineEligibility($subjects, $gradingRules) {
    $minPassingPercentage = 85.0; // Minimum required percentage
    
    $_SESSION['debug_output'] = '';
    $_SESSION['debug_output'] .= "<div style='background: #f5f5f5; padding: 15px; margin: 15px 0; border: 1px solid #ddd;'>";
    $_SESSION['debug_output'] .= "<h3>Grade Eligibility Check:</h3>";

    foreach ($subjects as $subject) {
        $grade = $subject['grade'];
        $isSubjectEligible = false;
        
        $_SESSION['debug_output'] .= "Checking grade: " . $grade . "<br>";
        
        // Find the matching grade rule for this grade
        $matchingRule = null;
        foreach ($gradingRules as $rule) {
            $gradeValue = floatval($rule['grade_value']);
            $minPercentage = floatval($rule['min_percentage']);
            $maxPercentage = floatval($rule['max_percentage']);
            
            $_SESSION['debug_output'] .= "Comparing with rule: Grade Value = $gradeValue, Min % = $minPercentage, Max % = $maxPercentage<br>";
            
            // Check if the grade falls within this rule's range
            if ($grade == $gradeValue) {
                $matchingRule = $rule;
                $_SESSION['debug_output'] .= "Found matching grade rule. Percentage range: $minPercentage - $maxPercentage<br>";
                break;
            }
        }
        
        // If we found a matching rule, check if the percentage meets our requirement
        if ($matchingRule) {
            $ruleMinPercentage = floatval($matchingRule['min_percentage']);
            if ($ruleMinPercentage >= $minPassingPercentage) {
                $isSubjectEligible = true;
                $_SESSION['debug_output'] .= "Subject is eligible: Grade $grade corresponds to percentage $ruleMinPercentage% (≥ $minPassingPercentage%)<br>";
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
            if (stripos($subject['description'], $tech_subject) !== false) {
                addDebugOutput("Tech Subject Found:", [
                    'subject' => $subject['description'],
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
    $check_stmt = $conn->prepare("SELECT reference_id FROM students WHERE reference_id = ?");
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
    
    $stmt = $conn->prepare("INSERT INTO students (
        last_name, first_name, middle_name, gender, dob, email, contact_number, street, 
        student_type, previous_school, year_level, previous_program, desired_program, 
        tor, school_id, reference_id, is_tech
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssssssssssssssi", 
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
        $studentData['is_tech']
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

// Add Azure Computer Vision SDK
require 'vendor/autoload.php';
use MicrosoftAzure\Storage\Common\ServicesBuilder;
use GuzzleHttp\Client;

// Add Azure configuration
function performOCR($imagePath) {
    require_once 'config/azure_config.php';
    
    try {
        $client = new GuzzleHttp\Client();
        
        // First API call to submit the image
        $response = $client->post(AZURE_ENDPOINT . "vision/v3.2/read/analyze", [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
                'Ocp-Apim-Subscription-Key' => AZURE_KEY
            ],
            'body' => file_get_contents($imagePath)
        ]);
        
        if (!$response->hasHeader('Operation-Location')) {
            throw new Exception("No Operation-Location header received from Azure");
        }
        
        $operationLocation = $response->getHeader('Operation-Location')[0];
        
        // Poll for results
        $maxRetries = 10;
        $retryCount = 0;
        $result = null;
        
        do {
            sleep(1);
            $resultResponse = $client->get($operationLocation, [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => AZURE_KEY
                ]
            ]);
            
            $result = json_decode($resultResponse->getBody(), true);
            $retryCount++;
        } while (($result['status'] ?? '') !== 'succeeded' && $retryCount < $maxRetries);

        // Process the results
        $lines = [];
        foreach ($result['analyzeResult']['readResults'] as $page) {
            foreach ($page['lines'] as $line) {
                $box = $line['boundingBox'];
                $y = $box[1]; // vertical position
                $x = $box[0]; // horizontal position
                $text = trim($line['text']);
                
                // Skip empty lines and headers
                if (empty($text) || preg_match('/(UNIVERSITY|SEMESTER|Course Code|Page|Student Name|ID Number)/i', $text)) {
                    continue;
                }
                
                $lines[] = [
                    'text' => $text,
                    'x' => $x,
                    'y' => $y
                ];
            }
        }
        
        // Sort lines by vertical position first, then horizontal
        usort($lines, function($a, $b) {
            $yDiff = $a['y'] - $b['y'];
            return $yDiff == 0 ? $a['x'] - $b['x'] : $yDiff;
        });
        
        // Group lines into rows based on Y position
        $rows = [];
        $currentRow = [];
        $lastY = null;
        $yThreshold = 10; // Pixels threshold for same row
        
        foreach ($lines as $line) {
            if ($lastY === null || abs($line['y'] - $lastY) > $yThreshold) {
                if (!empty($currentRow)) {
                    // Sort items in current row by X position
                    usort($currentRow, function($a, $b) {
                        return $a['x'] - $b['x'];
                    });
                    $rows[] = array_column($currentRow, 'text');
                }
                $currentRow = [];
                $lastY = $line['y'];
            }
            $currentRow[] = $line;
        }
        
        // Add the last row
        if (!empty($currentRow)) {
            usort($currentRow, function($a, $b) {
                return $a['x'] - $b['x'];
            });
            $rows[] = array_column($currentRow, 'text');
        }
        
        // Convert rows to structured text
        $structuredText = '';
        foreach ($rows as $row) {
            $structuredText .= implode("\t", $row) . "\n";
        }
        
        return $structuredText;
        
    } catch (Exception $e) {
        error_log("OCR Error: " . $e->getMessage());
        throw $e;
    }
}

function preprocessImage($imagePath) {
    // Get image info
    $imageInfo = getimagesize($imagePath);
    if ($imageInfo === false) {
        throw new Exception("Invalid image file");
    }
    
    // Log image details
    error_log("Image Type: " . $imageInfo['mime']);
    error_log("Image Dimensions: " . $imageInfo[0] . "x" . $imageInfo[1]);
    
    return $imagePath;
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

            // Preprocess the image before OCR
            $tor_path = preprocessImage($tor_path);

            // Add this function at the top
            try {
                addDebugOutput("Starting OCR Process");
                
                // Replace Tesseract OCR with Azure OCR
                $ocr_output = performOCR($tor_path);
                
                if (empty($ocr_output)) {
                    $_SESSION['ocr_error'] = "The system could not read any text from the uploaded document. Please ensure you have uploaded:
                        \n- A clear, high-quality image
                        \n- An official Transcript of Records
                        \n- A document that is not password protected";
                    header("Location: registration_success.php");
                    exit();
                }

                // Store the raw OCR output in debug but don't show it in the error message
                addDebugOutput("Raw OCR Output:", $ocr_output);

                // Basic validation of OCR output to check if it's a TOR
                $required_keywords = [
                    'TRANSCRIPT','Transcript', 'Records', 'RECORDS', 'UNIT', 'Units', 'Unit', 'UNITS', 'Credit Unit/s',
                    'GRADE', 'Grade','SUBJECT', 'Subject', 'Subject Code', 'Subject Description', 'Course Code', 'Course Code','Course Description'
                ];
                $found_keywords = 0;
                $found_words = [];
                
                foreach ($required_keywords as $keyword) {
                    if (stripos($ocr_output, $keyword) !== false) {
                        $found_keywords++;
                        $found_words[] = $keyword;
                    }
                }

                // Require at least 4 keywords to consider it a valid TOR
                if ($found_keywords < 4) {
                    $_SESSION['ocr_error'] = "The uploaded document does not appear to be a valid Transcript of Records. Please ensure you are uploading an official TOR that contains grades and subject information.";
                    header("Location: registration_success.php");
                    exit();
                }

                addDebugOutput("Document Validation:", [
                    'Keywords Found' => $found_keywords,
                    'Matched Words' => $found_words
                ]);

                addDebugOutput("Raw OCR Output:", $ocr_output);

                // Extract subjects from OCR text
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
                    'is_tech' => $is_tech
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