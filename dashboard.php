<?php
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
    session_start(); // Start session if needed
// Include database connection
include('config/config.php');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Not logged in as admin, redirect to admin login page
    header("Location: admin_login.php");
    exit();
}
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes
=======
    // Include admin session management
    require_once 'config/admin_session.php';
    // Include database connection
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();
>>>>>>> Stashed changes

// Initialize stats array
$stats = array();

// Fetch Student Statistics with error handling
try {
    // Total Students
    $query = "SELECT COUNT(*) as total FROM register_studentsqe";
    $result = $conn->query($query);
    $stats['total_students'] = ($result) ? $result->fetch_assoc()['total'] : 0;

    // Qualified Students
    $query = "SELECT COUNT(*) as qualified FROM register_studentsqe WHERE status = 'accepted'";
    $result = $conn->query($query);
    $stats['qualified_students'] = ($result) ? $result->fetch_assoc()['qualified'] : 0;

    // Pending Students
    $query = "SELECT COUNT(*) as pending FROM register_studentsqe WHERE status = 'pending'";
    $result = $conn->query($query);
    $stats['pending_students'] = ($result) ? $result->fetch_assoc()['pending'] : 0;
} catch (Exception $e) {
    error_log("Error fetching student statistics: " . $e->getMessage());
    // Set default values if query fails
    $stats['total_students'] = 0;
    $stats['qualified_students'] = 0;
    $stats['pending_students'] = 0;
}

// Exam Statistics with error handling
try {
    $query = "SELECT 
        COUNT(*) as total_exams,
        SUM(CASE WHEN exam_type = 'tech' THEN 1 ELSE 0 END) as technical_exams,
        SUM(CASE WHEN exam_type = 'non-tech' THEN 1 ELSE 0 END) as non_technical_exams,
        SUM(CASE WHEN is_scheduled = 1 THEN 1 ELSE 0 END) as scheduled_exams
    FROM exams";
    $result = $conn->query($query);
    if ($result) {
        $exam_stats = $result->fetch_assoc();
        $stats = array_merge($stats, $exam_stats);
    }
} catch (Exception $e) {
    error_log("Error fetching exam statistics: " . $e->getMessage());
    // Set default values if query fails
    $stats['total_exams'] = 0;
    $stats['technical_exams'] = 0;
    $stats['non_technical_exams'] = 0;
    $stats['scheduled_exams'] = 0;
}

// Question Bank Total
try {
    $query = "SELECT COUNT(*) as total FROM question_bank";
    $result = $conn->query($query);
    $stats['question_bank_total'] = ($result) ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    error_log("Error fetching question bank total: " . $e->getMessage());
    $stats['question_bank_total'] = 0;
}

// Exam Results Statistics
try {
    $query = "SELECT 
        COUNT(*) as total_attempts,
        SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count,
        SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as failed_count
    FROM exam_assignments 
    WHERE completion_status = 'completed'";
    $result = $conn->query($query);
    if ($result) {
        $exam_results = $result->fetch_assoc();
        $stats['total_attempts'] = $exam_results['total_attempts'] ?? 0;
        $stats['passed_count'] = $exam_results['passed_count'] ?? 0;
        $stats['failed_count'] = $exam_results['failed_count'] ?? 0;
        
        // Calculate pass rate
        $stats['pass_rate'] = $stats['total_attempts'] > 0 
            ? round(($stats['passed_count'] / $stats['total_attempts']) * 100) 
            : 0;
    }
} catch (Exception $e) {
    error_log("Error fetching exam results: " . $e->getMessage());
    $stats['total_attempts'] = 0;
    $stats['passed_count'] = 0;
    $stats['failed_count'] = 0;
    $stats['pass_rate'] = 0;
}

