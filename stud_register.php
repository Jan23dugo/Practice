<?php
// Include database connection
include 'config/config.php';

// Add session security measures
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

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
    // Updated sanitization methods
    $firstname = htmlspecialchars(trim($_POST['firstname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $middlename = htmlspecialchars(trim($_POST['middlename'] ?? ''), ENT_QUOTES, 'UTF-8');
    $lastname = htmlspecialchars(trim($_POST['lastname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Don't sanitize password to preserve special characters
    $confirm_password = $_POST['confirm_password'];
    $contact_number = htmlspecialchars(trim($_POST['contact_number'] ?? ''), ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8');
    $date_of_birth = htmlspecialchars(trim($_POST['date_of_birth'] ?? ''), ENT_QUOTES, 'UTF-8');
    $gender = htmlspecialchars(trim($_POST['gender'] ?? ''), ENT_QUOTES, 'UTF-8');
    $student_type = htmlspecialchars(trim($_POST['student_type'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    // Validate student type input
    $allowed_student_types = ['Transferee', 'Shiftee', 'Ladderized'];
    if (!in_array($student_type, $allowed_student_types)) {
        $registration_error = "Invalid student type selection";
        $show_registration = true;
        exit();
    }

    // Validate gender input
    $allowed_genders = ['Male', 'Female', 'Other'];
    if (!in_array($gender, $allowed_genders)) {
        $registration_error = "Invalid gender selection";
        $show_registration = true;
        exit();
    }

    // Validate date format
    $date = DateTime::createFromFormat('Y-m-d', $date_of_birth);
    if (!$date || $date->format('Y-m-d') !== $date_of_birth) {
        $registration_error = "Invalid date format";
        $show_registration = true;
        exit();
    }
    
    // Profile picture handling with additional security
    $profile_picture = null;
    $upload_error = '';
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file = $_FILES['profile_picture'];
        
        // Get the real MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $upload_error = "Only JPG, JPEG, and PNG files are allowed.";
        } elseif ($file['size'] > $max_size) {
            $upload_error = "File size must be less than 5MB.";
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            // Generate a random filename with limited characters
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            
            // Define upload paths using relative paths
            $upload_dir = 'uploads/profile_pictures';
            $destination = $upload_dir . '/' . $filename;
            
            // Check if directory exists and is writable
            if (!is_dir($upload_dir)) {
                $upload_error = "Upload directory does not exist.";
            } elseif (!is_writable($upload_dir)) {
                $upload_error = "Upload directory is not writable.";
            } else {
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $upload_error = "Error uploading file. Please try again.";
                } else {
                    $profile_picture = $destination;
                    // Set proper permissions
                    @chmod($destination, 0644);
                }
            }
        }
    }
    
    if (!empty($upload_error)) {
        $registration_error = $upload_error;
        $show_registration = true;
    } else if (empty($firstname)|| empty($middlename) || empty($lastname) || empty($email) || empty($password) || empty($contact_number) || empty($address) || empty($date_of_birth) || empty($gender)) {
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
        try {
            // Use prepared statements for all database operations
            $conn->begin_transaction();
            
            // Check if email already exists
            $check_query = "SELECT COUNT(*) as count FROM students WHERE email = ?";
            $stmt = $conn->prepare($check_query);
            if (!$stmt) {
                throw new Exception("Preparation failed: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                throw new Exception("Execution failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $registration_error = "Email already exists";
                $show_registration = true;
            } else {
                // Hash the password with strong algorithm
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 2
                ]);
                
                // Insert new student with prepared statement
                $insert_query = "INSERT INTO students (firstname, middlename, lastname, email, password, contact_number, address, date_of_birth, gender, student_type, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($insert_query);
                if (!$stmt) {
                    throw new Exception("Preparation failed: " . $conn->error);
                }
                
                $stmt->bind_param("sssssssssss", 
                    $firstname,
                    $middlename,
                    $lastname, 
                    $email, 
                    $hashed_password, 
                    $contact_number, 
                    $address, 
                    $date_of_birth, 
                    $gender,
                    $student_type,
                    $profile_picture
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Execution failed: " . $stmt->error);
                }
                
                $conn->commit();
                $registration_success = "Registration successful! You can now login.";
                $show_registration = false;
                
                // Log successful registration
                error_log("New user registered: " . $email . " at " . date('Y-m-d H:i:s'));
            }
        } catch (Exception $e) {
            $conn->rollback();
            $registration_error = "Registration failed: " . $e->getMessage();
            $show_registration = true;
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Process login form submission with improved security
if (isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $login_password = $_POST['login_password'];
    
    if (empty($email) || empty($login_password)) {
        $login_error = "Both fields are required";
    } else {
        try {
            // Use prepared statement for login
            $login_query = "SELECT * FROM students WHERE email = ? LIMIT 1";
            $stmt = $conn->prepare($login_query);
            if (!$stmt) {
                throw new Exception("Preparation failed: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                throw new Exception("Execution failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $student = $result->fetch_assoc();
                
                if (password_verify($login_password, $student['password'])) {
                    // Generate a new session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Store minimal information in session
                    $_SESSION['stud_id'] = $student['stud_id'];
                    $_SESSION['firstname'] = $student['firstname'];
                    $_SESSION['middlename'] = $student['middlename'];
                    $_SESSION['lastname'] = $student['lastname'];
                    $_SESSION['email'] = $student['email'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['first_login'] = true; // Set first_login flag
                    
                    // Log successful login
                    error_log("Successful login: " . $email . " at " . date('Y-m-d H:i:s'));
                    
                    // Redirect to student dashboard
                    header("Location: stud_dashboard.php");
                    exit();
                } else {
                    // Use generic error message for security
                    $login_error = "Invalid email or password";
                    error_log("Failed login attempt for email: " . $email . " at " . date('Y-m-d H:i:s'));
                }
            } else {
                // Use generic error message for security
                $login_error = "Invalid email or password";
                error_log("Failed login attempt for non-existent email: " . $email . " at " . date('Y-m-d H:i:s'));
            }
        } catch (Exception $e) {
            $login_error = "An error occurred. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: stud_register.php?timeout=1");
    exit();
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
            font-size: 16px;
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
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        header {
            background-color: #75343a;
            color: white;
            padding: 5px 2%;
            display: flex;
            position: fixed;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 70px;
        }

        header .logo img {
            height: 50px;
        }

        nav ul {
            list-style: none;
            display: flex;
        }

        nav ul li {
            margin-right: 20px;
            padding-left: 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
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
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }

        .left {
            position: fixed;
            top: 70px;
            left: 0;
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

        .header {
            margin-top: auto;
            margin-bottom: 2rem;
        }

        .left h1 {
            margin-top: 8rem;
            margin-bottom: 15px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #b8afa8;
        }

        .left h2 {
            margin-bottom: 1.5rem;
            font-size: clamp(1.2rem, 1.8vw, 1.8rem);
            font-weight: 800;
        }

        .left p {
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
        }

        .footer {
            margin-bottom: 2rem;
        }

        .footer img {
            width: clamp(60px, 8vw, 80px);
            margin-bottom: 1rem;
        }

        .right {
            margin-left: 60%;
            width: 40%;
            padding: 1.5rem 3rem;
            height: calc(100vh - 70px);
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
        }

        .auth-header {
            margin-bottom: 1rem;
            text-align: center;
        }

        .auth-header h2 {
            font-size: 1.75rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .auth-header p {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .auth-body {
            margin-top: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.6rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            color: #333;
            margin-bottom: 2px;
            font-weight: normal;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 6px 8px;
            font-size: 0.825rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
            height: 28px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 1px rgba(117, 52, 58, 0.1);
        }

        select.form-control {
            height: 28px;
            padding: 2px 6px;
            background-position: right 6px center;
        }

        input[type="date"] {
            height: 28px;
            padding: 2px 6px;
        }

        .form-group:last-child {
            grid-column: 1 / -1;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            width: 100%;
            padding: 8px;
            font-size: 0.875rem;
            height: 34px;
            margin-top: 0.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .auth-footer {
            margin-top: 0.75rem;
            text-align: center;
            font-size: 0.8rem;
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            display: none;
            font-size: 0.7rem;
            padding: 0.4rem;
            margin-top: 0.2rem;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .password-requirements.show {
            display: block;
        }

        .password-requirements ul {
            margin: 0.2rem 0 0 0;
            padding-left: 1rem;
        }

        .password-requirements li {
            margin-bottom: 0.1rem;
        }

        .auth-body form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        ::placeholder {
            font-size: 0.825rem;
            color: #999;
        }

        /* Custom styling for alerts */
        .alert {
            margin-bottom: 15px;
            padding: 10px;
            color: #fff;
            border-radius: 5px;
        }

        .alert-danger {
            background-color: #e74c3c;
        }

        .alert-success {
            background-color: #2ecc71;
        }

        @media (max-width: 900px) {
            h1 {
                font-size: clamp(1.3rem, 5vw, 2.2rem);
            }
            h2, .auth-header h2 {
                font-size: clamp(1.1rem, 4vw, 1.7rem);
            }
            .left h1 {
                font-size: clamp(1.1rem, 5vw, 1.7rem);
            }
            .left h2 {
                font-size: clamp(1rem, 4vw, 1.3rem);
            }
            p, label, input, button, select, .form-text, .forgot-password, .auth-footer {
                font-size: clamp(0.95rem, 2vw, 1.05rem);
            }
        }

        @media (max-width: 600px) {
            h1 {
                font-size: clamp(1rem, 6vw, 1.3rem);
            }
            h2, .auth-header h2 {
                font-size: clamp(0.95rem, 5vw, 1.1rem);
            }
            .left h1 {
                font-size: clamp(0.95rem, 6vw, 1.1rem);
            }
            .left h2 {
                font-size: clamp(0.9rem, 5vw, 1rem);
            }
            p, label, input, button, select, .form-text, .forgot-password, .auth-footer {
                font-size: clamp(0.85rem, 3vw, 0.95rem);
            }
            .btn-primary {
                font-size: clamp(0.95rem, 2vw, 1rem);
            }
        }

        @media screen and (min-width: 1440px) {
            .container {
                max-width: 100%;
            }
            
            .auth-body {
                max-width: 1000px;
            }
        }

        @media screen and (min-width: 1920px) {
            .container {
                max-width: 100%;
            }
            
            .left, .right {
                padding: 3rem 6rem;
            }
            
            .auth-body {
                max-width: 1200px;
            }
        }

        /* Add smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Back button styling */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            padding: 0;
        }

        .back-link i {
            font-size: 1rem;
        }

        .back-link:hover {
            color: var(--primary);
        }

        /* Login specific styles */
        .login-form .auth-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .login-form .auth-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .login-form .auth-header p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .login-form .form-group {
            margin-bottom: 1.25rem;
        }

        .login-form .form-group label {
            display: block;
            font-size: 1rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .login-form .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 0.9rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            height: 40px;
        }

        .login-form .forgot-password {
            text-align: right;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .login-form .forgot-password a {
            color: var(--primary);
            font-size: 0.875rem;
            text-decoration: none;
        }

        .login-form button[type="submit"] {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            height: 40px;
            margin-bottom: 1.5rem;
        }

        .login-form .auth-footer {
            text-align: center;
            font-size: 0.9rem;
        }

        /* Registration specific styles */
        .registration-form .auth-header {
            margin-bottom: 1rem;
            text-align: center;
        }

        .registration-form .auth-header h2 {
            font-size: 1.75rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .registration-form .auth-header p {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .registration-form .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.6rem;
            margin-bottom: 0.5rem;
        }

        .registration-form .form-group {
            margin-bottom: 0;
        }

        .registration-form .form-group label {
            display: block;
            font-size: 0.8rem;
            color: #333;
            margin-bottom: 2px;
        }

        .registration-form .form-group input,
        .registration-form .form-group select {
            width: 100%;
            padding: 6px 8px;
            font-size: 0.825rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            height: 28px;
        }

        .registration-form .form-group:last-child {
            grid-column: 1 / -1;
            margin-bottom: 0.5rem;
        }

        .registration-form button[type="submit"] {
            width: 100%;
            padding: 8px;
            font-size: 0.875rem;
            height: 34px;
            margin-top: 0.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .registration-form .auth-footer {
            margin-top: 0.75rem;
            text-align: center;
            font-size: 0.8rem;
        }

        /* Add these styles for password requirements */
        .registration-form .password-requirements {
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 4px;
            font-size: 0.8rem;
            display: none;
            transition: all 0.3s ease;
            position: absolute;
            width: 100%;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .registration-form .form-group {
            position: relative;
        }

        .registration-form .requirement-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #666;
            font-size: 0.75rem;
            margin-bottom: 4px;
        }

        .requirement-item::before {
            content: "×";
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
            border-radius: 50%;
            background-color: #dc3545;
            color: white;
            font-weight: bold;
        }

        .requirement-item.valid::before {
            content: "✓";
            background-color: #198754;
        }

        .requirement-item.valid {
            color: #198754;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const lengthCheck = document.getElementById('length-check');
            const uppercaseCheck = document.getElementById('uppercase-check');
            const numberCheck = document.getElementById('number-check');
            const specialCheck = document.getElementById('special-check');
            const requirements = document.querySelector('.password-requirements');
            const allFormInputs = document.querySelectorAll('.registration-form input, .registration-form select');

            function checkPasswordRequirements(password) {
                // Check length
                const isLengthValid = password.length >= 8;
                lengthCheck.classList.toggle('valid', isLengthValid);

                // Check uppercase
                const hasUppercase = /[A-Z]/.test(password);
                uppercaseCheck.classList.toggle('valid', hasUppercase);

                // Check number
                const hasNumber = /[0-9]/.test(password);
                numberCheck.classList.toggle('valid', hasNumber);

                // Check special character
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                specialCheck.classList.toggle('valid', hasSpecial);
            }

            // Show password requirements only when password field is focused
            passwordInput.addEventListener('focus', function() {
                requirements.style.display = 'block';
            });

            // Hide requirements when focus moves to another input
            allFormInputs.forEach(input => {
                if (input.id !== 'password') {
                    input.addEventListener('focus', function() {
                        requirements.style.display = 'none';
                    });
                }
            });

            // Update requirements as user types
            passwordInput.addEventListener('input', function() {
                checkPasswordRequirements(this.value);
            });

            // Initial check of password requirements
            checkPasswordRequirements(passwordInput.value);

            const profilePicInput = document.getElementById('profile_picture');
            const previewDiv = document.getElementById('image_preview');
            
            if (profilePicInput && previewDiv) {
                profilePicInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewDiv.innerHTML = `<img src="${e.target.result}" alt="Profile Preview">`;
                        }
                        reader.readAsDataURL(file);
                    } else {
                        previewDiv.innerHTML = '';
                    }
                });
            }
        });
    </script>
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
        
        <div class="right">
            <?php if (!$show_registration): ?>
                <div class="login-form">
                    <a href="index.php" class="back-link">
                        <i class="bi bi-arrow-left"></i>Back to Home
                    </a>
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
                                <div class="forgot-password">
                                    <a href="forgot_password.php">Forgot Password?</a>
                                </div>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary">Login</button>
                        </form>
                    </div>
                    <div class="auth-footer">
                        <p>Don't have an account? <a href="stud_register.php?register=true">Register here</a></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="registration-form">
                    <a href="index.php" class="back-link">
                        <i class="bi bi-arrow-left"></i>Back to Home
                    </a>
                    <div class="auth-header">
                        <h2>STUDENT REGISTRATION</h2>
                        <p>Create your student account</p>
                    </div>
                    <div class="auth-body">
                        <?php if (!empty($registration_error)): ?>
                            <div class="alert alert-danger"><?php echo $registration_error; ?></div>
                        <?php endif; ?>
                        
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="form-grid">
                                <!-- First Name - Last Name -->
                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" required>
                                </div>
                                <div class="form-group">
                                    <label for="middletname">Middle Name</label>
                                    <input type="text" class="form-control" id="middlename" name="middlename" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" required>
                                </div>
                                <div class="form-group">
                                    <label for="student_type">Student Type</label>
                                    <select class="form-control" id="student_type" name="student_type" required>
                                        <option value="">Select Student Type</option>
                                        <option value="Transferee">Transferee</option>
                                        <option value="Shiftee">Shiftee</option>
                                        <option value="Ladderized">Ladderized</option>
                                    </select>
                                </div>

                                <!-- Email - Contact Number -->
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                                </div>

                                <!-- Password - Confirm Password -->
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="password-requirements">
                                        <h6>Password requirements:</h6>
                                        <div class="requirement-item" id="length-check">At least 8 characters long</div>
                                        <div class="requirement-item" id="uppercase-check">At least one uppercase letter</div>
                                        <div class="requirement-item" id="number-check">At least one number</div>
                                        <div class="requirement-item" id="special-check">At least one special character</div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <!-- Date of Birth - Address -->
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>

                                <!-- Gender -->
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select class="form-control" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="register" class="btn btn-primary">Register</button>
                        </form>
                    </div>
                    <div class="auth-footer">
                        <p>Already have an account? <a href="stud_register.php">Login here</a></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
