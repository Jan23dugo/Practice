<?php
// Quick response for ping request
if (isset($_GET['ping'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'time' => time()]);
    exit;
}

// Disable all error reporting and warnings for JSON responses
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log'); // Log to file instead of output

// Handle field name mappings for backward compatibility
if (isset($_POST['address']) && !isset($_POST['street'])) {
    $_POST['street'] = $_POST['address'];
}

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
                'Grades' => str_replace(',', '.', $grade_match[0]),
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
                    'Grades' => str_replace(',', '.', $numeric_values[0])  // Changed from [1]
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
    $grade = floatval(str_replace(',', '.', $subject['Grades']));
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
                    'Grades' => floatval($subject['grade'] ?? $subject['Grades'] ?? 0)
                ];
            }
            return $subjects;
        }
        
        // Check for Azure Document Intelligence structure
        if (isset($data['analyzeResult']) && isset($data['analyzeResult']['documents']) 
            && !empty($data['analyzeResult']['documents'])) {
            
            logExtraction("Found Azure Document Intelligence structure");
            $document = $data['analyzeResult']['documents'][0];
            
            if (isset($document['fields']) && isset($document['fields']['TorTable']) 
                && isset($document['fields']['TorTable']['valueArray'])) {
                
                logExtraction("Found table data in Azure results");
                $rows = $document['fields']['TorTable']['valueArray'];
                
                // Skip the first row if it's a header
                for ($i = 1; $i < count($rows); $i++) {
                    if (isset($rows[$i]['valueObject'])) {
                        $rowData = $rows[$i]['valueObject'];
                        
                        // Map the column names to our fields
                        $subject_code = isset($rowData['Subject Code']['valueString']) ? trim($rowData['Subject Code']['valueString']) : '';
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
                        if (!empty($subject_code) && !empty($grade)) {
                            $subjects[] = [
                                'subject_code' => $subject_code,
                                'subject_description' => $subject_description,
                                'units' => $units,
                                'Grades' => $grade
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
    
    // Try multiple patterns for different transcript formats
    foreach ($lines as $line) {
        if (empty($line)) continue;
        
        // Skip header rows and footers
        if (preg_match('/(SUBJECT|COURSE|CODE|TITLE|TERM|SEMESTER|NOTHING FOLLOWS)/i', $line)) {
            continue;
        }
        
        // Pattern 1: Standard format with subject code, description, units, grade
        if (preg_match('/([A-Z0-9]+[-]?[A-Z0-9]*)\s*[-]?\s*([^0-9]+?)\s+([\d.]+)\s+([\d.]+)/i', $line, $matches)) {
            $subjects[] = [
                'subject_code' => trim($matches[1]),
                'subject_description' => trim($matches[2]),
                'units' => floatval($matches[3]),
                'Grades' => floatval($matches[4])
            ];
            continue;
        }
        
        // Pattern 2: Format with subject code at start and numbers at end
        if (preg_match('/^([A-Z]{2,4}\s*\d{3,4}[A-Z]?)\s+(.+?)\s+([\d.,]+)\s+([\d.,]+)$/i', $line, $matches)) {
            $subjects[] = [
                'subject_code' => trim($matches[1]),
                'subject_description' => trim($matches[2]),
                'units' => floatval(str_replace(',', '.', $matches[3])),
                'Grades' => floatval(str_replace(',', '.', $matches[4]))
            ];
            continue;
        }
        
        // Pattern 3: Look for typical PUP/TUP transcript format
        if (preg_match('/([A-Z]{2,4})\s*(\d{3,4}[A-Z]?)\s+(.+?)\s+([\d.,]+)\s+([\d.,]+)$/i', $line, $matches)) {
            $subjects[] = [
                'subject_code' => trim($matches[1] . ' ' . $matches[2]),
                'subject_description' => trim($matches[3]),
                'units' => floatval(str_replace(',', '.', $matches[4])),
                'Grades' => floatval(str_replace(',', '.', $matches[5]))
            ];
        }
    }
    
    // If we found subjects, log and return them
    if (!empty($subjects)) {
        logExtraction("Successfully extracted subjects using fallback text parsing", ['count' => count($subjects)]);
        return $subjects;
    }
    
    // If still no subjects, try the generic and position-based methods
    $genericSubjects = extractSubjectsGeneric($text);
    if (!empty($genericSubjects)) {
        logExtraction("Extracted subjects using generic method", ['count' => count($genericSubjects)]);
        return $genericSubjects;
    }
    
    $positionSubjects = extractSubjectsByPosition($text);
    if (!empty($positionSubjects)) {
        logExtraction("Extracted subjects using position-based method", ['count' => count($positionSubjects)]);
        return $positionSubjects;
    }
    
    logExtraction("Extraction complete", ['subjects_found' => count($subjects)]);
    return $subjects;
}

function standardizeText($text) {
    if (empty($text)) {
        return '';
    }
    
    // Convert to lowercase
    $text = strtolower($text);
    
    // Replace common academic abbreviations and domain-specific terms
    $replacements = [
        // General academic
        'intro' => 'introduction',
        'prog' => 'programming',
        'comp' => 'computer',
        'sci' => 'science',
        'tech' => 'technology',
        'info' => 'information',
        'sys' => 'systems',
        'comm' => 'communication',
        'mgmt' => 'management',
        'dev' => 'development',
        'anal' => 'analysis',
        'struct' => 'structure',
        'org' => 'organization',
        
        // Mathematics
        'math' => 'mathematics',
        'stat' => 'statistics',
        'calc' => 'calculus',
        'alg' => 'algebra',
        'trig' => 'trigonometry',
        
        // Hospitality and tourism
        'hosp' => 'hospitality',
        'tourism' => 'tourism',
        'hrm' => 'hotel restaurant management',
        'food bev' => 'food and beverage',
        'bev' => 'beverage',
        'culinary' => 'culinary',
        'cuisine' => 'cuisine',
        'kitchen' => 'kitchen',
        'gastronomy' => 'gastronomy',
        'restaurant' => 'restaurant',
        'hotel' => 'hotel',
        'serv' => 'service',
        'macro' => 'macro',
        'micro' => 'micro',
        'perspective' => 'perspective',
        
        // General education
        'gec' => 'general education',
        'gee' => 'general education',
        'geed' => 'general education',
        'nstp' => 'national service training program',
        'cwts' => 'civic welfare training service',
        'pe' => 'physical education',
        'pathfit' => 'physical activities towards health and fitness',
        'filipino' => 'filipino',
        'fil' => 'filipino',
        'hist' => 'history',
        'contemp' => 'contemporary',
        'ethics' => 'ethics',
        'soc' => 'society',
        'society' => 'society',
        'develop' => 'development',
        'prof' => 'professional',
        'professional' => 'professional',
        'applied' => 'applied',
        'laboratory' => 'laboratory',
        'lab' => 'laboratory',
        'lec' => 'lecture',
        'lecture' => 'lecture'
    ];
    
    foreach ($replacements as $abbr => $full) {
        // Only replace if it's a standalone word with boundaries
        $text = preg_replace('/\b' . preg_quote($abbr, '/') . '\b/', $full, $text);
    }
    
    // Remove special characters except spaces and letters
    $text = preg_replace('/[^a-z\s]/', '', $text);
    
    // Remove common filler words that don't affect meaning
    $fillers = ['and', 'the', 'to', 'for', 'of', 'in', 'on', 'with', 'a', 'an', 'as', 'by', 'at', '&'];
    $text = ' ' . $text . ' '; // Add spaces to ensure word boundaries
    foreach ($fillers as $filler) {
        $text = str_replace(' ' . $filler . ' ', ' ', $text);
    }
    
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

// Add this function after standardizeText
function testDescriptionSimilarity($desc1, $desc2) {
    $normalized1 = standardizeText($desc1);
    $normalized2 = standardizeText($desc2);
    
    similar_text($normalized1, $normalized2, $similarity);
    
    return [
        'original1' => $desc1,
        'original2' => $desc2,
        'normalized1' => $normalized1,
        'normalized2' => $normalized2,
        'similarity' => $similarity
    ];
}

// Add this function after testDescriptionSimilarity
function extractKeywords($text) {
    // Convert to lowercase
    $text = strtolower($text);
    
    // Remove punctuation
    $text = preg_replace('/[^\w\s]/', ' ', $text);
    
    // Remove common stopwords and articles
    $stopwords = ['a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'this', 'that', 'of', 'to', 'for', 'in', 'on', 'with', 
                 'by', 'at', 'from', 'as', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
                 'will', 'would', 'should', 'can', 'could', 'may', 'might', 'must', 'shall'];
    
    foreach ($stopwords as $word) {
        $text = preg_replace('/\b' . $word . '\b/', ' ', $text);
    }
    
    // Extract words
    preg_match_all('/\b\w{3,}\b/', $text, $matches);
    
    return array_unique($matches[0]);
}

function keywordOverlap($desc1, $desc2) {
    // Get keywords from both descriptions
    $keywords1 = extractKeywords($desc1);
    $keywords2 = extractKeywords($desc2);
    
    // Debug log the keywords
    logExtraction("Keywords for comparison", [
        'desc1' => $desc1,
        'keywords1' => $keywords1,
        'desc2' => $desc2,
        'keywords2' => $keywords2
    ]);
    
    if (empty($keywords1) || empty($keywords2)) {
        return 0;
    }
    
    // Check for exact matches in the main words (nouns, etc.)
    $matches = 0;
    $totalKeywords = count($keywords1) + count($keywords2);
    
    // Count matches
    foreach ($keywords1 as $keyword) {
        foreach ($keywords2 as $k2) {
            // Perfect match
            if ($keyword === $k2) {
                $matches += 2;
            }
            // Partial match (one is substring of other)
            else if (strlen($keyword) >= 4 && strlen($k2) >= 4) {
                if (strpos($keyword, $k2) !== false || strpos($k2, $keyword) !== false) {
                    $matches += 1;
                }
            }
        }
    }
    
    // Normalize score between 0 and 100
    $score = ($totalKeywords > 0) ? min(100, ($matches * 100) / $totalKeywords) : 0;
    
    logExtraction("Keyword overlap score", [
        'matches' => $matches,
        'total_keywords' => $totalKeywords,
        'score' => $score
    ]);
    
    return $score;
}

// Add this after the keywordOverlap function
function standardizeSubjectCode($code) {
    if (empty($code)) {
        return '';
    }
    
    // Convert to uppercase
    $code = strtoupper(trim($code));
    
    // Remove common separators and standardize format
    $code = preg_replace('/[\s\-\.\/]+/', ' ', $code);
    
    // Remove any non-alphanumeric characters
    $code = preg_replace('/[^A-Z0-9\s]/', '', $code);
    
    // Special case for common OCR errors
    $code = str_replace(['O', 'o'], '0', $code);
    $code = str_replace(['l', 'I'], '1', $code);
    
    return trim($code);
}

// Keep all existing functions but update database operations in these three main functions
function matchCreditedSubjects($conn, $subjects, $student_id) {
    $conn = ensureValidConnection($conn);
    define('DEBUG_MODE', true); // Set to true for detailed logging
    
    try {
        // Get the student's desired program
        $stmt = $conn->prepare("SELECT desired_program FROM register_studentsqe WHERE student_id = ?");
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
        
        if (DEBUG_MODE) {
            logExtraction("MATCHING START: Student and Program", [
                'student_id' => $student_id,
                'desired_program' => $desired_program,
                'subjects_from_ocr' => count($subjects)
            ]);
        }

        // Get ONLY the subjects for the student's chosen program
        $sql = "SELECT * FROM coded_courses WHERE program = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for fetching program subjects: " . $conn->error);
        }
        
        $stmt->bind_param("s", $desired_program);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            logExtraction("ERROR: No subjects found for program", [
                'program' => $desired_program
            ]);
            return 0;
        }
        
        $program_subjects = [];
        while($row = $result->fetch_assoc()) {
            $program_subjects[] = $row;
        }
        $stmt->close();
        
        if (DEBUG_MODE) {
            $coursesList = [];
            foreach ($program_subjects as $subject) {
                $coursesList[] = [
                    'code' => $subject['subject_code'],
                    'description' => $subject['subject_description'],
                    'units' => $subject['units']
                ];
            }
            
            logExtraction("AVAILABLE COURSES IN PROGRAM", [
                'program' => $desired_program,
                'course_count' => count($program_subjects),
                'courses' => $coursesList
            ]);
            
            // Log all OCR subjects for debugging
            $ocrSubjects = [];
            foreach ($subjects as $subject) {
                if (isset($subject['subject_description']) && isset($subject['units'])) {
                    $ocrSubjects[] = [
                        'code' => $subject['subject_code'] ?? 'N/A',
                        'description' => $subject['subject_description'],
                        'units' => $subject['units'],
                        'grade' => $subject['grade'] ?? 'N/A'
                    ];
                }
            }
            
            logExtraction("OCR SUBJECTS TO MATCH", [
                'count' => count($ocrSubjects),
                'subjects' => $ocrSubjects
            ]);
        }

        // Initialize counters and tracking arrays
        $insertCount = 0;
        $matchInfo = [];
        $unmatchedSubjects = [];
        
        // Process each subject from OCR results
        foreach ($subjects as $tor_subject) {
            // Only require description and units, skip grade check if it's N/A
            if (empty($tor_subject['subject_description']) || empty($tor_subject['units'])) {
                continue;
            }
            
            $tor_description = trim($tor_subject['subject_description']);
            $tor_units = floatval($tor_subject['units']);
            $tor_code = trim($tor_subject['subject_code'] ?? '');
            $tor_grade = trim($tor_subject['grade'] ?? 'N/A');
            
            // Fix common unit formatting issues
            if ($tor_units == 1 || $tor_units == 2 || $tor_units == 3) {
                // Convert integer units to float format (e.g., 3 to 3.0)
                $tor_units_formatted = number_format($tor_units, 2);
            } else {
                $tor_units_formatted = $tor_units;
            }
            
            // Standardize the description for matching
            $normalized_tor_desc = standardizeText($tor_description);
            
            if (DEBUG_MODE) {
                logExtraction("PROCESSING SUBJECT", [
                    'original_code' => $tor_code,
                    'description' => $tor_description,
                    'normalized_desc' => $normalized_tor_desc,
                    'units' => $tor_units,
                    'grade' => $tor_grade
                ]);
            }

            // Special case for specific known subjects
            $specialCases = [
                "Mathematics in the Modern World" => "Mathematics in the Modern World",
                "Math in the Modern World" => "Mathematics in the Modern World",
                "Readings in Philippine History" => "Readings in Philippine History",
                "National Service Training Program" => "Civic Welfare Training Service",
                "NSTP" => "Civic Welfare Training Service",
                "Understanding the Self" => "Understanding the Self",
                "The Contemporary World" => "The Contemporary World",
                "Science, Technology and Society" => "Science, Technology and Society",
                "Ethics" => "Ethics",
                "Purposive Communication" => "Purposive Communication",
                "Filipino" => "Filipinolohiya",
                "Physical" => "Physical Fitness",
                "Physical Education" => "Physical Fitness",
                "PE" => "Physical Fitness",
                "Communication" => "Purposive Communication"
            ];
            
            // Check for special case matches first
            $specialCaseMatch = null;
            foreach ($specialCases as $pattern => $targetDesc) {
                if (stripos($tor_description, $pattern) !== false) {
                    // Find the corresponding program subject
                    foreach ($program_subjects as $program_subject) {
                        if (stripos($program_subject['subject_description'], $targetDesc) !== false &&
                            (abs(floatval($program_subject['units']) - $tor_units) < 0.5 || 
                             strval($program_subject['units']) == strval($tor_units_formatted))) { // Allow slight unit difference
                            $specialCaseMatch = $program_subject;
                            
                            logExtraction("SPECIAL CASE MATCH", [
                                'tor_description' => $tor_description,
                                'pattern' => $pattern,
                                'target' => $targetDesc,
                                'matched_subject' => $program_subject['subject_description'],
                                'matched_code' => $program_subject['subject_code']
                            ]);
                            
                            break 2;
                        }
                    }
                }
            }
            
            // If we found a special case match, use it directly
            if ($specialCaseMatch) {
                $bestMatch = $specialCaseMatch;
                $highestSimilarity = 100; // Give it a perfect score
                $highestKeywordMatch = 100;
            } else {
                // Try matching by course code prefix if available
                $codeMatch = null;
                if (!empty($tor_code)) {
                    $codeMatch = matchSubjectByCode($tor_code, $program_subjects);
                    if ($codeMatch) {
                        logExtraction("CODE PREFIX MATCH", [
                            'tor_code' => $tor_code,
                            'tor_prefix' => getSubjectPrefix($tor_code),
                            'program_code' => $codeMatch['subject_code'],
                            'program_prefix' => getSubjectPrefix($codeMatch['subject_code']),
                            'program_description' => $codeMatch['subject_description']
                        ]);
                    }
                }
                
                // If we found a course code match, use it (if units are compatible)
                if ($codeMatch && (abs(floatval($codeMatch['units']) - $tor_units) < 1.0 || 
                                  strval($codeMatch['units']) == strval($tor_units_formatted))) {
                    $bestMatch = $codeMatch;
                    $highestSimilarity = 90; // High score but not perfect
                    $highestKeywordMatch = 90;
                } else {
                    // Standard matching logic
                    $bestMatch = null;
                    $highestSimilarity = 0;
                    $highestKeywordMatch = 0;
                    
                    foreach ($program_subjects as $program_subject) {
                        // Units should be close but not necessarily exact
                        if (abs($program_subject['units'] - $tor_units) >= 1.0) {
                            if (DEBUG_MODE) {
                                logExtraction("SKIPPING - Units too different", [
                                    'program_subject' => $program_subject['subject_description'],
                                    'program_units' => $program_subject['units'],
                                    'tor_units' => $tor_units
                                ]);
                            }
                            continue;
                        }
                        
                        // Standardize program description
                        $program_desc = standardizeText($program_subject['subject_description']);
                        
                        // Skip if descriptions are too different in length
                        $lengthRatio = strlen($program_desc) / max(1, strlen($normalized_tor_desc));
                        if ($lengthRatio < 0.4 || $lengthRatio > 2.5) {
                            if (DEBUG_MODE) {
                                logExtraction("SKIPPING - Description length too different", [
                                    'program_subject' => $program_subject['subject_description'],
                                    'program_desc_length' => strlen($program_desc),
                                    'tor_desc_length' => strlen($normalized_tor_desc),
                                    'ratio' => $lengthRatio
                                ]);
                            }
                            continue;
                        }
                        
                        // Calculate similarity metrics
                        similar_text(strtolower($program_desc), strtolower($normalized_tor_desc), $similarity);
                        $keywordMatch = keywordOverlap($program_subject['subject_description'], $tor_description);
                        $combinedScore = ($similarity * 0.7) + ($keywordMatch * 0.3);
                        
                        if (DEBUG_MODE) {
                            logExtraction("COMPARING", [
                                'program_subject' => $program_subject['subject_description'],
                                'program_code' => $program_subject['subject_code'],
                                'tor_description' => $tor_description,
                                'similarity' => $similarity,
                                'keyword_match' => $keywordMatch,
                                'combined_score' => $combinedScore
                            ]);
                        }

                        // Check if this is a better match than previous ones
                        $currentBestScore = ($highestSimilarity * 0.7) + ($highestKeywordMatch * 0.3);
                        // Lower similarity threshold to 60
                        if ($combinedScore > $currentBestScore && $similarity >= 60) {
                            $highestSimilarity = $similarity;
                            $highestKeywordMatch = $keywordMatch;
                            $bestMatch = $program_subject;
                            
                            if (DEBUG_MODE) {
                                logExtraction("NEW BEST MATCH FOUND", [
                                    'program_subject' => $program_subject['subject_description'],
                                    'program_code' => $program_subject['subject_code'],
                                    'similarity' => $similarity,
                                    'keyword_match' => $keywordMatch
                                ]);
                            }
                        }
                    }
                }
            }

            // If a valid match was found in the program
            if ($bestMatch) {
                // Ensure db connection is valid
                $conn = ensureValidConnection($conn);
                
                // Check for existing match to avoid duplicates
                $checkSql = "SELECT COUNT(*) as count FROM matched_courses 
                            WHERE student_id = ? AND subject_code = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("is", $student_id, $bestMatch['subject_code']);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
                
                if ($checkResult['count'] > 0) {
                    logExtraction("SKIPPED - Match already exists", [
                        'subject_code' => $bestMatch['subject_code'],
                        'subject_description' => $bestMatch['subject_description'],
                        'tor_description' => $tor_description
                    ]);
                    continue;
                }
                
                // Handle N/A grades by replacing with a placeholder value
                if ($tor_grade == 'N/A' || empty($tor_grade)) {
                    $tor_grade = 'PENDING';
                }
                
                // Insert the matched course
                $insert_sql = "INSERT INTO matched_courses 
                             (subject_code, original_code, subject_description, units, student_id, grade, matched_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt) {
                    // Important: subject_code is from the coded_courses table, original_code is from OCR
                    $insert_stmt->bind_param(
                        "sssdis",
                        $bestMatch['subject_code'],    // From coded_courses
                        $tor_code,                     // From OCR
                        $bestMatch['subject_description'], // From coded_courses
                        $bestMatch['units'],           // From coded_courses
                        $student_id,
                        $tor_grade                     // From OCR
                    );
                    
                    if ($insert_stmt->execute()) {
                        $insertCount++;
                        
                        logExtraction("MATCHED SUCCESSFULLY", [
                            'program_code' => $bestMatch['subject_code'],
                            'program_description' => $bestMatch['subject_description'],
                            'original_code' => $tor_code,
                            'original_description' => $tor_description,
                            'similarity' => $highestSimilarity,
                            'keyword_match' => $highestKeywordMatch
                        ]);
                        
                        $matchInfo[] = "✓ Matched: {$tor_description} ({$tor_units} units) with {$bestMatch['subject_description']} ({$bestMatch['subject_code']})";
                    } else {
                        logExtraction("DATABASE ERROR - Insert failed", [
                            'error' => $insert_stmt->error,
                            'subject_code' => $bestMatch['subject_code'],
                            'original_code' => $tor_code
                        ]);
                    }
                    $insert_stmt->close();
                }
            } else {
                // Track unmatched subjects
                $unmatchedSubjects[] = [
                    'code' => $tor_code,
                    'description' => $tor_description,
                    'units' => $tor_units,
                    'grade' => $tor_grade
                ];
                
                logExtraction("NO MATCH FOUND", [
                    'original_code' => $tor_code,
                    'description' => $tor_description,
                    'units' => $tor_units,
                    'program' => $desired_program
                ]);
                
                $matchInfo[] = "✗ No match found for: {$tor_description} ({$tor_units} units)";
            }
        }
        
        // Log summary of matching results
        logExtraction("MATCHING SUMMARY", [
            'program' => $desired_program,
            'total_subjects_from_ocr' => count($subjects),
            'matched_subjects' => $insertCount,
            'unmatched_subjects' => count($unmatchedSubjects),
            'unmatched_details' => $unmatchedSubjects
        ]);
        
        // Store match info in session for potential display
        $_SESSION['matches'] = $matchInfo;
        $_SESSION['matched_count'] = $insertCount;
        $_SESSION['unmatched_count'] = count($unmatchedSubjects);
        
        return $insertCount;
    } catch (Exception $e) {
        logExtraction("ERROR in matchCreditedSubjects", [
            'message' => $e->getMessage(),
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

// Update getGradingSystemRules to use university_code
function getGradingSystemRules($conn, $universityCodeOrName) {
    try {
        $conn = getNewConnection();
        logExtraction("Getting grading system rules for university", [
            'input' => $universityCodeOrName
        ]);

        // First, try to match by university_code
        $query = "SELECT id, university_code, university_name, min_percentage, max_percentage, grade_value, description, is_special_grade 
                 FROM university_grading_systems 
                 WHERE university_code = ? 
                 ORDER BY min_percentage DESC";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("s", $universityCodeOrName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $stmt->close();
            $statementClosed = true;
            // Fallback: try by university_name (case-insensitive)
            $conn = getNewConnection();
            $query = "SELECT id, university_code, university_name, min_percentage, max_percentage, grade_value, description, is_special_grade 
                     FROM university_grading_systems 
                     WHERE LOWER(university_name) = LOWER(?) 
                     ORDER BY min_percentage DESC";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database error on fallback name search: " . $conn->error);
            }
            $stmt->bind_param("s", $universityCodeOrName);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                // Fallback: LIKE query for partial matches
                $stmt->close();
                $statementClosed = true;
                $conn = getNewConnection();
                $likeTerm = "%$universityCodeOrName%";
                $query = "SELECT id, university_code, university_name, min_percentage, max_percentage, grade_value, description, is_special_grade 
                         FROM university_grading_systems 
                         WHERE university_name LIKE ? 
                         ORDER BY min_percentage DESC";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Database error on fuzzy search: " . $conn->error);
                }
                $stmt->bind_param("s", $likeTerm);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows == 0) {
                    logExtraction("No grading system found for university, marking for manual review", [
                        'input' => $universityCodeOrName
                    ]);
                    $stmt->close();
                    return [];
                }
            } else {
                $statementClosed = false;
            }
        } else {
            $statementClosed = false;
        }

        $gradingRules = [];
        while ($row = $result->fetch_assoc()) {
            $gradingRules[] = $row;
        }
        if (!isset($statementClosed) || !$statementClosed) {
            $stmt->close();
        }
        closeConnection($conn);
        logExtraction("Retrieved grading rules for university", [
            'input' => $universityCodeOrName,
            'rules_count' => count($gradingRules),
            'sample_rule' => count($gradingRules) > 0 ? $gradingRules[0] : null
        ]);
        return $gradingRules;
    } catch (Exception $e) {
        logExtraction("Error in getGradingSystemRules", [
            'error' => $e->getMessage()
        ]);
        if (isset($stmt) && (!isset($statementClosed) || !$statementClosed)) {
            try {
                $stmt->close();
            } catch (Exception $closeEx) {
                logExtraction("Warning: Could not close statement", [
                    'error' => $closeEx->getMessage()
                ]);
            }
        }
        if (isset($conn)) {
            closeConnection($conn);
        }
        return [];
    }
}

