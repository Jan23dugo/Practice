<?php
session_start();
include('config/config.php');

// Check if request is POST and has required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id']) && isset($_POST['exam_id'])) {
    $question_id = (int)$_POST['question_id'];
    $exam_id = (int)$_POST['exam_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get the original question
        $query = "SELECT * FROM questions WHERE question_id = ? AND exam_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $question_id, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Question not found");
        }
        
        $original_question = $result->fetch_assoc();
        
        // Get the highest position value for this exam
        $query = "SELECT MAX(position) as max_position FROM questions WHERE exam_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $position = ($row['max_position'] !== null) ? $row['max_position'] + 1 : 0;
        
        // Insert the duplicated question
        $query = "INSERT INTO questions (exam_id, question_text, question_type, points, position, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issii", $exam_id, $original_question['question_text'], 
                          $original_question['question_type'], $original_question['points'], $position);
        
        if (!$stmt->execute()) {
            throw new Exception("Error duplicating question: " . $stmt->error);
        }
        
        $new_question_id = $conn->insert_id;
        
        // Get the original answers
        $query = "SELECT * FROM answers WHERE question_id = ? ORDER BY position ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $answers_result = $stmt->get_result();
        
        // Duplicate the answers
        while ($answer = $answers_result->fetch_assoc()) {
            $query = "INSERT INTO answers (question_id, answer_text, is_correct, position) 
                      VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isii", $new_question_id, $answer['answer_text'], 
                              $answer['is_correct'], $answer['position']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error duplicating answer: " . $stmt->error);
            }
        }
        
        // If it's a programming question, duplicate the programming data
        if ($original_question['question_type'] === 'programming') {
            // Get the original programming data
            $query = "SELECT * FROM programming_questions WHERE question_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $programming_data = $result->fetch_assoc();
                
                // Insert the duplicated programming data
                $query = "INSERT INTO programming_questions (question_id, starter_code, language) 
                          VALUES (?, ?, ?)";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iss", $new_question_id, $programming_data['starter_code'], 
                                  $programming_data['language']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error duplicating programming data: " . $stmt->error);
                }
                
                $new_programming_id = $conn->insert_id;
                
                // Get the original test cases
                $query = "SELECT * FROM test_cases WHERE programming_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $programming_data['programming_id']);
                $stmt->execute();
                $test_cases_result = $stmt->get_result();
                
                // Duplicate the test cases
                while ($test_case = $test_cases_result->fetch_assoc()) {
                    $query = "INSERT INTO test_cases (programming_id, input, expected_output, is_hidden) 
                              VALUES (?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("issi", $new_programming_id, $test_case['input'], 
                                      $test_case['expected_output'], $test_case['is_hidden']);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error duplicating test case: " . $stmt->error);
                    }
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Question duplicated successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Return error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} else {
    // Return error for invalid request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
