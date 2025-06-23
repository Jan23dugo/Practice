<?php
// Capture output instead of sending directly to browser
ob_start();

session_start();

// Include database connection first
include('config/config.php');

// Check if assign_exam.php exists before including it
if (file_exists('assign_exam.php')) {
    include('assign_exam.php'); // Include the assignment functionality
}

// Enable error reporting but suppress display
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set header to return JSON - moved after includes
header('Content-Type: application/json');

// Create logs directory if it doesn't exist
$log_dir = __DIR__ . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Debug logging function
function debugLog($message) {
    global $log_dir;
    $timestamp = date('Y-m-d H:i:s');
    $log_file = $log_dir . '/exam_save_debug_' . date('Y-m-d') . '.log';
    $log_message = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'exam_id' => null,
    'assignment_stats' => null
];

// Define default image path
$default_image_path = 'assets/images/default-exam-cover.jpg';

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        debugLog("=== EXAM SAVE PROCESS STARTED ===");
        debugLog("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        debugLog("Raw POST data: " . print_r($_POST, true));
        debugLog("Raw FILES data: " . print_r($_FILES, true));
        
        // Get form data
        $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
        $quiz_name = isset($_POST['quiz-name']) ? $_POST['quiz-name'] : 'Untitled Quiz';
        $quiz_description = isset($_POST['quiz-description']) ? $_POST['quiz-description'] : '';
        $exam_type = isset($_POST['exam-type']) ? $_POST['exam-type'] : 'tech'; // Default to tech
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 60; // Default 60 minutes
        $exam_instructions = isset($_POST['exam-instructions']) ? trim($_POST['exam-instructions']) : null;
        
        debugLog("Parsed basic fields:");
        debugLog("  exam_id: " . $exam_id);
        debugLog("  quiz_name: " . $quiz_name);
        debugLog("  exam_type: " . $exam_type);
        debugLog("  duration: " . $duration);
        
        // Check if this is a temporary exam creation (minimal data)
        $is_temporary = !isset($_POST['is_scheduled']) && !isset($_POST['randomize-questions']) && 
                        !isset($_POST['randomize-choices']) && !isset($_POST['passing_score_type']);
        
        // Validate duration
        if ($duration < 1 || $duration > 480) {
            throw new Exception("Duration must be between 1 and 480 minutes");
        }
        
        // Process scheduling data
        $is_scheduled = isset($_POST['is_scheduled']) ? 1 : 0;
        $window_start = null;
        $window_end = null;

        debugLog("=== SCHEDULING DATA PROCESSING ===");
        debugLog("is_scheduled checkbox: " . (isset($_POST['is_scheduled']) ? 'CHECKED' : 'NOT CHECKED'));
        debugLog("is_scheduled value: " . $is_scheduled);
        debugLog("POST window_start: " . (isset($_POST['window_start']) ? "'{$_POST['window_start']}'" : 'NOT SET'));
        debugLog("POST window_end: " . (isset($_POST['window_end']) ? "'{$_POST['window_end']}'" : 'NOT SET'));
        debugLog("window_start empty?: " . (empty($_POST['window_start']) ? 'YES' : 'NO'));
        debugLog("window_end empty?: " . (empty($_POST['window_end']) ? 'YES' : 'NO'));

        if ($is_scheduled) {
            debugLog("Processing scheduled exam...");
            if (isset($_POST['window_start']) && !empty($_POST['window_start'])) {
                $raw_start = $_POST['window_start'];
                // Convert HTML5 datetime-local format to MySQL datetime format
                $window_start = date('Y-m-d H:i:s', strtotime($raw_start));
                debugLog("✓ window_start raw: '{$raw_start}' -> converted: '{$window_start}'");
            } else {
                debugLog("✗ window_start is empty or not set");
            }
            if (isset($_POST['window_end']) && !empty($_POST['window_end'])) {
                $raw_end = $_POST['window_end'];
                // Convert HTML5 datetime-local format to MySQL datetime format
                $window_end = date('Y-m-d H:i:s', strtotime($raw_end));
                debugLog("✓ window_end raw: '{$raw_end}' -> converted: '{$window_end}'");
            } else {
                debugLog("✗ window_end is empty or not set");
            }
            // If either start or end is missing, set is_scheduled to 0
            if (empty($window_start) || empty($window_end)) {
                debugLog("❌ One or both datetime fields empty, disabling scheduling");
                debugLog("  window_start empty: " . (empty($window_start) ? 'YES' : 'NO'));
                debugLog("  window_end empty: " . (empty($window_end) ? 'YES' : 'NO'));
                $is_scheduled = 0;
                $window_start = null;
                $window_end = null;
            } else {
                debugLog("✅ Both datetime fields have values, scheduling enabled");
            }
        } else {
            debugLog("Exam not scheduled, skipping datetime processing");
        }

        // Process other settings
        $randomize_questions = isset($_POST['randomize-questions']) ? 1 : 0;
        $randomize_choices = isset($_POST['randomize-choices']) ? 1 : 0;
        $passing_score_type = isset($_POST['passing_score_type']) && !empty($_POST['passing_score_type']) ? $_POST['passing_score_type'] : null;
        $passing_score = isset($_POST['passing_score']) && !empty($_POST['passing_score']) ? (int)$_POST['passing_score'] : null;

        // Process cover image
        $cover_image_path = $default_image_path; // Set default image
        $removeImage = isset($_POST['remove_cover_image']) && $_POST['remove_cover_image'] == '1';
        
        if ($removeImage) {
            // Remove any existing image
            if ($exam_id > 0) {
                $query = "SELECT cover_image FROM exams WHERE exam_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $exam_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    if (!empty($row['cover_image']) && $row['cover_image'] !== $default_image_path && file_exists($row['cover_image'])) {
                        unlink($row['cover_image']);
                    }
                }
            }
            $cover_image_path = $default_image_path;
        } elseif (isset($_FILES['cover-image']) && $_FILES['cover-image']['size'] > 0) {
            // Process new image upload
            $uploadDir = 'uploads/covers/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['cover-image']['name']);
            $targetFilePath = $uploadDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow certain file formats
            $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
            if (in_array(strtolower($fileType), $allowTypes)) {
                // Upload file to server
                if (move_uploaded_file($_FILES['cover-image']['tmp_name'], $targetFilePath)) {
                    $cover_image_path = $targetFilePath;
                } else {
                    throw new Exception("Sorry, there was an error uploading your file.");
                }
            } else {
                throw new Exception("Sorry, only JPG, JPEG, PNG, & GIF files are allowed.");
            }
        } elseif ($exam_id > 0) {
            // Keep existing image if there is one
            $query = "SELECT cover_image FROM exams WHERE exam_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $exam_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $cover_image_path = $row['cover_image'];
            }
        }

        // Start a transaction
        $conn->begin_transaction();
        
        try {
            if ($exam_id > 0) {
                // Update existing exam
                $query = "UPDATE exams SET 
                          title = ?, 
                          description = ?, 
                          instructions = ?,
                          exam_type = ?,
                          duration = ?,
                          is_scheduled = ?,
                          window_start = ?,
                          window_end = ?,
                          randomize_questions = ?,
                          randomize_choices = ?,
                          updated_at = NOW()";
                
                // Handle passing score parameters
                if ($passing_score_type !== null) {
                    $query .= ", passing_score_type = ?";
                } else {
                    $query .= ", passing_score_type = NULL";
                }
                
                if ($passing_score !== null) {
                    $query .= ", passing_score = ?";
                } else {
                    $query .= ", passing_score = NULL";
                }
                
                // Add cover image if needed
                if ($cover_image_path !== null) {
                    $query .= ", cover_image = ?";
                }
                
                $query .= " WHERE exam_id = ?";
                
                // Create parameters array based on conditional fields
                $params = array($quiz_name, $quiz_description, $exam_instructions, $exam_type, $duration, 
                               $is_scheduled, $window_start, $window_end,
                               $randomize_questions, $randomize_choices);
                $types = "ssssiissii";
                
                debugLog("=== DATABASE UPDATE PREPARATION ===");
                debugLog("SQL Query: " . $query);
                debugLog("Parameter types: " . $types);
                debugLog("Parameters being saved:");
                debugLog("  quiz_name: '{$quiz_name}'");
                debugLog("  quiz_description: '{$quiz_description}'");
                debugLog("  exam_instructions: " . ($exam_instructions === null ? 'NULL' : "'{$exam_instructions}'"));
                debugLog("  exam_type: '{$exam_type}'");
                debugLog("  duration: {$duration}");
                debugLog("  is_scheduled: {$is_scheduled}");
                debugLog("  window_start: " . ($window_start === null ? 'NULL' : "'{$window_start}'"));
                debugLog("  window_end: " . ($window_end === null ? 'NULL' : "'{$window_end}'"));
                debugLog("  randomize_questions: {$randomize_questions}");
                debugLog("  randomize_choices: {$randomize_choices}");
                
                // Add passing score parameters if applicable
                if ($passing_score_type !== null) {
                    $params[] = $passing_score_type;
                    $types .= "s";
                }
                
                if ($passing_score !== null) {
                    $params[] = $passing_score;
                    $types .= "i";
                }
                
                // Add cover image parameter if applicable
                if ($cover_image_path !== null) {
                    $params[] = $cover_image_path;
                    $types .= "s";
                }
                
                // Add exam ID parameter
                $params[] = $exam_id;
                $types .= "i";
                
                // Prepare statement with dynamic parameters
                $stmt = $conn->prepare($query);
                
                // Create dynamic parameter binding
                $bind_params = array($types);
                foreach ($params as $key => $value) {
                    $bind_params[] = &$params[$key];
                }
                
                call_user_func_array(array($stmt, 'bind_param'), $bind_params);
                
                // Execute query
                debugLog("=== EXECUTING DATABASE UPDATE ===");
                $result = $stmt->execute();
                
                if ($result) {
                    debugLog("✅ Database update successful");
                    $response['success'] = true;
                    $response['message'] = "Exam updated successfully!";
                    $response['exam_id'] = $exam_id;
                    
                    // Verify what was actually saved
                    $verify_query = "SELECT exam_id, title, is_scheduled, window_start, window_end FROM exams WHERE exam_id = ?";
                    $verify_stmt = $conn->prepare($verify_query);
                    $verify_stmt->bind_param("i", $exam_id);
                    $verify_stmt->execute();
                    $verify_result = $verify_stmt->get_result();
                    
                    if ($verify_row = $verify_result->fetch_assoc()) {
                        debugLog("=== VERIFICATION - WHAT WAS ACTUALLY SAVED ===");
                        debugLog("  exam_id: " . $verify_row['exam_id']);
                        debugLog("  title: " . $verify_row['title']);
                        debugLog("  is_scheduled: " . $verify_row['is_scheduled']);
                        debugLog("  window_start: " . $verify_row['window_start']);
                        debugLog("  window_end: " . $verify_row['window_end']);
                    }

                    // Check if exam is now scheduled and assign to students
                    if ($is_scheduled && function_exists('assignExamToStudents')) {
                        $assignment_result = assignExamToStudents($exam_id, $exam_type);
                        
                        if ($assignment_result && function_exists('getAssignmentStats')) {
                            // Get assignment statistics
                            $stats = getAssignmentStats($exam_id);
                            $response['assignment_stats'] = $stats;
                            $response['message'] = "Exam updated and assigned successfully!";
                        }
                    }
                } else {
                    debugLog("❌ Database update failed: " . $conn->error);
                    debugLog("❌ Statement error: " . $stmt->error);
                    throw new Exception("Error updating exam: " . $conn->error);
                }
            } else {
                // Handle temporary exam creation with minimal fields
                if ($is_temporary) {
                    $query = "INSERT INTO exams (
                        title, description, instructions, exam_type, duration, 
                        randomize_questions, randomize_choices, 
                        cover_image, is_scheduled, 
                        window_start, window_end,
                        created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssiiiisiss",
                        $quiz_name, 
                        $quiz_description, 
                        $exam_instructions,
                        $exam_type,
                        $duration,
                        $randomize_questions, 
                        $randomize_choices, 
                        $cover_image_path, 
                        $is_scheduled,
                        $window_start, 
                        $window_end
                    );
                } else {
                    // Create new exam with all fields
                    $query = "INSERT INTO exams (
                        title, description, instructions, exam_type, duration,
                        randomize_questions, randomize_choices, 
                        cover_image, is_scheduled, window_start, window_end,
                        created_at, updated_at";
                    
                    // Add optional fields if they have values
                    if ($passing_score_type !== null) {
                        $query .= ", passing_score_type";
                    }
                    
                    if ($passing_score !== null) {
                        $query .= ", passing_score";
                    }
                    
                    $query .= ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()";
                    
                    // Add optional placeholders
                    if ($passing_score_type !== null) {
                        $query .= ", ?";
                    }
                    
                    if ($passing_score !== null) {
                        $query .= ", ?";
                    }
                    
                    $query .= ")";
                    
                    // Create parameters array
                    $params = array($quiz_name, $quiz_description, $exam_instructions, $exam_type, $duration,
                                   $randomize_questions, $randomize_choices, 
                                   $cover_image_path, $is_scheduled, $window_start, $window_end);
                    $types = "sssisiisisss";
                    
                    // Add optional parameters
                    if ($passing_score_type !== null) {
                        $params[] = $passing_score_type;
                        $types .= "s";
                    }
                    
                    if ($passing_score !== null) {
                        $params[] = $passing_score;
                        $types .= "i";
                    }
                    
                    // Prepare statement with dynamic parameters
                    $stmt = $conn->prepare($query);
                    
                    // Create dynamic parameter binding
                    $bind_params = array($types);
                    foreach ($params as $key => $value) {
                        $bind_params[] = &$params[$key];
                    }
                    
                    call_user_func_array(array($stmt, 'bind_param'), $bind_params);
                }
                
                // Execute the insert query
                $result = $stmt->execute();
                
                if ($result) {
                    $exam_id = $conn->insert_id;
                    $response['success'] = true;
                    $response['message'] = $is_temporary ? "Temporary exam created successfully" : "Exam created successfully!";
                    $response['exam_id'] = $exam_id;
                } else {
                    throw new Exception("Error creating exam: " . $conn->error);
                }
            }

            // Only assign exam to students if it's scheduled and not a temporary exam
            if ($is_scheduled && !$is_temporary && function_exists('assignExamToStudents')) {
                // Assign exam to appropriate students
                $assignment_result = assignExamToStudents($exam_id, $exam_type);
                
                if ($assignment_result && function_exists('getAssignmentStats')) {
                    // Get assignment statistics
                    $stats = getAssignmentStats($exam_id);
                    $response['assignment_stats'] = $stats;
                    $response['message'] = "Exam " . ($exam_id > 0 ? "updated" : "created") . " and assigned successfully!";
                }
            } else if (!$is_temporary) {
                $response['message'] = "Exam " . ($exam_id > 0 ? "updated" : "created") . " successfully! (Not assigned to students because no schedule was set)";
            }
            
            // Commit the transaction
            $conn->commit();
            
            $response['success'] = true;
            $response['exam_id'] = $exam_id;
            
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            throw $e;
        }
    } else {
        throw new Exception("Invalid request method. Only POST is supported.");
    }
} catch (Exception $e) {
    debugLog("❌ MAIN EXCEPTION CAUGHT: " . $e->getMessage());
    debugLog("❌ Stack trace: " . $e->getTraceAsString());
    debugLog("=== EXAM SAVE PROCESS FAILED ===");
    
    // Clean any output buffered
    ob_end_clean();
    
    // Set the proper content type
    header('Content-Type: application/json');
    
    // Return error JSON response
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Clear any unwanted output that might have been captured
ob_end_clean();

// Set content type
header('Content-Type: application/json');

// Final debug log
debugLog("=== EXAM SAVE PROCESS COMPLETED ===");
debugLog("Final response: " . json_encode($response));
debugLog("========================================================================================");

// Return the response
echo json_encode($response);
exit;
?>
