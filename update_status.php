<?php
// Start session if not already started
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to the browser

// Log the request for debugging
$log_file = fopen("status_update_log.txt", "a"); // Changed to 'a' to append to log
fwrite($log_file, "Request received: " . date("Y-m-d H:i:s") . "\n");
fwrite($log_file, "POST data: " . print_r($_POST, true) . "\n");
fwrite($log_file, "Session data: " . print_r($_SESSION, true) . "\n");

// Include database configuration
include('config/config.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['status']) || !isset($_POST['reference_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$status = $_POST['status'];
$reference_id = $_POST['reference_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Update student status
    $stmt = $conn->prepare("UPDATE register_studentsqe SET status = ? WHERE reference_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare the status update query: " . $conn->error);
    }
    $stmt->bind_param("ss", $status, $reference_id);
    $stmt->execute();

    // If status is rejected and reason is provided, store the reason
    if ($status === 'rejected' && isset($_POST['rejection_reason'])) {
        $reason = $_POST['rejection_reason'];
        
        // First, delete any existing rejection reason for this reference_id
        $stmt = $conn->prepare("DELETE FROM rejection_reasons WHERE reference_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare the delete query: " . $conn->error);
        }
        $stmt->bind_param("s", $reference_id);
        $stmt->execute();
        
        // Insert new rejection reason
        $stmt = $conn->prepare("INSERT INTO rejection_reasons (reference_id, reason) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare the insert query: " . $conn->error);
        }
        $stmt->bind_param("ss", $reference_id, $reason);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
fwrite($log_file, "Database connection closed\n");
fclose($log_file);
?>
