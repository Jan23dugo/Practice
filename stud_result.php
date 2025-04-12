<?php
session_start();
include('config/config.php');

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
            overflow: hidden;
        }
        
        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        
        /* Add your results page specific styles here */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .results-table th,
        .results-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 14px;
            display: inline-block;
        }

        .status-passed {
            background-color: var(--success);
            color: white;
        }

        .status-failed {
            background-color: var(--danger);
            color: white;
        }

        .status-pending {
            background-color: var(--warning);
            color: var(--text-dark);
        }

        /* Dashboard Card Styles */
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray);
        }

        .card-header h3 {
            font-size: 20px;
            color: var(--primary);
        }

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

        /* Add to your existing CSS */
        .no-items {
            text-align: center;
            padding: 40px 20px;
        }

        .no-items .material-symbols-rounded {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 15px;
            display: block;
        }

        .no-items h3 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 20px;
        }

        .no-items p {
            color: var(--text-dark);
            opacity: 0.7;
            margin-bottom: 20px;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .registration-action:hover {
            background-color: var(--primary-dark);
        }

        .campus-notice {
            background-color: var(--warning);
            color: var(--text-dark);
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .page-title {
            margin-bottom: 30px;
        }
        
        .page-title h2 {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        
        .page-title p {
            color: var(--text-dark);
            opacity: 0.8;
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
            background-color: var(--gray-light);
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
                <h2>Exam Results</h2>
                <p>View your qualifying exam results</p>
            </div>

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
                            <h3>Released Results</h3>
                        </div>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Exam Title</th>
                                    <th>Type</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($releasedResults as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['title']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($result['exam_type'])); ?></td>
                                        <td><?php echo $result['final_score']; ?>%</td>
                                        <td>
                                            <span class="status-badge <?php echo $result['passed'] ? 'status-passed' : 'status-failed'; ?>">
                                                <?php echo $result['passed'] ? 'Passed' : 'Failed'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($examAttempts)): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Exam Results</h3>
                    </div>
                    <div class="no-items">
                        <span class="material-symbols-rounded">assignment</span>
                        <h3>No Exam Results Available</h3>
                        <p>You haven't taken any qualifying exams yet.</p>
                        <a href="stud_dashboard.php" class="registration-action">
                            <span class="material-symbols-rounded">dashboard</span>
                            Return to Dashboard
                        </a>
                    </div>
                    
                    <div class="campus-notice">
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