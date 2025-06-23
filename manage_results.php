    <?php
    session_start();
    require_once('config/config.php');

<<<<<<< Updated upstream
    // Check if user is logged in as admin
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header("Location: admin_login.php");
        exit();
    }
=======
// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if(isset($_POST['release_results'])) {
    // Initialize logging
    $log_file = 'logs/result_release_' . date('Y-m-d_H-i-s') . '.log';
    $log_data = [];
    
    $exam_id = $_POST['exam_id'];
    $passed_message = $_POST['passed_message'];
    $passed_next_steps = $_POST['passed_next_steps'];
    $failed_message = $_POST['failed_message'];
    $failed_next_steps = $_POST['failed_next_steps'];
    
    // Log initial data
    $log_data[] = "=== RESULT RELEASE STARTED ===";
    $log_data[] = "Timestamp: " . date('Y-m-d H:i:s');
    $log_data[] = "Exam ID: " . $exam_id;
    $log_data[] = "Passed Message Length: " . strlen($passed_message);
    $log_data[] = "Passed Next Steps Length: " . strlen($passed_next_steps);
    $log_data[] = "Failed Message Length: " . strlen($failed_message);
    $log_data[] = "Failed Next Steps Length: " . strlen($failed_next_steps);
    $log_data[] = "";
    
    $success = true;
    $passed_count = 0;
    $failed_count = 0;
    $errors = [];
    
    // Update passed students
    $log_data[] = "=== UPDATING PASSED STUDENTS ===";
    $passed_query = "UPDATE exam_assignments 
                     SET is_released = 1, 
                         result_message = ?, 
                         next_steps = ?
                     WHERE exam_id = ? AND passed = 1 AND completion_status = 'completed'";
    $log_data[] = "Query: " . $passed_query;
    $log_data[] = "Parameters: [message_length=" . strlen($passed_message) . ", next_steps_length=" . strlen($passed_next_steps) . ", exam_id=" . $exam_id . "]";
    
    $stmt_passed = $conn->prepare($passed_query);
    $stmt_passed->bind_param("ssi", $passed_message, $passed_next_steps, $exam_id);
    
    if($stmt_passed->execute()) {
        $passed_count = $stmt_passed->affected_rows;
        $log_data[] = "SUCCESS: Updated " . $passed_count . " passed students";
    } else {
        $success = false;
        $error_msg = $stmt_passed->error;
        $errors[] = "Failed to update passed students: " . $error_msg;
        $log_data[] = "ERROR: " . $error_msg;
    }
    $log_data[] = "";
    
    // Update failed students (only if there are any)
    $log_data[] = "=== CHECKING FOR FAILED STUDENTS ===";
    $check_query = "SELECT COUNT(*) as count FROM exam_assignments WHERE exam_id = ? AND passed = 0 AND completion_status = 'completed'";
    $log_data[] = "Check Query: " . $check_query;
    $log_data[] = "Parameters: [exam_id=" . $exam_id . "]";
    
    $check_failed = $conn->prepare($check_query);
    $check_failed->bind_param("i", $exam_id);
    $check_failed->execute();
    $failed_exists = $check_failed->get_result()->fetch_assoc()['count'];
    $log_data[] = "Failed students found: " . $failed_exists;
    
    if($failed_exists > 0) {
        $log_data[] = "=== UPDATING FAILED STUDENTS ===";
        $failed_query = "UPDATE exam_assignments 
                         SET is_released = 1, 
                             result_message = ?, 
                             next_steps = ?
                         WHERE exam_id = ? AND passed = 0 AND completion_status = 'completed'";
        $log_data[] = "Query: " . $failed_query;
        $log_data[] = "Parameters: [message_length=" . strlen($failed_message) . ", next_steps_length=" . strlen($failed_next_steps) . ", exam_id=" . $exam_id . "]";
        
        $stmt_failed = $conn->prepare($failed_query);
        $stmt_failed->bind_param("ssi", $failed_message, $failed_next_steps, $exam_id);
        
        if($stmt_failed->execute()) {
            $failed_count = $stmt_failed->affected_rows;
            $log_data[] = "SUCCESS: Updated " . $failed_count . " failed students";
        } else {
            $success = false;
            $error_msg = $stmt_failed->error;
            $errors[] = "Failed to update failed students: " . $error_msg;
            $log_data[] = "ERROR: " . $error_msg;
        }
    } else {
        $log_data[] = "No failed students to update";
    }
    $log_data[] = "";
    
    if($success) {
        if($failed_exists > 0) {
            $success_msg = "Results released successfully! ($passed_count passed, $failed_count failed)";
        } else {
            $success_msg = "Results released successfully! ($passed_count passed, no failed students)";
        }
        $log_data[] = "=== RESULT RELEASE COMPLETED SUCCESSFULLY ===";
        $log_data[] = "Final Status: SUCCESS";
        $log_data[] = "Passed Students Updated: " . $passed_count;
        $log_data[] = "Failed Students Updated: " . $failed_count;
    } else {
        $error_msg = "Error releasing results: " . implode("; ", $errors);
        $log_data[] = "=== RESULT RELEASE FAILED ===";
        $log_data[] = "Final Status: FAILED";
        $log_data[] = "Errors: " . implode("; ", $errors);
    }
    
    // Write log to file
    $log_data[] = "";
    $log_data[] = "=== END LOG ===";
    
    // Ensure logs directory exists
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($log_file, implode("\n", $log_data));
    
    // Also add a quick debug query to check current state
    $debug_query = "SELECT assignment_id, student_id, passed, is_released, result_message, next_steps 
                    FROM exam_assignments 
                    WHERE exam_id = ? AND completion_status = 'completed'";
    $debug_stmt = $conn->prepare($debug_query);
    $debug_stmt->bind_param("i", $exam_id);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();
    
    $debug_data = [];
    $debug_data[] = "\n=== POST-RELEASE DATABASE STATE ===";
    while($row = $debug_result->fetch_assoc()) {
        $debug_data[] = "Assignment ID: " . $row['assignment_id'] . 
                       ", Student ID: " . $row['student_id'] . 
                       ", Passed: " . $row['passed'] . 
                       ", Released: " . $row['is_released'] . 
                       ", Message Length: " . strlen($row['result_message']) . 
                       ", Next Steps Length: " . strlen($row['next_steps']);
    }
    
    file_put_contents($log_file, implode("\n", $debug_data), FILE_APPEND);
}
>>>>>>> Stashed changes

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

