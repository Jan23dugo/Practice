<?php
// Capture output instead of sending directly to browser
ob_start();

// Temporary debugging code
file_put_contents('form_debug.txt', 
    date('Y-m-d H:i:s') . "\n" .
    "POST data:\n" . 
    print_r($_POST, true) . 
    "\n\n" . 
    "FILES data:\n" . 
    print_r($_FILES, true) . 
    "\n--------------------\n"
, FILE_APPEND);

session_start();
include('config/config.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set header to return JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'exam_id' => null
];

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
        $quiz_name = isset($_POST['quiz-name']) ? $_POST['quiz-name'] : 'Untitled Quiz';
        $quiz_description = isset($_POST['quiz-description']) ? $_POST['quiz-description'] : '';
        $exam_type = isset($_POST['exam-type']) ? $_POST['exam-type'] : '';
        
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
        $passing_score_type = isset($_POST['passing_score_type']) ? $_POST['passing_score_type'] : null;
        $passing_score = isset($_POST['passing_score']) && !empty($_POST['passing_score']) ? (int)$_POST['passing_score'] : null;

        // Process cover image
        $cover_image_path = null;
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
                    if (!empty($row['cover_image']) && file_exists($row['cover_image'])) {
                        unlink($row['cover_image']);
                    }
                }
            }
            $cover_image_path = null;
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

        // Debug logging - create a file to track what's being submitted
        $debug_info = "Form submission at " . date('Y-m-d H:i:s') . "\n";
        $debug_info .= "is_scheduled: " . ($is_scheduled ? 'YES' : 'NO') . "\n";
        $debug_info .= "scheduled_date: " . ($scheduled_date ?? 'NULL') . "\n";
        $debug_info .= "scheduled_time: " . ($scheduled_time ?? 'NULL') . "\n";
        $debug_info .= "POST data: " . print_r($_POST, true) . "\n";
        $debug_info .= "------------------------------\n";
        file_put_contents('schedule_debug.txt', $debug_info, FILE_APPEND);

        // Start a transaction
        $conn->begin_transaction();
        
        try {
            if ($exam_id > 0) {
                // Update existing exam
                $query = "UPDATE exams SET 
                          title = ?, 
                          description = ?, 
                          exam_type = ?,
                          is_scheduled = ?,
                          scheduled_date = ?,
                          scheduled_time = ?,
                          randomize_questions = ?,
                          randomize_choices = ?,
                          passing_score_type = ?,
                          passing_score = ?,
                          updated_at = NOW()";
                
                if ($cover_image_path !== null) {
                    $query .= ", cover_image = ?";
                }
                
                $query .= " WHERE exam_id = ?";
                
                $stmt = $conn->prepare($query);
                
                if ($cover_image_path !== null) {
                    $stmt->bind_param(
                        "sssissiisssi",
                        $quiz_name, 
                        $quiz_description, 
                        $exam_type,
                        $is_scheduled,
                        $scheduled_date,
                        $scheduled_time,
                        $randomize_questions,
                        $randomize_choices,
                        $passing_score_type,
                        $passing_score,
                        $cover_image_path,
                        $exam_id
                    );
                } else {
                    $stmt->bind_param(
                        "sssissiissii",
                        $quiz_name, 
                        $quiz_description, 
                        $exam_type,
                        $is_scheduled,
                        $scheduled_date,
                        $scheduled_time,
                        $randomize_questions,
                        $randomize_choices,
                        $passing_score_type,
                        $passing_score,
                        $exam_id
                    );
                }
                
                // Execute the query
                $result = $stmt->execute();
                
                // Log the result
                file_put_contents('direct_query_debug.txt', 
                    "Query execution result: " . ($result ? "SUCCESS" : "FAILURE") . "\n" .
                    "Error (if any): " . $conn->error . "\n" .
                    "Affected rows: " . $stmt->affected_rows . "\n" .
                    "bind_param types: " . ($cover_image_path !== null ? "sssissiisssi" : "sssissiissii") . "\n" .
                    "--------------------\n",
                    FILE_APPEND
                );
                
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = "Exam updated successfully!";
                    $response['exam_id'] = $exam_id;
                } else {
                    throw new Exception("Error updating exam: " . $conn->error);
                }
            } else {
                // Create new exam
                $query = "INSERT INTO exams (title, description, exam_type, randomize_questions, randomize_choices, passing_score_type, passing_score, cover_image, is_scheduled, scheduled_date, scheduled_time) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssiiississ",
                    $quiz_name, 
                    $quiz_description, 
                    $exam_type, 
                    $randomize_questions, 
                    $randomize_choices, 
                    $passing_score_type, 
                    $passing_score, 
                    $cover_image_path, 
                    $is_scheduled, 
                    $scheduled_date,
                    $scheduled_time
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Error creating exam: " . $conn->error);
                }
                
                $exam_id = $conn->insert_id;
                
                $response['success'] = true;
                $response['message'] = "Exam created successfully!";
                $response['exam_id'] = $exam_id;
            }
            
            // Commit the transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            throw $e;
        }
    }
} catch (Exception $e) {
    $response['message'] = "Error: " . $e->getMessage();
}

// Clear any output buffer
ob_end_clean();

// Set proper content type and ensure no HTML is output
header('Content-Type: application/json');

// Return the response
echo json_encode($response);
exit;
?>
