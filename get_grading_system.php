<?php
// Start session and include database configuration
session_start();
require_once 'config/config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if grading system name is provided
if (!isset($_GET['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Grading system name is required']);
    exit;
}

try {
    // Prepare and execute query to get grading system details
    $stmt = $conn->prepare("SELECT * FROM university_grading_systems WHERE grading_name = ? ORDER BY is_special_grade, grade_value");
    $grading_name = $_GET['name'];
    $stmt->bind_param("s", $grading_name);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $grades = [];
    
    while ($row = $result->fetch_assoc()) {
        $grades[] = [
            'grade_value' => $row['grade_value'],
            'description' => $row['description'],
            'min_percentage' => $row['min_percentage'],
            'max_percentage' => $row['max_percentage'],
            'is_special_grade' => (bool)$row['is_special_grade']
        ];
    }
    
    if (empty($grades)) {
        http_response_code(404);
        echo json_encode(['error' => 'Grading system not found']);
        exit;
    }
    
    echo json_encode($grades);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 