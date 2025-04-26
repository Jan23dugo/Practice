<?php
// Start output buffering at the very beginning
ob_start();
session_start();

// Disable error display for production
error_reporting(0);
ini_set('display_errors', 0);

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

// Add this near the top of the file after session_start()
if (!isset($_SESSION['debug_output'])) {
    $_SESSION['debug_output'] = '';
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

// Add this near the top of the file after other includes
if (!file_exists(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0777, true);
}

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

// Add this near the top of the file after other includes
class DocumentFormat {
    const TOR = 'tor';
    const GRADES = 'grades';
}

function detectDocumentFormat($text) {
    // Keywords that typically appear in TOR
    $torKeywords = ['TRANSCRIPT OF RECORDS', 'OFFICIAL TRANSCRIPT', 'ACADEMIC RECORD'];
    
    // Keywords that typically appear in Copy of Grades
    $gradesKeywords = ['COPY OF GRADES', 'GRADE REPORT', 'SEMESTRAL GRADES'];
    
    $upperText = strtoupper($text);
    
    foreach ($torKeywords as $keyword) {
        if (strpos($upperText, $keyword) !== false) {
            return DocumentFormat::TOR;
        }
    }
    
    foreach ($gradesKeywords as $keyword) {
        if (strpos($upperText, $keyword) !== false) {
            return DocumentFormat::GRADES;
        }
    }
    
    // Default to grades if format cannot be determined
    return DocumentFormat::GRADES;
}

function extractGradesFromCopyOfGrades($text) {
    $subjects = [];
    $lines = explode("\n", $text);
    
    // Common patterns in Copy of Grades
    $patterns = [
        // Pattern 1: Code Description Units Grade
        '/^([A-Z]{2,4}\s*\d{3,4}[A-Z]?)\s+(.+?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)/i',
        
        // Pattern 2: Code Units Grade Description
        '/^([A-Z]{2,4}\s*\d{3,4}[A-Z]?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+(.+)$/i',
        
        // Pattern 3: Simple table format
        '/([A-Z]{2,4}\s*\d{3,4}[A-Z]?)[|\t](.+?)[|\t](\d+(?:\.\d+)?)[|\t](\d+(?:\.\d+)?)/i'
    ];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                $subject = [
                    'subject_code' => trim($matches[1]),
                    'subject_description' => trim($matches[2]),
                    'units' => floatval($matches[3]),
                    'grade' => floatval($matches[4])
                ];
                
                // Validate the extracted data
                if (validateSubjectData($subject)) {
                    $subjects[] = $subject;
                }
                break;
            }
        }
    }
    
    return $subjects;
}

// Modify the existing extractSubjects function to handle both formats
function extractSubjects($text, $documentType = null) {
    // Clean and standardize the text
    $text = cleanOCRText($text);
    
    // If document type wasn't provided, try to detect it
    if ($documentType === null) {
        $documentType = detectDocumentFormat($text);
    }
    
    // Log the detected document type
    logExtraction("Detected document type: " . $documentType);
    
    // Extract subjects based on document type
    if ($documentType === DocumentFormat::GRADES) {
        $subjects = extractGradesFromCopyOfGrades($text);
    } else {
        // Use existing TOR extraction methods
        $subjects = extractSubjectsGeneric($text);
        
        // If generic method fails, try position-based method
        if (empty($subjects)) {
            $subjects = extractSubjectsByPosition($text);
        }
    }
    
    // Log extraction results
    logExtraction("Extracted " . count($subjects) . " subjects", $subjects);
    
    return $subjects;
}

function standardizeText($text) {
    // Convert to lowercase
    $text = strtolower($text);
    // Remove special characters except spaces and letters
    $text = preg_replace('/[^a-z\s]/', '', $text);
    // Standardize spaces
    $text = preg_replace('/\s+/', ' ', trim($text));
    return $text;
}

