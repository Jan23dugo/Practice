<?php

// Function to get exam details
function getExam($exam_id) {
    global $conn;
    
    $query = "SELECT *, instructions FROM exams WHERE exam_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

// Function to get all questions for an exam
function getExamQuestions($exam_id) {
    global $conn;
    
    $query = "SELECT * FROM questions WHERE exam_id = ? ORDER BY position ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $questions = [];
    
    while ($row = $result->fetch_assoc()) {
        $question = $row;
        $question['answers'] = getQuestionAnswers($row['question_id']);
        
        // If it's a programming question, get additional data
        if ($row['question_type'] === 'programming') {
            $question['programming_data'] = getProgrammingData($row['question_id']);
        }
        
        $questions[] = $question;
    }
    
    return $questions;
}

// Function to get answers for a question
function getQuestionAnswers($question_id) {
    global $conn;
    
    $query = "SELECT * FROM answers WHERE question_id = ? ORDER BY position ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $answers = [];
    
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row;
    }
    
    return $answers;
}

// Function to get programming question data
function getProgrammingData($question_id) {
    global $conn;
    
    $query = "SELECT * FROM programming_questions WHERE question_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $programming_data = $result->fetch_assoc();
    $programming_data['test_cases'] = getTestCases($programming_data['programming_id']);
    
    return $programming_data;
}

// Function to get test cases for a programming question
function getTestCases($programming_id) {
    global $conn;
    
    $query = "SELECT * FROM test_cases WHERE programming_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $programming_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $test_cases = [];
    
    while ($row = $result->fetch_assoc()) {
        $test_cases[] = $row;
    }
    
    return $test_cases;
}

// Function to get all exams
function getAllExams() {
    global $conn;
    
    $query = "SELECT *, instructions FROM exams ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    $exams = [];
    
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
    
    return $exams;
}

// If this file is accessed directly with an exam_id parameter, return JSON data
if (isset($_GET['exam_id'])) {
    header('Content-Type: application/json');
    $exam_id = (int)$_GET['exam_id'];
    $exam = getExam($exam_id);
    
    if (!$exam) {
        echo json_encode(['error' => 'Exam not found']);
        exit();
    }
    
    $exam['questions'] = getExamQuestions($exam_id);
    echo json_encode($exam);
    exit();
}

// If accessed with 'all' parameter, return all exams
if (isset($_GET['all'])) {
    header('Content-Type: application/json');
    $exams = getAllExams();
    echo json_encode($exams);
    exit();
}
?>
