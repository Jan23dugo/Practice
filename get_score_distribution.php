<?php
session_start();
require_once('config/config.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Check if exam_id is provided
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid exam ID'
    ]);
    exit;
}

$exam_id = (int)$_GET['exam_id'];

// Initialize distribution array
$distribution = [
    '91-100' => 0,
    '81-90' => 0,
    '71-80' => 0,
    '61-70' => 0,
    '51-60' => 0,
    '41-50' => 0,
    '31-40' => 0,
    '21-30' => 0,
    '11-20' => 0,
    '0-10' => 0
];

// Fetch scores for this exam
$query = "
SELECT final_score
FROM exam_assignments
WHERE exam_id = ? AND completion_status = 'completed'";

$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $score = (int)$row['final_score'];
        
        if ($score >= 91) $distribution['91-100']++;
        elseif ($score >= 81) $distribution['81-90']++;
        elseif ($score >= 71) $distribution['71-80']++;
        elseif ($score >= 61) $distribution['61-70']++;
        elseif ($score >= 51) $distribution['51-60']++;
        elseif ($score >= 41) $distribution['41-50']++;
        elseif ($score >= 31) $distribution['31-40']++;
        elseif ($score >= 21) $distribution['21-30']++;
        elseif ($score >= 11) $distribution['11-20']++;
        else $distribution['0-10']++;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'distribution' => $distribution
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database query error'
    ]);
}
?> 