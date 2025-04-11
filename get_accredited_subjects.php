<?php
session_start();
include('config/config.php');

// Check if user is logged in as admin
// This check is commented out to match Qualified_stud.php
// if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
//     header('Content-Type: application/json');
//     echo json_encode(['error' => 'Unauthorized access']);
//     exit();
// }

// Validate input
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid student ID']);
    exit();
}

$student_id = (int)$_GET['student_id'];

// Fetch matched courses for the student
$query = "SELECT matched_id, subject_code, original_code, subject_description, units, grade 
          FROM matched_courses 
          WHERE student_id = ?
          ORDER BY subject_code";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = [
        'matched_id' => $row['matched_id'],
        'subject_code' => htmlspecialchars($row['subject_code']),
        'original_code' => htmlspecialchars($row['original_code']),
        'subject_description' => htmlspecialchars($row['subject_description']),
        'units' => $row['units'],
        'grade' => $row['grade']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($subjects);
exit();
?>
