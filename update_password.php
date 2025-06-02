<?php
session_start();
include 'config/config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate input
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate password requirements
if (strlen($new_password) < 8) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit();
}

if (!preg_match('/[A-Z]/', $new_password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one uppercase letter']);
    exit();
}

if (!preg_match('/[a-z]/', $new_password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one lowercase letter']);
    exit();
}

if (!preg_match('/[0-9]/', $new_password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one number']);
    exit();
}

if (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one special character']);
    exit();
}

if ($new_password !== $confirm_password) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit();
}

// Verify current password
$admin_id = $_SESSION['admin_id'];
$query = "SELECT password FROM admin WHERE admin_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin account not found']);
    exit();
}

$admin = $result->fetch_assoc();
if (!password_verify($current_password, $admin['password'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit();
}

// Hash new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$update_query = "UPDATE admin SET password = ? WHERE admin_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $hashed_password, $admin_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
} 