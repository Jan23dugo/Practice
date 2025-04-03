<?php
    session_start(); // Start session if needed

// Check if user is logged in as admin
//if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Not logged in as admin, redirect to admin login page
//    header("Location: admin_login.php");
//    exit();
//}

// Include database connection
include('config/config.php');

// Current page handling
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 20;
$offset = ($current_page - 1) * $items_per_page;

// Fetch total question count for pagination
$count_query = "
SELECT COUNT(*) as total
FROM questions q
JOIN exams e ON q.exam_id = e.exam_id
WHERE q.question_type != 'programming'";

$count_result = $conn->query($count_query);
$total_questions = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_questions / $items_per_page);

// Fetch real data for item analysis - questions with stats
$questions_query = "
SELECT 
    q.question_id,
    q.question_text,
    e.title AS exam_title,
    (
        SELECT COUNT(DISTINCT sa.student_id) 
        FROM student_answers sa 
        WHERE sa.question_id = q.question_id AND sa.is_correct = 1
    ) AS correct_answers,
    (
        SELECT COUNT(DISTINCT sa.student_id) 
        FROM student_answers sa 
        WHERE sa.question_id = q.question_id AND (sa.is_correct = 0 OR sa.is_correct IS NULL)
    ) AS incorrect_answers
FROM questions q
JOIN exams e ON q.exam_id = e.exam_id
WHERE q.question_type != 'programming'
GROUP BY q.question_id
ORDER BY e.title, q.question_id
LIMIT ?, ?"; // Use prepared statement for LIMIT with pagination

$stmt = $conn->prepare($questions_query);
$stmt->bind_param("ii", $offset, $items_per_page);
$stmt->execute();
$questions_result = $stmt->get_result();
$questions = [];

if ($questions_result && $questions_result->num_rows > 0) {
    while ($row = $questions_result->fetch_assoc()) {
        $total_answers = $row['correct_answers'] + $row['incorrect_answers'];
        if ($total_answers > 0) {
            $difficulty_score = $row['incorrect_answers'] / $total_answers;
        } else {
            $difficulty_score = 0.5; // Default value if no students have answered
        }
        
        $questions[] = [
            'exam_title' => $row['exam_title'],
            'question_id' => $row['question_id'],
            'question_text' => $row['question_text'],
            'correct_answers' => $row['correct_answers'],
            'incorrect_answers' => $row['incorrect_answers'],
            'difficulty_score' => $difficulty_score,
            'for_revision' => $difficulty_score > 0.7 // Flag questions that more than 70% of students get wrong
        ];
    }
}

// If no real data found, use a small sample for display purposes
if (empty($questions)) {
    $questions = [
        [
            'exam_title' => 'Sample Exam',
            'question_id' => 1,
            'question_text' => 'Sample question (no real data found)',
            'correct_answers' => 5,
            'incorrect_answers' => 5,
            'difficulty_score' => 0.5,
            'for_revision' => false
        ]
    ];
}

// Fetch student demographics
$demographics_query = "
SELECT 
    student_type,
    COUNT(*) as count
FROM register_studentsqe 
GROUP BY student_type";

$demographics_result = $conn->query($demographics_query);
$demographics = [
    'transferee' => 0,
    'shiftee' => 0,
    'ladderized' => 0,
    'regular' => 0,
    'total' => 0
];

if ($demographics_result && $demographics_result->num_rows > 0) {
    while ($row = $demographics_result->fetch_assoc()) {
        $type = strtolower($row['student_type']);
        if (isset($demographics[$type])) {
            $demographics[$type] = (int)$row['count'];
        } else {
            $demographics['regular'] += (int)$row['count']; // Default category
        }
        $demographics['total'] += (int)$row['count'];
    }
}

// Fetch exam statistics
$exams_query = "
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN exam_type = 'tech' THEN 1 ELSE 0 END) as technical,
    SUM(CASE WHEN exam_type != 'tech' THEN 1 ELSE 0 END) as non_technical,
    SUM(CASE WHEN is_scheduled = 1 THEN 1 ELSE 0 END) as scheduled,
    COUNT(DISTINCT exam_id) as unique_exams
FROM exams";

