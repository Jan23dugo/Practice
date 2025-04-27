<?php
    session_start(); // Start session if needed

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Not logged in as admin, redirect to admin login page
    header("Location: admin_login.php");
    exit();
}

// Include database connection
include('config/config.php');

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
        /* Dashboard Stats Containers */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
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
            font-size: 18px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: auto;
            width: 70%;
            height: 15%;
            border-radius: 50px;
            box-shadow: 2px 8px 10px rgba(206, 62, 62, 0.1);
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
            background: #75343A;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            overflow: hidden;
            height: 400px;
        }

        .announcement-section-header {
            background: white; /* PUP maroon color */
            color: #75343A;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: auto;
            width: 70%;
            height: 15%;
            border-radius: 50px;
            box-shadow: 2px 8px 10px rgba(206, 62, 62, 0.1);
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
            padding: 20px;
            max-height: 350px;
            overflow-y: auto;
            
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
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .a-list-item:last-child {
            border-bottom: 1px solid rgb(194, 157, 157);
            padding-bottom: 10px;
        }
        
        .a-list-item-content {
            flex: 1;
        }
        
        .a-list-item-title {
            font-size: 18px;
            font-weight: 700;
            color: #f0f0f0;
            margin-bottom: 5px;
        }
        
        .a-list-item-subtitle {
            font-size: 14px;
            color:rgb(245, 230, 230);
        }

        .exam-description {
            font-size: 14px;
            color: #777;
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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
            font-size: 20px;
            color: #f0f0f0;
            font-weight: 600;
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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
            font-size: 20px;
            color: #75343A;
            font-weight: 600;
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

            .main {
                margin-left: 0px;
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
    </style>
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="dashboard-header">
        <h1 class="dashboard-title">STREAMS ADMIN DASHBOARD</h1>
        <div class="dashboard-date">
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>
    
   
    
    <!-- Dashboard Sections -->
    <div class="dashboard-sections">
        <!-- Upcoming Exams -->
        <div class="dashboard-section">
            <div class="section-header">
                <span>Upcoming Exams</span>
                <a href="exam.php">View All</a>
            </div>
            <div class="section-body">
                <?php if ($upcoming_exams && $upcoming_exams->num_rows > 0): ?>
                    <?php while ($exam = $upcoming_exams->fetch_assoc()): 
                        $exam_date = date('M d, Y', strtotime($exam['scheduled_date']));
                        $exam_time = date('h:i A', strtotime($exam['scheduled_time']));
                    ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title"><?php echo htmlspecialchars($exam['title']); ?></div>
                                <div class="list-item-subtitle">
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
                            <span class="list-item-badge <?php echo $exam['exam_type'] === 'tech' ? 'badge-tech' : 'badge-non-tech'; ?>">
                                <?php echo ucfirst($exam['exam_type']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">No upcoming exams scheduled</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Announcements -->
        <div class="announcement-dashboard-section">
            <div class="announcement-section-header">
                <span>Recent Announcements</span>
                <a href="announcement.php">View All</a>
            </div>
            <div class="announcement-section-body">
                <?php if ($recent_announcements && $recent_announcements->num_rows > 0): ?>
                    <?php while ($announcement = $recent_announcements->fetch_assoc()): ?>
                        <div class="a-list-item">
                            <div class="a-list-item-content">
                                <div class="a-list-item-title">
                                    <?php echo htmlspecialchars($announcement['title']); ?>
                                </div>
                                <div class="a-list-item-subtitle">
                                    <?php 
                                        $content = strip_tags($announcement['content']);
                                        echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                    ?>
                                    <br>
                                    <small>Posted: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
                                </div>
                            </div>
                            <span class="a-list-item-badge <?php echo $announcement['status'] === 'active' ? 'badge-accepted' : 'badge-pending'; ?>">
                                <?php echo ucfirst($announcement['status']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">No recent announcements found</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-section">

        </div>
    </div>
    
    <!-- Analytics Preview Section -->
    <h2 class="section-heading">Analytics Overview</h2>
    <div class="analytics-container">
    <!-- Exam Results Analytics Preview -->
    <div class="eq-analytics-preview">
        <div class="eq-analytics-header">
            <h3 class="eq-analytics-title">Exam Results</h3>
            <a href="analytics.php" class="eq-analytics-action">
                Full Analysis <span class="material-symbols-rounded">analytics</span>
            </a>
        </div>
        
        <div class="eq-analytics-grid">
            <div class="eq-metrics-card">
                <div class="eq-metrics-value"><?php echo $stats['total_attempts']; ?></div>
                <div class="eq-metrics-label">Total Attempts</div>
            </div>
            
            <div class="eq-metrics-card">
                <div class="eq-metrics-value"><?php echo $stats['passed_count']; ?></div>
                <div class="eq-metrics-label">Passed</div>
                <div class="eq-progress-bar">
                    <div class="eq-progress progress-pass" style="width: <?php echo ($stats['total_attempts'] > 0) ? ($stats['passed_count'] / $stats['total_attempts'] * 100) : 0; ?>%"></div>
                </div>
            </div>
            
            <div class="eq-metrics-card">
                <div class="eq-metrics-value"><?php echo $stats['failed_count']; ?></div>
                <div class="eq-metrics-label">Failed</div>
                <div class="eq-progress-bar">
                    <div class="eq-progress progress-fail" style="width: <?php echo ($stats['total_attempts'] > 0) ? ($stats['failed_count'] / $stats['total_attempts'] * 100) : 0; ?>%"></div>
                </div>
            </div>
            
            <div class="eq-metrics-card">
                <div class="eq-metrics-value"><?php echo $stats['pass_rate']; ?>%</div>
                <div class="eq-metrics-label">Pass Rate</div>
            </div>
        </div>
    </div>
    
    <!-- Item Analysis Preview -->
    <div class="analytics-preview">
        <div class="analytics-header">
            <h3 class="analytics-title">Item Analysis Preview</h3>
            <a href="analytics.php" class="analytics-action">
                Full Item Analysis <span class="material-symbols-rounded">lab_profile</span>
            </a>
        </div>
        
        <p style="margin-bottom: 15px;">Items flagged for revision based on student performance difficulty analysis:</p>
        
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
</body>
</html>
