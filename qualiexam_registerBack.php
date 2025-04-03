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

// Main extraction function that combines all methods
function extractSubjects($text) {
    $logFile = __DIR__ . '/logs/subject_extraction_' . date('Y-m-d_H-i-s') . '.txt';
    $logContent = "=== EXTRACTION PROCESS LOG ===\n\n";
    
    // Try different extraction methods
    $results_generic = extractSubjectsGeneric($text);
    $results_position = extractSubjectsByPosition($text);
    
    // Log raw results
    $logContent .= "Generic Method Results:\n" . print_r($results_generic, true) . "\n\n";
    $logContent .= "Position Method Results:\n" . print_r($results_position, true) . "\n\n";
    
    // Validate results from each method
    $valid_generic = array_filter($results_generic, 'validateSubjectData');
    $valid_position = array_filter($results_position, 'validateSubjectData');
    
    // Log validated results
    $logContent .= "Valid Generic Results: " . count($valid_generic) . "\n";
    $logContent .= "Valid Position Results: " . count($valid_position) . "\n\n";
    
    // Choose the method that found more valid subjects
    $results = count($valid_generic) > count($valid_position) ? 
               $valid_generic : $valid_position;
    
    // If both methods failed, try to salvage any valid subjects from either method
    if (empty($results)) {
        $logContent .= "Both methods failed to extract subjects. Attempting to combine valid results.\n";
        $results = array_merge($valid_generic, $valid_position);
        $results = array_unique($results, SORT_REGULAR);
    }
    
    // Final validation and cleanup
    $final_results = array_values(array_filter($results, function($subject) {
        return validateSubjectData($subject);
    }));
    
    // Log final results
    $logContent .= "Final Extracted Subjects:\n" . print_r($final_results, true);
    file_put_contents($logFile, $logContent);
    
    return $final_results;
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

// Add Google Cloud Vision SDK
require 'vendor/autoload.php';
use GuzzleHttp\Client;

// Add Google Cloud configuration
function performOCR($imagePath) {
    require_once 'config/google_cloud_config.php';
    
    try {
        $client = new GuzzleHttp\Client();
        
        // Prepare the request payload for Google Cloud Vision API
        $imageContent = base64_encode(file_get_contents($imagePath));
        
        $payload = [
            'requests' => [
                [
                    'image' => [
                        'content' => $imageContent
                    ],
                    'features' => [
                        [
                            'type' => 'DOCUMENT_TEXT_DETECTION',
                            'maxResults' => 100
                        ]
                    ]
                ]
            ]
        ];
        
        // Make the API request to Google Cloud Vision
        $response = $client->post(GOOGLE_CLOUD_VISION_ENDPOINT . '?key=' . GOOGLE_CLOUD_API_KEY, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $payload
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        // Process the results
        $lines = [];
        
        // Check if we have text annotations
        if (isset($result['responses'][0]['textAnnotations'])) {
            $textAnnotations = $result['responses'][0]['textAnnotations'];
            
            // Skip the first element as it contains the entire text
            for ($i = 1; $i < count($textAnnotations); $i++) {
                $annotation = $textAnnotations[$i];
                $text = trim($annotation['description']);
                
                // Skip empty lines and headers
                if (empty($text) || preg_match('/(UNIVERSITY|SEMESTER|Course Code|Page|Student Name|ID Number)/i', $text)) {
                    continue;
                }
                
                // Get bounding polygon coordinates
                $vertices = $annotation['boundingPoly']['vertices'];
                $x = $vertices[0]['x'] ?? 0;
                $y = $vertices[0]['y'] ?? 0;
                
                $lines[] = [
                    'text' => $text,
                    'x' => $x,
                    'y' => $y
                ];
            }
        } elseif (isset($result['responses'][0]['fullTextAnnotation'])) {
            // Alternative approach using fullTextAnnotation
            $fullText = $result['responses'][0]['fullTextAnnotation'];
            
            foreach ($fullText['pages'] as $page) {
                foreach ($page['blocks'] as $block) {
                    foreach ($block['paragraphs'] as $paragraph) {
                        foreach ($paragraph['words'] as $word) {
                            $wordText = '';
                            foreach ($word['symbols'] as $symbol) {
                                $wordText .= $symbol['text'];
                            }
                            
                            // Skip empty words and headers
                            if (empty($wordText) || preg_match('/(UNIVERSITY|SEMESTER|Course Code|Page|Student Name|ID Number)/i', $wordText)) {
                                continue;
                            }
                            
                            // Get bounding box coordinates
                            $vertices = $word['boundingBox']['vertices'];
                            $x = $vertices[0]['x'] ?? 0;
                            $y = $vertices[0]['y'] ?? 0;
                            
                            $lines[] = [
                                'text' => $wordText,
                                'x' => $x,
                                'y' => $y
                            ];
                        }
                    }
                }
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
        
        // Modified text processing section
        $structuredText = '';
        
        // Initialize arrays for each category
        $subjectCodes = [];
        $descriptions = [];
        $units = [];
        $grades = [];
        
        foreach ($rows as $row) {
            $lineItems = array_filter($row); // Remove empty elements
            
            // Skip semester headers or other non-subject rows
            if (preg_match('/(1ST|2ND|SEMESTER|TERM)/i', implode(' ', $lineItems))) {
                continue;
            }
            
            if (count($lineItems) >= 4) {
                // Get subject code (usually first element)
                $code = array_shift($lineItems);
                if (!empty($lineItems) && is_numeric(current($lineItems))) {
                    $code .= ' ' . array_shift($lineItems);
                }
                $subjectCodes[] = $code;
                
                // Get the last two numeric values (grade and units)
                $numericValues = array_filter($lineItems, function($item) {
                    return preg_match('/^[0-9.]+$/', str_replace(',', '.', $item));
                });
                
                if (count($numericValues) >= 2) {
                    $grade = array_pop($numericValues);
                    $unit = array_pop($numericValues);
                    
                    // Remove these values from lineItems
                    $lineItems = array_diff($lineItems, [$grade, $unit]);
                    
                    // Add to respective arrays
                    $grades[] = $grade;
                    $units[] = $unit;
                    
                    // Remaining elements form the description
                    $descriptions[] = implode(' ', array_values($lineItems));
                }
            }
        }
        
        // Format the output as lists with proper order
        $structuredText = '';
        
        // First add any header text that might help trigger the subject section
        $structuredText .= "TERM SUBJECT CODE DESCRIPTIVE TITLE\n\n";
        
        foreach ($subjectCodes as $index => $code) {
            if (isset($descriptions[$index]) && isset($grades[$index]) && isset($units[$index])) {
                // Combine all info in one line with tabs as separators
                // Note: We swap grades and units here to correct the order
                $structuredText .= $code . "\t" . 
                                 $descriptions[$index] . "\t" . 
                                 $units[$index] . "\t" . // This was grades before
                                 $grades[$index] . "\n"; // This was units before
            }
        }
        
        // Add detailed logging before returning the structured text
        $logFile = __DIR__ . '/logs/ocr_extraction_' . date('Y-m-d_H-i-s') . '.txt';
        
        // Log raw data first
        $logContent = "=== RAW OCR DATA ===\n";
        $logContent .= print_r($lines, true) . "\n\n";
        
        // Log the organized arrays
        $logContent .= "=== ORGANIZED DATA ===\n";
        $logContent .= "Subject Codes:\n" . print_r($subjectCodes, true) . "\n\n";
        $logContent .= "Descriptions:\n" . print_r($descriptions, true) . "\n\n";
        $logContent .= "Units:\n" . print_r($units, true) . "\n\n";
        $logContent .= "Grades:\n" . print_r($grades, true) . "\n\n";
        
        // Log the final structured text
        $logContent .= "=== FINAL STRUCTURED TEXT ===\n";
        $logContent .= $structuredText . "\n";        
        file_put_contents($logFile, $logContent);

        // Also add to debug output
        addDebugOutput("OCR Processing Results", [
            'subject_count' => count($subjectCodes),
            'sample_subject' => !empty($subjectCodes) ? $subjectCodes[0] : 'None found',
            'log_file' => $logFile
        ]);

        // Add format detection
        $format = detectTORFormat($structuredText);
        
        // Process lines with format-specific rules
        foreach ($lines as &$line) {
            $context = [
                'position' => $line === reset($lines) ? 'start' : 
                             ($line === end($lines) ? 'end' : 'middle')
            ];
            $line['type'] = identifyDataType($line['text'], $context);
        }
        
        // Group by detected types
        $groupedData = [
            'subject_codes' => [],
            'descriptions' => [],
            'units' => [],
            'grades' => []
        ];
        
        foreach ($lines as $line) {
            switch ($line['type']) {
                case 'subject_code':
                    $groupedData['subject_codes'][] = $line['text'];
                    break;
                case 'grade':
                    $groupedData['grades'][] = $line['text'];
                    break;
                case 'units':
                    $groupedData['units'][] = $line['text'];
                    break;
                default:
                    $groupedData['descriptions'][] = $line['text'];
            }
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

            // Preprocess the image before OCR
            $tor_path = preprocessImage($tor_path);

            // Add this function at the top
            try {
                addDebugOutput("Starting OCR Process");
                
                // Replace Tesseract OCR with Google Cloud OCR
                $ocr_output = performOCR($tor_path);
                
                if (empty($ocr_output)) {
                    $_SESSION['ocr_error'] = "The system could not read any text from the uploaded document. Please ensure you have uploaded a clear, high-quality image.";
                    header("Location: registration_success.php");
                    exit();
                }

                // Store the raw OCR output in debug but don't show it in the error message
                addDebugOutput("Raw OCR Output:", $ocr_output);

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

