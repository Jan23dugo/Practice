<?php
session_start();

// Clear any old session messages at the start
if (isset($_SESSION['success']) && isset($_SESSION['ocr_error'])) {
    // If both exist, prioritize showing the error
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration Successful</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #75343A;
            --primary-dark: #5a2930;
            --primary-light: #9e4a52;
            --secondary: #f8f0e3;
            --accent: #d4af37;
            --text-dark: #333333;
            --text-light: #ffffff;
            --gray-light: #f5f5f5;
            --gray: #e0e0e0;
            --success: #4CAF50;
            --warning: #FF9800;
            --danger: #F44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-dark);
            background-color: var(--gray-light);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .success-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .confirm-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }

        .success-message {
            text-align: center;
            margin-bottom: 30px;
        }

        .success-message h2 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .success-message p {
            color: var(--text-dark);
            opacity: 0.8;
            font-size: 16px;
        }

        .eligibility-status {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .eligible {
            background-color: var(--gray-light);
            border-left: 4px solid var(--success);
        }

        .not-eligible {
            background-color: var(--gray-light);
            border-left: 4px solid var(--warning);
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--text-light);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--gray-light);
            color: var(--primary);
        }

        .btn-secondary:hover {
            background-color: var(--gray);
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
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            max-width: 800px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-dark);
            opacity: 0.7;
        }

        .close:hover {
            opacity: 1;
        }

        /* Table Styles */
        .credited-subjects-table {
            margin: 20px 0;
            width: 100%;
            overflow-x: auto;
        }

        .credited-subjects-table table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        .credited-subjects-table th,
        .credited-subjects-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }

        .credited-subjects-table th {
            background-color: var(--primary);
            color: var(--text-light);
            font-weight: 500;
        }

        .credited-subjects-table tr:hover {
            background-color: var(--gray-light);
        }

        .email-error {
            background-color: var(--gray-light);
            border-left: 4px solid var(--danger);
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .success-container {
                padding: 0 15px;
                margin: 20px auto;
        }

        .modal-content {
                margin: 20px;
                padding: 20px;
            }

            .btn {
                display: block;
                width: 100%;
                margin-bottom: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header (matching dashboard style) -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="img/Logo.png" alt="PUP Logo">
                    <div class="logo-text">
                        <h1>PUP Qualifying Exam Portal</h1>
                        <p>Registration Status</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="main-wrapper">
    <div class="success-container">
        <?php if (isset($_SESSION['email_error'])): ?>
            <div class="email-error">
                    <span class="material-symbols-rounded">error</span>
                <p><?php echo $_SESSION['email_error']; ?></p>
            </div>
            <?php unset($_SESSION['email_error']); ?>
        <?php endif; ?>
        
            <!-- Registration Status Card -->
            <div class="confirm-container">
            <div class="success-message">
                <h2>Registration Status</h2>
                <?php
                if (isset($_SESSION['ocr_error'])) {
                    echo "<div class='eligibility-status not-eligible'>";
                        echo "<span class='material-symbols-rounded status-icon'>error</span>";
                        echo "<h3>Document Verification Error</h3>";
                    echo "<p>" . htmlspecialchars($_SESSION['ocr_error']) . "</p>";
                        echo "<div class='requirements-list'>";
                    echo "<p>Please ensure you have uploaded:</p>";
                    echo "<ul>";
                        echo "<li><span class='material-symbols-rounded'>description</span>A valid Transcript of Records (TOR)</li>";
                        echo "<li><span class='material-symbols-rounded'>high_quality</span>A clear, readable copy of the document</li>";
                        echo "<li><span class='material-symbols-rounded'>grade</span>The document contains your grades and subject information</li>";
                        echo "<li><span class='material-symbols-rounded'>image</span>The image is not blurry or distorted</li>";
                    echo "</ul>";
                    echo "</div>";
                        echo "</div>";
                        echo "<div class='action-buttons'>";
                        echo "<a href='registerFront.php' class='btn btn-primary'><span class='material-symbols-rounded'>refresh</span>Try Again</a>";
                        echo "<a href='index.php' class='btn btn-secondary'><span class='material-symbols-rounded'>home</span>Back to Home</a>";
                    echo "</div>";
                    unset($_SESSION['ocr_error']);
                } elseif (isset($_SESSION['is_eligible'])) {
                    if ($_SESSION['is_eligible']) {
                        echo "<div class='eligibility-status eligible'>";
                            echo "<span class='material-symbols-rounded status-icon'>check_circle</span>";
                            echo "<h3>Registration Successful!</h3>";
                        echo "<p>Congratulations! Based on your grades you are qualified to take the Qualifying Exam</p>";
                        if (isset($_SESSION['success'])) {
                                echo "<p class='success-details'>" . htmlspecialchars($_SESSION['success']) . "</p>";
                        }
                        echo "</div>";
                            echo "<div class='action-buttons'>";
                            echo "<button class='btn btn-primary' onclick='showCreditedSubjectsModal()'>";
                            echo "<span class='material-symbols-rounded'>list_alt</span>View Credited Subjects";
                            echo "</button>";
                            echo "<a href='stud_dashboard.php' class='btn btn-secondary'>";
                            echo "<span class='material-symbols-rounded'>dashboard</span>Go to Dashboard";
                            echo "</a>";
                            echo "</div>";
                    } else {
                        echo "<div class='eligibility-status not-eligible'>";
                            echo "<span class='material-symbols-rounded status-icon'>info</span>";
                            echo "<h3>Registration Completed</h3>";
                        if (isset($_SESSION['eligibility_message'])) {
                            echo "<p>" . htmlspecialchars($_SESSION['eligibility_message']) . "</p>";
                        }
                        echo "</div>";
                            echo "<div class='action-buttons'>";
                            echo "<a href='stud_dashboard.php' class='btn btn-primary'>";
                            echo "<span class='material-symbols-rounded'>dashboard</span>Go to Dashboard";
                            echo "</a>";
                            echo "</div>";
                    }
                    unset($_SESSION['is_eligible']);
                    unset($_SESSION['success']);
                    unset($_SESSION['eligibility_message']);
                }
                ?>
            </div>
        </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PUP Qualifying Exam Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- Add these additional styles -->
    <style>
        header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo img {
            height: 50px;
            width: auto;
        }

        .main-wrapper {
            flex: 1;
            padding: 20px 0;
        }

        .requirements-list {
            text-align: left;
            margin: 20px 0;
            background: var(--gray-light);
            padding: 20px;
            border-radius: 8px;
        }

        .requirements-list ul {
            list-style: none;
            margin-top: 10px;
        }

        .requirements-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .status-icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .success-details {
            margin-top: 15px;
            padding: 15px;
            background: var(--gray-light);
            border-radius: 6px;
        }

        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            text-align: center;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .logo-text h1 {
                font-size: 18px;
            }
        }
    </style>
</body>
</html>