// Recent registrations with error handling
try {
    $query = "SELECT * FROM register_studentsqe 
              ORDER BY registration_date DESC 
              LIMIT 5";
    $recent_registrations = $conn->query($query);
    if (!$recent_registrations) {
        throw new Exception("Failed to fetch recent registrations");
    }
} catch (Exception $e) {
    error_log("Error fetching recent registrations: " . $e->getMessage());
    // Create empty result set if query fails
    $recent_registrations = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Recent announcements with error handling
try {
    $query = "SELECT * FROM announcements 
              WHERE status = 'active' 
              ORDER BY created_at DESC 
              LIMIT 5";
    $recent_announcements = $conn->query($query);
    if (!$recent_announcements) {
        throw new Exception("Failed to fetch recent announcements");
    }
} catch (Exception $e) {
    error_log("Error fetching recent announcements: " . $e->getMessage());
    // Create empty result set if query fails
    $recent_announcements = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Upcoming exams with error handling
try {
    $query = "SELECT 
                e.exam_id, 
                e.title, 
                e.exam_type, 
                e.scheduled_date, 
                e.scheduled_time, 
                e.duration, 
                e.description
              FROM exams e 
              WHERE e.is_scheduled = 1 
                AND e.scheduled_date >= CURDATE() 
              ORDER BY e.scheduled_date ASC, e.scheduled_time ASC 
              LIMIT 5";
    $upcoming_exams = $conn->query($query);
    if (!$upcoming_exams) {
        throw new Exception("Failed to fetch upcoming exams");
    }
} catch (Exception $e) {
    error_log("Error fetching upcoming exams: " . $e->getMessage());
    // Create empty result set if query fails
    $upcoming_exams = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Remove mock data and add real query for difficult exams
try {
    $query = "
        SELECT 
            e.title,
            COUNT(qb.question_id) as total_questions,
            SUM(CASE WHEN sa.is_correct = 0 THEN 1 ELSE 0 END) as difficult_questions, 
            (SUM(CASE WHEN sa.is_correct = 0 THEN 1 ELSE 0 END) * 100.0 / COUNT(qb.question_id)) as difficulty_percent
        FROM exams e
        LEFT JOIN questions q ON e.exam_id = q.exam_id
        LEFT JOIN question_bank qb ON q.question_id = qb.question_id
        LEFT JOIN student_answers sa ON qb.question_id = sa.question_id
        GROUP BY e.exam_id, e.title
        ORDER BY difficulty_percent DESC
        LIMIT 5";
    
    $difficult_exams_result = $conn->query($query);
    if (!$difficult_exams_result) {
        throw new Exception("Failed to fetch exam difficulty analysis");
    }
} catch (Exception $e) {
    error_log("Error fetching exam difficulty analysis: " . $e->getMessage());
    // Create empty result set if query fails
    $difficult_exams_result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Pending Applicants
try {
    $query = "SELECT * FROM register_studentsqe WHERE status = 'pending' ORDER BY registration_date DESC LIMIT 5";
    $pending_applicants = $conn->query($query);
    if (!$pending_applicants) {
        throw new Exception("Failed to fetch pending applicants");
    }
} catch (Exception $e) {
    error_log("Error fetching pending applicants: " . $e->getMessage());
    // Create empty result set if query fails
    $pending_applicants = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CCIS Qualifying Exam System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body {
            background:rgb(255, 255, 255);
            font-family: 'Montserrat', Arial, sans-serif;
        }
        .dashboard-greeting {
            font-size: 1.5rem;
            font-weight: 700;
            color: #75343A;
            margin-bottom: 1.2rem;
            margin-top: 1.2rem;
            letter-spacing: 0.5px;
        }
        .announcement-card-container, .dashboard-section, .announcement-dashboard-section {
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .announcement-card-container:hover .announcement-dashboard-section,
        .announcement-card-container:hover .announcement-dashboard-section.maroon,
        .dashboard-section:hover,
        .announcement-dashboard-section:hover {
            box-shadow: 0 8px 24px rgba(117,52,58,0.18), 0 2px 8px rgba(0,0,0,0.08);
            transform: scale(1.025);
        }
        .a-list-item {
            cursor: pointer;
            transition: background 0.15s;
        }
        .a-list-item:hover {
            font-weight: 600;
        }
        .announcement-dashboard-section, .announcement-dashboard-section.maroon {
            /* already has max-height, overflow-y, etc. */
        }
        .dashboard-card-header {
            background: #75343A;
            color: rgb(247, 247, 247);
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 2.5rem;
            padding: 0.6rem 2rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            letter-spacing: 1px;
            margin-bottom: -1.8rem;
        }
        
        .announcement-dashboard-card-header {
            background:rgb(255, 244, 244);
            color: #75343A;
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
            border-radius: 2.5rem;
            padding: 0.7rem 2.5rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            letter-spacing: 1px;
            margin-bottom: -3rem;
        }

        .shortcut-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #75343A;
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            padding: 0;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .shortcut-button:hover {
            background: #5a2930;
            transform: scale(1.1);
        }

        .shortcut-button .material-symbols-rounded {
            font-size: 20px;
        }

        .quiz-shortcut-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: inherit;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            padding: 0;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            opacity: 0.8;
        }

        .quiz-shortcut-button:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .quiz-shortcut-button .material-symbols-rounded {
            font-size: 28px;
        }

        .announcement-dashboard-section {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.10);
            border: 1.5px solid #bbb;
            width: 380px;
            padding: 0 0 1.5rem 0;
            position: relative;
            height: 350px;
            max-height: 350px;
            overflow-y: auto;
        }
        .announcement-section-body {
            overflow-y: auto;
            overflow-x: auto;
        }
        .a-list-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }
        .a-list-item:last-child {
            border-bottom: 1px solid rgb(194, 157, 157);
            padding-bottom: 8px;
        }
        .a-list-item-content {
            flex: 1;
        }
        .a-list-item-title {
            font-size: 16px;
            font-weight: 700;
            color: #75343A;
            margin-bottom: 4px;
        }
        .a-list-item-subtitle {
            font-size: 13px;
            color:rgb(0, 0, 0);
        }
        .a-list-item-badge {
            background: #A6E6A6;
            color: #256029;
            font-weight: 700;
            border-radius: 2rem;
            padding: 0.4rem 1.2rem;
            font-size: 1.1rem;
            margin-left: 1rem;
            margin-top: 0.2rem;
            font-family: 'Montserrat', Arial, sans-serif;
        }
        small {
            color:rgb(0, 0, 0);
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 0.2rem;
        }
        /* Dashboard Stats Containers */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .dashboard-title {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        
        .dashboard-date {
            font-size: 18px;
            color: #555;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-title {
            font-size: 16px;
            color: #555;
            font-weight: 500;
        }
        
        .stat-icon {
            height: 45px;
            width: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .icon-students { background: linear-gradient(45deg, #4a6cf7, #70a1ff); }
        .icon-qualified { background: linear-gradient(45deg, #00b894, #55efc4); }
        .icon-pending { background: linear-gradient(45deg, #fdcb6e, #ffeaa7); }
        .icon-exams { background: linear-gradient(45deg, #d63031, #ff7675); }
        .icon-tech { background: linear-gradient(45deg, #6c5ce7, #a29bfe); }
        .icon-non-tech { background: linear-gradient(45deg, #e84393, #fd79a8); }
        .icon-scheduled { background: linear-gradient(45deg, #00cec9, #81ecec); }
        .icon-questions { background: linear-gradient(45deg, #2d3436, #636e72); }
        .icon-pass { background: linear-gradient(45deg, #27ae60, #2ecc71); }
        .icon-fail { background: linear-gradient(45deg, #c0392b, #e74c3c); }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }
        
        /* Section Headings */
        .section-heading {
            font-size: 22px;
            font-weight: 600;
            color: #75343A;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        /* Dashboard Sections */
        .dashboard-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 20px;
        }
        
        .dashboard-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            overflow: hidden;
            height: 400px;
        }
        
        .section-header {
            background: #75343A; /* PUP maroon color */
            color: white;
            padding: 15px 20px;
            font-size: 24px;
            font-weight: 800;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: auto;
            width: 90%;
            height: 15%;
            border-radius: 50px;
            box-shadow: 2px 8px 10px rgba(0, 0, 0, 0.1);
        }
        
        .section-header a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: opacity 0.2s ease;
        }
        
        .section-header a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .announcement-dashboard-section {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.10);
            border: 1.5px solid #bbb;
            width: 380px;
            padding: 0 0 1.5rem 0;
            margin: 0 auto;
            position: relative;
            height: 350px;
            max-height: 350px;
            overflow-y: auto;
        }

        .announcement-section-header {
            background: white; /* PUP maroon color */
            color: #75343A;
            padding: 15px 20px;
            font-size: 24px;
            font-weight: 800;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: auto;
            width: 90%;
            height: 15%;
            border-radius: 50px;
            box-shadow: 2px 8px 10px rgba(0, 0, 0, 0.1);
        }
        
        .announcement-section-header a {
            color: #75343A;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: opacity 0.2s ease;
        }
        
        .announcement-section-header a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .announcement-section-body {
            padding: 0px 15px;
            margin-top: 10px;
            overflow-y: auto;
            overflow-x: auto;
        }
        
        .section-body {
            padding: 20px;
            max-height: 350px;
            overflow-y: auto;
        }
        
        /* List Items */
        .list-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .list-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .list-item-content {
            flex: 1;
        }
        
        .list-item-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .list-item-subtitle {
            font-size: 14px;
            color: #777;
        }

        .exam-description {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }
        
        .list-item-badge {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            align-self: flex-start;
            white-space: nowrap;
        }
        
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-tech {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .badge-non-tech {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-revision {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #888;
            font-style: italic;
        }

        /* List Items */
        .a-list-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .a-list-item:last-child {
            border-bottom: 1px solid rgb(194, 157, 157);
            padding-bottom: 8px;
        }
        
        .a-list-item-content {
            flex: 1;
        }
        
        .a-list-item-title {
            font-size: 16px;
            font-weight: 700;
            color: #75343A;
            margin-bottom: 4px;
        }
        
        .a-list-item-subtitle {
            font-size: 13px;
            color:rgb(0, 0, 0);
        }

        .exam-description {
            font-size: 14px;
            color: #75343A;
            margin-bottom: 5px;
        }
        
        .a-list-item-badge {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            align-self: flex-start;
            white-space: nowrap;
        }
        
        /* Container for both analytics sections */
        .analytics-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        /* Analytics Cards */
        .eq-analytics-preview {
            background: #75343A;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            flex: 1 1 48%; /* This allows each section to take up about 50% of the space */
            min-width: 450px;
        }
        
        .eq-analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom:e 1px solid #eee;
        }
        
        .eq-analytics-title {
            font-size: 24px;
            color: #f0f0f0;
            font-weight: 800;
        }
        
        .eq-analytics-action {
            background-color: #75343A;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .eq-analytics-action:hover {
            background-color: #5a2930;
        }
        
        .eq-analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .eq-metrics-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .eq-metrics-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .eq-metrics-label {
            font-size: 14px;
            color: #666;
        }

        /* Analytics Cards */
        .analytics-preview {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            flex: 1 1 48%; /* This allows each section to take up about 50% of the space */
            min-width: 450px;
        }
        
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .analytics-title {
            font-size: 24px;
            color: #75343A;
            font-weight: 800;
        }
        
        .analytics-action {
            background-color: #75343A;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .analytics-action:hover {
            background-color: #5a2930;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .metrics-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .metrics-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .metrics-label {
            font-size: 14px;
            color: #666;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }
        
        .progress {
            height: 100%;
            border-radius: 4px;
        }
        
        .progress-pass {
            background-color: #4CAF50;
        }
        
        .progress-fail {
            background-color: #F44336;
        }
        
        .progress-neutral {
            background-color: #2196F3;
        }
        
        /* Item Analysis Table */
        .analysis-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .analysis-table th {
            padding: 12px 15px;
            text-align: left;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-weight: 500;
            color: #333;
        }
        
        .analysis-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .difficulty-indicator {
            display: inline-block;
            width: 100%;
            height: 6px;
            background-color: #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }
        
        .difficulty-level {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
        }
        
        .difficulty-easy { background-color: #4CAF50; }
        .difficulty-medium { background-color: #FF9800; }
        .difficulty-hard { background-color: #F44336; }
        
        /* Responsive Styles */
        @media (max-width: 1200px) {
            .dashboard-title {
                margin-top: 50px;
            }
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 300px;
            }

            
        }
        
        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .dashboard-title {
                font-size: 1.5rem;
            }
            
            .chart-container {
                height: 250px;
            }
            
            .upcoming-exams {
                padding: 1rem;
            }
            
            .exam-card {
                padding: 1rem;
            }
            
            .exam-card h3 {
                font-size: 1.1rem;
            }
            
            .exam-card p {
                font-size: 0.9rem;
            }
            
            .exam-info {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .exam-info span {
                font-size: 0.85rem;
            }
            
            .exam-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .exam-actions button {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-header {
                padding: 1rem;
            }
            
            .dashboard-title {
                font-size: 1.3rem;
            }
            
            .chart-container {
                height: 200px;
            }
            
            .upcoming-exams {
                padding: 0.75rem;
            }
            
            .exam-card {
                padding: 0.75rem;
            }
            
            .exam-card h3 {
                font-size: 1rem;
            }
            
            .exam-card p {
                font-size: 0.85rem;
            }
            
            .exam-info span {
                font-size: 0.8rem;
            }
            
            .exam-actions button {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
        }

        /* Flexible dashboard row and cards */
        .custom-dashboard-row {
            display: flex;
            gap: 20px;
            justify-content: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .announcement-card-container,
        .examination-results-card {
            flex: 1 1 320px;
            max-width: 100%;
            min-width: 280px;
            box-sizing: border-box;
            width: auto;
        }
        /* Remove fixed widths from cards */
        .announcement-card-container {
            width: auto;
            min-width: 280px;
        }
        .examination-results-card {
            width: auto;
            min-width: 280px;
        }
        /* Exam Result Card Styles */
        .exam-result-card {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(117,52,58,0.13), 0 2px 8px rgba(0,0,0,0.08);
            border: 1.5px solid #e3dede;
            padding: 0 0 2rem 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s;
        }
        .exam-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2.5rem 1rem 2.5rem;
            border-bottom: 2px solid #ececec;
            text-align: center;
            align-items: center;
        }
        .exam-result-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #75343A;
            letter-spacing: 1.5px;
        }
        .exam-result-action {
            background: #8B4A50;
            color: #fff;
            border: none;
            border-radius: .2rem;
            padding: 0.5rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(117,52,58,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .exam-result-action:hover {
            background: #75343A;
            box-shadow: 0 4px 16px rgba(117,52,58,0.13);
        }
        .exam-result-metrics {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 1rem;
        }
        .exam-result-metric {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .passing-rate, .total-attempts {
            background: linear-gradient(135deg, #f3f4f2 80%, #e9ece6 100%);
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(107,138,90,0.07);
            flex: 1.2;
            min-width: 150px;
            max-width: 200px;
            padding: 1.7rem 0.7rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .passing-rate:hover,
        .total-attempts:hover {
            box-shadow: 0 8px 32px rgba(107,138,90,0.18), 0 2px 8px rgba(0,0,0,0.10);
            transform: translateY(-4px) scale(1.04);
            background: linear-gradient(135deg, #e9ece6 80%, #f3f4f2 100%);
            z-index: 2;
        }
        .big-pass-rate {
            font-size: 4rem;
            font-weight: 900;
            color: #6b8a5a;
            margin-bottom: 0.2rem;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(107,138,90,0.08);
        }
        .pass-label {
            font-size: 1.15rem;
            color: #6b8a5a;
            font-weight: 800;
            letter-spacing: 1px;
            text-align: center;
        }
        .pass-fail {
            flex: 1.5;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            justify-content: center;
            align-items: center;
        }
        .fail-box, .pass-box {
            background: linear-gradient(135deg, #f3f4f2 80%, #e9ece6 100%);
            border-radius: 14px;
            width: 180px;
            padding: 1.1rem 0.7rem 0.7rem 0.7rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 2px 8px rgba(139,74,80,0.07);
        }
        .fail-box:hover,
        .pass-box:hover {
            box-shadow: 0 8px 32px rgba(107,138,90,0.18), 0 2px 8px rgba(0,0,0,0.10);
            transform: translateY(-4px) scale(1.04);
            background: linear-gradient(135deg, #e9ece6 80%, #f3f4f2 100%);
            z-index: 2;
        }
        .fail-count {
            color: #8B4A50;
            font-size: 2.2rem;
            font-weight: 900;
            margin-bottom: 0.1rem;
            letter-spacing: 1px;
        }
        .fail-label {
            color: #8B4A50;
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 0.2rem;
            letter-spacing: 1px;
        }
        .pass-count {
            color: #6b8a5a;
            font-size: 2.2rem;
            font-weight: 900;
            margin-bottom: 0.1rem;
            letter-spacing: 1px;
        }
        .pass-label {
            color: #6b8a5a;
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 0.2rem;
            letter-spacing: 1px;
        }
        .progress-bar {
            width: 95%;
            height: 16px;
            background: #e5e5e5;
            border-radius: 10px;
            margin-top: 0.2rem;
            overflow: hidden;
            margin-bottom: 0.2rem;
        }
        .progress {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s cubic-bezier(.4,2,.6,1);
        }
        .fail-progress {
            background: linear-gradient(90deg, #8B4A50 70%, #b88d99 100%);
        }
        .pass-progress {
            background: linear-gradient(90deg, #6b8a5a 70%, #A6E6A6 100%);
        }
        .total-count {
            font-size: 4rem;
            font-weight: 900;
            color: #222;
            margin-bottom: 0.2rem;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(34,34,34,0.08);
        }
        .total-label {
            font-size: 1.15rem;
            color: #222;
            font-weight: 800;
            letter-spacing: 1px;
            text-align: center;
        }
        @media (max-width: 1200px) {
            .exam-result-metrics {
                flex-direction: column;
                align-items: stretch;
                gap: 1.5rem;
            }
            .passing-rate, .total-attempts, .fail-box, .pass-box {
                margin-right: 0;
                max-width: 100%;
                width: 100%;
            }
        }
        .exam-filter-container {
            display: flex;
            align-items: center;
        }
        .exam-type-filter {
            padding: 8px 15px;
            border: 2px solid #75343A;
            border-radius: 8px;
            background-color: white;
            color: #75343A;
            font-family: 'Montserrat', Arial, sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            outline: none;
        }
        .exam-type-filter:hover {
            border-color: #5a2930;
            background-color: #fff5f5;
        }
        .exam-type-filter:focus {
            border-color: #5a2930;
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.2);
        }
        .exam-type-filter option {
            background-color: white;
            color: #333;
            font-weight: normal;
        }
        .view-more-link {
            position: absolute;
            bottom: 10px;
            right: 10px;
            color: #75343A;
            opacity: 0.7;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .view-more-link:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .view-more-link .material-symbols-rounded {
            font-size: 24px;
        }
        .clickable-row { cursor: pointer; }

        /* Responsive objects inside containers */
        @media (max-width: 991px) {
            .custom-dashboard-row {
                flex-direction: column;
                gap: 16px;
            }
            .announcement-card-container,
            .examination-results-card {
                min-width: 0;
                width: 100%;
                max-width: 100%;
            }
            .analytics-container {
                flex-direction: column;
                gap: 16px;
            }
            .analytics-preview {
                min-width: 0;
                width: 100%;
                max-width: 100%;
            }
            .exam-result-metrics {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem 0.5rem 0 0.5rem;
            }
            .passing-rate, .total-attempts, .fail-box, .pass-box {
                min-width: 0;
                width: 100%;
                max-width: 100%;
                margin-right: 0;
            }
            .analysis-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
        @media (max-width: 600px) {
            .dashboard-header, .dashboard-title {
                font-size: 1.1rem;
                padding: 0.5rem;
            }
            .exam-result-header {
                padding: 1rem 0.5rem 0.5rem 0.5rem;
            }
            .exam-result-title {
                font-size: 1.1rem;
            }
            .big-pass-rate, .total-count {
                font-size: 2rem;
            }
            .pass-label, .fail-label, .total-label {
                font-size: 0.9rem;
            }
            .fail-count, .pass-count {
                font-size: 1.2rem;
            }
            .metrics-card, .eq-metrics-card {
                padding: 8px;
            }
            .analysis-table th, .analysis-table td {
                padding: 6px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="dashboard-header">
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
        <h1 class="dashboard-title">STREAMS ADMIN DASHBOARD</h1>
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
=======
        <h1 class="dashboard-title">STREAMS Admin Dashboard</h1>
>>>>>>> Stashed changes
        <div class="dashboard-date">
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>

    <div class="dashboard-sections custom-dashboard-row">
        <!-- Upcoming Exams -->
        <div class="announcement-card-container">
            <div class="dashboard-card-header">
                <span class="material-symbols-rounded" style="vertical-align:middle;">event</span> Upcoming Exams
                <a href="quiz_editor.php" class="quiz-shortcut-button" title="Create New Exam">
                    <span class="material-symbols-rounded">add_box</span>
                </a>
            </div>
            <div class="announcement-dashboard-section">
                <div class="announcement-section-body">
                    <?php if ($upcoming_exams && $upcoming_exams->num_rows > 0): ?>
                        <?php while ($exam = $upcoming_exams->fetch_assoc()): 
                            $exam_date = date('M d, Y', strtotime($exam['scheduled_date']));
                            $exam_time = date('h:i A', strtotime($exam['scheduled_time']));
                        ?>
                            <a href="exam.php?id=<?php echo urlencode($exam['exam_id']); ?>" style="text-decoration:none; color:inherit;">
                            <div class="a-list-item">
                                <div class="a-list-item-content">
                                    <div class="a-list-item-title"><?php echo htmlspecialchars($exam['title']); ?></div>
                                    <div class="a-list-item-subtitle">
                                        <?php if (!empty($exam['description'])): ?>
                                            <div class="exam-description">
                                                <?php echo htmlspecialchars($exam['description']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <strong>Date:</strong> <?php echo $exam_date; ?> •
                                        <strong>Time:</strong> <?php echo $exam_time; ?> •
                                        <strong>Duration:</strong> <?php echo $exam['duration']; ?> minutes
                                    </div>
                                </div>
                                <span class="a-list-item-badge"><?php echo ucfirst($exam['exam_type']); ?></span>
                            </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">No upcoming exams scheduled</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Applicants Section -->
        <div class="examination-results-card">
            <div class="exam-result-card">
                <div class="exam-result-header">
                    <span class="exam-result-title">Pending Applicants</span>
                </div>
                <div class="announcement-section-body">
                    <?php
                    // Fetch pending applicants with pagination
                    $query = "SELECT * FROM register_studentsqe 
                             WHERE status = 'pending' OR status = 'needs_review'
                             ORDER BY registration_date DESC 
                             LIMIT 5";
                    $pending_applicants = $conn->query($query);
                    
                    if (!$pending_applicants) {
                        echo "Query Error: " . $conn->error;
                    }
                    ?>
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
                    <p style="margin-bottom: 10px; font-style: italic;">Click on the student name to view their application details.</p>
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
=======
                    <p style="margin-bottom: 10px; font-style: italic; color:rgb(97, 97, 97);">Click on the student name to view their application details.</p>
>>>>>>> Stashed changes
                    <table class="analysis-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Desired Program</th>
                                <th>Status</th>
                                <th>Applied Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($pending_applicants && $pending_applicants->num_rows > 0): ?>
                            <?php while ($applicant = $pending_applicants->fetch_assoc()): ?>
                                <tr class="clickable-row" data-href="Applicants.php?id=<?php echo urlencode($applicant['student_id']); ?>">
                                    <td><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['desired_program']); ?></td>
                                    <td>
                                        <span class="list-item-badge <?php echo $applicant['status'] === 'needs_review' ? 'badge-revision' : 'badge-pending'; ?>">
                                            <?php echo $applicant['status'] === 'needs_review' ? 'Manual Review' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($applicant['registration_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">No pending applicants found</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="announcement-card-container">
            <div class="dashboard-card-header">
                <span class="material-symbols-rounded" style="vertical-align:middle;">campaign</span> Recent announcements
                <a href="announcement.php" class="quiz-shortcut-button" title="Create New Announcement">
                    <span class="material-symbols-rounded">add_box</span>
                </a>
            </div>
            <div class="announcement-dashboard-section">
                <div class="announcement-section-body">
                    <?php if ($recent_announcements && $recent_announcements->num_rows > 0): ?>
                        <?php while ($announcement = $recent_announcements->fetch_assoc()): ?>
                            <a href="announcement.php?id=<?php echo urlencode($announcement['id']); ?>" style="text-decoration:none; color:inherit;">
                            <div class="a-list-item">
                                <div class="a-list-item-content">
                                    <div class="a-list-item-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                    <div class="a-list-item-subtitle">
                                        <?php 
                                            $content = strip_tags($announcement['content']);
                                            echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                        ?>
                                        <br>
                                        <small>Posted: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
                                    </div>
                                </div>
                                <span class="a-list-item-badge">Active</span>
                            </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">No recent announcements found</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="analytics-container">
        <!-- Recent Exam Results -->
        <div class="analytics-preview">
            <div class="analytics-header">
                <h3 class="analytics-title">RECENT EXAM RESULTS</h3>
                <div class="exam-filter-container">
                    <select id="examTypeFilter" class="exam-type-filter">
                        <option value="all">All Exams</option>
                        <option value="tech">Technical</option>
                        <option value="non-tech">Non-Technical</option>
                        <option value="ladderized">Ladderized</option>
                    </select>
                </div>
            </div>
            <div class="exam-result-metrics">
                <!-- Passing Rate -->
                <div class="exam-result-metric passing-rate">
                    <div class="big-pass-rate"><?php echo $stats['pass_rate']; ?>%</div>
                    <div class="pass-label">PASSING RATE</div>
                    <a href="analytics.php" class="view-more-link" title="View Full Exam Results">
                        <span class="material-symbols-rounded">open_in_new</span>
                    </a>
                </div>
                <!-- Failed/Passed -->
                <div class="exam-result-metric pass-fail">
                    <div class="fail-box">
                        <div class="fail-count"><?php echo $stats['failed_count']; ?></div>
                        <div class="fail-label">FAILED</div>
                        <div class="progress-bar">
                            <div class="progress fail-progress" style="width: <?php echo ($stats['total_attempts'] > 0) ? ($stats['failed_count'] / $stats['total_attempts'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="pass-box">
                        <div class="pass-count"><?php echo $stats['passed_count']; ?></div>
                        <div class="pass-label">PASSED</div>
                        <div class="progress-bar">
                            <div class="progress pass-progress" style="width: <?php echo ($stats['total_attempts'] > 0) ? ($stats['passed_count'] / $stats['total_attempts'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
                <!-- Total Attempts -->
                <div class="exam-result-metric total-attempts">
                    <div class="total-count"><?php echo $stats['total_attempts']; ?></div>
                    <div class="total-label">TOTAL ATTEMPTS</div>
                </div>
            </div>
        </div>

        <!-- Item Analysis Preview -->
        <div class="analytics-preview">
            <div class="analytics-header">
                <h3 class="analytics-title">ITEM ANALYSIS PREVIEW</h3>
            </div>
            
            <p style="margin-bottom: 15px; font-style: italic; color:rgb(97, 97, 97);">Items flagged for revision based on student performance difficulty analysis:</p>
            
            <table class="analysis-table">
                <thead>
                    <tr>
                        <th>Exam Title</th>
                        <th>Total Questions</th>
                        <th>Questions for Revision</th>
                        <th>Difficulty Level</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($difficult_exams_result && $difficult_exams_result->num_rows > 0): ?>
                    <?php while ($exam = $difficult_exams_result->fetch_assoc()): 
                        $difficulty_class = '';
                        $difficulty_percent = round($exam['difficulty_percent'] ?? 0);
                        
                        if ($difficulty_percent < 30) {
                            $difficulty_class = 'difficulty-easy';
                        } else if ($difficulty_percent < 70) {
                            $difficulty_class = 'difficulty-medium';
                        } else {
                            $difficulty_class = 'difficulty-hard';
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                            <td><?php echo (int)$exam['total_questions']; ?></td>
                            <td>
                                <?php if ((int)$exam['difficult_questions'] > 0): ?>
                                    <span class="list-item-badge badge-revision">
                                        <?php echo (int)$exam['difficult_questions']; ?> for revision
                                    </span>
                                <?php else: ?>
                                    <span class="list-item-badge badge-accepted">No revision needed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="difficulty-indicator">
                                    <div class="difficulty-level <?php echo $difficulty_class; ?>" style="width: <?php echo $difficulty_percent; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">No exam difficulty analysis available</div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script src="assets/js/side.js"></script>
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
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
=======
<script src="assets/js/admin-session.js"></script>
>>>>>>> Stashed changes
<script>
document.addEventListener('DOMContentLoaded', function() {
    const examTypeFilter = document.getElementById('examTypeFilter');
    
    examTypeFilter.addEventListener('change', function() {
        const selectedType = this.value;
        
        // Make an AJAX request to fetch filtered results
        fetch('get_filtered_results.php?type=' + selectedType)
            .then(response => response.json())
            .then(data => {
                // Update the metrics with new data
                document.querySelector('.big-pass-rate').textContent = data.pass_rate + '%';
                document.querySelector('.failed-count').textContent = data.failed_count;
                document.querySelector('.passed-count').textContent = data.passed_count;
                document.querySelector('.total-count').textContent = data.total_attempts;
                
                // Update progress bars
                const failProgress = document.querySelector('.fail-progress');
                const passProgress = document.querySelector('.pass-progress');
                
                if (data.total_attempts > 0) {
                    const failPercentage = (data.failed_count / data.total_attempts * 100);
                    const passPercentage = (data.passed_count / data.total_attempts * 100);
                    
                    failProgress.style.width = failPercentage + '%';
                    passProgress.style.width = passPercentage + '%';
                } else {
                    failProgress.style.width = '0%';
                    passProgress.style.width = '0%';
                }
            })
            .catch(error => {
                console.error('Error fetching filtered results:', error);
            });
    });

    document.querySelectorAll('.clickable-row').forEach(function(row) {
        row.addEventListener('click', function() {
            window.location = this.getAttribute('data-href');
        });
    });
});
</script>
</body>
</html>
