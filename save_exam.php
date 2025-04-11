<?php
// Capture output instead of sending directly to browser
ob_start();

// Temporary debugging code - in a conditional block
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents('form_debug.txt', 
        date('Y-m-d H:i:s') . "\n" .
        "POST data:\n" . 
        print_r($_POST, true) . 
        "\n\n" . 
        "FILES data:\n" . 
        print_r($_FILES, true) . 
        "\n--------------------\n"
    , FILE_APPEND);
}

session_start();
include('config/config.php');
include('assign_exam.php'); // Include the assignment functionality

// Enable error reporting but suppress display
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set header to return JSON - moved after includes
header('Content-Type: application/json');

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
        // Get form data
        $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
        $quiz_name = isset($_POST['quiz-name']) ? $_POST['quiz-name'] : 'Untitled Quiz';
        $quiz_description = isset($_POST['quiz-description']) ? $_POST['quiz-description'] : '';
        $exam_type = isset($_POST['exam-type']) ? $_POST['exam-type'] : 'tech'; // Default to tech
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 60; // Default 60 minutes
        
        // Check if this is a temporary exam creation (minimal data)
        $is_temporary = !isset($_POST['is_scheduled']) && !isset($_POST['randomize-questions']) && 
                        !isset($_POST['randomize-choices']) && !isset($_POST['passing_score_type']);
        
        // Validate duration
        if ($duration < 1 || $duration > 480) {
            throw new Exception("Duration must be between 1 and 480 minutes");
        }
        
        // Process scheduling data
        $is_scheduled = isset($_POST['is_scheduled']) ? 1 : 0;
        $scheduled_date = null;
        $scheduled_time = null;

        if ($is_scheduled) {
            if (isset($_POST['scheduled_date']) && !empty($_POST['scheduled_date'])) {
                $scheduled_date = $_POST['scheduled_date'];
            }
            
            if (isset($_POST['scheduled_time']) && !empty($_POST['scheduled_time'])) {
                $scheduled_time = $_POST['scheduled_time'];
            }
            
            // If either date or time is missing, set is_scheduled to 0
            if (empty($scheduled_date) || empty($scheduled_time)) {
                $is_scheduled = 0;
                $scheduled_date = null;
                $scheduled_time = null;
            }
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

        // Log temporary exam creation for debugging
        if ($is_temporary) {
            file_put_contents('temp_exam_debug.txt', 
                date('Y-m-d H:i:s') . " - Creating temporary exam with minimal data\n" .
                "quiz_name: " . $quiz_name . "\n" .
                "exam_type: " . $exam_type . "\n" .
                "duration: " . $duration . "\n",
                FILE_APPEND
            );
        }

        // Start a transaction
        $conn->begin_transaction();
        
        try {
            if ($exam_id > 0) {
                // Update existing exam
                $query = "UPDATE exams SET 
                          title = ?, 
                          description = ?, 
                          exam_type = ?,
                          duration = ?,
                          is_scheduled = ?,
                          scheduled_date = ?,
                          scheduled_time = ?,
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
                $params = array($quiz_name, $quiz_description, $exam_type, $duration, 
                               $is_scheduled, $scheduled_date, $scheduled_time,
                               $randomize_questions, $randomize_choices);
                $types = "sssiissii";
                
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
                $result = $stmt->execute();
                
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Exam updated successfully!";
                    $response['exam_id'] = $exam_id;

                    // Check if exam is now scheduled and assign to students
                    if ($is_scheduled) {
                        $assignment_result = assignExamToStudents($exam_id, $exam_type);
                        
                        if ($assignment_result) {
                            // Get assignment statistics
                            $stats = getAssignmentStats($exam_id);
                            $response['assignment_stats'] = $stats;
                            $response['message'] = "Exam updated and assigned successfully!";
                        }
                    }
                } else {
                    throw new Exception("Error updating exam: " . $conn->error);
                }
            } else {
                // Handle temporary exam creation with minimal fields
                if ($is_temporary) {
                    $query = "INSERT INTO exams (
                        title, description, exam_type, duration, 
                        randomize_questions, randomize_choices, 
                        cover_image, is_scheduled, 
                        created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssiisis",
                        $quiz_name, 
                        $quiz_description, 
                        $exam_type,
                        $duration,
                        $randomize_questions, 
                        $randomize_choices, 
                        $cover_image_path, 
                        $is_scheduled
                    );
                } else {
                    // Create new exam with all fields
                    $query = "INSERT INTO exams (
                        title, description, exam_type, duration,
                        randomize_questions, randomize_choices, 
                        cover_image, is_scheduled, scheduled_date, scheduled_time,
                        created_at, updated_at";
                    
                    // Add optional fields if they have values
                    if ($passing_score_type !== null) {
                        $query .= ", passing_score_type";
                    }
                    
                    if ($passing_score !== null) {
                        $query .= ", passing_score";
                    }
                    
                    $query .= ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()";
                    
                    // Add optional placeholders
                    if ($passing_score_type !== null) {
                        $query .= ", ?";
                    }
                    
                    if ($passing_score !== null) {
                        $query .= ", ?";
                    }
                    
                    $query .= ")";
                    
                    // Create parameters array
                    $params = array($quiz_name, $quiz_description, $exam_type, $duration,
                                   $randomize_questions, $randomize_choices, 
                                   $cover_image_path, $is_scheduled, $scheduled_date, $scheduled_time);
                    $types = "sssiississs";
                    
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
                    
                    // Log successful temporary exam creation
                    if ($is_temporary) {
                        file_put_contents('temp_exam_debug.txt',
                            "Successfully created temporary exam with ID: " . $exam_id . "\n",
                            FILE_APPEND
                        );
                    }
                } else {
                    throw new Exception("Error creating exam: " . $conn->error);
                }
            }

            // Only assign exam to students if it's scheduled and not a temporary exam
            if ($is_scheduled && !$is_temporary) {
                // Assign exam to appropriate students
                $assignment_result = assignExamToStudents($exam_id, $exam_type);
                
                if ($assignment_result) {
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
    // Clean any output buffered
    ob_end_clean();
    
    // Set the proper content type
    header('Content-Type: application/json');
    
    // Log the error
    file_put_contents('error_log.txt', 
        date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n" . 
        "Trace: " . $e->getTraceAsString() . "\n" .
        "--------------------\n",
        FILE_APPEND
    );
    
    // Return error JSON response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

// Clear any unwanted output that might have been captured
ob_end_clean();

// Set content type
header('Content-Type: application/json');

// Return the response
echo json_encode($response);
exit;
?>
