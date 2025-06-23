<?php
// Start session at the very beginning of the file
session_start();

// Check if user is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: stud_register.php");
    exit();
}

// Database connection
require_once 'config/config.php';

// Fetch student information
$stud_id = $_SESSION['stud_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE stud_id = ?");
$stmt->bind_param("i", $stud_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIS Qualifying Examination Registration</title> 

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Navigation links */
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .back-btn i {
            font-size: 14px;
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

        /* Main content */
        .main-content {
            flex: 1;
            padding: 30px 0;
            padding-bottom: 80px; /* Increased padding to account for fixed footer */
        }
        
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            background-color: rgba(0, 0, 0, 0.6); /* Darker semi-transparent background */
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-content {
            background-color: #ffffff;
            margin: 5% auto; /* Centered vertically */
            padding: 40px;
            border: none;
            border-radius: 12px;
            width: 90%; /* Responsive width */
            max-width: 700px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out;
        }

        .modal-content h2 {
            font-size: 28px;
            margin-bottom: 25px;
            color: var(--primary);
            font-weight: 700;
            padding-right: 30px; /* Space for close button */
        }

        .modal-content p {
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .modal-content strong {
            color: var(--primary);
            font-weight: 600;
        }

        .modal-content ul {
            margin: 15px 0;
            padding-left: 20px;
        }

        .modal-content li {
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .close-btn {
            position: absolute;
            right: 25px;
            top: 20px;
            color: var(--primary);
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-btn:hover {
            background-color: var(--gray-light);
            transform: rotate(90deg);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-content {
                margin: 10% auto;
                padding: 30px 20px;
                width: 95%;
            }
            
            .modal-content h2 {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            .modal-content p {
                font-size: 15px;
            }
            
            footer {
                height: 50px; /* Slightly smaller on mobile */
            }
            
            .main-content {
                padding-bottom: 70px; /* Adjusted for mobile footer height */
            }
        }
        
        /* Form styling */
        .form-section {
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .header-logo img {
            height: 80px;
        }
        
        .header-logo h1 {
            font-size: 1.5rem;
            text-align: center;
            color: #800000;
            margin: 0;
        }
        
        .step {
            display: none;
            animation: fadeIn 0.5s;
        }
        
        .step.active {
            display: block !important; /* Force display when active */
        }
        
        .form-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-field {
            margin-bottom: 15px;
        }
        
        .form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-field input, 
        .form-field select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-field input:focus, 
        .form-field select:focus {
            outline: none;
            border-color: #800000;
        }
        
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .prev-btn, .nxt-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .prev-btn {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .nxt-btn {
            background-color: #800000;
            color: white;
        }
        
        .prev-btn:hover {
            background-color: #e0e0e0;
        }
        
        .nxt-btn:hover {
            background-color: #600000;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .debug-output {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            overflow-x: auto;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Progress indicator */
        .progress-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
            max-width: 100%;
        }
        
        .progress-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #ddd;
            z-index: 1;
        }
        
        .step-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #555;
            position: relative;
            z-index: 2;
            flex-shrink: 0;
            margin: 0 5px;
        }
        
        .step-indicator.active {
            background-color: #800000;
            color: white;
        }
        
        .step-indicator.completed {
            background-color: #4caf50;
            color: white;
        }
        
        /* Content wrapper */
        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .content-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .content-header h2 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .content-header p {
            font-size: 16px;
            color: var(--text-dark);
            opacity: 0.8;
        }

        /* Grading System Field Styles */
        .grading-select-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .select-container {
            flex: 1;
        }
        
        .view-grading-btn {
            padding: 10px 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .view-grading-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .view-grading-btn:not(:disabled):hover {
            background-color: var(--primary-dark);
        }
        
        /* Grading Preview Modal Styles */
        #gradingPreviewModal .modal-content {
            max-width: 800px;
        }
        
        #gradingSystemContent {
            margin-top: 20px;
        }
        
        .grading-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .grading-table th,
        .grading-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .grading-table th {
            background-color: var(--primary);
            color: white;
        }
        
        .grading-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .error-message {
            color: #dc3545;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
            margin-top: 10px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-container label {
            cursor: pointer;
            user-select: none;
            margin-bottom: 0;
        }

        #ocrPreviewModal .modal-content {
            max-width: 900px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            padding: 0;
            overflow: hidden;
        }

        #ocrPreviewModal .modal-header {
            padding: 20px;
            background: white;
            border-bottom: 1px solid #ddd;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        #ocrPreviewModal .modal-header h2 {
            margin: 0;
            padding-right: 30px;
        }

        #ocrResultsContent {
            flex: 1;
            overflow-y: auto;
            padding: 0 20px;
            margin: 0;
        }

        #ocrPreviewModal .modal-footer {
            padding: 20px;
            background: white;
            border-top: 1px solid #ddd;
            position: sticky;
            bottom: 0;
            z-index: 1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .grading-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grading-table thead {
            position: sticky;
            top: 0;
            background: var(--primary);
            z-index: 1;
        }

        .grading-table th,
        .grading-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .grading-table tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="img/Logo.png" alt="PUP Logo">
                    <div class="logo-text">
                        <h1>STREAMS</h1>
                        <p>Student Qualifying Examination Registration</p>
                    </div>
                </div>
                <div class="nav-links">
                    <a href="stud_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="main-content">
        <div class="content-wrapper">

            <div id="infoModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeModal()">&times;</span>
                    <h2>CCIS Qualifying Examination Information</h2>
                    <p>Welcome to the CCIS Qualifying Examination registration. Please note the following requirements:</p>
                    <strong><p>Exam Registration Requirements:</p></strong>
                    <ul>
                        <li>Must <strong>not</strong> have a <strong>failing grade or grade lower than 2.00 (or 85)</strong></li>
                        <li>Must be an <strong>incoming Second Year if transferee or shiftee</strong> (must have completed at least 2 semester).
                            <br>If ladderized, must be <strong>graduated on their 3-year diplomat program</strong>. </li>
                        <li>Must have <strong>no failing grade, dropped, incomplete, and withdrawn mark</strong> in any subjects.</li>
                    </ul>
                    <strong><p>Required Documents:</p></strong>
                    <ul>
                        <li>Submit a copy of your <strong>Transcript of Records (TOR), or Informative or Certified Copy of Grades</strong> (initial requirement of the college only) </li>
                        <li>Provide a <strong>valid School ID</strong></li>
                        <li>Ensure all contact information is accurate</li>
                        <li>Select the correct "Student Type" (Transferee, Shiftee, or Ladderized) as it affects the required information</li>
                    </ul>
                    <p>After completing the registration, you will receive an email with further instructions for the examination.</p>
                </div>
            </div>

            <section class="form-section">

                <?php
                if (isset($_SESSION['debug_output'])) {
                    echo "<div class='debug-output'>";
                    echo "<h3>Debug Information:</h3>";
                    echo "<pre>" . htmlspecialchars($_SESSION['debug_output']) . "</pre>";
                    echo "</div>";
                    unset($_SESSION['debug_output']);
                }

                if (isset($_SESSION['last_error'])) {
                    echo "<div class='error'>" . htmlspecialchars($_SESSION['last_error']) . "</div>";
                    unset($_SESSION['last_error']);
                }
                ?>

                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="step-indicator active" data-step="1">1</div>
                    <div class="step-indicator" data-step="2">2</div>
                    <div class="step-indicator" data-step="3">3</div>
                    <div class="step-indicator" data-step="4">4</div>
                    <div class="step-indicator" data-step="5">5</div>
                </div>

                <!-- Form -->
                <form id="multi-step-form" action="qualiexam_registerBack.php" method="POST" enctype="multipart/form-data" onsubmit="return submitForm(event)">
                    <input type="hidden" name="action" value="final_submit">
                    <input type="hidden" name="subjects" id="subjects_data">
                    <div class="step active">
                        <h2>Student Type Selection</h2>
                        <div class="form-field">
                            <label for="student_type">Student Type</label>
                            <?php
                            // Add debugging for the comparison
                            $currentType = isset($student['student_type']) ? strtolower(trim($student['student_type'])) : '';
                            echo "<!-- Current student type: '$currentType' -->";
                            ?>
                            <select id="student_type" name="student_type" required onchange="handleStudentTypeChange()">
                                <option value="" disabled <?php echo !isset($student['student_type']) ? 'selected' : ''; ?>>Select Student Type</option>
                                <option value="transferee" <?php echo ($currentType === 'transferee') ? 'selected' : ''; ?>>Transferee</option>
                                <option value="shiftee" <?php echo ($currentType === 'shiftee') ? 'selected' : ''; ?>>Shiftee</option>
                                <option value="ladderized" <?php echo ($currentType === 'ladderized') ? 'selected' : ''; ?>>Ladderized</option>
                            </select>
                        </div>
                        <div class="buttons">
                            <button type="button" class="nxt-btn" onclick="validateStep()">Next</button>
                        </div>
                    </div>

                    <div class="step">
                        <h2>Personal Details</h2>
                        <div class="form-group">
                            <div class="form-field">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['lastname']); ?>" required>
                            </div>
                            <div class="form-field">
                                <label for="first_name">Given Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['firstname']); ?>" required>
                            </div>
                            <div class="form-field">
                                <label for="middle_name">Middle Name (Optional)</label>
                                <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($student['middlename'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>" required>
                            </div>
                            <div class="form-field">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" required>
                                    <option value="">--Select Gender--</option>
                                    <option value="Male" <?php echo ($student['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($student['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($student['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="buttons">
                            <button type="button" class="prev-btn" onclick="prevStep()">Previous</button>
                            <button type="button" class="nxt-btn" onclick="validateStep()">Next</button>
                        </div>
                    </div>

                    <!-- Step 2: Contact Details -->
                    <div class="step">
                        <h2>Contact Details</h2>
                        <div class="form-group">
                            <div class="form-field">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            <div class="form-field">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($student['contact_number'] ?? ''); ?>" required>
                            </div>
                            <div class="form-field">
                                <label for="street">Address</label>
                                <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="buttons">
                            <button type="button" class="prev-btn" onclick="prevStep()">Previous</button>
                            <button type="button" class="nxt-btn" onclick="validateStep()">Next</button>
                        </div>
                    </div>

                    <!-- Step 3: Academic Details -->
                    <div class="step">
                        <h2>Academic Information</h2>
                        <div class="form-group">
                            <div class="form-field" id="year-level-field">
                                <label for="year_level">Years of Residency</label>
                                <input type="number" id="year_level" name="year_level">
                            </div>
                            <div class="form-field" id="previous-school-field">
                                <label for="previous_school">Name of Previous School</label>
                                <select id="previous_school" name="previous_school" required>
                                <option value="" disabled selected>Select Previous University</option>
                                    <option value="AMA University">AMA University (AMA)</option>
                                    <option value="Technological University of the Philippines">Technological University of the Philippines (TUP)</option>
                                    <option value="Polytechnic University of the Philippines">Polytechnic University of the Philippines (PUP)</option>
                                    <option value="University of Perpetual">University of Perpetual (UP)</option>
                                    <option value="University of the Philippines">University of the Philippines (UP)</option>
                                    <option value="Diploma in Information and Communication Technology">Diploma in Information and Communication Technology (DICT)</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-field" id="previous-program-field">
                                <label for="previous_program">Name of Previous Program</label>
                                <select id="previous_program" name="previous_program" required>
                                    <option value="" disabled selected>Select Previous Program</option>
                                </select>
                            </div>

                            <div class="form-field" id="program-apply-field">
                                <label for="desired_program">Name of Program Applying To</label>
                                <select id="desired_program" name="desired_program" required>
                                    <option value="" disabled selected>Select Desired Program</option>
                                    <option value="BSCS">Bachelor of Science in Computer Science (BSCS)</option>
                                    <option value="BSIT">Bachelor of Science in Information Technology (BSIT)</option>
                                </select>
                            </div>

                            <div class="form-field" id="grading-system-field">
                                <label for="grading_system">Grading System Used</label>
                                <div class="grading-select-group">
                                    <div class="select-container">
                                        <select id="grading_system" name="grading_system" required onchange="handleGradingSystemChange(this.value)">
                                            <option value="" disabled selected>Select Grading System</option>
                                            <?php
                                            // Fetch available grading systems
                                            $query = "SELECT DISTINCT grading_name FROM university_grading_systems ORDER BY grading_name";
                                            $result = $conn->query($query);
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($row['grading_name']) . '">' . 
                                                     htmlspecialchars($row['grading_name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button type="button" id="viewGradingBtn" class="view-grading-btn" onclick="openGradingPreview()" disabled>
                                        <i class="fas fa-eye"></i> View Grading System
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="buttons">
                            <button type="button" class="prev-btn" onclick="prevStep()">Previous</button>
                            <button type="button" class="nxt-btn" onclick="validateStep()">Next</button>
                        </div>
                    </div>

                    <!-- Step 4: Upload Documents -->
                    <div class="step">
                        <h2>Document Submission</h2>
                        <div class="form-group">
                            <div class="form-field" id="tor-field">
                                <label for="tor">Upload Copy of Transcript of Records (TOR)</label>
                                <input type="file" id="tor" name="tor" required>
                                <small>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                            </div>
                            <div class="form-field">
                                <label for="school_id">Upload Copy of School ID</label>
                                <input type="file" id="school_id" name="school_id" required>
                                <small>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                            </div>
                            <div class="form-field">
                                <div class="checkbox-container">
                                    <input type="checkbox" id="has_copy_grades" name="has_copy_grades" onchange="toggleCopyGradesUpload()">
                                    <label for="has_copy_grades">I have a Copy of Grades</label>
                                </div>
                            </div>
                            <div class="form-field" id="copy-grades-field" style="display: none;">
                                <label for="copy_grades">Upload Copy of Grades</label>
                                <input type="file" id="copy_grades" name="copy_grades">
                                <small>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                            </div>
                        </div>
                        <div class="buttons">
                            <button type="button" class="prev-btn" onclick="prevStep()">Previous</button>
                            <button type="submit" class="nxt-btn">Submit Application</button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PUP Qualifying Exam Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- Add this before the closing body tag -->
    <div id="gradingPreviewModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeGradingPreview()">&times;</span>
            <h2>Grading System Preview</h2>
            <div id="gradingSystemContent">
                <div class="loading">Loading grading system details...</div>
            </div>
        </div>
    </div>

    <!-- Add this modal for OCR preview -->
    <div id="ocrPreviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-btn" onclick="closeOCRPreview()">&times;</span>
                <h2>OCR Results Preview</h2>
            </div>
            <div id="ocrResultsContent">
                <div class="loading">Processing documents...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="prev-btn" onclick="closeOCRPreview()">Cancel</button>
                <button type="button" class="nxt-btn" onclick="confirmAndSubmit()">Confirm and Submit</button>
            </div>
        </div>
    </div>

<script>
// Define these functions in the global scope
function handleStudentTypeChange() {
    const studentType = document.getElementById('student_type').value;
    const previousProgramSelect = document.getElementById("previous_program");
    const yearLevelField = document.getElementById("year-level-field");

    if (studentType === 'ladderized') {
        // Hide year level field
        yearLevelField.style.display = 'none';
        
        // Clear existing options and add only DICT
        previousProgramSelect.innerHTML = `
            <option value="Diploma in Information Communication Technology (DICT)" selected>
                Diploma in Information Communication Technology (DICT)
            </option>
        `;
        previousProgramSelect.disabled = true;
    } else {
        // Show year level field
        yearLevelField.style.display = 'block';
        previousProgramSelect.disabled = false;
        
        // Repopulate the dropdown with all programs
        fetch('data/courses.json')
            .then(response => response.json())
            .then(data => {
                // Clear existing options and add default option
                previousProgramSelect.innerHTML = '<option value="" disabled selected>Select Previous Program</option>';
                
                // Add all programs except DICT for non-ladderized students
                data.forEach(course => {
                    if (studentType !== 'ladderized' && course === "Diploma in Information Communication Technology (DICT)") {
                        return;
                    }
                    
                    const option = document.createElement("option");
                    option.value = course;
                    option.textContent = course;
                    previousProgramSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading programs:', error));
    }
}

function validateStep() {
    const activeStep = document.querySelector('.step.active');
    const inputs = activeStep.querySelectorAll('input, select');
    let isValid = true;
    
    const currentIndex = Array.from(document.querySelectorAll('.step')).indexOf(activeStep);
    console.log('Validating step', currentIndex + 1);

    inputs.forEach(input => {
        // Skip year_level validation for ladderized students
        if (input.id === 'year_level' && 
            document.getElementById('student_type').value === 'ladderized') {
            return;
        }
        
        if (input.hasAttribute('required') && !input.checkValidity()) {
            isValid = false;
            console.log('Invalid input:', input.id);
            input.reportValidity();
        }
    });

    if (isValid) {
        console.log('Step', currentIndex + 1, 'is valid, proceeding to next step');
        nextStep();
    } else {
        console.log('Step', currentIndex + 1, 'has validation errors');
    }
}

function nextStep() {
    const currentStep = document.querySelector('.step.active');
    const nextStep = currentStep.nextElementSibling;
    const stepIndicators = document.querySelectorAll('.step-indicator');

    if (nextStep) {
        const currentIndex = Array.from(document.querySelectorAll('.step')).indexOf(currentStep);
        stepIndicators[currentIndex].classList.remove('active');
        stepIndicators[currentIndex].classList.add('completed');
        stepIndicators[currentIndex + 1].classList.add('active');
        
        currentStep.classList.remove('active');
        nextStep.classList.add('active');
        
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
    }
}

function prevStep() {
    const currentStep = document.querySelector('.step.active');
    const prevStep = currentStep.previousElementSibling;
    const stepIndicators = document.querySelectorAll('.step-indicator');

    if (prevStep) {
        // Get the current index
        const currentIndex = Array.from(document.querySelectorAll('.step')).indexOf(currentStep);
        
        // Update step indicators
        stepIndicators[currentIndex].classList.remove('active');
        stepIndicators[currentIndex - 1].classList.remove('completed');
        stepIndicators[currentIndex - 1].classList.add('active');
        
        // Change visible step
        currentStep.classList.remove('active');
        prevStep.classList.add('active');
        
        // Scroll to top of form
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
        
        console.log('Moving back to step', currentIndex);
    }
}

// Keep your existing DOMContentLoaded event listener for initialization
document.addEventListener('DOMContentLoaded', function() {
    const previousProgramSelect = document.getElementById("previous_program");
    const yearLevelField = document.getElementById("year-level-field");
    const stepIndicators = document.querySelectorAll('.step-indicator');
    
    // Handle prefilled student type on page load
    if (document.getElementById('student_type').value) {
        handleStudentTypeChange();
    }
    
    // Debug: Check all steps are properly defined
    const steps = document.querySelectorAll('.step');
    console.log('Total steps found:', steps.length);
    steps.forEach((step, index) => {
        console.log(`Step ${index + 1} heading:`, step.querySelector('h2')?.textContent || 'No heading');
    });
    
    // Debug: Check step indicators
    console.log('Total step indicators found:', stepIndicators.length);
    
    // Specifically check the Document Submission step
    const documentStep = Array.from(steps).find(step => 
        step.querySelector('h2')?.textContent.includes('Document Submission'));
    
    if (documentStep) {
        console.log('Document Submission step found:', documentStep);
    } else {
        console.error('Document Submission step not found!');
    }

    // Function to populate the dropdown from JSON
    function populatePreviousProgramSelect() {
        // Show loading indicator
        previousProgramSelect.innerHTML = `<option value="">Loading programs...</option>`;
        previousProgramSelect.disabled = true;
        
        fetch('data/courses.json') // Updated path to match your file structure
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                // Clear existing options, but keep the default option
                previousProgramSelect.innerHTML = `<option value="">Select Previous Program</option>`;
                previousProgramSelect.disabled = false;
                
                // Populate dropdown with courses from JSON
                data.forEach(course => {
                    const option = document.createElement("option");
                    option.value = course; // Set the value to the course name
                    option.textContent = course; // Set the displayed text to the course name
                    previousProgramSelect.appendChild(option); // Add option to the dropdown
                });

                // Call the function to handle student type change after populating
                handleStudentTypeChange(); 
            })
            .catch(error => {
                console.error('Error loading programs:', error);
                previousProgramSelect.innerHTML = `<option value="">Error loading programs. Please try again.</option>`;
                previousProgramSelect.disabled = false;
                
                // Add a retry button
                const previousProgramField = document.getElementById('previous-program-field');
                const retryButton = document.createElement('button');
                retryButton.type = 'button';
                retryButton.className = 'retry-btn';
                retryButton.textContent = 'Retry Loading Programs';
                retryButton.style.marginTop = '10px';
                retryButton.style.padding = '5px 10px';
                retryButton.style.backgroundColor = '#f0f0f0';
                retryButton.style.border = '1px solid #ddd';
                retryButton.style.borderRadius = '4px';
                retryButton.style.cursor = 'pointer';
                retryButton.onclick = populatePreviousProgramSelect;
                
                // Remove existing retry button if any
                const existingRetryButton = previousProgramField.querySelector('.retry-btn');
                if (existingRetryButton) {
                    previousProgramField.removeChild(existingRetryButton);
                }
                
                previousProgramField.appendChild(retryButton);
            });
    }

    // Initially populate the previous program dropdown
    populatePreviousProgramSelect();

    // Add event listener for student type selection
    document.getElementById('student_type').addEventListener('change', handleStudentTypeChange);
});

// Function to open the modal
function openModal() {
    document.getElementById("infoModal").style.display = "block";
}

// Function to close the modal
function closeModal() {
    document.getElementById("infoModal").style.display = "none";
}

// Open the modal when the page loads
window.onload = function() {
    openModal();
};

// Function to handle grading system change
function handleGradingSystemChange(value) {
    const viewButton = document.getElementById('viewGradingBtn');
    viewButton.disabled = !value;
}

// Function to open grading preview modal
function openGradingPreview() {
    const modal = document.getElementById('gradingPreviewModal');
    const contentDiv = document.getElementById('gradingSystemContent');
    const gradingSystem = document.getElementById('grading_system').value;
    
    modal.style.display = 'block';
    contentDiv.innerHTML = '<div class="loading">Loading grading system details...</div>';
    
    // Fetch grading system details
    fetch(`get_grading_system.php?name=${encodeURIComponent(gradingSystem)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Create table with grading system details
            let tableHtml = `
                <table class="grading-table">
                    <thead>
                        <tr>
                            <th>Grade Value</th>
                            <th>Description</th>
                            <th>Percentage Range</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach(grade => {
                tableHtml += `
                    <tr>
                        <td>${grade.grade_value}</td>
                        <td>${grade.description}</td>
                        <td>${grade.min_percentage}% - ${grade.max_percentage}%</td>
                    </tr>
                `;
            });
            
            tableHtml += '</tbody></table>';
            contentDiv.innerHTML = tableHtml;
        })
        .catch(error => {
            contentDiv.innerHTML = `
                <div class="error-message">
                    Error loading grading system: ${error.message}
                </div>
            `;
        });
}

// Function to close grading preview modal
function closeGradingPreview() {
    document.getElementById('gradingPreviewModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const gradingModal = document.getElementById('gradingPreviewModal');
    const infoModal = document.getElementById('infoModal');
    
    if (event.target === gradingModal) {
        gradingModal.style.display = 'none';
    }
    if (event.target === infoModal) {
        infoModal.style.display = 'none';
    }
}

function toggleCopyGradesUpload() {
    const checkbox = document.getElementById('has_copy_grades');
    const copyGradesField = document.getElementById('copy-grades-field');
    const copyGradesInput = document.getElementById('copy_grades');
    
    if (checkbox.checked) {
        copyGradesField.style.display = 'block';
        copyGradesInput.required = true;
    } else {
        copyGradesField.style.display = 'none';
        copyGradesInput.required = false;
        copyGradesInput.value = ''; // Clear the file input
    }
}

function submitForm(event) {
    event.preventDefault();
    
    // Disable submit button to prevent multiple submissions
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        button.disabled = true;
        button.textContent = 'Processing...';
    });
    
    console.log("Starting form submission process");
    
    // Clear any previous error messages
    const existingErrors = document.querySelectorAll('.error');
    existingErrors.forEach(error => error.remove());
    
    // Process the form
    processOCR();
}

// Create a separate function for OCR processing
async function processOCR() {
    try {
        // Show loading state
        const ocrModal = document.getElementById('ocrPreviewModal');
        const ocrContent = document.getElementById('ocrResultsContent');
        ocrModal.style.display = 'block';
        ocrContent.innerHTML = '<div class="loading">Processing documents...</div>';
        
        // Get the form data
        const form = document.getElementById('multi-step-form');
        const formData = new FormData(form);
        formData.set('action', 'process_ocr');
        
        console.log("Sending OCR request");
        
        // Send the OCR request
        const response = await fetch('qualiexam_registerBack.php', {
            method: 'POST',
            body: formData
        });
        
        // Check if the response is OK (status code 200-299)
        if (!response.ok) {
            console.error(`HTTP error! Status: ${response.status}`);
            throw new Error(`Server returned status code ${response.status}`);
        }
        
        // Get raw response for debugging
        const responseText = await response.text();
        
        // Log the raw response to console for debugging
        console.log("Raw OCR response:", responseText);
        
        // Check if response is empty
        if (!responseText || responseText.trim() === '') {
            console.error("Empty response received from server");
            throw new Error("Server returned an empty response");
        }
        
        // Check for PHP errors in the response
        if (responseText.includes("Fatal error") || 
            responseText.includes("Parse error") || 
            responseText.includes("Warning:") || 
            responseText.includes("Notice:")) {
            console.error("PHP error detected in response:", responseText);
            
            // Extract the error message
            const errorMatch = responseText.match(/(Fatal error|Parse error|Warning|Notice):[^<]+/);
            const errorMessage = errorMatch ? errorMatch[0] : "PHP error detected in response";
            
            throw new Error(errorMessage);
        }
        
        // Check if response starts with proper JSON (should begin with { or [)
        const trimmedResponse = responseText.trim();
        if (!(trimmedResponse.startsWith('{') || trimmedResponse.startsWith('['))) {
            console.error("Response does not start with valid JSON:", responseText);
            throw new Error("Server returned an invalid format");
        }
        
        // Try to parse as JSON with better error information
        let result;
        try {
            result = JSON.parse(trimmedResponse);
        } catch (parseError) {
            console.error("JSON parse error:", parseError);
            console.log("Problematic response:", responseText);
            
            // Additional debugging for malformed JSON
            let problematicChar = '';
            let problematicIndex = 0;
            
            try {
                // Try to find where the parsing fails by parsing incrementally
                for (let i = 0; i < 100; i++) {
                    // Try parsing chunks to find where it fails
                    const testStr = trimmedResponse.substring(0, Math.floor(trimmedResponse.length * (i/100)));
                    JSON.parse(testStr);
                }
            } catch (e) {
                if (e instanceof SyntaxError) {
                    const match = e.message.match(/position\s+(\d+)/);
                    if (match && match[1]) {
                        problematicIndex = parseInt(match[1]);
                        problematicChar = trimmedResponse.charAt(problematicIndex);
                        console.error(`JSON parse failed at index ${problematicIndex}, character: '${problematicChar}'`);
                        console.log("Context:", trimmedResponse.substring(Math.max(0, problematicIndex - 20), problematicIndex + 20));
                    }
                }
            }
            
            throw new Error(`Invalid JSON response: ${parseError.message}. Check character at position ${problematicIndex}: '${problematicChar}'`);
        }
        
        // Re-enable submit buttons
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            button.disabled = false;
            button.textContent = 'Submit Application';
        });
        
        if (result.error) {
            // Display error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = result.error;
            ocrContent.innerHTML = '';
            ocrContent.appendChild(errorDiv);
            
            console.error('OCR processing error:', result.error);
            return;
        }
        
        if (!result.subjects || result.subjects.length === 0) {
            ocrContent.innerHTML = '<div class="error-message">No subjects could be extracted from the documents. Please check your uploads.</div>';
            return;
        }
        
        console.log("Successfully extracted subjects:", result.subjects.length);
        
        // Store the subjects data as a JSON string
        document.getElementById('subjects_data').value = JSON.stringify(result.subjects);
        
        // Display the OCR results in a table
        let tableHtml = `
            <table class="grading-table">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Description</th>
                        <th>Units</th>
                        <th>Grades</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        result.subjects.forEach(subject => {
            tableHtml += `
                <tr>
                    <td>${subject.subject_code || ''}</td>
                    <td>${subject.subject_description || ''}</td>
                    <td>${subject.units || ''}</td>
                    <td>${subject.Grades || ''}</td>
                </tr>
            `;
        });
        
        tableHtml += '</tbody></table>';
        ocrContent.innerHTML = tableHtml;
        
    } catch (error) {
        console.error('Error during OCR processing:', error);
        
        const ocrContent = document.getElementById('ocrResultsContent');
        const errorMessage = error.message || 'An error occurred during processing';
        
        // Create a more informative error display
        ocrContent.innerHTML = `
            <div class="error-message">
                <strong>Error during OCR processing:</strong><br>
                ${errorMessage}<br>
                <small>If this error persists, please try refreshing the page or contact support.</small>
            </div>
        `;
        
        // Re-enable submit buttons
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            button.disabled = false;
            button.textContent = 'Submit Application';
        });
    }
}

function closeOCRPreview() {
    document.getElementById('ocrPreviewModal').style.display = 'none';
}

async function confirmAndSubmit() {
    try {
        // Disable the confirm button to prevent double submissions
        const confirmButton = document.querySelector('.modal-footer .nxt-btn');
        const cancelButton = document.querySelector('.modal-footer .prev-btn');
        
        if (confirmButton) {
            confirmButton.disabled = true;
            confirmButton.textContent = 'Processing...';
        }
        
        if (cancelButton) {
            cancelButton.disabled = true;
        }
        
        console.log("Starting final submission");
        
        // Get form data ready for submission
        const form = document.getElementById('multi-step-form');
        const formData = new FormData(form);
        
        // Get the subjects data from the hidden input
        const subjectsData = document.getElementById('subjects_data').value;
        
        if (!subjectsData) {
            throw new Error("Subject data is missing. Please try again from the beginning.");
        }
        
        // Make sure we're sending the correct action and data
        formData.set('action', 'final_submit');
        formData.set('subjects', subjectsData);
        
        console.log("Submitting form with action:", formData.get('action'));
        console.log("Subject data length:", subjectsData.length);
        
        // Close the modal and show the loading indicator
        document.getElementById('ocrPreviewModal').style.display = 'none';
        
        // Show loading indicator
        const formSection = document.querySelector('.form-section');
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading';
        loadingDiv.id = 'submission-loading';
        loadingDiv.innerHTML = '<div class="loading">Processing your registration. Please wait...</div>';
        formSection.appendChild(loadingDiv);
        
        // Send the request to the server
        const response = await fetch('qualiexam_registerBack.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response text for debugging
        const responseText = await response.text();
        console.log("Raw response:", responseText);
        
        // Try to parse as JSON
        let result;
        try {
            // Try to extract only valid JSON from the response
            // This helps if there are PHP notices or warnings before/after the JSON
            const jsonMatch = responseText.match(/(\{.*\}|\[.*\])/s);
            if (jsonMatch) {
                result = JSON.parse(jsonMatch[0]);
            } else {
                result = JSON.parse(responseText);
            }
        } catch (parseError) {
            console.error('Error parsing response as JSON:', parseError);
            console.log('Response text:', responseText);
            
            // If we can't parse JSON but there's a success message anywhere in the response, 
            // we'll assume success and redirect anyway
            if (responseText.includes('success') && responseText.includes('reference_id')) {
                console.log("Found success indicators in response, proceeding with redirect");
                // Extract reference ID if possible
                const refMatch = responseText.match(/reference_id["']?\s*:\s*["']?([^"',}]+)/);
                const referenceId = refMatch ? refMatch[1] : 'Generated';
                
                // Save data in session storage for the success page
                sessionStorage.setItem('registration_success', 'true');
                sessionStorage.setItem('reference_id', referenceId);
                
                // Redirect to success page
                window.location.href = 'registration_success.php';
                return;
            }
            
            throw new Error('Invalid server response. Please contact support.');
        }
        
        // Remove loading indicator
        const loadingElement = document.getElementById('submission-loading');
        if (loadingElement) {
            loadingElement.remove();
        }
        
        if (result.error) {
            // Display error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.textContent = result.error;
            formSection.insertBefore(errorDiv, formSection.firstChild);
            
            console.error('Submission error:', result.error);
            
            // Re-enable buttons
            if (confirmButton) {
                confirmButton.disabled = false;
                confirmButton.textContent = 'Confirm and Submit';
            }
            
            if (cancelButton) {
                cancelButton.disabled = false;
            }
            
            return;
        }
        
        if (result.success) {
            // Store in sessionStorage for persistence across page loads
            sessionStorage.setItem('registration_success', 'true');
            sessionStorage.setItem('reference_id', result.reference_id || 'Generated');
            sessionStorage.setItem('student_name', 
                document.getElementById('first_name').value + ' ' + document.getElementById('last_name').value);
            sessionStorage.setItem('email', document.getElementById('email').value);
            
            // Show success message before redirect
            const successDiv = document.createElement('div');
            successDiv.style.backgroundColor = '#d4edda';
            successDiv.style.color = '#155724';
            successDiv.style.padding = '15px';
            successDiv.style.borderRadius = '4px';
            successDiv.style.marginBottom = '15px';
            successDiv.innerHTML = `
                <strong>Registration Successful!</strong><br>
                Your reference ID is: ${result.reference_id || 'Generated'}<br>
                Redirecting to success page...
            `;
            formSection.insertBefore(successDiv, formSection.firstChild);
            
            // Scroll to the top to show the success message
            window.scrollTo(0, 0);
            
            // Redirect immediately to success page
            window.location.href = 'registration_success.php';
        } else {
            // If no explicit success/error but response received, assume success
            console.log("No explicit success/error in response, assuming success");
            sessionStorage.setItem('registration_success', 'true');
            
            // Try to extract a reference ID if available
            if (result.reference_id) {
                sessionStorage.setItem('reference_id', result.reference_id);
            }
            
            // Store student info for the success page
            sessionStorage.setItem('student_name', 
                document.getElementById('first_name').value + ' ' + document.getElementById('last_name').value);
            sessionStorage.setItem('email', document.getElementById('email').value);
            
            // Redirect to success page
            window.location.href = 'registration_success.php';
        }
        
    } catch (error) {
        console.error('Error during final submission:', error);
        
        // Close the modal if it's open
        document.getElementById('ocrPreviewModal').style.display = 'none';
        
        // Show a user-friendly error message
        const formSection = document.querySelector('.form-section');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error';
        errorDiv.innerHTML = `
            <strong>Submission Error</strong><br>
            ${error.message || 'An error occurred during submission. Please try again.'}<br>
            <small>If this error persists, please contact support.</small>
        `;
        formSection.insertBefore(errorDiv, formSection.firstChild);
        
        // Scroll to the top to show the error
        window.scrollTo(0, 0);
        
        // Remove loading indicator if it exists
        const loadingElement = document.getElementById('submission-loading');
        if (loadingElement) {
            loadingElement.remove();
        }
        
        // Re-enable buttons in modal
        const confirmButton = document.querySelector('.modal-footer .nxt-btn');
        const cancelButton = document.querySelector('.modal-footer .prev-btn');
        
        if (confirmButton) {
            confirmButton.disabled = false;
            confirmButton.textContent = 'Confirm and Submit';
        }
        
        if (cancelButton) {
            cancelButton.disabled = false;
        }
    }
}
</script>

</body>
</html>
