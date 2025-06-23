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
    <link rel="stylesheet" href="assets/css/registerForm.css">
    <style>
        /* Multi-step form specific styles */
        .step {
            display: none;
            animation: fadeIn 0.5s;
        }
        
        .step.active {
            display: block !important;
        }
        
        /* Progress indicator */
        .progress-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
            padding: 0 20px;
        }
        
        .progress-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 20px;
            right: 20px;
            height: 2px;
            background-color: var(--gray);
            z-index: 1;
        }
        
        .step-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--text-dark);
            position: relative;
            z-index: 2;
        }
        
        .step-indicator.active {
            background-color: var(--primary);
            color: white;
            transform: scale(1.2);
            transition: all 0.3s ease;
        }
        
        .step-indicator.completed {
            background-color: var(--success);
            color: white;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            position: relative;
            animation: slideIn 0.3s ease;
        }
        
        /* OCR Preview specific styles */
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
            border-bottom: 1px solid var(--gray);
            position: sticky;
            top: 0;
        }

        #ocrPreviewModal .modal-footer {
            padding: 20px;
            background: white;
            border-top: 1px solid var(--gray);
            position: sticky;
            bottom: 0;
        }
        
        #ocrResultsContent {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* Animation keyframes */
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
        
        /* Document upload fields */
        .form-field input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed var(--gray);
            border-radius: 8px;
            background-color: var(--gray-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-field input[type="file"]:hover {
            border-color: var(--primary);
            background-color: rgba(117, 52, 58, 0.05);
        }
        
        .form-field small {
            display: block;
            margin-top: 5px;
            color: var(--text-dark);
            opacity: 0.7;
            font-size: 0.85rem;
        }

        /* Checkbox container */
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .checkbox-container label {
            margin: 0;
            cursor: pointer;
        }

        /* Error and debug messages */
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #c62828;
        }
    </style>
</head>

