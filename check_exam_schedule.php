<?php
session_start();
include 'config/config.php';

// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'not_logged_in']);
    exit();
}

// Get exam_id from request
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
if ($exam_id === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_exam']);
    exit();
}

// Check if this exam is assigned to this student
$stud_id = $_SESSION['stud_id'];
$check_query = "SELECT ea.* 
                FROM exam_assignments ea 
                JOIN register_studentsqe rs ON ea.student_id = rs.student_id
                WHERE ea.exam_id = ? 
                AND rs.stud_id = ? 
                AND ea.completion_status = 'pending'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $exam_id, $stud_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'not_assigned']);
    exit();
}

// Get exam and assignment information with window_start and window_end
$exam_query = "SELECT e.*, ea.window_start, ea.window_end, rs.student_id as registered_student_id 
               FROM exams e
               JOIN exam_assignments ea ON e.exam_id = ea.exam_id
               JOIN register_studentsqe rs ON ea.student_id = rs.student_id
               WHERE e.exam_id = ? 
               AND rs.stud_id = ?";
$stmt = $conn->prepare($exam_query);
$stmt->bind_param("ii", $exam_id, $stud_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

// Check if exam is scheduled and validate window
if ($exam['is_scheduled'] == 1) {
    $current_datetime = new DateTime();
    $window_start = new DateTime($exam['window_start']);
    $window_end = new DateTime($exam['window_end']);

    // If current time is before window start, return error
    if ($current_datetime < $window_start) {
        $time_until = $current_datetime->diff($window_start);
        $hours = $time_until->h + ($time_until->days * 24);
        $minutes = $time_until->i;
        $error_message = "This exam window starts at " . $window_start->format('F j, Y g:i A') . ". Please come back in " . $hours . " hours and " . $minutes . " minutes.";
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'scheduled',
            'message' => $error_message,
            'window_start' => $window_start->format('Y-m-d H:i:s'),
            'current_time' => $current_datetime->format('Y-m-d H:i:s')
        ]);
        exit();
    }
    // If current time is after window end, return error
    if ($current_datetime > $window_end) {
        $error_message = "This exam window has ended at " . $window_end->format('F j, Y g:i A') . ".";
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'expired',
            'message' => $error_message,
            'window_end' => $window_end->format('Y-m-d H:i:s'),
            'current_time' => $current_datetime->format('Y-m-d H:i:s')
        ]);
        exit();
    }
}

// If we get here, the exam is available
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'exam_id' => $exam_id
]); 