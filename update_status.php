<?php
// Start session if not already started
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to the browser

// Log the request for debugging
$log_file = fopen("status_update_log.txt", "w");
fwrite($log_file, "Request received: " . date("Y-m-d H:i:s") . "\n");
fwrite($log_file, "POST data: " . print_r($_POST, true) . "\n");
fwrite($log_file, "Session data: " . print_r($_SESSION, true) . "\n");

// Include database configuration
include('config/config.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the status and reference_id from the POST data
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $reference_id = isset($_POST['reference_id']) ? $_POST['reference_id'] : '';
    
    fwrite($log_file, "Status: $status, Reference ID: $reference_id\n");
    
    // Validate inputs
    if (empty($status) || empty($reference_id)) {
        fwrite($log_file, "Error: Missing required parameters\n");
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        fclose($log_file);
        exit;
    }
    
    // Validate status value
    $allowed_statuses = ['pending', 'accepted', 'rejected'];
    if (!in_array($status, $allowed_statuses)) {
        fwrite($log_file, "Error: Invalid status value\n");
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        fclose($log_file);
        exit;
    }
    
    // Skip authentication check for now
    fwrite($log_file, "Skipping authentication check for debugging\n");
    
    // Prepare and execute the update query
    $query = "UPDATE register_studentsqe SET status = ? WHERE reference_id = ?";
    fwrite($log_file, "Query: $query\n");
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        fwrite($log_file, "Prepare failed: " . $conn->error . "\n");
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        fclose($log_file);
        exit;
    }
    
    $stmt->bind_param("ss", $status, $reference_id);
    
    if ($stmt->execute()) {
        fwrite($log_file, "Execute successful. Affected rows: " . $stmt->affected_rows . "\n");
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        fwrite($log_file, "Execute failed: " . $stmt->error . "\n");
        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    // If not a POST request, return an error
    fwrite($log_file, "Error: Invalid request method\n");
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// Close the database connection
$conn->close();
fwrite($log_file, "Database connection closed\n");
fclose($log_file);
?>
