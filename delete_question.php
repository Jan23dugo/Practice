<?php
session_start();
include('config/config.php');

// Check if request is POST and has required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id'])) {
    $question_id = (int)$_POST['question_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Check if it's a programming question
        $query = "SELECT question_type FROM questions WHERE question_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Question not found");
        }
        
        $question = $result->fetch_assoc();
        
        // If it's a programming question, delete related data
        if ($question['question_type'] === 'programming') {
            // Get programming_id
            $query = "SELECT programming_id FROM programming_questions WHERE question_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $programming = $result->fetch_assoc();
                $programming_id = $programming['programming_id'];
                
                // Delete test cases
                $query = "DELETE FROM test_cases WHERE programming_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $programming_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error deleting test cases: " . $stmt->error);
                }
                
                // Delete programming data
                $query = "DELETE FROM programming_questions WHERE programming_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $programming_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error deleting programming data: " . $stmt->error);
                }
            }
        }
        
        // Delete answers
        $query = "DELETE FROM answers WHERE question_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $question_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting answers: " . $stmt->error);
        }
        
        // Delete the question
        $query = "DELETE FROM questions WHERE question_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $question_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting question: " . $stmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
        
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
