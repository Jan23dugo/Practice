<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['university_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'University name is required']);
    exit;
}

$university_name = mysqli_real_escape_string($conn, $_GET['university_name']);

$query = "SELECT university_code FROM universities WHERE university_name = '$university_name'";
$result = mysqli_query($conn, $query);
$university = mysqli_fetch_assoc($result);

if (!$university) {
    http_response_code(404);
    echo json_encode(['error' => 'University not found']);
    exit;
}

echo json_encode(['university_code' => $university['university_code']]); 