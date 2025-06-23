<?php
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
// Database connection configuration
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "exam";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

=======
// Set timezone to Philippine time
date_default_timezone_set('Asia/Manila');

// Database connection configuration
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "exam";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

>>>>>>> Stashed changes
=======
// Set timezone to Philippine time
date_default_timezone_set('Asia/Manila');

// Database connection configuration
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "exam";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

>>>>>>> Stashed changes
=======
// Set timezone to Philippine time
date_default_timezone_set('Asia/Manila');

// Database connection configuration
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "exam";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

>>>>>>> Stashed changes
=======
// Set timezone to Philippine time
date_default_timezone_set('Asia/Manila');

// Database connection configuration
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "exam";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

>>>>>>> Stashed changes
// Check connection
if ($conn->connect_error) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
    exit;
}

// Set the character set to utf8
$conn->set_charset("utf8");

// Disable error reporting for API endpoints
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>