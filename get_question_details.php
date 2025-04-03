<?php
// Include database connection
include('config/config.php');

if (isset($_GET['question_id'])) {
    $question_id = (int)$_GET['question_id'];
    
    try {
        // Get question details
        $query = "SELECT * FROM question_bank WHERE question_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $question_result = $stmt->get_result();
        
        if ($question_result->num_rows > 0) {
            $question = $question_result->fetch_assoc();
            
            // Get answers if applicable
            $answers = [];
            if ($question['question_type'] === 'multiple-choice' || $question['question_type'] === 'true-false') {
                $query = "SELECT * FROM question_bank_answers WHERE question_id = ? ORDER BY position";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $question_id);
                $stmt->execute();
                $answers_result = $stmt->get_result();
                
                while ($answer = $answers_result->fetch_assoc()) {
                    $answers[] = $answer;
                }
            }
            
            // Get programming details if applicable
            $programming = null;
            $test_cases = [];
            if ($question['question_type'] === 'programming') {
                $query = "SELECT * FROM question_bank_programming WHERE question_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $question_id);
                $stmt->execute();
                $programming_result = $stmt->get_result();
                
                if ($programming_result->num_rows > 0) {
                    $programming = $programming_result->fetch_assoc();
                    
                    // Get test cases
                    $query = "SELECT * FROM question_bank_test_cases WHERE programming_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $programming['programming_id']);
                    $stmt->execute();
                    $test_cases_result = $stmt->get_result();
                    
                    while ($test_case = $test_cases_result->fetch_assoc()) {
                        $test_cases[] = $test_case;
                    }
                }
            }
            
            // Prepare response
            $response = [
                'success' => true,
                'question' => [
                    'question_id' => $question['question_id'],
                    'question_text' => $question['question_text'],
                    'question_type' => $question['question_type'],
                    'category' => $question['category'],
                    'points' => $question['points'],
                    'answers' => $answers,
                    'programming' => $programming,
                    'test_cases' => $test_cases
                ]
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Question not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Question ID is required']);
}
?>
