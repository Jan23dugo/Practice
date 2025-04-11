<?php
include('config/config.php');

function assignExamToStudents($exam_id, $exam_type) {
    global $conn;
    
    try {
        // Log the start of the assignment process
        $log_file = 'exam_assignment.log';
        file_put_contents($log_file, "Starting exam assignment for exam_id: $exam_id (Type: $exam_type)\n", FILE_APPEND);

        // Get all eligible students based on exam type from register_studentsqe
        // Only select students who don't already have this exam assigned
        $student_query = "SELECT rs.student_id, rs.first_name, rs.last_name, rs.email 
                         FROM register_studentsqe rs
                         WHERE rs.status = 'accepted' 
                         AND rs.is_tech = ?
                         AND NOT EXISTS (
                             SELECT 1 FROM exam_assignments ea 
                             WHERE ea.exam_id = ? 
                             AND ea.student_id = rs.student_id
                         )";
        
        $stmt = $conn->prepare($student_query);
        // For tech exam (is_tech = 1), for non-tech exam (is_tech = 0)
        $is_tech = ($exam_type === 'tech') ? 1 : 0;
        $stmt->bind_param("ii", $is_tech, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Log the number of eligible students found
        $eligible_count = $result->num_rows;
        file_put_contents($log_file, "Found $eligible_count new eligible students for assignment\n", FILE_APPEND);
        
        if ($eligible_count === 0) {
            // Check if there are any existing assignments
            $check_query = "SELECT COUNT(*) as assignment_count FROM exam_assignments WHERE exam_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $exam_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_assoc();
            
            if ($check_result['assignment_count'] > 0) {
                file_put_contents($log_file, "No new students to assign, exam already has {$check_result['assignment_count']} existing assignments\n", FILE_APPEND);
                return true;
            }
            
            file_put_contents($log_file, "No eligible students found for exam type: $exam_type\n", FILE_APPEND);
            return false;
        }

        // Prepare the assignment insert statement
        $assign_query = "INSERT INTO exam_assignments 
                        (exam_id, student_id, assigned_date, completion_status) 
                        VALUES (?, ?, NOW(), 'pending')";
        
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

        file_put_contents($log_file, "Completed exam assignment. Total new students assigned: $students_assigned\n", FILE_APPEND);
        
        return true;

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