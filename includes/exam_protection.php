<?php
require_once 'config/ip_config.php';

function verifyExamAccess() {
    // Check if user is logged in
    if (!isset($_SESSION['stud_id'])) {
        header("Location: stud_login.php");
        exit();
    }
    
    // Check if accessing from university network
    if (!isUniversityNetwork()) {
        $_SESSION['error'] = "Exams can only be taken from university computers.";
        header("Location: stud_dashboard.php");
        exit();
    }
    
    // Additional security checks
    if (!isset($_SESSION['exam_token'])) {
        $_SESSION['exam_token'] = bin2hex(random_bytes(32));
    }
    
    // Prevent multiple tabs/windows
    if (isset($_SESSION['exam_active']) && $_SESSION['exam_active'] === true) {
        $_SESSION['error'] = "An exam is already in progress in another window.";
        header("Location: stud_dashboard.php");
        exit();
    }
} 