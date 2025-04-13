<?php
// Include database connection
include 'config/config.php';
session_start();

$message = '';
$messageType = '';
$validToken = false;
$token = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token and check expiry
    $query = "SELECT * FROM students WHERE reset_token = ? AND reset_token_expiry > NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $validToken = true;
    } else {
        $message = "Invalid or expired reset link. Please request a new one.";
        $messageType = "danger";
    }
}

if (isset($_POST['update_password'])) {
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $token = mysqli_real_escape_string($conn, $_POST['token']);
    
    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "danger";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $message = "Password must contain at least one uppercase letter.";
        $messageType = "danger";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $message = "Password must contain at least one number.";
        $messageType = "danger";
    } elseif (!preg_match("/[^A-Za-z0-9]/", $password)) {
        $message = "Password must contain at least one special character.";
        $messageType = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } else {
        // Verify token again
        $query = "SELECT * FROM students WHERE reset_token = ? AND reset_token_expiry > NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Hash new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $updateQuery = "UPDATE students SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ss", $hashed_password, $token);
            
            if ($stmt->execute()) {
                $message = "Password has been successfully updated. You can now login with your new password.";
                $messageType = "success";
                $validToken = false; // Hide the form
            } else {
                $message = "Error updating password. Please try again.";
                $messageType = "danger";
            }
        } else {
            $message = "Invalid or expired reset link. Please request a new one.";
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PUP Qualifying Exam Portal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        /* Reuse the same styles from forgot_password.php */
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
        
        .password-requirements {
            background-color: var(--gray-light);
            padding: 15px;
            border-radius: 8px;
            margin: 5px 0 15px;
        }
        
        .password-requirements ul {
            list-style: none;
            margin: 10px 0 0;
            padding: 0;
        }
        
        .password-requirements li {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .password-requirements li::before {
            content: "•";
            color: var(--primary);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-login a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="header-left">
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
                    <h2>Reset Password</h2>
                    <p>Enter your new password</p>
                </div>
                <div class="auth-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($validToken): ?>
                        <form action="" method="post">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
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
                            
                            <button type="submit" name="update_password" class="btn btn-primary">Reset Password</button>
                        </form>
                    <?php endif; ?>
                    
                    <div class="back-to-login">
                        <a href="stud_register.php">Back to Login</a>
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