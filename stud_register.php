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
    } else if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($contact_number) || empty($address) || empty($date_of_birth) || empty($gender)) {
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
                    $_SESSION['lastname'] = $student['lastname'];
                    $_SESSION['email'] = $student['email'];
                    $_SESSION['last_activity'] = time();
                    
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
            max-width: 800px;
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
            padding: 30px 40px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 15px;
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
        
        .form-control:hover {
            border-color: var(--primary-light);
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .password-requirements {
            grid-column: 1 / -1;
            background-color: var(--gray-light);
            padding: 15px;
            border-radius: 8px;
            margin: 5px 0;
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            position: relative;
            z-index: 10;
        }
        
        .password-requirements.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        .password-requirements ul {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin: 8px 0 0 0;
            padding: 0;
            list-style: none;
        }
        
        .password-requirements li {
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .password-requirements li::before {
            content: "×";
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
            border-radius: 50%;
            background-color: #ff4444;
            color: white;
            font-weight: bold;
        }
        
        .password-requirements li.valid {
            color: #4CAF50;
        }
        
        .password-requirements li.valid::before {
            content: "✓";
            background-color: #4CAF50;
        }
        
        .btn {
            padding: 14px 20px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-top: 20px;
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
            margin-bottom: 10px;
        }
        
        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: 8px;
            font-size: 13px;
        }
        
        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .forgot-password a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        /* Footer Styles */
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 20px 0;
            width: 100%;
            position: fixed;
            bottom: 0;
            left: 0;
            z-index: 900;
            height: 60px; /* Fixed height for consistency */
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        
        footer .container {
            width: 100%;
            max-width: 1200px;
            padding: 0 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        footer p {
            text-align: center;
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        /* Adjust main content to account for fixed footer */
        .auth-container {
            margin-bottom: 80px; /* Increased margin to account for fixed footer */
        }
        
        /* Mobile responsiveness for footer */
        @media (max-width: 768px) {
            footer {
                height: 50px; /* Slightly smaller on mobile */
            }
            
            .auth-container {
                max-width: 95%;
                margin: 20px auto;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .auth-body {
                padding: 20px;
            }
            
            .password-requirements ul {
                grid-template-columns: 1fr;
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
        
        .image-preview {
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
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        input[type="file"] {
            padding: 10px;
            background-color: white;
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const strengthIndicator = document.createElement('div');
            const passwordRequirements = document.querySelector('.password-requirements');
            
            strengthIndicator.className = 'password-strength';
            strengthIndicator.style.height = '5px';
            strengthIndicator.style.marginTop = '5px';
            strengthIndicator.style.borderRadius = '3px';
            strengthIndicator.style.transition = 'all 0.3s ease';
            
            if (passwordInput) {
                passwordInput.parentNode.insertBefore(strengthIndicator, passwordInput.nextSibling);
                
                // Show password requirements on focus
                passwordInput.addEventListener('focus', function() {
                    if (passwordRequirements) {
                        passwordRequirements.classList.add('show');
                    }
                });

                // Hide password requirements when focus is lost
                passwordInput.addEventListener('blur', function() {
                    if (passwordRequirements) {
                        passwordRequirements.classList.remove('show');
                    }
                });
                
                // Function to check password requirements
                function checkPasswordRequirements(password) {
                    const requirements = {
                        length: password.length >= 8,
                        uppercase: /[A-Z]/.test(password),
                        number: /[0-9]/.test(password),
                        special: /[^A-Za-z0-9]/.test(password)
                    };

                    // Update requirement list items
                    const requirementsList = passwordRequirements.querySelectorAll('li');
                    requirementsList[0].classList.toggle('valid', requirements.length);
                    requirementsList[1].classList.toggle('valid', requirements.uppercase);
                    requirementsList[2].classList.toggle('valid', requirements.number);
                    requirementsList[3].classList.toggle('valid', requirements.special);

                    return Object.values(requirements).every(Boolean);
                }
                
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    let feedback = '';
                    
                    // Check password requirements
                    const allRequirementsMet = checkPasswordRequirements(password);
                    
                    // Update strength based on requirements
                    if (password.length >= 8) strength += 25;
                    if (/[A-Z]/.test(password)) strength += 25;
                    if (/[0-9]/.test(password)) strength += 25;
                    if (/[^A-Za-z0-9]/.test(password)) strength += 25;
                    
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

                    // Disable submit button if requirements are not met
                    const submitButton = document.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = !allRequirementsMet;
                        submitButton.style.opacity = allRequirementsMet ? '1' : '0.5';
                        submitButton.style.cursor = allRequirementsMet ? 'pointer' : 'not-allowed';
                    }
                });
            }
            
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
                        
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" required>
                                </div>
                                <div class="form-group">
                                    <label for="middlename">Middle Name</label>
                                    <input type="text" class="form-control" id="middlename" name="middlename">
                                    <small class="form-text text-muted">Optional</small>
                                </div>
                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="profile_picture">Profile Picture</label>
                                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/jpg">
                                    <small class="form-text text-muted">Upload a profile picture (JPG, JPEG, or PNG, max 5MB)</small>
                                    <div id="image_preview" class="image-preview"></div>
                                </div>
                                <div class="form-group full-width">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="password-requirements">
                                        <p>Password requirements:</p>
                                        <ul>
                                            <li>At least 8 characters long</li>
                                            <li>At least one uppercase letter</li>
                                            <li>At least one number</li>
                                            <li>At least one special character</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="address">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
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
                                <div class="form-group">
                                    <label for="student_type">Student Type</label>
                                    <select class="form-control" id="student_type" name="student_type" required>
                                        <option value="">Select Student Type</option>
                                        <option value="Transferee">Transferee</option>
                                        <option value="Shiftee">Shiftee</option>
                                        <option value="Ladderized">Ladderized</option>
                                    </select>
                                </div>
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
