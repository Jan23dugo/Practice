<?php
session_start();
include('config/config.php');
require_once 'config/ip_config.php'; // Include IP configurations

// Check if current IP is verified
$is_ip_verified = isCurrentIPVerified($conn);

// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: stud_register.php");
    exit();
}

// Get student information from session
$stud_id = $_SESSION['stud_id'];
$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$email = $_SESSION['email'];

// Fetch student profile picture
$query = "SELECT profile_picture FROM students WHERE stud_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['stud_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Function to get all exam attempts (both released and pending)
function getExamAttempts($stud_id) {
    global $conn;
    
    $query = "SELECT 
                e.title, 
                e.exam_type, 
                e.passing_score,
                ea.final_score, 
                ea.passed, 
                ea.completion_time,
                ea.completion_status,
                ea.is_released,
                ea.result_message,
                ea.next_steps,
                e.passing_score_type
              FROM exam_assignments ea
              JOIN exams e ON ea.exam_id = e.exam_id
              JOIN register_studentsqe rs ON ea.student_id = rs.student_id
              WHERE rs.stud_id = ? 
              AND ea.completion_status = 'completed'
              ORDER BY ea.completion_time DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $stud_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get exam attempts
$examAttempts = getExamAttempts($stud_id);

// Separate released and pending results
$releasedResults = array_filter($examAttempts, function($attempt) {
    return $attempt['is_released'] == 1;
});

$pendingResults = array_filter($examAttempts, function($attempt) {
    return $attempt['is_released'] == 0;
});

// Set active page for sidebar
$activePage = 'results';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - PUP Qualifying Exam Portal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/main.css">
    <style>
        /* Page-specific styles */
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
        
        .result-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .result-header {
            padding: 15px 20px;
            background-color: var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray);
        }
        
        .result-title {
            font-size: 18px;
            color: var(--primary);
            font-weight: 500;
        }
        
        .result-date {
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.7;
        }
        
        .result-content {
            padding: 20px;
        }
        
        .result-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .detail-item {
            text-align: center;
            padding: 15px;
            background-color: var(--gray-light);
            border-radius: 8px;
        }
        
        .detail-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .detail-label {
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.7;
        }
        
        .passed-badge, .failed-badge, .pending-badge {
            padding: 8px 14px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }
        
        .passed-badge {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }
        
        .failed-badge {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger);
        }
        
        .pending-badge {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--warning);
        }
        
        .result-footer {
            padding: 15px 20px;
            background-color: var(--gray-light);
            border-top: 1px solid var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .tab-container {
            margin-bottom: 30px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--gray);
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            position: relative;
            font-weight: 500;
        }
        
        .tab.active {
            color: var(--primary);
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            background-color: var(--gray-light);
            border-radius: 8px;
            margin: 0;
        }
        
        .no-results .material-symbols-rounded {
            font-size: 64px;
            
            margin-bottom: 15px;
            display: block;
        }
        
        .no-results h3 {
            color: var(--primary);
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .no-results p {
            color: var(--text-dark);
            opacity: 0.8;
            max-width: 600px;
            margin: 0 auto 20px;
            font-size: 16px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .info-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #FFF8E1;
            padding: 15px;
            border-radius: 6px;
            margin: 20px;
            border-left: 4px solid #FFC107;
        }
        
        .info-notice .material-symbols-rounded {
            color: #856404;
            font-size: 24px;
        }
        
        .info-notice p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .result-details {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .no-results {
                padding: 30px 15px;
            }
            
            .no-results .material-symbols-rounded {
                font-size: 48px;
            }
            
            .no-results h3 {
                font-size: 20px;
            }
            
            .info-notice {
                margin: 15px;
                padding: 12px;
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
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="img/Logo.png" alt="PUP Logo">
                    <div class="logo-text">
                        <h1>PUP Qualifying Exam Portal</h1>
                        <p>Exam Results</p>
                    </div>
                </div>
                <div class="nav-links">
                    <a href="#" id="notifications">
                        <span class="material-symbols-rounded">notifications</span>
                    </a>
                    <div class="profile-menu">
                        <a href="#" id="profile-menu">
                            <div class="profile-icon">
                                <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                                    <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($_SESSION['firstname'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="dropdown-menu">
                            <a href="stud_dashboard.php" class="dropdown-item">
                                <span class="material-symbols-rounded">dashboard</span>
                                Dashboard
                            </a>
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
                </div>
            </div>
        </div>
    </header>

    <div class="main-wrapper">
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
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
>>>>>>> Stashed changes
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
>>>>>>> Stashed changes
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
>>>>>>> Stashed changes
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
>>>>>>> Stashed changes
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
>>>>>>> Stashed changes
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
>>>>>>> Stashed changes
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
>>>>>>> Stashed changes
=======
        <div class="logo">
            <img src="img/Logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>PUP Qualifying Exam Portal</h1>
                <p>Exam Results</p>
            </div>
        </div>
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
            
            <div class="profile-menu">
                <a href="#" id="profile-menu">
                    <div class="profile-icon">
                        <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                            <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture">
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
        <!-- Main Content -->
        <main class="main-content">
            <?php if (!empty($pendingResults)): ?>
                <div class="notice-banner">
                    <span class="material-symbols-rounded">pending</span>
                    <div class="notice-content">
                        <h4>Results Pending</h4>
                        <p>Some of your exam results are still being processed and will be released soon.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <?php if (!empty($pendingResults)): ?>
                    <!-- Pending Results Card -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Pending Results</h3>
                        </div>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Exam Title</th>
                                    <th>Type</th>
                                    <th>Completion Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingResults as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['title']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($result['exam_type'])); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($result['completion_time'])); ?></td>
                                        <td>
                                            <span class="status-badge status-pending">Pending Release</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($releasedResults)): ?>
                    <!-- Released Results Card -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Exam Results</h3>
                        </div>
                        
                        <?php foreach ($releasedResults as $result): ?>
                            <div class="result-message-card" style="margin: 20px; padding: 25px; border-radius: 12px; background: <?php echo $result['passed'] ? 'linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%)' : 'linear-gradient(135deg, #ffebee 0%, #fce4ec 100%)'; ?>; border-left: 5px solid <?php echo $result['passed'] ? '#4caf50' : '#f44336'; ?>;">
                                
                                <div class="result-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <div>
                                        <h4 style="margin: 0; color: #333; font-size: 20px; font-weight: 600;">
                                            <?php echo htmlspecialchars($result['title']); ?>
                                        </h4>
                                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                                            <?php echo htmlspecialchars(ucfirst($result['exam_type'])); ?> Exam • 
                                            <?php echo date('M d, Y', strtotime($result['completion_time'])); ?>
                                        </p>
                                    </div>
                                    <div class="status-badge-large" style="padding: 12px 20px; border-radius: 25px; font-weight: 600; font-size: 16px; background: <?php echo $result['passed'] ? '#4caf50' : '#f44336'; ?>; color: white;">
                                        <?php echo $result['passed'] ? '✓ PASSED' : '✗ FAILED'; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($result['result_message'])): ?>
                                    <div class="result-message" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                                            <span class="material-symbols-rounded" style="color: <?php echo $result['passed'] ? '#4caf50' : '#f44336'; ?>; font-size: 24px; margin-top: 2px;">
                                                <?php echo $result['passed'] ? 'check_circle' : 'cancel'; ?>
                                            </span>
                                            <div>
                                                <h5 style="margin: 0 0 8px 0; color: #333; font-size: 16px; font-weight: 600;">Result</h5>
                                                <p style="margin: 0; color: #555; line-height: 1.6; font-size: 15px;">
                                                    <?php echo nl2br(htmlspecialchars($result['result_message'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($result['next_steps']) && $result['passed']): ?>
                                    <div class="next-steps" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                                            <span class="material-symbols-rounded" style="color: #2196f3; font-size: 24px; margin-top: 2px;">
                                                arrow_forward
                                            </span>
                                            <div>
                                                <h5 style="margin: 0 0 8px 0; color: #333; font-size: 16px; font-weight: 600;">Next Steps</h5>
                                                <p style="margin: 0; color: #555; line-height: 1.6; font-size: 15px;">
                                                    <?php echo nl2br(htmlspecialchars($result['next_steps'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($examAttempts)): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Exam Results</h3>
                    </div>
                    <div class="no-results">
                        <span class="material-symbols-rounded">assignment</span>
                        <h3>No Exam Results Available</h3>
                        <p>You haven't taken any qualifying exams yet.</p>
                        <a href="stud_dashboard.php" class="btn-primary">
                            <span class="material-symbols-rounded">dashboard</span>
                            Return to Dashboard
                        </a>
                    </div>
                    
                    <div class="info-notice">
                        <span class="material-symbols-rounded">info</span>
                        <p>Check your dashboard for upcoming exam schedules and registration details.</p>
                    </div>
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
            
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
            
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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                }
            }
            
            window.addEventListener('resize', handleResize);
            
            // Initial adjustment
            adjustLayout();
            
            // Tab functionality
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    const contentId = tab.getAttribute('data-tab');
                    document.getElementById(contentId).classList.add('active');
                });
            });
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
                }
            }
            
            window.addEventListener('resize', handleResize);
            
            // Initial adjustment
            adjustLayout();
            
            // Tab functionality
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    const contentId = tab.getAttribute('data-tab');
                    document.getElementById(contentId).classList.add('active');
                });
            });
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
        });
    </script>
</body>
</html> 
