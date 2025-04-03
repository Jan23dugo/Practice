<?php
// Include database connection
require_once '../config/config.php';

// Set response header to JSON
header('Content-Type: application/json');

// Check if exam ID is provided
if (!isset($_GET['exam_id']) || empty($_GET['exam_id'])) {
    echo json_encode(['success' => false, 'message' => 'Exam ID is required']);
    exit;
}

$exam_id = (int)$_GET['exam_id'];

// Fetch exam settings
$query = "SELECT * FROM exams WHERE exam_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
    
    // Format the response
    $response = [
        'success' => true,
        'settings' => $settings
    ];
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Exam not found']);
}

$stmt->close();
$conn->close();
?>