function registerStudent($conn, $studentData, $subjects) {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
        
=======
    // Ensure stud_id is set
    if (empty($studentData['stud_id'])) {
        throw new Exception('Student ID not found in session. Please log in again.');
    }
    try {
        // Get columns from the table
        $result = $conn->query("SHOW COLUMNS FROM register_studentsqe");
>>>>>>> Stashed changes
=======
    // Ensure stud_id is set
    if (empty($studentData['stud_id'])) {
        throw new Exception('Student ID not found in session. Please log in again.');
    }
    try {
        // Get columns from the table
        $result = $conn->query("SHOW COLUMNS FROM register_studentsqe");
>>>>>>> Stashed changes
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        logExtraction("Table columns", ['columns' => $columns]);
        
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        // Required columns for our insert
        $requiredColumns = [
            'last_name', 'first_name', 'middle_name', 'gender', 'dob', 'email', 
            'contact_number', 'street', 'student_type', 'previous_school', 
            'year_level', 'previous_program', 'desired_program', 'tor', 
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            'school_id', 'reference_id', 'is_tech', 'status', 'stud_id'
=======
            'school_id', 'is_tech', 'status', 'stud_id'
>>>>>>> Stashed changes
=======
            'school_id', 'is_tech', 'status', 'stud_id'
>>>>>>> Stashed changes
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        if (!empty($missingColumns)) {
            throw new Exception("Missing required columns in table: " . implode(', ', $missingColumns));
        }
        
        // Start transaction
        $conn->begin_transaction();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
    
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
        
        // Add notes about why manual review is needed if applicable
        $adminNotes = '';
        if ($studentData['status'] === 'needs_review') {
            $adminNotes = "Manual review required: ";
            
            // Check if grading rules were missing
            if (isset($GLOBALS['needsManualReview']) && $GLOBALS['needsManualReview']) {
                $adminNotes .= "No grading system rules found for university '{$studentData['previous_school']}'. ";
            }
            
            // Add notes about any other issues
            if (isset($GLOBALS['manualReviewReasons']) && !empty($GLOBALS['manualReviewReasons'])) {
                $adminNotes .= implode(". ", $GLOBALS['manualReviewReasons']);
            }
        }
        
        // Log the SQL INSERT statement preparation
        logExtraction("Preparing SQL INSERT statement");
        
        // Check if admin_notes column exists
        $hasAdminNotes = in_array('admin_notes', $columns);
        
        // Insert student data with simple SQL statement - match the exact columns in the database
        if ($hasAdminNotes) {
            $sql = "INSERT INTO register_studentsqe (
                last_name, first_name, middle_name, gender, dob, email, contact_number, street, 
                student_type, previous_school, year_level, previous_program, desired_program, 
                tor, school_id, reference_id, is_tech, status, stud_id, admin_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error preparing statement: " . $conn->error . " SQL: $sql");
            }

            $stmt->bind_param(
                "ssssssssssssssssiiis", 
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
                $studentData['status'],
                $_SESSION['stud_id'],
                $adminNotes
            );
        } else {
            // If admin_notes column doesn't exist, use the original query without it
            $sql = "INSERT INTO register_studentsqe (
                last_name, first_name, middle_name, gender, dob, email, contact_number, street, 
                student_type, previous_school, year_level, previous_program, desired_program, 
                tor, school_id, reference_id, is_tech, status, stud_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error preparing statement: " . $conn->error . " SQL: $sql");
            }

            $stmt->bind_param(
                "sssssssssssssssssii", 
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
                $studentData['status'],
                $_SESSION['stud_id']
            );
        }

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
=======
        
        // Set default status as pending
       
        
        // Add notes about why manual review is needed if applicable
        $adminNotes = '';
        if ($studentData['status'] === 'needs_review') {
            $adminNotes = "Manual review required: ";
            $reasonMsg = "No grading system rules found for university '{$studentData['previous_school']}'.";
            $reasons = isset($GLOBALS['manualReviewReasons']) ? $GLOBALS['manualReviewReasons'] : [];
            // Normalize all reasons for comparison
            $normalizedReasons = array_map(function($r) {
                return strtolower(trim($r, ". "));
            }, $reasons);
            $normalizedReasonMsg = strtolower(trim($reasonMsg, ". "));
            $includeReasonMsg = false;
            if (isset($GLOBALS['needsManualReview']) && $GLOBALS['needsManualReview'] && !in_array($normalizedReasonMsg, $normalizedReasons, true)) {
                $includeReasonMsg = true;
            }
            // Remove duplicates from reasons
            $uniqueReasons = [];
            foreach ($reasons as $r) {
                $norm = strtolower(trim($r, ". "));
                if ($norm !== $normalizedReasonMsg && !in_array($norm, $uniqueReasons, true)) {
                    $uniqueReasons[] = $norm;
                }
            }
            $notesParts = [];
            if ($includeReasonMsg) {
                $notesParts[] = $reasonMsg;
            }
            if (!empty($uniqueReasons)) {
                $notesParts[] = implode(". ", array_map('ucfirst', $uniqueReasons)) . '.';
            }
            // If nothing is left, always show the main reasonMsg
            if (empty($notesParts)) {
                $notesParts[] = $reasonMsg;
            }
            $adminNotes .= implode(' ', $notesParts);
        }

        // Check if admin_notes column exists
        $hasAdminNotes = in_array('admin_notes', $columns);
>>>>>>> Stashed changes

=======
        
        // Set default status as pending
       
        
        // Add notes about why manual review is needed if applicable
        $adminNotes = '';
        if ($studentData['status'] === 'needs_review') {
            $adminNotes = "Manual review required: ";
            $reasonMsg = "No grading system rules found for university '{$studentData['previous_school']}'.";
            $reasons = isset($GLOBALS['manualReviewReasons']) ? $GLOBALS['manualReviewReasons'] : [];
            // Normalize all reasons for comparison
            $normalizedReasons = array_map(function($r) {
                return strtolower(trim($r, ". "));
            }, $reasons);
            $normalizedReasonMsg = strtolower(trim($reasonMsg, ". "));
            $includeReasonMsg = false;
            if (isset($GLOBALS['needsManualReview']) && $GLOBALS['needsManualReview'] && !in_array($normalizedReasonMsg, $normalizedReasons, true)) {
                $includeReasonMsg = true;
            }
            // Remove duplicates from reasons
            $uniqueReasons = [];
            foreach ($reasons as $r) {
                $norm = strtolower(trim($r, ". "));
                if ($norm !== $normalizedReasonMsg && !in_array($norm, $uniqueReasons, true)) {
                    $uniqueReasons[] = $norm;
                }
            }
            $notesParts = [];
            if ($includeReasonMsg) {
                $notesParts[] = $reasonMsg;
            }
            if (!empty($uniqueReasons)) {
                $notesParts[] = implode(". ", array_map('ucfirst', $uniqueReasons)) . '.';
            }
            // If nothing is left, always show the main reasonMsg
            if (empty($notesParts)) {
                $notesParts[] = $reasonMsg;
            }
            $adminNotes .= implode(' ', $notesParts);
        }

        // Check if admin_notes column exists
        $hasAdminNotes = in_array('admin_notes', $columns);

>>>>>>> Stashed changes
        // Insert student data with correct columns
        if ($hasAdminNotes) {
            $sql = "INSERT INTO register_studentsqe (
                last_name, first_name, middle_name, gender, dob, email, contact_number, street, 
                student_type, previous_school, year_level, previous_program, desired_program, 
                tor, school_id, is_tech, status, stud_id, admin_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error preparing statement: " . $conn->error . " SQL: $sql");
            }
            $stmt->bind_param(
                "ssssssssssissssisss",
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
                $studentData['is_tech'],
                $studentData['status'],
                $studentData['stud_id'],
                $adminNotes
            );
        } else {
            $sql = "INSERT INTO register_studentsqe (
                last_name, first_name, middle_name, gender, dob, email, contact_number, street, 
                student_type, previous_school, year_level, previous_program, desired_program, 
                tor, school_id, is_tech, status, stud_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error preparing statement: " . $conn->error . " SQL: $sql");
            }
            $stmt->bind_param(
                "ssssssssssissssissi",
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
                $studentData['is_tech'],
                $studentData['status'],
                $studentData['stud_id']
            );
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting student: " . $stmt->error);
        }
        
        $student_id = $stmt->insert_id;
        $stmt->close();
        
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        // Store reference ID in session
        $_SESSION['success'] = "Your reference ID is: " . $reference_id;
        $_SESSION['reference_id'] = $reference_id;
