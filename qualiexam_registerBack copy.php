<?php
// Disable all error reporting and warnings for JSON responses
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log'); // Log to file instead of output

// Enable DEBUG_MODE for detailed error messages
define('DEBUG_MODE', true);

// Prevent PHP from using the deprecated ${var} syntax in strings
ini_set('allow_url_fopen', '1');
ini_set('allow_url_include', '0');

// Start output buffering at the very beginning
ob_start();

// Include necessary files
include('config/config.php');

// Define a flag to control connection closure
define('KEEP_CONNECTION_OPEN', true);

// Start the session
session_start();

// Force JSON content type for AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
}

// Handle fatal errors to return proper JSON responses
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clean any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Send proper JSON error response
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Fatal server error occurred. Please try again later.']);
        exit;
    }
});

// Include necessary files and libraries
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
    try {
        // Make sure the logs directory exists
        $logDir = __DIR__ . '/logs';
        if (!file_exists($logDir)) {
            // Try to create the directory with full permissions
            if (!mkdir($logDir, 0777, true)) {
                // If we can't create the directory, use a fallback
                $logDir = sys_get_temp_dir();
            }
        }
        
        // Make log directory writable if it exists but isn't writable
        if (file_exists($logDir) && !is_writable($logDir)) {
            @chmod($logDir, 0777);
        }
        
        $logFile = $logDir . '/extraction_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        if ($data !== null) {
            // Limit data size to prevent huge log files
            if (is_array($data) || is_object($data)) {
                $dataStr = print_r($data, true);
                // Truncate if too long
                if (strlen($dataStr) > 10000) {
                    $dataStr = substr($dataStr, 0, 10000) . "... [truncated]";
                }
                $logMessage .= "Data: " . $dataStr . "\n";
            } else {
                $logMessage .= "Data: " . $data . "\n";
            }
        }
        
        $logMessage .= "----------------------------------------\n";
        
        // Try to write to the log file, fallback to error_log if we can't
        if (!@file_put_contents($logFile, $logMessage, FILE_APPEND)) {
            error_log("Failed to write to log file. Message was: " . $message);
            // Also try the PHP error log as a fallback
            error_log($logMessage);
        }
    } catch (Exception $e) {
        // Last resort: try to log the failure using PHP's error_log
        error_log("Exception in logExtraction: " . $e->getMessage());
    }
}

