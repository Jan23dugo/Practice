<?php
include('config/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    // Get the POST data
    $question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
    $question_text = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
    $question_type = isset($_POST['question_type']) ? trim($_POST['question_type']) : '';
    
    // Validate inputs
    if (!$question_id || empty($question_text) || empty($question_type)) {
        $response['message'] = 'Invalid input data';
        echo json_encode($response);
        exit;
    }
    
    // Update the question in the database
    $stmt = $conn->prepare("UPDATE questions SET question_text = ?, question_type = ? WHERE question_id = ?");
    $stmt->bind_param("ssi", $question_text, $question_type, $question_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Question updated successfully';
    } else {
        $response['message'] = 'Error updating question: ' . $conn->error;
    }
    
    $stmt->close();
    echo json_encode($response);
    exit;
} 