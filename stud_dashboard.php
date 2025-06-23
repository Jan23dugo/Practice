<?php
// Start the session
session_start();

// Turn off error display for production
ini_set('display_errors', 0);
error_reporting(0);

// Add this near the top of the file, after session_start()
include 'config/config.php'; // Include database connection
require_once 'config/ip_config.php'; // Include IP configurations

// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    // Redirect to login page if not logged in
    header("Location: stud_register.php");
    exit();
}

// Check if current IP is verified
$is_ip_verified = isCurrentIPVerified($conn);

// Handle exam schedule validation AJAX request
if (isset($_GET['action']) && $_GET['action'] === 'validate_exam') {
    header('Content-Type: application/json');
    
    $stud_id = $_SESSION['stud_id'];
    
    if (isset($_GET['exam_id'])) {
        $exam_id = intval($_GET['exam_id']);
        // Simplified check for a specific exam assignment
        $query = "SELECT 1 FROM exam_assignments ea 
                 JOIN register_studentsqe rs ON ea.student_id = rs.student_id 
                 WHERE rs.stud_id = ? AND ea.exam_id = ? AND ea.completion_status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $stud_id, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo json_encode(['error' => true, 'message' => 'This exam is not assigned to you.']);
            exit();
        }
        echo json_encode(['success' => true]);
        exit();
    } else {
        // Simplified check for ANY assigned pending exam
        $query = "SELECT 1 FROM exam_assignments ea 
                 JOIN register_studentsqe rs ON ea.student_id = rs.student_id 
                 WHERE rs.stud_id = ? AND ea.completion_status = 'pending' 
                 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $stud_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo json_encode(['error' => true, 'message' => 'No pending exams have been assigned to you.']);
            exit();
        }
        echo json_encode(['success' => true]);
        exit();
    }
}

// Get student information from session
$stud_id = $_SESSION['stud_id']; // This is now the database ID
$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$email = $_SESSION['email'];

// Exam error alert logic
$exam_error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'no_exam_assigned') {
        $exam_error_message = 'No qualifying exam has been assigned to you at this time.';
    } else if ($_GET['error'] === 'exam_window_closed') {
        $exam_error_message = 'The exam window is currently closed.';
    } else if ($_GET['error'] === 'already_taken') {
        $exam_error_message = 'You have already completed this exam.';
    } else {
        $exam_error_message = 'An unknown error occurred.';
    }
}

// Fetch student profile picture
$query = "SELECT profile_picture FROM students WHERE stud_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['stud_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Replace the getScheduledExams() function with this:
function getScheduledExams($stud_id) {
    global $conn;
    
    if (!$conn) {
        return array();
    }
    
    try {
        $query = "SELECT e.exam_id, e.title, e.venue, e.exam_type, ea.window_start, ea.window_end, ea.completion_status 
                  FROM exams e
                  INNER JOIN exam_assignments ea ON e.exam_id = ea.exam_id
                  WHERE ea.student_id = ? 
                  AND e.is_scheduled = 1 
                  AND ea.completion_status = 'pending'
                  AND ea.window_start <= NOW()
                  AND ea.window_end >= NOW()
                  ORDER BY ea.window_start ASC";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $stud_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return array();
    } catch (Exception $e) {
        error_log("Error fetching scheduled exams: " . $e->getMessage());
        return array();
    }
}

// Get scheduled exams
$examSchedules = getScheduledExams($_SESSION['stud_id']);

// Get announcements from database
function getActiveAnnouncements($limit = 2) {
    global $conn;
    
    // Check if connection exists
    if (!$conn) {
        return array(); // Return empty array if no connection
    }
    
    try {
        $query = "SELECT * FROM announcements 
                  WHERE status = 'active' 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Log error if needed
        error_log("Error fetching announcements: " . $e->getMessage());
        return array(); // Return empty array on error
    }
}

// Fetch announcements
$announcements = getActiveAnnouncements(2); // Get only 2 most recent announcements

// Active page for sidebar highlighting
$activePage = 'dashboard';

