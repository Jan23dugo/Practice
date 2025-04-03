<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
require_once('config/config.php');

// Function to log messages to a file
function writeLog($message, $type = 'INFO') {
    $logFile = __DIR__ . '/logs/exam_submission.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$type] $message" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
    }
    
    // Write to log file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to redirect with a message
function redirectWithMessage($url, $message, $type = 'error') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit;
}

// Start logging
writeLog("=== Starting New Exam Submission ===");

// Check if user is logged in
if (!isset($_SESSION['stud_id'])) {
    writeLog("No active session", 'ERROR');
    redirectWithMessage('login.php', 'Your session has expired. Please log in again.');
}

// Decode the form data
if (!isset($_POST['all_answers']) || !isset($_POST['exam_id'])) {
    writeLog("Missing required form data", 'ERROR');
    redirectWithMessage('exams.php', 'Missing required data for exam submission.');
}

try {
    // Get the form data
    $exam_id = $_POST['exam_id'];
    $answers_json = $_POST['all_answers'];
    
    // Decode the answers
    $answers = json_decode($answers_json, true);
    
    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        writeLog("JSON decode error: " . json_last_error_msg(), 'ERROR');
        redirectWithMessage('exams.php', 'Invalid answer format. Please try again.');
    }
    
    // Log the submission data
    writeLog("Exam ID: $exam_id, Answer count: " . count($answers));
    
    // Get student ID
    $stud_id = $_SESSION['stud_id'];
    
    // Get student info
    $stmt = $conn->prepare("SELECT student_id FROM register_studentsqe WHERE stud_id = ?");
    if (!$stmt) {
        writeLog("Database error: " . $conn->error, 'ERROR');
        redirectWithMessage('exams.php', 'Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $stud_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        redirectWithMessage('exams.php', 'Student registration not found.');
    }
    
    $studentData = $result->fetch_assoc();
    $student_id = $studentData['student_id'];
    $stmt->close();
    
    writeLog("Student ID: $stud_id, Database ID: $student_id");
    
    // Start a transaction
    $conn->begin_transaction();
    
    try {
        // Update exam_assignments to mark as completed
        $stmt = $conn->prepare("UPDATE exam_assignments 
                               SET completion_status = 'completed',
                                   completion_time = NOW()
                               WHERE exam_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $exam_id, $student_id);
        $stmt->execute();
        $stmt->close();
        
        // Initialize score variables
        $totalScore = 0;
        $questionCount = 0;

        // Process each answer
        foreach ($answers as $question_id => $answer) {
            // Skip any invalid answers
            if (!isset($answer['question_type'])) {
                writeLog("Missing question_type for question $question_id", 'WARNING');
                continue;
            }
            
            writeLog("Processing answer for question $question_id, type: " . $answer['question_type']);
            
            // Clear any existing answers for this question
            $stmt = $conn->prepare("DELETE FROM student_answers 
                                   WHERE student_id = ? AND exam_id = ? AND question_id = ?");
            $stmt->bind_param("iii", $student_id, $exam_id, $question_id);
                            $stmt->execute();
            $stmt->close();
            
            // Insert the new answer
            if ($answer['question_type'] === 'programming') {
                // For programming questions
                if (!isset($answer['code']) || !isset($answer['programming_id'])) {
                    writeLog("Missing required fields for programming question $question_id", 'WARNING');
                        continue;
                    }
                
                // Get question points
                $stmt = $conn->prepare("SELECT points FROM questions WHERE question_id = ?");
                $stmt->bind_param("i", $question_id);
                    $stmt->execute();
                $result = $stmt->get_result();
                $questionData = $result->fetch_assoc();
                $points = $questionData['points'] ?? 1; // Default to 1 if not set
                $stmt->close();
                
                // Add points to total score (if you want to give full credit for all programming submissions)
                $totalScore += $points;
                $questionCount++;

                // Store the score in the database
                $stmt = $conn->prepare("INSERT INTO student_answers 
                        (student_id, exam_id, question_id, programming_answer, 
                     submission_time, question_type, programming_id, score) 
                    VALUES (?, ?, ?, ?, NOW(), 'programming', ?, ?)");
                    
                $stmt->bind_param("iiisid", 
                        $student_id, 
                    $exam_id, 
                        $question_id, 
                        $answer['code'],
                        $answer['programming_id'],
                    $points
                );
                
                $stmt->execute();
                $stmt->close();
            } else {
                // For multiple choice questions
                if (!isset($answer['answer_id'])) {
                    writeLog("Missing answer_id for question $question_id", 'WARNING');
                    continue;
                }
                
                // Get if the answer is correct
                $stmt = $conn->prepare("SELECT is_correct FROM answers WHERE answer_id = ?");
                $stmt->bind_param("i", $answer['answer_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $answerData = $result->fetch_assoc();
                $is_correct = $answerData['is_correct'] ?? 0;
                $stmt->close();
                
                // Get question points
                $stmt = $conn->prepare("SELECT points FROM questions WHERE question_id = ?");
                $stmt->bind_param("i", $question_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $questionData = $result->fetch_assoc();
                $points = $questionData['points'] ?? 1; // Default to 1 if not set
                $stmt->close();
                
                // Calculate score
                $score = $is_correct * $points;
                $totalScore += $score;
                $questionCount++;

                // Include score in the INSERT statement
                $stmt = $conn->prepare("INSERT INTO student_answers 
                    (student_id, exam_id, question_id, answer_id, 
                     submission_time, question_type, is_correct, score) 
                    VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
                    
                $stmt->bind_param("iiiisid", 
                    $student_id, 
                    $exam_id, 
                    $question_id, 
                    $answer['answer_id'], 
                    $answer['question_type'],
                    $is_correct,
                    $score
                );
                
                $stmt->execute();
                $stmt->close();
            }
        }

        // Get passing score and type
        $stmt = $conn->prepare("SELECT passing_score, passing_score_type FROM exams WHERE exam_id = ?");
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $examData = $result->fetch_assoc();
        $passingScore = $examData['passing_score'] ?? 50;
        $passingScoreType = $examData['passing_score_type'] ?? 'percentage';
        $stmt->close();

        // Get the maximum possible score
        $stmt = $conn->prepare("SELECT SUM(points) as max_score FROM questions WHERE exam_id = ?");
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $maxScoreData = $result->fetch_assoc();
        $maxPossibleScore = $maxScoreData['max_score'] ?? $questionCount; // Default to 1 per question
        $stmt->close();

        writeLog("Total score: $totalScore, Max possible: $maxPossibleScore, Question count: $questionCount");

        // Calculate final score based on passing_score_type
        if ($passingScoreType === 'percentage') {
            // Calculate correct percentage based on max possible points
            $finalScore = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;
            
            writeLog("Percentage calculation: ($totalScore / $maxPossibleScore) * 100 = $finalScore%");
            
            // Determine if passed based on percentage
            $passed = ($finalScore >= $passingScore) ? 1 : 0;
            writeLog("Passing check: $finalScore >= $passingScore ? " . ($passed ? "Yes" : "No"));
        } else {
            // Raw score
            $finalScore = $totalScore;
            // Determine if passed based on raw points
        $passed = ($finalScore >= $passingScore) ? 1 : 0;
            writeLog("Raw score: $finalScore points, Passing requires: $passingScore, Passed: " . ($passed ? "Yes" : "No"));
        }

        // Get total number of questions in the exam
        $stmt = $conn->prepare("SELECT COUNT(*) as total_questions FROM questions WHERE exam_id = ?");
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $examQuestionData = $result->fetch_assoc();
        $totalQuestions = $examQuestionData['total_questions'];
        $stmt->close();

        // Count how many questions were answered
        $answeredQuestions = count($answers);

        // Update exam_assignments with all values
        $stmt = $conn->prepare("UPDATE exam_assignments 
                               SET final_score = ?,
                                   passed = ?,
                                   answered_questions = ?,
                                   total_questions = ?
                               WHERE exam_id = ? AND student_id = ?");
        $stmt->bind_param("diiiii", $finalScore, $passed, $answeredQuestions, $totalQuestions, $exam_id, $student_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit the transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['message'] = "Your exam has been submitted successfully! Your score: " . round($finalScore, 2) . "%";
        $_SESSION['message_type'] = "success";
        
        // Redirect to completion page
        writeLog("Exam submitted successfully");
        header("Location: exam_complete.php");
        exit;

    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        writeLog("Error processing answers: " . $e->getMessage(), 'ERROR');
        redirectWithMessage('exams.php', 'Error submitting exam: ' . $e->getMessage());
    }

} catch (Exception $e) {
    writeLog("Error during submission: " . $e->getMessage(), 'ERROR');
    redirectWithMessage('exams.php', 'Error submitting exam: ' . $e->getMessage());
} finally {
    writeLog("=== End of Exam Submission ===");
}
?> 