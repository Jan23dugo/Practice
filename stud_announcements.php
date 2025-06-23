<?php
// Start the session
session_start();

// Turn off error display for production
ini_set('display_errors', 0);
error_reporting(0);

// Add this near the top of the file, after session_start()
include 'config/config.php'; // Include database connection

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

// Function to get all active announcements
function getAllAnnouncements() {
    global $conn;
    
    // Check if connection exists
    if (!$conn) {
        return array(); // Return empty array if no connection
    }
    
    try {
        $query = "SELECT * FROM announcements 
                  WHERE status = 'active' 
                  ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Log error if needed
        error_log("Error fetching all announcements: " . $e->getMessage());
        return array(); // Return empty array on error
    }
}

// Fetch all announcements
$announcements = getAllAnnouncements();

// Active page for sidebar highlighting
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - PUP Qualifying Exam Portal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/main.css">
    <style>
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
        /* --- MAIN CONTENT LAYOUT FIX AFTER SIDEBAR REMOVAL --- */
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: stretch;
            margin-left: 0 !important;
            padding-left: 0 !important;
        }
        .main-content {
            display: block;
            margin: 0 auto !important;
            padding-top: 48px;
            padding-bottom: 48px;
            min-height: calc(100vh - 180px); /* header + footer height */
            max-width: 1000px;
            width: 100%;
            box-sizing: border-box;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 25px;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(117, 52, 58, 0.13);
            margin-left: auto;
            margin-right: auto;
        }
        .back-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(117, 52, 58, 0.18);
        }
        .page-title {
            margin-bottom: 25px;
            position: relative;
            width: 100%;
            text-align: center;
        }
        .page-title h2 {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 8px;
            font-weight: 700;
        }
        .page-title p {
            font-size: 1.1rem;
            color: var(--text-dark);
            opacity: 0.7;
            margin: 0;
        }
        /* --- ANNOUNCEMENT CARDS --- */
        .announcements-container {
            margin-bottom: 40px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }
        .announcement-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary);
            transition: transform 0.3s, box-shadow 0.3s;
            width: 100%;
            max-width: 100%;
        }
        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.13);
        }
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: rgba(117, 52, 58, 0.05);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .announcement-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin: 0;
            font-weight: 600;
        }
        .announcement-date {
            font-size: 1rem;
            color: var(--text-dark);
            opacity: 0.7;
            background-color: var(--gray-light);
            padding: 6px 12px;
            border-radius: 30px;
        }
        .announcement-body {
            padding: 32px 32px 32px 32px;
        }
        .announcement-content {
            font-size: 1.08rem;
            line-height: 1.6;
            color: var(--text-dark);
        }
        .announcement-content p {
            margin: 0 0 15px 0;
        }
        .announcement-content p:last-child {
            margin-bottom: 0;
        }
        .no-announcements {
            text-align: center;
            padding: 40px 20px;
            background-color: var(--gray-light);
            border-radius: 10px;
            color: var(--text-dark);
        }
        .no-announcements h3 {
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .no-announcements p {
            margin: 0;
            opacity: 0.7;
        }
        /* Responsive adjustments */
        @media (max-width: 1100px) {
            .main-content {
                max-width: 98vw;
                padding-left: 8px;
                padding-right: 8px;
            }
        }
        @media (max-width: 700px) {
            .announcement-header, .announcement-body {
                padding: 10px 8px;
            }
            .main-content {
                padding-top: 16px;
                padding-bottom: 16px;
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
                <p>Student Dashboard</p>
            </div>
        </div>
        <nav class="nav-links">
            <a href="stud_dashboard.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">dashboard</span>
                Dashboard
            </a>
            <a href="exam_instructions.php" class="<?php echo $activePage == 'take_exam' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">quiz</span>
                Take Exam
            </a>
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
            </div>
        </nav>
    </header>
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Main Content -->
        <main class="main-content">
            <a href="stud_dashboard.php" class="back-button">
                <span class="material-symbols-rounded">arrow_back</span>
                Back to Dashboard
            </a>
            
            <div class="page-title">
                <h2>Announcements</h2>
                <p>Stay updated with all the latest information about qualifying exams and important notices</p>
            </div>
            
            <div class="announcements-container">
                <?php if (empty($announcements)): ?>
                    <div class="no-announcements">
                        <h3>No Announcements Available</h3>
                        <p>There are no announcements at this time. Please check back later.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-card">
                            <div class="announcement-header">
                                <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <span class="announcement-date">
                                    <?php echo date('F d, Y', strtotime($announcement['created_at'])); ?>
                                </span>
                            </div>
                            <div class="announcement-body">
                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PUP Qualifying Exam Portal. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html> 