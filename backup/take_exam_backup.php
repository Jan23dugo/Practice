<?php
session_start();
include('config/config.php');

// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: stud_register.php");
    exit();
}

// Get and validate exam_id
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
if ($exam_id === 0) {
    header("Location: stud_dashboard.php?error=invalid_exam");
    exit();
}

// Check if this exam is assigned to this student
$stud_id = $_SESSION['stud_id'];
$check_query = "SELECT ea.* 
                FROM exam_assignments ea 
                JOIN register_studentsqe rs ON ea.student_id = rs.student_id
                WHERE ea.exam_id = ? 
                AND rs.stud_id = ? 
                AND ea.completion_status = 'pending'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $exam_id, $stud_id);
$stmt->execute();
$result = $stmt->get_result();

// Add debug logging
file_put_contents('exam_debug.log', "Check Query executed for exam_id: $exam_id and student_id: $stud_id\n", FILE_APPEND);
file_put_contents('exam_debug.log', "Result rows: " . $result->num_rows . "\n", FILE_APPEND);

if ($result->num_rows === 0) {
    header("Location: stud_dashboard.php?error=not_assigned");
    exit();
}

// Debug the query
file_put_contents('exam_debug.log', "Accessing exam_id: $exam_id\n", FILE_APPEND);

// Get exam information
$exam_query = "SELECT e.*, ea.assigned_date, rs.student_id as registered_student_id 
               FROM exams e
               JOIN exam_assignments ea ON e.exam_id = ea.exam_id
               JOIN register_studentsqe rs ON ea.student_id = rs.student_id
               WHERE e.exam_id = ? 
               AND rs.stud_id = ?";
