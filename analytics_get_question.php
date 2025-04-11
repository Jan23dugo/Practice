<?php
include('config/config.php');

header('Content-Type: application/json');

try {
    $question_id = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;
    
    if ($question_id <= 0) {
        throw new Exception('Invalid question ID');
    }

    // Get question details
    $query = "SELECT q.*, e.title as exam_title 
              FROM questions q 
              JOIN exams e ON q.exam_id = e.exam_id 
              WHERE q.question_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $question = $result->fetch_assoc();
    
    if (!$question) {
        throw new Exception('Question not found');
    }

    $response = [
        'success' => true,
        'data' => [
            'question_id' => $question['question_id'],
            'exam_id' => $question['exam_id'],
            'exam_title' => $question['exam_title'],
            'question_text' => $question['question_text'],
            'question_type' => $question['question_type'],
            'points' => $question['points']
        ]
    ];
    
    // Get additional details based on question type
    switch ($question['question_type']) {
        case 'multiple-choice':
            // Get multiple choice options
            $query = "SELECT answer_text, is_correct, position 
                     FROM answers 
                     WHERE question_id = ? 
                     ORDER BY position";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $choices = [];
            while ($row = $result->fetch_assoc()) {
                $choices[] = [
                    'text' => $row['answer_text'],
                    'is_correct' => (bool)$row['is_correct'],
                    'position' => $row['position']
                ];
            }
            $response['data']['choices'] = $choices;
            break;

        case 'true-false':
            // Get true/false answer
            $query = "SELECT answer_text, is_correct 
                     FROM answers 
                     WHERE question_id = ? 
                     ORDER BY position LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $answer = $result->fetch_assoc();
            
            $response['data']['correct_answer'] = $answer['is_correct'] ? 'True' : 'False';
            break;

        case 'programming':
            // Get programming question details
            $query = "SELECT pq.*, tc.input, tc.expected_output, tc.is_hidden, tc.description 
                     FROM programming_questions pq 
                     LEFT JOIN test_cases tc ON pq.programming_id = tc.programming_id 
                     WHERE pq.question_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $test_cases = [];
            $programming_details = null;
            
            while ($row = $result->fetch_assoc()) {
                if (!$programming_details) {
                    $programming_details = [
                        'language' => $row['language'] ?? 'python',
                        'starter_code' => $row['starter_code'] ?? ''
                    ];
                }
                
                if ($row['input'] !== null) {
                    $test_cases[] = [
                        'input' => $row['input'],
                        'expected_output' => $row['expected_output'],
                        'is_hidden' => (bool)$row['is_hidden'],
                        'description' => $row['description']
                    ];
                }
            }
            
            $response['data']['programming'] = $programming_details;
            $response['data']['test_cases'] = $test_cases;
            break;
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>