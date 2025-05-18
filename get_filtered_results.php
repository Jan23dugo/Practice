<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
include('config/config.php');

// Get the exam type from the request
$examType = isset($_GET['type']) ? $_GET['type'] : 'all';

try {
    // Base query
    $query = "SELECT 
        COUNT(*) as total_attempts,
        SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count,
        SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as failed_count
    FROM exam_assignments ea
    JOIN exams e ON ea.exam_id = e.exam_id
    WHERE ea.completion_status = 'completed'";

    // Add exam type filter if not 'all'
    if ($examType !== 'all') {
        $query .= " AND e.exam_type = ?";
    }

    $stmt = $conn->prepare($query);
    
    if ($examType !== 'all') {
        $stmt->bind_param('s', $examType);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    // Calculate pass rate
    $passRate = $data['total_attempts'] > 0 
        ? round(($data['passed_count'] / $data['total_attempts']) * 100) 
        : 0;

    // Prepare response
    $response = [
        'total_attempts' => (int)$data['total_attempts'],
        'passed_count' => (int)$data['passed_count'],
        'failed_count' => (int)$data['failed_count'],
        'pass_rate' => $passRate
    ];

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 