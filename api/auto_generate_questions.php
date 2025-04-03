<?php
// Ensure no HTML errors are output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set response header to JSON
header('Content-Type: application/json');

try {
    // Include database configuration
    require_once '../config/config.php';
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validate input
    if (!$data || !isset($data['exam_id']) || !isset($data['total_questions']) || !isset($data['question_types'])) {
        throw new Exception('Missing required parameters');
    }
    
    // Extract parameters
    $examId = (int)$data['exam_id'];
    $totalQuestions = (int)$data['total_questions'];
    $questionTypes = $data['question_types'];
    $category = isset($data['category']) && !empty($data['category']) ? $data['category'] : null;
    $pointsPerQuestion = isset($data['points_per_question']) ? (int)$data['points_per_question'] : 1;
    
    // Validate parameters
    if ($examId <= 0) {
        throw new Exception('Invalid exam ID');
    }
    
    if ($totalQuestions <= 0 || $totalQuestions > 50) {
        throw new Exception('Number of questions must be between 1 and 50');
    }
    
    if (empty($questionTypes)) {
        throw new Exception('At least one question type must be selected');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Build query to get random questions from question bank
    $query = "SELECT * FROM question_bank WHERE 1=1";
    $params = [];
    $types = "";
    
    // Add type filter
    if (!empty($questionTypes)) {
        $typeConditions = [];
        foreach ($questionTypes as $type) {
            // Convert UI-friendly names to database values if needed
            switch ($type) {
                case 'multiple_choice':
                    $typeConditions[] = "question_type = 'multiple-choice'";
                    break;
                case 'true_false':
                    $typeConditions[] = "question_type = 'true-false'";
                    break;
                case 'programming':
                    $typeConditions[] = "question_type = 'programming'";
                    break;
                default:
                    $typeConditions[] = "question_type = '$type'";
            }
        }
        if (!empty($typeConditions)) {
            $query .= " AND (" . implode(" OR ", $typeConditions) . ")";
        }
    }
    
    // Add category filter if specified
    if ($category !== null) {
        $query .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    // Add order and limit
    $query .= " ORDER BY RAND() LIMIT ?";
    $params[] = $totalQuestions;
    $types .= "i";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    // Bind parameters if any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if we have enough questions
    if ($result->num_rows < $totalQuestions) {
        throw new Exception("Not enough questions available. Requested: $totalQuestions, Available: " . $result->num_rows);
    }
    
    // Get the next position for questions in the exam
    $positionQuery = "SELECT COALESCE(MAX(position), 0) as max_position FROM questions WHERE exam_id = ?";
    $positionStmt = $conn->prepare($positionQuery);
    $positionStmt->bind_param('i', $examId);
    $positionStmt->execute();
    $positionResult = $positionStmt->get_result();
    $positionRow = $positionResult->fetch_assoc();
    $nextPosition = (int)$positionRow['max_position'] + 1;
    
    // Add questions to the exam
    $addedQuestions = 0;
    
    while ($question = $result->fetch_assoc()) {
        $questionId = $question['question_id'];
        $questionType = $question['question_type'];
        $questionText = $question['question_text'];
        $points = isset($data['points_per_question']) ? (int)$data['points_per_question'] : (int)$question['points'];
        
        // Insert question into the exam
        $insertQuery = "INSERT INTO questions (exam_id, question_type, question_text, points, position) 
                        VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param('issii', $examId, $questionType, $questionText, $points, $nextPosition);
        $insertStmt->execute();
        
        // Get the new question ID
        $newQuestionId = $conn->insert_id;
        
        // Handle different question types
        if ($questionType === 'multiple-choice' || $questionType === 'true-false') {
            // Copy answers from question bank
            $answersQuery = "SELECT * FROM question_bank_answers WHERE question_id = ?";
            $answersStmt = $conn->prepare($answersQuery);
            $answersStmt->bind_param('i', $questionId);
            $answersStmt->execute();
            $answersResult = $answersStmt->get_result();
            
            while ($answer = $answersResult->fetch_assoc()) {
                $answerText = $answer['answer_text'];
                $isCorrect = $answer['is_correct'];
                $position = $answer['position'];
                
                $insertAnswerQuery = "INSERT INTO answers (question_id, answer_text, is_correct, position) 
                                     VALUES (?, ?, ?, ?)";
                $insertAnswerStmt = $conn->prepare($insertAnswerQuery);
                $insertAnswerStmt->bind_param('isii', $newQuestionId, $answerText, $isCorrect, $position);
                $insertAnswerStmt->execute();
            }
        } elseif ($questionType === 'programming') {
            // Copy programming details from question bank
            $programmingQuery = "SELECT * FROM question_bank_programming WHERE question_id = ?";
            $programmingStmt = $conn->prepare($programmingQuery);
            $programmingStmt->bind_param('i', $questionId);
            $programmingStmt->execute();
            $programmingResult = $programmingStmt->get_result();
            
            if ($programmingRow = $programmingResult->fetch_assoc()) {
                $language = $programmingRow['language'];
                $starterCode = $programmingRow['starter_code'];
                
                // Note: solution_code column doesn't exist in your schema
                // Using NULL or empty string as a placeholder
                $solutionCode = "";
                
                $insertProgrammingQuery = "INSERT INTO programming_questions (question_id, language, starter_code) 
                                          VALUES (?, ?, ?)";
                $insertProgrammingStmt = $conn->prepare($insertProgrammingQuery);
                $insertProgrammingStmt->bind_param('iss', $newQuestionId, $language, $starterCode);
                $insertProgrammingStmt->execute();
                
                // Get the new programming ID
                $newProgrammingId = $conn->insert_id;
                
                // Copy test cases - updated to match your schema
                $testCasesQuery = "SELECT * FROM question_bank_test_cases WHERE programming_id = ?";
                $testCasesStmt = $conn->prepare($testCasesQuery);
                $testCasesStmt->bind_param('i', $programmingRow['programming_id']);
                $testCasesStmt->execute();
                $testCasesResult = $testCasesStmt->get_result();
                
                while ($testCase = $testCasesResult->fetch_assoc()) {
                    $input = $testCase['input'];
                    $expectedOutput = $testCase['expected_output'];
                    $isHidden = $testCase['is_hidden'];
                    
                    // Check if description column exists in your schema
                    // Using empty string as a placeholder if it doesn't
                    $description = isset($testCase['description']) ? $testCase['description'] : "";
                    
                    $insertTestCaseQuery = "INSERT INTO test_cases (programming_id, input, expected_output, is_hidden, description) 
                                           VALUES (?, ?, ?, ?, ?)";
                    $insertTestCaseStmt = $conn->prepare($insertTestCaseQuery);
                    $insertTestCaseStmt->bind_param('issis', $newProgrammingId, $input, $expectedOutput, $isHidden, $description);
                    $insertTestCaseStmt->execute();
                }
            }
        }
        
        $nextPosition++;
        $addedQuestions++;
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'added_questions' => $addedQuestions,
        'message' => "Successfully added $addedQuestions questions to the exam."
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if started
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close connection if open
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>
