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

// Current page handling
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 20;
$offset = ($current_page - 1) * $items_per_page;

// Get selected exam ID from GET parameter
$selected_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : null;

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_difficulty = isset($_GET['filter_difficulty']) ? $_GET['filter_difficulty'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Fetch all exams for the dropdown
$exams_list_query = "SELECT exam_id, title FROM exams ORDER BY title";
$exams_list_result = $conn->query($exams_list_query);
$exams_list = [];
if ($exams_list_result && $exams_list_result->num_rows > 0) {
    while ($row = $exams_list_result->fetch_assoc()) {
        $exams_list[] = $row;
    }
}

// Modify the count query to include exam filter if selected
$count_query = "
SELECT COUNT(*) as total
FROM questions q
JOIN exams e ON q.exam_id = e.exam_id";
if ($selected_exam_id) {
    $count_query .= " AND e.exam_id = " . $selected_exam_id;
}

$count_result = $conn->query($count_query);
$total_questions = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_questions / $items_per_page);

// Modify questions query to include exam filter and filters
$questions_query = "
SELECT 
    q.question_id,
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        question_text,
                        '<p>', ''
                    ),
                    '</p>', ''
                ),
                '<br>', ' '
            ),
            '<br/>', ' '
        ),
        '<br />', ' '
    ) AS question_text,
    q.question_type,
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
    ) AS incorrect_answers,
    CASE 
        WHEN (
            SELECT CASE 
                WHEN COUNT(*) = 0 THEN 0.5
                ELSE CAST(SUM(CASE WHEN sa.is_correct = 0 OR sa.is_correct IS NULL THEN 1 ELSE 0 END) AS FLOAT) / COUNT(*)
            END
            FROM student_answers sa 
            WHERE sa.question_id = q.question_id
        ) < 0.3 THEN 'easy'
        WHEN (
            SELECT CASE 
                WHEN COUNT(*) = 0 THEN 0.5
                ELSE CAST(SUM(CASE WHEN sa.is_correct = 0 OR sa.is_correct IS NULL THEN 1 ELSE 0 END) AS FLOAT) / COUNT(*)
            END
            FROM student_answers sa 
            WHERE sa.question_id = q.question_id
        ) < 0.7 THEN 'medium'
        ELSE 'hard'
    END as difficulty_level
FROM questions q
JOIN exams e ON q.exam_id = e.exam_id
WHERE 1=1";

if ($selected_exam_id) {
    $questions_query .= " AND e.exam_id = ?";
}
if ($filter_type) {
    $questions_query .= " AND q.question_type = '" . $conn->real_escape_string($filter_type) . "'";
}
if ($filter_difficulty) {
    $questions_query .= " HAVING difficulty_level = '" . $conn->real_escape_string($filter_difficulty) . "'";
}
if ($filter_status === 'revision') {
    $questions_query .= " HAVING difficulty_level = 'hard'";
} elseif ($filter_status === 'good') {
    $questions_query .= " HAVING difficulty_level IN ('easy', 'medium')";
}

$questions_query .= "
ORDER BY e.title, q.question_id
LIMIT ?, ?";

$stmt = $conn->prepare($questions_query);
if ($selected_exam_id) {
    $stmt->bind_param("iii", $selected_exam_id, $offset, $items_per_page);
} else {
    $stmt->bind_param("ii", $offset, $items_per_page);
}
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
            'question_type' => $row['question_type'],
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
            'question_type' => 'multiple-choice',
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
WHERE ea.completion_status = 'completed'";
if ($selected_exam_id) {
    $results_query .= " AND e.exam_id = " . $selected_exam_id;
}
$results_query .= "
GROUP BY e.exam_id
ORDER BY e.exam_id DESC
LIMIT 10";

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

