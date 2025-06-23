<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: stud_register.php");
    exit();
}

// Get student information from session
$stud_id = $_SESSION['stud_id'];
$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$email = $_SESSION['email'] ?? null;

// Database connection
require_once 'config/config.php';
require_once 'config/ip_config.php'; // Include IP configurations

// Check if current IP is verified
$is_ip_verified = isCurrentIPVerified($conn);

// First, check if registration exists for this student by student ID 
$query = "SELECT rs.*, 
          DATE_FORMAT(rs.registration_date, '%M %d, %Y') as formatted_date,
          DATE_FORMAT(rs.registration_date, '%h:%i %p') as formatted_time,
          r.reason as rejection_reason
          FROM register_studentsqe rs
          LEFT JOIN rejection_reasons r ON rs.student_id = r.student_id
          WHERE rs.stud_id = ? 
          ORDER BY rs.registration_date DESC 
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $stud_id);
$stmt->execute();
$result = $stmt->get_result();
$registration = $result->fetch_assoc();

// If registration found but email in session doesn't match, update the session email
if ($registration && $email !== $registration['email']) {
    $_SESSION['email'] = $registration['email'];
    $email = $_SESSION['email'];
}

// If no registration found by student ID, try with email if it's set
if (!$registration && !empty($email)) {
    $query = "SELECT rs.*, 
              DATE_FORMAT(rs.registration_date, '%M %d, %Y') as formatted_date,
              DATE_FORMAT(rs.registration_date, '%h:%i %p') as formatted_time,
              r.reason as rejection_reason
              FROM register_studentsqe rs
              LEFT JOIN rejection_reasons r ON rs.student_id = r.student_id
              WHERE rs.email = ? 
              ORDER BY rs.registration_date DESC 
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $registration = $result->fetch_assoc();
}

// Fetch student profile picture
$query = "SELECT profile_picture FROM students WHERE stud_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['stud_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Active page for sidebar highlighting
$activePage = 'registration';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status - PUP Qualifying Exam Portal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/main.css">
    <style>
        /* Additional page-specific styles */
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
        
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        .sidebar-profile p {
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.7;
        }
        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--accent);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--primary-dark);
            font-weight: bold;
            overflow: hidden;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s;
            font-weight: 500;
        }

        .sidebar-menu a:hover {
            background-color: var(--gray-light);
            color: var(--primary);
        }

        .sidebar-menu a.active {
            background-color: var(--primary-light);
            color: var(--text-light);
        }

        .sidebar-menu .material-symbols-rounded {
            font-size: 20px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .profile-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-weight: bold;
            overflow: hidden;
        }

        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        /* Page Title Styles */
        .page-title {
            margin-bottom: 30px;
        }

        .page-title h2 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .page-title p {
            color: var(--text-dark);
            opacity: 0.8;
        }

        /* Add these button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--text-light);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        /* No registration styles */
        /* Add to your existing styles */
        .main-wrapper {
            display: flex;
            margin-top: 80px;
            flex: 1;
        }

=======
        /* Fix footer overlap issue */
>>>>>>> Stashed changes
=======
        /* Fix footer overlap issue */
>>>>>>> Stashed changes
=======
        /* Fix footer overlap issue */
>>>>>>> Stashed changes
=======
        /* Fix footer overlap issue */
>>>>>>> Stashed changes
=======
        /* Fix footer overlap issue */
>>>>>>> Stashed changes
=======
        /* Fix footer overlap issue */
>>>>>>> Stashed changes
=======
        /* Fix footer overlap issue */
>>>>>>> Stashed changes
=======
        /* Fix footer overlap issue */