$exams_result = $conn->query($exams_query);
$exams = [
    'total' => 0,
    'technical' => 0,
    'non_technical' => 0,
    'scheduled' => 0,
    'completed' => 0
];

if ($exams_result && $exams_result->num_rows > 0) {
    $row = $exams_result->fetch_assoc();
    $exams['total'] = (int)$row['total'];
    $exams['technical'] = (int)$row['technical'];
    $exams['non_technical'] = (int)$row['non_technical'];
    $exams['scheduled'] = (int)$row['scheduled'];
    
    // Fetch completed exams count
    $completed_query = "
    SELECT COUNT(DISTINCT e.exam_id) as completed
    FROM exams e
    JOIN exam_assignments ea ON e.exam_id = ea.exam_id
    WHERE ea.completion_status = 'completed'";
    
    $completed_result = $conn->query($completed_query);
    if ($completed_result && $completed_result->num_rows > 0) {
        $completed_row = $completed_result->fetch_assoc();
        $exams['completed'] = (int)$completed_row['completed'];
    }
}

// Fetch exam results
$results_query = "
SELECT 
    e.exam_id,
    e.title AS exam_title,
    COUNT(DISTINCT ea.student_id) AS total_students,
    SUM(CASE WHEN ea.passed = 1 THEN 1 ELSE 0 END) AS pass_count,
    SUM(CASE WHEN ea.passed = 0 THEN 1 ELSE 0 END) AS fail_count,
    AVG(ea.final_score) AS average_score,
    MAX(ea.final_score) AS highest_score,
    MIN(ea.final_score) AS lowest_score
FROM exams e
JOIN exam_assignments ea ON e.exam_id = ea.exam_id
WHERE ea.completion_status = 'completed'
GROUP BY e.exam_id
ORDER BY e.exam_id DESC
LIMIT 10"; // Limit to 10 most recent exams

$results_result = $conn->query($results_query);
$exam_results = [];

if ($results_result && $results_result->num_rows > 0) {
    while ($row = $results_result->fetch_assoc()) {
        $total_students = (int)$row['total_students'];
        $pass_count = (int)$row['pass_count'];
        
        $exam_results[] = [
            'exam_id' => $row['exam_id'],
            'exam_title' => $row['exam_title'],
            'total_students' => $total_students,
            'pass_count' => $pass_count,
            'fail_count' => (int)$row['fail_count'],
            'pass_rate' => $total_students > 0 ? round(($pass_count / $total_students) * 100) : 0,
            'average_score' => round($row['average_score'], 1),
            'highest_score' => round($row['highest_score']),
            'lowest_score' => round($row['lowest_score'])
        ];
    }
}

// If no real data found, use a sample for display
if (empty($exam_results)) {
    $exam_results = [
        [
            'exam_id' => 1,
            'exam_title' => 'Sample Exam (no real data found)',
            'total_students' => 0,
            'pass_count' => 0,
            'fail_count' => 0,
            'pass_rate' => 0,
            'average_score' => 0,
            'highest_score' => 0,
            'lowest_score' => 0
        ]
    ];
}

// Function to get score distribution for a specific exam
function getScoreDistribution($conn, $exam_id) {
    $distribution = [
        '91-100' => 0,
        '81-90' => 0,
        '71-80' => 0,
        '61-70' => 0,
        '51-60' => 0,
        '41-50' => 0,
        '31-40' => 0,
        '21-30' => 0,
        '11-20' => 0,
        '0-10' => 0
    ];
    
    $query = "
    SELECT final_score
    FROM exam_assignments
    WHERE exam_id = ? AND completion_status = 'completed'";
    
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $score = (int)$row['final_score'];
            
            if ($score >= 91) $distribution['91-100']++;
            elseif ($score >= 81) $distribution['81-90']++;
            elseif ($score >= 71) $distribution['71-80']++;
            elseif ($score >= 61) $distribution['61-70']++;
            elseif ($score >= 51) $distribution['51-60']++;
            elseif ($score >= 41) $distribution['41-50']++;
            elseif ($score >= 31) $distribution['31-40']++;
            elseif ($score >= 21) $distribution['21-30']++;
            elseif ($score >= 11) $distribution['11-20']++;
            else $distribution['0-10']++;
        }
        
        $stmt->close();
    }
    
    return $distribution;
}

