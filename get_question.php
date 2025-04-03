<?php
include('config/config.php');

header('Content-Type: application/json');

try {
    if (!isset($_GET['question_id'])) {
        throw new Exception('Question ID is required');
    }

    $question_id = (int)$_GET['question_id'];
    
    // Get question details
    $query = "SELECT * FROM questions WHERE question_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Question not found');
    }

    $question = $result->fetch_assoc();
    
    // Get answers for multiple choice questions
    $answers = [];
    if ($question['question_type'] !== 'programming') {
        $query = "SELECT * FROM answers WHERE question_id = ? ORDER BY position, answer_id";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $answers_result = $stmt->get_result();
        
        while ($answer = $answers_result->fetch_assoc()) {
            $answers[] = $answer;
        }
    }
    
    // Get programming details
    $query = "SELECT * FROM programming_questions WHERE question_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $programming_result = $stmt->get_result();
    $programming = $programming_result->fetch_assoc();
    
    // Get test cases
    if ($programming) {
        $query = "SELECT * FROM test_cases WHERE programming_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $programming['programming_id']);
        $stmt->execute();
        $test_cases_result = $stmt->get_result();
        
        $test_cases = [];
        while ($test_case = $test_cases_result->fetch_assoc()) {
            $test_cases[] = [
                'input' => $test_case['input'],
                'expected_output' => $test_case['expected_output'],
                'is_hidden' => (int)$test_case['is_hidden'],
                'description' => $test_case['description'] ?? ''
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'question' => $question,
        'answers' => $answers,
        'programming' => $programming,
        'test_cases' => $test_cases ?? []
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