<<<<<<< Updated upstream
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
                font-size: 36px;
                font-weight: 7 00;
                color: #75343A;
                text-align: left;
                margin-bottom: 20px;
=======
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
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: popupSlideIn 0.3s ease-out;
        }

        @keyframes popupSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
>>>>>>> Stashed changes
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
                    MANAGE EXAM RESULTS
                </h2>

                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>

                <?php if(isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>

<<<<<<< Updated upstream
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
=======
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
                                        <input type="hidden" name="release_results" value="1">
                                        <input type="hidden" name="exam_id" value="<?php echo $row['exam_id']; ?>">
                                        <input type="hidden" name="passed_message" id="passedMessage<?php echo $row['exam_id']; ?>">
                                        <input type="hidden" name="passed_next_steps" id="passedNextSteps<?php echo $row['exam_id']; ?>">
                                        <input type="hidden" name="failed_message" id="failedMessage<?php echo $row['exam_id']; ?>">
                                        <input type="hidden" name="failed_next_steps" id="failedNextSteps<?php echo $row['exam_id']; ?>">
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
                <p>Release results for <strong id="examTitle"></strong></p>
                
                <!-- Passed Students Section -->
                <div style="margin: 20px 0; padding: 15px; background: #e8f5e9; border-radius: 8px; border-left: 4px solid #4caf50;">
                    <h4 style="margin: 0 0 15px 0; color: #2e7d32; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-rounded" style="font-size: 20px;">check_circle</span>
                        Messages for PASSED Students
                    </h4>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="passedResultMessage" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Success Message:</label>
                        <textarea id="passedResultMessage" 
                                  placeholder="e.g., Congratulations! You have successfully passed the PUP CCIS qualifying exam. Your performance demonstrates readiness for the program." 
                                  style="width: 100%; min-height: 70px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical; box-sizing: border-box;"
                                  maxlength="500"></textarea>
                    </div>
                    
                    <div>
                        <label for="passedNextSteps" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Next Steps for Enrollment:</label>
                        <textarea id="passedNextSteps" 
                                  placeholder="e.g., Please proceed to the Registrar's Office within 5 working days with your documents for enrollment. Contact registrar@pup.edu.ph for questions." 
                                  style="width: 100%; min-height: 70px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical; box-sizing: border-box;"
                                  maxlength="500"></textarea>
                    </div>
                </div>
                
                <!-- Failed Students Section -->
                <div style="margin: 20px 0; padding: 15px; background: #ffebee; border-radius: 8px; border-left: 4px solid #f44336;">
                    <h4 style="margin: 0 0 15px 0; color: #c62828; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-rounded" style="font-size: 20px;">cancel</span>
                        Message for FAILED Students
                    </h4>
                    
                    <div>
                        <label for="failedResultMessage" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Result Message:</label>
                        <textarea id="failedResultMessage" 
                                  placeholder="e.g., Unfortunately, you did not meet the passing requirements for the qualifying exam. We appreciate your interest in the program." 
                                  style="width: 100%; min-height: 70px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical; box-sizing: border-box;"
                                  maxlength="500"></textarea>
                        <small style="color: #666; font-size: 11px; display: block; margin-top: 5px;">Note: This exam does not offer retakes. Focus on providing closure and alternative guidance.</small>
                    </div>
                </div>
                
                <small style="color: #666; font-size: 12px; display: block; margin-bottom: 10px;">Maximum 500 characters per field</small>
                <p class="warning-text">This action cannot be undone. Students will see personalized messages based on their results.</p>
            </div>
            <div class="popup-footer">
                <button class="btn-cancel" onclick="closeReleasePopup()">Cancel</button>
                <button class="btn-confirm" onclick="confirmRelease()">Release Results</button>
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
            function showReleasePopup(examId, examTitle) {
                currentExamId = examId;
                document.getElementById('examTitle').textContent = examTitle;
                document.getElementById('releasePopup').classList.add('active');
=======
        function closeReleasePopup() {
            document.getElementById('releasePopup').classList.remove('active');
            currentExamId = null;
        }

        function confirmRelease() {
            if (currentExamId) {
                const passedMessage = document.getElementById('passedResultMessage').value.trim();
                const passedNextSteps = document.getElementById('passedNextSteps').value.trim();
                const failedMessage = document.getElementById('failedResultMessage').value.trim();
                
                // Validation
                if (!passedMessage) {
                    alert('Please enter a success message for students who passed.');
                    return;
                }
                
                if (!failedMessage) {
                    alert('Please enter a message for students who failed.');
                    return;
                }
                
                // Set the hidden form values
                document.getElementById('passedMessage' + currentExamId).value = passedMessage;
                document.getElementById('passedNextSteps' + currentExamId).value = passedNextSteps;
                document.getElementById('failedMessage' + currentExamId).value = failedMessage;
                document.getElementById('failedNextSteps' + currentExamId).value = ''; // Empty for failed students
                
                // Debug: Log form data before submission
                console.log('Submitting form with data:', {
                    examId: currentExamId,
                    passedMessage: passedMessage,
                    passedNextSteps: passedNextSteps,
                    failedMessage: failedMessage
                });
                
                // Submit the form
                document.getElementById('releaseForm' + currentExamId).submit();
>>>>>>> Stashed changes
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