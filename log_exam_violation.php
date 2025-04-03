<?php
session_start();
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($_SESSION['stud_id']) && isset($data['type'])) {
        $stmt = $conn->prepare("INSERT INTO exam_violations (student_id, violation_type, exam_token, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", 
            $_SESSION['stud_id'],
            $data['type'],
            $_SESSION['exam_token'],
            $_SERVER['REMOTE_ADDR']
        );
        $stmt->execute();
    }
} 