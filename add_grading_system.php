<?php
session_start();
require_once 'db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $universityName = $_POST['university_name'] ?? '';
    $gradeValues = $_POST['grade_value'] ?? [];
    $minPercentages = $_POST['min_percentage'] ?? [];
    $maxPercentages = $_POST['max_percentage'] ?? [];
    $descriptions = $_POST['description'] ?? [];
    
    // Validate input
    if (empty($universityName) || empty($gradeValues) || count($gradeValues) < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Ensure all arrays have the same length
    if (count($gradeValues) !== count($minPercentages) || 
        count($gradeValues) !== count($maxPercentages) || 
        count($gradeValues) !== count($descriptions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Inconsistent grading rule data']);
        exit;
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert grading system rules
        $stmt = $conn->prepare("INSERT INTO university_grading_systems 
                               (university_name, grade_value, min_percentage, max_percentage, description, is_special_grade) 
                               VALUES (?, ?, ?, ?, ?, 0)");
        
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $rulesAdded = 0;
        
        // Insert each grading rule
        for ($i = 0; $i < count($gradeValues); $i++) {
            $gradeValue = $gradeValues[$i];
            $minPercentage = floatval($minPercentages[$i]);
            $maxPercentage = floatval($maxPercentages[$i]);
            $description = $descriptions[$i];
            
            // Additional validation
            if ($minPercentage > $maxPercentage) {
                throw new Exception("Min percentage cannot be greater than max percentage for grade: " . $gradeValue);
            }
            
            // Bind parameters and execute
            $stmt->bind_param("ssdds", 
                $universityName, 
                $gradeValue, 
                $minPercentage, 
                $maxPercentage, 
                $description
            );
            
            if ($stmt->execute()) {
                $rulesAdded++;
            } else {
                throw new Exception("Error adding rule for grade " . $gradeValue . ": " . $stmt->error);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        echo json_encode([
            'success' => true, 
            'message' => 'Grading system for ' . $universityName . ' added successfully with ' . $rulesAdded . ' rules',
            'rules_added' => $rulesAdded
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?> 