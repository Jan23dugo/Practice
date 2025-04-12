<?php
session_start();
require_once('config/config.php');

// Check if user is logged in as admin
//if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
//    header("Location: admin_login.php");
//    exit();
//}

if(isset($_POST['release_results'])) {
    $exam_id = $_POST['exam_id'];
    
    $stmt = $conn->prepare("UPDATE exam_assignments 
                           SET is_released = 1 
                           WHERE exam_id = ?");
    $stmt->bind_param("i", $exam_id);
    if($stmt->execute()) {
        $success_msg = "Results released successfully!";
    } else {
        $error_msg = "Error releasing results: " . $conn->error;
    }
}

// Fetch completed exams that haven't been released
$query = "SELECT DISTINCT e.exam_id, e.title, 
          COUNT(ea.student_id) as total_submissions
          FROM exams e
          JOIN exam_assignments ea ON e.exam_id = ea.exam_id
          WHERE ea.completion_status = 'completed'
          GROUP BY e.exam_id";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exam Results</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* Apply styles to the "Manage Exam Results" title */
        .manage-results-title {
            font-size: 22px;
            font-weight: 500;
            color: #75343A;
            text-align: left;
            padding: 10px 0;
            margin-bottom: 20px;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }

        /* Table Header */
        th {
            background: #75343A;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 16px;
            text-transform: uppercase;
        }

        /* Table Rows */
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eef0f3;
            color: #333;
            font-size: 16px;
        }

        /* Alternate Row Color */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Hover Effect */
        tbody tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s ease;
        }

        /* Release Button */
        .release-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-size: 16px;
        }

        .release-btn:hover {
            background: #45a049;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        /* View Answers Button */
        .view-answers-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-left: 8px;
            text-decoration: none;
            font-size: 16px;
        }

        .view-answers-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .view-answers-btn .material-symbols-rounded {
            font-size: 16px;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Container Layout */
        .container {
            display: flex;
            min-height: 100vh;
        }

        .main {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        /* Popup Styles */
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .popup.active {
            display: flex;
        }

        .popup-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: popupSlideIn 0.3s ease-out;
        }

        @keyframes popupSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .popup-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .popup-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.2rem;
        }

        .close-popup {
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .close-popup:hover {
            color: #333;
        }

        .popup-body {
            padding: 20px;
        }

        .popup-body p {
            margin: 0 0 15px 0;
            line-height: 1.5;
        }

        .warning-text {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .popup-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-cancel, .btn-confirm {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 16px;
        }

        .btn-cancel {
            background:rgb(245, 230, 230);
            color: #333;
        }

        .btn-cancel:hover {
            background:rgb(233, 212, 212);
        }

        .btn-confirm {
            background: #75343A;
            color: white;
        }

        .btn-confirm:hover {
            background: #5c2930;
        }


        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <div class="main">
            <h2 class="manage-results-title">
                <span class="material-symbols-rounded">grade</span>
                Manage Exam Results
            </h2>

            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Exam Title</th>
                        <th>Total Submissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo $row['total_submissions']; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" id="releaseForm<?php echo $row['exam_id']; ?>">
                                        <input type="hidden" name="exam_id" value="<?php echo $row['exam_id']; ?>">
                                        <button type="button" class="release-btn" onclick="showReleasePopup(<?php echo $row['exam_id']; ?>, '<?php echo htmlspecialchars($row['title']); ?>')">
                                            Release Results
                                        </button>
                                    </form>
                                    
                                    <a href="view_student_answers.php?exam_id=<?php echo $row['exam_id']; ?>" 
                                       class="view-answers-btn">
                                        <span class="material-symbols-rounded">visibility</span>
                                        View Student Answers
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Release Results Popup -->
    <div id="releasePopup" class="popup">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Release Exam Results</h3>
                <button class="close-popup" onclick="closeReleasePopup()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="popup-body">
                <p>Are you sure you want to release the results for <strong id="examTitle"></strong>?</p>
                <p class="warning-text">This action cannot be undone. Students will be able to view their results immediately.</p>
            </div>
            <div class="popup-footer">
                <button class="btn-cancel" onclick="closeReleasePopup()">Cancel</button>
                <button class="btn-confirm" onclick="confirmRelease()">Release Results</button>
            </div>
        </div>
    </div>

    <script src="assets/js/side.js"></script>
    <script>
        let currentExamId = null;

        function showReleasePopup(examId, examTitle) {
            currentExamId = examId;
            document.getElementById('examTitle').textContent = examTitle;
            document.getElementById('releasePopup').classList.add('active');
        }

        function closeReleasePopup() {
            document.getElementById('releasePopup').classList.remove('active');
            currentExamId = null;
        }

        function confirmRelease() {
            if (currentExamId) {
                document.getElementById('releaseForm' + currentExamId).submit();
            }
        }

        // Close popup when clicking outside
        document.getElementById('releasePopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReleasePopup();
            }
        });
    </script>
</body>
</html>