=======
        // Store student ID in session
>>>>>>> Stashed changes
=======
        // Store student ID in session
>>>>>>> Stashed changes
        $_SESSION['student_id'] = $student_id;
        
        // Store manual review flag for UI display if applicable
        if ($studentData['status'] === 'needs_review') {
            $_SESSION['registration_status'] = 'needs_review';
            $_SESSION['message'] = "Your application has been submitted for manual review by an administrator. This is usually due to your institution's grading system not being registered in our database.";
            $_SESSION['message_type'] = 'warning';
        } else {
            $_SESSION['registration_status'] = 'pending';
        }
        
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        // Remove the automatic subject registration to prevent storing unmatched courses
        // We'll rely on matchCreditedSubjects to properly match and store only the matched subjects
        
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
        
=======
        // Commit transaction
        $conn->commit();
        
>>>>>>> Stashed changes
=======
        // Commit transaction
        $conn->commit();
        
>>>>>>> Stashed changes
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
    // First check if the student is ladderized - they are automatically eligible
    if (isset($_POST['student_type']) && $_POST['student_type'] === 'ladderized') {
        logExtraction("Student is ladderized - automatically eligible");
        return true;
    }
    
    logExtraction("Starting eligibility determination", [
        'subject_count' => count($subjects), 
        'rules_count' => count($gradingRules)
    ]);
    
    $minPassingPercentage = 85.0; // Minimum required percentage
    
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
        $grade = isset($subject['Grades']) ? trim($subject['Grades']) : (isset($subject['grade']) ? trim($subject['grade']) : null);
        if (empty($grade)) {
            logExtraction("Subject missing grade", ['subject' => $subject]);
            return false; // If any subject is missing a grade, student is not eligible
        }
        
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
        $gradeValue = floatval(str_replace(',', '.', $grade));
        
        foreach ($gradingRules as $rule) {
            $ruleGradeValue = floatval($rule['grade_value']);
            
            // For systems where lower numbers are better grades (e.g., 1.0 is better than 1.5)
            if ($gradeValue <= $ruleGradeValue) {
                $matchingRule = $rule;
                break;
            }
        }
        
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
                logExtraction("Subject is not eligible - below required percentage", [
                    'subject_code' => $subjectCode,
                    'grade' => $grade,
                    'percentage' => $ruleMinPercentage,
                    'required' => $minPassingPercentage
                ]);
                return false; // Exit immediately if any subject doesn't meet the requirement
            }
        } else {
            logExtraction("No matching grade rule found", [
                'subject_code' => $subjectCode,
                'grade' => $grade
            ]);
            return false; // If we can't find a matching rule, consider it as not eligible
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
    
    // First check if the student is ladderized - this takes precedence
    if (isset($_POST['student_type']) && $_POST['student_type'] === 'ladderized') {
        logExtraction("Student identified as ladderized type", [
            'student_type' => $_POST['student_type']
        ]);
        return 2; // Return 2 for ladderized students
    }
    
    // Check if the student's program name exists in the POST data
    if (isset($_POST['previous_program'])) {
        $programName = strtolower($_POST['previous_program']);
        
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        // Define tech programs - both full names and abbreviations
        $techPrograms = [
            "bscs", // Abbreviation
            "bsit", // Abbreviation
            "bs cs", // Alternative format
            "bs it", // Alternative format
            "bachelor of science in computer science",
            "bachelor of science in information technology",
            "computer science",
            "information technology",
            "dict", // Added DICT as a tech program
            "diploma in information communication technology"
        ];
        
        // Log the exact program name we're checking
        logExtraction("Checking program name for tech classification", [
            'program' => $programName
        ]);
        
        // Check for exact match first
        foreach ($techPrograms as $techProgram) {
            // Check if the program name exactly matches or contains the tech program
            if ($programName === $techProgram || strpos($programName, $techProgram) !== false) {
                logExtraction("Tech student identified by program name", [
                    'program' => $programName,
                    'matched_keyword' => $techProgram
                ]);
                return 1; // Return 1 for regular tech students
            }
        }
        
=======
=======
>>>>>>> Stashed changes
        // Get database connection
        $conn = getNewConnection();
        
        // Fetch tech programs from database
        $query = "SELECT program_name, program_code FROM tech_programs WHERE is_active = 1";
        $result = $conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $techProgram = strtolower($row['program_name']);
                $techCode = strtolower($row['program_code']);
                
                // Check for exact match or if program name contains the tech program
                if ($programName === $techProgram || 
                    strpos($programName, $techProgram) !== false ||
                    strpos($programName, $techCode) !== false) {
                    
                    logExtraction("Tech student identified by program name", [
                        'program' => $programName,
                        'matched_keyword' => $techProgram
                    ]);
                    
                    closeConnection($conn);
                    return 1; // Return 1 for regular tech students
                }
            }
        }
        
        closeConnection($conn);
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        logExtraction("Student not classified as tech student - program doesn't match tech criteria", [
            'program' => $programName
        ]);
    } else {
        logExtraction("No previous program data available to determine tech status");
    }
    
    return 0; // Return 0 for non-tech students
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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        $endpoint = "https://group8ocr.cognitiveservices.azure.com/";
        $apiKey = "8DzDS3AgnWPQcA2gfPyqkUdna2ncf0ac0pvxeNmTZ8rRCQXImevlJQQJ99BDACqBBLyXJ3w3AAALACOGvEIc";
