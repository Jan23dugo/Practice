<?php
function calculateGrade($percentage, $university_name, $conn) {
    // Prepare the query to find the matching grade
    $stmt = $conn->prepare("
        SELECT grade_value 
        FROM university_grading_systems 
        WHERE university_name = ? 
        AND ? BETWEEN min_percentage AND max_percentage 
        LIMIT 1
    ");
    
    $stmt->bind_param("sd", $university_name, $percentage);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['grade_value'];
    }
    
    // Return null if no matching grade is found
    return null;
}

function getGradingSystem($university_name, $conn) {
    // Get the complete grading system for a university
    $stmt = $conn->prepare("
        SELECT min_percentage, max_percentage, grade_value 
        FROM university_grading_systems 
        WHERE university_name = ? 
        ORDER BY grade_value DESC
    ");
    
    $stmt->bind_param("s", $university_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>