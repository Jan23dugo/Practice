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
    </script>
</body>
</html> 
