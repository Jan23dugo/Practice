<?php
// Include database connection
include 'config/config.php';
session_start();

// Initialize variables
$registration_error = '';
$login_error = '';
$registration_success = '';
$show_registration = false;

// Check if registration form should be shown
if (isset($_GET['register'])) {
    $show_registration = true;
}

// Process registration form submission
if (isset($_POST['register'])) {
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($phone) || empty($address) || empty($date_of_birth) || empty($gender)) {
        $registration_error = "All fields are required";
        $show_registration = true;
    } elseif ($password !== $confirm_password) {
        $registration_error = "Passwords do not match";
        $show_registration = true;
    } elseif (strlen($password) < 8) {
        $registration_error = "Password must be at least 8 characters long";
        $show_registration = true;
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $registration_error = "Password must contain at least one uppercase letter";
        $show_registration = true;
    } elseif (!preg_match('/[0-9]/', $password)) {
        $registration_error = "Password must contain at least one number";
        $show_registration = true;
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $registration_error = "Password must contain at least one special character";
        $show_registration = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = "Invalid email format";
        $show_registration = true;
    } else {
        // Check if email already exists
        $check_query = "SELECT * FROM students WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $registration_error = "Email already exists";
            $show_registration = true;
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new student
            $insert_query = "INSERT INTO students (firstname, lastname, email, password, phone, address, date_of_birth, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssssssss", $firstname, $lastname, $email, $hashed_password, $phone, $address, $date_of_birth, $gender);
            
            if ($stmt->execute()) {
                $registration_success = "Registration successful! You can now login.";
                $show_registration = false;
            } else {
                $registration_error = "Registration failed: " . $conn->error;
                $show_registration = true;
            }
        }
    }
}

// Process login form submission
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $login_password = mysqli_real_escape_string($conn, $_POST['login_password']);
    
    if (empty($email) || empty($login_password)) {
        $login_error = "Both fields are required";
    } else {
        // Check if the email exists
        $login_query = "SELECT * FROM students WHERE email = ?";
        $stmt = $conn->prepare($login_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();
            
            if (password_verify($login_password, $student['password'])) {
                // Login successful - store student info in session
                $_SESSION['stud_id'] = $student['stud_id'];
                $_SESSION['firstname'] = $student['firstname'];
                $_SESSION['lastname'] = $student['lastname'];
                $_SESSION['email'] = $student['email'];
                
                // Redirect to student dashboard
                header("Location: stud_dashboard.php");
                exit();
            } else {
                $login_error = "Invalid password";
            }
        } else {
            $login_error = "Email not found";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - PUP Qualifying Exam</title>
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
        
        /* Header Styles */
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
        
        /* Auth Section */
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
            border: 1px solid var(--gray);
            border-radius: 6px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
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
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .auth-footer {
            text-align: center;
            padding: 0 30px 30px;
        }
        
        .auth-footer p {
            font-size: 14px;
            color: var(--text-dark);
            opacity: 0.8;
        }
        
        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 20px 0;
            text-align: center;
            font-size: 14px;
        }
        
        /* Adjust the container width for better centering */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .auth-container {
                max-width: 95%;
            }
        }
        
        /* Add these styles in the <style> section */
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
    </style>
    <script>
        // Password strength checker
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            strengthIndicator.style.height = '5px';
            strengthIndicator.style.marginTop = '5px';
            strengthIndicator.style.borderRadius = '3px';
            strengthIndicator.style.transition = 'all 0.3s ease';
            
            if (passwordInput) {
                passwordInput.parentNode.insertBefore(strengthIndicator, passwordInput.nextSibling);
                
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    let feedback = '';
                    
                    // Length check
                    if (password.length >= 8) {
                        strength += 25;
                    }
                    
                    // Uppercase check
                    if (/[A-Z]/.test(password)) {
                        strength += 25;
                    }
                    
                    // Number check
                    if (/[0-9]/.test(password)) {
                        strength += 25;
                    }
                    
                    // Special character check
                    if (/[^A-Za-z0-9]/.test(password)) {
                        strength += 25;
                    }
                    
                    // Update the strength indicator
                    strengthIndicator.style.width = '100%';
                    
                    if (strength === 0) {
                        strengthIndicator.style.backgroundColor = '#e0e0e0';
                    } else if (strength <= 25) {
                        strengthIndicator.style.backgroundColor = '#f44336'; // Red
                        feedback = 'Weak';
                    } else if (strength <= 50) {
                        strengthIndicator.style.backgroundColor = '#ff9800'; // Orange
                        feedback = 'Moderate';
                    } else if (strength <= 75) {
                        strengthIndicator.style.backgroundColor = '#ffc107'; // Yellow
                        feedback = 'Good';
                    } else {
                        strengthIndicator.style.backgroundColor = '#4caf50'; // Green
                        feedback = 'Strong';
                    }
                    
                    // Add feedback text
                    const feedbackText = document.getElementById('password-strength-text');
                    if (feedbackText) {
                        feedbackText.textContent = password.length > 0 ? `Password strength: ${feedback}` : '';
                    } else if (password.length > 0) {
                        const feedbackElement = document.createElement('div');
                        feedbackElement.id = 'password-strength-text';
                        feedbackElement.style.fontSize = '12px';
                        feedbackElement.style.marginTop = '5px';
                        feedbackElement.textContent = `Password strength: ${feedback}`;
                        strengthIndicator.parentNode.insertBefore(feedbackElement, strengthIndicator.nextSibling);
                    }
                });
            }
        });
    </script>
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
                <?php if ($show_registration): ?>
                    <!-- Registration Form -->
                    <div class="auth-header">
                        <h2>Student Registration</h2>
                        <p>Create your student account</p>
                    </div>
                    <div class="auth-body">
                        <?php if (!empty($registration_error)): ?>
                            <div class="alert alert-danger"><?php echo $registration_error; ?></div>
                        <?php endif; ?>
                        
                        <form action="" method="post">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small style="font-size: 12px; color: #666;">
                                    Password requirements:
                                    <ul style="margin-top: 5px; padding-left: 15px;">
                                        <li>At least 8 characters long</li>
                                        <li>At least one uppercase letter</li>
                                        <li>At least one number</li>
                                        <li>At least one special character</li>
                                    </ul>
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <button type="submit" name="register" class="btn btn-primary">Register</button>
                        </form>
                    </div>
                    <div class="auth-footer">
                        <p>Already have an account? <a href="stud_register.php">Login here</a></p>
                    </div>
                <?php else: ?>
                    <!-- Login Form -->
                    <div class="auth-header">
                        <h2>Student Login</h2>
                        <p>Access your student account</p>
                    </div>
                    <div class="auth-body">
                        <?php if (!empty($login_error)): ?>
                            <div class="alert alert-danger"><?php echo $login_error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($registration_success)): ?>
                            <div class="alert alert-success"><?php echo $registration_success; ?></div>
                        <?php endif; ?>
                        
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="login_password">Password</label>
                                <input type="password" class="form-control" id="login_password" name="login_password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary">Login</button>
                        </form>
                    </div>
                    <div class="auth-footer">
                        <p>Don't have an account? <a href="stud_register.php?register=true">Register here</a></p>
                    </div>
                <?php endif; ?>
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
