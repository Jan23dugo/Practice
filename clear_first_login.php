<?php
session_start();

// Set JSON response headers
header('Content-Type: application/json');

// Clear the first_login flag
unset($_SESSION['first_login']);

// Send success response
echo json_encode(['success' => true, 'message' => 'First login flag cleared']);
?> 