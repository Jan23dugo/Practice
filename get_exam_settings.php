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

// Get the exam ID
$exam_id = (int)$_GET['exam_id'];

try {
    // Prepare the query to get exam settings
    $query = "SELECT * FROM exams WHERE exam_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Exam not found'
        ]);
        exit;
    }
    
    // Fetch the exam data
    $exam = $result->fetch_assoc();
    
    // Return the exam settings
    echo json_encode([
        'success' => true,
        'exam' => $exam
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 