// Add this near the top of the file after other includes
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

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
    
    // First try to parse as JSON directly
    $data = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        logExtraction("Input appears to be JSON, checking structure");
        
        // If JSON has subjects array directly
        if (isset($data['subjects'])) {
            logExtraction("Found direct subjects array in JSON");
            foreach ($data['subjects'] as $subject) {
                $subjects[] = [
                    'subject_code' => $subject['subject_code'],
                    'subject_description' => $subject['subject_description'] ?? $subject['description'] ?? '',
                    'units' => floatval($subject['units']),
                    'grade' => floatval($subject['grade'])
                ];
            }
            return $subjects;
        }
        
        // Check for Azure Document Intelligence structure
        if (isset($data['analyzeResult']) && isset($data['analyzeResult']['documents']) 
            && !empty($data['analyzeResult']['documents'])) {
            
            logExtraction("Found Azure Document Intelligence structure");
            $document = $data['analyzeResult']['documents'][0];
            
            if (isset($document['fields']) && isset($document['fields']['extractTable']) 
                && isset($document['fields']['extractTable']['valueArray'])) {
                
                logExtraction("Found table data in Azure results");
                $rows = $document['fields']['extractTable']['valueArray'];
                
                // Skip the first row if it's a header
                for ($i = 1; $i < count($rows); $i++) {
                    if (isset($rows[$i]['valueObject'])) {
                        $rowData = $rows[$i]['valueObject'];
                        
                        // Map the column names to our fields
                        $subject_code = isset($rowData['Code']['valueString']) ? trim($rowData['Code']['valueString']) : '';
                        $subject_description = isset($rowData['Description']['valueString']) ? trim($rowData['Description']['valueString']) : '';
                        $grade = isset($rowData['Grades']['valueString']) ? trim($rowData['Grades']['valueString']) : '';
                        $units = isset($rowData['Units']['valueString']) ? floatval($rowData['Units']['valueString']) : 0;
                        
                        // Check if this is a continuation line
                        if (empty($subject_code) && !empty($subject_description) && !empty($subjects)) {
                            // Append this description to the previous subject
                            $lastIndex = count($subjects) - 1;
                            $subjects[$lastIndex]['subject_description'] .= ' ' . $subject_description;
                            continue;
                        }
                        
                        // Only add if we have some data
                        if (!empty($subject_code) || !empty($subject_description)) {
                            $subjects[] = [
                                'subject_code' => $subject_code,
                                'subject_description' => $subject_description,
                                'grade' => floatval(str_replace(',', '.', $grade)),
                                'units' => $units
                            ];
                        }
                    }
                }
                
                if (!empty($subjects)) {
                    logExtraction("Successfully extracted subjects from Azure format", ['count' => count($subjects)]);
                    return $subjects;
                }
            }
        }
    }

    // If we get here, try parsing as plain text
    logExtraction("Falling back to text parsing", ['text_length' => strlen($text)]);

    // Original text parsing code
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
    
    logExtraction("Extraction complete", ['subjects_found' => count($subjects)]);
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
        global $conn, $servername, $username, $password, $dbname;
        
        // Log the database configuration values to verify they're defined
        logExtraction("getNewConnection - Database configuration check", [
            'servername' => isset($servername) ? $servername : 'NOT SET',
            'username' => isset($username) ? $username : 'NOT SET',
            'dbname' => isset($dbname) ? $dbname : 'NOT SET',
            'password' => isset($password) ? '[MASKED]' : 'NOT SET'
        ]);
        
        // If database constants are not set, include config.php again
        if (!isset($servername) || !isset($username) || !isset($password) || !isset($dbname)) {
            logExtraction("Database constants not found, including config.php");
            include_once('config/config.php');
        }
        
        // If the global connection exists and is valid, return it
        if (isset($conn) && $conn && $conn->ping()) {
            logExtraction("Reusing existing valid database connection");
            return $conn;
        } else {
            // If connection is not valid, create a new one
            logExtraction("Creating new database connection to {$servername}/{$dbname}");
            $newConn = new mysqli($servername, $username, $password, $dbname);
            
            if ($newConn->connect_error) {
                logExtraction("Database connection error: " . $newConn->connect_error);
                return null;
            }
            
            // Set character set
            $newConn->set_charset("utf8");
            
            // Update the global connection
            $GLOBALS['conn'] = $newConn;
            
            logExtraction("New database connection established successfully");
            return $newConn;
        }
    } catch (Exception $e) {
        logExtraction("Database connection error: " . $e->getMessage());
        return null;
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

// Only modify the existing closeConnection function
function closeConnection($conn) {
    // Skip connection closing if KEEP_CONNECTION_OPEN is defined and true
    if (!defined('KEEP_CONNECTION_OPEN') || !KEEP_CONNECTION_OPEN) {
        try {
            if ($conn && $conn instanceof mysqli) {
                $conn->close();
            }
        } catch (Exception $e) {
            logExtraction("Error closing connection", [
                'error' => $e->getMessage()
            ]);
        }
    } else {
        // Just log that we're keeping connection open
        logExtraction("Keeping database connection open due to KEEP_CONNECTION_OPEN flag");
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

// Add this at the beginning of the file, after the first session_start()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set header to return JSON for AJAX requests
    header('Content-Type: application/json');
}

// Keep all existing functions but update database operations in these three main functions
function matchCreditedSubjects($conn, $subjects, $student_id) {
    try {
        // Ensure we have a valid connection
        if (!$conn || !method_exists($conn, 'ping') || !@$conn->ping()) {
            $conn = fixDatabaseConnection();
            if (!$conn) {
                throw new Exception("Database connection is not valid and couldn't be fixed");
            }
        }
        
        // Get the student's desired program
        $stmt = $conn->prepare("SELECT desired_program FROM register_studentsqe WHERE stud_id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("Student record not found");
        }
        
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
        
        // Create an associative array for faster lookups
        while($row = $result->fetch_assoc()) {
            // Add subject to the array, indexed by its code and description for quicker matching
            $program_subjects[$row['subject_code']] = $row;
            
            // Also create a normalized version of the description for matching
            $normalized_desc = strtolower(trim($row['subject_description']));
            $row['normalized_desc'] = $normalized_desc;
            $program_subjects_by_desc[$normalized_desc] = $row;
        }
        $stmt->close();

        logExtraction("Retrieved Program Subjects", [
            'program' => $desired_program,
            'total_program_subjects' => count($program_subjects)
        ]);

        // If no program subjects found, log warning
        if (empty($program_subjects)) {
            logExtraction("WARNING: No subjects found for program", [
                'program' => $desired_program
            ]);
            return 0;
        }

        // DEBUG: List all available program subjects for reference
        $allProgramSubjects = [];
        foreach ($program_subjects as $ps) {
            $allProgramSubjects[] = [
                'code' => $ps['subject_code'],
                'description' => $ps['subject_description'],
                'units' => $ps['units']
            ];
        }
        logExtraction("All available program subjects", $allProgramSubjects);

        // Initialize matches counter and arrays for tracking
        $insertCount = 0;
        $matchInfo = [];
        $unmatchedSubjects = [];
        
        // For each subject in student's TOR
        foreach ($subjects as $tor_subject) {
            // Ensure connection for each iteration
            if (!$conn->ping()) {
                $conn = fixDatabaseConnection();
            }
            
            if (empty($tor_subject['subject_code']) || empty($tor_subject['subject_description']) || 
                empty($tor_subject['units']) || empty($tor_subject['grade'])) {
                continue;
            }
            
            $tor_description = trim($tor_subject['subject_description']);
            $tor_units = floatval($tor_subject['units']);
            $tor_code = trim($tor_subject['subject_code']);
            $tor_grade = $tor_subject['grade'];
            
            // Normalize the description for comparison
            $normalized_tor_desc = strtolower($tor_description);
            
            logExtraction("Processing TOR Subject", [
                'code' => $tor_code,
                'description' => $tor_description,
                'units' => $tor_units,
                'grade' => $tor_grade
            ]);

            $bestMatch = null;
            $matchFound = false;
            $matchMethod = "";
            
            // STEP 1: Try direct code matching first (most reliable)
            if (isset($program_subjects[$tor_code]) && $program_subjects[$tor_code]['units'] == $tor_units) {
                $bestMatch = $program_subjects[$tor_code];
                $matchFound = true;
                $matchMethod = "Exact code match";
            }
            
            // STEP 2: If no code match, try exact description matching
            if (!$matchFound) {
                // Check for an exact description match
                foreach ($program_subjects as $subject) {
                    if (strtolower(trim($subject['subject_description'])) == $normalized_tor_desc && 
                        $subject['units'] == $tor_units) {
                        $bestMatch = $subject;
                        $matchFound = true;
                        $matchMethod = "Exact description match";
                        break;
                    }
                }
            }
            
            // STEP 3: Finally, try similarity matching only if the previous methods failed
            if (!$matchFound) {
                $highestSimilarity = 0;
                $similarityMatches = [];
                
                foreach ($program_subjects as $subject) {
                    // Only consider subjects with matching units
                    if ($subject['units'] == $tor_units) {
                        $subjectDesc = trim($subject['subject_description']);
                        
                        // Calculate similarity
                        similar_text(
                            strtolower($subjectDesc),
                            $normalized_tor_desc,
                            $similarity
                        );
                        
                        // Store all potential matches with similarity scores
                        if ($similarity > 80) {
                            $similarityMatches[] = [
                                'subject' => $subject,
                                'similarity' => $similarity
                            ];
                        }
                        
                        // Keep track of the best match
                        if ($similarity > $highestSimilarity && $similarity >= 97) { // Extremely high threshold
                            $highestSimilarity = $similarity;
                            $bestMatch = $subject;
                            $matchFound = true;
                            $matchMethod = "High similarity match ({$similarity}%)";
                        }
                    }
                }
                
                // Log all potential similarity matches for debugging
                if (!empty($similarityMatches)) {
                    usort($similarityMatches, function($a, $b) {
                        return $b['similarity'] <=> $a['similarity'];
                    });
                    
                    logExtraction("Potential similarity matches for '{$tor_description}'", 
                        array_map(function($match) {
                            return [
                                'code' => $match['subject']['subject_code'],
                                'description' => $match['subject']['subject_description'],
                                'similarity' => $match['similarity']
                            ];
                        }, $similarityMatches)
                    );
                }
            }

            // If we found a match
            if ($matchFound && $bestMatch) {
                // VERIFICATION: Make sure the match is actually in our program subjects
                $verificationCheck = false;
                foreach ($program_subjects as $validSubject) {
                    if ($validSubject['subject_code'] == $bestMatch['subject_code']) {
                        $verificationCheck = true;
                        break;
                    }
                }
                
                if (!$verificationCheck) {
                    logExtraction("CRITICAL ERROR: Match verification failed", [
                        'matched_code' => $bestMatch['subject_code'],
                        'matched_description' => $bestMatch['subject_description']
                    ]);
                    continue; // Skip this match as it failed verification
                }
                
                // Ensure connection
                if (!$conn->ping()) {
                    $conn = fixDatabaseConnection();
                }
                
                // Check for existing match to avoid duplicates
                $checkSql = "SELECT COUNT(*) as count FROM matched_courses 
                            WHERE student_id = ? AND subject_code = ? AND original_code = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("iss", $student_id, $bestMatch['subject_code'], $tor_code);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result()->fetch_assoc();
                
                if ($checkResult['count'] == 0) {
                    $insert_sql = "INSERT INTO matched_courses 
                                 (subject_code, subject_description, units, student_id, original_code, grade, matched_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    
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
                            $insertCount++;
                            
                            logExtraction("Successfully matched and inserted", [
                                'tor_subject' => $tor_description,
                                'program_subject' => $bestMatch['subject_description'],
                                'match_method' => $matchMethod
                            ]);
                            
                            $matchInfo[] = "✓ Matched: {$tor_description} ({$tor_units} units) with {$bestMatch['subject_description']} ({$bestMatch['subject_code']}) - {$matchMethod}";
                        } else {
                            logExtraction("Error inserting match", [
                                'error' => $insert_stmt->error,
                                'tor_subject' => $tor_description,
                                'program_subject' => $bestMatch['subject_description']
                            ]);
                        }
                        $insert_stmt->close();
                    }
                } else {
                    logExtraction("Match already exists in database", [
                        'tor_subject' => $tor_description,
                        'program_subject' => $bestMatch['subject_description']
                    ]);
                }
                $checkStmt->close();
            } else {
                // Track unmatched subjects but don't insert them
                $unmatchedSubjects[] = [
                    'code' => $tor_code,
                    'description' => $tor_description,
                    'units' => $tor_units
                ];
                
                logExtraction("No match found in program subjects", [
                    'tor_subject' => $tor_description,
                    'tor_code' => $tor_code,
                    'tor_units' => $tor_units
                ]);
                
                $matchInfo[] = "✗ No match found for: {$tor_description} ({$tor_units} units)";
            }
        }
        
        // Log summary of matching results
        logExtraction("Matching Summary", [
            'total_subjects' => count($subjects),
            'matched' => $insertCount,
            'unmatched' => count($unmatchedSubjects),
            'unmatched_subjects' => $unmatchedSubjects
        ]);
        
        // Store match info in session for potential display
        $_SESSION['matches'] = $matchInfo;
        $_SESSION['matched_count'] = $insertCount;
        $_SESSION['unmatched_count'] = count($unmatchedSubjects);
        
        logExtraction("Matching Process Complete", [
            'total_matches' => $insertCount
        ]);
        
        return $insertCount;
        
    } catch (Exception $e) {
        error_log("Error in matchCreditedSubjects: " . $e->getMessage());
        logExtraction("Error in matchCreditedSubjects", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return 0;
    }
}

// Add this new function to handle grade conversion
function convertGradeToStandardScale($grade, $gradingRules) {
    foreach ($gradingRules as $rule) {
        if ($grade == $rule['grade_value']) {
            // Return the average of min and max percentage
            return ($rule['min_percentage'] + $rule['max_percentage']) / 2;
        }
    }
    // If no matching rule found, return 0 to indicate failing grade
    return 0;
}

function getGradingSystemRules($conn, $universityName) {
    try {
        // Always start with a fresh connection for this operation
        $conn = getNewConnection();
        
        logExtraction("Getting grading system rules", [
            'grading_system' => $universityName
        ]);
        
        // Query based on the grading_name field
        $query = "SELECT id, grading_name, min_percentage, max_percentage, grade_value, description, is_special_grade 
                 FROM university_grading_systems 
                 WHERE grading_name = ? 
                 ORDER BY min_percentage DESC";
                 
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $universityName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $stmt->close();
            
            // Get a fresh connection for the second query with fuzzy matching
            $conn = getNewConnection();
            
            $likeTerm = "%$universityName%";
            $query = "SELECT id, grading_name, min_percentage, max_percentage, grade_value, description, is_special_grade 
                     FROM university_grading_systems 
                     WHERE grading_name LIKE ? 
                     ORDER BY min_percentage DESC";
                     
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database error on fuzzy search: " . $conn->error);
            }
            $stmt->bind_param("s", $likeTerm);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        $gradingRules = [];
        while ($row = $result->fetch_assoc()) {
            $gradingRules[] = $row;
        }
        
        $stmt->close();
        closeConnection($conn);
        
        logExtraction("Successfully retrieved grading rules", [
            'grading_system' => $universityName,
            'rules_count' => count($gradingRules),
            'sample_rule' => count($gradingRules) > 0 ? $gradingRules[0] : null
        ]);
        
        return $gradingRules;
        
    } catch (Exception $e) {
        logExtraction("Error in getGradingSystemRules", [
            'error' => $e->getMessage()
        ]);
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            closeConnection($conn);
        }
        throw $e;
    }
}

