<?php
session_start();
include('config/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['exam_id'])) {
    $exam_id = intval($_POST['exam_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First check if the exam has assignments
        $check_assignments = $conn->prepare("SELECT COUNT(*) FROM exam_assignments WHERE exam_id = ?");
        $check_assignments->bind_param("i", $exam_id);
        $check_assignments->execute();
        $result = $check_assignments->get_result();
        $has_assignments = $result->fetch_row()[0] > 0;
        
        if ($has_assignments) {
            // First delete related assignments
            $delete_assignments = $conn->prepare("DELETE FROM exam_assignments WHERE exam_id = ?");
            $delete_assignments->bind_param("i", $exam_id);
            $delete_assignments->execute();
        }
        
        // Then delete related questions
        $delete_questions = $conn->prepare("DELETE FROM questions WHERE exam_id = ?");
        $delete_questions->bind_param("i", $exam_id);
        $delete_questions->execute();
        
        // Finally delete the exam
        $delete_exam = $conn->prepare("DELETE FROM exams WHERE exam_id = ?");
        $delete_exam->bind_param("i", $exam_id);
        $delete_exam->execute();
        
        // Check if exam was actually deleted
        if ($delete_exam->affected_rows > 0) {
            // Commit transaction
            $conn->commit();
            $_SESSION['success'] = "Exam deleted successfully.";
        } else {
            // Rollback if exam not found
            $conn->rollback();
            $_SESSION['error'] = "Exam not found or already deleted.";
        }
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['error'] = "Error deleting exam: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

// Redirect back to exam list
header("Location: exam.php");
exit;
?> 