<body>
    <!-- Loading Spinner -->
    <div id="loading-spinner"></div>

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

            <!-- Info Modal -->
            <div id="infoModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>CCIS Qualifying Examination Information</h2>
                        <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
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
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert <?php echo ($_SESSION['message_type'] === 'error') ? 'alert-danger' : 'alert-success'; ?>" role="alert">
                    <?php 
                        echo htmlspecialchars($_SESSION['message']); 
                        // Clear the message after displaying
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['registration_status'])): ?>
                <div class="alert <?php 
                    $alertClass = 'alert-success';
                    switch ($_SESSION['registration_status']) {
                        case 'ocr_failed':
                        case 'needs_review':
                            $alertClass = 'alert-warning';
                            break;
                        case 'rejected':
                            $alertClass = 'alert-danger';
                            break;
                    }
                    echo $alertClass;
                ?>" role="alert">
                    <?php 
                    switch ($_SESSION['registration_status']) {
                        case 'ocr_failed':
                            echo "Your registration has been saved, but we couldn't automatically process your documents. An admin will review your application manually.";
                            break;
                        case 'needs_review':
                            echo "Your registration has been saved and will be reviewed manually by an administrator.";
                            break;
                        case 'pending':
                            echo "Your registration has been completed successfully and is pending review.";
                            break;
                        case 'approved':
                            echo "Your registration has been approved!";
                            break;
                        case 'rejected':
                            echo "Your registration has been rejected. Please contact the administrator for more information.";
                            break;
                        default:
                            echo "Registration completed successfully.";
                    }
                    // Clear the registration status after displaying
                    unset($_SESSION['registration_status']);
                    ?>
                </div>
            <?php endif; ?>

            <section class="form-section">
                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="step-indicator active" data-step="1" data-title="Student Type">1</div>
                    <div class="step-indicator" data-step="2" data-title="Personal Info">2</div>
                    <div class="step-indicator" data-step="3" data-title="Contact">3</div>
                    <div class="step-indicator" data-step="4" data-title="Academic">4</div>
                    <div class="step-indicator" data-step="5" data-title="Documents">5</div>
                </div>

                <!-- Form -->
                <form id="multi-step-form" action="qualiexam_registerBack.php" method="POST" enctype="multipart/form-data" onsubmit="return submitForm(event)">
                    <input type="hidden" name="action" value="final_submit">
                    <input type="hidden" name="subjects" id="subjects_data">
                    <div class="step active">
                        <div class="section-container">
                            <div class="section-header">
                                <h2>Student Type Selection</h2>
                                <div class="section-note">
                                    <i class="fas fa-info-circle"></i>
                                    Please select your student type carefully. This will determine the required information and documents needed for your application.
                                </div>
                            </div>
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
                                <div class="field-note">
                                    <strong>Note:</strong>
                                    - Transferee: Student from another university
                                    - Shiftee: Student changing course within PUP
                                    - Ladderized: DICT graduate proceeding to BSCS/BSIT
                                </div>
                            </div>
                            <div class="buttons">
                                <button type="button" class="btn btn-next">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="step">
                        <div class="section-container">
                            <div class="section-header">
                                <h2>Personal Information</h2>
                                <div class="section-note">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Please provide your personal information exactly as it appears on your official documents.</span>
                                </div>
                            </div>

                            <div class="personal-info-grid">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" required class="form-control" 
                                           value="<?php echo htmlspecialchars($student['lastname'] ?? ''); ?>"
                                           placeholder="Enter last name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="first_name">Given Name</label>
                                    <input type="text" id="first_name" name="first_name" required class="form-control"
                                           value="<?php echo htmlspecialchars($student['firstname'] ?? ''); ?>"
                                           placeholder="Enter first name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="middle_name">Middle Name (Optional)</label>
                                    <input type="text" id="middle_name" name="middle_name" class="form-control"
                                           value="<?php echo htmlspecialchars($student['middlename'] ?? ''); ?>"
                                           placeholder="Enter middle name">
                                    <div class="field-note">Leave blank if none</div>
                                </div>

                                <div class="form-group">
                                    <label for="dob">Date of Birth</label>
                                    <input type="date" 
                                           id="dob" 
                                           name="dob" 
                                           required 
                                           max="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>">
                                    <div class="field-note">Select your date of birth from the calendar</div>
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" required>
                                        <option value="" disabled <?php echo !isset($student['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                                        <option value="Male" <?php echo (isset($student['gender']) && $student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($student['gender']) && $student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($student['gender']) && $student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="field-note">Select your gender identity</div>
                                </div>
                            </div>

                            <div class="navigation-buttons">
                                <button type="button" class="btn btn-previous" onclick="prevStep()">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </button>
                                <button type="button" class="btn btn-next" onclick="validateStep()">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Contact Details -->
                    <div class="step">
                        <div class="contact-section">
                            <div class="section-header">
                                <h2>Contact Information</h2>
                                <div class="section-note">
                                    <i class="fas fa-info-circle"></i>
                                    Please provide accurate contact information. Important updates about your application will be sent to these contact details.
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           required 
                                           placeholder="Enter your email address"
                                           value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                                    <div class="field-note">Use an active email address that you check regularly</div>
                                </div>

                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="tel" 
                                           id="contact_number" 
                                           name="contact_number" 
                                           required 
                                           pattern="[0-9]{11}" 
                                           placeholder="09123456789"
                                           value="<?php echo htmlspecialchars($student['contact_number'] ?? ''); ?>">
                                    <div class="field-note">Enter your 11-digit mobile number</div>
                                </div>

                                <div class="form-group full-width">
                                    <label for="address">Complete Address</label>
                                    <input type="text" 
                                           id="address" 
                                           name="address" 
                                           required 
                                           placeholder="Enter your complete residential address"
                                           value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                                    <div class="field-note">Provide your complete current residential address</div>
                                </div>
                            </div>
                            <div class="navigation-buttons">
                                <button type="button" class="btn btn-previous" onclick="prevStep()">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </button>
                                <button type="button" class="btn btn-next" onclick="validateStep()">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Academic Details -->
                    <div class="step">
                        <div class="section-container">
                            <div class="section-header">
                                <h2>Academic Information</h2>
                                <div class="section-note">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Provide accurate information about your academic background.</span>
                                </div>
                            </div>
                            <div class="academic-info-grid">
                                <div class="form-group">
                                    <label for="year_level">Years of Residency</label>
                                    <input type="number" 
                                           id="year_level" 
                                           name="year_level" 
                                           min="1" 
                                           max="5" 
                                           required 
                                           placeholder="Enter number of years"
                                           value="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>">
                                    <div class="field-note">Years in previous program</div>
                                </div>

                                <div class="form-group">
                                    <label for="previous_school">Previous School</label>
                                    <select id="previous_school" name="previous_school" required class="form-control editable-select" onchange="handleEditableSelect(this)">
                                        <option value="" disabled selected>Select Previous University</option>
                                        <?php
                                        $query = "SELECT university_code, university_name FROM universities ORDER BY university_name";
                                        $result = $conn->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($row['university_code']) . '">' . 
                                                 htmlspecialchars($row['university_name']) . '</option>';
                                        }
                                        ?>
                                        <option value="Other">Other (Please specify)</option>
                                    </select>
                                    <div class="field-note">Select or enter your previous educational institution</div>
                                </div>

                                <div class="form-group">
                                    <label for="previous_program">Previous Program</label>
                                    <select id="previous_program" name="previous_program" required class="form-control">
                                        <option value="" disabled selected>Select Previous Program</option>
                                        <?php
                                        // Fetch programs from the database
                                        $query = "SELECT program_code, program_name FROM programs ORDER BY program_name";
                                        $result = $conn->query($query);
                                        
                                        if ($result) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($row['program_code']) . '">' . 
                                                     htmlspecialchars($row['program_name']) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="">Error loading programs</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="field-note">Select your previous program of study</div>
                                </div>

                                <div class="form-group">
                                    <label for="desired_program">Program Applying To</label>
                                    <select id="desired_program" name="desired_program" required class="form-control">
                                        <option value="" disabled selected>Select Desired Program</option>
                                        <?php
                                        $query = "SELECT program_code, program_name FROM programs WHERE is_accepting_transfer = 1 ORDER BY program_name";
                                        $result = $conn->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($row['program_code']) . '">' . 
                                                 htmlspecialchars($row['program_name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="field-note">Select the program you wish to transfer to</div>
                                </div>
                            </div>

                            <div class="navigation-buttons">
                                <button type="button" class="btn btn-previous" onclick="prevStep()">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </button>
                                <button type="button" class="btn btn-next" onclick="validateStep()">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Document Upload -->
                    <div class="step">
                        <div class="section-container">
                            <div class="section-header">
                                <h2>Document Submission</h2>
                                <div class="section-note">
                                    <i class="fas fa-info-circle"></i>
                                    Please upload clear, legible scanned copies or photos of your documents. All documents must be in PDF, JPG, or PNG format and must not exceed 5MB each.
                                </div>
                            </div>
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
                                <button type="button" class="btn btn-previous" onclick="prevStep()">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </button>
                                <button type="submit" class="btn btn-next">
                                    <i class="fas fa-check"></i> Submit Application
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

            <div class="decorative-container">
                <img src="assets/images/education-illustration.svg" alt="Education Illustration" class="decorative-image">
                <div class="decorative-text">
                    <h2>Welcome to CCIS Qualifying Examination</h2>
                    <p>Take the next step in your academic journey. Complete your registration to proceed with the qualifying examination for the College of Computing and Information Sciences.</p>
                </div>
            </div>
        </div>
    </div>
    

    <!-- Add this before the closing body tag -->

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
    const yearLevelField = document.getElementById("year_level");

    // First, enable all options
    Array.from(previousProgramSelect.options).forEach(option => {
        option.style.display = '';
    });

    if (studentType === 'ladderized') {
        // For ladderized, only show DICT program
        Array.from(previousProgramSelect.options).forEach(option => {
            if (!option.text.includes('DICT') && option.value !== '') {
                option.style.display = 'none';
            }
        });
        
        // Set DICT as selected if available
        const dictOption = Array.from(previousProgramSelect.options)
            .find(option => option.text.includes('DICT'));
        if (dictOption) {
            dictOption.selected = true;
        }

        // Hide year level field if it exists
        if (yearLevelField) {
            yearLevelField.style.display = 'none';
            yearLevelField.value = '1'; // Set default value
        }
    } else {
        // For other types, show all programs except DICT
        Array.from(previousProgramSelect.options).forEach(option => {
            if (option.text.includes('DICT')) {
                option.style.display = 'none';
            }
        });

        // Reset selection
        previousProgramSelect.value = '';

        // Show year level field if it exists
        if (yearLevelField) {
            yearLevelField.style.display = 'block';
        }
    }
}

function validateStep() {
    const activeStep = document.querySelector('.step.active');
    const stepIndex = Array.from(document.querySelectorAll('.step')).indexOf(activeStep);
    let isValid = true;

    // Get all required inputs in the current step
    const requiredInputs = activeStep.querySelectorAll('input[required], select[required]');
    
    // Clear any existing error messages
    const existingErrors = activeStep.querySelectorAll('.error-message');
    existingErrors.forEach(error => error.remove());

    // Validate based on which step we're on
    switch(stepIndex) {
        case 0: // Student Type
            const studentType = document.getElementById('student_type');
            if (!studentType.value) {
                isValid = false;
                showError(studentType, 'Please select a student type');
            }
            break;

        case 1: // Personal Information
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    showError(input, `${input.previousElementSibling.textContent} is required`);
                }
            });
            break;

        case 2: // Contact Information
            const email = document.getElementById('email');
            const contact = document.getElementById('contact_number');
            const address = document.getElementById('address');

            if (!email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                isValid = false;
                showError(email, 'Please enter a valid email address');
            }
            if (!contact.value.match(/^[0-9]{11}$/)) {
                isValid = false;
                showError(contact, 'Please enter a valid 11-digit contact number');
            }
            if (!address.value.trim()) {
                isValid = false;
                showError(address, 'Address is required');
            }
            break;

        case 3: // Academic Information
            const previousSchool = document.getElementById('previous_school');
            const previousProgram = document.getElementById('previous_program');
            const desiredProgram = document.getElementById('desired_program');
            const yearLevel = document.getElementById('year_level');

            if (!previousSchool.value) {
                isValid = false;
                showError(previousSchool, 'Please select your previous school');
            }
            if (!previousProgram.value) {
                isValid = false;
                showError(previousProgram, 'Please select your previous program');
            }
            if (!desiredProgram.value) {
                isValid = false;
                showError(desiredProgram, 'Please select your desired program');
            }
            // Only validate year level if not ladderized
            if (document.getElementById('student_type').value !== 'ladderized') {
                if (!yearLevel.value || yearLevel.value < 1 || yearLevel.value > 5) {
                    isValid = false;
                    showError(yearLevel, 'Please enter a valid year level (1-5)');
                }
            }
            break;

        case 4: // Document Upload
            const documentType = document.querySelector('input[name="document_type"]:checked');
            const schoolId = document.getElementById('school_id');

            if (!documentType) {
                isValid = false;
                showError(document.querySelector('.document-choice'), 'Please select a document type');
            }

            if (!schoolId.files.length) {
                isValid = false;
                showError(schoolId, 'Please upload your school ID');
            }

            // Check for TOR or Grades based on selection
            if (documentType && documentType.value === 'tor') {
                const tor = document.getElementById('tor');
                if (!tor.files.length) {
                    isValid = false;
                    showError(tor, 'Please upload your Transcript of Records');
                }
            } else if (documentType && documentType.value === 'grades') {
                const grades = document.getElementById('copy_grades');
                if (!grades.files.length) {
                    isValid = false;
                    showError(grades, 'Please upload your Copy of Grades');
                }
            }
            break;
    }

    if (isValid) {
        nextStep();
    }

    return isValid;
}

