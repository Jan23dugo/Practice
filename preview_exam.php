<?php
session_start();
include('config/config.php');

// Get and validate exam_id
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
if ($exam_id === 0) {
    header("Location: quiz_editor.php?error=invalid_exam");
    exit();
}

// Get exam information
$exam_query = "SELECT * FROM exams WHERE exam_id = ?";
$stmt = $conn->prepare($exam_query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    header("Location: quiz_editor.php?error=exam_not_found");
    exit();
}

// Get questions
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

$stmt = $conn->prepare($questions_query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$questions_result = $stmt->get_result();

$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}

$total_questions = count($questions);
$exam_duration = isset($exam['duration']) ? intval($exam['duration']) : 60;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Exam - PUP Qualifying Exam</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
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
                    <span>Preview Mode</span>
                </div>
                <div class="progress-indicator">
                    <span class="progress-text"><?php echo $total_questions; ?> questions</span>
                </div>
            </div>
        </div>

        <div class="main-container">
            <div class="question-content">
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
                                        </div>

                                        <div class="question-text">
                                            <?php echo $question['question_text']; ?>
                                        </div>
                                    </div>

                                    <?php if ($question['question_type'] === 'programming'): ?>
                                        <div class="programming-question">
                                            <div class="editor-container">
                                                <pre class="code-preview"><?php echo htmlspecialchars($starterCode); ?></pre>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="choices-container">
                                            <?php
                                            $answers_query = "SELECT * FROM answers WHERE question_id = ? ORDER BY position, answer_id";
                                            $stmt = $conn->prepare($answers_query);
                                            $stmt->bind_param("i", $question['question_id']);
                                            $stmt->execute();
                                            $answers = $stmt->get_result();
                                            ?>

                                            <?php while ($answer = $answers->fetch_assoc()): ?>
                                                <div class="option-item">
                                                    <div class="option-marker"></div>
                                                    <div class="option-text">
                                                        <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                    </div>
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
                                        </div>
                                        <div class="test-cases-content">
                                            <?php
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
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-questions">
                            <h3>No questions available for this exam</h3>
                            <p>Please add some questions to preview the exam.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fixed Navigation Bar -->
        <div class="fixed-navigation">
            <div class="navigation-group">
                <button class="btn btn-outline" onclick="prevQuestion()" aria-label="Previous question">
                    <span class="material-symbols-rounded">arrow_back</span>
                </button>
                
                <button class="btn btn-primary" onclick="nextQuestion()" aria-label="Next question">
                    <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </div>
            
            <button type="button" onclick="window.location.href='quiz_editor.php?exam_id=<?php echo $exam_id; ?>'" class="btn btn-primary compact-btn">
                <span class="material-symbols-rounded">arrow_back</span>
                Back to Editor
            </button>
        </div>
    </div>

    <script>
        let currentQuestionIndex = 0;
        const totalQuestions = <?php echo $total_questions; ?>;

        function showQuestion(index) {
            // Hide all questions
            document.querySelectorAll('.question-container').forEach(container => {
                container.style.display = 'none';
            });
            
            // Show the selected question
            const questionContainer = document.getElementById(`question-${index + 1}`);
            if (questionContainer) {
                questionContainer.style.display = 'flex';
            }
        }

        function prevQuestion() {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                showQuestion(currentQuestionIndex);
            }
        }

        function nextQuestion() {
            if (currentQuestionIndex < totalQuestions - 1) {
                currentQuestionIndex++;
                showQuestion(currentQuestionIndex);
            }
        }

        // Initialize with first question
        showQuestion(0);
    </script>

    <style>
        .fixed-navigation {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }

        .navigation-group {
            display: flex;
            gap: 10px;
        }

        .compact-btn {
            padding: 8px 16px !important;
            font-size: 14px !important;
            min-width: auto !important;
            height: auto !important;
        }

        .option-marker {
            width: 20px;
            height: 20px;
            border: 2px solid #8e44ad;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 4px;
        }

        .option-text {
            flex: 1;
            padding: 4px 0;
        }

        .option-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: default;
            transition: all 0.2s ease;
        }

        .option-item:hover {
            background-color: #f8f9fa;
        }

        .code-preview {
            color: white;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow-x: auto;
            white-space: pre;
            margin: 10px 0;
        }
    </style>
</body>
</html> 