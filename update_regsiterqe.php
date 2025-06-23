<?php
session_start();
include('config/config.php');

// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$stud_id = $_SESSION['stud_id'];

// Check if student is allowed to re-register after rejection
$checkRejected = $conn->prepare("SELECT rs.student_id, r.allow_reregistration FROM register_studentsqe rs LEFT JOIN rejections r ON rs.student_id = r.student_id WHERE rs.stud_id = ? AND rs.status = 'rejected' ORDER BY rs.registration_date DESC LIMIT 1");
$checkRejected->bind_param("i", $stud_id);
$checkRejected->execute();
$resultRejected = $checkRejected->get_result();
$existingRejected = $resultRejected->fetch_assoc();
$isRejected = ($existingRejected && $existingRejected['allow_reregistration'] == 1);

if ($isRejected) {
    // Update the existing record with new data
    $updateStmt = $conn->prepare("UPDATE register_studentsqe SET status = 'pending', registration_date = NOW() WHERE student_id = ?");
    $updateStmt->bind_param("i", $existingRejected['student_id']);
    $updateStmt->execute();
    echo json_encode(['success' => true, 'message' => 'Registration updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'You are not allowed to re-register.']);
}
?> 