<?php
session_start();

// Initialize variables
$hasError = isset($_SESSION['ocr_error']);
$hasSuccess = isset($_SESSION['success']) && !empty($_SESSION['success']);
$errorMessage = $hasError ? $_SESSION['ocr_error'] : '';
$successMessage = $hasSuccess ? $_SESSION['success'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration Status</title>
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

        /* Add these new styles */
        .error-container {
            background-color: #fff3f3;
            border-left: 4px solid var(--danger);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .error-title {
            display: flex;
            align-items: center;
            color: var(--danger);
            margin-bottom: 15px;
        }

        .error-title .material-symbols-rounded {
            margin-right: 10px;
        }

        .error-message {
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .error-checklist {
            list-style: none;
            padding: 0;
        }

        .error-checklist li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            padding-left: 30px;
            position: relative;
        }

        .error-checklist li:before {
            content: "check_circle";
            font-family: 'Material Symbols Rounded';
            position: absolute;
            left: 0;
            color: var(--text-dark);
            opacity: 0.7;
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
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
                <?php if ($hasError): ?>
                    <div class="error-container">
                        <div class="error-title">
                            <span class="material-symbols-rounded">error</span>
                            <h3>Document Verification Error</h3>
                        </div>
                        <div class="error-message">
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                        <ul class="error-checklist">
                            <li>A valid Transcript of Records (TOR)</li>
                            <li>A clear, readable copy of the document</li>
                            <li>The document contains your grades and subject information</li>
                            <li>The image is not blurry or distorted</li>
                        </ul>
                        <div class="action-buttons">
                            <a href="registration.php" class="btn btn-primary">Try Again</a>
                            <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        </div>
                    </div>
                <?php elseif ($hasSuccess): ?>
                    <div class="success-container">
                        <?php if (isset($_SESSION['is_eligible']) && $_SESSION['is_eligible']): ?>
                            <div class="status-icon">
                                <span class="material-symbols-rounded" style="color: var(--success);">check_circle</span>
                            </div>
                            <h3 style="color: var(--success); margin-bottom: 15px;">Congratulations!</h3>
                            <p>Based on our evaluation of your academic records, you are qualified to take the PUP Qualifying Examination.</p>
                            <div class="success-details">
                                <p><strong>Next Steps:</strong></p>
                                <ul style="list-style: none; padding-left: 0; margin-top: 10px;">
                                    <li style="margin-bottom: 8px;">✓ Your application will be reviewed by our admin team</li>
                                    <li style="margin-bottom: 8px;">✓ Please allow 2-3 business days for verification</li>
                                    <li style="margin-bottom: 8px;">✓ You can check your application status on the Exam Registration Status page</li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="status-icon">
                                <span class="material-symbols-rounded" style="color: var(--warning);">info</span>
                            </div>
                            <h3 style="color: var(--warning); margin-bottom: 15px;">We regret to inform you</h3>
                            <p>Based on our evaluation of your academic records, you do not meet the qualifying criteria for the PUP Qualifying Examination at this time.</p>
                            <div class="success-details">
                                <p><strong>Reason:</strong></p>
                                <p>Your grades do not meet the minimum requirements for credit transfer eligibility.</p>
                                <p style="margin-top: 15px;"><strong>What you can do:</strong></p>
                                <ul style="list-style: none; padding-left: 0; margin-top: 10px;">
                                    <li style="margin-bottom: 8px;">• Review our eligibility requirements</li>
                                    <li style="margin-bottom: 8px;">• Consider regular admission options</li>
                                    <li style="margin-bottom: 8px;">• Contact our admissions office for guidance</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <div class="action-buttons">
                            <a href="exam_registration_status.php" class="btn btn-primary">Check Application Status</a>
                            <a href="stud_dashboard.php" class="btn btn-secondary">Back to Home</a>
                        </div>
                    </div>
                <?php endif; ?>
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
            text-align: center;
            margin-bottom: 20px;
        }

        .status-icon .material-symbols-rounded {
            font-size: 48px;
        }

        .success-details {
            margin-top: 20px;
            padding: 20px;
            background: var(--gray-light);
            border-radius: 8px;
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
            min-width: 200px;
            justify-content: center;
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

            .btn {
                width: 100%;
            }
        }
    </style>
</body>
</html>
<?php
// Clear session messages after displaying
unset($_SESSION['ocr_error']);
unset($_SESSION['success']);
unset($_SESSION['reference_id']);
unset($_SESSION['is_eligible']);
?>