// Get score distribution for the first exam (if any)
$score_distribution = [];
if (!empty($exam_results)) {
    $score_distribution = getScoreDistribution($conn, $exam_results[0]['exam_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - CCIS Qualifying Exam System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Linking Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Main Structure Styles */
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .analytics-title {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        
        .analytics-date {
            font-size: 18px;
            color: #555;
            font-weight: 500;
        }
        
        .analytics-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 30px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .analytics-tab {
            padding: 15px 25px;
            font-size: 16px;
            font-weight: 500;
            color: #555;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .analytics-tab:hover {
            color: #75343A;
        }
        
        .analytics-tab.active {
            color: #75343A;
            border-bottom-color: #75343A;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Card Styles */
        .analytics-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 20px;
            color: #333;
            font-weight: 600;
        }
        
        .card-action {
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
            cursor: pointer;
        }
        
        .card-action:hover {
            background-color: #5a2930;
        }
        
        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .metric-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        }
        
        .metric-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }
        
        .metric-label {
            font-size: 14px;
            color: #666;
        }
        
        /* Tables */
        .analytics-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .analytics-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #eee;
        }
        
        .analytics-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        
        .analytics-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .analytics-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Progress Bars */
        .progress-bar {
            height: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
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
        
        /* Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-revision {
            background-color: #ffecec;
            color: #F44336;
            border: 1px solid #ffcdd2;
        }
        
        .badge-good {
            background-color: #e8f5e9;
            color: #4CAF50;
            border: 1px solid #c8e6c9;
        }
        
        .badge-warning {
            background-color: #fff8e1;
            color: #FFC107;
            border: 1px solid #ffecb3;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #75343A;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5a2930;
        }
        
        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        /* Action Buttons Group */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        
        /* Chart Containers */
        .chart-container {
            height: 300px;
            margin: 20px 0;
        }
        
        .half-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .half-charts {
                grid-template-columns: 1fr;
            }
        }
        
        /* Two-Column Layout */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 992px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
        
        /* Difficulty Indicator */
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
        
        .easy { background-color: #4CAF50; }
        .medium { background-color: #FF9800; }
        .hard { background-color: #F44336; }
        
        /* Score Distribution Grid */
        .distribution-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .distribution-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .distribution-range {
            font-weight: 700;
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .distribution-count {
            font-size: 14px;
            color: #666;
        }

        /* Detail Panel for Exam Results */
        .detail-panel {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        
        .detail-panel.active {
            display: block;
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .student-card {
            background-color: white;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .student-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .student-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .student-score {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .score-value {
            font-weight: 700;
            font-size: 18px;
        }
        
        .pass {
            color: #4CAF50;
        }
        
        .fail {
            color: #F44336;
        }

        /* Pagination Styles */
        .pagination-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 20px 0;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 8px;
            margin: 0 4px;
            border-radius: 4px;
            color: #333;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .pagination-btn:hover {
            background-color: #e0e0e0;
        }
        
        .pagination-btn.active {
            background-color: #75343A;
            color: white;
            border-color: #75343A;
        }
        
        .pagination-btn.disabled {
            color: #aaa;
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .pagination-ellipsis {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
        }
        
        .pagination-info {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="analytics-header">
        <h1 class="analytics-title">Analytics Dashboard</h1>
        <div class="analytics-date">
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>
    
    <!-- Analytics Navigation Tabs -->
    <div class="analytics-tabs">
        <button class="analytics-tab active" data-tab="item-analysis">Item Analysis</button>
        <button class="analytics-tab" data-tab="demographics">Student Demographics</button>
        <button class="analytics-tab" data-tab="exam-overview">Exam Overview</button>
        <button class="analytics-tab" data-tab="exam-results">Exam Results</button>
    </div>
    
    <!-- Item Analysis Tab Content -->
    <div id="item-analysis" class="tab-content active">
        <div class="analytics-card">
            <div class="card-header">
                <h2 class="card-title">Question Difficulty Analysis</h2>
                <div class="action-buttons">
                    <button class="btn btn-secondary">
                        <span class="material-symbols-rounded">filter_alt</span> Filter
                    </button>
                    <button class="btn btn-primary">
                        <span class="material-symbols-rounded">download</span> Export
                    </button>
                </div>
            </div>
            
            <p>This analysis evaluates the difficulty of exam questions based on student performance. Questions with high incorrect answer rates are flagged for potential revision.</p>
            
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Question</th>
                        <th>Correct Answers</th>
                        <th>Incorrect Answers</th>
                        <th>Difficulty Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $question): 
                        $difficulty_class = '';
                        if ($question['difficulty_score'] < 0.3) {
                            $difficulty_class = 'easy';
                        } else if ($question['difficulty_score'] < 0.7) {
                            $difficulty_class = 'medium';
                        } else {
                            $difficulty_class = 'hard';
                        }
                        
                        $total = $question['correct_answers'] + $question['incorrect_answers'];
                        $correct_percentage = $total > 0 ? round(($question['correct_answers'] / $total) * 100) : 0;
                        $incorrect_percentage = $total > 0 ? round(($question['incorrect_answers'] / $total) * 100) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($question['exam_title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($question['question_text'], 0, 50)) . (strlen($question['question_text']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo $question['correct_answers']; ?> (<?php echo $correct_percentage; ?>%)</td>
                            <td><?php echo $question['incorrect_answers']; ?> (<?php echo $incorrect_percentage; ?>%)</td>
                            <td>
                                <div class="difficulty-indicator">
                                    <div class="difficulty-level <?php echo $difficulty_class; ?>" style="width: <?php echo ($question['difficulty_score'] * 100); ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <?php if ($question['for_revision']): ?>
                                    <span class="badge badge-revision">For Revision</span>
                                <?php else: ?>
                                    <span class="badge badge-good">Good</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="chart-container">
                <canvas id="questionDifficultyChart"></canvas>
            </div>
            
            <!-- Pagination controls -->
            <?php if($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if($current_page > 1): ?>
                        <a href="?page=1" class="pagination-btn">&laquo;</a>
                        <a href="?page=<?php echo $current_page - 1; ?>" class="pagination-btn">&lsaquo;</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">&laquo;</span>
                        <span class="pagination-btn disabled">&lsaquo;</span>
                    <?php endif; ?>
                    
                    <?php
                    // Calculate range of page numbers to display
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    // Always show first page
                    if($start_page > 1) {
                        echo '<a href="?page=1" class="pagination-btn">1</a>';
                        if($start_page > 2) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                    }
                    
                    // Display page numbers
                    for($i = $start_page; $i <= $end_page; $i++) {
                        if($i == $current_page) {
                            echo '<span class="pagination-btn active">' . $i . '</span>';
                        } else {
                            echo '<a href="?page=' . $i . '" class="pagination-btn">' . $i . '</a>';
                        }
                    }
                    
                    // Always show last page
                    if($end_page < $total_pages) {
                        if($end_page < $total_pages - 1) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . '" class="pagination-btn">' . $total_pages . '</a>';
                    }
                    
                    // Next and last buttons
                    if($current_page < $total_pages) {
                        echo '<a href="?page=' . ($current_page + 1) . '" class="pagination-btn">&rsaquo;</a>';
                        echo '<a href="?page=' . $total_pages . '" class="pagination-btn">&raquo;</a>';
                    } else {
                        echo '<span class="pagination-btn disabled">&rsaquo;</span>';
                        echo '<span class="pagination-btn disabled">&raquo;</span>';
                    }
                    ?>
                </div>
                <div class="pagination-info">
                    Showing <?php echo min(($current_page - 1) * $items_per_page + 1, $total_questions); ?> - 
                    <?php echo min($current_page * $items_per_page, $total_questions); ?> of <?php echo $total_questions; ?> questions
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card-footer">
                <p><strong>Note:</strong> Questions flagged "For Revision" must be manually updated in the question bank by administrators. This system identifies potentially problematic questions but does not automatically modify them.</p>
            </div>
        </div>
    </div>
    
    <!-- Student Demographics Tab Content -->
    <div id="demographics" class="tab-content">
        <div class="analytics-card">
            <div class="card-header">
                <h2 class="card-title">Student Demographics</h2>
                <button class="card-action">
                    <span class="material-symbols-rounded">download</span> Export Data
                </button>
            </div>
            
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value"><?php echo $demographics['total']; ?></div>
                    <div class="metric-label">Total Students</div>
                </div>
                
                <?php if ($demographics['transferee'] > 0): ?>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $demographics['transferee']; ?></div>
                    <div class="metric-label">Transferees</div>
                </div>
                <?php endif; ?>
                
                <?php if ($demographics['shiftee'] > 0): ?>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $demographics['shiftee']; ?></div>
                    <div class="metric-label">Shiftees</div>
                </div>
                <?php endif; ?>
                
                <?php if ($demographics['ladderized'] > 0): ?>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $demographics['ladderized']; ?></div>
                    <div class="metric-label">Ladderized</div>
                </div>
                <?php endif; ?>
                
                <?php if ($demographics['regular'] > 0): ?>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $demographics['regular']; ?></div>
                    <div class="metric-label">Regular</div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="two-columns">
                <div class="chart-container">
                    <canvas id="demographicsPieChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="demographicsTrendChart"></canvas>
                </div>
            </div>
            
            <div class="card-footer">
                <p>This data represents the distribution of student types registered for the CCIS qualifying exams. Understanding the demographic breakdown helps in tailoring exam content and support resources appropriately.</p>
            </div>
        </div>
    </div>
    
    <!-- Exam Overview Tab Content -->
    <div id="exam-overview" class="tab-content">
        <div class="analytics-card">
            <div class="card-header">
                <h2 class="card-title">Exam Overview</h2>
                <a href="exams.php" class="card-action">
                    <span class="material-symbols-rounded">list</span> View All Exams
                </a>
            </div>
            
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value"><?php echo $exams['total']; ?></div>
                    <div class="metric-label">Total Exams</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $exams['technical']; ?></div>
                    <div class="metric-label">Technical Exams</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $exams['non_technical']; ?></div>
                    <div class="metric-label">Non-Technical Exams</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $exams['scheduled']; ?></div>
                    <div class="metric-label">Scheduled Exams</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $exams['completed']; ?></div>
                    <div class="metric-label">Completed Exams</div>
                </div>
            </div>
            
            <div class="chart-container">
                <canvas id="examTypesChart"></canvas>
            </div>
            
            <div class="card-footer">
                <p>The exam overview provides a snapshot of the current exam database, including technical and non-technical assessments. Click "View All Exams" to see the comprehensive list and manage individual exams.</p>
            </div>
        </div>
    </div>
    
    <!-- Exam Results Tab Content -->
    <div id="exam-results" class="tab-content">
        <div class="analytics-card">
            <div class="card-header">
                <h2 class="card-title">Exam Results Summary</h2>
                <div class="action-buttons">
                    <button class="btn btn-secondary" id="downloadSelectedBtn" disabled>
                        <span class="material-symbols-rounded">download</span> Download Selected
                    </button>
                    <button class="btn btn-primary" id="downloadAllBtn">
                        <span class="material-symbols-rounded">download</span> Download All
                    </button>
                </div>
            </div>
            
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Exam Title</th>
                        <th>Total Students</th>
                        <th>Pass Rate</th>
                        <th>Average Score</th>
                        <th>Score Range</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exam_results as $index => $result): ?>
                        <tr>
                            <td><input type="checkbox" class="examCheckbox" value="<?php echo $result['exam_id']; ?>"></td>
                            <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                            <td><?php echo $result['total_students']; ?></td>
                            <td>
                                <?php echo $result['pass_rate']; ?>%
                                <div class="progress-bar">
                                    <div class="progress progress-pass" style="width: <?php echo $result['pass_rate']; ?>%"></div>
                                </div>
                            </td>
                            <td><?php echo $result['average_score']; ?></td>
                            <td><?php echo $result['lowest_score']; ?> - <?php echo $result['highest_score']; ?></td>
                            <td>
                                <button class="btn btn-secondary view-details-btn" data-exam-id="<?php echo $result['exam_id']; ?>" data-exam-title="<?php echo htmlspecialchars($result['exam_title']); ?>">
                                    <span class="material-symbols-rounded">visibility</span> Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Detailed Results Panel (hidden by default) -->
            <div id="exam-detail-panel" class="detail-panel">
                <div class="detail-header">
                    <h3 class="detail-title" id="detail-exam-title">Technical Assessment Exam Results</h3>
                    <button class="btn btn-secondary" id="close-detail-btn">
                        <span class="material-symbols-rounded">close</span> Close
                    </button>
                </div>
                
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value" id="detail-total-students">0</div>
                        <div class="metric-label">Total Students</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="detail-pass-count">0</div>
                        <div class="metric-label">Passed</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="detail-fail-count">0</div>
                        <div class="metric-label">Failed</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="detail-pass-rate">0%</div>
                        <div class="metric-label">Pass Rate</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="detail-avg-score">0</div>
                        <div class="metric-label">Average Score</div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="scoreDistributionChart"></canvas>
                </div>
                
                <h4 style="margin-top: 30px; margin-bottom: 15px;">Score Distribution</h4>
                <div class="distribution-grid" id="score-distribution-grid">
                    <!-- Distribution items will be populated via JavaScript -->
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-secondary">
                        <span class="material-symbols-rounded">print</span> Print Results
                    </button>
                    <button class="btn btn-primary">
                        <span class="material-symbols-rounded">download</span> Download Full Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="assets/js/side.js"></script>
<script>
    // Tab Navigation
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.analytics-tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                               // Remove active class from all tabs
                               tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Item Analysis Chart
        const questionLabels = <?php echo json_encode(array_map(function($q) { 
            return substr($q['question_text'], 0, 30) . '...'; 
        }, $questions)); ?>;
        
        const correctData = <?php echo json_encode(array_map(function($q) { 
            return $q['correct_answers']; 
        }, $questions)); ?>;
        
        const incorrectData = <?php echo json_encode(array_map(function($q) { 
            return $q['incorrect_answers']; 
        }, $questions)); ?>;
        
        const questionDifficultyCtx = document.getElementById('questionDifficultyChart').getContext('2d');
        new Chart(questionDifficultyCtx, {
            type: 'bar',
            data: {
                labels: questionLabels,
                datasets: [
                    {
                        label: 'Correct Answers',
                        data: correctData,
                        backgroundColor: '#4CAF50',
                    },
                    {
                        label: 'Incorrect Answers',
                        data: incorrectData,
                        backgroundColor: '#F44336',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Demographics Pie Chart
        const demographicsCtx = document.getElementById('demographicsPieChart').getContext('2d');
        const demoLabels = [];
        const demoData = [];
        
        <?php foreach (['transferee', 'shiftee', 'ladderized', 'regular'] as $type): ?>
            <?php if ($demographics[$type] > 0): ?>
                demoLabels.push('<?php echo ucfirst($type); ?>');
                demoData.push(<?php echo $demographics[$type]; ?>);
            <?php endif; ?>
        <?php endforeach; ?>
        
        new Chart(demographicsCtx, {
            type: 'doughnut',
            data: {
                labels: demoLabels,
                datasets: [{
                    data: demoData,
                    backgroundColor: ['#4a6cf7', '#6c5ce7', '#00b894', '#75343A'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Student Type Distribution',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
        
        // Demographics Trend Chart - since we don't have historical data yet,
        // we'll keep this as a placeholder with mock data
        const trendCtx = document.getElementById('demographicsTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Transferees',
                        data: [15, 20, 25, 30, 38, 45],
                        borderColor: '#4a6cf7',
                        tension: 0.3,
                        fill: false
                    },
                    {
                        label: 'Shiftees',
                        data: [10, 15, 18, 22, 26, 30],
                        borderColor: '#6c5ce7',
                        tension: 0.3,
                        fill: false
                    },
                    {
                        label: 'Ladderized',
                        data: [8, 10, 12, 15, 20, 25],
                        borderColor: '#00b894',
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Registration Trend (Sample)',
                        font: {
                            size: 16
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Exam Types Chart
        const examTypesCtx = document.getElementById('examTypesChart').getContext('2d');
        new Chart(examTypesCtx, {
            type: 'bar',
            data: {
                labels: ['Total Exams', 'Technical', 'Non-Technical', 'Scheduled', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $exams['total']; ?>,
                        <?php echo $exams['technical']; ?>,
                        <?php echo $exams['non_technical']; ?>,
                        <?php echo $exams['scheduled']; ?>,
                        <?php echo $exams['completed']; ?>
                    ],
                    backgroundColor: ['#75343A', '#d63031', '#e84393', '#00cec9', '#6c5ce7'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Exam Categories Overview',
                        font: {
                            size: 16
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Score Distribution Chart for Detailed Results View
        const scoreDistributionCtx = document.getElementById('scoreDistributionChart').getContext('2d');
        const scoreDistributionData = <?php echo json_encode(array_values($score_distribution)); ?>;
        const scoreDistributionLabels = <?php echo json_encode(array_keys($score_distribution)); ?>;
        
        const scoreDistributionChart = new Chart(scoreDistributionCtx, {
            type: 'bar',
            data: {
                labels: scoreDistributionLabels,
                datasets: [{
                    label: 'Number of Students',
                    data: scoreDistributionData,
                    backgroundColor: '#75343A',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Score Distribution',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Students'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Score Range'
                        }
                    }
                }
            }
        });
        
        // Global score distribution data
        let globalScoreDistribution = <?php echo json_encode($score_distribution); ?>;
        const allExamData = <?php echo json_encode($exam_results); ?>;
        
        // Populate Score Distribution Grid
        function populateScoreDistribution(distribution) {
            const grid = document.getElementById('score-distribution-grid');
            grid.innerHTML = '';
            
            for (const [range, count] of Object.entries(distribution)) {
                const item = document.createElement('div');
                item.className = 'distribution-item';
                
                const rangeElem = document.createElement('div');
                rangeElem.className = 'distribution-range';
                rangeElem.textContent = range;
                
                const countElem = document.createElement('div');
                countElem.className = 'distribution-count';
                countElem.textContent = `${count} students`;
                
                item.appendChild(rangeElem);
                item.appendChild(countElem);
                grid.appendChild(item);
            }
        }
        
        // Initially populate with first exam's data
        populateScoreDistribution(globalScoreDistribution);
        
        // Handle View Details button clicks
        const viewDetailsBtns = document.querySelectorAll('.view-details-btn');
        const closeDetailBtn = document.getElementById('close-detail-btn');
        const detailPanel = document.getElementById('exam-detail-panel');
        
        viewDetailsBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const examId = this.getAttribute('data-exam-id');
                const examTitle = this.getAttribute('data-exam-title');
                
                // Update the detail panel title
                document.getElementById('detail-exam-title').textContent = examTitle;
                
                // Reset the detail view
                document.getElementById('detail-total-students').textContent = '...';
                document.getElementById('detail-pass-count').textContent = '...';
                document.getElementById('detail-fail-count').textContent = '...';
                document.getElementById('detail-pass-rate').textContent = '...';
                document.getElementById('detail-avg-score').textContent = '...';
                
                // Show the loading overlay
                document.getElementById('detail-loading').style.display = 'flex';
                document.getElementById('exam-detail-panel').style.display = 'block';
                
                // Fetch the score distribution data
                fetch(`get_score_distribution.php?exam_id=${examId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the score distribution grid
                            updateScoreDistributionGrid(data.distribution);
                            
                            // Find the exam in the exam_results array
                            const examResult = findExamResult(examId);
                            if (examResult) {
                                document.getElementById('detail-total-students').textContent = examResult.total_students;
                                document.getElementById('detail-pass-count').textContent = examResult.pass_count;
                                document.getElementById('detail-fail-count').textContent = examResult.fail_count;
                                document.getElementById('detail-pass-rate').textContent = examResult.pass_rate + '%';
                                document.getElementById('detail-avg-score').textContent = examResult.average_score;
                            }
                            
                            // Update the score distribution chart
                            updateScoreDistributionChart(data.distribution);
                        } else {
                            console.error('Error fetching score distribution:', data.error);
                            alert('Failed to load exam details. Please try again.');
                        }
                        
                        // Hide the loading overlay
                        document.getElementById('detail-loading').style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Network error:', error);
                        alert('Network error. Please check your connection and try again.');
                        document.getElementById('detail-loading').style.display = 'none';
                    });
            });
        });
        
        function findExamResult(examId) {
            // Convert examId to number for comparison
            examId = Number(examId);
            
            // This assumes we have a global examResults variable with the exam data
            // We'll define this variable with PHP
            return allExamData.find(exam => exam.exam_id === examId);
        }
        
        function updateScoreDistributionGrid(distribution) {
            const grid = document.getElementById('score-distribution-grid');
            grid.innerHTML = ''; // Clear existing content
            
            const ranges = Object.keys(distribution).sort((a, b) => {
                // Extract the first number from each range (e.g., "91-100" -> 91)
                const aStart = parseInt(a.split('-')[0]);
                const bStart = parseInt(b.split('-')[0]);
                return bStart - aStart; // Sort in descending order
            });
            
            ranges.forEach(range => {
                const count = distribution[range];
                const percentage = count > 0 ? Math.round((count / getTotalStudents(distribution)) * 100) : 0;
                
                const row = document.createElement('div');
                row.className = 'score-range-row';
                
                const rangeLabel = document.createElement('div');
                rangeLabel.className = 'score-range-label';
                rangeLabel.textContent = range;
                
                const barContainer = document.createElement('div');
                barContainer.className = 'score-bar-container';
                
                const bar = document.createElement('div');
                bar.className = 'score-bar';
                bar.style.width = `${percentage}%`;
                
                const countLabel = document.createElement('div');
                countLabel.className = 'score-count';
                countLabel.textContent = `${count} (${percentage}%)`;
                
                barContainer.appendChild(bar);
                barContainer.appendChild(countLabel);
                
                row.appendChild(rangeLabel);
                row.appendChild(barContainer);
                
                grid.appendChild(row);
            });
        }
        
        function getTotalStudents(distribution) {
            return Object.values(distribution).reduce((sum, count) => sum + count, 0);
        }
        
        function updateScoreDistributionChart(distribution) {
            const ctx = document.getElementById('scoreDistributionChart').getContext('2d');
            
            // Sort the ranges
            const ranges = Object.keys(distribution).sort((a, b) => {
                const aStart = parseInt(a.split('-')[0]);
                const bStart = parseInt(b.split('-')[0]);
                return aStart - bStart; // Sort in ascending order for the chart
            });
            
            // Get the data in the sorted order
            const counts = ranges.map(range => distribution[range]);
            
            // If we already have a chart, destroy it
            if (window.scoreDistChart) {
                window.scoreDistChart.destroy();
            }
            
            // Create the new chart
            window.scoreDistChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ranges,
                    datasets: [{
                        label: 'Number of Students',
                        data: counts,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Score Distribution'
                        }
                    }
                }
            });
        }
        
        // Close button for the detail panel
        closeDetailBtn.addEventListener('click', function() {
            document.getElementById('exam-detail-panel').style.display = 'none';
        });
        
        // Handle checkbox selection for downloading
        const selectAllCheckbox = document.getElementById('selectAll');
        const examCheckboxes = document.querySelectorAll('.examCheckbox');
        const downloadSelectedBtn = document.getElementById('downloadSelectedBtn');
        
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            
            examCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            downloadSelectedBtn.disabled = !isChecked;
        });
        
        examCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const anyChecked = Array.from(examCheckboxes).some(cb => cb.checked);
                downloadSelectedBtn.disabled = !anyChecked;
                
                // Update "select all" checkbox
                const allChecked = Array.from(examCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            });
        });
        
        // Download buttons (mock functionality)
        document.getElementById('downloadSelectedBtn').addEventListener('click', function() {
            const selectedExams = Array.from(examCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
                
            alert('Downloading reports for selected exams: ' + selectedExams.join(', '));
            // In a real application, this would trigger a download of the selected reports
        });
        
        document.getElementById('downloadAllBtn').addEventListener('click', function() {
            alert('Downloading reports for all exams');
            // In a real application, this would trigger a download of all reports
        });
    });
</script>
</body>
</html>