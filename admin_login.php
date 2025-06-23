<?php
// Load session configuration first
require_once 'config/session_config.php';

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

// Check if user was redirected due to session timeout
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $login_error = 'Your session has expired due to inactivity. Please login again.';
}

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
            $_SESSION['last_activity'] = time(); // Set initial activity time
            $_SESSION['session_regenerated'] = time(); // Set initial regeneration time
            
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
    <title>Student Registration Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
            font-family: 'Open Sans', sans-serif;
            line-height: 1.6;
            height: 100vh;
            background-color: #f7f7f7;
        }

        header {
            background-color: #75343a;
            color: white;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            padding: 10px;
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
=======
            padding: 5px 2%;
>>>>>>> Stashed changes
            display: flex;
            position: fixed;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            top: 0;
            z-index: 1000;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        }

        header .logo img {
            width: 70px;
            height: auto;
            margin-left: 30px;
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
=======
            height: 70px;
        }

        header .logo img {
            height: 50px;
>>>>>>> Stashed changes
        }

        nav ul {
            list-style: none;
            display: flex;
        }

        nav ul li {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            margin-right: 50px;
            padding-left: 30px;
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
=======
            margin-right: 20px;
            padding-left: 15px;
>>>>>>> Stashed changes
        }

        nav ul li a {
            color: white;
            text-decoration: none;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            font-size: 22px;
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
=======
            font-size: 1rem;
>>>>>>> Stashed changes
            font-weight: 600;
            padding: 8px 0px;
            border-radius: 6px;
            transition: color 0.3s ease, transform 0.3s ease;
            position: relative;
        }

        nav ul li a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background-color: var(--accent);
            transition: var(--transition);
        }

        nav ul li a:hover::after {
            width: 80%;
        }

        .container {
            display: flex;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            height: 100%;
            padding-top: 93px;
        }

        .right {
            width: 60%;
            background-image: url('assets/images/Homepage.png'); /* Background Image */
            background-size: cover;
            color: white;
            padding: 50px 30px;
            text-align: center;
            align-items: center;
        }

        .right h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 800;
            color: #b8afa8;
            margin-top: 200px;
        }

        .right h2 {
            font-size: 2.7rem;
            margin-bottom: 20px;
            font-weight: 800;
        }

        .right p {
            font-size: 1.3rem;
            margin-bottom: 40px;
        }

        .footer img {
            height: 100px;
            width: 100px;
        }

        .left {
            width: 40%;
            background-color: #ac555c;
            padding: 40px; /* Adjust padding here */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            max-height: 100vh;
            overflow-y: auto;
        }

        .auth-header h2 {
            font-size: 35px;
            text-align: center;
            color: #f4f4f4;
            margin-bottom: 20px;
            font-weight: 800;
            text-shadow: 0px 5px 6px rgba(0, 0, 0, 0.1);
        }

        .auth-header p {
            color: #f4f4f4;
            font-size: 16px;
            margin-bottom: 30px;
            text-align: center;
        }
    
        .auth-body {
            margin: 0px;
            width: 100%; /* Ensure the form takes up the full width */
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            width: 100%;
        }

        .form-group {
            width: 100%; /* Make sure each form group is 100% width */
            margin-bottom: 15px;
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
>>>>>>> Stashed changes
        }

        .form-group label {
            display: block;
<<<<<<< Updated upstream
            font-size: 14px;
            color: #f4f4f4;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: #f9f9f9;
            color: #333;
            transition: border 0.3s ease;
        }

        .form-group input[type="file"] {
            padding: 10px;
            background-color: white;
        }

        .form-group .image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 10px auto;
            background-color: var(--gray-light);
            border: 2px dashed var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .form-group .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-group small {
            font-size: 0.85rem;
            color: #f4f4f4;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #75343a;
            outline: none;
        }

        .btn-primary {
            width: 100%;
            padding: 12px 20px;
            font-size: 18px;
            background-color: #75343a;
=======
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
=======
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .right {
            position: fixed;
            top: 70px;
            right: 0;
            width: 60%;
            height: calc(100vh - 70px);
            padding: 2rem;
            background-image: url('assets/images/Homepage.png');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 2rem;
        }

        .right .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .right h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .right h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .right p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .right .footer {
            margin-bottom: 2rem;
        }

        .right .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
            width: 40%;
            height: calc(100vh - 70px);
            padding: 2rem 4rem 6rem;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .back-link {
            position: absolute;
            top: 1.5rem;
            left: 4rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            z-index: 1;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-align: center;
        }
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 0.95rem;
            background-color: var(--primary);
>>>>>>> Stashed changes
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #a32f3b;
        }

        .forgot-password {
            text-align: right;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .forgot-password a {
            color: #8e2f3b;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
        }

        .auth-footer a {
            color: #8e2f3b;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
        /* Custom styling for alerts */
        .alert {
            margin-bottom: 20px;
            padding: 15px;
            color: #fff;
            border-radius: 5px;
        }

        .alert-danger {
            background-color: #e74c3c;
        }

        .alert-success {
            background-color: #2ecc71;
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
>>>>>>> Stashed changes
        }

        .verification-message {
            margin-bottom: 2rem;
        }

        #verification_code {
            letter-spacing: 0.25rem;
            font-size: 1.25rem;
            text-align: center;
            font-weight: 600;
        }

<<<<<<< Updated upstream
        .resend-code p {
            color: var(--text-light);
        }
        
=======
        .resend-code {
            margin-top: 1.5rem;
            text-align: center;
        }

        .resend-code p {
            font-size: 0.875rem;
            color: #666;
        }

>>>>>>> Stashed changes
        .resend-code a {
            color:rgb(0, 113, 233);
            text-decoration: none;
            font-weight: 500;
        }

        .resend-code a:hover {
            text-decoration: underline;
        }
<<<<<<< Updated upstream
        
        #verification_code {
            letter-spacing: 4px;
            font-size: 20px;
            text-align: center;
        }

        .header-left {
            position: absolute;
            top: 120px;
            left: 20px;
            margin-left: 25px;
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
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
>>>>>>> Stashed changes
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

<<<<<<< Updated upstream
        .back-button .material-symbols-rounded {
            font-size: 20px;
        }
=======
>>>>>>> Stashed changes
=======
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
>>>>>>> Stashed changes
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
>>>>>>> Stashed changes
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
>>>>>>> Stashed changes
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
>>>>>>> Stashed changes
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
>>>>>>> Stashed changes
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
>>>>>>> Stashed changes
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
>>>>>>> Stashed changes
=======
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
>>>>>>> Stashed changes

        .verification-message {
            margin-bottom: 2rem;
        }

        #verification_code {
            letter-spacing: 0.25rem;
            font-size: 1.25rem;
            text-align: center;
            font-weight: 600;
        }

        .resend-code {
            margin-top: 1.5rem;
            text-align: center;
        }

        .resend-code p {
            font-size: 0.875rem;
            color: #666;
        }

        .resend-code a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .resend-code a:hover {
            text-decoration: underline;
        }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php#banner"><img src="assets/images/PUPLogo.png" alt="PUP Logo" /></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php#banner"><i class="bi bi-house"></i> Home</a></li>
                <li><a href="index.php#about"><i class="bi bi-question-circle"></i> About</a></li>
                <li><a href="index.php#faqs"><i class="bi bi-question-circle"></i> FAQ</a></li>
                <li><a href="index.php#contact"><i class="bi bi-person-lines-fill"></i> Contact</a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <div class="left">
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
            <div class="header-left">
                <a href="index.php" class="back-button">
                <i class="bi bi-arrow-left"></i>Back to Home
                </a>
            </div>    
            <div class="auth-header">
                    <h2>ADMINISTRATOR LOGIN</h2>
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
                            <i class="bi bi-envelope-check"></i>
                                A verification code has been sent to your email.
                            </div>
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
>>>>>>> Stashed changes
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
<<<<<<< Updated upstream
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
        
        <div class="right">
        <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