// Add these functions after the includes but before other functions
function getNewConnection() {
    try {
        // Include database configuration
        require 'config/config.php';
        
        // Log database configuration (without sensitive info)
        logExtraction("Database configuration loaded", [
            'host' => $servername,
            'database' => $dbname
        ]);
        
        // Set longer timeout in PHP
        ini_set('mysql.connect_timeout', '600');
        ini_set('default_socket_timeout', '600');
        
        // First establish connection to MySQL server
        $newConn = new mysqli($servername, $username, $password);
        
        if ($newConn->connect_error) {
            logExtraction("Database connection failed", [
                'error' => $newConn->connect_error
            ]);
            throw new Exception("Connection failed: " . $newConn->connect_error);
        }
        
        // Explicitly select the database
        if (!$newConn->select_db($dbname)) {
            logExtraction("Failed to select database", [
                'error' => $newConn->error,
                'database' => $dbname,
                'databases_list' => implode(', ', getDatabasesList($newConn))
            ]);
            throw new Exception("Failed to select database '$dbname': " . $newConn->error);
        }
        
        // Set MySQL session variables for longer timeouts
        $newConn->query("SET SESSION wait_timeout=28800"); // 8 hours
        $newConn->query("SET SESSION interactive_timeout=28800"); // 8 hours
        $newConn->query("SET SESSION net_read_timeout=600"); // 10 minutes
        $newConn->query("SET SESSION net_write_timeout=600"); // 10 minutes
        
        // Set connection options
        $newConn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 600);
        $newConn->options(MYSQLI_OPT_READ_TIMEOUT, 600);
        
        // Set connection charset and collation
        $newConn->set_charset('utf8mb4');
        $newConn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Enable keep-alive
        $newConn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        
        // Verify database selection
        $result = $newConn->query("SELECT DATABASE()");
        $row = $result->fetch_row();
        $currentDb = $row[0];
        
        logExtraction("New connection established with extended timeouts", [
            'wait_timeout' => 28800,
            'interactive_timeout' => 28800,
            'net_read_timeout' => 600,
            'net_write_timeout' => 600,
            'database' => $currentDb,
            'connection_id' => $newConn->thread_id
        ]);
        
        return $newConn;
        
    } catch (Exception $e) {
        logExtraction("Error in getNewConnection", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

// Helper function to get list of available databases
function getDatabasesList($conn) {
    $databases = [];
    if ($result = $conn->query("SHOW DATABASES")) {
        while ($row = $result->fetch_row()) {
            $databases[] = $row[0];
        }
        $result->close();
    }
    return $databases;
}

function closeConnection($conn) {
    try {
        if ($conn && $conn instanceof mysqli) {
            $conn->close();
        }
    } catch (Exception $e) {
        logExtraction("Error closing connection", [
            'error' => $e->getMessage()
        ]);
    }
}

function ensureValidConnection($conn) {
    try {
        // If no connection exists, create a new one
        if (!$conn) {
            logExtraction("No connection exists, creating new connection");
            return getNewConnection();
        }

        // Try to ping, but catch any errors
        try {
            if (!$conn->ping()) {
                logExtraction("Ping failed, creating new connection");
                closeConnection($conn);
                return getNewConnection();
            }
        } catch (Exception $e) {
            logExtraction("Error during ping, creating new connection", [
                'error' => $e->getMessage()
            ]);
            closeConnection($conn);
            return getNewConnection();
        }

        // If we get here, connection is valid
        return $conn;
    } catch (Exception $e) {
        logExtraction("Error in ensureValidConnection", [
            'error' => $e->getMessage()
        ]);
        // Always return a new connection if anything fails
        return getNewConnection();
    }
}

// Keep all existing functions but update database operations in these three main functions
function matchCreditedSubjects($conn, $subjects, $student_id) {
    $conn = ensureValidConnection($conn);
    
    try {
        // Get the student's desired program
        $stmt = $conn->prepare("SELECT desired_program FROM register_studentsqe WHERE student_id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $desired_program = $student['desired_program'];
        $stmt->close();
        
        logExtraction("Starting Subject Matching Process", [
            'student_id' => $student_id,
            'desired_program' => $desired_program,
            'total_subjects_from_tor' => count($subjects)
        ]);

        // Get all subjects for the desired program
        $sql = "SELECT * FROM coded_courses WHERE program = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for fetching program subjects: " . $conn->error);
        }
        
        $stmt->bind_param("s", $desired_program);
        $stmt->execute();
        $result = $stmt->get_result();
        $program_subjects = [];
        while($row = $result->fetch_assoc()) {
            $program_subjects[] = $row;
        }
        $stmt->close();

        logExtraction("Retrieved Program Subjects", [
            'program' => $desired_program,
            'total_program_subjects' => count($program_subjects)
        ]);

        $_SESSION['matches'] = [];
        
        // For each subject in student's TOR
        foreach ($subjects as $tor_subject) {
            $conn = ensureValidConnection($conn);
            
            if (!isset($tor_subject['subject_code']) || !isset($tor_subject['subject_description']) || 
                !isset($tor_subject['units']) || !isset($tor_subject['grade'])) {
                continue;
            }
            
            $tor_description = $tor_subject['subject_description'];
            $tor_units = floatval($tor_subject['units']);
            $tor_code = $tor_subject['subject_code'];
            $tor_grade = $tor_subject['grade'];
            
            logExtraction("Processing TOR Subject", [
                'code' => $tor_code,
                'description' => $tor_description,
                'units' => $tor_units
            ]);

            $bestMatch = null;
            $highestSimilarity = 0;

            foreach ($program_subjects as $program_subject) {
                // Only consider subjects with matching units
                if ($program_subject['units'] == $tor_units) {
                    // Calculate similarity between descriptions
                    similar_text(
                        strtolower($program_subject['subject_description']),
                        strtolower($tor_description),
                        $similarity
                    );

                    logExtraction("Checking similarity", [
                        'program_subject' => $program_subject['subject_description'],
                        'tor_subject' => $tor_description,
                        'similarity' => $similarity
                    ]);

                    // Only consider matches with high similarity (80% or more)
                    if ($similarity > $highestSimilarity && $similarity >= 80) {
                        $highestSimilarity = $similarity;
                        $bestMatch = $program_subject;
                    }
                }
            }

            // If we found a good match
            if ($bestMatch) {
                $conn = ensureValidConnection($conn);
                
                // Check for existing match
                $checkSql = "SELECT COUNT(*) as count FROM matched_courses 
                            WHERE student_id = ? AND subject_code = ? AND original_code = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("iss", $student_id, $bestMatch['subject_code'], $tor_code);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result()->fetch_assoc();
                
                if ($checkResult['count'] == 0) {
                    $insert_sql = "INSERT INTO matched_courses 
                                 (subject_code, subject_description, units, student_id, matched_at, original_code, grade) 
                                 VALUES (?, ?, ?, ?, NOW(), ?, ?)";
                    
                    $insert_stmt = $conn->prepare($insert_sql);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param(
                            "ssdiss",
                            $bestMatch['subject_code'],
                            $bestMatch['subject_description'],
                            $bestMatch['units'],
                            $student_id,
                            $tor_code,
                            $tor_grade
                        );
                        
                        if ($insert_stmt->execute()) {
                            logExtraction("Successfully matched and inserted", [
                                'tor_subject' => $tor_description,
                                'program_subject' => $bestMatch['subject_description'],
                                'similarity' => $highestSimilarity
                            ]);
                            
                            $_SESSION['matches'][] = "✓ Matched: {$tor_description} ({$tor_units} units) with {$bestMatch['subject_description']} ({$bestMatch['subject_code']})";
                        }
                        $insert_stmt->close();
                    }
                }
                $checkStmt->close();
            } else {
                logExtraction("No suitable match found", [
                    'tor_subject' => $tor_description,
                    'best_similarity' => $highestSimilarity
                ]);
                $_SESSION['matches'][] = "✗ No match found for: {$tor_description} ({$tor_units} units)";
            }
        }
        
        logExtraction("Matching Process Complete", [
            'total_matches' => count($_SESSION['matches'])
        ]);
        
        return true;
        
    } catch (Exception $e) {
        logExtraction("Error in matchCreditedSubjects", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

function getGradingSystemRules($conn, $universityName) {
    try {
        $conn = ensureValidConnection($conn);
        
        logExtraction("Getting grading system rules", [
            'university' => $universityName
        ]);
        
        $query = "SELECT grading_name, min_percentage, max_percentage, grade_value, description, is_special_grade 
                 FROM university_grading_systems 
                 WHERE grading_name = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $universityName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $gradingRules = [];
        while ($row = $result->fetch_assoc()) {
            $gradingRules[] = $row;
        }
        
        $stmt->close();
        
        logExtraction("Successfully retrieved grading rules", [
            'rules_count' => count($gradingRules)
        ]);
        
        return $gradingRules;
        
    } catch (Exception $e) {
        logExtraction("Error in getGradingSystemRules", [
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}

function registerStudent($conn, $studentData, $subjects) {
    $conn = ensureValidConnection($conn);
    
    try {
        logExtraction("Starting student registration", [
            'student_name' => $studentData['first_name'] . ' ' . $studentData['last_name']
        ]);
        
        // Start transaction
        $conn->begin_transaction();
    
        // Generate reference ID
        $year = date('Y');
        $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $reference_id = "CCIS-{$year}-{$unique}";
        
        // Check for existing reference ID
    $check_stmt = $conn->prepare("SELECT reference_id FROM register_studentsqe WHERE reference_id = ?");
        if (!$check_stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
    $check_stmt->bind_param("s", $reference_id);
        while (true) {
    $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows == 0) {
                break;
            }
        $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $reference_id = "CCIS-{$year}-{$unique}";
    }
        $check_stmt->close();
        
        // Add reference_id to studentData
    $studentData['reference_id'] = $reference_id;
        
        // Insert student data
    $stmt = $conn->prepare("INSERT INTO register_studentsqe (
        last_name, first_name, middle_name, gender, dob, email, contact_number, street, 
        student_type, previous_school, year_level, previous_program, desired_program, 
        tor, school_id, reference_id, is_tech, status, stud_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $is_tech = $studentData['is_tech'] ? 1 : 0;
        $stud_id = $_SESSION['stud_id'] ?? null;

        if (!$stud_id) {
            throw new Exception("No student ID found in session");
        }

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
            $reference_id,  // Use the generated reference_id
            $is_tech,
            $stud_id
    );

        if (!$stmt->execute()) {
            throw new Exception("Error registering student: " . $stmt->error);
        }
        
        $student_id = $stmt->insert_id;
        $_SESSION['success'] = "Your reference ID is: " . $reference_id;
        $_SESSION['reference_id'] = $reference_id;
        $_SESSION['student_id'] = $student_id;
        
        // Match credited subjects
        if (!empty($subjects)) {
        matchCreditedSubjects($conn, $subjects, $student_id);
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Send email
        sendRegistrationEmail($studentData['email'], $reference_id);
        
        $stmt->close();
        
        // Return true and include the reference_id
        return [
            'success' => true,
            'reference_id' => $reference_id,
            'student_id' => $student_id
        ];
        
    } catch (Exception $e) {
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->rollback();
        
        logExtraction("Error in registerStudent", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        throw $e;
    }
}

// Update the determineEligibility function to use the selected grading system
function determineEligibility($subjects, $gradingRules, $selectedGradingSystem) {
    $minPassingPercentage = 85.0; // Minimum required percentage
    
    logExtraction("Starting eligibility determination", [
        'total_subjects' => count($subjects),
        'grading_system' => $selectedGradingSystem
    ]);

    foreach ($subjects as $subject) {
        $grade = $subject['grade'];
        $isSubjectEligible = false;
        
        logExtraction("Checking subject eligibility", [
            'grade' => $grade,
            'subject_code' => $subject['subject_code'] ?? 'N/A'
        ]);
        
        // Find the matching grade rule for this grade
        $matchingRule = null;
        foreach ($gradingRules as $rule) {
            if ($rule['grading_name'] === $selectedGradingSystem) {
                $gradeValue = floatval($rule['grade_value']);
                $minPercentage = floatval($rule['min_percentage']);
                $maxPercentage = floatval($rule['max_percentage']);
                
                // Check if the grade falls within this rule's range
                if ($grade == $gradeValue) {
                    $matchingRule = $rule;
                    break;
                }
            }
        }
        
        // If we found a matching rule, check if the percentage meets our requirement
        if ($matchingRule) {
            $ruleMinPercentage = floatval($matchingRule['min_percentage']);
            if ($ruleMinPercentage >= $minPassingPercentage) {
                $isSubjectEligible = true;
                logExtraction("Subject is eligible", [
                    'grade' => $grade,
                    'percentage' => $ruleMinPercentage
                ]);
            } else {
                logExtraction("Subject is not eligible", [
                    'grade' => $grade,
                    'percentage' => $ruleMinPercentage,
                    'required' => $minPassingPercentage
                ]);
                return false;
            }
        } else {
            logExtraction("No matching grade rule found", [
                'grade' => $grade,
                'grading_system' => $selectedGradingSystem
            ]);
            return false;
        }

        if (!$isSubjectEligible) {
            return false;
        }
    }

    logExtraction("All subjects meet eligibility criteria");
    return true;
}

// Function to check if the student is a tech student based on their previous program
function isTechStudent($previousProgram) {
    // List of tech programs
    $techPrograms = [
        'BSIT',
        'BSCS',
        'BSIS',
        'Bachelor of Science in Information Technology',
        'Bachelor of Science in Computer Science',
        'Bachelor of Science in Information Systems',
        'BS Information Technology',
        'BS Computer Science',
        'BS Information Systems'
    ];
    
    // Convert to uppercase for consistent comparison
    $previousProgram = strtoupper($previousProgram);
    
    foreach ($techPrograms as $program) {
        // Check if the previous program matches any tech program
        if (stripos($previousProgram, strtoupper($program)) !== false) {
            logExtraction("Tech student identified", [
                'previous_program' => $previousProgram,
                'matched_program' => $program
                ]);
                return true;
        }
    }
    
    logExtraction("Non-tech student identified", [
        'previous_program' => $previousProgram
    ]);
    return false;
}

// Add this function at the top of the file
function ensureConnection($conn) {
    if (!$conn->ping()) {
        // Connection is dead, create a new connection
        $conn->close();
        include('config/config.php'); // This should recreate the $conn variable
        return $GLOBALS['conn'];
    }
    return $conn;
}

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

// Replace the existing performOCR function with this Azure Document Intelligence version
function performOCR($filePath) {
    try {
        logExtraction("Starting Azure Document Intelligence OCR process for file", ['path' => $filePath]);

        // Azure Document Intelligence API credentials
        $endpoint = "https://streamsocr.cognitiveservices.azure.com/";
        $apiKey = "7YOiSya9zTZO2WkLje6TdmiSaoG0kKLvcWy2kdFuMXqzKcu9Jr0XJQQJ99BDACqBBLyXJ3w3AAALACOGbhSw";
        $modelId = "transcript_extractor_v3";

        // Read file content
        $fileData = file_get_contents($filePath);

        // Get operation location from Azure
        $operationLocation = analyzeDocument($endpoint, $apiKey, $modelId, $fileData);
        
        // Get analysis results
        $results = getResults($operationLocation, $apiKey);
        
        // Log the raw result structure to debug
        logExtraction("Raw result structure", [
            'status' => $results['status'],
            'has_documents' => isset($results['analyzeResult']['documents']),
            'document_count' => isset($results['analyzeResult']['documents']) ? count($results['analyzeResult']['documents']) : 0,
            'has_content' => isset($results['analyzeResult']['content']),
            'keys' => array_keys($results['analyzeResult'])
        ]);
        
        // Extract structured data if available
        if ($results['status'] === 'succeeded' && 
            isset($results['analyzeResult']['documents']) && 
            !empty($results['analyzeResult']['documents'])) {
            
            $document = $results['analyzeResult']['documents'][0];
            
            // Log document fields
            if (isset($document['fields'])) {
                logExtraction("Document fields available", [
                    'field_names' => array_keys($document['fields'])
                ]);
                
                // Check for our table field
                if (isset($document['fields']['extractTable'])) {
                    // Return the full results as JSON for structured processing
                    $json = json_encode($results);
                    logExtraction("Returning structured JSON data", [
                        'length' => strlen($json)
                    ]);
                    return $json;
                }
            }
        }
        
        // If we get here, fall back to content text
        if (isset($results['analyzeResult']['content'])) {
            $text = $results['analyzeResult']['content'];
            logExtraction("Falling back to raw text content", [
                'text_sample' => substr($text, 0, 100) . '...',
                'text_length' => strlen($text)
            ]);
            return $text;
        }
        
        // Last resort - return the whole response as JSON
        $json = json_encode($results);
        logExtraction("No structured data or content found, returning full response", [
            'json_length' => strlen($json)
        ]);
        return $json;
    } catch (Exception $e) {
        logExtraction("Error in Azure OCR process", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

// Add these functions from test_ocr.php
function analyzeDocument($endpoint, $apiKey, $modelId, $fileData) {
    // Create the URL for the analyze operation
    $url = $endpoint . "documentintelligence/documentModels/" . $modelId . ":analyze?api-version=2024-11-30";
    
    // Set up the headers
    $headers = [
        "Content-Type: application/octet-stream",
        "Ocp-Apim-Subscription-Key: " . $apiKey
    ];

    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    // Execute the request
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers_str = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Get the operation-location header
    if (preg_match('/operation-location: (.*)/i', $headers_str, $matches)) {
        return trim($matches[1]);
    } else {
        throw new Exception("Failed to get operation location. HTTP Status: " . $http_code . "\nResponse: " . $body);
    }
}

function getResults($operationLocation, $apiKey, $maxAttempts = 30) {
    $headers = [
        "Ocp-Apim-Subscription-Key: " . $apiKey
    ];
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $ch = curl_init($operationLocation);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        $resultData = json_decode($result, true);
        
        if (isset($resultData['status'])) {
            if ($resultData['status'] === 'succeeded') {
                return $resultData;
            } else if ($resultData['status'] === 'failed') {
                throw new Exception("Analysis failed: " . json_encode($resultData));
            }
        }
        
        // Wait before next attempt
        $waitTime = min($attempt, 4); // Cap the wait time at 4 seconds
        sleep($waitTime);
    }
    
    throw new Exception("Operation timed out after " . $maxAttempts . " attempts");
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

/**
 * Validates an uploaded file
 * @param array $file The uploaded file array from $_FILES
 * @throws Exception if validation fails
 */
function validateUploadedFile($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = array(
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
        );
        $errorMessage = isset($uploadErrors[$file['error']]) 
            ? $uploadErrors[$file['error']] 
            : "Unknown upload error";
        throw new Exception("File upload failed: " . $errorMessage);
    }

    // Check file size (5MB limit)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        throw new Exception("File size exceeds maximum limit of 5MB");
    }

    // Check file type
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception("Invalid file type. Only PDF, JPG, and PNG files are allowed.");
    }
    
    // Additional security check for file extension
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception("Invalid file extension. Only PDF, JPG, and PNG files are allowed.");
    }
}

// Main processing code
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        logExtraction("Starting POST request processing");
        
        // Initialize response array
        $response = array();
        
        // Check if this is OCR processing request
        if (isset($_POST['process_ocr']) && !isset($_POST['confirm_registration'])) {
            logExtraction("Processing OCR request");
            
            try {
                // Debug log the received files
                logExtraction("Received files", $_FILES);
                
                // Create upload directories if they don't exist
                $uploadDirs = ['uploads', 'uploads/tor', 'uploads/school_id'];
    foreach ($uploadDirs as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception('Failed to create upload directory: ' . $dir);
            }
        }
    }

                // Handle academic document upload
                if (!isset($_FILES['academic_document'])) {
                    throw new Exception('No academic document was uploaded');
                }

                $academicDoc = $_FILES['academic_document'];
                validateUploadedFile($academicDoc);

                // Generate unique filename for academic document
                $academicDocExt = strtolower(pathinfo($academicDoc['name'], PATHINFO_EXTENSION));
                $academicDocNewName = uniqid('tor_') . '.' . $academicDocExt;
                $academicDocPath = 'uploads/tor/' . $academicDocNewName;

                if (!move_uploaded_file($academicDoc['tmp_name'], $academicDocPath)) {
                    throw new Exception('Failed to move academic document to upload directory');
                }

                // Handle school ID upload
                if (!isset($_FILES['school_id'])) {
                    throw new Exception('No school ID was uploaded');
                }

                $schoolId = $_FILES['school_id'];
                validateUploadedFile($schoolId);

                // Generate unique filename for school ID
                $schoolIdExt = strtolower(pathinfo($schoolId['name'], PATHINFO_EXTENSION));
                $schoolIdNewName = uniqid('id_') . '.' . $schoolIdExt;
                $schoolIdPath = 'uploads/school_id/' . $schoolIdNewName;

                if (!move_uploaded_file($schoolId['tmp_name'], $schoolIdPath)) {
                    throw new Exception('Failed to move school ID to upload directory');
                }

                // Store file paths in session for later use
                $_SESSION['uploaded_files'] = [
                    'academic_document' => $academicDocPath,
                    'school_id' => $schoolIdPath
                ];
                
                // Perform OCR on the academic document
                $ocrOutput = performOCR($academicDocPath);
                
                if (empty($ocrOutput)) {
                    throw new Exception("OCR process failed to extract any text from the document");
                }
                
                // Clean OCR output
                $cleanOutput = strip_tags($ocrOutput);
                $cleanOutput = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleanOutput);
                
                // Extract subjects
                $subjects = extractSubjects($cleanOutput);
                
                if (empty($subjects)) {
                    throw new Exception("No subjects could be extracted from the document");
                }
                
                // Sanitize the subjects array
                $sanitizedSubjects = array_map(function($subject) {
                    return array(
                        'subject_code' => strip_tags(trim($subject['subject_code'] ?? '')),
                        'subject_description' => strip_tags(trim($subject['subject_description'] ?? '')),
                        'units' => floatval($subject['units'] ?? 0),
                        'grade' => floatval($subject['grade'] ?? 0)
                    );
                }, $subjects);
                
                // Return success response
                $response = array(
                    'success' => true,
                    'subjects' => $sanitizedSubjects,
                    'files' => [
                        'academic_document' => $academicDocPath,
                        'school_id' => $schoolIdPath
                    ]
                );
                
                logExtraction("Processing successful", [
                    'subjects_count' => count($sanitizedSubjects),
                    'files_saved' => [
                        'academic_document' => $academicDocPath,
                        'school_id' => $schoolIdPath
                    ]
                ]);
                
            } catch (Exception $e) {
                logExtraction("Error in processing", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $response = array(
                    'success' => false,
                    'error' => $e->getMessage()
                );
            }
            
            // Ensure clean output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Send JSON response
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
        // Check if this is final registration submission
        elseif (isset($_POST['confirm_registration'])) {
            logExtraction("Processing final registration");
            
            try {
                // Parse the grades data
                $gradesData = json_decode($_POST['grades'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid grades data format: " . json_last_error_msg());
                }

                // Get student data from form
                $studentData = array(
                    'first_name' => $_POST['first_name'],
                    'middle_name' => $_POST['middle_name'] ?? '',
                    'last_name' => $_POST['last_name'],
                    'gender' => $_POST['gender'],
                    'dob' => $_POST['dob'],
                    'email' => $_POST['email'],
                    'contact_number' => $_POST['contact_number'],
                    'street' => $_POST['street'],
                    'student_type' => $_POST['student_type'],
                    'previous_school' => $_POST['previous_school'],
                    'year_level' => $_POST['student_type'] === 'ladderized' ? null : ($_POST['year_level'] ?? ''),
                    'previous_program' => $_POST['student_type'] === 'ladderized' ? 
                        'Diploma in Information and Communication Technology (DICT)' : 
                        $_POST['previous_program'],
                    'desired_program' => $_POST['desired_program'],
                    'is_tech' => isTechStudent($_POST['previous_program']),
                    // Use the file paths from the session that were saved during OCR processing
                    'tor_path' => $_SESSION['uploaded_files']['academic_document'] ?? null,
                    'school_id_path' => $_SESSION['uploaded_files']['school_id'] ?? null
                );

                // Validate that we have the file paths
                if (empty($studentData['tor_path']) || !file_exists($studentData['tor_path'])) {
                    throw new Exception("Academic document not found. Please try uploading again.");
                }
                if (empty($studentData['school_id_path']) || !file_exists($studentData['school_id_path'])) {
                    throw new Exception("School ID not found. Please try uploading again.");
                }

                // Register student and get result
                $result = registerStudent($conn, $studentData, $gradesData);

                // Send success response with all necessary data
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Registration successful',
                    'reference_id' => $result['reference_id'],
                    'student_name' => $studentData['first_name'] . ' ' . $studentData['last_name'],
                    'email' => $studentData['email']
                ]);

            } catch (Exception $e) {
                sendJsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
    } catch (Exception $e) {
        logExtraction("Error in main process", [
            'error' => $e->getMessage()
        ]);
        
        $response = array(
            'success' => false,
            'error' => $e->getMessage()
        );
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// If we reach here, something went wrong
$_SESSION['ocr_error'] = "An unexpected error occurred. Please try again.";
header("Location: registration_error.php");
exit();

// Add this before the matchCreditedSubjects function
function isGeneralEducation($subjectCode) {
    // List of general education subject code prefixes
    $geSubjects = ['GEED', 'GEC', 'GEE', 'NSTP', 'PE', 'PATH'];
    
    // Convert subject code to uppercase for consistent comparison
    $subjectCode = strtoupper($subjectCode);
    
    foreach ($geSubjects as $prefix) {
        if (stripos($subjectCode, $prefix) === 0) {
            logExtraction("GE subject identified", [
                'subject_code' => $subjectCode,
                'matched_prefix' => $prefix
            ]);
            return true;
        }
    }
    
    // Also check for common GE course numbers
    if (preg_match('/^(GE|GEN)\d/', $subjectCode)) {
        logExtraction("GE subject identified by number pattern", [
            'subject_code' => $subjectCode
        ]);
        return true;
    }
    
    return false;
}

// Function to handle file upload
function handleFileUpload($file, $targetDir) {
    $fileName = basename($file["name"]);
    $targetPath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    
    // Check if file is an actual image
    if (!getimagesize($file["tmp_name"])) {
        throw new Exception("File is not an image.");
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }
    
    // Allow only specific file formats
    if ($fileType != "jpg" && $fileType != "jpeg" && $fileType != "png") {
        throw new Exception("Only JPG, JPEG & PNG files are allowed.");
    }
    
    // Move file to target directory
    if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
        throw new Exception("Failed to upload file.");
    }
    
    return $targetPath;
}

// Function to send JSON response and exit
function sendJsonResponse($data) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Send response
    echo json_encode($data);
    exit();
}
?>