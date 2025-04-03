<?php
// Add these at the very top of the file
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include('config/config.php');


// Check if student is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: stud_register.php");
    exit();
}

$stud_id = $_SESSION['stud_id'];

// First get the student's email from students table
$student_query = "SELECT email FROM students WHERE stud_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $stud_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: stud_dashboard.php?error=invalid_student");
    exit();
}

// Check if student has an accepted registration
$reg_query = "SELECT student_id FROM register_studentsqe 
              WHERE email = ? AND status = 'accepted'";
$stmt = $conn->prepare($reg_query);
$stmt->bind_param("s", $student['email']);
$stmt->execute();
$registration = $stmt->get_result()->fetch_assoc();

if (!$registration) {
    header("Location: stud_dashboard.php?error=not_registered");
    exit();
}

// Now get their assigned exam
$exam_query = "SELECT ea.*, e.title, e.description, e.duration, e.passing_score, 
                      e.exam_type, e.scheduled_date, e.scheduled_time,
                      e.is_scheduled, e.passing_score_type, e.randomize_questions, 
                      e.randomize_choices, rs.student_id as registered_student_id
               FROM exam_assignments ea
               JOIN exams e ON ea.exam_id = e.exam_id
               JOIN register_studentsqe rs ON ea.student_id = rs.student_id
               WHERE rs.stud_id = ? 
               AND ea.completion_status = 'pending'";

$stmt = $conn->prepare($exam_query);
$stmt->bind_param("i", $stud_id);
$stmt->execute();
$assigned_exam = $stmt->get_result()->fetch_assoc();

// For debugging
if (!$assigned_exam) {
    // Log the values we're using
    error_log("No exam found for email: " . $student['email']);
    error_log("Student ID from session: " . $stud_id);
    header("Location: stud_dashboard.php?error=no_exam_assigned");
    exit();
}

// Store the registration ID in session for use in take_exam.php
$_SESSION['registered_student_id'] = $registration['student_id'];

// Define current_datetime
$current_datetime = date('Y-m-d H:i:s');

