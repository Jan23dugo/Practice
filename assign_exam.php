<?php
include('config/config.php');

function assignExamToStudents($exam_id, $exam_type) {
    global $conn;
    
    try {
        // Check if this exam already has assignments
        $check_query = "SELECT COUNT(*) as assignment_count FROM exam_assignments WHERE exam_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $exam_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        
        // If assignments already exist, don't reassign
        if ($check_result['assignment_count'] > 0) {
            // Log that we're skipping assignment because it already exists
            $log_file = 'exam_assignment.log';
            file_put_contents($log_file, "Skipping assignment for exam_id: $exam_id - Assignments already exist\n", FILE_APPEND);
            return true; // Return true to indicate success (existing assignments)
        }
        
        // Get all eligible students based on exam type from register_studentsqe
        $student_query = "SELECT student_id, first_name, last_name, email 
                         FROM register_studentsqe 
                         WHERE status = 'accepted' 
                         AND is_tech = ?";
        
        $stmt = $conn->prepare($student_query);
        // For tech exam (is_tech = 1), for non-tech exam (is_tech = 0)
        $is_tech = ($exam_type === 'tech') ? 1 : 0;
        $stmt->bind_param("i", $is_tech);
        $stmt->execute();
        $result = $stmt->get_result();

        // Log the assignment process
        $log_file = 'exam_assignment.log';
        file_put_contents($log_file, "Starting exam assignment for exam_id: $exam_id (Type: $exam_type)\n", FILE_APPEND);
        
        if ($result->num_rows === 0) {
            file_put_contents($log_file, "No eligible students found for exam type: $exam_type\n", FILE_APPEND);
            return false;
        }

        // Prepare the assignment insert statement
        $assign_query = "INSERT INTO exam_assignments 
                        (exam_id, student_id, assigned_date, completion_status) 
                        VALUES (?, ?, NOW(), 'pending')
                        ON DUPLICATE KEY UPDATE assigned_date = NOW()";
        
        $assign_stmt = $conn->prepare($assign_query);
        $students_assigned = 0;

        // Insert assignments for each eligible student
        while ($student = $result->fetch_assoc()) {
            $assign_stmt->bind_param("ii", $exam_id, $student['student_id']);
            
            if ($assign_stmt->execute()) {
                $students_assigned++;
                file_put_contents($log_file, "Assigned exam to student: {$student['first_name']} {$student['last_name']} (ID: {$student['student_id']})\n", FILE_APPEND);
            } else {
                file_put_contents($log_file, "Failed to assign exam to student ID: {$student['student_id']}\n", FILE_APPEND);
            }
        }

        file_put_contents($log_file, "Completed exam assignment. Total students assigned: $students_assigned\n", FILE_APPEND);
        
        return $students_assigned > 0;

    } catch (Exception $e) {
        file_put_contents($log_file, "Error in exam assignment: " . $e->getMessage() . "\n", FILE_APPEND);
        throw $e;
    }
}

// Function to get assignment statistics
function getAssignmentStats($exam_id) {
    global $conn;
    
    $stats_query = "SELECT 
                        COUNT(*) as total_assigned,
                        SUM(CASE WHEN completion_status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN completion_status = 'pending' THEN 1 ELSE 0 END) as pending
                    FROM exam_assignments 
                    WHERE exam_id = ?";
    
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
} 