<?php
session_start();
require_once('config/config.php');

// Check if user is logged in
if (!isset($_SESSION['stud_id'])) {
    header('Location: stud_register.php');
    exit;
}

$success_message = "Your exam has been submitted successfully! Your instructor will release the results once all exams have been reviewed.";
$error_message = "";

// Check if there's a message from the submission process
if (isset($_SESSION['message'])) {
    if ($_SESSION['message_type'] == 'error') {
        $error_message = $_SESSION['message'];
        $success_message = "";
    } else {
        $success_message = $_SESSION['message'];
    }
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get student info
$stud_id = $_SESSION['stud_id'];
$stmt = $conn->prepare("SELECT last_name, first_name, middle_name FROM register_studentsqe WHERE stud_id = ?");
$stmt->bind_param("i", $stud_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Format the student name (last name, first name middle name)
$student_name = $student ? 
    ($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']) : 
    "Student";
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Completed</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --error: #f44336;
            --gray: #e0e0e0;
            --gray-dark: #757575;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .completion-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 40px;
            text-align: center;
        }
        
        .completion-icon {
            font-size: 80px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .error-icon {
            color: var(--error);
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #333;
        }
        
        p {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="completion-container">
        <?php if (empty($error_message)): ?>
            <span class="material-symbols-rounded completion-icon">check_circle</span>
            <h1>Exam Submitted Successfully</h1>
            <p><?php echo $success_message; ?></p>
            <p>Thank you, <?php echo htmlspecialchars($student_name); ?>, for completing your exam.</p>
            <p>The results will be available on your dashboard once they are released by your instructor.</p>
        <?php else: ?>
            <span class="material-symbols-rounded completion-icon error-icon">error</span>
            <h1>Error Submitting Exam</h1>
            <p><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <a href="stud_dashboard.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html> 