// Add at the top of the file after session_start()
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="item_analysis_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers
    fputcsv($output, [
        'Exam',
        'Question',
        'Type',
        'Correct Answers',
        'Correct %',
        'Incorrect Answers',
        'Incorrect %',
        'Difficulty Level',
        'Status'
    ]);
    
    // Modify export query to remove LIMIT
    $export_query = "
    SELECT 
        q.question_id,
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            question_text,
                            '<p>', ''
                        ),
                        '</p>', ''
                    ),
                    '<br>', ' '
                ),
                '<br/>', ' '
            ),
            '<br />', ' '
        ) AS question_text,
        q.question_type,
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
        ) AS incorrect_answers,
        CASE 
            WHEN (
                SELECT CASE 
                    WHEN COUNT(*) = 0 THEN 0.5
                    ELSE CAST(SUM(CASE WHEN sa.is_correct = 0 OR sa.is_correct IS NULL THEN 1 ELSE 0 END) AS FLOAT) / COUNT(*)
                END
                FROM student_answers sa 
                WHERE sa.question_id = q.question_id
            ) < 0.3 THEN 'easy'
            WHEN (
                SELECT CASE 
                    WHEN COUNT(*) = 0 THEN 0.5
                    ELSE CAST(SUM(CASE WHEN sa.is_correct = 0 OR sa.is_correct IS NULL THEN 1 ELSE 0 END) AS FLOAT) / COUNT(*)
                END
                FROM student_answers sa 
                WHERE sa.question_id = q.question_id
            ) < 0.7 THEN 'medium'
            ELSE 'hard'
        END as difficulty_level
    FROM questions q
    JOIN exams e ON q.exam_id = e.exam_id
    WHERE 1=1";
    
    if ($selected_exam_id) {
        $export_query .= " AND e.exam_id = " . (int)$selected_exam_id;
    }
    if ($filter_type) {
        $export_query .= " AND q.question_type = '" . $conn->real_escape_string($filter_type) . "'";
    }
    if ($filter_difficulty) {
        $export_query .= " HAVING difficulty_level = '" . $conn->real_escape_string($filter_difficulty) . "'";
    }
    if ($filter_status === 'revision') {
        $export_query .= " HAVING difficulty_level = 'hard'";
    } elseif ($filter_status === 'good') {
        $export_query .= " HAVING difficulty_level IN ('easy', 'medium')";
    }
    
    $export_query .= " ORDER BY e.title, q.question_id";
    
    $result = $conn->query($export_query);
    
    while ($row = $result->fetch_assoc()) {
        $total = $row['correct_answers'] + $row['incorrect_answers'];
        $correct_percentage = $total > 0 ? round(($row['correct_answers'] / $total) * 100) : 0;
        $incorrect_percentage = $total > 0 ? round(($row['incorrect_answers'] / $total) * 100) : 0;
        
        fputcsv($output, [
            $row['exam_title'],
            $row['question_text'],
            ucfirst($row['question_type']),
            $row['correct_answers'] . ' (' . $correct_percentage . '%)',
            $correct_percentage,
            $row['incorrect_answers'] . ' (' . $incorrect_percentage . '%)',
            $incorrect_percentage,
            ucfirst($row['difficulty_level']),
            $row['difficulty_level'] === 'hard' ? 'For Revision' : 'Good'
        ]);
    }
    
    fclose($output);
    exit();
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
            height: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        
        .difficulty-level {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            transition: width 0.3s ease;
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

        /* Add styles for exam selector */
        .exam-selector {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .exam-selector select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 200px;
            font-size: 14px;
        }
        
        .exam-selector label {
            font-weight: 500;
            color: #333;
        }

        /* Enhanced table styles */
        .question-text {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .question-text:hover {
            white-space: normal;
            overflow: visible;
            background-color: #fff;
            position: relative;
            z-index: 1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 10px;
            border-radius: 4px;
        }
        
        .success-rate {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .rate-value {
            min-width: 45px;
            font-weight: 600;
        }
        
        .progress-bar {
            flex-grow: 1;
            height: 6px;
            background-color: #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            background-color: #4CAF50;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .summary-insights-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .insight-card {
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .insight-value {
            font-size: 24px;
            font-weight: 700;
            color: #75343A;
            margin-bottom: 8px;
        }
        
        .insight-label {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        /* Add styles for the summary statistics */
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #75343A;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        /* Filter Panel Styles */
        .filter-panel {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 500;
            color: #333;
        }

        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: white;
        }

        .close-modal {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #75343A;
            outline: none;
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .edit-btn {
            margin-left: 8px;
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .material-symbols-rounded {
            font-size: 16px;
        }

        /* Add these styles to your existing CSS */
        .status-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-modal {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close-modal:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #75343A;
            outline: none;
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
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
        
        <!-- Add exam selector -->
        <div class="exam-selector">
            <label for="exam-select">Select Exam:</label>
            <select id="exam-select" onchange="changeExam(this.value)">
                <option value="">All Exams</option>
                <?php foreach ($exams_list as $exam): ?>
                    <option value="<?php echo $exam['exam_id']; ?>" 
                            <?php echo ($selected_exam_id == $exam['exam_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($exam['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
                        <button class="btn btn-secondary" onclick="toggleFilterPanel()">
                            <span class="material-symbols-rounded">filter_alt</span> Filter
                        </button>
                        <button class="btn btn-primary" onclick="exportData()">
                            <span class="material-symbols-rounded">download</span> Export
                        </button>
                    </div>
                </div>
                
                <!-- Filter Panel -->
                <div id="filterPanel" class="filter-panel" style="display: none;">
                    <form id="filterForm" method="GET" class="filter-form">
                        <!-- Preserve exam_id if it exists -->
                        <?php if ($selected_exam_id): ?>
                            <input type="hidden" name="exam_id" value="<?php echo $selected_exam_id; ?>">
                        <?php endif; ?>
                        
                        <div class="filter-group">
                            <label for="filter_type">Question Type:</label>
                            <select name="filter_type" id="filter_type">
                                <option value="">All Types</option>
                                <option value="multiple-choice" <?php echo $filter_type === 'multiple-choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                <option value="programming" <?php echo $filter_type === 'programming' ? 'selected' : ''; ?>>Programming</option>
                                <option value="true-false" <?php echo $filter_type === 'true-false' ? 'selected' : ''; ?>>True/False</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter_difficulty">Difficulty Level:</label>
                            <select name="filter_difficulty" id="filter_difficulty">
                                <option value="">All Levels</option>
                                <option value="easy" <?php echo $filter_difficulty === 'easy' ? 'selected' : ''; ?>>Easy</option>
                                <option value="medium" <?php echo $filter_difficulty === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="hard" <?php echo $filter_difficulty === 'hard' ? 'selected' : ''; ?>>Hard</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter_status">Status:</label>
                            <select name="filter_status" id="filter_status">
                                <option value="">All Status</option>
                                <option value="good" <?php echo $filter_status === 'good' ? 'selected' : ''; ?>>Good</option>
                                <option value="revision" <?php echo $filter_status === 'revision' ? 'selected' : ''; ?>>For Revision</option>
                            </select>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Statistics Panel -->
                <div class="stats-summary">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_questions; ?></div>
                        <div class="stat-label">Total Questions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php 
                            $flagged = array_filter($questions, function($q) { return $q['for_revision']; });
                            echo count($flagged); 
                            ?>
                        </div>
                        <div class="stat-label">Flagged for Revision</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php 
                            $success_rates = array_map(function($q) {
                                $total = $q['correct_answers'] + $q['incorrect_answers'];
                                return $total > 0 ? ($q['correct_answers'] / $total) * 100 : 0;
                            }, $questions);
                            echo !empty($success_rates) ? round(array_sum($success_rates) / count($success_rates), 1) : 0;
                            ?>%
                        </div>
                        <div class="stat-label">Average Success Rate</div>
                    </div>
                </div>
                
                <p>This analysis evaluates the difficulty of exam questions based on student performance. Questions with high incorrect answer rates are flagged for potential revision.</p>
                
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Question</th>
                            <th>Type</th>
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
                                <td>
                                    <div class="question-text">
                                        <?php echo htmlspecialchars($question['question_text']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $question['question_type'] === 'programming' ? 'badge-warning' : 'badge-good'; ?>">
                                        <?php echo ucfirst($question['question_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo $question['correct_answers']; ?> (<?php echo $correct_percentage; ?>%)</td>
                                <td><?php echo $question['incorrect_answers']; ?> (<?php echo $incorrect_percentage; ?>%)</td>
                                <td>
                                    <div class="difficulty-indicator">
                                        <div class="difficulty-level <?php echo $difficulty_class; ?>" 
                                             style="width: <?php echo ($question['difficulty_score'] * 100); ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($question['for_revision']): ?>
                                        <div class="status-actions">
                                            <span class="badge badge-revision">For Revision</span>
                                            <button type="button" class="btn btn-sm btn-secondary edit-btn" 
                                                    data-question-id="<?php echo $question['question_id']; ?>"
                                                    data-question-text="<?php echo htmlspecialchars($question['question_text']); ?>"
                                                    data-question-type="<?php echo $question['question_type']; ?>"
                                                    onclick="openEditModal(
                                                        <?php echo json_encode($question['question_id']); ?>, 
                                                        <?php echo json_encode($question['question_text']); ?>, 
                                                        <?php echo json_encode($question['question_type']); ?>,
                                                        <?php echo json_encode($selected_exam_id); ?>
                                                    )">
                                                <span class="material-symbols-rounded">edit</span>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-good">Good</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination controls -->
                <?php if($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php if($current_page > 1): ?>
                            <a href="?page=1<?php echo $selected_exam_id ? '&exam_id='.$selected_exam_id : ''; ?>" class="pagination-btn">&laquo;</a>
                            <a href="?page=<?php echo $current_page - 1; ?><?php echo $selected_exam_id ? '&exam_id='.$selected_exam_id : ''; ?>" class="pagination-btn">&lsaquo;</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">&laquo;</span>
                            <span class="pagination-btn disabled">&lsaquo;</span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if($start_page > 1) {
                            echo '<a href="?page=1'.($selected_exam_id ? '&exam_id='.$selected_exam_id : '').'" class="pagination-btn">1</a>';
                            if($start_page > 2) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                        }
                        
                        for($i = $start_page; $i <= $end_page; $i++) {
                            if($i == $current_page) {
                                echo '<span class="pagination-btn active">' . $i . '</span>';
                            } else {
                                echo '<a href="?page=' . $i . ($selected_exam_id ? '&exam_id='.$selected_exam_id : '').'" class="pagination-btn">' . $i . '</a>';
                            }
                        }
                        
                        if($end_page < $total_pages) {
                            if($end_page < $total_pages - 1) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . ($selected_exam_id ? '&exam_id='.$selected_exam_id : '').'" class="pagination-btn">' . $total_pages . '</a>';
                        }
                        
                        if($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?><?php echo $selected_exam_id ? '&exam_id='.$selected_exam_id : ''; ?>" class="pagination-btn">&rsaquo;</a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $selected_exam_id ? '&exam_id='.$selected_exam_id : ''; ?>" class="pagination-btn">&raquo;</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">&rsaquo;</span>
                            <span class="pagination-btn disabled">&raquo;</span>
                        <?php endif; ?>
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
        
        <!-- Include Student Demographics Tab -->
        <?php include 'stud_demographics.php'; ?>
        
        <!-- Include Exam Overview Tab -->
        <?php include 'exam_overview.php'; ?>
        
        <!-- Include Exam Results Tab -->
        <?php include 'exam_results.php'; ?>
    </div>
</div>

<!-- Include the question edit modal -->
<?php include 'question_edit_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/side.js"></script>
<script src="assets/js/analytics.js"></script>
</body>
</html>