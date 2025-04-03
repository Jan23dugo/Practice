<?php
// Include database connection
include 'config/config.php';
require 'vendor/autoload.php';
require 'config/admin_mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

// Initialize variables
$login_error = '';
$verification_sent = false;

// Function to generate verification code
function generateVerificationCode() {
    return sprintf("%06d", mt_rand(0, 999999));
}

// Function to check login attempts
function checkLoginAttempts($conn, $email) {
    $query = "SELECT login_attempts, last_attempt FROM admin_login_attempts WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Reset attempts if last attempt was more than 15 minutes ago
        if (strtotime($row['last_attempt']) < strtotime('-15 minutes')) {
            updateLoginAttempts($conn, $email, 0);
            return 0;
        }
        return $row['login_attempts'];
    }
    return 0;
}

// Function to update login attempts
function updateLoginAttempts($conn, $email, $attempts) {
    $query = "INSERT INTO admin_login_attempts (email, login_attempts, last_attempt) 
              VALUES (?, ?, NOW()) 
              ON DUPLICATE KEY UPDATE 
              login_attempts = ?, last_attempt = NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $email, $attempts, $attempts);
    $stmt->execute();
}

// Process login form submission
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $login_password = mysqli_real_escape_string($conn, $_POST['login_password']);
    
    // Check login attempts
    $attempts = checkLoginAttempts($conn, $email);
    if ($attempts >= 3) {
        $login_error = "Too many failed attempts. Please try again after 15 minutes.";
    } else {
        if (empty($email) || empty($login_password)) {
            $login_error = "Both fields are required";
        } else {
            // Check if the email exists in admin table
            $login_query = "SELECT * FROM admin WHERE email = ?";
            $stmt = $conn->prepare($login_query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                if (password_verify($login_password, $admin['password'])) {
                    // Generate verification code
                    $verification_code = generateVerificationCode();
                    
                    // Store verification details in session
                    $_SESSION['admin_verification'] = [
                        'admin_id' => $admin['admin_id'],
                        'email' => $admin['email'],
                        'code' => $verification_code,
                        'expiry' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
                    ];
                    
                    // Reset login attempts
                    updateLoginAttempts($conn, $email, 0);
                    
                    // Send verification email
                    $mail = AdminMailConfig::setupMailer();
                    if ($mail) {
                        try {
                            $mail->addAddress($email);
                            $mail->Subject = 'PUP Admin Portal - Security Verification Code';
                            $mail->Body = "
<html>
<body style='font-family: Arial, sans-serif;'>
    <h2>PUP Admin Portal Security Verification</h2>
    <p>A login attempt was made to your administrator account.</p>
    <p>Your verification code is: <strong style='font-size: 24px;'>{$verification_code}</strong></p>
    <p>This code will expire in 10 minutes.</p>
    <hr>
    <p style='font-size: 12px; color: #666;'>
        If you did not attempt to login, please contact the system administrator immediately.
        <br>
        This is an automated message, please do not reply.
    </p>
</body>
</html>";
                            $mail->AltBody = "Your PUP Admin Portal verification code is: {$verification_code}\nThis code will expire in 10 minutes.";
                            
                            if ($mail->send()) {
                                $verification_sent = true;
                            } else {
                                $login_error = "Failed to send verification code";
                            }
                        } catch (Exception $e) {
                            $login_error = "Email error: " . $mail->ErrorInfo;
                        }
                    } else {
                        $login_error = "Email configuration error";
                    }
                } else {
                    updateLoginAttempts($conn, $email, $attempts + 1);
                    $login_error = "Invalid credentials";
                }
            } else {
                $login_error = "Invalid credentials";
            }
        }
    }
}