>>>>>>> Stashed changes
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

        /* Details Grid Styles */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .detail-group {
            background-color: var(--gray-light);
            padding: 25px;
            border-radius: 8px;
        }

        .detail-group h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 18px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-dark);
        }

        .detail-value {
            color: var(--text-dark);
            opacity: 0.8;
        }

        /* Document Links */
        .document-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .document-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background-color: white;
            color: var(--primary);
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .document-link:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Status Badge */
        .status-badge {
            margin-left: auto;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-badge.status-pending {
            background-color: var(--warning);
            color: var(--text-dark);
        }

        .status-badge.status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-badge.status-accepted {
            background-color: #d4edda;
            color: #155724;
        }

        /* Status Icon */
        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-icon .material-symbols-rounded {
            color: white;
            font-size: 24px;
        }
        
        /* No Registration Found Styles */
        .no-registration {
            text-align: center;
            padding: 40px 20px;
            background-color: var(--gray-light);
            border-radius: 8px;
            margin: 20px 0;
        }

        .no-registration .material-symbols-rounded {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .no-registration h3 {
            color: var(--primary);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .no-registration p {
            color: var(--text-dark);
            opacity: 0.8;
            margin-bottom: 20px;
        }

        .rejection-reason {
            display: block;
            margin-top: 10px;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 4px;
            font-style: italic;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .status-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .status-badge {
                margin-left: 0;
            }
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

        .nav-links a.active, .nav-links a:hover {
            background: rgba(255,255,255,0.13);
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
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            overflow-y: auto;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal.active {
            display: block;
            visibility: visible;
            opacity: 1;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 15vh auto;
            max-width: 500px;
            width: 90%;
            border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalSlideIn 0.4s ease-out;
        }

        .modal-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            text-align: right;
        }

        .modal-btn {
            background: #856404;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .modal-btn:hover {
            background: #6d5204;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #856404;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #6d5204;
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

        body.modal-open {
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Registration Status</p>
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
    </header>

    <div class="main-wrapper">
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menuToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
                <!-- Sidebar -->
                <aside class="sidebar">
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
                    <a href="exam_registration_status.php" class="<?php echo $activePage == 'registration_status' ? 'active' : ''; ?>">
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
        <nav class="nav-links">
            <a href="stud_dashboard.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">dashboard</span>
                Dashboard
            </a>
            <?php if ($is_ip_verified): ?>
            <a href="javascript:void(0)" onclick="validateHeaderExamSchedule()" class="<?php echo $activePage == 'take_exam' ? 'active' : ''; ?>">
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

    <div class="main-wrapper">
        <main class="main-content">
            <div class="page-title">
                <h2>Registration Status</h2>
                <p>View your qualifying exam registration details and status</p>
            </div>

            <?php if ($registration): ?>
                <div class="status-card">
                    <div class="status-header">
                        <div class="status-icon">
                            <span class="material-symbols-rounded">description</span>
                        </div>
                        <div>
                            <h3>Qualifying Exam Registration</h3>
                            <p>Submitted on <?php echo $registration['formatted_date']; ?> at <?php echo $registration['formatted_time']; ?></p>
                        </div>
                        <div class="status-badge status-<?php echo strtolower($registration['status']); ?>">
                            <?php echo ucfirst($registration['status']); ?>
                        </div>
                    </div>

                    <div class="details-grid">
                        <!-- Personal Information -->
                        <div class="detail-group">
                            <h3>
                                <span class="material-symbols-rounded">person</span>
                                Personal Information
                            </h3>
                            <div class="detail-row">
                                <span class="detail-label">Full Name</span>
                                <span class="detail-value">
                                    <?php echo $registration['first_name'] . ' ' . 
                                             ($registration['middle_name'] ? $registration['middle_name'] . ' ' : '') . 
                                             $registration['last_name']; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Gender</span>
                                <span class="detail-value"><?php echo $registration['gender']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date of Birth</span>
                                <span class="detail-value"><?php echo date('F d, Y', strtotime($registration['dob'])); ?></span>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="detail-group">
                            <h3>
                                <span class="material-symbols-rounded">contact_mail</span>
                                Contact Information
                            </h3>
                            <div class="detail-row">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?php echo $registration['email']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Contact Number</span>
                                <span class="detail-value"><?php echo $registration['contact_number']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Address</span>
                                <span class="detail-value"><?php echo $registration['street']; ?></span>
                            </div>
                        </div>

                        <!-- Academic Information -->
                        <div class="detail-group">
                            <h3>
                                <span class="material-symbols-rounded">school</span>
                                Academic Information
                            </h3>
                            <div class="detail-row">
                                <span class="detail-label">Student Type</span>
                                <span class="detail-value"><?php echo ucfirst($registration['student_type']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Previous School</span>
                                <span class="detail-value"><?php echo $registration['previous_school']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Previous Program</span>
                                <span class="detail-value"><?php echo $registration['previous_program']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Desired Program</span>
                                <span class="detail-value"><?php echo $registration['desired_program']; ?></span>
                            </div>
                            <?php if ($registration['student_type'] != 'ladderized'): ?>
                            <div class="detail-row">
                                <span class="detail-label">Years of Residency</span>
                                <span class="detail-value"><?php echo $registration['year_level']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Documents -->
                        <div class="detail-group">
                            <h3>
                                <span class="material-symbols-rounded">folder</span>
                                Submitted Documents
                            </h3>
                            <div class="document-links">
                                <a href="<?php echo $registration['tor']; ?>" class="document-link" target="_blank">
                                    <span class="material-symbols-rounded">description</span>
                                    Transcript of Records
                                </a>
                                <a href="<?php echo $registration['school_id']; ?>" class="document-link" target="_blank">
                                    <span class="material-symbols-rounded">badge</span>
                                    School ID
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if ($registration['status'] === 'pending'): ?>
                        <p class="status-note">
                            <span class="material-symbols-rounded">info</span>
                            Your registration is currently under review. You will be notified once it has been processed.
                        </p>
                    <?php elseif ($registration['status'] === 'rejected' && isset($registration['rejection_reason'])): ?>
                        <p class="status-note status-note-rejected">
                            <span class="material-symbols-rounded">error</span>
                            Your registration has been rejected for the following reason:
                            <br>
                            <span class="rejection-reason"><?php echo htmlspecialchars($registration['rejection_reason']); ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="status-card">
                    <div class="no-registration">
                        <span class="material-symbols-rounded">app_registration</span>
                        <h3>No Registration Found</h3>
                        <p>You haven't registered for the qualifying exam yet. Click below to start your registration.</p>
                        <a href="qualiexam_register.php" class="btn">
                            Register Now
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

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

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PUP Qualifying Exam Portal. All rights reserved.</p>
        </div>
    </footer>

    <script>
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

        function validateHeaderExamSchedule() {
            // Get the first available exam ID from the exam list
            const examItems = document.querySelectorAll('.exam-item');
            if (examItems.length === 0) {
                // Show error if no exams are available
                showAlertModal('No exams are currently available.');
                return;
            }

            // Find the first exam that is assigned to the student
            let assignedExamId = null;
            examItems.forEach(item => {
                const examAction = item.querySelector('.exam-action');
                if (examAction && examAction.textContent.trim() === 'Take Exam') {
                    assignedExamId = examAction.getAttribute('onclick').match(/\d+/)[0];
                }
            });

            if (assignedExamId) {
                validateExamSchedule(assignedExamId);
            } else {
                // Show error if no assigned exams
                showAlertModal('You don\'t have any assigned exams at the moment.');
            }
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const alertModal = document.getElementById('alertModal');
            if (event.target === alertModal) {
                closeAlertModal();
            }
        });
    </script>
</body>
</html>
