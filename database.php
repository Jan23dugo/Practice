<?php
// Database connection settings
$hostname = 'localhost';
$username = 'your_username';
$password = 'your_password';
$database = 'coding_exam_db';

// Create connection
$conn = new mysqli($hostname, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?> 