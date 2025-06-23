<?php
// Start the session
session_start();

// Include database connection
include 'config/config.php';
require_once 'config/ip_config.php'; // Include IP configurations

// Check if current IP is verified
$is_ip_verified = isCurrentIPVerified($conn);

// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    // Redirect to login page if not logged in
    header("Location: stud_register.php");
    exit();
}

// Check if student ID is provided
$student_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['stud_id'];

// Fetch student information
$query = "SELECT * FROM students WHERE stud_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Check if student is registered
$check_registration = "SELECT * FROM register_studentsqe WHERE student_id = ?";
$reg_stmt = $conn->prepare($check_registration);
$reg_stmt->bind_param("i", $student_id);
$reg_stmt->execute();
$registration = $reg_stmt->get_result()->fetch_assoc();

// Fetch only taken exams
$exam_query = "SELECT e.*, er.score, er.total_questions, er.completion_time 
              FROM exams e 
              INNER JOIN exam_results er ON e.exam_id = er.exam_id 
              WHERE er.student_id = ?
              ORDER BY er.completion_time DESC";
$exam_stmt = $conn->prepare($exam_query);
$exam_stmt->bind_param("i", $student_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();
$exams = [];
while ($row = $exam_result->fetch_assoc()) {
    $exams[] = $row;
}

// Calculate statistics only from taken exams
$total_exams = count($exams);
$completed_exams = 0;
$total_score = 0;

foreach ($exams as $exam) {
    if ($exam['score'] !== null) {
        $completed_exams++;
        $total_score += $exam['score'];
    }
}

$average_score = $completed_exams > 0 ? round($total_score / $completed_exams, 2) : 0;

// Active page for sidebar highlighting
$activePage = 'profile';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);

    // Check if email already exists for other users
    $check_email = "SELECT stud_id FROM students WHERE email = ? AND stud_id != ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("si", $email, $_SESSION['stud_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $update_error = "Email already exists";
    } else {
        // Update profile
        $update_query = "UPDATE students SET firstname = ?, lastname = ?, email = ?, contact_number = ?, address = ?, date_of_birth = ?, gender = ? WHERE stud_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssssi", $firstname, $lastname, $email, $contact_number, $address, $date_of_birth, $gender, $_SESSION['stud_id']);
        
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['email'] = $email;
            
            // Refresh student data
            $student['firstname'] = $firstname;
            $student['lastname'] = $lastname;
            $student['email'] = $email;
            $student['contact_number'] = $contact_number;
            $student['address'] = $address;
            $student['date_of_birth'] = $date_of_birth;
            $student['gender'] = $gender;
            
            $update_success = "Profile updated successfully";
        } else {
            $update_error = "Failed to update profile";
        }
    }
}

