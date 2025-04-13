<?php
// Include database connection and PHPMailer
include 'config/config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

session_start();

$message = '';
$messageType = '';
$showCodeForm = false;
$showPasswordForm = false;
$showConfirmation = false;
$studentInfo = null;

if (isset($_POST['find_account'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists
    $query = "SELECT firstname, lastname, email FROM students WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $studentInfo = $result->fetch_assoc();
        $showConfirmation = true;
        $_SESSION['reset_email'] = $email;
    } else {
        $message = "No account found with that email address.";
        $messageType = "danger";
    }
}

if (isset($_POST['send_code'])) {
    $email = $_SESSION['reset_email'] ?? '';
    
    if (empty($email)) {
        $message = "Session expired. Please start over.";
        $messageType = "danger";
    } else {
        // Generate 6-digit code
        $reset_code = sprintf("%06d", mt_rand(1, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store code in database
        $updateQuery = "UPDATE students SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sss", $reset_code, $expiry, $email);
        
        if ($stmt->execute()) {
            // Initialize PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ccisqualifyingexam@gmail.com';
                $mail->Password = 'zbjv xljt nwyz gqdk';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // Recipients
                $mail->setFrom('ccisqualifyingexam@gmail.com', 'PUP CCIS Exam');
                $mail->addAddress($email);
                
                // Email Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Code';
                $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
                        .header { background-color: #800000; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; }
                        .code { font-size: 32px; font-weight: bold; text-align: center; padding: 20px; background: #f5f5f5; margin: 20px 0; letter-spacing: 5px; }
                        .footer { background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Password Reset Code</h2>
                        </div>
                        <div class='content'>
                            <p>Hello,</p>
                            <p>You have requested to reset your password. Here is your password reset code:</p>
                            <div class='code'>$reset_code</div>
                            <p>This code will expire in 1 hour.</p>
                            <p>If you didn't request this, please ignore this email.</p>
                            <p>Best regards,<br>PUP CCIS Faculty</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
                </html>";

                $mail->AltBody = "Your password reset code is: $reset_code\n\nThis code will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nPUP CCIS Faculty";
                
                // Send email
                $mail->send();
                $_SESSION['reset_code'] = $reset_code; // Store code in session for debugging
                $message = "Reset code has been sent to your email.";
                $messageType = "success";
                $showCodeForm = true;
                $showConfirmation = false;
            } catch (Exception $e) {
                $message = "Error sending email. Please try again later.";
                $messageType = "danger";
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }
        } else {
            $message = "Error processing request. Please try again later.";
            $messageType = "danger";
            error_log("Database Error: " . $stmt->error);
        }
    }
}

if (isset($_POST['verify_code'])) {
    $code = mysqli_real_escape_string($conn, $_POST['reset_code']);
    $email = $_SESSION['reset_email'] ?? '';
    
    if (empty($email)) {
        $message = "Session expired. Please try again.";
        $messageType = "danger";
    } else {
        // Debug logging
        error_log("Verifying code for email: " . $email);
        error_log("Submitted code: " . $code);
        error_log("Session code: " . ($_SESSION['reset_code'] ?? 'not set'));
        
        // First, check if the email exists and has a valid reset token
        $query = "SELECT reset_token, reset_token_expiry FROM students WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            error_log("Database token: " . $row['reset_token']);
            error_log("Database expiry: " . $row['reset_token_expiry']);
            error_log("Current time: " . date('Y-m-d H:i:s'));
            
            if ($row['reset_token'] === $code && strtotime($row['reset_token_expiry']) > time()) {
                $showPasswordForm = true;
                $showCodeForm = false;
                $_SESSION['code_verified'] = true;
            } else {
                if ($row['reset_token'] !== $code) {
                    $message = "Invalid reset code. Please check and try again.";
                } else {
                    $message = "Reset code has expired. Please request a new code.";
                }
                $messageType = "danger";
                $showCodeForm = true;
            }
        } else {
            $message = "Error verifying code. Please try again.";
            $messageType = "danger";
            $showCodeForm = true;
        }
    }
}

if (isset($_POST['update_password'])) {
    $email = $_SESSION['reset_email'] ?? '';
    $code_verified = $_SESSION['code_verified'] ?? false;
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    if (empty($email) || !$code_verified) {
        $message = "Session expired or invalid access. Please restart the password reset process.";
        $messageType = "danger";
        $showPasswordForm = false;
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "danger";
        $showPasswordForm = true;
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "danger";
        $showPasswordForm = true;
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $message = "Password must contain at least one uppercase letter.";
        $messageType = "danger";
        $showPasswordForm = true;
    } elseif (!preg_match('/[0-9]/', $password)) {
        $message = "Password must contain at least one number.";
        $messageType = "danger";
        $showPasswordForm = true;
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $message = "Password must contain at least one special character.";
        $messageType = "danger";
        $showPasswordForm = true;
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE students SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE email = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            $message = "Password has been successfully updated. You can now login with your new password.";
            $messageType = "success";
            // Clear all reset-related session variables
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_code']);
            unset($_SESSION['code_verified']);
        } else {
            $message = "Error updating password. Please try again.";
            $messageType = "danger";
            $showPasswordForm = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PUP Qualifying Exam Portal</title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }
        
        header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo img {
            height: 60px;
            width: auto;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .logo-text p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .auth-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 0;
        }
        
        .auth-container {
            width: 100%;
            max-width: 450px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 0 auto;
        }
        
        .auth-header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 20px;
            text-align: center;
        }
        
        .auth-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .auth-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .auth-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 2px solid var(--gray);
            border-radius: 8px;
            transition: all 0.3s ease;
            background-color: var(--gray-light);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px 20px;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--text-light);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(117, 52, 58, 0.2);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .back-button {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-light);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        
        .back-button:hover {
            background-color: var(--primary-dark);
        }
        
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 20px 0;
            text-align: center;
            font-size: 14px;
            margin-top: auto;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .auth-container {
                max-width: 95%;
                margin: 20px auto;
            }
            
            .auth-body {
                padding: 20px;
            }
        }
        
        .code-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .code-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            border: 2px solid var(--gray);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .code-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
        }
        
        .password-requirements {
            background-color: var(--gray-light);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 13px;
        }
        
        .password-requirements ul {
            list-style: none;
            margin: 10px 0 0;
            padding: 0;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
            color: #666;
        }
        
        .account-info {
            text-align: center;
        }
        
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background-color: var(--gray);
            color: var(--text-dark);
            text-decoration: none;
            text-align: center;
        }
        
        .btn-secondary:hover {
            background-color: var(--gray-light);
        }
        
        .text-center {
            text-align: center;
            margin: 15px 0;
        }
        
        .alert-info {
            background-color: #e1f5fe;
            color: #014361;
            border: 1px solid #b3e5fc;
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <a href="stud_register.php" class="back-button">
                        <span class="material-symbols-rounded">arrow_back</span>
                        Back to Login
                    </a>
                    <div class="logo">
                        <img src="img/Logo.png" alt="PUP Logo">
                        <div class="logo-text">
                            <h1>PUP Qualifying Exam Portal</h1>
                            <p>Student Portal</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Auth Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-header">
                    <h2>Forgot Password</h2>
                    <p><?php 
                        if ($showCodeForm) {
                            echo "Enter the reset code sent to your email";
                        } elseif ($showPasswordForm) {
                            echo "Create your new password";
                        } elseif ($showConfirmation) {
                            echo "Confirm your account";
                        } else {
                            echo "Find your account";
                        }
                    ?></p>
                </div>
                <div class="auth-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($showPasswordForm): ?>
                        <!-- New Password Form -->
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="password-requirements">
                                <p>Password requirements:</p>
                                <ul>
                                    <li>At least 8 characters long</li>
                                    <li>At least one uppercase letter</li>
                                    <li>At least one number</li>
                                    <li>At least one special character</li>
                                </ul>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                        </form>
                    <?php elseif ($showCodeForm): ?>
                        <!-- Reset Code Form -->
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="reset_code">Enter Reset Code</label>
                                <input type="text" class="form-control" id="reset_code" name="reset_code" required maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit code">
                                <small style="display: block; margin-top: 5px; color: #666;">Please check your email for the 6-digit reset code</small>
                            </div>
                            <button type="submit" name="verify_code" class="btn btn-primary">Verify Code</button>
                        </form>
                    <?php elseif ($showConfirmation): ?>
                        <!-- Account Confirmation Form -->
                        <div class="account-info">
                            <div class="alert alert-info">
                                <strong>Account Found:</strong><br>
                                Name: <?php echo htmlspecialchars($studentInfo['firstname'] . ' ' . $studentInfo['lastname']); ?><br>
                                Email: <?php echo htmlspecialchars($studentInfo['email']); ?>
                            </div>
                            <p class="text-center">Would you like to reset the password for this account?</p>
                            <form action="" method="post" class="confirmation-form">
                                <div class="button-group">
                                    <button type="submit" name="send_code" class="btn btn-primary">Yes, Send Reset Code</button>
                                    <a href="forgot_password.php" class="btn btn-secondary">No, Try Different Email</a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Email Form -->
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <small class="form-text text-muted">Enter the email address associated with your account.</small>
                            </div>
                            <button type="submit" name="find_account" class="btn btn-primary">Find Account</button>
                        </form>
                    <?php endif; ?>
                    
                    <div class="auth-footer">
                        <p><a href="stud_register.php">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PUP Qualifying Exam Portal. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 