function showError(element, message) {
    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = 'red';
    errorDiv.style.fontSize = '0.8em';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;

    // Insert error message after the element
    element.parentNode.insertBefore(errorDiv, element.nextSibling);

    // Highlight the input
    element.style.borderColor = 'red';
    element.addEventListener('input', function() {
        // Remove error styling when user starts typing
        this.style.borderColor = '';
        const errorMsg = this.parentNode.querySelector('.error-message');
        if (errorMsg) {
            errorMsg.remove();
        }
    });
}

function nextStep() {
    const currentStep = document.querySelector('.step.active');
    const nextStep = currentStep.nextElementSibling;
    const stepIndicators = document.querySelectorAll('.step-indicator');
    const currentIndex = Array.from(document.querySelectorAll('.step')).indexOf(currentStep);

    if (nextStep) {
        // Update step indicators
        stepIndicators[currentIndex].classList.remove('active');
        stepIndicators[currentIndex].classList.add('completed');
        stepIndicators[currentIndex + 1].classList.add('active');

        // Change visible step
        currentStep.classList.remove('active');
        nextStep.classList.add('active');

        // Scroll to top of form
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
    }
}

function prevStep() {
    const currentStep = document.querySelector('.step.active');
    const prevStep = currentStep.previousElementSibling;
    const stepIndicators = document.querySelectorAll('.step-indicator');
    const currentIndex = Array.from(document.querySelectorAll('.step')).indexOf(currentStep);

    if (prevStep) {
        // Update step indicators
        stepIndicators[currentIndex].classList.remove('active');
        stepIndicators[currentIndex - 1].classList.remove('completed');
        stepIndicators[currentIndex - 1].classList.add('active');

        // Change visible step
        currentStep.classList.remove('active');
        prevStep.classList.add('active');

        // Scroll to top of form
        document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
    }
}

