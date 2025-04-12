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
    <link rel="stylesheet" href="assets/css/style.css">
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
        
        /* Footer */
        footer {
            background-color: var(--primary);
            color: var(--text-light);
            padding: 20px 0;
            text-align: center;
            font-size: 14px;
            margin-top: auto;
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            padding: 30px 0;
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
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; /* 10% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 600px;
            text-align: left;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
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
                        <h1>PUP Qualifying Exam Portal</h1>
                        <p>Student Registration</p>
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
            <div class="content-header">
                <h2><i class="fas fa-clipboard-list"></i> Qualifying Examination Registration</h2>
                <p>Complete the form below to register for the CCIS Qualifying Examination</p>
            </div>

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
                <div class="header-logo">
                    <img src="img/Logo.png" alt="PUP Logo" class="ccislogo">
                    <h1>STREAMS Student Registration and Document Submission</h1>
                    <img src="img/Logo.png" alt="PUP CCIS Logo" class="puplogo">
                </div>

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
                <form id="multi-step-form" action="qualiexam_registerBack.php" method="POST" enctype="multipart/form-data">
                    <div class="step active">
                        <h2>Student Type Selection</h2>
                        <div class="form-field">
                            <label for="student_type">Student Type</label>
                            <select id="student_type" name="student_type" required onchange="handleStudentTypeChange()">
                                <option value="">-- Select Student Type --</option>
                                <option value="transferee">Transferee</option>
                                <option value="shiftee">Shiftee</option>
                                <option value="ladderized">Ladderized</option>
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
                                <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>">
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
                                <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" required>
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
                                    <option value="">--Select Previous University--</option>
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
                                    <option value="">--Select Previous Program--</option>
                                </select>
                            </div>

                            <div class="form-field" id="program-apply-field">
                                <label for="desired_program">Name of Program Applying To</label>
                                <select id="desired_program" name="desired_program" required>
                                    <option value="">--Select Desired Program--</option>
                                    <option value="BSCS">Bachelor of Science in Computer Science (BSCS)</option>
                                    <option value="BSIT">Bachelor of Science in Information Technology (BSIT)</option>
                                </select>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const previousProgramSelect = document.getElementById("previous_program");
    const yearLevelField = document.getElementById("year-level-field");
    const stepIndicators = document.querySelectorAll('.step-indicator');
    
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
                previousProgramSelect.innerHTML = `<option value="">--Select Previous Program--</option>`;
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

    // This function should be defined here to ensure it's in the global scope
    window.validateStep = function() {
        const activeStep = document.querySelector('.step.active');
        const inputs = activeStep.querySelectorAll('input, select');
        let isValid = true;
        
        // Debug which step we're validating
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
            nextStep(); // Move to the next step if valid
        } else {
            console.log('Step', currentIndex + 1, 'has validation errors');
        }
    };

    // This function should be defined here to ensure it's in the global scope
    window.nextStep = function() {
        const currentStep = document.querySelector('.step.active');
        const nextStep = currentStep.nextElementSibling;

        if (nextStep) {
            // Update step indicators
            const currentIndex = Array.from(document.querySelectorAll('.step')).indexOf(currentStep);
            stepIndicators[currentIndex].classList.remove('active');
            stepIndicators[currentIndex].classList.add('completed');
            stepIndicators[currentIndex + 1].classList.add('active');
            
            // Change visible step
            currentStep.classList.remove('active');
            nextStep.classList.add('active');
            
            // Scroll to top of form
            document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
            
            // Debug log to check step transition
            console.log('Moving from step', currentIndex + 1, 'to step', currentIndex + 2);
        } else {
            console.log('No next step found');
        }
    };

    // This function should be defined here to ensure it's in the global scope
    window.prevStep = function() {
        const currentStep = document.querySelector('.step.active');
        const prevStep = currentStep.previousElementSibling;

        if (prevStep) {
            // Update step indicators
            const currentIndex = Array.from(document.querySelectorAll('.step')).indexOf(currentStep);
            stepIndicators[currentIndex].classList.remove('active');
            stepIndicators[currentIndex - 1].classList.remove('completed');
            stepIndicators[currentIndex - 1].classList.add('active');
            
            // Change visible step
            currentStep.classList.remove('active');
            prevStep.classList.add('active');
            
            // Scroll to top of form
            document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
        }
    };

    // Handle student type change logic
    window.handleStudentTypeChange = function() {
        const studentType = document.getElementById('student_type').value;
        const previousProgramSelect = document.getElementById("previous_program");

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
                    previousProgramSelect.innerHTML = '<option value="">--Select Previous Program--</option>';
                    
                    // Add all programs except DICT for non-ladderized students
                    data.forEach(course => {
                        // Skip DICT for non-ladderized students to avoid confusion
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
    };

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
</script>

</body>
</html>