function registerStudent($conn, $studentData, $subjects) {
    try {
        // Ensure connection is valid before starting transaction
        if (!$conn || !method_exists($conn, 'ping') || !@$conn->ping()) {
            // Connection is invalid, get a new one using our helper function
            $conn = fixDatabaseConnection();
            if (!$conn) {
                throw new Exception("Database connection is not valid and couldn't be fixed");
            }
        }
        
        // Skip checks since fixDatabaseConnection already validated the connection
        
        // Check if the required table exists
        $tableCheckQuery = "SHOW TABLES LIKE 'register_studentsqe'";
        $result = $conn->query($tableCheckQuery);
        if (!$result || $result->num_rows === 0) {
            throw new Exception("Required table 'register_studentsqe' does not exist");
        }
        
        // Check table structure to ensure all columns exist
        $describeQuery = "DESCRIBE register_studentsqe";
        $result = $conn->query($describeQuery);
        if (!$result) {
            throw new Exception("Failed to get table structure: " . $conn->error);
        }
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        logExtraction("Table columns", ['columns' => $columns]);
        
        // Required columns for our insert
        $requiredColumns = [
            'last_name', 'first_name', 'middle_name', 'gender', 'dob', 'email', 
            'contact_number', 'street', 'student_type', 'previous_school', 
            'year_level', 'previous_program', 'desired_program', 'tor', 
            'school_id', 'reference_id', 'is_tech', 'status', 'stud_id'
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        if (!empty($missingColumns)) {
            throw new Exception("Missing required columns in table: " . implode(', ', $missingColumns));
        }
        
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
        
        // Make sure we have a unique reference ID
        $exists = true;
        while ($exists) {
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                $exists = false;
            } else {
                $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $reference_id = "CCIS-{$year}-{$unique}";
            }
        }
        $check_stmt->close();
        
        $studentData['reference_id'] = $reference_id;
    
        // Ensure stud_id exists in session
        if (!isset($_SESSION['stud_id'])) {
            throw new Exception("Student ID not found in session. Please log in again.");
        }
        
        // Handle null values for middle_name
        $middleName = empty($studentData['middle_name']) ? null : $studentData['middle_name'];
        
        // Handle year_level for ladderized students
        $yearLevel = ($studentData['student_type'] === 'ladderized' || empty($studentData['year_level'])) 
            ? 0 : (int)$studentData['year_level'];
        
        // Convert other values
        $isTech = (int)$studentData['is_tech'];
        $studId = (int)$_SESSION['stud_id'];
        
        // Log the SQL INSERT statement preparation
        logExtraction("Preparing SQL INSERT statement");
        
        // Insert student data with simple SQL statement - match the exact columns in the database
        $sql = "INSERT INTO register_studentsqe (
            last_name, first_name, middle_name, gender, dob, email, contact_number, street, 
            student_type, previous_school, year_level, previous_program, desired_program, 
            tor, school_id, reference_id, is_tech, status, stud_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error preparing statement: " . $conn->error . " SQL: $sql");
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
            $studentData['reference_id'],
            $studentData['is_tech'],
                $_SESSION['stud_id']
        );

        // Log all parameter values for debugging
        logExtraction("Insert parameters", [
            'last_name' => $studentData['last_name'],
            'first_name' => $studentData['first_name'],
            'middle_name' => $middleName,
            'gender' => $studentData['gender'],
            'dob' => $studentData['dob'],
            'email' => $studentData['email'],
            'contact_number' => $studentData['contact_number'],
            'street' => $studentData['street'],
            'student_type' => $studentData['student_type'],
            'previous_school' => $studentData['previous_school'],
            'year_level' => $yearLevel,
            'previous_program' => $studentData['previous_program'],
            'desired_program' => $studentData['desired_program'],
            'tor_path' => $studentData['tor_path'],
            'school_id_path' => $studentData['school_id_path'],
            'reference_id' => $reference_id,
            'is_tech' => $isTech,
            'stud_id' => $studId
        ]);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting student: " . $stmt->error);
        }
        
        $student_id = $stmt->insert_id;
        $stmt->close();
        
        // Store reference ID in session
        $_SESSION['success'] = "Your reference ID is: " . $reference_id;
        $_SESSION['reference_id'] = $reference_id;
        $_SESSION['student_id'] = $student_id;
        
        // Simple subject registration (skip complex matching for now)
        foreach ($subjects as $subject) {
            // Insert each subject with basic information
            $subjectCode = $subject['subject_code'] ?? '';
            // Check for both possible key names for description
            $subjectDesc = $subject['subject_description'] ?? $subject['description'] ?? '';
            $units = floatval($subject['units'] ?? 0);
            $grade = $subject['grade'] ?? '';
            
            $sql = "INSERT INTO matched_courses (
                subject_code, subject_description, units, student_id, original_code, grade
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $subjStmt = $conn->prepare($sql);
            if ($subjStmt) {
                $subjStmt->bind_param("ssdiss", 
                    $subjectCode, 
                    $subjectDesc,
                    $units,
                    $student_id,
                    $subjectCode,  // original_code same as subject_code
                    $grade
                );
                $subjStmt->execute();
                $subjStmt->close();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Try to send email but don't fail if it doesn't work
        try {
            if (function_exists('sendRegistrationEmail')) {
                sendRegistrationEmail($studentData['email'], $reference_id);
            }
        } catch (Exception $emailEx) {
            // Just log the error but continue
            error_log("Email send error: " . $emailEx->getMessage());
        }
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
        }
        
        error_log("Registration error: " . $e->getMessage());
        logExtraction("Registration error", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}

// 2. Function to determine eligibility based on grades and grading system rules
function determineEligibility($subjects, $gradingRules) {
    logExtraction("Starting eligibility determination", [
        'subject_count' => count($subjects), 
        'rules_count' => count($gradingRules)
    ]);
    
    $minPassingPercentage = 85.0; // Minimum required percentage (90.00 in the database)
    
    // No subjects found
    if (empty($subjects)) {
        logExtraction("No subjects found for eligibility check");
        return false;
    }
    
    // No grading rules found
    if (empty($gradingRules)) {
        logExtraction("No grading rules found for eligibility check");
        return false;
    }
    
    // Check each subject
    foreach ($subjects as $subject) {
        if (empty($subject['grade'])) {
            logExtraction("Subject missing grade", ['subject' => $subject]);
            continue; // Skip subjects without grades
        }
        
        $grade = trim($subject['grade']);
        $subjectCode = $subject['subject_code'] ?? 'Unknown';
        $isSubjectEligible = false;
        
        logExtraction("Checking subject eligibility", [
            'subject_code' => $subjectCode,
            'grade' => $grade
        ]);
        
        // Skip checking NSTP subjects (they're usually Pass/Fail)
        if (stripos($subjectCode, 'NSTP') === 0) {
            logExtraction("Skipping NSTP subject", ['subject_code' => $subjectCode]);
            continue;
        }
        
        // Find the matching grade rule for this grade
        $matchingRule = null;
        
        // First, try to find an exact match on grade_value
        foreach ($gradingRules as $rule) {
            $gradeValue = (string)$rule['grade_value'];
            
            // Try to match exactly first
            if (strcasecmp(trim($grade), trim($gradeValue)) === 0) {
                $matchingRule = $rule;
                logExtraction("Found exact grade match", [
                    'grade' => $grade,
                    'grade_value' => $gradeValue
                ]);
                break;
            }
        }
        
        // If no exact match, try numeric comparison for numeric grades
        if (!$matchingRule && is_numeric($grade)) {
            $gradeNumeric = floatval($grade);
            
            foreach ($gradingRules as $rule) {
                if (is_numeric($rule['grade_value'])) {
                    $ruleValue = floatval($rule['grade_value']);
                    
                    // For the university system in screenshot, lower grade_value is better
                    // (e.g., 1.0 is Excellent, 1.5 is Very Good)
                    if ($gradeNumeric <= $ruleValue) {
                        $matchingRule = $rule;
                        logExtraction("Found numeric grade match", [
                            'grade' => $grade,
                            'grade_value' => $rule['grade_value']
                        ]);
                        break;
                    }
                }
            }
        }
        
        // If we found a matching rule, check if the percentage meets our requirement
        if ($matchingRule) {
            $ruleMinPercentage = floatval($matchingRule['min_percentage']);
            
            logExtraction("Found matching grade rule", [
                'grade' => $grade,
                'min_percentage' => $ruleMinPercentage,
                'required_percentage' => $minPassingPercentage
            ]);
            
            if ($ruleMinPercentage >= $minPassingPercentage) {
                $isSubjectEligible = true;
                logExtraction("Subject is eligible", [
                    'subject_code' => $subjectCode,
                    'grade' => $grade,
                    'percentage' => $ruleMinPercentage
                ]);
            } else {
                logExtraction("Subject is not eligible", [
                    'subject_code' => $subjectCode,
                    'grade' => $grade,
                    'percentage' => $ruleMinPercentage,
                    'required' => $minPassingPercentage
                ]);
                
                // Exit early if one subject is not eligible
                return false;
            }
        } else {
            logExtraction("No matching grade rule found", [
                'subject_code' => $subjectCode,
                'grade' => $grade
            ]);
            
            // If no rule found, consider it as not eligible
            return false;
        }

        if (!$isSubjectEligible) {
            logExtraction("Subject does not meet eligibility criteria", [
                'subject_code' => $subjectCode,
                'grade' => $grade
            ]);
            return false;
        }
    }

    logExtraction("All subjects meet eligibility criteria");
    return true;
}

// 3. Function to check if the student is a tech student based on their previous program
function isTechStudent($subjects) {
    logExtraction("Starting Tech Student Check");
    
    // Check if the student's program name exists in the POST data
    if (isset($_POST['previous_program'])) {
        $programName = strtolower($_POST['previous_program']);
        
        $techPrograms = [
            "Bachelor of Science in Computer Science (BSCS)",
            "Bachelor of Science in Information Technology (BSIT)"
        ];
        
        foreach ($techPrograms as $techProgram) {
            if (strpos($programName, $techProgram) !== false) {
                logExtraction("Tech student identified by program name", [
                    'program' => $programName,
                    'matched_keyword' => $techProgram
                ]);
                return true;
            }
        }
        
        logExtraction("Student not classified as tech student - program doesn't match tech criteria", [
            'program' => $programName
        ]);
    } else {
        logExtraction("No previous program data available to determine tech status");
    }
    
    return false;
}

// Add this function at the top of the file
function ensureConnection($conn) {
    try {
        // Check if connection exists and is valid
        if (!$conn || !method_exists($conn, 'ping') || !@$conn->ping()) {
            logExtraction("Connection is invalid in ensureConnection(), creating a new one");
            // Use our improved function to get a new connection
            return fixDatabaseConnection();
        }
        
        // Test connection with a simple query
        $testResult = $conn->query("SELECT 1");
        if (!$testResult) {
            logExtraction("Connection test failed in ensureConnection(): " . $conn->error);
            return fixDatabaseConnection();
        }
        
        logExtraction("Connection validated in ensureConnection()");
        return $conn;
    } catch (Exception $e) {
        logExtraction("Error in ensureConnection: " . $e->getMessage());
        return fixDatabaseConnection();
    }
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

        // Check if file exists
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }

        // Check file size
        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            throw new Exception("File is empty: $filePath");
        }
        
        logExtraction("File validation passed", [
            'size' => $fileSize,
            'type' => mime_content_type($filePath)
        ]);

        // Azure Document Intelligence API credentials
        $endpoint = "https://streamsocr.cognitiveservices.azure.com/";
        $apiKey = "7YOiSya9zTZO2WkLje6TdmiSaoG0kKLvcWy2kdFuMXqzKcu9Jr0XJQQJ99BDACqBBLyXJ3w3AAALACOGbhSw";
        $modelId = "transcript_extractor_v4";

        // Read file content
        $fileData = file_get_contents($filePath);
        if ($fileData === false) {
            throw new Exception("Failed to read file content: $filePath");
        }

        logExtraction("Sending request to Azure Document Intelligence", [
            'fileSize' => strlen($fileData),
            'endpoint' => $endpoint,
            'modelId' => $modelId
        ]);

        // Get operation location from Azure
        $operationLocation = analyzeDocument($endpoint, $apiKey, $modelId, $fileData);
        
        if (empty($operationLocation)) {
            throw new Exception("Failed to get operation location from Azure service");
        }
        
        logExtraction("Got operation location", ['location' => $operationLocation]);
        
        // Get analysis results
        $results = getResults($operationLocation, $apiKey);
        
        if (empty($results) || !is_array($results)) {
            throw new Exception("Received invalid results from Azure service");
        }
        
        // Log the status of the result
        logExtraction("Received results from Azure", [
            'status' => $results['status'] ?? 'unknown',
            'has_content' => isset($results['analyzeResult']['content']) ? 'yes' : 'no',
            'has_documents' => isset($results['analyzeResult']['documents']) ? 'yes' : 'no'
        ]);
        
        // Check for failed status
        if (isset($results['status']) && $results['status'] === 'failed') {
            $errorDetails = isset($results['error']) ? json_encode($results['error']) : 'Unknown error';
            throw new Exception("Azure analysis failed: $errorDetails");
        }
        
        // Extract structured data if available
        if (isset($results['status']) && $results['status'] === 'succeeded' && 
            isset($results['analyzeResult']['documents']) && 
            !empty($results['analyzeResult']['documents'])) {
            
            $document = $results['analyzeResult']['documents'][0];
            
            // Check for our table field
            if (isset($document['fields']) && isset($document['fields']['extractTable'])) {
                // Return the full results as JSON for structured processing
                $json = json_encode($results);
                if ($json === false) {
                    throw new Exception("Failed to encode results as JSON: " . json_last_error_msg());
                }
                
                logExtraction("Returning structured JSON data", [
                    'length' => strlen($json)
                ]);
                return $json;
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
        if ($json === false) {
            throw new Exception("Failed to encode results as JSON: " . json_last_error_msg());
        }
        
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
    // Log the start of the analysis process
    logExtraction("Starting document analysis", [
        'endpoint' => $endpoint,
        'modelId' => $modelId,
        'data_size' => strlen($fileData)
    ]);
    
    // Create the URL for the analyze operation
    $url = $endpoint . "documentintelligence/documentModels/" . $modelId . ":analyze?api-version=2024-11-30";
    
    // Set up the headers
    $headers = [
        "Content-Type: application/octet-stream",
        "Ocp-Apim-Subscription-Key: " . $apiKey
    ];

    // Initialize cURL
    $ch = curl_init($url);
    if (!$ch) {
        logExtraction("Failed to initialize cURL");
        throw new Exception("Failed to initialize cURL for API request");
    }
    
    // Set cURL options with longer timeout
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // 30 seconds connection timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes total timeout
    
    // Log before execution
    logExtraction("Executing cURL request to Azure");

    // Execute the request
    $response = curl_exec($ch);
    
    // Check for cURL errors
    if ($response === false) {
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        logExtraction("cURL request failed", [
            'error' => $curlError,
            'errno' => $curlErrno
        ]);
        
        throw new Exception("API request failed: $curlError (Error code: $curlErrno)");
    }
    
    // Get HTTP status and header size
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    // Log response info
    logExtraction("Received response from Azure", [
        'http_code' => $http_code,
        'response_size' => strlen($response),
        'header_size' => $header_size
    ]);
    
    // Extract headers and body
    $headers_str = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    curl_close($ch);
    
    // Check for HTTP errors
    if ($http_code < 200 || $http_code >= 300) {
        logExtraction("HTTP error from Azure", [
            'http_code' => $http_code,
            'response_body' => $body
        ]);
        
        throw new Exception("Azure API returned error: HTTP $http_code - $body");
    }

    // Get the operation-location header
    if (preg_match('/operation-location: (.*)/i', $headers_str, $matches)) {
        $operationLocation = trim($matches[1]);
        
        logExtraction("Successfully extracted operation location", [
            'location' => $operationLocation
        ]);
        
        return $operationLocation;
    } else {
        logExtraction("Failed to extract operation location", [
            'headers' => $headers_str,
            'body' => $body
        ]);
        
        throw new Exception("Failed to get operation location. HTTP Status: " . $http_code . "\nResponse: " . $body);
    }
}

function getResults($operationLocation, $apiKey, $maxAttempts = 30) {
    logExtraction("Starting to poll for results", [
        'operation_location' => $operationLocation,
        'max_attempts' => $maxAttempts
    ]);
    
    $headers = [
        "Ocp-Apim-Subscription-Key: " . $apiKey
    ];
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        logExtraction("Polling attempt", [
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts
        ]);
        
        $ch = curl_init($operationLocation);
        if (!$ch) {
            logExtraction("Failed to initialize cURL for polling");
            throw new Exception("Failed to initialize cURL for polling operation");
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // 30 seconds connection timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout
        
        $result = curl_exec($ch);
        
        // Check for cURL errors
        if ($result === false) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);
            
            logExtraction("cURL polling request failed", [
                'error' => $curlError,
                'errno' => $curlErrno,
                'attempt' => $attempt
            ]);
            
            // If this is the last attempt, throw an exception
            if ($attempt >= $maxAttempts) {
                throw new Exception("Polling request failed after $maxAttempts attempts: $curlError");
            }
            
            // Otherwise wait and try again
            $waitTime = min($attempt, 10); // Cap wait time at 10 seconds
            sleep($waitTime);
            continue;
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for HTTP errors
        if ($httpCode < 200 || $httpCode >= 300) {
            logExtraction("HTTP error during polling", [
                'http_code' => $httpCode,
                'response' => $result,
                'attempt' => $attempt
            ]);
            
            // If this is the last attempt, throw an exception
            if ($attempt >= $maxAttempts) {
                throw new Exception("Polling operation failed with HTTP code $httpCode after $maxAttempts attempts");
            }
            
            // Otherwise wait and try again
            $waitTime = min($attempt, 10);
            sleep($waitTime);
            continue;
        }
        
        // Try to parse the JSON response
        $resultData = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logExtraction("JSON parse error during polling", [
                'error' => json_last_error_msg(),
                'response' => $result,
                'attempt' => $attempt
            ]);
            
            // If this is the last attempt, throw an exception
            if ($attempt >= $maxAttempts) {
                throw new Exception("Failed to parse JSON after $maxAttempts attempts: " . json_last_error_msg());
            }
            
            // Otherwise wait and try again
            $waitTime = min($attempt, 10);
            sleep($waitTime);
            continue;
        }
        
        // Check the status field
        if (isset($resultData['status'])) {
            logExtraction("Polling status update", [
                'status' => $resultData['status'],
                'attempt' => $attempt
            ]);
            
            if ($resultData['status'] === 'succeeded') {
                logExtraction("Polling succeeded", [
                    'attempts_needed' => $attempt,
                    'has_documents' => isset($resultData['analyzeResult']['documents']),
                    'document_count' => isset($resultData['analyzeResult']['documents']) ? count($resultData['analyzeResult']['documents']) : 0
                ]);
                return $resultData;
            } else if ($resultData['status'] === 'failed') {
                $errorMessage = isset($resultData['error']) ? json_encode($resultData['error']) : "Unknown error";
                logExtraction("Polling failed", [
                    'error' => $errorMessage,
                    'attempt' => $attempt
                ]);
                throw new Exception("Analysis failed: " . $errorMessage);
            }
            // For 'running' or 'notStarted' status, we continue polling
        } else {
            logExtraction("Unexpected response format", [
                'response' => $result,
                'attempt' => $attempt
            ]);
            
            // If this is the last attempt, throw an exception
            if ($attempt >= $maxAttempts) {
                throw new Exception("Invalid response format after $maxAttempts attempts");
            }
        }
        
        // Wait before next attempt with progressive backoff
        $waitTime = min($attempt, 10); // Cap the wait time at 10 seconds
        sleep($waitTime);
    }
    
    logExtraction("Polling timed out", [
        'max_attempts' => $maxAttempts
    ]);
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
    // Set header to return JSON for AJAX requests
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'process_ocr':
                // Process OCR and return results for preview
                processOCRPreview();
                break;
                
            case 'final_submit':
                // Handle final form submission with verified subjects
                handleFinalSubmission();
                break;
                
            default:
                throw new Exception("Invalid action specified");
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