// Process verification code submission
if (isset($_POST['verify'])) {
    if (!isset($_SESSION['admin_verification'])) {
        $login_error = "Verification session expired. Please login again.";
        $verification_sent = false;
    } else {
        $entered_code = $_POST['verification_code'];
        $verification = $_SESSION['admin_verification'];
        
        if (strtotime($verification['expiry']) < time()) {
            $login_error = "Verification code expired. Please login again.";
            unset($_SESSION['admin_verification']);
            $verification_sent = false;
        } elseif ($entered_code !== $verification['code']) {
            $login_error = "Invalid verification code";
        } else {
            // Verification successful
            $_SESSION['admin_id'] = $verification['admin_id'];
            $_SESSION['email'] = $verification['email'];
            $_SESSION['is_admin'] = true;
            
            // Clear verification data
            unset($_SESSION['admin_verification']);
            
            // Log successful login
            $log_query = "INSERT INTO admin_login_logs (admin_id, email, ip_address, status) 
                         VALUES (?, ?, ?, 'success')";
            $stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("iss", $_SESSION['admin_id'], $_SESSION['email'], $ip);
            $stmt->execute();
            
            // Redirect to admin dashboard
            header("Location:dashboard.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PUP Qualifying Exam</title>
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
            display: flex;
            justify-content: center;
        }
        
        /* Header Styles */
        header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 20px;
            width: 100%;
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
        
        /* Auth Section */
        .auth-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 0;
            width: 100%;
        }
        
        .auth-container {
            width: 100%;
            max-width: 400px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 0 auto;
        }
        
        .auth-header {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 25px 20px;
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
            border: 1px solid var(--gray);
            border-radius: 6px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--text-light);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Back Button */
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
        
        .back-button .material-symbols-rounded {
            font-size: 20px;
        }
        
        /* Footer */
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 20px 0;
            text-align: center;
            font-size: 14px;
            margin-top: auto;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .auth-container {
                max-width: 95%;
            }
            
            .logo-text h1 {
                font-size: 20px;
            }
        }
        
        .verification-message {
            margin-bottom: 20px;
        }
        
        .alert-info {
            background-color: #cce5ff;
            border-color: #b8daff;
            color: #004085;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .resend-code {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }
        
        .resend-code a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .resend-code a:hover {
            text-decoration: underline;
        }
        
        #verification_code {
            letter-spacing: 4px;
            font-size: 20px;
            text-align: center;
        }
        
        /* Add a specific container class for the header */
        header .container {
            justify-content: flex-start;
        }
        
        /* Add a specific container class for the footer */
        footer .container {
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <a href="index.php" class="back-button">
                        <span class="material-symbols-rounded">arrow_back</span>
                        Back to Home
                    </a>
                    <div class="logo">
                        <img src="img/Logo.png" alt="PUP Logo">
                        <div class="logo-text">
                            <h1>PUP Qualifying Exam Portal</h1>
                            <p>Admin Portal</p>
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
                    <h2>Admin Login</h2>
                    <p>Access your administrative account</p>
                </div>
                <div class="auth-body">
                    <?php if (!empty($login_error)): ?>
                        <div class="alert alert-danger">
                            <span class="material-symbols-rounded">error</span>
                            <?php echo $login_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($verification_sent): ?>
                        <!-- Verification Code Form -->
                        <div class="verification-message">
                            <div class="alert alert-info">
                                <span class="material-symbols-rounded">mail</span>
                                A verification code has been sent to your email.
                            </div>
                        </div>
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="verification_code">Enter Verification Code</label>
                                <input type="text" class="form-control" id="verification_code" 
                                       name="verification_code" required maxlength="6" 
                                       pattern="[0-9]{6}" placeholder="Enter 6-digit code">
                                <small class="form-text text-muted">
                                    The code will expire in 10 minutes
                                </small>
                            </div>
                            <button type="submit" name="verify" class="btn btn-primary">
                                Verify Code
                            </button>
                        </form>
                        <div class="resend-code">
                            <p>Didn't receive the code? 
                                <a href="javascript:void(0)" onclick="window.location.reload();">
                                    Send again
                                </a>
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Login Form -->
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" 
                                       name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="login_password">Password</label>
                                <input type="password" class="form-control" id="login_password" 
                                       name="login_password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary">
                                Login as Administrator
                            </button>
                        </form>
                    <?php endif; ?>
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