// Create exam_datetime from scheduled date and time
if (isset($assigned_exam['scheduled_date']) && isset($assigned_exam['scheduled_time'])) {
    $exam_datetime = $assigned_exam['scheduled_date'] . ' ' . $assigned_exam['scheduled_time'];
} else {
    $exam_datetime = null;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Instructions - <?php echo htmlspecialchars($assigned_exam['title'] ?? 'Exam'); ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        :root {
            --primary-color: #8e68cc;
            --primary-dark: #7d5bb9;
            --surface-color: #ffffff;
            --background-color: #f8f9fa;
            --text-primary: #333333;
            --text-secondary: #666666;
            --border-color: #e0e0e0;
            --maroon-color: #702439;
            --maroon-dark: #5a1c2e;
            --maroon-light: #8c2e47;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .instructions-wrapper {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--surface-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .exam-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .exam-header h1 {
            font-size: 2.5rem;
            color: var(--maroon-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .exam-header p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
        }

        .exam-details {
            background: var(--background-color);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--text-primary);
            padding: 0.5rem;
            border-radius: 4px;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-item .material-symbols-rounded {
            margin-right: 1rem;
            color: var(--maroon-color);
            font-size: 24px;
        }

        .rules-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--surface-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .rules-section h2 {
            color: var(--maroon-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .rules-list {
            list-style: none;
            padding: 0;
        }

        .rules-list li {
            margin-bottom: 1rem;
            padding-left: 2rem;
            position: relative;
            color: var(--text-primary);
        }

        .rules-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--maroon-color);
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .btn-proceed {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            background: var(--maroon-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-proceed:hover {
            background: var(--maroon-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-back {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            background: var(--surface-color);
            color: var(--maroon-color);
            border: 2px solid var(--maroon-color);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-back:hover {
            background: #f8f4ff;
            color: var(--maroon-dark);
            border-color: var(--maroon-dark);
        }

        .scheduled-info {
            background-color: #fff8e1;
            border: 1px solid #ffe082;
            color: #856404;
            padding: 1.2rem;
            border-radius: 8px;
            margin: 1.5rem auto;
            display: flex;
            align-items: center;
            gap: 1rem;
            max-width: 600px;
        }

        .scheduled-info .material-symbols-rounded {
            font-size: 24px;
            color: #f57c00;
        }

        .exam-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .status-scheduled {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .status-ongoing {
            background-color: #e8f5e9;
            color: #1b5e20;
        }

        .time-remaining {
            color: var(--maroon-color);
            font-weight: 500;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .instructions-wrapper {
                margin: 1rem;
                padding: 1.5rem;
            }

            .exam-header h1 {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-proceed, .btn-back {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="instructions-wrapper">
        <div class="exam-section">
            <div class="exam-header">
                <h1><?php echo htmlspecialchars($assigned_exam['title']); ?></h1>
                <p><?php echo htmlspecialchars($assigned_exam['description'] ?? 'No description available.'); ?></p>
                
                <?php if (isset($assigned_exam['is_scheduled']) && $assigned_exam['is_scheduled'] == 1): ?>
                <div class="scheduled-info">
                    <span class="material-symbols-rounded">event</span>
                    <div>
                        <strong>Scheduled for:</strong> 
                        <?php 
                            $scheduled_datetime = new DateTime($assigned_exam['scheduled_date'] . ' ' . $assigned_exam['scheduled_time']);
                            echo $scheduled_datetime->format('F j, Y g:i A'); 
                        ?>
                        <span class="exam-status <?php echo $current_datetime >= $exam_datetime ? 'status-ongoing' : 'status-scheduled'; ?>">
                            <?php echo $current_datetime >= $exam_datetime ? 'Ongoing' : 'Scheduled'; ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="exam-details">
                <div class="detail-item">
                    <span class="material-symbols-rounded">timer</span>
                    <span>Duration: <?php echo htmlspecialchars($assigned_exam['duration'] ?? '0'); ?> minutes</span>
                </div>
                <?php if (isset($assigned_exam['passing_score']) && $assigned_exam['passing_score']): ?>
                <div class="detail-item">
                    <span class="material-symbols-rounded">grade</span>
                    <span>Passing Score: <?php echo $assigned_exam['passing_score']; ?>
                        <?php echo isset($assigned_exam['passing_score_type']) && $assigned_exam['passing_score_type'] === 'percentage' ? '%' : ' points'; ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="detail-item">
                    <span class="material-symbols-rounded">quiz</span>
                    <span>Exam Type: <?php echo ucfirst($assigned_exam['exam_type']); ?></span>
                </div>
                <?php if (isset($assigned_exam['randomize_questions']) && $assigned_exam['randomize_questions']): ?>
                <div class="detail-item">
                    <span class="material-symbols-rounded">shuffle</span>
                    <span>Questions will be randomized</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="rules-section">
                <h2>Exam Rules and Guidelines</h2>
                <ul class="rules-list">
                    <li>Once you start the exam, the timer will begin and cannot be paused.</li>
                    <li>Ensure you have a stable internet connection before starting the exam.</li>
                    <li>Do not refresh the page or close the browser window during the exam.</li>
                    <li>You must complete all questions within the allocated time of <?php echo $assigned_exam['duration']; ?> minutes.</li>
                    <?php if (isset($assigned_exam['randomize_questions']) && $assigned_exam['randomize_questions']): ?>
                    <li>Questions will appear in random order for each student.</li>
                    <?php endif; ?>
                    <?php if (isset($assigned_exam['randomize_choices']) && $assigned_exam['randomize_choices']): ?>
                    <li>For multiple choice questions, answer choices will be randomized.</li>
                    <?php endif; ?>
                    <li>You can flag questions for review and return to them later.</li>
                    <li>Once you submit the exam, you cannot return to modify your answers.</li>
                    <li>Any form of cheating or malpractice will result in disqualification.</li>
                </ul>
            </div>

            <div class="action-buttons">
                <button class="btn-back" onclick="window.location.href='stud_dashboard.php'">
                    <span class="material-symbols-rounded">arrow_back</span>
                    Back to Dashboard
                </button>
                
                <button class="btn-proceed" onclick="return proceedToExam(<?php echo htmlspecialchars($assigned_exam['exam_id']); ?>)">
                    Proceed to Exam
                    <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function proceedToExam(examId) {
            if (confirm('Are you ready to start the exam? The timer will begin immediately.')) {
                console.log("Proceeding to exam with ID: " + examId);
                <?php $_SESSION['current_exam_id'] = $assigned_exam['exam_id']; ?>
                window.location.href = 'take_exam.php?exam_id=' + examId;
                return false;
            }
            return false;
        }
    </script>
</body>
</html> 