// Initialize when the document loads
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all next buttons
    document.querySelectorAll('.btn-next').forEach(button => {
        button.addEventListener('click', validateStep);
    });

    // Add event listeners to all previous buttons
    document.querySelectorAll('.btn-previous').forEach(button => {
        button.addEventListener('click', prevStep);
    });

    // Initialize the first step
    const firstStep = document.querySelector('.step');
    const firstIndicator = document.querySelector('.step-indicator');
    if (firstStep && firstIndicator) {
        firstStep.classList.add('active');
        firstIndicator.classList.add('active');
    }

    // Add event listener for student type changes
    const studentTypeSelect = document.getElementById('student_type');
    if (studentTypeSelect) {
        studentTypeSelect.addEventListener('change', handleStudentTypeChange);
        // Also handle initial state
        handleStudentTypeChange();
    }
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
        
        if (!response.ok) {
            throw new Error(`Server returned status code ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log("Raw OCR response:", responseText);
        
        if (!responseText || responseText.trim() === '') {
            throw new Error("Server returned an empty response");
        }
        
        // Parse the response
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error("JSON parse error:", parseError);
            throw new Error("Invalid server response format");
        }
        
        // Re-enable submit buttons
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            button.disabled = false;
            button.textContent = 'Submit Application';
        });
        
        if (result.error) {
            ocrContent.innerHTML = `<div class="error-message">${result.error}</div>`;
            return;
        }
        
        if (!result.subjects || result.subjects.length === 0) {
            ocrContent.innerHTML = '<div class="error-message">No subjects could be extracted from the documents. Please check your uploads.</div>';
            return;
        }
        
        // Store the subjects data
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
