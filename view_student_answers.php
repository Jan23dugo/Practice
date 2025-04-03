<?php
session_start();
require_once('config/config.php');

// Check if user is logged in as admin
// if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
//     header("Location: admin_login.php");
//     exit();
// }

// Check if exam_id is set
if (!isset($_GET['exam_id'])) {
    header("Location: manage_results.php");
    exit();
}

$exam_id = $_GET['exam_id'];

// Get the exam details
$query = "SELECT * FROM exams WHERE exam_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    header("Location: manage_results.php");
    exit();
}

// Get all students who have completed this exam
$query = "SELECT rs.student_id, rs.stud_id, rs.first_name, rs.last_name, rs.reference_id, rs.email, 
          ea.final_score, ea.passed, ea.completion_time 
          FROM exam_assignments ea 
          JOIN register_studentsqe rs ON ea.student_id = rs.student_id 
          WHERE ea.exam_id = ? AND ea.completion_status = 'completed'
          ORDER BY rs.last_name, rs.first_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get the selected student if student_id is set
$selected_student = null;
$student_answers = [];

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    
    // Get the student information
    foreach ($students as $student) {
        if ($student['student_id'] == $student_id) {
            $selected_student = $student;
            break;
        }
    }
    
    if ($selected_student) {
        // Get all answers for this student
        $query = "SELECT sa.*, q.question_text, q.question_type, q.points 
                 FROM student_answers sa
                 JOIN questions q ON sa.question_id = q.question_id
                 WHERE sa.student_id = ? AND sa.exam_id = ?
                 ORDER BY q.position, q.question_id";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $student_id, $exam_id);
        $stmt->execute();
        $answers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Organize answers by question
        foreach ($answers as $answer) {
            $student_answers[$answer['question_id']] = $answer;
            
            // For multiple choice, also get the answer text
            if ($answer['question_type'] !== 'programming' && isset($answer['answer_id'])) {
                $query = "SELECT answer_text FROM answers WHERE answer_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $answer['answer_id']);
                $stmt->execute();
                $answer_result = $stmt->get_result()->fetch_assoc();
                
                if ($answer_result) {
                    $student_answers[$answer['question_id']]['answer_text'] = $answer_result['answer_text'];
                }
            }
        }
        
        // Get all questions for this exam
        $query = "SELECT q.*, COUNT(a.answer_id) AS answer_count,
                 (SELECT COUNT(*) FROM student_answers sa WHERE sa.question_id = q.question_id AND sa.student_id = ? AND sa.exam_id = ?) AS has_answer
                 FROM questions q
                 LEFT JOIN answers a ON q.question_id = a.question_id
                 WHERE q.exam_id = ?
                 GROUP BY q.question_id
                 ORDER BY q.position, q.question_id";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $student_id, $exam_id, $exam_id);
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Answers - <?php echo htmlspecialchars($exam['title']); ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .main {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .page-header {
            font-size: 22px;
            font-weight: 500;
            color: #75343A;
            text-align: left;
            padding: 10px 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #75343A;
            color: white;
            padding: 12px 15px;
            font-weight: 500;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .student-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .student-card.selected {
            border: 2px solid #75343A;
        }
        
        .student-header {
            background: #f0f0f0;
            padding: 10px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .student-body {
            padding: 10px;
        }
        
        .student-info {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .student-score {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        
        .passed {
            color: #28a745;
            font-weight: 500;
        }
        
        .failed {
            color: #dc3545;
            font-weight: 500;
        }
        
        .question-section {
            margin-top: 30px;
        }
        
        .question-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .question-header {
            padding: 12px 15px;
            background: #f0f0f0;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .question-body {
            padding: 15px;
        }
        
        .question-text {
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .answer-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .answer-label {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .answer-content {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        
        .answer-correct {
            color: #28a745;
            font-weight: 500;
        }
        
        .answer-incorrect {
            color: #dc3545;
            font-weight: 500;
        }
        
        .no-answer {
            color: #6c757d;
            font-style: italic;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            background: #f0f0f0;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            margin-bottom: 15px;
            transition: background 0.2s;
        }
        
        .back-link:hover {
            background: #e0e0e0;
        }
        
        .student-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .summary-section {
            flex: 1;
        }
        
        .summary-title {
            font-weight: 500;
            margin-bottom: 5px;
            color: #555;
        }
        
        .summary-value {
            font-size: 1.1em;
        }
        
        .summary-score {
            font-size: 1.5em;
            font-weight: 700;
            color: #75343A;
        }
        
        @media (max-width: 768px) {
            .student-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <div class="main">
            <div class="page-header">
                <span class="material-symbols-rounded">assignment</span>
                <?php echo htmlspecialchars($exam['title']); ?> - View Student Answers
            </div>
            
            <a href="manage_results.php" class="back-link">
                <span class="material-symbols-rounded">arrow_back</span> Back to Exam Results
            </a>
            
            <?php if ($selected_student): ?>
                <a href="view_student_answers.php?exam_id=<?php echo $exam_id; ?>" class="back-link">
                    <span class="material-symbols-rounded">people</span> Back to Student List
                </a>
                
                <div class="card">
                    <div class="card-header">
                        Student Information
                    </div>
                    <div class="card-body">
                        <div class="student-summary">
                            <div class="summary-section">
                                <div class="summary-title">Student Name</div>
                                <div class="summary-value"><?php echo htmlspecialchars($selected_student['first_name'] . ' ' . $selected_student['last_name']); ?></div>
                            </div>
                            <div class="summary-section">
                                <div class="summary-title">Student Number</div>
                                <div class="summary-value"><?php echo htmlspecialchars($selected_student['reference_id']); ?></div>
                            </div>
                            <div class="summary-section">
                                <div class="summary-title">Email</div>
                                <div class="summary-value"><?php echo htmlspecialchars($selected_student['email']); ?></div>
                            </div>
                            <div class="summary-section">
                                <div class="summary-title">Final Score</div>
                                <div class="summary-score">
                                    <?php echo $selected_student['final_score']; ?>
                                    <span class="<?php echo $selected_student['passed'] ? 'passed' : 'failed'; ?>">
                                        (<?php echo $selected_student['passed'] ? 'Passed' : 'Failed'; ?>)
                                    </span>
                                </div>
                            </div>
                            <div class="summary-section">
                                <div class="summary-title">Completion Date</div>
                                <div class="summary-value">
                                    <?php echo date('M d, Y g:i A', strtotime($selected_student['completion_time'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="question-section">
                    <h3>Exam Questions and Answers</h3>
                    
                    <?php foreach ($questions as $question): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <div>Question <?php echo $question['position'] ? $question['position'] : 'N/A'; ?> (<?php echo $question['points']; ?> points)</div>
                                <div>
                                    <?php if ($question['has_answer']): ?>
                                        <?php 
                                            $answer = $student_answers[$question['question_id']];
                                            if ($question['question_type'] !== 'programming') {
                                                if ($answer['is_correct']) {
                                                    echo '<span class="answer-correct">Correct</span>';
                                                } else {
                                                    echo '<span class="answer-incorrect">Incorrect</span>';
                                                }
                                            } else {
                                                echo '<span>Score: ' . $answer['score'] . '</span>';
                                            }
                                        ?>
                                    <?php else: ?>
                                        <span class="no-answer">No Answer</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="question-body">
                                <div class="question-text">
                                    <?php echo $question['question_text']; ?>
                                </div>
                                
                                <?php if ($question['has_answer']): ?>
                                    <div class="answer-section">
                                        <div class="answer-label">Student's Answer:</div>
                                        
                                        <?php if ($question['question_type'] === 'programming'): ?>
                                            <div class="answer-content"><?php echo htmlspecialchars($student_answers[$question['question_id']]['programming_answer']); ?></div>
                                        <?php else: ?>
                                            <div class="answer-content">
                                                <?php echo htmlspecialchars($student_answers[$question['question_id']]['answer_text'] ?? 'N/A'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="answer-section">
                                        <div class="no-answer">Student did not answer this question</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        Select a Student to View Answers
                    </div>
                    <div class="card-body">
                        <p>
                            Select a student from the list below to view their exam answers and results.
                        </p>
                        
                        <?php if (empty($students)): ?>
                            <p>No students have completed this exam yet.</p>
                        <?php else: ?>
                            <div class="student-grid">
                                <?php foreach ($students as $student): ?>
                                    <a href="view_student_answers.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student['student_id']; ?>" 
                                       class="student-card">
                                        <div class="student-header">
                                            <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>
                                            <span class="<?php echo $student['passed'] ? 'passed' : 'failed'; ?>">
                                                <?php echo $student['passed'] ? '✓' : '✗'; ?>
                                            </span>
                                        </div>
                                        <div class="student-body">
                                            <div class="student-info">
                                                <strong>ID:</strong> <?php echo htmlspecialchars($student['reference_id']); ?>
                                            </div>
                                            <div class="student-info">
                                                <strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?>
                                            </div>
                                            <div class="student-score">
                                                <div>Score: <strong><?php echo $student['final_score']; ?></strong></div>
                                                <div class="<?php echo $student['passed'] ? 'passed' : 'failed'; ?>">
                                                    <?php echo $student['passed'] ? 'Passed' : 'Failed'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/side.js"></script>
</body>
</html> 