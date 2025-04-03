<?php
// Include database connection
include('config/config.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if exam ID is provided
if (!isset($_GET['exam_id']) || empty($_GET['exam_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Exam ID is required'
    ]);
    exit;
}

$exam_id = (int)$_GET['exam_id'];

try {
    // Get exam details
    $query = "SELECT * FROM exams WHERE exam_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam_result = $stmt->get_result();
    
    if ($exam_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Exam not found'
        ]);
        exit;
    }
    
    $exam = $exam_result->fetch_assoc();
    
    // Get questions for this exam
    $query = "SELECT q.*, pq.starter_code, pq.language 
             FROM questions q 
             LEFT JOIN programming_questions pq ON q.question_id = pq.question_id 
             WHERE q.exam_id = ? 
             ORDER BY q.position ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $questions_result = $stmt->get_result();
    
    $questions = [];
    
    while ($question = $questions_result->fetch_assoc()) {
        // Format the question data
        $formatted_question = [
            'question_id' => $question['question_id'],
            'question_text' => $question['question_text'],
            'question_type' => $question['question_type'],
            'points' => $question['points'],
            'position' => $question['position']
        ];

        // Add programming-specific details if it's a programming question
        if ($question['question_type'] === 'programming') {
            $formatted_question['starter_code'] = $question['starter_code'];
            $formatted_question['language'] = $question['language'];
            
            // Get test cases for programming questions
            $test_query = "SELECT tc.* FROM test_cases tc 
                          JOIN programming_questions pq ON tc.programming_id = pq.programming_id 
                          WHERE pq.question_id = ?";
            $stmt = $conn->prepare($test_query);
            $stmt->bind_param("i", $question['question_id']);
            $stmt->execute();
            $test_cases_result = $stmt->get_result();
            
            $test_cases = [];
            while ($test_case = $test_cases_result->fetch_assoc()) {
                $test_cases[] = [
                    'input' => $test_case['input'],
                    'expected_output' => $test_case['expected_output'],
                    'is_hidden' => $test_case['is_hidden'],
                    'description' => $test_case['description']
                ];
            }
            $formatted_question['test_cases'] = $test_cases;
        } else {
            // Get answers for multiple choice or true/false questions
            $answer_query = "SELECT answer_id, answer_text, is_correct, position 
                           FROM answers 
                           WHERE question_id = ? 
                           ORDER BY position ASC";
            $stmt = $conn->prepare($answer_query);
            $stmt->bind_param("i", $question['question_id']);
            $stmt->execute();
            $answers_result = $stmt->get_result();
            
            $answers = [];
            while ($answer = $answers_result->fetch_assoc()) {
                $answers[] = [
                    'text' => $answer['answer_text'],
                    'is_correct' => (bool)$answer['is_correct'],
                    'position' => $answer['position']
                ];
            }
            $formatted_question['answers'] = $answers;
        }
        
        $questions[] = $formatted_question;
    }
    
    // Return exam and questions data
    echo json_encode([
        'success' => true,
        'exam' => [
            'title' => $exam['title'],
            'description' => $exam['description'],
            'exam_type' => $exam['exam_type'],
            'is_scheduled' => $exam['is_scheduled'],
            'scheduled_date' => $exam['scheduled_date'],
            'scheduled_time' => $exam['scheduled_time'],
            'randomize_questions' => $exam['randomize_questions'],
            'randomize_choices' => $exam['randomize_choices']
        ],
        'questions' => $questions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
