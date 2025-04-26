<?php
// Start session at the very beginning of the file
session_start();

// Add logging function at the top of the file
function logRegistrationActivity($message, $data = null) {
    $log_file = 'logs/registration_log.txt';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $student_id = isset($_SESSION['stud_id']) ? $_SESSION['stud_id'] : 'No ID';
    $log_message = "[{$timestamp}] Student ID: {$student_id} - {$message}";
    
    if ($data !== null) {
        $log_message .= "\nData: " . print_r($data, true);
    }
    
    $log_message .= "\n" . str_repeat('-', 80) . "\n";
    
    // Append to log file
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Log the start of registration process
logRegistrationActivity('Registration process started');

// Check if user is logged in
if (!isset($_SESSION['stud_id'])) {
    logRegistrationActivity('User not logged in - Redirecting to login');
    header("Location: stud_register.php");
    exit();
}

// Database connection
require_once 'config/config.php';

// Log database connection status
if ($conn->connect_error) {
    logRegistrationActivity('Database connection failed', ['error' => $conn->connect_error]);
} else {
    logRegistrationActivity('Database connection successful');
}

// Fetch student information
$stud_id = $_SESSION['stud_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE stud_id = ?");
$stmt->bind_param("i", $stud_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Log student data retrieval
logRegistrationActivity('Student data retrieved', ['student_id' => $stud_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIS Qualifying Examination Registration</title> 

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/qualiexam.css">
    
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
            

            <div id="infoModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeInfoModal()">&times;</span>
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
                                <option value="transferee" <?php echo (strtolower($student['student_type']) === 'transferee') ? 'selected' : ''; ?>>Transferee</option>
                                <option value="shiftee" <?php echo (strtolower($student['student_type']) === 'shiftee') ? 'selected' : ''; ?>>Shiftee</option>
                                <option value="ladderized" <?php echo (strtolower($student['student_type']) === 'ladderized') ? 'selected' : ''; ?>>Ladderized</option>
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
                                <input type="text" id="previous_school" name="previous_school" required>
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
                                    <option value="">Select Desired Program</option>
                                    <option value="BSCS">Bachelor of Science in Computer Science (BSCS)</option>
                                    <option value="BSIT">Bachelor of Science in Information Technology (BSIT)</option>
                                </select>
                            </div>

                            <div class="form-field" id="grading-system-field">
                                <label for="grading_system">Grading System Used</label>
                                <div class="grading-select-group">
                                    <div class="select-container">
                                        <select id="grading_system" name="grading_system" required onchange="handleGradingSystemChange(this.value)">
                                            <option value="">Select Grading System</option>
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

                            <!-- Grading System Preview Modal -->
                            <div id="gradingPreviewModal" class="modal grading-preview-modal">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="preview-title">Grading System Details</h4>
                                        <button type="button" class="close-btn" onclick="closeGradingPreview()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="grading-system-preview">
                                            <div class="preview-content">
                                                <!-- Regular Grades Section -->
                                                <div class="grades-section">
                                                    <h5 class="section-title">
                                                        <i class="fas fa-chart-line"></i>
                                                        Regular Grades
                                                    </h5>
                                                    <div class="grade-list" id="regular-grades-container">
                                                        <!-- Regular grades will be populated here -->
                                                    </div>
                                                </div>
                                                
                                                <!-- Special Grades Section -->
                                                <div class="grades-section">
                                                    <h5 class="section-title">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        Special Grades
                                                    </h5>
                                                    <div class="grade-list" id="special-grades-container">
                                                        <!-- Special grades will be populated here -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                        <input type="hidden" name="process_ocr" value="1">
                        <div class="form-group">
                            <div class="form-field document-upload-field" id="academic-docs-container">
                                <!-- TOR Upload Section -->
                                <div id="tor-upload-section">
                                    <label for="academic_document_tor" class="main-label">
                                        Scanned Transcript of Records (TOR)
                                    </label>
                                    <div class="upload-container">
                                        <input type="file" id="academic_document_tor" name="academic_document" class="academic-file-input" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="document-note">Please ensure your TOR is complete and officially signed.<br>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                                    </div>
                                </div>

                                <div class="checkbox-option">
                                    <input type="checkbox" id="useGrades" name="use_grades">
                                    <label for="useGrades">I don't have my TOR yet. I will submit my Copy of Grades instead.</label>
                                </div>

                                <!-- COG Upload Section -->
                                <div id="cog-upload-section" style="display: none;">
                                    <label for="academic_document_cog" class="main-label">
                                        Scanned Copy of Grades (COG)
                                    </label>
                                    <div class="upload-container">
                                        <input type="file" id="academic_document_cog" name="academic_document" class="academic-file-input" accept=".pdf,.jpg,.jpeg,.png" disabled>
                                        <small class="document-note">Please ensure your Copy of Grades is complete and officially signed.<br>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field document-upload-field">
                                <label for="school_id" class="main-label">Scanned School ID</label>
                                <div class="upload-container">
                                    <input type="file" id="school_id" name="school_id" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="document-note">Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <!-- OCR Preview Modal -->
                        <div id="ocrPreviewModal" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Verify Scanned Grades</h3>
                                    <button type="button" class="close-modal" onclick="hideOCRPreview()">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <div id="loadingGrades" class="loading-container">
                                        <div class="loading-spinner"></div>
                                        <p class="loading-text">Processing document, please wait...</p>
                                    </div>
                                    <div id="gradesTable" style="display: none;">
                                        <div class="preview-controls">
                                            <p class="preview-instructions">
                                                <i class="fas fa-info-circle"></i> 
                                                Please review the extracted grades below. Click on any cell to edit if needed.
                                            </p>
                                            <button type="button" class="add-row-btn" onclick="addNewGradeRow()">
                                                <i class="fas fa-plus"></i> Add New Row
                                            </button>
                                        </div>
                                        <div class="table-container">
                                            <table class="grades-preview-table">
                                                <thead>
                                                    <tr>
                                                        <th>Subject Code</th>
                                                        <th>Description</th>
                                                        <th>Units</th>
                                                        <th>Grade</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="gradesPreviewBody">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div id="ocrError" style="display: none;">
                                        <p class="error-message">Error processing document. Please try again.</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="cancel-btn" onclick="cancelOCRPreview()">Cancel</button>
                                    <button type="button" class="confirm-btn" onclick="confirmGrades()">Confirm Grades</button>
                                </div>
                            </div>
                        </div>

                        <div class="buttons">
                            <button type="button" class="prev-btn" onclick="prevStep()">Previous</button>
                            <button type="submit" name="process_ocr" class="nxt-btn">Submit Application</button>
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
    const form = document.getElementById('multi-step-form');
    const stepIndicators = document.querySelectorAll('.step-indicator');
    const previousProgramSelect = document.getElementById("previous_program");
    const yearLevelField = document.getElementById("year-level-field");
    const modal = document.getElementById('ocrPreviewModal');
    
    // Add OCR Loading UI
    const ocrLoadingStyles = document.createElement('style');
    ocrLoadingStyles.textContent = `
        .ocr-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .ocr-loading-container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
        }

        .ocr-spinner {
            width: 70px;
            height: 70px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #800000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        .ocr-loading-text {
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .ocr-loading-steps {
            text-align: left;
            margin: 1rem auto;
            max-width: 300px;
        }

        .ocr-step {
            display: flex;
            align-items: center;
            margin: 0.5rem 0;
            padding: 0.5rem;
            border-radius: 4px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .ocr-step.active {
            background: #fff3cd;
        }

        .ocr-step.completed {
            background: #d4edda;
        }

        .ocr-step-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .ocr-step-text {
            flex-grow: 1;
            font-size: 0.9rem;
        }
    `;
    document.head.appendChild(ocrLoadingStyles);

    // Create and append OCR loading overlay
    const ocrLoadingOverlay = document.createElement('div');
    ocrLoadingOverlay.className = 'ocr-loading-overlay';
    ocrLoadingOverlay.innerHTML = `
        <div class="ocr-loading-container">
            <div class="ocr-spinner"></div>
            <div class="ocr-loading-text">Processing Your Documents</div>
            <div class="ocr-loading-steps">
                <div class="ocr-step" data-step="upload">
                    <div class="ocr-step-icon">📤</div>
                    <div class="ocr-step-text">Uploading documents...</div>
                </div>
                <div class="ocr-step" data-step="process">
                    <div class="ocr-step-icon">🔍</div>
                    <div class="ocr-step-text">Extracting information...</div>
                </div>
                <div class="ocr-step" data-step="analyze">
                    <div class="ocr-step-icon">📊</div>
                    <div class="ocr-step-text">Analyzing content...</div>
                </div>
                <div class="ocr-step" data-step="complete">
                    <div class="ocr-step-icon">✅</div>
                    <div class="ocr-step-text">Preparing results...</div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(ocrLoadingOverlay);

    // Function to safely handle step transitions
    function handleStepTransition(fromStep, toStep) {
        try {
            const steps = document.querySelectorAll('.step');
            const currentIndex = Array.from(steps).indexOf(fromStep);
            const targetIndex = Array.from(steps).indexOf(toStep);
            
            // Update indicators
            stepIndicators[currentIndex].classList.remove('active');
            if (targetIndex > currentIndex) {
                stepIndicators[currentIndex].classList.add('completed');
            } else {
                stepIndicators[currentIndex].classList.remove('completed');
            }
            stepIndicators[targetIndex].classList.add('active');
            
            // Update step visibility
            fromStep.classList.remove('active');
            toStep.classList.add('active');
            
            // Scroll to top
            document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
            
            return true;
        } catch (error) {
            console.error('Step transition error:', error);
            return false;
        }
    }

    // Function to populate the previous program dropdown
    async function populatePreviousProgramSelect() {
        try {
            previousProgramSelect.innerHTML = '<option value="">Loading programs...</option>';
            previousProgramSelect.disabled = true;
            
            const response = await fetch('data/courses.json');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (!Array.isArray(data)) {
                throw new Error('Invalid data format');
            }

            // Clear and add default option
            previousProgramSelect.innerHTML = '<option value="">--Select Previous Program--</option>';
            
            // Sort programs alphabetically
            data.sort().forEach(course => {
                if (course && typeof course === 'string') {
                    const option = document.createElement('option');
                    option.value = course;
                    option.textContent = course;
                    previousProgramSelect.appendChild(option);
                }
            });

            previousProgramSelect.disabled = false;
            
            // If student type is already selected, handle it
            handleStudentTypeChange(); 
            
        } catch (error) {
            console.error('Error loading programs:', error);
            previousProgramSelect.innerHTML = `
                <option value="">Error loading programs</option>
            `;
            previousProgramSelect.disabled = true;
            
            // Add retry button
            const retryButton = document.createElement('button');
            retryButton.type = 'button';
            retryButton.className = 'retry-btn';
            retryButton.textContent = 'Retry Loading Programs';
            retryButton.style.cssText = 'margin-top: 10px; padding: 8px 16px; background-color: #800000; color: white; border: none; border-radius: 4px; cursor: pointer;';
            
            // Remove existing retry button if any
            const existingRetry = document.querySelector('.retry-btn');
            if (existingRetry) {
                existingRetry.remove();
            }
            
            // Add the retry button after the select element
            previousProgramSelect.parentNode.appendChild(retryButton);
            
            // Add click handler
            retryButton.onclick = () => {
                retryButton.remove();
                populatePreviousProgramSelect();
            };
        }
    }
    
    // Handle student type changes
    window.handleStudentTypeChange = function() {
        const studentType = document.getElementById('student_type').value;
        
        if (studentType === 'ladderized') {
            yearLevelField.style.display = 'none';
            previousProgramSelect.innerHTML = `
                <option value="Diploma in Information Communication Technology (DICT)" selected>
                    Diploma in Information Communication Technology (DICT)
                </option>
            `;
            previousProgramSelect.disabled = true;
        } else {
            yearLevelField.style.display = 'block';
            previousProgramSelect.disabled = false;
            
            // Only repopulate if it's not already populated
            if (previousProgramSelect.options.length <= 1) {
                populatePreviousProgramSelect();
            }
        }
    };

    // Function to validate each step
    window.validateStep = function() {
        const activeStep = document.querySelector('.step.active');
        const inputs = activeStep.querySelectorAll('input, select');
        let isValid = true;

        inputs.forEach(input => {
            // Skip year_level validation for ladderized students
            if (input.id === 'year_level' && 
                document.getElementById('student_type').value === 'ladderized') {
                return;
            }
            
            if (input.hasAttribute('required') && !input.checkValidity()) {
                isValid = false;
                input.reportValidity();
            }
        });

        if (isValid) {
            nextStep();
        }
    };

    // Next step navigation
    window.nextStep = function() {
        const currentStep = document.querySelector('.step.active');
        const nextStep = currentStep.nextElementSibling;

        if (nextStep && nextStep.classList.contains('step')) {
            handleStepTransition(currentStep, nextStep);
        }
    };

    // Previous step navigation
    window.prevStep = function() {
        const currentStep = document.querySelector('.step.active');
        const prevStep = currentStep.previousElementSibling;

        if (prevStep && prevStep.classList.contains('step')) {
            handleStepTransition(currentStep, prevStep);
        }
    };

    // Update the form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        logToServer('form_submission_started');
        
        const formData = new FormData(form);
        const useGrades = document.getElementById('useGrades').checked;
        const academicFileInput = useGrades ? document.getElementById('academic_document_cog') : document.getElementById('academic_document_tor');
        const schoolIdFileInput = document.getElementById('school_id');
        
        // Validate file presence
        if (!academicFileInput.files[0] || !schoolIdFileInput.files[0]) {
            logToServer('document_validation_failed', {
                academic_doc: !!academicFileInput.files[0],
                school_id: !!schoolIdFileInput.files[0]
            });
            showError('Please select all required documents');
            return;
        }

        // Validate file types
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        const academicFile = academicFileInput.files[0];
        const schoolIdFile = schoolIdFileInput.files[0];

        if (!allowedTypes.includes(academicFile.type)) {
            showError(useGrades ? 'Copy of Grades must be a PDF, JPG, or PNG file' : 'Academic document must be a PDF, JPG, or PNG file');
            return;
        }

        if (!allowedTypes.includes(schoolIdFile.type)) {
            showError('School ID must be a PDF, JPG, or PNG file');
            return;
        }

        // Validate file sizes (5MB limit)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (academicFile.size > maxSize) {
            showError(useGrades ? 'Copy of Grades must be less than 5MB' : 'Academic document must be less than 5MB');
            return;
        }

        if (schoolIdFile.size > maxSize) {
            showError('School ID must be less than 5MB');
            return;
        }
        
        showLoading(true); // Show OCR loading
        updateOCRStep('upload');
        logToServer('ocr_process_started');
        
        // Add document type to formData
        formData.append('document_type', useGrades ? 'cog' : 'tor');
        formData.append('process_ocr', '1');
        
        try {
            const response = await fetch('qualiexam_registerBack.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Invalid response format from server");
            }
            
            const jsonData = await response.json();
            logToServer('backend_response_received', {
                success: jsonData.success,
                has_subjects: !!jsonData.data?.subjects
            });
            
            if (!jsonData.success) {
                throw new Error(jsonData.error || 'OCR processing failed');
            }

            if (!jsonData.data?.subjects || jsonData.data.subjects.length === 0) {
                throw new Error('No grades could be extracted from the document. Please ensure you uploaded a valid ' + (useGrades ? 'Copy of Grades' : 'Transcript of Records'));
            }

            // Store file paths in session storage
            if (jsonData.data.files) {
                sessionStorage.setItem('academic_document_path', jsonData.data.files.academic_document);
                sessionStorage.setItem('school_id_path', jsonData.data.files.school_id);
                sessionStorage.setItem('document_type', useGrades ? 'cog' : 'tor');
            }
            
            hideLoading(true);
            displayOCRResults(jsonData.data.subjects);
            showOCRPreview();
            logToServer('ocr_preview_displayed');
            
        } catch (error) {
            hideLoading(true);
            logToServer('error_occurred', {
                error_message: error.message
            });
            console.error('Error:', error);
            showError(error.message || 'An error occurred while processing your documents.');
        }
    });

    // Function to display OCR results in the preview modal
    function displayOCRResults(subjects) {
        const tableBody = document.querySelector('#gradesPreviewBody');
        const loadingDiv = document.getElementById('loadingGrades');
        const gradesTable = document.getElementById('gradesTable');
        
        if (!tableBody || !loadingDiv || !gradesTable) return;
        
        tableBody.innerHTML = '';
        subjects.forEach(subject => {
            const row = createGradeRow(subject);
            tableBody.appendChild(row);
        });
        
        loadingDiv.style.display = 'none';
        gradesTable.style.display = 'block';
    }

    // Function to create a new grade row
    function createGradeRow(subject = { subject_code: '', subject_description: '', units: '', grade: '' }) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td contenteditable="true">${subject.subject_code || ''}</td>
            <td contenteditable="true">${subject.subject_description || ''}</td>
            <td contenteditable="true">${subject.units || ''}</td>
            <td contenteditable="true">${subject.grade || ''}</td>
            <td class="action-cell">
                <button type="button" class="delete-row-btn" onclick="deleteGradeRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        // Add input validation for units and grade
        const unitsCell = row.cells[2];
        const gradeCell = row.cells[3];
        
        unitsCell.addEventListener('input', function() {
            this.textContent = this.textContent.replace(/[^0-9.]/g, '');
            if (this.textContent.length > 4) {
                this.textContent = this.textContent.slice(0, 4);
            }
            // Validate range (1-6)
            const value = parseFloat(this.textContent);
            if (value && (value < 1 || value > 6)) {
                this.style.color = '#dc3545';
            } else {
                this.style.color = '';
            }
        });
        
        gradeCell.addEventListener('input', function() {
            this.textContent = this.textContent.replace(/[^0-9.]/g, '');
            if (this.textContent.length > 4) {
                this.textContent = this.textContent.slice(0, 4);
            }
            // Validate range (1.00-5.00)
            const value = parseFloat(this.textContent);
            if (value && (value < 1.00 || value > 5.00)) {
                this.style.color = '#dc3545';
            } else {
                this.style.color = '';
            }
        });
        
        return row;
    }

    // Make addNewGradeRow function globally accessible
    window.addNewGradeRow = function() {
        const tableBody = document.querySelector('#gradesPreviewBody');
        if (tableBody) {
            const row = createGradeRow();
            tableBody.appendChild(row);
            // Add fade-in animation
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                row.style.opacity = '1';
            }, 50);
            
            // Focus on the first cell of the new row
            const firstCell = row.querySelector('td[contenteditable="true"]');
            if (firstCell) {
                firstCell.focus();
            }
        }
    };

    // Make deleteGradeRow function globally accessible
    window.deleteGradeRow = function(button) {
        const row = button.closest('tr');
        if (row) {
            // Add fade-out animation
            row.style.transition = 'opacity 0.3s ease';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
            }, 300);
        }
    };

    // Update the collectGradesData function
    function collectGradesData() {
        const gradesData = [];
        const rows = document.querySelectorAll('.grades-preview-table tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const subject_code = cells[0].textContent.trim();
            const subject_description = cells[1].textContent.trim();
            const units = cells[2].textContent.trim();
            const grade = cells[3].textContent.trim();
            
            // Only add if at least one field is filled
            if (subject_code || subject_description || units || grade) {
                gradesData.push({
                    subject_code: subject_code,
                    subject_description: subject_description,
                    units: units,
                    grade: grade
                });
            }
        });
        
        return gradesData;
    }

    // Update the confirmGrades function to validate data
    function confirmGrades() {
        const grades = collectGradesData();
        
        // Validate that we have at least one grade
        if (grades.length === 0) {
            showError('Please add at least one subject grade before confirming.');
            return;
        }
        
        // Validate each grade entry
        let isValid = true;
        let errorMessage = '';
        
        grades.forEach((grade, index) => {
            if (!grade.subject_code) {
                errorMessage = `Row ${index + 1}: Subject code is required.`;
                isValid = false;
            }
            if (!grade.subject_description) {
                errorMessage = `Row ${index + 1}: Subject description is required.`;
                isValid = false;
            }
            if (!grade.units || isNaN(grade.units)) {
                errorMessage = `Row ${index + 1}: Valid units are required.`;
                isValid = false;
            }
            if (!grade.grade || isNaN(grade.grade)) {
                errorMessage = `Row ${index + 1}: Valid grade is required.`;
                isValid = false;
            }
            
            // Validate grade range (1.00 to 5.00)
            const gradeNum = parseFloat(grade.grade);
            if (gradeNum < 1.00 || gradeNum > 5.00) {
                errorMessage = `Row ${index + 1}: Grade must be between 1.00 and 5.00.`;
                isValid = false;
            }
            
            // Validate units range (1 to 6)
            const unitsNum = parseFloat(grade.units);
            if (unitsNum < 1 || unitsNum > 6) {
                errorMessage = `Row ${index + 1}: Units must be between 1 and 6.`;
                isValid = false;
            }
        });
        
        if (!isValid) {
            showError(errorMessage);
            return;
        }
        
        // If all validation passes, proceed with form submission
        const formData = new FormData(document.getElementById('multi-step-form'));
        formData.append('grades', JSON.stringify(grades));
        // ... rest of your confirmation logic ...
    }

    // Initialize the form
    populatePreviousProgramSelect();
    document.getElementById('student_type').addEventListener('change', handleStudentTypeChange);

    // Modal handling
    if (modal) {
        window.onclick = function(event) {
            if (event.target === modal) {
                hideOCRPreview();
            }
        }
    }

    // Add event listener for the checkbox
    const useGradesCheckbox = document.getElementById('useGrades');
    const torSection = document.getElementById('tor-upload-section');
    const cogSection = document.getElementById('cog-upload-section');
    const torInput = document.getElementById('academic_document_tor');
    const cogInput = document.getElementById('academic_document_cog');

    if (useGradesCheckbox) {
        useGradesCheckbox.addEventListener('change', function() {
            if (this.checked) {
                // Hide TOR section and show COG section
                torSection.style.display = 'none';
                cogSection.style.display = 'block';
                torInput.disabled = true;
                cogInput.disabled = false;
                // Clear TOR input
                torInput.value = '';
                // Add fade-in animation
                cogSection.classList.add('fade-in');
            } else {
                // Show TOR section and hide COG section
                cogSection.style.display = 'none';
                torSection.style.display = 'block';
                cogInput.disabled = true;
                torInput.disabled = false;
                // Clear COG input
                cogInput.value = '';
                // Add fade-in animation
                torSection.classList.add('fade-in');
            }
        });
    }

    // Remove fade-in class after animation completes
    [torSection, cogSection].forEach(section => {
        if (section) {
            section.addEventListener('animationend', function() {
                this.classList.remove('fade-in');
            });
        }
    });

    // Update the confirm button handler
    document.querySelector('.confirm-btn').addEventListener('click', async function(e) {
        e.preventDefault();
        
        try {
            logToServer('confirmation_started');
            const gradesData = collectGradesData();
            const formData = new FormData(form);
            const useGrades = document.getElementById('useGrades').checked;
            
            formData.append('confirm_registration', '1');
            formData.append('grades', JSON.stringify(gradesData));
            formData.append('academic_document_path', sessionStorage.getItem('academic_document_path'));
            formData.append('school_id_path', sessionStorage.getItem('school_id_path'));
            formData.append('document_type', sessionStorage.getItem('document_type'));
            
            showLoading();
            hideOCRPreview();
            
            logToServer('submitting_final_data');
            
            const response = await fetch('qualiexam_registerBack.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Received non-JSON response from server");
            }
            
            const result = await response.json();
            logToServer('final_response_received', {
                success: result.success,
                document_type: useGrades ? 'cog' : 'tor'
            });
            
            hideLoading();
            
            if (result.success) {
                logToServer('registration_successful');
                
                // Store data in session storage
                sessionStorage.setItem('registration_success', 'true');
                sessionStorage.setItem('student_name', `${formData.get('first_name')} ${formData.get('last_name')}`);
                sessionStorage.setItem('reference_id', result.data?.reference_id || '');
                sessionStorage.setItem('email', formData.get('email'));
                
                logToServer('redirect_initiated');
                window.location.href = 'registration_success.php';
            } else {
                throw new Error(result.error || 'Registration failed');
            }
        } catch (error) {
            hideLoading();
            logToServer('confirmation_error', {
                error_message: error.message
            });
            console.error('Error during registration:', error);
            showError('Error completing registration: ' + error.message);
        }
    });
});

// Function to open the modal
function openModal() {
    document.getElementById("infoModal").style.display = "block";
}

// Function to close the info modal
function closeInfoModal() {
    document.getElementById("infoModal").style.display = "none";
}

// Open the modal when the page loads
window.onload = function() {
    openModal();
};

// Modal handling
window.onclick = function(event) {
    const gradingPreviewModal = document.getElementById('gradingPreviewModal');
    const infoModal = document.getElementById('infoModal');
    const ocrModal = document.getElementById('ocrPreviewModal');
    
    if (event.target === gradingPreviewModal) {
        closeGradingPreview();
    } else if (event.target === infoModal) {
        closeInfoModal();
    } else if (event.target === ocrModal) {
        hideOCRPreview();
    }
};

// Close modals on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const ocrModal = document.getElementById('ocrPreviewModal');
        const infoModal = document.getElementById('infoModal');
        const gradingPreviewModal = document.getElementById('gradingPreviewModal');
        
        if (ocrModal && ocrModal.style.display === 'block') {
            hideOCRPreview();
        }
        if (infoModal && infoModal.style.display === 'block') {
            closeInfoModal();
        }
        if (gradingPreviewModal && gradingPreviewModal.style.display === 'block') {
            closeGradingPreview();
        }
    }
});

let currentGradingSystem = '';

function handleGradingSystemChange(value) {
    const viewButton = document.getElementById('viewGradingBtn');
    viewButton.disabled = !value;
    currentGradingSystem = value;
}

function openGradingPreview() {
    if (!currentGradingSystem) return;
    
    const modal = document.getElementById('gradingPreviewModal');
    const regularGradesContainer = document.getElementById('regular-grades-container');
    const specialGradesContainer = document.getElementById('special-grades-container');

    // Show modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling

    // Show loading state
    regularGradesContainer.innerHTML = '<div class="loading-grades"><i class="fas fa-spinner"></i> Loading grades...</div>';
    specialGradesContainer.innerHTML = '<div class="loading-grades"><i class="fas fa-spinner"></i> Loading grades...</div>';

    // Fetch grading system data
    fetch(`get_grading_system.php?name=${encodeURIComponent(currentGradingSystem)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            // Clear containers
            regularGradesContainer.innerHTML = '';
            specialGradesContainer.innerHTML = '';
            
            // Separate and sort grades
            const regularGrades = data.filter(grade => !grade.is_special_grade)
                                    .sort((a, b) => parseFloat(a.grade_value) - parseFloat(b.grade_value));
            const specialGrades = data.filter(grade => grade.is_special_grade);

            // Display regular grades
            if (regularGrades.length > 0) {
                regularGrades.forEach(grade => {
                    const gradeItem = document.createElement('div');
                    gradeItem.className = 'grade-item';
                    gradeItem.innerHTML = `
                        <div class="grade-value">${grade.grade_value}</div>
                        <div class="grade-description">${grade.description || 'No description'}</div>
                        <div class="grade-range">${grade.min_percentage} - ${grade.max_percentage}%</div>
                    `;
                    regularGradesContainer.appendChild(gradeItem);
                });
            } else {
                regularGradesContainer.innerHTML = '<div class="empty-grades">No regular grades defined</div>';
            }

            // Display special grades
            if (specialGrades.length > 0) {
                specialGrades.forEach(grade => {
                    const gradeItem = document.createElement('div');
                    gradeItem.className = 'grade-item';
                    gradeItem.innerHTML = `
                        <div class="grade-value">${grade.grade_value}</div>
                        <div class="grade-description">${grade.description || 'No description'}</div>
                    `;
                    specialGradesContainer.appendChild(gradeItem);
                });
            } else {
                specialGradesContainer.innerHTML = '<div class="empty-grades">No special grades defined</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorHtml = `
                <div class="error-message">
                    Failed to load grading system. Please try again or contact support.
                    <button onclick="openGradingPreview()" class="retry-btn">
                        <i class="fas fa-sync-alt"></i> Retry
                    </button>
                </div>
            `;
            regularGradesContainer.innerHTML = errorHtml;
            specialGradesContainer.innerHTML = '';
        });
}

function closeGradingPreview() {
    const modal = document.getElementById('gradingPreviewModal');
    modal.style.display = 'none';
    document.body.style.overflow = ''; // Restore background scrolling
}

// Close modal when clicking outside
if (modal) {
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            hideOCRPreview();
        }
    });
}

// Close modal when pressing Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        hideOCRPreview();
    }
});

function toggleDocumentSections(useCOG) {
    const torSection = document.getElementById('tor-upload-section');
    const cogSection = document.getElementById('cog-upload-section');
    const torInput = document.getElementById('academic_document_tor');
    const cogInput = document.getElementById('academic_document_cog');

    if (useCOG) {
        // Switch to COG
        torSection.style.display = 'none';
        cogSection.style.display = 'block';
        torInput.disabled = true;
        cogInput.disabled = false;
        cogSection.classList.add('fade-in');
        torInput.value = ''; // Clear TOR input
    } else {
        // Switch to TOR
        cogSection.style.display = 'none';
        torSection.style.display = 'block';
        cogInput.disabled = true;
        torInput.disabled = false;
        torSection.classList.add('fade-in');
        cogInput.value = ''; // Clear COG input
    }
}

// Function to show the modal
function showOCRPreview() {
    const modal = document.getElementById('ocrPreviewModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

// Function to hide the modal
function hideOCRPreview() {
    const modal = document.getElementById('ocrPreviewModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = ''; // Restore scrolling
    }
}

// Add client-side logging function
function logToServer(action, data = {}) {
    const logData = new FormData();
    logData.append('action', action);
    logData.append('data', JSON.stringify(data));
    logData.append('log_request', '1');
    
    fetch('log_registration.php', {
        method: 'POST',
        body: logData
    }).catch(error => console.error('Logging error:', error));
}

// Loading indicator styles
const loadingStyles = document.createElement('style');
loadingStyles.textContent = `
    #loading {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background-color: #ff4444;
        color: white;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 9999;
        display: none;
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
`;
document.head.appendChild(loadingStyles);

// Create loading indicator
const loadingDiv = document.createElement('div');
loadingDiv.id = 'loading';
loadingDiv.innerHTML = '<div class="loading-spinner"></div>';
document.body.appendChild(loadingDiv);

// Create error message container
const errorDiv = document.createElement('div');
errorDiv.className = 'error-message';
document.body.appendChild(errorDiv);

// Function to show loading indicator
function showLoading(isOCR = false) {
    if (isOCR) {
        const overlay = document.querySelector('.ocr-loading-overlay');
        if (overlay) {
            overlay.style.display = 'flex';
            updateOCRStep('upload');
            
            // Simulate progress through steps
            setTimeout(() => updateOCRStep('process'), 2000);
            setTimeout(() => updateOCRStep('analyze'), 4000);
            setTimeout(() => updateOCRStep('complete'), 6000);
        }
    } else {
        const loadingDiv = document.getElementById('loading');
        if (loadingDiv) {
            loadingDiv.style.display = 'flex';
        }
    }
}

// Function to hide loading indicator
function hideLoading(isOCR = false) {
    if (isOCR) {
        const overlay = document.querySelector('.ocr-loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            // Reset steps
            document.querySelectorAll('.ocr-step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
        }
    } else {
        const loadingDiv = document.getElementById('loading');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
    }
}

function updateOCRStep(currentStep) {
    const steps = ['upload', 'process', 'analyze', 'complete'];
    const currentIndex = steps.indexOf(currentStep);
    
    steps.forEach((step, index) => {
        const stepElement = document.querySelector(`.ocr-step[data-step="${step}"]`);
        if (stepElement) {
            if (index < currentIndex) {
                stepElement.classList.remove('active');
                stepElement.classList.add('completed');
            } else if (index === currentIndex) {
                stepElement.classList.add('active');
                stepElement.classList.remove('completed');
            } else {
                stepElement.classList.remove('active', 'completed');
            }
        }
    });
}

// Function to show error message
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-alert';
    errorDiv.innerHTML = `
        <div class="error-content">
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    document.body.appendChild(errorDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Add these styles for better error visibility
const errorStyles = `
    .error-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        animation: slideIn 0.5s ease-out;
    }
    
    .error-content {
        background-color: #fff;
        border-left: 4px solid #dc3545;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 1rem 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border-radius: 4px;
    }
    
    .error-content i {
        color: #dc3545;
        font-size: 1.2rem;
    }
    
    .error-content button {
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0 0.5rem;
    }
    
    .error-content button:hover {
        color: #333;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = errorStyles;
document.head.appendChild(styleSheet);

</script>

</body>
</html>
