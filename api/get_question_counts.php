<?php
// Include database configuration
require_once '../config/config.php';

// Set response header to JSON
header('Content-Type: application/json');

// Get filter parameters from URL
$types = isset($_GET['types']) ? explode(',', $_GET['types']) : ['multiple_choice', 'true_false', 'programming'];
$category = isset($_GET['category']) && !empty($_GET['category']) ? (int)$_GET['category'] : null;

try {
    // Build the base query to count questions
    $query = "SELECT question_type, COUNT(*) as count FROM question_bank WHERE 1=1";
    $params = [];
    
    // Add type filter if specified
    if (!empty($types) && $types[0] !== '') {
        $typeConditions = [];
        foreach ($types as $type) {
            // Convert UI-friendly names to database values if needed
            switch ($type) {
                case 'multiple_choice':
                    $typeConditions[] = "question_type = 'multiple-choice'";
                    break;
                case 'true_false':
                    $typeConditions[] = "question_type = 'true-false'";
                    break;
                case 'programming':
                    $typeConditions[] = "question_type = 'programming'";
                    break;
                default:
                    $typeConditions[] = "question_type = '$type'";
            }
        }
        if (!empty($typeConditions)) {
            $query .= " AND (" . implode(" OR ", $typeConditions) . ")";
        }
    }
    
    // Add category filter if specified
    if ($category !== null) {
        $query .= " AND category_id = ?";
        $params[] = $category;
    }
    
    // Group by question type
    $query .= " GROUP BY question_type";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    // Bind parameters if any
    if (!empty($params)) {
        $types_str = str_repeat('i', count($params)); // Assuming all params are integers
        $stmt->bind_param($types_str, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Initialize counts for all types
    $counts = [
        'total' => 0,
        'by_type' => [
            'multiple_choice' => 0,
            'true_false' => 0,
            'programming' => 0
        ]
    ];
    
    // Process results
    while ($row = $result->fetch_assoc()) {
        $type = $row['question_type'];
        $count = (int)$row['count'];
        
        // Map database question types to API response types
        switch ($type) {
            case 'multiple-choice':
                $counts['by_type']['multiple_choice'] = $count;
                break;
            case 'true-false':
                $counts['by_type']['true_false'] = $count;
                break;
            case 'programming':
                $counts['by_type']['programming'] = $count;
                break;
            default:
                // Handle any other types
                $counts['by_type'][$type] = $count;
        }
        
        // Add to total
        $counts['total'] += $count;
    }
    
    // Return the counts as JSON
    echo json_encode($counts);
    
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Close the connection
$conn->close();
?> 