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
$email = $_SESSION['email'];

// Database connection
require_once 'config/config.php';

// Fetch registration details
$query = "SELECT rs.*, 
          DATE_FORMAT(rs.registration_date, '%M %d, %Y') as formatted_date,
          DATE_FORMAT(rs.registration_date, '%h:%i %p') as formatted_time
          FROM register_studentsqe rs
          WHERE rs.email = ? 
          ORDER BY rs.registration_date DESC 
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email); // Using the email from session
$stmt->execute();
$result = $stmt->get_result();
$registration = $result->fetch_assoc();

// Active page for sidebar highlighting
$activePage = 'registration_status';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status - PUP Qualifying Exam Portal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
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
            --success: #4CAF50;
            --warning: #FF9800;
            --danger: #F44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-dark);
            background-color: var(--gray-light);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }

        /* Header Styles */
        header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo img {
            height: 50px;
            width: auto;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-text h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .logo-text p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Sidebar Styles */
        .sidebar-profile {
            padding: 0 20px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--gray);
            text-align: center;
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

        .sidebar {
            width: 250px;
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding: 25px 0;
            height: calc(100vh - 80px);
            position: fixed;
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        /* Copy all the responsive styles from stud_dashboard.php */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
                overflow: hidden;
                transition: width 0.3s;
            }
            
            .sidebar.active {
                width: 250px;
                padding: 25px 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        /* Status Card Styles */
        .status-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }

        .status-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray);
        }

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

        /* Status Note */
        .status-note {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--warning);
            color: var(--text-dark);
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0.9;
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
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-value {
                padding-left: 10px;
            }
        }

        /* Add these footer styles to your <style> section */
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 20px 0;
            margin-top: auto;
            width: 100%;
            position: fixed;
            bottom: 0;
            left: 0;
        }

        footer p {
            text-align: center;
            font-size: 14px;
            opacity: 0.9;
        }

        /* Adjust main content to account for footer */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            padding-bottom: 80px; /* Add padding to prevent content from being hidden by footer */
        }

        /* Adjust sidebar height to account for footer */
        .sidebar {
            width: 250px;
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding: 25px 0;
            height: calc(100vh - 160px); /* Adjust height to account for header and footer */
            position: fixed;
            overflow-y: auto;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
                padding-bottom: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="img/Logo.png" alt="PUP Logo">
                    <div class="logo-text">
                        <h1>PUP Qualifying Exam Portal</h1>
                        <p>Registration Status</p>
                    </div>
                </div>
                <div class="nav-links">
                    <a href="#" id="notifications">
                        <span class="material-symbols-rounded">notifications</span>
                    </a>
                    <a href="#" id="profile-menu">
                        <div class="profile-icon"><?php echo substr($firstname, 0, 1); ?></div>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-profile">
                <div class="profile-image"><?php echo substr($firstname, 0, 1); ?></div>
                <h3><?php echo $firstname . ' ' . $lastname; ?></h3>
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
                    <a href="exam_registration_status.php" class="<?php echo $activePage == 'registration_status' ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">app_registration</span>
                        Exam Registration Status
                    </a>
                </li>
                <li>
                    <a href="stud_profile.php" class="<?php echo $activePage == 'profile' ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">person</span>
                        Profile
                    </a>
                </li>
                <li>
                    <a href="stud_settings.php" class="<?php echo $activePage == 'settings' ? 'active' : ''; ?>">
                        <span class="material-symbols-rounded">settings</span>
                        Settings
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
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="status-card">
                    <div class="no-registration">
                        <span class="material-symbols-rounded">assignment_late</span>
                        <h3>No Registration Found</h3>
                        <p>You haven't registered for the qualifying exam yet.</p>
                        <a href="qualiexam_register.php" class="btn btn-primary">
                            <span class="material-symbols-rounded">app_registration</span>
                            Register Now
                        </a>
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

    <script>
        // Toggle mobile menu (for responsive design)
        document.addEventListener('DOMContentLoaded', function() {
            const profileMenu = document.getElementById('profile-menu');
            
            if (profileMenu) {
                profileMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sidebar = document.querySelector('.sidebar');
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