// Check if student already registered for the qualifying exam
$already_registered = false;
$reg_stmt = $conn->prepare("SELECT student_id, status FROM register_studentsqe WHERE stud_id = ? ORDER BY registration_date DESC LIMIT 1");
$reg_stmt->bind_param("i", $stud_id);
$reg_stmt->execute();
$reg_result = $reg_stmt->get_result();
if ($reg_result && $reg_result->num_rows > 0) {
    $already_registered = true;
    $registration_status = $reg_result->fetch_assoc()['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - PUP Qualifying Exam Portal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/main.css">
    <style>
        /* Updated sidebar styles */
        .sidebar {
            height: 100vh;
            padding-bottom: 0;
            position: fixed;
            overflow-y: auto;
            z-index: 99;
        }
        
        /* Updated main-content styles */
        .main-content {
            padding-bottom: 20px;
            margin-left: 250px; /* Match sidebar width */
            overflow-x: hidden;
        }
        
        /* Updated footer styles */
        footer {
            position: relative;
            margin-top: 0;
            padding: 15px 0;
        }
        
        /* Updated main-wrapper styles */
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
=======
            margin-left: 0 !important;
            padding-left: 0 !important;
>>>>>>> Stashed changes
        }
        
        /* Dashboard grid spacing */
        .dashboard-grid {
            margin-bottom: 30px; /* Reduced from 90px */
        }
        
        /* Additional page-specific styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        /* Fix footer overlap issue */
        .main-content {
            padding-bottom: 80px !important; /* Ensure content doesn't get hidden behind footer */
        }
        
        /* Fix sidebar height to extend to footer */
        .sidebar {
            height: auto !important; /* Changed from fixed height to auto */
            min-height: calc(100vh - 80px) !important; /* Minimum height */
            bottom: 0;
            padding-bottom: 60px; /* Reduced padding to prevent overlap with footer */
            z-index: 99; /* Ensure sidebar is above content but below overlay */
            position: fixed; /* Keep it fixed on desktop */
            overflow-y: auto; /* Allow scrolling if content is too tall */
        }
        
        /* Footer positioning */
        footer {
            position: relative !important; /* Changed from fixed to relative */
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 98; /* Below sidebar but above main content */
            background-color: var(--primary); /* Changed from white to primary color */
            color: white; /* Text color changed to white for contrast */
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            margin-top: 20px;
            clear: both;
        }
        
        /* Footer text color */
        footer p {
            color: white;
            margin: 0;
            text-align: center;
        }
        
        /* Improved sidebar overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Main wrapper adjustments for better footer positioning */
        .main-wrapper {
            display: flex;
            min-height: calc(100vh - 140px); /* Account for header and footer */
            position: relative;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        /* Main content adjustments */
        .main-content {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            flex: 1;
            padding: 20px;
            padding-bottom: 30px !important; /* Reduced padding */
            margin-left: 250px; /* Match sidebar width */
            overflow-x: hidden; /* Prevent horizontal scroll */
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            margin-left: 0 !important;
            padding-left: 0 !important;
            width: 100% !important;
            box-sizing: border-box;
            max-width: 1200px;
            margin: 0 auto !important;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        }
        
        /* Improved sidebar animation for mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 999;
                position: fixed;
                top: 80px;
                left: 0;
                width: 250px;
                max-width: 80%;
                height: calc(100vh - 80px) !important; /* Fixed height on mobile */
                padding-bottom: 100px; /* Extra padding to ensure scrollability */
            }
            
            .sidebar.active {
                transform: translateX(0);
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            }
            
            body.sidebar-open {
                overflow: hidden;
            }
            
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background-color: var(--primary);
                color: white;
                border: none;
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 997;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                cursor: pointer;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 15px;
                padding-bottom: 20px !important;
            }
            
            footer {
                margin-top: 0;
            }
            
            /* Ensure sidebar doesn't overlap with footer on mobile */
            .sidebar {
                padding-bottom: 80px;
            }
        }
        
        /* Make footer non-fixed on larger screens */
        @media (min-width: 769px) {
            footer {
                position: relative !important;
                margin-top: 20px;
            }
            
            .main-content {
                padding-bottom: 30px !important;
            }
            
            .dashboard-grid {
                margin-bottom: 30px;
            }
            
            /* Hide mobile menu toggle on desktop */
            .menu-toggle {
                display: none;
            }
        }

        /* Improved dropdown menu animation */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
            min-width: 200px;
            z-index: 1000;
            transform: translateY(10px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        
        .dropdown-menu.active {
            display: block;
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Exam Schedule Styles */
        .exam-list {
            list-style: none;
        }
        
        .exam-item {
            padding: 15px;
            border-radius: 6px;
            background-color: var(--gray-light);
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exam-info h4 {
            font-size: 18px;
            margin-bottom: 5px;
            color: var(--primary);
        }
        
        .exam-info p {
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.7;
            margin: 2px 0;
        }
        
        .exam-info p:last-child {
            color: var(--primary);
            font-weight: 500;
            opacity: 1;
        }
        
        .exam-status {
            background-color: var(--accent);
            color: var(--text-dark);
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
            text-align: center;
            min-width: 100px;
        }
        
        .no-items {
            padding: 20px 0;
            text-align: center;
            color: var(--text-dark);
            opacity: 0.7;
        }
        
        /* Announcements Styles */
        .announcement-item {
            padding: 15px;
            border-left: 4px solid var(--primary);
            background-color: var(--gray-light);
            margin-bottom: 15px;
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .announcement-header h4 {
            font-size: 18px;
            color: var(--primary);
            margin: 0;
        }
        
        .announcement-date {
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.7;
        }
        
        .announcement-content {
            font-size: 14px;
            line-height: 1.5;
            color: var(--text-dark);
        }
        
        .announcement-content p {
            margin: 0;
        }
        
        /* Registration Section */
        .registration-section {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .registration-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(117, 52, 58, 0.15);
            padding: 35px;
            border-left: 5px solid var(--primary);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .registration-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(117, 52, 58, 0.25);
        }
        
        .registration-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(117, 52, 58, 0.08) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            z-index: -1;
        }
        
        .registration-info {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            position: relative;
        }
        
        .registration-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 8px 15px rgba(117, 52, 58, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .registration-icon::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: transparent;
            border: 2px solid rgba(117, 52, 58, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.5;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .registration-icon .material-symbols-rounded {
            font-size: 40px;
        }
        
        .registration-text h3 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .registration-text p {
            font-size: 16px;
            color: var(--text-dark);
            opacity: 0.8;
            line-height: 1.6;
        }
        
        .registration-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 6px 15px rgba(117, 52, 58, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .registration-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: width 0.3s ease;
            z-index: -1;
        }
        
        .registration-action:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(117, 52, 58, 0.3);
        }
        
        .registration-action:hover::before {
            width: 100%;
        }
        
        .registration-badge {
            position: absolute;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            top: -10px;
            right: -10px;
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
=======
            top: 1px;
            right: -2px;
>>>>>>> Stashed changes
            background: #d4af37;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transform: rotate(5deg);
            z-index: 2;
        }
        
        @media (max-width: 768px) {
            .registration-info {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .registration-text h3 {
                font-size: 24px;
            }
            
            .registration-action {
                width: 100%;
                padding: 14px 20px;
                font-size: 16px;
            }
            
            .registration-icon {
                width: 70px;
                height: 70px;
            }
            
            .registration-icon .material-symbols-rounded {
                font-size: 35px;
            }

            /* Dashboard grid becomes single column on mobile */
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            /* Adjust padding for mobile */
            .registration-card {
                padding: 25px 20px;
            }

            /* Make page title more compact */
            .page-title h2 {
                font-size: 24px;
                margin-bottom: 5px;
            }

            .page-title p {
                font-size: 14px;
            }
            
            /* Make card headers more compact */
            .card-header {
                padding: 15px;
            }
            
            .card-header h3 {
                font-size: 18px;
            }
            
            /* Adjust dashboard card padding */
            .dashboard-card {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            /* Adjust notice banner */
            .notice-banner {
                padding: 15px;
                margin-bottom: 20px;
            }
        }

        /* Improved responsiveness for exam items */
        @media (max-width: 576px) {
            .exam-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }
            
            .exam-info {
                margin-bottom: 15px;
                width: 100%;
            }
            
            .exam-status {
                width: 100%;
                text-align: center;
                margin-top: 10px;
            }

            .exam-action {
                display: block;
                width: 100%;
                text-align: center;
                padding: 8px 0;
            }

            /* Make announcement header stack on very small screens */
            .announcement-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .announcement-date {
                margin-top: 5px;
            }

            /* Adjust notice banner for small screens */
            .notice-banner {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }

            .notice-banner .material-symbols-rounded {
                margin-bottom: 10px;
            }
            
            /* Adjust header elements */
            .logo-text h1 {
                font-size: 20px;
            }
            
            .logo-text p {
                font-size: 12px;
            }
            
            .logo img {
                width: 40px;
                height: 40px;
            }
        }

        /* Additional responsiveness for extra small screens */
        @media (max-width: 400px) {
            .registration-text h3 {
                font-size: 20px;
            }

            .registration-text p {
                font-size: 14px;
            }

            .registration-action {
                font-size: 16px;
                padding: 12px 15px;
            }

            .registration-badge {
                font-size: 12px;
                padding: 4px 10px;
            }

            .exam-info h4 {
                font-size: 16px;
            }

            .exam-info p {
                font-size: 13px;
            }
        }
        
        /* Campus Notice */
        .campus-notice {
            background-color: var(--warning);
            color: var(--text-dark);
            padding: 10px 15px;
            border-radius: 4px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Improve header responsiveness */
        @media (max-width: 576px) {
            .header-content {
                flex-direction: column;
                align-items: center;
            }

            .logo {
                margin-bottom: 15px;
            }

            .nav-links {
                width: 100%;
                justify-content: space-around;
            }
        }

        /* Improve exam-action styling */
        .exam-action {
            display: inline-block;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            transition: all 0.3s;
        }

        .exam-action:hover {
            color: var(--primary);
        }

        /* Make notice banner more responsive */
        .notice-banner {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            margin-bottom: 25px;
        }

        .notice-banner .material-symbols-rounded {
            color: #856404;
            font-size: 24px;
        }

        .notice-content h4 {
            margin: 0 0 5px 0;
            color: #856404;
        }

        .notice-content p {
            margin: 0;
            font-size: 14px;
            color: #856404;
        }
        
        /* Welcome Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            overflow-y: auto;
            opacity: 0;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            animation: fadeIn 0.3s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .modal.active {
            display: block;
            visibility: visible;
            opacity: 1;
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0;
                visibility: hidden;
            }
            to { 
                opacity: 1;
                visibility: visible;
            }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        }
        
        .modal-content {
            position: relative;
            background-color: white;
            margin: 5vh auto;
            max-width: 700px;
            width: 90%;
            border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalSlideIn 0.4s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .modal-logo {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }
        
        .modal-header h2 {
            font-size: 24px;
            margin: 0;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            color: white;
            cursor: pointer;
            opacity: 0.8;
            transition: all 0.2s;
        }
        
        .close-modal:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .modal-body {
            padding: 25px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .welcome-section {
            margin-bottom: 25px;
        }
        
        .welcome-section h3 {
            color: var(--primary);
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 12px;
            position: relative;
            padding-left: 15px;
        }
        
        .welcome-section h3::before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            height: 70%;
            width: 4px;
            background-color: var(--primary);
            border-radius: 2px;
        }
        
        .welcome-section p {
            margin: 0;
            font-size: 15px;
            line-height: 1.6;
            color: var(--text-dark);
        }
        
        .requirements-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .requirements-list li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            background-color: var(--gray-light);
            padding: 15px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .requirements-list li:hover {
            background-color: rgba(212, 175, 55, 0.1);
            transform: translateX(5px);
        }
        
        .req-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .req-details {
            flex: 1;
        }
        
        .req-details strong {
            display: block;
            margin-bottom: 5px;
            color: var(--text-dark);
            font-size: 16px;
        }
        
        .req-details p {
            margin: 0;
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.8;
        }
        
        .modal-footer {
            background-color: var(--gray-light);
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--gray);
        }
        
        .dont-show-again {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-dark);
            cursor: pointer;
        }
        
        .modal-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(117, 52, 58, 0.25);
        }
        
        .modal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(117, 52, 58, 0.35);
        }
        
        body.modal-open {
            overflow: hidden;
        }
        
        /* Responsive modal styles */
        @media (max-width: 768px) {
            .modal-content {
                margin: 10px auto;
                width: 95%;
                max-height: 95vh;
            }
            
            .modal-header {
                padding: 15px 20px;
            }
            
            .modal-logo {
                width: 40px;
                height: 40px;
            }
            
            .modal-header h2 {
                font-size: 20px;
            }
            
            .modal-body {
                padding: 15px;
            }
            
            .welcome-section h3 {
                font-size: 18px;
            }
            
            .welcome-section p {
                font-size: 14px;
            }
            
            .modal-footer {
                padding: 15px;
                flex-direction: column;
                gap: 15px;
            }
            
            .modal-btn {
                width: 100%;
            }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        }

        /* Updated header styles */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--primary);
            color: #fff;
            width: 100%;
            min-height: 80px;
            padding: 0 32px;
            box-sizing: border-box;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .logo-text h1, .logo-text p {
            color: #fff;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-left: auto;
        }
        .nav-links a {
            display: flex;
            align-items: center;
            gap: 7px;
           
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: 500;
            font-size: 1rem;
            transition: background 0.2s, color 0.2s;
        }
        .nav-links a.active, .nav-links a:hover {
            background-color: rgba(118, 51, 56, 1);
            color: #fff;
        }
        .nav-links .material-symbols-rounded {
            font-size: 22px;
            color: #fff;
        }
        #notifications {
            margin-left: 8px;
            color: #fff;
            font-size: 22px;
            display: flex;
            align-items: center;
        }
        .profile-menu {
            margin-left: 10px;
            position: relative;
        }
        .profile-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #fff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            border: 2px solid #fff;
            overflow: hidden;
        }
        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        @media (max-width: 900px) {
            .header-content {
                gap: 18px;
                padding: 0 8px;
            }
        }
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 10px 8px;
                min-height: 60px;
            }
            .nav-links {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 8px;
            }
            .logo img {
                width: 32px;
                height: 32px;
            }
            .profile-icon {
                width: 30px;
                height: 30px;
                font-size: 0.95rem;
            }
        }
        @media (max-width: 1240px) {
            .main-content {
                max-width: 98vw;
                padding-left: 8px;
                padding-right: 8px;
            }
        }

        /* Alert Message Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        
        .alert .material-symbols-rounded {
            font-size: 24px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>STREAMS</h1>
                <p>Student Dashboard</p>
            </div>
        </div>
        <nav class="nav-links">
            <a href="stud_dashboard.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">dashboard</span>
                Dashboard
            </a>
            <?php if ($is_ip_verified): ?>
            <a href="exam_instructions.php" class="<?php echo $activePage == 'take_exam' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">quiz</span>
                Take Exam
            </a>
            <?php endif; ?>
            <a href="exam_registration_status.php" class="<?php echo $activePage == 'registration' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">app_registration</span>
                Registration Status
            </a>
            <a href="stud_result.php" class="<?php echo $activePage == 'results' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">grade</span>
                Results
            </a>
         
            <div class="profile-menu">
                <a href="#" id="profile-menu">
                    <div class="profile-icon">
                        <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                            <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($_SESSION['firstname'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="dropdown-menu">
                    
                    <a href="stud_profile.php" class="dropdown-item">
                        <span class="material-symbols-rounded">person</span>
                        Profile
                    </a>
                    <a href="stud_logout.php" class="dropdown-item">
                        <span class="material-symbols-rounded">logout</span>
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menuToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-profile">
                <div class="profile-image">
                    <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                        <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <?php echo substr($_SESSION['firstname'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <h3><?php echo $_SESSION['firstname'] . ' ' . $_SESSION['lastname']; ?></h3>
                <p>Student</p>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="stud_dashboard.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">dashboard</span>
                        Dashboard
                    </a>
                </li>
              
                <li>
                    <a href="exam_instructions.php" class="<?php echo $activePage == 'take_exam' ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">quiz</span>
                        Take Exam
                    </a>
                </li>
               
                <li>
                    <a href="exam_registration_status.php" class="<?php echo $activePage == 'registration' ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">app_registration</span>
                        Exam Registration Status
                    </a>
                </li>
                <li>
                    <a href="stud_result.php" class="<?php echo $activePage == 'results' ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">grade</span>
                        Exam Results
                    </a>
                </li>
                <li>
                    <a href="stud_profile.php" class="<?php echo $activePage == 'profile' ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">person</span>
                        Profile
                    </a>
                </li>
                <li>
                    <a href="stud_logout.php">
                        <span class="material-symbols-rounded">logout</span>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>
        
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-title">
                <h2>Welcome, <?php echo $firstname; ?>!</h2>
                <p>View exam schedules, announcements, and register for the CCIS Qualifying Exam</p>
            </div>

            <!-- Exam Registration Section - Moved to top -->
            <div class="registration-section" style="margin-top: 0; margin-bottom: 30px;">
                <div class="registration-card">
                    <div class="registration-badge">Important!</div>
                    <div class="registration-info">
                        <div class="registration-icon">
                            <span class="material-symbols-rounded">app_registration</span>
                        </div>
                        <div class="registration-text">
                            <h3>CCIS Qualifying Exam Registration</h3>
                            <p>Register for the upcoming CCIS Qualifying Exam to advance your academic journey. This is a required step for all transferees, shiftees, and ladderized students.</p>
                        </div>
                    </div>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                    <a href="qualiexam_register.php" class="registration-action">
                        <span class="material-symbols-rounded">how_to_reg</span>
                        Register for Qualifying Exam
                    </a>
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                    <?php if (!$already_registered): ?>
                        <a href="qualiexam_register.php" class="registration-action">
                            <span class="material-symbols-rounded">how_to_reg</span>
                            Register for Qualifying Exam
                        </a>
                    <?php else: ?>
                        <div class="registration-action" style="background: #e2e3e5; color: #888; cursor: not-allowed; pointer-events: none;">
                            <span class="material-symbols-rounded">check_circle</span>
                            You have already registered for the Qualifying Exam<?php 
                            if (!empty($registration_status)) {
                                $display_status = strtolower($registration_status);
                                if ($display_status === 'needs_review' || $display_status === 'pending') {
                                    echo " (Pending)";
                                } else {
                                    echo " (" . ucfirst($registration_status) . ")";
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                </div>
            </div>

            <!-- Important Notice Banner - Keep only this one -->
            <div class="notice-banner">
                <span class="material-symbols-rounded">info</span>
                <div class="notice-content">
                    <h4>Important Notice</h4>
                    <p>All CCIS Qualifying Exams will be conducted on-campus only. Please make sure to register and check the Announcement for more details.</p>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Exam Schedule Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Exam Schedule</h3>
                    </div>
                    
                    <div class="exam-list">
                        <?php if (empty($examSchedules)): ?>
                            <div class="no-items">
                                <p>No qualifying exams have been scheduled yet. Please check back later.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($examSchedules as $exam): ?>
                                <div class="exam-item">
                                    <div class="exam-info">
                                        <h4><?php echo htmlspecialchars($exam['title']); ?></h4>
                                        <p>
                                            Date: <?php echo date('F d, Y', strtotime($exam['window_start'])); ?> | 
                                            Time: <?php echo date('h:i A', strtotime($exam['window_start'])); ?>
                                        </p>
                                        <p>Venue: <?php echo htmlspecialchars($exam['venue']); ?></p>
                                        <p>Type: <?php echo htmlspecialchars(ucfirst($exam['exam_type'])); ?></p>
                                    </div>
                                    <div>
                                        <?php
                                        $check_assignment = "SELECT 1 FROM exam_assignments ea 
                                                           JOIN register_studentsqe rs ON ea.student_id = rs.student_id
                                                           WHERE ea.exam_id = ? 
                                                           AND rs.email = ? 
                                                           AND ea.completion_status = 'pending'";
                                        $stmt = $conn->prepare($check_assignment);
                                        $stmt->bind_param("is", $exam['exam_id'], $_SESSION['email']);
                                        $stmt->execute();
                                        $is_assigned = $stmt->get_result()->num_rows > 0;
                                        ?>
                                        
                                        <div class="exam-status">
                                            <?php if ($is_assigned): ?>
                                                <?php if ($is_ip_verified): ?>
                                                    <a href="javascript:void(0)" onclick="validateExamSchedule(<?php echo $exam['exam_id']; ?>)" class="exam-action">
                                                        Take Exam
                                                    </a>
                                                <?php else: ?>
                                                    <div class="exam-status" style="color: #dc3545;">
                                                        IP Not Verified
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="exam-status">
                                                    Not Assigned
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Announcements Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Announcements</h3>
                        <a href="stud_announcements.php" class="view-all">
                            View All <span class="material-symbols-rounded">arrow_forward</span>
                        </a>
                    </div>
                    
                    <div class="announcements-list">
                        <?php if (empty($announcements)): ?>
                            <div class="no-items">
                                <p>There are no announcements at this time.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-item">
                                    <div class="announcement-header">
                                        <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                        <span class="announcement-date">
                                            <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="announcement-content">
                                        <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-warning">
                    <span class="material-symbols-rounded">warning</span>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PUP Qualifying Exam Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- Welcome Modal for First-Time Login -->
    <div id="welcomeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <img src="img/Logo.png" alt="PUP Logo" class="modal-logo">
                <h2>Welcome to STREAMS</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="welcome-section">
                    <h3>Welcome to the CCIS Qualifying Exam Portal!</h3>
                    <p>This system is designed to help you register, prepare for, and take the College of Computer and Information Sciences (CCIS) Qualifying Examination.</p>
                </div>
                
                <div class="welcome-section">
                    <h3>About the Qualifying Exam</h3>
                    <p>The CCIS Qualifying Exam is a required assessment for all transferees, shiftees, and ladderized students. Successfully passing this exam is a prerequisite for admission to CCIS programs.</p>
                </div>
                
                <div class="welcome-section">
                    <h3>Registration Requirements</h3>
                    <ul class="requirements-list">
                        <li>
                            <span class="req-icon"><span class="material-symbols-rounded">description</span></span>
                            <div class="req-details">
                                <strong>Transcript of Records (TOR)</strong>
                                <p>A scanned copy of your most recent Transcript of Records</p>
                            </div>
                        </li>
                        <li>
                            <span class="req-icon"><span class="material-symbols-rounded">badge</span></span>
                            <div class="req-details">
                                <strong>School ID</strong>
                                <p>A scanned copy of your valid School ID</p>
                            </div>
                        </li>
                        <li>
                            <span class="req-icon"><span class="material-symbols-rounded">person</span></span>
                            <div class="req-details">
                                <strong>Personal Information</strong>
                                <p>Complete personal and academic details</p>
                            </div>
                        </li>
                        <li>
                            <span class="req-icon"><span class="material-symbols-rounded">school</span></span>
                            <div class="req-details">
                                <strong>Academic Background</strong>
                                <p>Information about your previous/current academic program</p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="welcome-section">
                    <h3>How to Get Started</h3>
                    <p>To register for the qualifying exam, click on the "Register for Qualifying Exam" button on your dashboard.</p>
                </div>
            </div>
            <div class="modal-footer">
                <label class="dont-show-again">
                    <input type="checkbox" id="dontShowAgain"> Don't show this again
                </label>
                <button id="welcomeModalClose" class="modal-btn">Get Started</button>
            </div>
        </div>
    </div>

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    <!-- Alert Modal -->
    <div id="alertModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="background: #fff3cd; color: #856404;">
                <span class="material-symbols-rounded" style="font-size: 24px; margin-right: 10px;">warning</span>
                <h2 style="margin: 0; font-size: 20px;">Notice</h2>
                <span class="close-modal" onclick="closeAlertModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p id="alertMessage" style="margin: 0; font-size: 16px; line-height: 1.5;"></p>
            </div>
            <div class="modal-footer" style="text-align: right;">
                <button class="modal-btn" onclick="closeAlertModal()" style="background: #856404;">OK</button>
            </div>
        </div>
    </div>

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    <script src="js/main.js"></script>
    
    <script>
        // Welcome Modal for First-Time Login
        document.addEventListener('DOMContentLoaded', function() {
            const welcomeModal = document.getElementById('welcomeModal');
            const closeModal = document.querySelector('.close-modal');
            const modalClose = document.getElementById('welcomeModalClose');
            const dontShowAgain = document.getElementById('dontShowAgain');
            
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            // Check if this is the first login (using localStorage)
            const hasSeenWelcome = localStorage.getItem('hasSeenWelcome');
            
            if (!hasSeenWelcome) {
                // Show the modal
                welcomeModal.style.display = 'block';
                document.body.classList.add('modal-open');
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            // Check if this is the first login (using both localStorage and session)
            const hasSeenWelcome = localStorage.getItem('hasSeenWelcome');
            const isFirstLogin = <?php echo isset($_SESSION['first_login']) ? 'true' : 'false'; ?>;
            
            // Show modal if it's first login or hasn't been seen before
            if (isFirstLogin || !hasSeenWelcome) {
                console.log('First login detected, showing welcome modal');
                // Show the modal with proper styling
                welcomeModal.classList.add('active');
                document.body.classList.add('modal-open');
                
                // Log for debugging
                console.log('Welcome modal should be visible');
                
                // Clear the first_login flag by making an AJAX call
                fetch('clear_first_login.php')
                    .then(response => response.json())
                    .then(data => {
                        console.log('First login flag cleared:', data);
                    })
                    .catch(error => {
                        console.error('Error clearing first login flag:', error);
                    });
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            }
            
            // Close modal when clicking the X
            closeModal.addEventListener('click', function() {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                welcomeModal.style.display = 'none';
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
                document.body.classList.remove('modal-open');
                
                if (dontShowAgain.checked) {
                    localStorage.setItem('hasSeenWelcome', 'true');
                }
            });
            
            // Close modal when clicking the Get Started button
            modalClose.addEventListener('click', function() {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                welcomeModal.style.display = 'none';
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
=======
                welcomeModal.classList.remove('active');
>>>>>>> Stashed changes
                document.body.classList.remove('modal-open');
                
                if (dontShowAgain.checked) {
                    localStorage.setItem('hasSeenWelcome', 'true');
                }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            });
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target == welcomeModal) {
                    welcomeModal.classList.remove('active');
                    document.body.classList.remove('modal-open');
                    
                    if (dontShowAgain.checked) {
                        localStorage.setItem('hasSeenWelcome', 'true');
                    }
                }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
            });
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target == welcomeModal) {
                    welcomeModal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    
                    if (dontShowAgain.checked) {
                        localStorage.setItem('hasSeenWelcome', 'true');
                    }
                }
            });
        });

        // Adjust sidebar and content height
        function adjustLayout() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const footer = document.querySelector('footer');
            const headerHeight = 80; // Approximate header height

            if (window.innerWidth >= 769) {
                // For desktop: Adjust sidebar and content height
                const availableHeight = window.innerHeight - headerHeight - footer.offsetHeight;
                sidebar.style.height = availableHeight + 'px';
                mainContent.style.minHeight = availableHeight + 'px';
            } else {
                // For mobile: Set fixed height
                sidebar.style.height = 'calc(100vh - 80px)';
            }
        }

        // Run on page load and resize
        window.addEventListener('load', adjustLayout);
        window.addEventListener('resize', adjustLayout);

        // Enhanced responsive behaviors
        document.addEventListener('DOMContentLoaded', function() {
            // Handle mobile menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mainContent = document.querySelector('.main-content');
            
            if (menuToggle && sidebar && sidebarOverlay) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    sidebarOverlay.classList.toggle('active');
                    document.body.classList.toggle('sidebar-open');
                    
                    // Adjust main content when sidebar is open
                    if (sidebar.classList.contains('active') && window.innerWidth < 769) {
                        mainContent.style.opacity = '0.7';
                    } else {
                        mainContent.style.opacity = '1';
                    }
                });
                
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                    mainContent.style.opacity = '1';
                });
            }
            
            // Close sidebar when clicking a link on mobile
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            if (sidebarLinks.length > 0) {
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 769) {
                            sidebar.classList.remove('active');
                            sidebarOverlay.classList.remove('active');
                            document.body.classList.remove('sidebar-open');
                            mainContent.style.opacity = '1';
                        }
                    });
                });
            }
            
            // Handle profile dropdown menu
            const profileMenu = document.getElementById('profile-menu');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            if (profileMenu && dropdownMenu) {
                profileMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdownMenu.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!profileMenu.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('active');
                    }
                });
            }
            
            // Adjust UI elements on window resize
            function handleResize() {
                adjustLayout();
                
                // Reset main content opacity
                mainContent.style.opacity = '1';
                
                // Close mobile menu if window is resized to desktop
                if (window.innerWidth >= 769) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            }
            
            window.addEventListener('resize', handleResize);
            
            // Initial adjustment
            adjustLayout();
        });

=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
=======
            });
        });

>>>>>>> Stashed changes
        // Handle profile dropdown menu
        document.addEventListener('DOMContentLoaded', function() {
            const profileMenu = document.getElementById('profile-menu');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            if (profileMenu && dropdownMenu) {
                profileMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdownMenu.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!profileMenu.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('active');
                    }
                });
            }
        });

        function showAlertModal(message) {
            const modal = document.getElementById('alertModal');
            const messageElement = document.getElementById('alertMessage');
            messageElement.innerHTML = message;
            modal.classList.add('active');
            document.body.classList.add('modal-open');
        }

        function closeAlertModal() {
            const modal = document.getElementById('alertModal');
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
        }

        function validateExamSchedule(examId) {
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = 'Checking...';
            button.style.pointerEvents = 'none';

            fetch('stud_dashboard.php?action=validate_exam&exam_id=' + examId)
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    button.innerHTML = originalText;
                    button.style.pointerEvents = 'auto';

                    if (data.error) {
                        // Show error message in modal
                        const errorMessage = data.message || 'This exam is not yet available.';
                        showAlertModal(errorMessage);
                    } else if (data.success) {
                        // Only proceed if we get a success response
                        window.location.href = 'exam_instructions.php?exam_id=' + examId;
                    }
                })
                .catch(error => {
                    // Reset button state
                    button.innerHTML = originalText;
                    button.style.pointerEvents = 'auto';
                    
                    console.error('Error:', error);
                    // Show error message in modal
                    showAlertModal('An error occurred while checking the exam schedule. Please try again.');
                });
        }

        // Intercept Take Exam nav link
        document.addEventListener('DOMContentLoaded', function() {
            const takeExamLink = document.querySelector('a[href="exam_instructions.php"]');
            if (takeExamLink) {
                takeExamLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Validate if student has an assigned exam
                    fetch('stud_dashboard.php?action=validate_exam')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = 'exam_instructions.php';
                            } else {
                                showAlertModal(data.message || 'No qualifying exam has been assigned to you at this time.');
                            }
                        })
                        .catch(() => {
                            showAlertModal('An error occurred while checking your exam assignment. Please try again.');
                        });
                });
            }
        });
    </script>
    <?php if (!empty($exam_error_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showAlertModal(<?php echo json_encode($exam_error_message); ?>);
            // Remove error param from URL
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('error');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>


