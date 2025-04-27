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
            padding: 10px;
            display: flex;
            position: fixed;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        header .logo img {
            width: 70px;
            height: auto;
            margin-left: 30px;
        }

        nav ul {
            list-style: none;
            display: flex;
        }

        nav ul li {
            margin-right: 50px;
            padding-left: 30px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 22px;
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
            height: 100%;
            padding-top: 93px;
        }

        .left {
            width: 60%;
            background-image: url('assets/images/Homepage.png'); /* Background Image */
            background-size: cover;
            color: white;
            padding: 50px 30px;
            text-align: center;
            align-items: center;
        }

        .left h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 800;
            color: #b8afa8;
            margin-top: 200px;
        }

        .left h2 {
            font-size: 2.7rem;
            margin-bottom: 20px;
            font-weight: 800;
        }

        .left p {
            font-size: 1.3rem;
            margin-bottom: 40px;
        }

        .footer img {
            height: 100px;
            width: 100px;
        }

        .right {
            width: 40%;
            background-color: #ffffff;
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
            color: #75343a;
            margin-bottom: 20px;
            font-weight: 800;
            text-shadow: 0px 5px 6px rgba(0, 0, 0, 0.1);
            padding-top: 50px;
        }

        .auth-header p {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
            text-align: center;
        }
    
        .auth-body {
            margin: 0px;
            width: 100%; /* Ensure the form takes up the full width */
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
        }

        .form-group {
            width: 100%; /* Make sure each form group is 100% width */
            margin-bottom: 5px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: #f9f9f9;
            color: #333;
            transition: border 0.3s ease;
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
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
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
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .header-right {
            position: absolute;
            top: 120px;
            left: 1180px;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--primary-dark);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            transition: background-color 0.3s;
            font-weight: 500;
        }

        .back-button:hover {
            background-color: var(--primary-dark);
            color: var(--text-light);
        }

        .back-button .material-symbols-rounded {
            font-size: 20px;
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
            <div class="header-right">
                <a href="index.php" class="back-button">
                <i class="bi bi-arrow-left"></i>Back to Home
                </a>
            </div>    
            <?php if ($show_registration): ?>
                <!-- Registration Form -->
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
</body>
</html>
