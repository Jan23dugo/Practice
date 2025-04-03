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

// Mock data (for UI demonstration only)
$stats = [
    'total_students' => 156,
    'qualified_students' => 87,
    'pending_students' => 69,
    'total_exams' => 12,
    'tech_exams' => 5,
    'non_tech_exams' => 7,
    'scheduled_exams' => 4,
    'question_bank_total' => 234,
    'total_attempts' => 324,
    'passed_count' => 245,
    'failed_count' => 79,
    'pass_rate' => 76
];

// Recent registrations - only fetch if table exists
try {
$query = "SELECT * FROM register_studentsqe ORDER BY registration_date DESC LIMIT 5";
$recent_registrations = $conn->query($query);
} catch (Exception $e) {
    // Create empty result set if table doesn't exist
    $recent_registrations = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Recent announcements - only fetch if table exists
try {
$query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
$recent_announcements = $conn->query($query);
} catch (Exception $e) {
    // Create empty result set if table doesn't exist
    $recent_announcements = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Upcoming exams - only fetch if table exists
try {
$query = "SELECT * FROM exams WHERE is_scheduled = 1 AND scheduled_date >= CURDATE() ORDER BY scheduled_date ASC LIMIT 5";
$upcoming_exams = $conn->query($query);
} catch (Exception $e) {
    // Create empty result set if table doesn't exist
    $upcoming_exams = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Mock data for difficult exams (for UI preview only)
$mock_difficult_exams = [
    [
        'title' => 'Technical Assessment Exam',
        'total_questions' => 50,
        'difficult_questions' => 8,
        'difficulty_percent' => 16
    ],
    [
        'title' => 'Programming Fundamentals',
        'total_questions' => 40,
        'difficult_questions' => 12,
        'difficulty_percent' => 30
    ],
    [
        'title' => 'Database Systems',
        'total_questions' => 35,
        'difficult_questions' => 15,
        'difficulty_percent' => 43
    ],
    [
        'title' => 'Math Logic Exam',
        'total_questions' => 30,
        'difficult_questions' => 20, 
        'difficulty_percent' => 67
    ],
    [
        'title' => 'Web Development',
        'total_questions' => 45,
        'difficult_questions' => 5,
        'difficulty_percent' => 11
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CCIS Qualifying Exam System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Linking Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
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
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        
        .list-item-subtitle {
            font-size: 13px;
            color: #777;
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
        
        /* Analytics Cards */
        .analytics-preview {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
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
    </style>
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="dashboard-header">
        <h1 class="dashboard-title">CCIS Qualifying Exam Dashboard</h1>
        <div class="dashboard-date">
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>
    
    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Students</span>
                <div class="stat-icon icon-students">
                    <span class="material-symbols-rounded">group</span>
                </div>
            </div>
            <div class="stat-value"><?php echo $stats['total_students']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Qualified Students</span>
                <div class="stat-icon icon-qualified">
                    <span class="material-symbols-rounded">verified_user</span>
                </div>
            </div>
            <div class="stat-value"><?php echo $stats['qualified_students']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Pending Students</span>
                <div class="stat-icon icon-pending">
                    <span class="material-symbols-rounded">pending</span>
                </div>
            </div>
            <div class="stat-value"><?php echo $stats['pending_students']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Exams</span>
                <div class="stat-icon icon-exams">
                    <span class="material-symbols-rounded">quiz</span>
                </div>
            </div>
            <div class="stat-value"><?php echo $stats['total_exams']; ?></div>
        </div>
    </div>
    
    <!-- Dashboard Sections -->
    <div class="dashboard-sections">
        <!-- Recent Student Registrations -->
        <div class="dashboard-section">
            <div class="section-header">
                <span>Recent Student Registrations</span>
                <a href="registered_students.php">View All</a>
            </div>
            <div class="section-body">
                <?php if (isset($recent_registrations) && $recent_registrations->num_rows > 0): ?>
                    <?php while ($student = $recent_registrations->fetch_assoc()): ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </div>
                                <div class="list-item-subtitle">
                                    <?php echo htmlspecialchars($student['email']); ?> • 
                                    <?php echo htmlspecialchars($student['desired_program']); ?> • 
                                    <?php echo date('M d, Y', strtotime($student['registration_date'])); ?>
                                </div>
                            </div>
                            <span class="list-item-badge <?php echo $student['status'] === 'accepted' ? 'badge-accepted' : 'badge-pending'; ?>">
                                <?php echo ucfirst($student['status']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Mock data for demonstration -->
                    <div class="list-item">
                        <div class="list-item-content">
                            <div class="list-item-title">Janlloyd Dugo</div>
                            <div class="list-item-subtitle">jdugo23@gmail.com • BSIT • Mar 11, 2025</div>
                        </div>
                        <span class="list-item-badge badge-accepted">Accepted</span>
                    </div>
                    <div class="list-item">
                        <div class="list-item-content">
                            <div class="list-item-title">Maria Santos</div>
                            <div class="list-item-subtitle">msantos@gmail.com • BSCS • Mar 10, 2025</div>
                        </div>
                        <span class="list-item-badge badge-pending">Pending</span>
                    </div>
                    <div class="list-item">
                        <div class="list-item-content">
                            <div class="list-item-title">John Smith</div>
                            <div class="list-item-subtitle">jsmith@gmail.com • BSIT • Mar 8, 2025</div>
                        </div>
                        <span class="list-item-badge badge-accepted">Accepted</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Upcoming Exams -->
        <div class="dashboard-section">
            <div class="section-header">
                <span>Upcoming Exams</span>
                <a href="exams.php">View All</a>
            </div>
            <div class="section-body">
                <?php if (isset($upcoming_exams) && $upcoming_exams->num_rows > 0): ?>
                    <?php while ($exam = $upcoming_exams->fetch_assoc()): ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <?php echo htmlspecialchars($exam['title']); ?>
                                </div>
                                <div class="list-item-subtitle">
                                    <strong>Date:</strong> <?php echo date('M d, Y', strtotime($exam['scheduled_date'])); ?> •
                                    <strong>Time:</strong> <?php echo date('h:i A', strtotime($exam['scheduled_time'])); ?> •
                                    <strong>Duration:</strong> <?php echo $exam['duration']; ?> minutes
                                </div>
                            </div>
                            <span class="list-item-badge <?php echo $exam['exam_type'] === 'tech' ? 'badge-tech' : 'badge-non-tech'; ?>">
                                <?php echo $exam['exam_type'] === 'tech' ? 'Technical' : 'Non-Technical'; ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Mock data for demonstration -->
                    <div class="list-item">
                        <div class="list-item-content">
                            <div class="list-item-title">Technical Assessment Exam</div>
                            <div class="list-item-subtitle">
                                <strong>Date:</strong> Mar 24, 2025 •
                                <strong>Time:</strong> 08:28 AM •
                                <strong>Duration:</strong> 60 minutes
                            </div>
                        </div>
                        <span class="list-item-badge badge-tech">Technical</span>
                    </div>
                    <div class="list-item">
                        <div class="list-item-content">
                            <div class="list-item-title">General Programming Knowledge</div>
                            <div class="list-item-subtitle">
                                <strong>Date:</strong> Apr 15, 2025 •
                                <strong>Time:</strong> 10:00 AM •
                                <strong>Duration:</strong> 90 minutes
                            </div>
                        </div>
                        <span class="list-item-badge badge-tech">Technical</span>
                    </div>
                    <div class="list-item">
                        <div class="list-item-content">
                            <div class="list-item-title">Math & Logic Assessment</div>
                            <div class="list-item-subtitle">
                                <strong>Date:</strong> Apr 20, 2025 •
                                <strong>Time:</strong> 01:30 PM •
                                <strong>Duration:</strong> 45 minutes
                            </div>
                        </div>
                        <span class="list-item-badge badge-non-tech">Non-Technical</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Announcements -->
        <div class="dashboard-section">
            <div class="section-header">
                <span>Recent Announcements</span>
                <a href="announcements.php">View All</a>
            </div>
            <div class="section-body">
                <?php if (isset($recent_announcements) && $recent_announcements->num_rows > 0): ?>
                    <?php while ($announcement = $recent_announcements->fetch_assoc()): ?>
                        <div class="list-item">
                            <div class="list-item-content">
                                <div class="list-item-title">
                                    <?php echo htmlspecialchars($announcement['title']); ?>
                                </div>
                                <div class="list-item-subtitle">
                                    <?php 
                                        $content = strip_tags($announcement['content']);
                                        echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                    ?>
                                    <br>
                                    <small>Posted: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
                                </div>
                            </div>
                            <span class="list-item-badge <?php echo $announcement['status'] === 'active' ? 'badge-accepted' : 'badge-pending'; ?>">
                                <?php echo ucfirst($announcement['status']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Mock data for demonstration -->
                    <div class="list-item">
                        <div class="list-item-content">
                            <div class="list-item-title">CCIS Qualifying Exam Schedule</div>
                            <div class="list-item-subtitle">
                                Please be informed that the CCIS qualifying exam registration will be opened on March 17 - 20. Make sure to submit the necessary documents...
                                <br>
                                <small>Posted: Mar 16, 2025</small>
                            </div>
                        </div>
                        <span class="list-item-badge badge-accepted">Active</span>
                    </div>
                    <div class="list-item">
                        <div class="list-item-content">
                            <div class="list-item-title">Exam Location Update</div>
                            <div class="list-item-subtitle">
                                The qualifying exams will be held at the PUP CCIS Lab, 3rd floor. Please arrive 30 minutes before your scheduled time...
                                <br>
                                <small>Posted: Mar 14, 2025</small>
                            </div>
                        </div>
                        <span class="list-item-badge badge-accepted">Active</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Analytics Preview Section -->
    <h2 class="section-heading">Analytics Overview</h2>
    
    <!-- Exam Results Analytics Preview -->
    <div class="analytics-preview">
        <div class="analytics-header">
            <h3 class="analytics-title">Exam Results</h3>
            <a href="exam_analytics.php" class="analytics-action">
                Full Analysis <span class="material-symbols-rounded">analytics</span>
            </a>
        </div>
        
        <div class="analytics-grid">
            <div class="metrics-card">
                <div class="metrics-value"><?php echo $stats['total_attempts']; ?></div>
                <div class="metrics-label">Total Attempts</div>
            </div>
            
            <div class="metrics-card">
                <div class="metrics-value"><?php echo $stats['passed_count']; ?></div>
                <div class="metrics-label">Passed</div>
                <div class="progress-bar">
                    <div class="progress progress-pass" style="width: <?php echo ($stats['passed_count'] / $stats['total_attempts'] * 100); ?>%"></div>
                </div>
            </div>
            
            <div class="metrics-card">
                <div class="metrics-value"><?php echo $stats['failed_count']; ?></div>
                <div class="metrics-label">Failed</div>
                <div class="progress-bar">
                    <div class="progress progress-fail" style="width: <?php echo ($stats['failed_count'] / $stats['total_attempts'] * 100); ?>%"></div>
                </div>
            </div>
            
            <div class="metrics-card">
                <div class="metrics-value"><?php echo $stats['pass_rate']; ?>%</div>
                <div class="metrics-label">Pass Rate</div>
            </div>
        </div>
    </div>
    
    <!-- Item Analysis Preview -->
    <div class="analytics-preview">
        <div class="analytics-header">
            <h3 class="analytics-title">Item Analysis Preview</h3>
            <a href="item_analysis.php" class="analytics-action">
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
                <?php foreach ($mock_difficult_exams as $exam): 
                        $difficulty_class = '';
                        
                    if ($exam['difficulty_percent'] < 30) {
                            $difficulty_class = 'difficulty-easy';
                    } else if ($exam['difficulty_percent'] < 70) {
                            $difficulty_class = 'difficulty-medium';
                        } else {
                            $difficulty_class = 'difficulty-hard';
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                            <td><?php echo $exam['total_questions']; ?></td>
                            <td>
                                <?php if ($exam['difficult_questions'] > 0): ?>
                                    <span class="list-item-badge badge-revision">
                                        <?php echo $exam['difficult_questions']; ?> for revision
                                    </span>
                                <?php else: ?>
                                    <span class="list-item-badge badge-accepted">No revision needed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="difficulty-indicator">
                                <div class="difficulty-level <?php echo $difficulty_class; ?>" style="width: <?php echo $exam['difficulty_percent']; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
    </div>
</div>
</div>
<script src="assets/js/side.js"></script>
</body>
</html>
