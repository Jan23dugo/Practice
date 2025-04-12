<?php
// Start the session
session_start();

// Add this near the top of the file, after session_start()
include 'config/config.php'; // Include database connection
// require_once 'config/ip_config.php';

// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    // Redirect to login page if not logged in
    header("Location: stud_register.php");
    exit();
}

// Get student information from session
$stud_id = $_SESSION['stud_id']; // This is now the database ID
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

// Replace the getScheduledExams() function with this:
function getScheduledExams($stud_id) {
    global $conn;
    
    if (!$conn) {
        return array();
    }
    
    try {
        $query = "SELECT e.exam_id, e.title, e.scheduled_date, e.venue, e.exam_type, ea.completion_status 
                  FROM exams e
                  INNER JOIN exam_assignments ea ON e.exam_id = ea.exam_id
                  WHERE ea.student_id = ? 
                  AND e.is_scheduled = 1 
                  AND e.scheduled_date >= CURRENT_DATE()
                  AND ea.completion_status = 'pending'
                  ORDER BY e.scheduled_date ASC";
                  
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - PUP Qualifying Exam Portal</title>
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
        
        .nav-links a:hover {
            background-color: var(--primary-dark);
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
            cursor: pointer;
            overflow: hidden;
        }

        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-menu {
            border-radius: 50%;
        }
        
        /* Main Layout */
        .main-wrapper {
            display: flex;
            margin-top: 80px;
            flex: 1;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding: 25px 0;
            height: calc(100vh - 80px);
            position: fixed;
            overflow-y: auto;
        }
        
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
            overflow: hidden;
        }
        
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .sidebar-profile h3 {
            font-size: 18px;
            margin-bottom: 5px;
            color: var(--primary);
        }
        
        .sidebar-profile p {
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.7;
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
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
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
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            height: 100%;
        }
        
        .card-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            font-size: 20px;
            color: var(--primary);
        }
        
        .view-all {
            font-size: 14px;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-all:hover {
            text-decoration: underline;
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
            margin-top: 30px;
        }
        
        .registration-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
        }
        
        .registration-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .registration-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .registration-icon .material-symbols-rounded {
            font-size: 30px;
        }
        
        .registration-text h3 {
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .registration-text p {
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.8;
        }
        
        .registration-action {
            background-color: var(--primary);
            color: var(--text-light);
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-block;
            text-decoration: none;
        }
        
        .registration-action:hover {
            background-color: var(--primary-dark);
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
        
        /* Footer */
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            text-align: center;
            font-size: 14px;
            margin-top: auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
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
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .container {
                padding: 0 15px;
            }
            
            .logo-text h1 {
                font-size: 18px;
            }
            
            .logo-text p {
                display: none;
            }
        }

        /* Notice Banner */
        .notice-banner {
            background-color: var(--primary-light);
            color: var(--text-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notice-banner .material-symbols-rounded {
            font-size: 24px;
        }

        .notice-content h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .notice-content p {
            font-size: 14px;
            opacity: 0.9;
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
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
            min-width: 200px;
            z-index: 1000;
        }

        .dropdown-menu a{
            color: var(--primary);
        }
        
        .dropdown-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
            text-decoration: none;
            transition: background-color 0.3s;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background-color: var(--primary);
            color: var(--text-light);
        }
        
        #profile-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-light);
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
                        <p>Student Dashboard</p>
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
                                    <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
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
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
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
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-title">
                <h2>Welcome, <?php echo $firstname; ?>!</h2>
                <p>View exam schedules, announcements, and register for the CCIS Qualifying Exam</p>
            </div>

            <!-- Important Notice Banner -->
            <div class="notice-banner">
                <span class="material-symbols-rounded">info</span>
                <div class="notice-content">
                    <h4>Important Notice</h4>
                    <p>All CCIS Qualifying Exams will be conducted on-campus only. Please make sure to register and check the venue details.</p>
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
                                            Date: <?php echo date('F d, Y', strtotime($exam['scheduled_date'])); ?> | 
                                            Time: <?php echo date('h:i A', strtotime($exam['scheduled_date'])); ?>
                                        </p>
                                        <p>Venue: <?php echo htmlspecialchars($exam['venue']); ?></p>
                                        <p>Type: <?php echo htmlspecialchars(ucfirst($exam['exam_type'])); ?></p>
                                    </div>
                                    <div>
                                        <?php
                                        // Check if this exam is assigned using the registration email
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
                                                <a href="exam_instructions.php" class="exam-action">
                                                    Take Exam
                                                </a>
                                            <?php else: ?>
                                                Not Assigned
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Keep the campus notice -->
                        <div class="campus-notice">
                            <span class="material-symbols-rounded">location_on</span>
                            <p>All exams are conducted on-campus only</p>
                        </div>
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
            
            <!-- Exam Registration Section -->
            <div class="registration-section">
                <div class="registration-card">
                    <div class="registration-info">
                        <div class="registration-icon">
                            <span class="material-symbols-rounded">app_registration</span>
                        </div>
                        <div class="registration-text">
                            <h3>CCIS Qualifying Exam Registration</h3>
                            <p>Register for the upcoming CCIS Qualifying Exam to advance your academic journey.</p>
                        </div>
                    </div>
                    <a href="qualiexam_register.php" class="registration-action">Register for Qualifying Exam</a>
                    <div class="campus-notice">
                        <span class="material-symbols-rounded">school</span>
                        <p>Note: The qualifying exam will only be available on campus at the scheduled time and venue</p>
                    </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile Menu Toggle
            const profileMenu = document.querySelector('.profile-menu');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            const profileMenuTrigger = document.querySelector('#profile-menu');
            
            document.addEventListener('click', function(event) {
                if (!profileMenu.contains(event.target)) {
                    dropdownMenu.style.display = 'none';
                }
            });
            
            profileMenuTrigger.addEventListener('click', function(event) {
                event.preventDefault();
                dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
            });
        });
    </script>
</body>
</html>


