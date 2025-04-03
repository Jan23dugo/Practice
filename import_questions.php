<?php
include('config/config.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set header to return JSON response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Get exam_id and question_ids from JSON data
$exam_id = isset($data['exam_id']) ? (int)$data['exam_id'] : 0;
$question_ids = isset($data['question_ids']) ? $data['question_ids'] : [];

// Validate inputs
if ($exam_id <= 0 || empty($question_ids)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid exam ID or no questions selected'
    ]);
    exit;
}

try {
    $conn->begin_transaction();

    // Get the current maximum position for this exam
    $position_query = "SELECT COALESCE(MAX(position), 0) as max_pos FROM questions WHERE exam_id = ?";
    $stmt = $conn->prepare($position_query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $max_position = $stmt->get_result()->fetch_assoc()['max_pos'];
    $current_position = $max_position + 1;

    // Prepare statements for inserting questions and related data
    $insert_question = $conn->prepare("
        INSERT INTO questions (exam_id, question_text, question_type, points, position)
        SELECT ?, question_text, question_type, points, ?
        FROM question_bank WHERE question_id = ?
    ");

    $insert_answers = $conn->prepare("
        INSERT INTO answers (question_id, answer_text, is_correct, position)
        SELECT ?, answer_text, is_correct, position
        FROM question_bank_answers WHERE question_id = ?
    ");

    $insert_programming = $conn->prepare("
        INSERT INTO programming_questions (question_id, starter_code, language)
        SELECT ?, starter_code, language
        FROM question_bank_programming WHERE question_id = ?
    ");

    $insert_test_cases = $conn->prepare("
        INSERT INTO test_cases (programming_id, input, expected_output, is_hidden, description)
        SELECT ?, input, expected_output, is_hidden, description
        FROM question_bank_test_cases WHERE programming_id = ?
    ");

    $imported_count = 0;

    // Process each question
    foreach ($question_ids as $qb_question_id) {
        // Insert the question
        $insert_question->bind_param("iii", $exam_id, $current_position, $qb_question_id);
        $insert_question->execute();
        $new_question_id = $conn->insert_id;

        if ($new_question_id) {
            // Get question type
            $type_query = "SELECT question_type FROM question_bank WHERE question_id = ?";
            $stmt = $conn->prepare($type_query);
            $stmt->bind_param("i", $qb_question_id);
            $stmt->execute();
            $question_type = $stmt->get_result()->fetch_assoc()['question_type'];

            // Insert answers for multiple-choice and true-false questions
            if ($question_type !== 'programming') {
                $insert_answers->bind_param("ii", $new_question_id, $qb_question_id);
                $insert_answers->execute();
            } else {
                // Insert programming question details
                $insert_programming->bind_param("ii", $new_question_id, $qb_question_id);
                $insert_programming->execute();
                $new_programming_id = $conn->insert_id;

                // Get original programming_id
                $prog_query = "SELECT programming_id FROM question_bank_programming WHERE question_id = ?";
                $stmt = $conn->prepare($prog_query);
                $stmt->bind_param("i", $qb_question_id);
                $stmt->execute();
                $orig_programming_id = $stmt->get_result()->fetch_assoc()['programming_id'];

                // Insert test cases
                if ($orig_programming_id) {
                    $insert_test_cases->bind_param("ii", $new_programming_id, $orig_programming_id);
                    $insert_test_cases->execute();
                }
            }

            $imported_count++;
            $current_position++;
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Successfully imported $imported_count questions",
        'imported_count' => $imported_count
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error importing questions: ' . $e->getMessage()
    ]);
}

// Close prepared statements
if (isset($insert_question)) $insert_question->close();
if (isset($insert_answers)) $insert_answers->close();
if (isset($insert_programming)) $insert_programming->close();
if (isset($insert_test_cases)) $insert_test_cases->close();
$conn->close();

