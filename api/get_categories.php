<?php
// Include database configuration
require_once '../config/config.php';

// Set response header to JSON
header('Content-Type: application/json');

try {
    // Check if the question_bank table has a category_id column
    $checkCategoryColumn = $conn->query("SHOW COLUMNS FROM question_bank LIKE 'category_id'");
    
    if ($checkCategoryColumn->num_rows > 0) {
        // Get distinct categories from question_bank table
        $query = "
            SELECT DISTINCT qb.category_id as id, c.category_name as name
            FROM question_bank qb
            JOIN question_bank_categories c ON qb.category_id = c.category_id
            ORDER BY c.category_name ASC
        ";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Error executing query: " . $conn->error);
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        // Return categories as JSON
        echo json_encode($categories);
    } else {
        // If category_id column doesn't exist, check for direct category table
        $checkCategoryTable = $conn->query("SHOW TABLES LIKE 'question_bank_categories'");
        
        if ($checkCategoryTable->num_rows > 0) {
            // Get all categories from the categories table
            $query = "
                SELECT category_id as id, category_name as name
                FROM question_bank_categories
                ORDER BY category_name ASC
            ";
            
            $result = $conn->query($query);
            
            if (!$result) {
                throw new Exception("Error executing query: " . $conn->error);
            }
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            
            // Return categories as JSON
            echo json_encode($categories);
        } else {
            // No category information found
            echo json_encode([]);
        }
    }
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Close the connection
$conn->close();
?>