=======
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
=======
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>Back to Home
            </a>
            <div class="auth-header">
                <h2>Administrator Login</h2>
                <p>Access your administrative account</p>
            </div>
            <div class="auth-body">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($verification_sent): ?>
                    <div class="verification-message">
                        <div class="alert alert-info">
                            <i class="bi bi-envelope-check"></i>
                            A verification code has been sent to your email address.
                        </div>
                    </div>
                    <form action="" method="post" class="verification-form">
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" 
                                   required maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="Enter 6-digit code"
                                   autocomplete="off">
                            <small class="form-text">The code will expire in 10 minutes</small>
                        </div>
                        <button type="submit" name="verify" class="btn-primary">
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
                    <form action="" method="post" class="login-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary">
                            Login as Administrator
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right">
            <div class="header">
                <h1>COLLEGE OF COMPUTER AND INFORMATION SCIENCES</h1>
                <h2>STUDENT REGISTRATION, EXAMINATION, AND MANAGEMENT SYSTEM PORTAL</h2>
                <p>Welcome to the College of Computer and Information Sciences (CCIS)
                    <br>qualifying examination portal for transferees, shiftees, and ladderized 
                    <br>program students. This examination is a mandatory requirement for 
                    <br>admission to BSIT and BSCS programs at PUP.</p>
            </div>
            <div class="footer">
                <img src="assets/images/PUPLogo.png" alt="PUP Logo">
            </div>
        </div>
>>>>>>> Stashed changes
    </div>
</body>
</html>
