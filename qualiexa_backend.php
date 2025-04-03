<?php
// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Required dependencies
require_once 'vendor/autoload.php';
require_once 'config/config.php';
require_once 'config/google_cloud_config.php';

// Ensure database connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not established. Please check your configuration.");
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

use GuzzleHttp\Client as GuzzleClient;

/**
 * Class QualifyingExamRegistration
 * Handles the registration process for qualifying examinations
 */
class QualifyingExamRegistration {
    private $conn;
    private $uploadDir;
    private $logDir;
    private $allowedFileTypes;
    private $maxFileSize;

    /**
     * Constructor
     * @param mysqli $conn Database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;
        $this->uploadDir = __DIR__ . '/uploads/';
        $this->logDir = __DIR__ . '/logs/';
        $this->allowedFileTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB

        $this->initializeDirectories();
    }

    /**
     * Initialize required directories
     */
    private function initializeDirectories() {
        $dirs = [
            $this->uploadDir . 'tor/',
            $this->uploadDir . 'school_id/',
            $this->logDir
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Log messages with severity levels
     */
    private function logMessage($message, $data = null, $severity = 'INFO') {
        $logFile = $this->logDir . 'registration_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp][$severity] $message";

        if ($data !== null) {
            $logEntry .= "\nData: " . print_r($data, true);
        }

        $logEntry .= "\n" . str_repeat('-', 80) . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception('No file was uploaded');
        }

        if (!in_array($file['type'], $this->allowedFileTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed');
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File is too large. Maximum size is 5MB');
        }

        return true;
    }

    /**
     * Handle file upload with improved path handling
     */
    private function handleFileUpload($file, $type) {
        $this->validateFile($file);
        
        $targetDir = 'uploads/' . $type . '/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $targetDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload $type file");
        }

        return $targetPath;
    }

    /**
     * Extract subjects from OCR text with improved pattern matching
     */
    private function extractSubjects($text) {
        $this->logMessage('Raw OCR Text', ['text' => $text]);
        
        $subjects = [];
        $inSubjectsSection = false;
        $currentSubject = null;
        
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
        
        // Split into lines and clean
        $lines = array_map('trim', explode("\n", $text));
        
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
                if (preg_match('/^[A-Z]+\d*-?[A-Z]?(?:LEC|LAB)?-?[A-Z]?$/i', $part) || 
                    preg_match('/^(?:GEC|FCL|NSTP|PSY|PE|FIL|IT|CS)\s*\d{4}L?$/i', $part)) {
                    
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
        
        $this->logMessage('Extracted Subjects', [
            'count' => count($subjects),
            'subjects' => array_values($subjects)
        ]);
        
        return array_values($subjects);
    }

    /**
     * Validate subject data
     */
    private function isValidSubject($subject) {
        // Basic validation
        if (empty($subject['subject_code']) || empty($subject['description'])) {
            return false;
        }

        // Validate grade if present
        if ($subject['grade'] !== null) {
            $grade = floatval($subject['grade']);
            if ($grade < 1.00 || $grade > 5.00) {
                return false;
            }
        }

        // Validate units if present
        if ($subject['units'] !== null) {
            $units = floatval($subject['units']);
            if ($units <= 0 || $units > 6.0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Perform OCR using Google Cloud Vision API with improved error handling
     */
    private function performOCR($imagePath) {
        try {
            // Verify file exists and is readable
            if (!file_exists($imagePath) || !is_readable($imagePath)) {
                throw new Exception("Cannot read image file at: $imagePath");
            }

            $imageContent = file_get_contents($imagePath);
            if ($imageContent === false) {
                throw new Exception("Failed to read image content");
            }

            $base64Image = base64_encode($imageContent);
            
            $client = new GuzzleClient([
                'verify' => ini_get('curl.cainfo') ?: false,
                'timeout' => 30,  // Increased timeout
                'connect_timeout' => 10
            ]);

            $payload = [
                'requests' => [[
                    'image' => ['content' => $base64Image],
                    'features' => [[
                        'type' => 'DOCUMENT_TEXT_DETECTION',
                        'maxResults' => 1
                    ]],
                    'imageContext' => [
                        'languageHints' => ['en'],
                        'textDetectionParams' => [
                            'enableTextDetectionConfidenceScore' => true
                        ]
                    ]
                ]]
            ];

            $this->logMessage('Sending OCR request', ['image_size' => strlen($imageContent)]);

            $response = $client->post(GOOGLE_VISION_API_ENDPOINT . '?key=' . GOOGLE_API_KEY, [
                'json' => $payload,
                'headers' => ['Content-Type' => 'application/json']
            ]);

            $result = json_decode($response->getBody(), true);

            if (!isset($result['responses'][0]['textAnnotations'])) {
                $this->logMessage('OCR Response without text', $result);
                throw new Exception('No text detected in the image. Please ensure the image is clear and contains text.');
            }

            $extractedText = $result['responses'][0]['textAnnotations'][0]['description'];
            $this->logMessage('OCR Successful', ['text_length' => strlen($extractedText)]);

            return $extractedText;

        } catch (Exception $e) {
            $this->logMessage('OCR Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'ERROR');
            throw new Exception('Failed to process document text: ' . $e->getMessage());
        }
    }

    /**
     * Match subjects with coded courses using description similarity
     */
    private function matchSubjects($subjects) {
        $matchedSubjects = [];

        foreach ($subjects as $subject) {
            try {
                // Clean and standardize the description
                    $description = strtolower(trim($subject['description']));
                $description = preg_replace('/\s+/', ' ', $description);
                $units = $subject['units'];
                
                $this->logMessage('Checking Subject', [
                    'code' => $subject['subject_code'],
                    'description' => $description,
                    'units' => $units,
                    'grade' => $subject['grade']
                ]);
                
                // Try to match by description and units
                        $stmt = $this->conn->prepare("
                            SELECT * FROM coded_courses 
                            WHERE LOWER(subject_description) LIKE ?
                            AND units = ?
                            LIMIT 1
                        ");
                
                $description_pattern = "%{$description}%";
                $stmt->bind_param("sd", $description_pattern, $units);
                        $stmt->execute();
                        $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $matchedSubjects[] = [
                        'original_code' => $subject['subject_code'],
                        'matched_code' => $row['subject_code'],
                        'description' => $row['subject_description'],
                        'units' => $row['units'],
                        'grade' => $subject['grade']
                    ];

                    $this->logMessage('Subject Matched', [
                        'original' => $subject['subject_code'],
                        'matched' => $row['subject_code'],
                        'description' => $row['subject_description']
                    ]);
                } else {
                    $this->logMessage('No Match Found', [
                        'code' => $subject['subject_code'],
                        'description' => $description
                    ]);
                }
                
            } catch (Exception $e) {
                $this->logMessage('Error Matching Subject', [
                    'error' => $e->getMessage(),
                    'subject' => $subject
                ], 'ERROR');
            }
        }

        return $matchedSubjects;
    }

    /**
     * Check student eligibility
     */
    private function checkEligibility($subjects, $studentType) {
        if ($studentType === 'ladderized') {
            return true;
        }

        foreach ($subjects as $subject) {
            $grade = floatval($subject['grade']);
            if ($grade > 2.00) { // Failing grade check
                return false;
            }
        }

        return true;
    }

    /**
     * Process registration with Google Cloud Vision
     */
    public function processRegistration($postData, $files) {
        try {
            // Debug log at start of registration
            $this->logMessage('Starting registration process', [
                'email' => $postData['email'] ?? 'not provided',
                'student_type' => $postData['student_type'] ?? 'not provided'
            ]);

            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email', 'student_type', 'gender', 'dob', 
                             'contact_number', 'street', 'previous_school', 'desired_program'];
            
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (empty($postData[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                throw new Exception("Missing required fields: " . implode(', ', $missingFields));
            }

            // Validate files
            if (!isset($files['tor']) || empty($files['tor']['tmp_name'])) {
                throw new Exception("Transcript of Records (TOR) file is required");
            }
            if (!isset($files['school_id']) || empty($files['school_id']['tmp_name'])) {
                throw new Exception("School ID file is required");
            }

            // Log file information
            $this->logMessage('File uploads received', [
                'tor' => $files['tor']['name'],
                'school_id' => $files['school_id']['name']
            ]);

            // Generate reference ID
            $referenceId = $this->generateReferenceId();
            $this->logMessage('Generated Reference ID', ['reference_id' => $referenceId]);

            // Process files and OCR
            $torPath = $this->handleFileUpload($files['tor'], 'tor');
            $schoolIdPath = $this->handleFileUpload($files['school_id'], 'school_id');

            // Perform OCR using Google Cloud Vision
            $ocrText = $this->performOCR($torPath);
            if (empty($ocrText)) {
                throw new Exception("Failed to extract text from TOR. Please ensure the image is clear and readable.");
            }

            // Extract and validate subjects
            $subjects = $this->extractSubjects($ocrText);
            if (empty($subjects)) {
                throw new Exception('No valid subjects found in the document.');
            }

            // Check eligibility and set status
            $isEligible = $this->checkEligibility($subjects, $postData['student_type']);
            $status = $isEligible ? 'pending' : 'ineligible';

            // Prepare student data with reference ID and status
            $studentData = [
                'first_name' => $postData['first_name'],
                'last_name' => $postData['last_name'],
                'middle_name' => $postData['middle_name'] ?? '',
                'gender' => $postData['gender'],
                'dob' => $postData['dob'],
                'email' => $postData['email'],
                'contact_number' => $postData['contact_number'],
                'street' => $postData['street'],
                'student_type' => $postData['student_type'],
                'previous_school' => $postData['previous_school'],
                'year_level' => ($postData['student_type'] === 'ladderized') ? null : ($postData['year_level'] ?? null),
                'previous_program' => ($postData['student_type'] === 'ladderized') ? 
                    'Diploma in Information and Communication Technology (DICT)' : 
                    ($postData['previous_program'] ?? ''),
                'desired_program' => $postData['desired_program'],
                'tor_path' => $torPath,
                'school_id_path' => $schoolIdPath,
                'reference_id' => $referenceId,
                'status' => $status
            ];

            // Save registration
            $studentId = $this->saveRegistration($studentData, $subjects);
            
            if (!$studentId) {
                throw new Exception("Failed to save registration.");
            }

            // Set session data
            $_SESSION['success'] = "Registration successful! Your reference ID is: " . $referenceId;
            $_SESSION['student_id'] = $studentId;
            $_SESSION['reference_id'] = $referenceId;
            $_SESSION['registration_data'] = [
                'student_id' => $studentId,
                'name' => $postData['first_name'] . ' ' . $postData['last_name'],
                'email' => $postData['email'],
                'subjects_count' => count($subjects),
                'reference_id' => $referenceId,
                'status' => $status
            ];

            if (!$isEligible) {
                $_SESSION['eligibility_message'] = "Based on your grades, you may not be eligible for credit transfer. Your application will be reviewed by the admin.";
            }

            $_SESSION['matches'] = $this->getMatchResults($subjects);
            
            return true;

        } catch (Exception $e) {
            $this->logMessage('Registration Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'ERROR');
            throw $e;
        }
    }

    /**
     * Generate unique reference ID
     */
    private function generateReferenceId() {
        $year = date('Y');
        $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $referenceId = "CCIS-{$year}-{$unique}";
        
        // Check if reference ID exists
        $stmt = $this->conn->prepare("SELECT reference_id FROM students_registerqe WHERE reference_id = ?");
        $stmt->bind_param("s", $referenceId);
        $stmt->execute();
        
        // Generate new ID if exists
        while ($stmt->get_result()->num_rows > 0) {
            $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $referenceId = "CCIS-{$year}-{$unique}";
            $stmt->execute();
        }
        
        return $referenceId;
    }

    /**
     * Save registration to database
     */
    private function saveRegistration($studentData, $subjects) {
        $this->conn->begin_transaction();

        try {
            // Get relative paths for files
            $torRelativePath = 'uploads/tor/' . basename($studentData['tor_path']);
            $schoolIdRelativePath = 'uploads/school_id/' . basename($studentData['school_id_path']);

            // Insert student data with reference_id and status
            $sql = "INSERT INTO students_registerqe (
                first_name, last_name, middle_name, gender, dob, email, 
                contact_number, street, student_type, previous_school, 
                year_level, previous_program, desired_program, tor, 
                school_id, reference_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssssssssssssssss",
                $studentData['first_name'],
                $studentData['last_name'],
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
                $torRelativePath,
                $schoolIdRelativePath,
                $studentData['reference_id'],
                $studentData['status']
            );

            if (!$stmt->execute()) {
                throw new Exception("Error saving student data: " . $stmt->error);
            }

            $studentId = $this->conn->insert_id;

            // Match and save subjects
            $matchedSubjects = $this->matchSubjects($subjects);
            
            foreach ($matchedSubjects as $subject) {
                // Skip if subject_code is null or empty
                if (empty($subject['matched_code'])) {
                    $this->logMessage('Skipping subject with no code', $subject, 'WARNING');
                    continue;
                }

                $stmt = $this->conn->prepare("INSERT INTO matched_courses (
                    student_id, subject_code, original_code, subject_description, 
                    units, grade, matched_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())");

                $stmt->bind_param("isssdd",
                    $studentId,
                    $subject['matched_code'],
                    $subject['original_code'],
                    $subject['description'],
                    $subject['units'],
                    $subject['grade']
                );

                if (!$stmt->execute()) {
                    $this->logMessage('Error inserting matched course', [
                        'error' => $stmt->error,
                        'subject' => $subject
                    ], 'ERROR');
                }
            }

            $this->conn->commit();
            return $studentId;

        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logMessage('Database Error', ['error' => $e->getMessage()], 'ERROR');
            throw $e;
        }
    }

    /**
     * Get formatted match results for display
     */
    private function getMatchResults($subjects) {
        $matches = [];
        foreach ($subjects as $subject) {
            $matches[] = "✓ Found: {$subject['subject_code']} - {$subject['description']} ({$subject['units']} units) with grade {$subject['grade']}";
        }
        return $matches;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Initialize session debug info
        $_SESSION['debug_info'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_method' => $_SERVER['REQUEST_METHOD']
        ];

        // Debug log for incoming request
        error_log("Received POST request at " . date('Y-m-d H:i:s'));
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));

        // Store request data in debug info
        $_SESSION['debug_info']['post_data'] = $_POST;
        $_SESSION['debug_info']['files'] = array_map(function($file) {
            return [
                'name' => $file['name'] ?? 'not set',
                'type' => $file['type'] ?? 'not set',
                'size' => $file['size'] ?? 'not set',
                'error' => $file['error'] ?? 'not set'
            ];
        }, $_FILES);

        // Clear any existing error logs in session
        unset($_SESSION['error_logs']);
        unset($_SESSION['error']);
        unset($_SESSION['ocr_error']);
        unset($_SESSION['error_details']);
        unset($_SESSION['registration_failed']);

        // Validate POST data
        if (empty($_POST)) {
            throw new Exception("No form data received. Please ensure all fields are filled out.");
        }

        // Validate file uploads
        if (empty($_FILES)) {
            throw new Exception("No files uploaded. Please upload both TOR and School ID.");
        }

        // Check for required files
        if (!isset($_FILES['tor']) || $_FILES['tor']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("TOR file upload failed or not provided. Error code: " . 
                ($_FILES['tor']['error'] ?? 'No file uploaded'));
        }

        if (!isset($_FILES['school_id']) || $_FILES['school_id']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("School ID file upload failed or not provided. Error code: " . 
                ($_FILES['school_id']['error'] ?? 'No file uploaded'));
        }

        // Check database connection
        if (!isset($conn) || !($conn instanceof mysqli)) {
            throw new Exception("Database connection not established. Please try again later.");
        }

        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Initialize registration handler
        $registration = new QualifyingExamRegistration($conn);
        
        // Process the registration
        $success = $registration->processRegistration($_POST, $_FILES);

        if ($success) {
            $_SESSION['redirect_from'] = 'registration';
            $_SESSION['registration_complete'] = true;
            $_SESSION['debug_info']['status'] = 'success';
            header("Location: registration_success.php");
            exit();
        } else {
            $_SESSION['redirect_from'] = 'registration';
            $_SESSION['registration_failed'] = true;
            $_SESSION['debug_info']['status'] = 'failed';
            if (!isset($_SESSION['error_details'])) {
                $_SESSION['error_details'] = [
                    'message' => 'Registration failed for unknown reason',
                    'time' => date('Y-m-d H:i:s')
                ];
            }
            header("Location: registration_success.php?error=1&debug=1");
            exit();
        }
    } catch (Exception $e) {
        error_log("Registration Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        $_SESSION['error'] = $e->getMessage();
        $_SESSION['error_details'] = [
            'message' => $e->getMessage(),
            'time' => date('Y-m-d H:i:s'),
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString()
        ];
        $_SESSION['debug_info']['status'] = 'error';
        $_SESSION['debug_info']['error'] = $e->getMessage();
        $_SESSION['debug_info']['error_time'] = date('Y-m-d H:i:s');
        $_SESSION['redirect_from'] = 'registration';
        $_SESSION['registration_failed'] = true;
        
        header("Location: registration_success.php?error=1&debug=1");
        exit();
    }
} else {
    // Log invalid request method
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    $_SESSION['error'] = "Invalid request method. Please use the registration form.";
    header("Location: qualiexam_register.php");
    exit();
}

exit();