function processOCRPreview() {
    // Start output buffering to catch any unexpected output
    ob_start();
    
    try {
        // Set proper content type for JSON response
        header('Content-Type: application/json');
        
        // Check if user selected Copy of Grades instead of TOR
        $has_copy_grades = isset($_POST['has_copy_grades']) && $_POST['has_copy_grades'];
        $academic_document_path = null;
        $document_type = 'TOR'; // Default
        
        if ($has_copy_grades) {
            // User selected Copy of Grades
            if (!isset($_FILES['copy_grades']) || $_FILES['copy_grades']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Please upload a valid Copy of Grades");
            }
            
            validateUploadedFile($_FILES['copy_grades']);
            $academic_document_path = __DIR__ . '/uploads/tor/' . basename($_FILES['copy_grades']['name']);
            move_uploaded_file($_FILES['copy_grades']['tmp_name'], $academic_document_path);
            $document_type = 'Copy of Grades';
            
            logExtraction("Processing Copy of Grades", [
                'file_name' => basename($_FILES['copy_grades']['name']),
                'file_size' => $_FILES['copy_grades']['size']
            ]);
        } else {
            // User selected TOR (default)
            if (!isset($_FILES['tor']) || $_FILES['tor']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Please upload a valid Transcript of Records (TOR)");
            }
            
            validateUploadedFile($_FILES['tor']);
            $academic_document_path = __DIR__ . '/uploads/tor/' . basename($_FILES['tor']['name']);
            move_uploaded_file($_FILES['tor']['tmp_name'], $academic_document_path);
            $document_type = 'TOR';
            
            logExtraction("Processing TOR", [
                'file_name' => basename($_FILES['tor']['name']),
                'file_size' => $_FILES['tor']['size']
            ]);
        }

        // Process school ID (always required)
        if (!isset($_FILES['school_id']) || $_FILES['school_id']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please upload a valid School ID");
        }

        validateUploadedFile($_FILES['school_id']);
        $school_id_path = __DIR__ . '/uploads/school_id/' . basename($_FILES['school_id']['name']);
        move_uploaded_file($_FILES['school_id']['tmp_name'], $school_id_path);

        // Clean any output that might have been generated so far
        ob_clean();
        
        // Perform OCR on the academic document (TOR or Copy of Grades)
        try {
            $ocr_output = performOCR($academic_document_path);
            logExtraction("OCR completed successfully", [
                'document_type' => $document_type,
                'output_length' => strlen($ocr_output)
            ]);
        } catch (Exception $ocrEx) {
            logExtraction("OCR process failed", [
                'document_type' => $document_type,
                'error' => $ocrEx->getMessage()
            ]);
            throw new Exception("OCR processing failed: " . $ocrEx->getMessage());
        }

        // Clean the buffer again before continuing
        ob_clean();

        // Clean the buffer again before subject extraction
        ob_clean();
        
        // Extract subjects from OCR output
        try {
            $subjects = extractSubjects($ocr_output);
            logExtraction("Subjects extracted", [
                'count' => count($subjects)
            ]);
        } catch (Exception $extractEx) {
            logExtraction("Subject extraction failed", [
                'error' => $extractEx->getMessage()
            ]);
            throw new Exception("Failed to extract subjects: " . $extractEx->getMessage());
        }

        if (empty($subjects)) {
            throw new Exception("No subjects could be extracted from the documents. Please ensure you have uploaded valid academic records.");
        }

        // Store only the necessary paths in session for final submission
        $_SESSION['upload_paths'] = [
            'tor_path' => $academic_document_path, // This will be either TOR or Copy of Grades
            'school_id_path' => $school_id_path,
            'document_type' => $document_type // Store which type was uploaded
        ];

        // Clean any buffered output completely before sending JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Make sure headers haven't been sent yet
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        // Return the extracted subjects for preview
        $response = [
            'success' => true,
            'subjects' => $subjects
        ];
        
        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        // Log the exception
        logExtraction("Error in processOCRPreview", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Clean any buffered output completely before sending error response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Make sure headers haven't been sent yet
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Add this before matchCreditedSubjects
function fixDatabaseConnection() {
    global $conn, $servername, $username, $password, $dbname;
    
    try {
        // Log the database configuration values to verify they're defined
        logExtraction("Database configuration", [
            'servername' => isset($servername) ? $servername : 'NOT SET',
            'username' => isset($username) ? $username : 'NOT SET',
            'dbname' => isset($dbname) ? $dbname : 'NOT SET',
            'password' => isset($password) ? '[MASKED]' : 'NOT SET'
        ]);
        
        // If any of the required database parameters are missing, re-include config
        if (!isset($servername) || !isset($username) || !isset($password) || !isset($dbname)) {
            logExtraction("Database parameters missing, re-including config.php");
            include_once('config/config.php');
            
            // Check again after including
            logExtraction("Database configuration after re-including", [
                'servername' => isset($servername) ? $servername : 'STILL NOT SET',
                'username' => isset($username) ? $username : 'STILL NOT SET',
                'dbname' => isset($dbname) ? $dbname : 'STILL NOT SET',
                'password' => isset($password) ? '[MASKED]' : 'STILL NOT SET'
            ]);
            
            // If still not set, use defaults or error
            if (!isset($servername) || !isset($username) || !isset($dbname)) {
                logExtraction("CRITICAL: Database parameters still missing after re-including config.php");
                // Set safe defaults
                $servername = $servername ?? 'localhost';
                $username = $username ?? 'root';
                $password = $password ?? '';
                $dbname = $dbname ?? 'exam';
            }
        }

        // If connection exists and is valid, return it
        if (isset($conn) && $conn && method_exists($conn, 'ping') && @$conn->ping()) {
            logExtraction("Reusing existing valid database connection");
            return $conn;
        }
        
        // Create a new connection
        logExtraction("Creating new database connection to {$servername}/{$dbname} with username {$username}");
        $newConn = new mysqli($servername, $username, $password, $dbname);
        
        if ($newConn->connect_error) {
            logExtraction("Failed to create database connection: " . $newConn->connect_error);
            return null;
        }
        
        // Set character set
        $newConn->set_charset("utf8");
        
        // Update the global connection
        $GLOBALS['conn'] = $newConn;
        
        // Test connection with a simple query
        $testResult = $newConn->query("SELECT 1");
        if (!$testResult) {
            logExtraction("Connection test failed: " . $newConn->error);
            return null;
        }
        
        logExtraction("New database connection established and tested successfully");
        return $newConn;
        
    } catch (Exception $e) {
        logExtraction("Error in fixDatabaseConnection: " . $e->getMessage());
        return null;
    }
}

// Update the handleFinalSubmission function to use our new connection fixing method
function handleFinalSubmission() {
    // Enable error reporting during debug
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Store original connection if it exists
    global $conn;
    $originalConn = $conn;
    
    // Start output buffering to catch any unexpected output
    ob_start();
    
    try {
        // Log the start of the process with POST data debugging
        logExtraction("Starting handleFinalSubmission - debugging inputs", [
            'post_data' => array_map(function($item) {
                return is_string($item) && strlen($item) > 100 ? substr($item, 0, 100) . '...' : $item;
            }, $_POST),
            'session_data' => isset($_SESSION['upload_paths']) ? $_SESSION['upload_paths'] : 'No upload paths',
            'files' => isset($_FILES) ? array_keys($_FILES) : 'No files'
        ]);
        
        // Ensure we have a valid database connection before proceeding
        $conn = fixDatabaseConnection();
        if (!$conn) {
            throw new Exception("Could not establish database connection at the beginning of handleFinalSubmission");
        }
        
        // Check if database constants are defined
        global $servername, $username, $password, $dbname;
        logExtraction("Database configuration in handleFinalSubmission", [
            'servername' => isset($servername) ? $servername : 'NOT SET',
            'username' => isset($username) ? $username : 'NOT SET',
            'dbname' => isset($dbname) ? $dbname : 'NOT SET',
            'has_connection' => isset($conn) ? 'YES' : 'NO'
        ]);
        
        // Set proper content type for JSON response
        header('Content-Type: application/json');
        
        // Check if action is correct
        if (!isset($_POST['action']) || $_POST['action'] !== 'final_submit') {
            throw new Exception("Invalid action parameter: " . ($_POST['action'] ?? 'No action specified'));
        }
        
        // Verify subjects data
        if (!isset($_POST['subjects']) || empty($_POST['subjects'])) {
            throw new Exception("No subjects provided for registration");
        }
        
        // Test JSON parsing safely
        try {
            $subjects = json_decode($_POST['subjects'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON parse error: " . json_last_error_msg());
            }
            logExtraction("Successfully parsed subjects JSON", [
                'count' => count($subjects),
                'sample' => !empty($subjects) ? $subjects[0] : 'No subjects'
            ]);
        } catch (Exception $jsonEx) {
            throw new Exception("Error decoding subjects: " . $jsonEx->getMessage());
        }
        
        // Verify database connection is still valid at this point
        if (!$conn || !@$conn->ping()) {
            logExtraction("Database connection lost after JSON parsing, reconnecting");
            $conn = fixDatabaseConnection();
            
            if (!$conn) {
                throw new Exception("Could not re-establish database connection after JSON parsing");
            }
        }
        
        // Test connection with a simple query
        $testResult = $conn->query("SELECT 1");
        if (!$testResult) {
            logExtraction("Connection test failed: " . $conn->error);
            $conn = fixDatabaseConnection();
            if (!$conn || !$conn->query("SELECT 1")) {
                throw new Exception("Could not establish a working database connection");
            }
        }
        
        logExtraction("Database connection confirmed valid");

        if (!isset($_SESSION['upload_paths'])) {
            throw new Exception("Upload session expired. Please try again.");
        }

        // Clean any output that might have been generated
        ob_clean();

        if (empty($subjects)) {
            throw new Exception("No valid subjects found in the provided data.");
        }

        // Get the paths from session
        $tor_path = $_SESSION['upload_paths']['tor_path'] ?? null;
        $school_id_path = $_SESSION['upload_paths']['school_id_path'] ?? null;
        
        if (!$tor_path || !$school_id_path) {
            throw new Exception("Required document uploads are missing.");
        }

        // Validate form data
        $required_fields = [
            'first_name', 'last_name', 'gender', 'dob', 'email', 
            'contact_number', 'street', 'student_type', 'previous_school',
            'previous_program', 'desired_program', 'grading_system'
        ];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Required field missing: " . $field);
            }
        }
        
        // Get grading system rules for the student's previous school
        try {
            $grading_system_name = $_POST['grading_system'];
            $gradingRules = getGradingSystemRules($conn, $grading_system_name);
            
            if (empty($gradingRules)) {
                throw new Exception("Could not retrieve grading system rules for: " . $grading_system_name);
            }
            
            logExtraction("Retrieved grading rules", [
                'system' => $grading_system_name,
                'rules_count' => count($gradingRules),
                'passing_grade_values' => array_map(function($rule) {
                    return $rule['grade_value'] . ' (' . $rule['description'] . '): ' . $rule['min_percentage'] . '%';
                }, array_filter($gradingRules, function($rule) {
                    return floatval($rule['min_percentage']) >= 85.0;
                }))
            ]);
        } catch (Exception $gradingEx) {
            throw new Exception("Error retrieving grading rules: " . $gradingEx->getMessage());
        }
        
        // Check if student is eligible based on grades
        try {
            logExtraction("Starting eligibility check with " . count($subjects) . " subjects");
            $isEligible = determineEligibility($subjects, $gradingRules);
            
            if (!$isEligible) {
                throw new Exception("Student does not meet the eligibility criteria based on grades. Minimum passing grade is 85% (2.0 or better).");
            }
            
            logExtraction("Student meets eligibility criteria");
        } catch (Exception $eligibilityEx) {
            throw new Exception("Eligibility check error: " . $eligibilityEx->getMessage());
        }
        
        // Check if the student is a tech student based on their subjects
        try {
            $isTech = isTechStudent($subjects);
            
            logExtraction("Tech student assessment", [
                'is_tech' => $isTech ? 'Yes' : 'No'
            ]);
        } catch (Exception $techEx) {
            // If there's an error determining tech status, default to false but log the error
            logExtraction("Error determining tech status: " . $techEx->getMessage());
            $isTech = false;
        }

        // Prepare student data for registration
        try {
            $studentData = [
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
                'year_level' => ($_POST['student_type'] === 'ladderized') ? 0 : ($_POST['year_level'] ?? 0),
                'previous_program' => $_POST['previous_program'],
                'desired_program' => $_POST['desired_program'],
                'tor_path' => str_replace(__DIR__ . '/', '', $tor_path),
                'school_id_path' => str_replace(__DIR__ . '/', '', $school_id_path),
                'is_tech' => $isTech,
                'status' => 'pending'
            ];

            logExtraction("Prepared student data for registration", [
                'name' => $studentData['first_name'] . ' ' . $studentData['last_name'],
                'email' => $studentData['email'],
                'subject_count' => count($subjects),
                'student_type' => $studentData['student_type'],
                'is_tech' => $studentData['is_tech'] ? 'Yes' : 'No'
            ]);
        } catch (Exception $dataEx) {
            throw new Exception("Error preparing student data: " . $dataEx->getMessage());
        }

        // Begin transaction for the registration process
        try {
            // Make sure we're using a valid connection
            if (!$conn || !$conn->ping()) {
                $conn = fixDatabaseConnection();
                if (!$conn) {
                    throw new Exception("Database connection lost before starting transaction");
                }
            }
            
            $conn->begin_transaction();
            logExtraction("Database transaction started");
        } catch (Exception $txEx) {
            throw new Exception("Error starting database transaction: " . $txEx->getMessage());
        }
        
        try {
            // Register student in the database
            logExtraction("Calling registerStudent function");
            $registrationResult = registerStudent($conn, $studentData, $subjects);
            
            if (!$registrationResult) {
                throw new Exception("Failed to register student in the database.");
            }
            
            // Get the student_id from the registration result
            $student_id = $_SESSION['student_id'] ?? null;
            $reference_id = $_SESSION['reference_id'] ?? null;
            
            if (!$student_id || !$reference_id) {
                throw new Exception("Failed to generate student ID or reference ID.");
            }
            
            logExtraction("Student registered successfully", [
                'student_id' => $student_id,
                'reference_id' => $reference_id
            ]);
            
            // Match credited subjects from the uploaded documents
            logExtraction("Calling matchCreditedSubjects function");
            $matchedCount = matchCreditedSubjects($conn, $subjects, $student_id);
            
            logExtraction("Matched credited subjects", [
                'count' => $matchedCount
            ]);
            
            // Commit the transaction if everything is successful
            $conn->commit();
            logExtraction("Database transaction committed successfully");
            
            // Store data for the success page
            $_SESSION['last_registration'] = true;
            $_SESSION['student_name'] = $studentData['first_name'] . ' ' . $studentData['last_name'];
            $_SESSION['email'] = $studentData['email'];
            
            // Clear the session upload paths after successful registration
            unset($_SESSION['upload_paths']);
            
            // Clean all output buffers before sending response
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Ensure headers haven't been sent
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Registration completed successfully',
                'reference_id' => $reference_id
            ]);
            exit;
            
        } catch (Exception $txnEx) {
            // Rollback on any error
            try {
                if ($conn && $conn->ping()) {
                    $conn->rollback();
                    logExtraction("Transaction rolled back due to error");
                }
            } catch (Exception $rollbackEx) {
                logExtraction("Error during rollback: " . $rollbackEx->getMessage());
            }
            
            throw new Exception("Registration failed: " . $txnEx->getMessage());
        }

    } catch (Exception $e) {
        // Log the error with detailed information
        logExtraction("Error in handleFinalSubmission", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'post_data' => isset($_POST) ? array_keys($_POST) : 'No POST data',
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        // Rollback transaction if active
        try {
            if (isset($conn) && $conn && $conn->ping()) {
                // Check if a transaction is active
                $result = $conn->query("SELECT @@in_transaction");
                if ($result && $row = $result->fetch_row() && $row[0] == 1) {
                    $conn->rollback();
                    logExtraction("Transaction rolled back in error handler");
                }
            }
        } catch (Exception $rollbackEx) {
            logExtraction("Error during error-handler rollback: " . $rollbackEx->getMessage());
        }
        
        // Record error in session for debugging
        $_SESSION['last_error'] = $e->getMessage();
        $_SESSION['error_trace'] = $e->getTraceAsString();
        
        // Clean all output buffers before sending error response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Make sure headers haven't been sent
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        // Return detailed error message for debugging
        echo json_encode([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => DEBUG_MODE ? explode("\n", $e->getTraceAsString()) : 'Trace hidden in production'
        ]);
        exit;
    }
}

// If we reach here, something went wrong
$_SESSION['ocr_error'] = "An unexpected error occurred. Please try again.";
header("Location: registration_success.php");
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

// Add this helper function at the end of the file
function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

// Main request router
try {
    // Create upload directories if they don't exist
    $uploadDirs = [
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/tor',
        __DIR__ . '/uploads/school_id'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    
    // Handle different actions based on the POST parameter
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['action'])) {
            throw new Exception("No action specified");
        }
        
        switch ($_POST['action']) {
            case 'process_ocr':
                // Process the OCR and return the preview
                processOCRPreview();
                break;
                
            case 'final_submit':
                // Handle the final form submission
                handleFinalSubmission();
                break;
                
            default:
                throw new Exception("Invalid action specified");
        }
    } else {
        // If not a POST request, redirect back to the form
        header("Location: qualiexam_register.php");
        exit();
    }
} catch (Exception $e) {
    // Log the error
    logExtraction("Error in main request router", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Clean any output buffers before sending error response
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set appropriate header if not already sent
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    // Return JSON error response
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}

// Add this at the top of the file, after session_start();
// Define a flag to control connection closure
define('KEEP_CONNECTION_OPEN', true);

// Add this new function before handleFinalSubmission
function ensureConnectionOpen($conn) {
    // This function ensures we have a valid database connection
    // Delegate to fixDatabaseConnection which does the actual work
    return fixDatabaseConnection();
}
?>