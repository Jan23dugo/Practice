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

if (!isset($_POST['status']) || !isset($_POST['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$status = $_POST['status'];
$student_id = (int)$_POST['student_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get student email before updating status
    $stmt = $conn->prepare("SELECT email, first_name, last_name, reference_id FROM register_studentsqe WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student) {
        throw new Exception("Student not found");
    }

    // If status is being changed to accepted, generate reference ID if not exists
    if ($status === 'accepted' && empty($student['reference_id'])) {
        // Generate new reference ID
        $year = date('Y');
        $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $new_reference_id = "CCIS-{$year}-{$unique}";
        // Make sure it's unique
        $exists = true;
        while ($exists) {
            $stmt = $conn->prepare("SELECT reference_id FROM register_studentsqe WHERE reference_id = ?");
            $stmt->bind_param("s", $new_reference_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $exists = false;
            } else {
                $unique = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $new_reference_id = "CCIS-{$year}-{$unique}";
            }
        }
        // Update reference ID
        $stmt = $conn->prepare("UPDATE register_studentsqe SET reference_id = ? WHERE student_id = ?");
        $stmt->bind_param("si", $new_reference_id, $student_id);
        $stmt->execute();
        $student['reference_id'] = $new_reference_id;
    }

    // Update student status
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
    $stmt = $conn->prepare("UPDATE register_studentsqe SET status = ? WHERE reference_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare the status update query: " . $conn->error);
    }
    $stmt->bind_param("ss", $status, $reference_id);
=======
    $stmt = $conn->prepare("UPDATE register_studentsqe SET status = ? WHERE student_id = ?");
    $statusString = (string)$status;
    $stmt->bind_param("si", $statusString, $student_id);
>>>>>>> Stashed changes
=======
    $stmt = $conn->prepare("UPDATE register_studentsqe SET status = ? WHERE student_id = ?");
    $statusString = (string)$status;
    $stmt->bind_param("si", $statusString, $student_id);
>>>>>>> Stashed changes
=======
    $stmt = $conn->prepare("UPDATE register_studentsqe SET status = ? WHERE student_id = ?");
    $statusString = (string)$status;
    $stmt->bind_param("si", $statusString, $student_id);
>>>>>>> Stashed changes
=======
    $stmt = $conn->prepare("UPDATE register_studentsqe SET status = ? WHERE student_id = ?");
    $statusString = (string)$status;
    $stmt->bind_param("si", $statusString, $student_id);
>>>>>>> Stashed changes
=======
    $stmt = $conn->prepare("UPDATE register_studentsqe SET status = ? WHERE student_id = ?");
    $statusString = (string)$status;
    $stmt->bind_param("si", $statusString, $student_id);
>>>>>>> Stashed changes
=======
    $stmt = $conn->prepare("UPDATE register_studentsqe SET status = ? WHERE student_id = ?");
    $statusString = (string)$status;
    $stmt->bind_param("si", $statusString, $student_id);
>>>>>>> Stashed changes
    $stmt->execute();

    // If status is rejected and reason is provided, store the reason
    if ($status === 'rejected' && isset($_POST['rejection_reason'])) {
        $reason = $_POST['rejection_reason'];
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        
        // First, delete any existing rejection reason for this reference_id
        $stmt = $conn->prepare("DELETE FROM rejection_reasons WHERE reference_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare the delete query: " . $conn->error);
        }
        $stmt->bind_param("s", $reference_id);
=======
        // First, delete any existing rejection reason for this student_id
        $stmt = $conn->prepare("DELETE FROM rejection_reasons WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
>>>>>>> Stashed changes
        $stmt->execute();
        // Insert new rejection reason
<<<<<<< Updated upstream
        $stmt = $conn->prepare("INSERT INTO rejection_reasons (reference_id, reason) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare the insert query: " . $conn->error);
        }
        $stmt->bind_param("ss", $reference_id, $reason);
=======
        $stmt = $conn->prepare("INSERT INTO rejection_reasons (student_id, reason) VALUES (?, ?)");
        $stmt->bind_param("is", $student_id, $reason);
>>>>>>> Stashed changes
=======
        // First, delete any existing rejection reason for this student_id
        $stmt = $conn->prepare("DELETE FROM rejection_reasons WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        // Insert new rejection reason
        $stmt = $conn->prepare("INSERT INTO rejection_reasons (student_id, reason) VALUES (?, ?)");
        $stmt->bind_param("is", $student_id, $reason);
>>>>>>> Stashed changes
=======
        // First, delete any existing rejection reason for this student_id
        $stmt = $conn->prepare("DELETE FROM rejection_reasons WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        // Insert new rejection reason
        $stmt = $conn->prepare("INSERT INTO rejection_reasons (student_id, reason) VALUES (?, ?)");
        $stmt->bind_param("is", $student_id, $reason);
>>>>>>> Stashed changes
=======
        // First, delete any existing rejection reason for this student_id
        $stmt = $conn->prepare("DELETE FROM rejection_reasons WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        // Insert new rejection reason
        $stmt = $conn->prepare("INSERT INTO rejection_reasons (student_id, reason) VALUES (?, ?)");
        $stmt->bind_param("is", $student_id, $reason);
>>>>>>> Stashed changes
=======
        // First, delete any existing rejection reason for this student_id
        $stmt = $conn->prepare("DELETE FROM rejection_reasons WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        // Insert new rejection reason
        $stmt = $conn->prepare("INSERT INTO rejection_reasons (student_id, reason) VALUES (?, ?)");
        $stmt->bind_param("is", $student_id, $reason);
>>>>>>> Stashed changes
=======
        // First, delete any existing rejection reason for this student_id
        $stmt = $conn->prepare("DELETE FROM rejection_reasons WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        // Insert new rejection reason
        $stmt = $conn->prepare("INSERT INTO rejection_reasons (student_id, reason) VALUES (?, ?)");
        $stmt->bind_param("is", $student_id, $reason);
>>>>>>> Stashed changes
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Send appropriate email based on status
    require_once 'send_email.php';
    if ($status === 'accepted') {
        sendRegistrationEmail($student['email'], $student['reference_id']);
    } elseif ($status === 'rejected') {
        $reason = $_POST['rejection_reason'] ?? 'No reason provided';
        sendRejectionEmail($student['email'], $student['first_name'] . ' ' . $student['last_name'], $reason);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
fwrite($log_file, "Database connection closed\n");
fclose($log_file);
?>