=======
        $endpoint = "https://ocrstreams23.cognitiveservices.azure.com/";
        $apiKey = "6vzUhLMEFi9E6bVVpkG16zEc9p0TlPPLYZQDQvlmI3PDv2rRRCT3JQQJ99BEAC3pKaRXJ3w3AAALACOGTZUj";
>>>>>>> Stashed changes
=======
        $endpoint = "https://ocrstreams23.cognitiveservices.azure.com/";
        $apiKey = "6vzUhLMEFi9E6bVVpkG16zEc9p0TlPPLYZQDQvlmI3PDv2rRRCT3JQQJ99BEAC3pKaRXJ3w3AAALACOGTZUj";
>>>>>>> Stashed changes
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
        
        // Save the full JSON response to a debug file
        $debugDir = __DIR__ . '/logs/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0777, true);
        }
        $debugFile = $debugDir . '/azure_response_' . date('Ymd_His') . '.json';
        file_put_contents($debugFile, json_encode($results, JSON_PRETTY_PRINT));
        logExtraction("Saved full Azure response to debug file", ['file' => $debugFile]);
        
        // Log the detailed structure of the results
        logExtraction("Detailed Azure response structure", [
            'status' => $results['status'] ?? 'unknown',
            'has_analyzeResult' => isset($results['analyzeResult']) ? 'yes' : 'no',
            'document_count' => isset($results['analyzeResult']['documents']) ? count($results['analyzeResult']['documents']) : 0,
            'document_fields' => isset($results['analyzeResult']['documents'][0]['fields']) ? array_keys($results['analyzeResult']['documents'][0]['fields']) : [],
            'has_table' => isset($results['analyzeResult']['documents'][0]['fields']['TorTable']) ? 'yes' : 'no',
            'content_length' => isset($results['analyzeResult']['content']) ? strlen($results['analyzeResult']['content']) : 0
        ]);
        
        // If table field exists, log its structure too
        if (isset($results['analyzeResult']['documents'][0]['fields']['TorTable'])) {
            $tableField = $results['analyzeResult']['documents'][0]['fields']['TorTable'];
            logExtraction("Table field structure", [
                'type' => $tableField['type'] ?? 'unknown',
                'has_valueArray' => isset($tableField['valueArray']) ? 'yes' : 'no',
                'row_count' => isset($tableField['valueArray']) ? count($tableField['valueArray']) : 0,
                'row0_fields' => isset($tableField['valueArray'][0]['valueObject']) ? array_keys($tableField['valueArray'][0]['valueObject']) : []
            ]);
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
            if (isset($document['fields']) && isset($document['fields']['TorTable'])) {
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
            'original_dimensions' => "{$originalWidth}x{$originalHeight}",
            'cropped_dimensions' => "{$cropWidth}x{$cropHeight}",
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
                // Handle the final form submission
                $result = handleFinalSubmission();
                
                // Check if we should redirect (direct form submission) instead of returning JSON
                if (isset($_POST['redirect_on_success']) && !empty($_POST['redirect_on_success'])) {
                    $redirectUrl = $_POST['redirect_on_success'];
                    
                    if (isset($result['success']) && $result['success']) {
                        // Set flag to show success modal instead of redirecting to success page
                        $_SESSION['show_success_modal'] = true;
                        // Also keep the message just in case
                        $_SESSION['message'] = "Registration successful!";
                        $_SESSION['message_type'] = "success";
                        // Redirect back to registration page which will show the modal
                        header("Location: qualiexam_register.php");
                    } else {
                        $_SESSION['message'] = isset($result['error']) ? $result['error'] : "Registration failed";
                        $_SESSION['message_type'] = "error";
                        $_SESSION['show_error_modal'] = true;
                        header("Location: qualiexam_register.php");
                    }
                    exit();
                } else {
                    // For AJAX requests, include a flag in the JSON response
                    if (isset($result['success']) && $result['success']) {
                        // Set the session flag for showing modal
                        $_SESSION['show_success_modal'] = true;
                        
                        // Add a redirect URL to the JSON response
                        $result['redirect_url'] = 'qualiexam_register.php';
                    }
                    echo json_encode($result);
                    exit();
                }
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
        
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        if (!isset($_FILES['tor']) || $_FILES['tor']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please upload a valid Transcript of Records (TOR)");
        }

        validateUploadedFile($_FILES['tor']);
        $tor_path = __DIR__ . '/uploads/tor/' . basename($_FILES['tor']['name']);
        move_uploaded_file($_FILES['tor']['tmp_name'], $tor_path);

        // Process school ID
=======
=======
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        if (!isset($_FILES['school_id']) || $_FILES['school_id']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please upload a valid School ID");
        }

        validateUploadedFile($_FILES['school_id']);
        $school_id_path = __DIR__ . '/uploads/school_id/' . basename($_FILES['school_id']['name']);
        move_uploaded_file($_FILES['school_id']['tmp_name'], $school_id_path);

<<<<<<< Updated upstream
<<<<<<< Updated upstream
        // Process Copy of Grades if provided (for OCR purposes only)
        $copy_grades_path = null;
        if (isset($_POST['has_copy_grades']) && 
            isset($_FILES['copy_grades']) && 
            $_FILES['copy_grades']['error'] === UPLOAD_ERR_OK) {
            
            validateUploadedFile($_FILES['copy_grades']);
            $copy_grades_path = __DIR__ . '/uploads/tor/' . basename($_FILES['copy_grades']['name']);
            move_uploaded_file($_FILES['copy_grades']['tmp_name'], $copy_grades_path);
        }

        // Clean any output that might have been generated so far
        ob_clean();
        
        // Perform OCR on TOR with error handling
        try {
            $ocr_output = performOCR($tor_path);
            logExtraction("OCR completed successfully", [
=======
=======
>>>>>>> Stashed changes
        // Clean any output that might have been generated so far
        ob_clean();
        
        // Perform OCR on the academic document (TOR or Copy of Grades)
        try {
            $ocr_output = performOCR($academic_document_path);
            logExtraction("OCR completed successfully", [
                'document_type' => $document_type,
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                'output_length' => strlen($ocr_output)
            ]);
        } catch (Exception $ocrEx) {
            logExtraction("OCR process failed", [
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
                'document_type' => $document_type,
>>>>>>> Stashed changes
=======
                'document_type' => $document_type,
>>>>>>> Stashed changes
                'error' => $ocrEx->getMessage()
            ]);
            throw new Exception("OCR processing failed: " . $ocrEx->getMessage());
        }

        // Clean the buffer again before continuing
        ob_clean();
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        
        // If Copy of Grades exists, perform OCR and combine results
        if ($copy_grades_path) {
            try {
                $copy_grades_ocr = performOCR($copy_grades_path);
                logExtraction("Additional OCR for Copy of Grades completed", [
                    'output_length' => strlen($copy_grades_ocr)
                ]);
                
                if (isJson($ocr_output) && isJson($copy_grades_ocr)) {
                    $tor_data = json_decode($ocr_output, true);
                    $grades_data = json_decode($copy_grades_ocr, true);
                    if (isset($tor_data['analyzeResult']['documents']) && 
                        isset($grades_data['analyzeResult']['documents'])) {
                        $tor_data['analyzeResult']['documents'] = array_merge(
                            $tor_data['analyzeResult']['documents'],
                            $grades_data['analyzeResult']['documents']
                        );
                        $ocr_output = json_encode($tor_data);
                    }
                } else {
                    $ocr_output .= "\n=== COPY OF GRADES ===\n" . $copy_grades_ocr;
                }
            } catch (Exception $gradesEx) {
                logExtraction("Additional OCR process failed", [
                    'error' => $gradesEx->getMessage()
                ]);
                // Continue with just the TOR OCR results
            }
        }
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            'tor_path' => $tor_path,
            'school_id_path' => $school_id_path
=======
            'tor_path' => $academic_document_path, // This will be either TOR or Copy of Grades
            'school_id_path' => $school_id_path,
            'document_type' => $document_type // Store which type was uploaded
>>>>>>> Stashed changes
=======
            'tor_path' => $academic_document_path, // This will be either TOR or Copy of Grades
            'school_id_path' => $school_id_path,
            'document_type' => $document_type // Store which type was uploaded
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
    $needsManualReview = false; // Ensure this is declared at the top and only once
>>>>>>> Stashed changes
=======
    $needsManualReview = false; // Ensure this is declared at the top and only once
>>>>>>> Stashed changes
    // Enable error reporting during debug
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Define a timestamp for this execution to track in logs
    $execution_id = uniqid('submission_');
    $start_time = microtime(true);
    
    // Store original connection if it exists
    global $conn;
    $originalConn = $conn;
    
    // Start output buffering to catch any unexpected output
    ob_start();
    
    logExtraction("[DEBUG-$execution_id] STARTING final submission process at " . date('Y-m-d H:i:s'), [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'remote_addr' => $_SERVER['REMOTE_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ]);
    
    try {
        // Log the start of the process with POST data debugging
        logExtraction("[DEBUG-$execution_id] POST data received", [
            'post_data' => array_map(function($item) {
                return is_string($item) && strlen($item) > 100 ? substr($item, 0, 100) . '...' : $item;
            }, $_POST),
            'session_data' => isset($_SESSION['upload_paths']) ? $_SESSION['upload_paths'] : 'No upload paths',
            'files' => isset($_FILES) ? array_keys($_FILES) : 'No files',
            'stud_id' => $_SESSION['stud_id'] ?? 'Not set'
        ]);
        
        // Ensure we have a valid database connection before proceeding
        logExtraction("[DEBUG-$execution_id] Fixing database connection");
        $conn = fixDatabaseConnection();
        if (!$conn) {
            throw new Exception("Could not establish database connection at the beginning of handleFinalSubmission");
        }
        
        // Check if database constants are defined
        global $servername, $username, $password, $dbname;
        logExtraction("[DEBUG-$execution_id] Database configuration check", [
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
        
        logExtraction("[DEBUG-$execution_id] Parsing subjects JSON data");
        // Test JSON parsing safely
        try {
            $subjects = json_decode($_POST['subjects'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON parse error: " . json_last_error_msg());
            }
            logExtraction("[DEBUG-$execution_id] Successfully parsed subjects JSON", [
                'count' => count($subjects),
                'sample' => !empty($subjects) ? $subjects[0] : 'No subjects'
            ]);
        } catch (Exception $jsonEx) {
            throw new Exception("Error decoding subjects: " . $jsonEx->getMessage());
        }
        
        // Verify database connection is still valid at this point
        logExtraction("[DEBUG-$execution_id] Checking if database connection is still valid");
        if (!$conn || !@$conn->ping()) {
            logExtraction("[DEBUG-$execution_id] Database connection lost after JSON parsing, reconnecting");
            $conn = fixDatabaseConnection();
            
            if (!$conn) {
                throw new Exception("Could not re-establish database connection after JSON parsing");
            }
        }
        
        // Test connection with a simple query
        logExtraction("[DEBUG-$execution_id] Testing database connection with simple query");
        $testResult = $conn->query("SELECT 1");
        if (!$testResult) {
            logExtraction("[DEBUG-$execution_id] Connection test failed: " . $conn->error);
            $conn = fixDatabaseConnection();
            if (!$conn || !$conn->query("SELECT 1")) {
                throw new Exception("Could not establish a working database connection");
            }
        }
        
        logExtraction("[DEBUG-$execution_id] Database connection confirmed valid");

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
        
        logExtraction("[DEBUG-$execution_id] Document paths validation", [
            'tor_path' => $tor_path ?? 'Not set',
            'school_id_path' => $school_id_path ?? 'Not set'
        ]);
        
        // Check for existing files from previous registrations
        if (empty($tor_path) && isset($_POST['existing_tor']) && !empty($_POST['existing_tor'])) {
            $tor_path = $_POST['existing_tor'];
            logExtraction("[DEBUG-$execution_id] Using existing TOR file", ['path' => $tor_path]);
        }
        
        if (empty($school_id_path) && isset($_POST['existing_school_id']) && !empty($_POST['existing_school_id'])) {
            $school_id_path = $_POST['existing_school_id'];
            logExtraction("[DEBUG-$execution_id] Using existing School ID file", ['path' => $school_id_path]);
        }
        
        if (!$tor_path || !$school_id_path) {
            throw new Exception("Required document uploads are missing.");
        }

        // Validate form data
        logExtraction("[DEBUG-$execution_id] Validating form data fields");
        $required_fields = [
            'first_name', 'last_name', 'gender', 'dob', 'email', 
            'contact_number', 'address', 'student_type', 'previous_school',
            'desired_program'
        ];

        // Add previous_program to required fields only if not ladderized
        if ($_POST['student_type'] !== 'ladderized') {
            $required_fields[] = 'previous_program';
        }
        
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            logExtraction("[DEBUG-$execution_id] Missing required fields", ['fields' => $missing_fields]);
            throw new Exception("Required fields missing: " . implode(', ', $missing_fields));
        }
        
        logExtraction("[DEBUG-$execution_id] All required form fields are present");

        // Set DICT as previous program for ladderized students
        if ($_POST['student_type'] === 'ladderized') {
            $_POST['previous_program'] = "Diploma in Information Communication Technology (DICT)";
            logExtraction("[DEBUG-$execution_id] Set DICT as previous program for ladderized student", [
                'student_type' => $_POST['student_type'],
                'previous_program' => $_POST['previous_program']
            ]);
        }

        // Get grading system rules for the student's previous school
        logExtraction("[DEBUG-$execution_id] Getting grading system rules");
        try {
            $previous_school = $_POST['previous_school'];
            $needsManualReview = false;
            $GLOBALS['needsManualReview'] = false;
            $GLOBALS['manualReviewReasons'] = [];
            
            // If previous_school is a code, use it directly for grading system lookup
            $university_code = $_POST['previous_school'];
            $university_name = null;
            // If it's not a code, try to look up the code by name
            if (!preg_match('/^[A-Za-z0-9_\-]+$/', $university_code) || strlen($university_code) > 10) {
                // It's probably a name, look up the code
                $uniQuery = "SELECT university_code FROM universities WHERE LOWER(university_name) = LOWER(?) LIMIT 1";
                $uniStmt = $conn->prepare($uniQuery);
                if ($uniStmt) {
                    $uniStmt->bind_param("s", $university_code);
                    $uniStmt->execute();
                    $uniResult = $uniStmt->get_result();
                    if ($uniResult->num_rows > 0) {
                        $uniData = $uniResult->fetch_assoc();
                        $university_code = $uniData['university_code'];
                    }
                    $uniStmt->close();
                }
            }
            // Now use university_code for grading system lookup
            $gradingRules = getGradingSystemRules($conn, $university_code);
            
            if (empty($gradingRules)) {
                // Instead of throwing an exception, mark for manual review
                $needsManualReview = true;
                $GLOBALS['needsManualReview'] = true;
                $GLOBALS['manualReviewReasons'][] = "No grading system rules found for university '{$previous_school}'";
                logExtraction("[DEBUG-$execution_id] No grading rules found - marking for manual review", [
                    'university' => $previous_school
                ]);
            } else {
                logExtraction("[DEBUG-$execution_id] Retrieved grading rules", [
                    'university' => $previous_school,
                    'rules_count' => count($gradingRules),
                    'passing_grade_values' => array_map(function($rule) {
                        return $rule['grade_value'] . ' (' . $rule['description'] . '): ' . $rule['min_percentage'] . '%';
                    }, array_filter($gradingRules, function($rule) {
                        return floatval($rule['min_percentage']) >= 85.0;
                    }))
                ]);
            }
        } catch (Exception $gradingEx) {
            // Log the error but continue with manual review flag
            $needsManualReview = true;
            $GLOBALS['needsManualReview'] = true;
            $GLOBALS['manualReviewReasons'][] = "Error retrieving grading rules: " . $gradingEx->getMessage();
            logExtraction("[DEBUG-$execution_id] Error retrieving grading rules - marking for manual review", [
                'error' => $gradingEx->getMessage()
            ]);
        }
        
        // Check if student is eligible based on grades, but only if we have grading rules
        logExtraction("[DEBUG-$execution_id] Checking eligibility based on grades");
        try {
            $isEligible = true; // Default to eligible
            
            // Skip eligibility check for ladderized students
            if (isset($_POST['student_type']) && $_POST['student_type'] === 'ladderized') {
                logExtraction("[DEBUG-$execution_id] Student is ladderized - skipping grade eligibility check");
                $isEligible = true; // Automatically eligible
            }
            // For non-ladderized students, proceed with normal eligibility check
            else if (!$needsManualReview && !empty($gradingRules)) {
                logExtraction("[DEBUG-$execution_id] Starting eligibility check with " . count($subjects) . " subjects");
                $isEligible = determineEligibility($subjects, $gradingRules);
                
                if (!$isEligible) {
                    logExtraction("[DEBUG-$execution_id] Student does not meet eligibility criteria");
                    throw new Exception("Student does not meet the eligibility criteria based on grades. Minimum passing grade is 85% (2.0 or better).");
                }
                
                logExtraction("[DEBUG-$execution_id] Student meets eligibility criteria");
            } else {
                logExtraction("[DEBUG-$execution_id] Skipping automated eligibility check - will be reviewed manually");
            }
        } catch (Exception $eligibilityEx) {
            // If this is due to missing grading rules, mark for manual review
            if ($needsManualReview) {
                logExtraction("[DEBUG-$execution_id] Eligibility check skipped due to missing grading rules");
            } else {
                // Otherwise, this is a genuine eligibility failure
                logExtraction("[DEBUG-$execution_id] Eligibility check failed: " . $eligibilityEx->getMessage());
                throw new Exception("Eligibility check error: " . $eligibilityEx->getMessage());
            }
        }
        
        // Check if the student is a tech student based on their subjects
        logExtraction("[DEBUG-$execution_id] Checking if student is a tech student");
        try {
            $isTech = isTechStudent($subjects);
            
            logExtraction("[DEBUG-$execution_id] Tech student assessment", [
                'is_tech' => $isTech === 2 ? 'Ladderized' : ($isTech === 1 ? 'Yes' : 'No')
            ]);
        } catch (Exception $techEx) {
            // If there's an error determining tech status, default to false but log the error
            logExtraction("[DEBUG-$execution_id] Error determining tech status: " . $techEx->getMessage());
            $isTech = 0;
        }

        // Prepare student data for registration
        logExtraction("[DEBUG-$execution_id] Preparing student data for registration");
        try {
            $studentData = [
                'first_name' => $_POST['first_name'],
                'middle_name' => $_POST['middle_name'] ?? '',
                'last_name' => $_POST['last_name'],
                'gender' => $_POST['gender'],
                'dob' => $_POST['dob'], // HTML date input sends in YYYY-MM-DD format, which matches our database
                'email' => $_POST['email'],
                'contact_number' => $_POST['contact_number'],
                'street' => $_POST['street'] ?? $_POST['address'] ?? '',
                'student_type' => $_POST['student_type'],
                'previous_school' => $_POST['previous_school'],
                'year_level' => ($_POST['student_type'] === 'ladderized') ? 0 : ($_POST['year_level'] ?? 0),
                'previous_program' => $_POST['previous_program'],
                'desired_program' => $_POST['desired_program'],
                'tor_path' => str_replace(__DIR__ . '/', '', $tor_path),
                'school_id_path' => str_replace(__DIR__ . '/', '', $school_id_path),
                'is_tech' => $isTech,
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                'status' => $needsManualReview ? 'needs_review' : 'pending'
=======
=======
>>>>>>> Stashed changes
                'status' => $needsManualReview ? 'needs_review' : 'pending',
                'stud_id' => $_SESSION['stud_id'] ?? null,
                // Add admin_notes if manual review is needed
                'admin_notes' => $needsManualReview ? (implode('; ', $GLOBALS['manualReviewReasons'] ?? [])) : ''
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            ];

            logExtraction("[DEBUG-$execution_id] Prepared student data for registration", [
                'name' => $studentData['first_name'] . ' ' . $studentData['last_name'],
                'email' => $studentData['email'],
                'subject_count' => count($subjects),
                'student_type' => $studentData['student_type'],
                'is_tech' => $studentData['is_tech'] === 2 ? 'Ladderized' : ($studentData['is_tech'] === 1 ? 'Yes' : 'No'),
                'status' => $studentData['status']
            ]);
        } catch (Exception $dataEx) {
            logExtraction("[DEBUG-$execution_id] Error preparing student data: " . $dataEx->getMessage());
            throw new Exception("Error preparing student data: " . $dataEx->getMessage());
        }

        // Begin transaction for the registration process
        logExtraction("[DEBUG-$execution_id] Starting database transaction");
        try {
            // Make sure we're using a valid connection
            if (!$conn || !$conn->ping()) {
                logExtraction("[DEBUG-$execution_id] Database connection lost before starting transaction, reconnecting");
                $conn = fixDatabaseConnection();
                if (!$conn) {
                    throw new Exception("Database connection lost before starting transaction");
                }
            }
            
            $conn->begin_transaction();
            logExtraction("[DEBUG-$execution_id] Database transaction started successfully");
        } catch (Exception $txEx) {
            logExtraction("[DEBUG-$execution_id] Error starting database transaction: " . $txEx->getMessage());
            throw new Exception("Error starting database transaction: " . $txEx->getMessage());
        }
        
        try {
            // Register student in the database
            logExtraction("[DEBUG-$execution_id] Calling registerStudent function");
            $registrationResult = registerStudent($conn, $studentData, $subjects);
            
            if (!$registrationResult) {
                logExtraction("[DEBUG-$execution_id] registerStudent function returned false");
                throw new Exception("Failed to register student in the database.");
            }
            
            // Get the student_id from the registration result
            $student_id = $_SESSION['student_id'] ?? null;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            $reference_id = $_SESSION['reference_id'] ?? null;
            
            if (!$student_id || !$reference_id) {
                logExtraction("[DEBUG-$execution_id] Missing student_id or reference_id after registration", [
                    'student_id' => $student_id ?? 'Not set',
                    'reference_id' => $reference_id ?? 'Not set'
                ]);
                throw new Exception("Failed to generate student ID or reference ID.");
            }
            
            logExtraction("[DEBUG-$execution_id] Student registered successfully", [
                'student_id' => $student_id,
                'reference_id' => $reference_id
=======
=======
>>>>>>> Stashed changes
            // $reference_id = $_SESSION['reference_id'] ?? null; // No longer needed here
            
            if (!$student_id) {
                logExtraction("[DEBUG-$execution_id] Missing student_id after registration", [
                    'student_id' => $student_id ?? 'Not set'
                ]);
                throw new Exception("Failed to generate student ID.");
            }
            
            logExtraction("[DEBUG-$execution_id] Student registered successfully", [
                'student_id' => $student_id
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            ]);
            
            // Match credited subjects from the uploaded documents
            logExtraction("[DEBUG-$execution_id] Calling matchCreditedSubjects function");
            $matchedCount = matchCreditedSubjects($conn, $subjects, $student_id);
            
            logExtraction("[DEBUG-$execution_id] Matched credited subjects", [
                'count' => $matchedCount
            ]);
            
            // Commit the transaction if everything is successful
            logExtraction("[DEBUG-$execution_id] Committing database transaction");
            $conn->commit();
            logExtraction("[DEBUG-$execution_id] Database transaction committed successfully");
            
            // Store data for the success page
            $_SESSION['last_registration'] = true;
            $_SESSION['student_name'] = $studentData['first_name'] . ' ' . $studentData['last_name'];
            $_SESSION['email'] = $studentData['email'];
            $_SESSION['desired_program'] = $studentData['desired_program']; // Store desired program for fallback
            $_SESSION['is_eligible'] = $isEligible; // Flag to indicate if the student is eligible
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            $_SESSION['success'] = "Your registration was successful! Your reference ID is: " . $reference_id;
=======
            $_SESSION['success'] = "Your registration was successful and is now pending review. You will receive your reference ID via email once your application is accepted.";
>>>>>>> Stashed changes
=======
            $_SESSION['success'] = "Your registration was successful and is now pending review. You will receive your reference ID via email once your application is accepted.";
>>>>>>> Stashed changes
            
            // Set flag to show success modal
            $_SESSION['show_success_modal'] = true;
            
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
            
            // Calculate total execution time
            $execution_time = round(microtime(true) - $start_time, 2);
            
            // Return success response
            logExtraction("[DEBUG-$execution_id] Returning success response, total execution time: {$execution_time}s");
            return [
                'success' => true,
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                'message' => 'Registration completed successfully',
                'reference_id' => $reference_id,
=======
                'message' => 'Registration completed successfully. Your application is pending review. You will receive your reference ID via email if accepted.',
>>>>>>> Stashed changes
=======
                'message' => 'Registration completed successfully. Your application is pending review. You will receive your reference ID via email if accepted.',
>>>>>>> Stashed changes
                'execution_time' => $execution_time
            ];
            
        } catch (Exception $txnEx) {
            // Rollback on any error
            try {
                if ($conn && $conn->ping()) {
                    logExtraction("[DEBUG-$execution_id] Rolling back transaction due to error: " . $txnEx->getMessage());
                    $conn->rollback();
                    logExtraction("[DEBUG-$execution_id] Transaction rolled back due to error");
                }
            } catch (Exception $rollbackEx) {
                logExtraction("[DEBUG-$execution_id] Error during rollback: " . $rollbackEx->getMessage());
            }
            
            logExtraction("[DEBUG-$execution_id] Registration failed: " . $txnEx->getMessage());
            throw new Exception("Registration failed: " . $txnEx->getMessage());
        }

    } catch (Exception $e) {
        // Calculate total execution time
        $execution_time = round(microtime(true) - $start_time, 2);
        
        // Log the error with detailed information
        logExtraction("[DEBUG-$execution_id] ERROR in handleFinalSubmission (time: {$execution_time}s)", [
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
                    logExtraction("[DEBUG-$execution_id] Transaction rolled back in error handler");
                }
            }
        } catch (Exception $rollbackEx) {
            logExtraction("[DEBUG-$execution_id] Error during error-handler rollback: " . $rollbackEx->getMessage());
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
        logExtraction("[DEBUG-$execution_id] Returning error response to client");
        return [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => DEBUG_MODE ? explode("\n", $e->getTraceAsString()) : 'Trace hidden in production',
            'execution_time' => $execution_time,
            'execution_id' => $execution_id
        ];
    }
}

// If we reach here, something went wrong
$_SESSION['ocr_error'] = "An unexpected error occurred. Please try again.";
// Instead of redirecting, set a flag to show error modal
$_SESSION['show_error_modal'] = true;
// Redirect back to the registration page where the modal will be shown
header("Location: qualiexam_register.php");
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
                $result = handleFinalSubmission();
                
                // Check if we should redirect (direct form submission) instead of returning JSON
                if (isset($_POST['redirect_on_success']) && !empty($_POST['redirect_on_success'])) {
                    $redirectUrl = $_POST['redirect_on_success'];
                    
                    if (isset($result['success']) && $result['success']) {
                        // Set flag to show success modal instead of redirecting to success page
                        $_SESSION['show_success_modal'] = true;
                        // Also keep the message just in case
                        $_SESSION['message'] = "Registration successful!";
                        $_SESSION['message_type'] = "success";
                        // Redirect back to registration page which will show the modal
                        header("Location: qualiexam_register.php");
                    } else {
                        $_SESSION['message'] = isset($result['error']) ? $result['error'] : "Registration failed";
                        $_SESSION['message_type'] = "error";
                        $_SESSION['show_error_modal'] = true;
                        header("Location: qualiexam_register.php");
                    }
                    exit();
                } else {
                    // For AJAX requests, include a flag in the JSON response
                    if (isset($result['success']) && $result['success']) {
                        // Set the session flag for showing modal
                        $_SESSION['show_success_modal'] = true;
                        
                        // Add a redirect URL to the JSON response
                        $result['redirect_url'] = 'qualiexam_register.php';
                    }
                    echo json_encode($result);
                    exit();
                }
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

// Add this function at the end of the file
function getSubjectPrefix($subjectCode) {
    // Remove any spaces
    $code = str_replace(' ', '', $subjectCode);
    
    // Extract the alphabetic part of the code
    preg_match('/^([A-Za-z]+)/', $code, $matches);
    
    return isset($matches[1]) ? strtoupper($matches[1]) : '';
}

// Create a function to find a course code match
function matchSubjectByCode($tor_code, $program_subjects) {
    // If the TOR code is empty, return null
    if (empty($tor_code)) {
        return null;
    }
    
    // Extract the subject prefix from the TOR code
    $tor_prefix = getSubjectPrefix($tor_code);
    
    // If no valid prefix found, return null
    if (empty($tor_prefix)) {
        return null;
    }
    
    // Look for a program subject with the same prefix
    foreach ($program_subjects as $program_subject) {
        $program_prefix = getSubjectPrefix($program_subject['subject_code']);
        
        if ($program_prefix == $tor_prefix) {
            return $program_subject;
        }
    }
    
    return null;
}

// Add this helper function at the top of the file
function getPostField($field, $fallback = null) {
    if (isset($_POST[$field])) {
        return $_POST[$field];
    }
    
    // Handle special cases for address/street field
    if ($field === 'street' && isset($_POST['address'])) {
        return $_POST['address'];
    }
    
    if ($field === 'address' && isset($_POST['street'])) {
        return $_POST['street'];
    }
    
    return $fallback;
}
<<<<<<< Updated upstream
<<<<<<< Updated upstream
?>
=======


?>
>>>>>>> Stashed changes
=======


?>
>>>>>>> Stashed changes