// Add this to the PHP section at the top after session_start()
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_picture']['name'];
    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($filetype, $allowed)) {
        $temp_name = $_FILES['profile_picture']['tmp_name'];
        $new_filename = 'profile_' . $_SESSION['stud_id'] . '.' . $filetype;
        $upload_path = 'uploads/profile_pictures/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // Delete old profile picture if it exists
        if (!empty($student['profile_picture'])) {
            $old_file = $student['profile_picture'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        if (move_uploaded_file($temp_name, $upload_path . $new_filename)) {
            // Store the full path in the database
            $profile_picture_path = $upload_path . $new_filename;
            $update_query = "UPDATE students SET profile_picture = ? WHERE stud_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $profile_picture_path, $_SESSION['stud_id']);
            
            if ($stmt->execute()) {
                $student['profile_picture'] = $profile_picture_path;
                $update_success = "Profile picture updated successfully";
                
                // Refresh the page to show the new profile picture
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $update_error = "Failed to update profile picture in database";
            }
        } else {
            $update_error = "Failed to upload profile picture";
        }
    } else {
        $update_error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - PUP Qualifying Exam Portal</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Custom styles for profile page */
        :root {
            --primary: #75343A;
            --primary-dark: #5a2930;
            --primary-light: #9e4a52;
            --secondary: #f8f0e3;
            --accent: #d4af37;
            --text-dark: #333333;
            --text-light: #ffffff;
            --gray-light: #f5f5f5;
            --gray: #e0e0e0;
        }
        
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
            flex: 1;
            padding: 20px;
            padding-bottom: 30px !important; /* Reduced padding */
            margin-left: 250px; /* Match sidebar width */
            overflow-x: hidden; /* Prevent horizontal scroll */
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
        
        /* Profile Container */
        .profile-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        /* Profile Header */
        .profile-header {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            background: white;
            padding: 2rem;
            border-bottom: 1px solid var(--gray);
        }
        
        .profile-picture-container {
            width: 200px;
            height: 200px;
            margin: 0;
            flex-shrink: 0;
        }
        
        .profile-info {
            flex: 1;
            padding-top: 1rem;
        }
        
        .profile-info h2 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .profile-info p {
            color: var(--text-dark);
            opacity: 0.7;
            margin-bottom: 1.5rem;
        }
        
        .profile-actions {
            margin-left: auto;
            padding-top: 1rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
        }
        
        .profile-name {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .profile-id {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
      
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .edit-btn {
            background: var(--primary);
            color: white;
            border: none;
        }
        
        .edit-btn:hover {
            background: var(--primary-dark);
        }
        
        .message-btn {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .message-btn:hover {
            background: var(--gray-light);
        }
        
        /* Profile Tabs */
        .profile-tabs {
            display: flex;
            gap: 1rem;
            padding: 1rem 2rem;
            background: var(--gray-light);
            border-bottom: 1px solid var(--gray);
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            background: none;
            border: none;
            color: #666;
        }
        
        .tab-btn:hover {
            color: var(--primary);
        }
        
        .tab-btn.active {
            background: var(--primary);
            color: white;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .tab-content h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 24px;
        }
        
        /* Personal Info */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        /* Exam History */
        .exam-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .exam-table th,
        .exam-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }
        
        .exam-table th {
            font-weight: 600;
            color: var(--text-dark);
            background: var(--gray-light);
        }
        
        .exam-table tr:hover {
            background: var(--gray-light);
        }
        
        .exam-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: #e6f7e6;
            color: #2e7d32;
        }
        
        .status-pending {
            background: #fff8e1;
            color: #f57c00;
        }
        
        .status-failed {
            background: #ffebee;
            color: #c62828;
        }
        
        .no-registration,
        .no-exams {
            text-align: center;
            padding: 3rem;
            background: var(--gray-light);
            border-radius: 8px;
        }

        .no-registration h3,
        .no-exams h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .no-registration p,
        .no-exams p {
            color: var(--text-dark);
            opacity: 0.7;
            margin-bottom: 1rem;
        }

        .no-registration .action-btn,
        .no-exams .action-btn {
            margin: 0 auto;
        }

        /* Edit Profile Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1100;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h2 {
            color: var(--primary);
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .close-modal:hover {
            color: var(--primary);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray);
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-actions {
            margin-top: 2rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .cancel-btn {
            background: var(--gray-light);
            color: var(--text-dark);
            border: none;
        }
        
        .cancel-btn:hover {
            background: var(--gray);
        }
        
        .save-btn {
            background: var(--primary);
            color: white;
            border: none;
        }
        
        .save-btn:hover {
            background: var(--primary-dark);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #e6f7e6;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }
        
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #c62828;
        }

        /* Profile Menu Styles */
        .profile-menu {
            position: relative;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--text-light);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
            min-width: 200px;
            z-index: 1000;
            color: var(--primary);
        }
        
        .profile-menu:hover .dropdown-menu {
            display: block;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            text-decoration: none;
            transition: background-color 0.3s;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background-color: var(--gray-light);
        }
        
        #profile-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-light);
        }

        .profile-picture-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }

        .profile-picture {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background-color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: var(--primary-dark);
            font-weight: 500;
            text-transform: uppercase;
            overflow: hidden;
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .change-picture {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .change-picture:hover {
            background-color: var(--primary-dark);
        }

        #profile-picture-input {
            display: none;
        }

        .modal-profile-picture {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 1rem;
            background: var(--gray-light);
            border-radius: 8px;
        }

        .profile-picture-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: var(--primary-dark);
            overflow: hidden;
            border: 3px solid var(--primary);
        }

        .profile-picture-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .picture-upload {
            flex: 1;
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
        }

        .upload-btn:hover {
            background: var(--primary-dark);
        }

        .hidden-input {
            display: none;
        }

        .upload-info {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }

        /* --- HEADER FLEX LAYOUT LIKE STUD_DASHBOARD.PHP --- */
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
        .profile-menu {
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
        /* --- MAIN CONTENT LAYOUT FIX AFTER SIDEBAR REMOVAL --- */
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-left: 0 !important;
            padding-left: 0 !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding-left: 0 !important;
            width: 100% !important;
            box-sizing: border-box;
            max-width: 1200px;
            margin: 0 auto !important;
        }
        @media (max-width: 1240px) {
            .main-content {
                max-width: 98vw;
                padding-left: 8px;
                padding-right: 8px;
            }
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
                <p>Student Profile</p>
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
                            <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture">
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
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menuToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-profile">
                <div class="profile-image">
                    <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture">
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
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-title">
                <h2>Student Profile</h2>
                <p>View and manage your profile information</p>
            </div>
            
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-picture-container">
                        <div class="profile-picture">
                            <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <?php 
                                    echo strtoupper(substr($student['firstname'], 0, 1) . substr($student['lastname'], 0, 1));
                                ?>
                            <?php endif; ?>
                        </div>
                        <label for="profile-picture-input" class="change-picture" title="Change Profile Picture">
                            <span class="material-symbols-rounded">photo_camera</span>
                        </label>
                        <input type="file" id="profile-picture-input" name="profile_picture" accept="image/*" class="hidden-input">
                    </div>
                    <div class="profile-info">
                        <h2><?php echo $student['firstname'] . ' ' . $student['lastname']; ?></h2>
                        <p><?php echo $student['email']; ?></p>
                
                    </div>
                    <div class="profile-actions">
                        <button class="action-btn edit-btn">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>
                </div>

                <div class="profile-tabs">
                    <button class="tab-btn active" data-tab="personal">Personal Information</button>
                    <button class="tab-btn" data-tab="exams">Exam History</button>
                </div>

                <div class="tab-content active" id="personal-tab">
                    <h2>Personal Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo $student['firstname'] . ' ' . $student['lastname']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo $student['email']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value"><?php echo $student['contact_number'] ?? 'Not provided'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?php echo $student['address'] ?? 'Not provided'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?php echo $student['date_of_birth'] ?? 'Not provided'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Gender</div>
                            <div class="info-value"><?php echo $student['gender'] ?? 'Not provided'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Enrollment Date</div>
                            <div class="info-value"><?php echo $student['enrollment_date'] ?? 'Not provided'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><?php echo ucfirst($student['status'] ?? 'Active'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="exams-tab">
                    <h2>Exam History</h2>
                    <?php if (!$registration): ?>
                    <div class="no-registration">
                        <span class="material-symbols-rounded" style="font-size: 48px; color: var(--primary); margin-bottom: 1rem;">app_registration</span>
                        <h3>No Registration Found</h3>
                        <p>You haven't registered for the qualifying exam yet. Click below to start your registration.</p>
                        <a href="qualiexam_register.php" class="action-btn edit-btn" style="display: inline-flex; margin-top: 1rem; text-decoration: none;">
                            <span class="material-symbols-rounded">add</span> Register Now
                        </a>
                    </div>
                    <?php elseif (empty($exams)): ?>
                    <div class="no-exams">
                        <span class="material-symbols-rounded" style="font-size: 48px; color: var(--primary); margin-bottom: 1rem;">quiz</span>
                        <h3>No Exam History</h3>
                        <p>You haven't taken any exams yet or none are currently scheduled.</p>
                    </div>
                    <?php else: ?>
                    <table class="exam-table">
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Date Taken</th>
                                <th>Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td><?php echo $exam['title']; ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($exam['completion_time'])); ?></td>
                                <td><?php echo $exam['score'] . '%'; ?></td>
                                <td>
                                    <?php if ($exam['score'] >= 70): ?>
                                        <span class="exam-status status-completed">Passed</span>
                                    <?php else: ?>
                                        <span class="exam-status status-failed">Failed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PUP Qualifying Exam Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-modal">&times;</button>
            </div>
            
            <?php if (isset($update_success)): ?>
            <div class="alert alert-success">
                <?php echo $update_success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($update_error)): ?>
            <div class="alert alert-error">
                <?php echo $update_error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-profile-picture">
                    <div class="profile-picture-preview">
                        <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <?php echo strtoupper(substr($student['firstname'], 0, 1) . substr($student['lastname'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="picture-upload">
                        <label for="modal-profile-picture-input" class="upload-btn">
                            <span class="material-symbols-rounded">photo_camera</span>
                            Change Picture
                        </label>
                        <input type="file" id="modal-profile-picture-input" name="profile_picture" accept="image/*" class="hidden-input">
                        <p class="upload-info">Maximum file size: 5MB<br>Supported formats: JPG, JPEG, PNG</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo $student['firstname']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo $student['lastname']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $student['email']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" value="<?php echo $student['contact_number'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $student['date_of_birth'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($student['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($student['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($student['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo $student['address'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="action-btn cancel-btn close-modal">Cancel</button>
                    <button type="submit" name="update_profile" class="action-btn save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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

            // Tab switching functionality
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Edit Profile Modal
            const modal = document.getElementById('editProfileModal');
            const editBtn = document.querySelector('.edit-btn');
            const closeBtns = document.querySelectorAll('.close-modal');
            
            editBtn.addEventListener('click', function() {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
            
            closeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
            });
            
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            });

            // Profile Picture Upload
            const profilePictureInput = document.getElementById('profile-picture-input');
            
            profilePictureInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const form = this.closest('form');
                    form.submit();
                }
            });

            // Profile Picture Preview in Modal
            const modalProfilePictureInput = document.getElementById('modal-profile-picture-input');
            const profilePicturePreview = document.querySelector('.profile-picture-preview');
            
            modalProfilePictureInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (profilePicturePreview.querySelector('img')) {
                            profilePicturePreview.querySelector('img').src = e.target.result;
                        } else {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            profilePicturePreview.innerHTML = '';
                            profilePicturePreview.appendChild(img);
                        }
                    }
                    reader.readAsDataURL(this.files[0]);
                }
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
        // Tab switching functionality
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
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
            });
        });
        // Edit Profile Modal
        const modal = document.getElementById('editProfileModal');
        const editBtn = document.querySelector('.edit-btn');
        const closeBtns = document.querySelectorAll('.close-modal');
        editBtn.addEventListener('click', function() {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
        closeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        });
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        });
        // Profile Picture Upload
        const profilePictureInput = document.getElementById('profile-picture-input');
        profilePictureInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const form = this.closest('form');
                form.submit();
            }
        });
        // Profile Picture Preview in Modal
        const modalProfilePictureInput = document.getElementById('modal-profile-picture-input');
        const profilePicturePreview = document.querySelector('.profile-picture-preview');
        modalProfilePictureInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (profilePicturePreview.querySelector('img')) {
                        profilePicturePreview.querySelector('img').src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        profilePicturePreview.innerHTML = '';
                        profilePicturePreview.appendChild(img);
                    }
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        // Profile dropdown menu
        document.addEventListener('DOMContentLoaded', function() {
            const profileMenu = document.getElementById('profile-menu');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            if (profileMenu && dropdownMenu) {
                profileMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdownMenu.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!profileMenu.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('active');
                    }
                });
            }
        });

        
    </script>
</body>
</html>