$stmt = $conn->prepare($exam_query);
$stmt->bind_param("ii", $exam_id, $stud_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

// Debug exam info
if ($exam) {
    file_put_contents('exam_debug.log', "Found exam: {$exam['title']}\n", FILE_APPEND);
} else {
    file_put_contents('exam_debug.log', "ERROR: Exam with ID {$exam_id} not found\n", FILE_APPEND);
}

// Check if randomization is enabled
$randomize = isset($exam['randomize_questions']) && $exam['randomize_questions'] == 1;

// Modify the questions query to include programming question details
$questions_query = "
    SELECT 
        q.*,
        pq.programming_id,
        pq.starter_code,
        pq.language
    FROM questions q
    LEFT JOIN programming_questions pq ON q.question_id = pq.question_id
    WHERE q.exam_id = ?
    ORDER BY q.position, q.question_id";

file_put_contents('exam_debug.log', "Questions query: $questions_query for exam_id: $exam_id\n", FILE_APPEND);

$stmt = $conn->prepare($questions_query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$questions_result = $stmt->get_result();

// Check if the query returned any results
if ($questions_result->num_rows === 0) {
    file_put_contents('exam_debug.log', "Warning: No questions found for exam ID $exam_id\n", FILE_APPEND);
}

$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
    // Debug each question found
    file_put_contents('exam_debug.log', "Found question: {$row['question_id']} - {$row['question_text']}\n", FILE_APPEND);
}

$total_questions = count($questions);
$exam_duration = isset($exam['duration']) ? intval($exam['duration']) : 60; // Get duration from exam or use default

// Add debug log to check exam and questions
file_put_contents('exam_debug.log', "Exam ID: $exam_id\nTotal Questions: $total_questions\n");

// Debug questions to the log
if ($questions) {
    file_put_contents('exam_debug.log', "Questions found:\n" . print_r($questions, true), FILE_APPEND);
} else {
    file_put_contents('exam_debug.log', "No questions found for exam ID $exam_id\n", FILE_APPEND);
}

// Add this after each prepare statement
if ($stmt === false) {
    file_put_contents('exam_debug.log', "Query preparation failed: " . $conn->error . "\n", FILE_APPEND);
    // Handle error appropriately
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam - PUP Qualifying Exam</title>
    <link rel="stylesheet" href="assets/css/styles.css">
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <script>
        // Make sure this is set before loading exam.js
        window.totalQuestions = <?php echo json_encode($total_questions); ?>;
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="assets/take_exam/exam.js"></script>
    <link rel="stylesheet" href="assets/take_exam/exam_style.css">
</head>
<body>
    <div class="exam-wrapper">
    <div class="top-bar">
        <div class="exam-title">
            <img src="img/Logo.png" alt="PUP Logo" class="pup-logo">
            <h1><?php echo htmlspecialchars($exam['title'] ?? 'Exam'); ?></h1>
        </div>
        <div class="exam-controls">
            <div class="exam-timer">
                <span class="material-symbols-rounded">timer</span>
                <span id="timer" data-duration="<?php echo $exam['duration'] ?? 60; ?>">
                    <?php 
                        $duration = $exam['duration'] ?? 60;
                        printf('%02d:%02d:%02d', ($duration/60), ($duration%60), 0);
                    ?>
                </span>
            </div>
            <div class="progress-indicator">
                <span class="progress-text">0 of <?php echo $total_questions; ?> answered</span>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="question-content">
            <form id="examForm">
                <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam_id); ?>">
                <input type="hidden" id="current_exam_id" value="<?php echo $exam_id; ?>">

                <div id="question-container">
                    <?php if ($total_questions > 0): ?>
                        <?php foreach ($questions as $index => $question): ?>
                            <?php 
                            $question_number = $index + 1;
                            $is_programming = isset($question['question_type']) && $question['question_type'] === 'programming';
                            $starterCode = isset($question['starter_code']) ? htmlspecialchars($question['starter_code']) : '';
                            $programmingId = isset($question['programming_id']) ? $question['programming_id'] : '';
                            ?>
                            <div class="question-container <?php echo $is_programming ? 'programming-question' : ''; ?>" 
                                 id="question-<?php echo $question_number; ?>" 
                                 data-question-id="<?php echo $question['question_id']; ?>"
                                 data-question-type="<?php echo $question['question_type']; ?>"
                                 data-programming-id="<?php echo $programmingId; ?>"
                                 style="display: <?php echo $question_number === 1 ? 'flex' : 'none'; ?>;">
                                
                                <!-- Left side: Question and Editor -->
                                <div class="content-wrapper">
                                    <div class="question-box">
                                        <div class="question-header">
                                            <div class="question-info">
                                                <div class="question-badge">Question <?php echo $question_number; ?></div>
                                                <?php if (!empty($question['points'])): ?>
                                                    <div class="question-points"><?php echo $question['points']; ?> points</div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <button type="button" class="btn-flag" onclick="flagQuestion(<?php echo $question_number; ?>)">
                                                <span class="material-symbols-rounded">flag</span> Flag for review
                                            </button>
                                        </div>

                                        <div class="question-text">
                                            <?php echo htmlspecialchars($question['question_text']); ?>
                                        </div>
                                    </div>

                                    <?php if ($question['question_type'] === 'programming'): ?>
                                        <div class="programming-question">
                                            <div class="editor-container">
                                                <textarea id="code-editor-<?php echo $question_number; ?>" 
                                                          class="code-editor" 
                                                          data-programming-id="<?php echo $programmingId; ?>"
                                                          data-starter-code="<?php echo htmlspecialchars($starterCode); ?>"
                                                ></textarea>
                                            </div>
                                            <div class="test-results" id="test-results-<?php echo $question_number; ?>"></div>
                                           
                                        </div>
                                    <?php else: ?>
                                        <div class="choices-container">
                                            <?php
                                            $answers_query = "SELECT * FROM answers WHERE question_id = ?" . ($randomize ? " ORDER BY RAND()" : " ORDER BY position, answer_id");
                                            $stmt = $conn->prepare($answers_query);
                                            $stmt->bind_param("i", $question['question_id']);
                                            $stmt->execute();
                                            $answers = $stmt->get_result();
                                            ?>

                                            <?php while ($answer = $answers->fetch_assoc()): ?>
                                                <div class="option-item">
                                                    <input type="radio" name="q<?php echo $question_number; ?>" 
                                                           id="q<?php echo $question_number; ?>_<?php echo $answer['answer_id']; ?>" 
                                                           value="<?php echo $answer['answer_id']; ?>">
                                                    <label for="q<?php echo $question_number; ?>_<?php echo $answer['answer_id']; ?>">
                                                        <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                    </label>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($is_programming): ?>
                                    <!-- Right side: Test cases -->
                                    <div class="test-cases-sidebar">
                                        <div class="sidebar-header">
                                            <h3>Test Cases</h3>
                                            <div class="test-status">
                                                <div class="status-indicator" id="status-indicator-<?php echo $question_number; ?>">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="test-cases-content">
                                            <?php
                                            // Fetch all test cases, both sample and hidden
                                            $stmt = $conn->prepare("
                                                SELECT 
                                                    test_case_id,
                                                    input,
                                                    expected_output,
                                                    is_hidden,
                                                    description 
                                                FROM test_cases 
                                                WHERE programming_id = ? 
                                                ORDER BY is_hidden, test_case_id
                                            ");
                                            $stmt->bind_param("i", $question['programming_id']);
                                            $stmt->execute();
                                            $test_cases = $stmt->get_result();
                                            
                                            $sample_count = 0;
                                            $hidden_count = 0;
                                            
                                            while ($test = $test_cases->fetch_assoc()):
                                                $is_hidden = $test['is_hidden'] == 1;
                                                $count = $is_hidden ? ++$hidden_count : ++$sample_count;
                                                $case_type = $is_hidden ? 'Hidden' : 'Sample';
                                            ?>
                                                <div class="test-case-item <?php echo $is_hidden ? 'hidden-case' : 'sample-case'; ?>">
                                                    <div class="test-case-header">
                                                        <span class="case-type"><?php echo $case_type; ?> Test Case <?php echo $count; ?></span>
                                                        <div class="case-status" id="case-status-<?php echo $question_number; ?>-<?php echo $test['test_case_id']; ?>">
                                                            <span class="material-symbols-rounded">radio_button_unchecked</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($test['description']): ?>
                                                    <div class="test-case-description">
                                                        <?php echo htmlspecialchars($test['description']); ?>
                                                    </div>
                                                    <?php endif; ?>

                                                    <?php if (!$is_hidden): ?>
                                                    <table class="test-case-table">
                                                        <tr>
                                                            <td class="test-label">Input:</td>
                                                            <td class="test-value"><?php echo htmlspecialchars($test['input']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="test-label">Expected:</td>
                                                            <td class="test-value"><?php echo htmlspecialchars($test['expected_output']); ?></td>
                                                        </tr>
                                                    </table>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endwhile; ?>
                                            
                                            <div id="test-results-<?php echo $question_number; ?>" class="test-results"></div>
                                        </div>
                                        
                                        <div class="sidebar-footer">
                                            <button type="button" class="validate-code" onclick="runCode(<?php echo $question_number; ?>, <?php echo $question['question_id']; ?>, <?php echo $question['programming_id']; ?>)">
                                                <span class="material-symbols-rounded">code</span>
                                                Run Code
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-questions">
                            <h3>No questions available for this exam</h3>
                            <p>Please contact your instructor or administrator.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="question-navigator">
        <div class="navigator-header">
            <h3 class="navigator-title">Question Navigator</h3>
            <button class="navigator-close" onclick="toggleNavigator()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        
        <div class="question-grid">
            <?php for ($i = 1; $i <= $total_questions; $i++): ?>
                <div class="question-number <?php echo $i == $question_number ? 'current' : ''; ?>" 
                     onclick="navigateToQuestion(<?php echo $i; ?>); toggleNavigator();">
                    <?php echo $i; ?>
                </div>
            <?php endfor; ?>
        </div>
        
        <div class="navigator-footer">
            <button type="button" class="btn btn-outline full-width" onclick="toggleNavigator()">
                <span class="material-symbols-rounded">arrow_back</span> Back to Exam
            </button>
        </div>
    </div>
</div>

<!-- Create the navigator overlay -->
<div class="navigator-overlay"></div>

<!-- Fixed Navigation Bar (Without Navigator Toggle) -->
<div class="fixed-navigation">
    <div class="navigation-group">
        <button class="nav-btn" onclick="prevQuestion()" aria-label="Previous question">
            <span class="material-symbols-rounded">arrow_back</span>
        </button>
        
        <button class="nav-btn primary" onclick="nextQuestion()" aria-label="Next question">
            <span class="material-symbols-rounded">arrow_forward</span>
        </button>
    </div>
    
    <button type="button" onclick="submitExam(<?php echo $exam_id; ?>)" class="btn btn-primary">
        <span class="material-symbols-rounded">done_all</span>
        Submit Exam
    </button>
</div>

<select class="programming-language">
    <option value="python">Python</option>
    <option value="cpp">C++</option>
    <option value="java">Java</option>
</select>

</body>
</html> 