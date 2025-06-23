<?php
// Script to update the is_tech field in the register_studentsqe table
// to support the new value for ladderized students

// Include database configuration
require_once 'config/config.php';

// Check if the user is logged in as an administrator
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Access denied. Please login as an administrator.");
}

// Function to log messages
function log_message($message) {
    echo $message . "<br>";
    error_log($message);
}

try {
    // Check if the connection is valid
    if (!$conn || !$conn->ping()) {
        throw new Exception("Database connection failed");
    }
    
    // Check if the table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'register_studentsqe'";
    $result = $conn->query($tableCheckQuery);
    
    if ($result->num_rows === 0) {
        throw new Exception("Table 'register_studentsqe' does not exist");
    }
    
    // Check the current schema of the is_tech field
    $columnCheckQuery = "SHOW COLUMNS FROM register_studentsqe LIKE 'is_tech'";
    $result = $conn->query($columnCheckQuery);
    
    if ($result->num_rows === 0) {
        throw new Exception("Column 'is_tech' does not exist in table 'register_studentsqe'");
    }
    
    $column = $result->fetch_assoc();
    log_message("Current is_tech field type: " . $column['Type']);
    
    // Modify the is_tech column to support the new value
    $alterQuery = "ALTER TABLE register_studentsqe MODIFY COLUMN is_tech TINYINT(2) NOT NULL DEFAULT 0 COMMENT '0=non-tech, 1=tech, 2=ladderized'";
    
    if ($conn->query($alterQuery) === TRUE) {
        log_message("Successfully updated is_tech field to tinyint(2)");
    } else {
        throw new Exception("Error updating is_tech field: " . $conn->error);
    }
    
    // Update existing ladderized students to have is_tech = 2
    $updateQuery = "UPDATE register_studentsqe SET is_tech = 2 WHERE student_type = 'ladderized'";
    
    if ($conn->query($updateQuery) === TRUE) {
        $updatedRows = $conn->affected_rows;
        log_message("Successfully updated $updatedRows ladderized students to have is_tech = 2");
    } else {
        throw new Exception("Error updating existing ladderized students: " . $conn->error);
    }
    
    log_message("Database update completed successfully");
    
} catch (Exception $e) {
    log_message("Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 