<?php
session_start();
include('config/config.php');

// Check if this is an edit or new question
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'new';
$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
$question_type = isset($_POST['question_type']) ? $_POST['question_type'] : '';
$question_text = isset($_POST['question']) ? $_POST['question'] : '';
$points = isset($_POST['points']) ? (int)$_POST['points'] : 1;

// Clean up question text but preserve formatting
$question_text = trim($question_text);
// Define allowed HTML tags
$allowed_tags = '<strong><em><u><sup><sub><s><br><p><span>';
// Strip all tags except allowed ones
$question_text = strip_tags($question_text, $allowed_tags);
// Remove empty paragraphs
$question_text = preg_replace('/<p>\s*<\/p>/', '', $question_text);
// Convert multiple spaces to single space
$question_text = preg_replace('/\s+/', ' ', $question_text);
// Final trim
$question_text = trim($question_text);

// Validate required fields
if (empty($exam_id) || empty($question_type) || empty($question_text)) {
    die("Missing required fields");
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Debug log
    error_log("Saving " . ($question_id > 0 ? "existing" : "new") . " {$question_type} question. Exam ID: {$exam_id}");
    if ($question_type === 'programming') {
        $debug_language = isset($_POST['programming_language']) ? $_POST['programming_language'] : 'NOT SET';
        error_log("Programming language: {$debug_language}");
    }
    
    if ($question_id > 0) {
        // Update existing question
        $query = "UPDATE questions SET 
                  question_text = ?,
                  points = ?,
                  updated_at = NOW()
                  WHERE question_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $question_text, $points, $question_id);
        $stmt->execute();
        
        // Delete existing answers
        $query = "DELETE FROM answers WHERE question_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
    } else {
        // Insert new question
        $query = "INSERT INTO questions (exam_id, question_type, question_text, points, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issi", $exam_id, $question_type, $question_text, $points);
        $stmt->execute();
        
        $question_id = $conn->insert_id;
    }
    
    // Process answers based on question type
    if ($question_type === 'multiple-choice') {
        // Handle multiple choice questions
        if (isset($_POST['choices']) && is_array($_POST['choices'])) {
            $choices = $_POST['choices'];
            $correct_index = isset($_POST['correct']) ? (int)$_POST['correct'] : -1;
            
            for ($i = 0; $i < count($choices); $i++) {
                $is_correct = ($i === $correct_index) ? 1 : 0;
                $answer_text = $choices[$i];
                
                $query = "INSERT INTO answers (question_id, answer_text, is_correct, position) 
                          VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("isii", $question_id, $answer_text, $is_correct, $i);
                $stmt->execute();
            }
        }
    } elseif ($question_type === 'true-false') {
        // Handle true/false questions
        if (isset($_POST['correct_answer'])) {
            $correct_answer = $_POST['correct_answer']; // This should be "True" or "False"
            
            // Insert True answer
            $is_true_correct = ($correct_answer === 'True') ? 1 : 0;
            $query = "INSERT INTO answers (question_id, answer_text, is_correct, position) 
                      VALUES (?, 'True', ?, 0)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $question_id, $is_true_correct);
            $stmt->execute();
            
            // Insert False answer
            $is_false_correct = ($correct_answer === 'False') ? 1 : 0;
            $query = "INSERT INTO answers (question_id, answer_text, is_correct, position) 
                      VALUES (?, 'False', ?, 1)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $question_id, $is_false_correct);
            $stmt->execute();
        } else {
            throw new Exception("Missing correct answer for true/false question");
        }
    } elseif ($question_type === 'programming') {
        // Handle programming questions
        $starter_code = isset($_POST['starter_code']) ? $_POST['starter_code'] : '';
        $language = isset($_POST['programming_language']) ? $_POST['programming_language'] : 'python'; // Get selected language from form
        
        // Check if this is an update to an existing programming question
        $check_query = "SELECT programming_id FROM programming_questions WHERE question_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $question_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing programming question
            $row = $result->fetch_assoc();
            $programming_id = $row['programming_id'];
            
            $update_query = "UPDATE programming_questions 
                           SET starter_code = ?, language = ? 
                           WHERE programming_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssi", $starter_code, $language, $programming_id);
            $update_stmt->execute();
            
            // Delete existing test cases
            $delete_query = "DELETE FROM test_cases WHERE programming_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $programming_id);
            $delete_stmt->execute();
        } else {
            // Insert new programming question
            $query = "INSERT INTO programming_questions (question_id, starter_code, language) 
                      VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $question_id, $starter_code, $language);
            $stmt->execute();
            
            $programming_id = $conn->insert_id;
        }
        
        // Then save all test cases using the programming_id
        if (isset($_POST['test_input']) && isset($_POST['test_output'])) {
            $test_inputs = $_POST['test_input'];
            $test_outputs = $_POST['test_output'];
            $is_hidden = isset($_POST['is_hidden']) ? $_POST['is_hidden'] : array();
            $hidden_descriptions = isset($_POST['hidden_description']) ? $_POST['hidden_description'] : array();
            
            for ($i = 0; $i < count($test_inputs); $i++) {
                $input = $test_inputs[$i];
                $output = $test_outputs[$i];
                $hidden = in_array($i, array_keys($is_hidden)) ? 1 : 0;
                $hidden_desc = isset($hidden_descriptions[$i]) ? $hidden_descriptions[$i] : '';
                
                $query = "INSERT INTO test_cases 
                          (programming_id, input, expected_output, is_hidden, description) 
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("issis", $programming_id, $input, $output, $hidden, $hidden_desc);
                $stmt->execute();
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Redirect back to quiz editor
    header("Location: quiz_editor.php?exam_id=" . $exam_id);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    die("Error: " . $e->getMessage());
}
?>
