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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
   
>>>>>>> Stashed changes
=======
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
   
>>>>>>> Stashed changes
=======
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
   
>>>>>>> Stashed changes
=======
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
   
>>>>>>> Stashed changes
</head>

<body>
    <!-- Loading Spinner -->
    <div id="loading-spinner"></div>

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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

=======
    <div class="main-content">
        <div class="content-wrapper">
>>>>>>> Stashed changes
=======
    <div class="main-content">
        <div class="content-wrapper">
>>>>>>> Stashed changes
=======
    <div class="main-content">
        <div class="content-wrapper">
>>>>>>> Stashed changes
=======
    <div class="main-content">
        <div class="content-wrapper">
>>>>>>> Stashed changes
            <section class="form-section">
                <!-- Form -->
                <form id="multi-step-form" action="qualiexam_registerBack.php" method="POST" enctype="multipart/form-data" onsubmit="return submitForm(event)">
                    <input type="hidden" name="action" value="final_submit">
                    <input type="hidden" name="subjects" id="subjects_data">
                    <div class="step active">
                        <div class="section-container">
                            <div class="section-header">
                                <h2>Student Type Selection</h2>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
                                <a href="stud_dashboard.php" class="back-btn" aria-label="Back to Dashboard">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
>>>>>>> Stashed changes
=======
                                <a href="stud_dashboard.php" class="back-btn" aria-label="Back to Dashboard">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
>>>>>>> Stashed changes
=======
                                <a href="stud_dashboard.php" class="back-btn" aria-label="Back to Dashboard">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
>>>>>>> Stashed changes
=======
                                <a href="stud_dashboard.php" class="back-btn" aria-label="Back to Dashboard">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
>>>>>>> Stashed changes
                                <div class="section-note">
                                    <i class="fas fa-info-circle"></i>
                                    Please select your student type carefully. This will determine the required information and documents needed for your application.
                                </div>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                            </div>
                            <div class="form-field">
                                <label for="student_type">Student Type</label>
                                <?php
                                // Add debugging for the comparison
                                $currentType = isset($student['student_type']) ? strtolower(trim($student['student_type'])) : '';
                                echo "<!-- Current student type: '$currentType' -->";
                                ?>
                                <select id="student_type" name="student_type" required onchange="handleStudentTypeChange()" class="custom-select student-type-select">
                                    <option value="" disabled <?php echo !isset($student['student_type']) ? 'selected' : ''; ?> class="select-placeholder">Select Student Type</option>
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
                                <button type="button" class="btn btn-next" onclick="validateStep()">
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
                                    <input type="text" id="last_name" name="last_name" required class="form-control custom-input name-input" 
                                           value="<?php echo htmlspecialchars($student['lastname'] ?? ''); ?>"
                                           placeholder="Enter last name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="first_name">Given Name</label>
                                    <input type="text" id="first_name" name="first_name" required class="form-control custom-input name-input"
                                           value="<?php echo htmlspecialchars($student['firstname'] ?? ''); ?>"
                                           placeholder="Enter first name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="middle_name">Middle Name (Optional)</label>
                                    <input type="text" id="middle_name" name="middle_name" class="form-control custom-input name-input"
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
                                           class="custom-date"
                                           max="<?php echo date('Y-m-d'); ?>" 
                                           value="<?php echo isset($student['date_of_birth']) ? htmlspecialchars($student['date_of_birth']) : ''; ?>">
                                    <div class="field-note">Select your date of birth from the calendar</div>
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" required class="custom-select gender-select">
                                        <option value="" disabled <?php echo !isset($student['gender']) ? 'selected' : ''; ?> class="select-placeholder">Select Gender</option>
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
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                            </div>
                            <div class="form-field">
                                <label for="student_type">Student Type</label>
                                <?php
                                // Add debugging for the comparison
                                $currentType = isset($student['student_type']) ? strtolower(trim($student['student_type'])) : '';
                                echo "<!-- Current student type: '$currentType' -->";
                                ?>
                                <select id="student_type" name="student_type" required onchange="handleStudentTypeChange()" class="custom-select student-type-select">
                                    <option value="" disabled <?php echo !isset($student['student_type']) ? 'selected' : ''; ?> class="select-placeholder">Select Student Type</option>
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
                                <button type="button" class="btn btn-next" onclick="validateStep()">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                    <!-- Step 3: Contact Details -->
                    <div class="step">
                        <div class="section-container">
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
                                           class="custom-input email-input"
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
                                           class="custom-input tel-input"
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
                                           class="custom-input address-input"
                                           placeholder="Enter your complete residential address"
                                           value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                                    <div class="field-note">Provide your complete current residential address</div>
                                </div>
                            </div>
=======
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
                                    <input type="text" id="last_name" name="last_name" required class="form-control custom-input name-input" 
                                           value="<?php echo htmlspecialchars($student['lastname'] ?? ''); ?>"
                                           placeholder="Enter last name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="first_name">Given Name</label>
                                    <input type="text" id="first_name" name="first_name" required class="form-control custom-input name-input"
                                           value="<?php echo htmlspecialchars($student['firstname'] ?? ''); ?>"
                                           placeholder="Enter first name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="middle_name">Middle Name (Optional)</label>
                                    <input type="text" id="middle_name" name="middle_name" class="form-control custom-input name-input"
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
                                           class="custom-date"
                                           max="<?php echo date('Y-m-d'); ?>" 
                                           value="<?php echo isset($student['date_of_birth']) ? htmlspecialchars($student['date_of_birth']) : ''; ?>">
                                    <div class="field-note">Select your date of birth from the calendar</div>
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" required class="custom-select gender-select">
                                        <option value="" disabled <?php echo !isset($student['gender']) ? 'selected' : ''; ?> class="select-placeholder">Select Gender</option>
                                        <option value="Male" <?php echo (isset($student['gender']) && $student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($student['gender']) && $student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($student['gender']) && $student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="field-note">Select your gender identity</div>
                                </div>
                            </div>

>>>>>>> Stashed changes
=======
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
                                    <input type="text" id="last_name" name="last_name" required class="form-control custom-input name-input" 
                                           value="<?php echo htmlspecialchars($student['lastname'] ?? ''); ?>"
                                           placeholder="Enter last name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="first_name">Given Name</label>
                                    <input type="text" id="first_name" name="first_name" required class="form-control custom-input name-input"
                                           value="<?php echo htmlspecialchars($student['firstname'] ?? ''); ?>"
                                           placeholder="Enter first name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="middle_name">Middle Name (Optional)</label>
                                    <input type="text" id="middle_name" name="middle_name" class="form-control custom-input name-input"
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
                                           class="custom-date"
                                           max="<?php echo date('Y-m-d'); ?>" 
                                           value="<?php echo isset($student['date_of_birth']) ? htmlspecialchars($student['date_of_birth']) : ''; ?>">
                                    <div class="field-note">Select your date of birth from the calendar</div>
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" required class="custom-select gender-select">
                                        <option value="" disabled <?php echo !isset($student['gender']) ? 'selected' : ''; ?> class="select-placeholder">Select Gender</option>
                                        <option value="Male" <?php echo (isset($student['gender']) && $student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($student['gender']) && $student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($student['gender']) && $student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="field-note">Select your gender identity</div>
                                </div>
                            </div>

>>>>>>> Stashed changes
=======
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
                                    <input type="text" id="last_name" name="last_name" required class="form-control custom-input name-input" 
                                           value="<?php echo htmlspecialchars($student['lastname'] ?? ''); ?>"
                                           placeholder="Enter last name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="first_name">Given Name</label>
                                    <input type="text" id="first_name" name="first_name" required class="form-control custom-input name-input"
                                           value="<?php echo htmlspecialchars($student['firstname'] ?? ''); ?>"
                                           placeholder="Enter first name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="middle_name">Middle Name (Optional)</label>
                                    <input type="text" id="middle_name" name="middle_name" class="form-control custom-input name-input"
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
                                           class="custom-date"
                                           max="<?php echo date('Y-m-d'); ?>" 
                                           value="<?php echo isset($student['date_of_birth']) ? htmlspecialchars($student['date_of_birth']) : ''; ?>">
                                    <div class="field-note">Select your date of birth from the calendar</div>
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" required class="custom-select gender-select">
                                        <option value="" disabled <?php echo !isset($student['gender']) ? 'selected' : ''; ?> class="select-placeholder">Select Gender</option>
                                        <option value="Male" <?php echo (isset($student['gender']) && $student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($student['gender']) && $student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($student['gender']) && $student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="field-note">Select your gender identity</div>
                                </div>
                            </div>

>>>>>>> Stashed changes
=======
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
                                    <input type="text" id="last_name" name="last_name" required class="form-control custom-input name-input" 
                                           value="<?php echo htmlspecialchars($student['lastname'] ?? ''); ?>"
                                           placeholder="Enter last name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="first_name">Given Name</label>
                                    <input type="text" id="first_name" name="first_name" required class="form-control custom-input name-input"
                                           value="<?php echo htmlspecialchars($student['firstname'] ?? ''); ?>"
                                           placeholder="Enter first name">
                                    <div class="field-note">As shown in school records</div>
                                </div>

                                <div class="form-group">
                                    <label for="middle_name">Middle Name (Optional)</label>
                                    <input type="text" id="middle_name" name="middle_name" class="form-control custom-input name-input"
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
                                           class="custom-date"
                                           max="<?php echo date('Y-m-d'); ?>" 
                                           value="<?php echo isset($student['date_of_birth']) ? htmlspecialchars($student['date_of_birth']) : ''; ?>">
                                    <div class="field-note">Select your date of birth from the calendar</div>
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" required class="custom-select gender-select">
                                        <option value="" disabled <?php echo !isset($student['gender']) ? 'selected' : ''; ?> class="select-placeholder">Select Gender</option>
                                        <option value="Male" <?php echo (isset($student['gender']) && $student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($student['gender']) && $student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($student['gender']) && $student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="field-note">Select your gender identity</div>
                                </div>
                            </div>

>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
                                           class="custom-input number-input"
                                           placeholder="Enter number of years"
                                           value="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>">
                                    <div class="field-note">Years in previous program</div>
                                </div>

                                <div class="form-group">
                                    <label for="previous_school">Previous School</label>
                                    <select id="previous_school" name="previous_school" required class="custom-select editable-select" onchange="handleEditableSelect(this)">
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
                                    <select id="previous_program" name="previous_program" required class="custom-select">
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
                                    <select id="desired_program" name="desired_program" required class="custom-select">
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
=======
                    <!-- Step 3: Contact Details -->
                    <div class="step">
                        <div class="section-container">
                            <div class="section-header">
                                <h2>Contact Information</h2>
                                <div class="section-note">
                                    <i class="fas fa-info-circle"></i>
                                    Please provide accurate contact information. Important updates about your application will be sent to these contact details.
>>>>>>> Stashed changes
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           required 
                                           class="custom-input email-input"
                                           placeholder="Enter your email address"
                                           value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                                    <div class="field-note">Use an active email address that you check regularly</div>
                                </div>

<<<<<<< Updated upstream
=======
                    <!-- Step 3: Contact Details -->
                    <div class="step">
                        <div class="section-container">
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
                                           class="custom-input email-input"
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
                                           class="custom-input tel-input"
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
                                           class="custom-input address-input"
                                           placeholder="Enter your complete residential address"
                                           value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                                    <div class="field-note">Provide your complete current residential address</div>
                                </div>
                            </div>
>>>>>>> Stashed changes
=======
                    <!-- Step 3: Contact Details -->
                    <div class="step">
                        <div class="section-container">
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
                                           class="custom-input email-input"
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
                                           class="custom-input tel-input"
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
                                           class="custom-input address-input"
                                           placeholder="Enter your complete residential address"
                                           value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                                    <div class="field-note">Provide your complete current residential address</div>
                                </div>
                            </div>
>>>>>>> Stashed changes
=======
                    <!-- Step 3: Contact Details -->
                    <div class="step">
                        <div class="section-container">
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
                                           class="custom-input email-input"
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
                                           class="custom-input tel-input"
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
                                           class="custom-input address-input"
                                           placeholder="Enter your complete residential address"
                                           value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                                    <div class="field-note">Provide your complete current residential address</div>
                                </div>
                            </div>
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
                            <!-- Hidden field to store subjects data from OCR -->
                            <input type="hidden" id="subjects_data" name="subjects_data" value="">
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
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-check"></i> Submit Application
                                </button>
                            </div>
=======
                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="tel" 
                                           id="contact_number" 
                                           name="contact_number" 
                                           required 
                                           class="custom-input tel-input"
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
                                           class="custom-input address-input"
                                           placeholder="Enter your complete residential address"
                                           value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                                    <div class="field-note">Provide your complete current residential address</div>
                                </div>
                            </div>
=======
                    <!-- Step 4: Academic Details -->
                    <div class="step">
                        <div class="section-container">
                            <div class="section-header">
=======
                    <!-- Step 4: Academic Details -->
                    <div class="step">
                        <div class="section-container">
                            <div class="section-header">
>>>>>>> Stashed changes
=======
                    <!-- Step 4: Academic Details -->
                    <div class="step">
                        <div class="section-container">
                            <div class="section-header">
>>>>>>> Stashed changes
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
                                           class="custom-input number-input"
                                           placeholder="Enter number of years"
                                           value="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>">
                                    <div class="field-note">Years in previous program</div>
                                </div>

                                <div class="form-group">
                                    <label for="previous_school">Previous School</label>
                                    <input id="previous_school" name="previous_school" class="custom-input" type="text" placeholder="Type to search universities..." autocomplete="off" required />
                                    <div class="field-note">
                                        Start typing to search. If your school is not listed, you may enter it manually.<br>
                                        <span style="color:#75343A; font-style:italic;">Example: Polytechnic University of the Philippines</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="previous_program">Previous Program</label>
                                    <input id="previous_program" name="previous_program" class="custom-input" type="text" placeholder="Type to search programs..." autocomplete="off" required />
                                    <div class="field-note">
                                        Start typing to search. If your program is not listed, you may enter it manually.<br>
                                        <span style="color:#75343A; font-style:italic;">Example: Bachelor of Science in Computer Science</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="desired_program">Program Applying To</label>
                                    <select id="desired_program" name="desired_program" required class="custom-select">
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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
                                           class="custom-input number-input"
                                           placeholder="Enter number of years"
                                           value="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>">
                                    <div class="field-note">Years in previous program</div>
                                </div>

                                <div class="form-group">
                                    <label for="previous_school">Previous School</label>
                                    <input id="previous_school" name="previous_school" class="custom-input" type="text" placeholder="Type to search universities..." autocomplete="off" required />
                                    <div class="field-note">
                                        Start typing to search. If your school is not listed, you may enter it manually.<br>
                                        <span style="color:#75343A; font-style:italic;">Example: Polytechnic University of the Philippines</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="previous_program">Previous Program</label>
                                    <input id="previous_program" name="previous_program" class="custom-input" type="text" placeholder="Type to search programs..." autocomplete="off" required />
                                    <div class="field-note">
                                        Start typing to search. If your program is not listed, you may enter it manually.<br>
                                        <span style="color:#75343A; font-style:italic;">Example: Bachelor of Science in Computer Science</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="desired_program">Program Applying To</label>
                                    <select id="desired_program" name="desired_program" required class="custom-select">
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
=======
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
>>>>>>> Stashed changes
=======
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
>>>>>>> Stashed changes
=======
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
>>>>>>> Stashed changes
                            <!-- Hidden field to store subjects data from OCR -->
                            <input type="hidden" id="subjects_data" name="subjects_data" value="">
                            <div class="form-group">
                                <div class="form-field" id="tor-field">
                                    <label for="tor">Upload Scanned Copy of Transcript of Records (TOR)</label>
                                    <input type="file" id="tor" name="tor" required>
                                    <small>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                                </div>
                                <div class="form-field">
                                    <div class="checkbox-container">
                                        <input type="checkbox" id="has_copy_grades" name="has_copy_grades" onchange="toggleCopyGradesUpload()">
                                        <label for="has_copy_grades">Upload Scanned Copy of Grades</label>
                                    </div>
                                    <small style="color:#75343A;display:block;margin-top:4px;">
                                        If you do not have a Transcript of Records, you may check this box and upload your Copy of Grades instead.
                                    </small>
                                </div>
                                <div class="form-field" id="copy-grades-field" style="display: none;">
                                    <label for="copy_grades">Upload Scanned Copy of Grades</label>
                                    <input type="file" id="copy_grades" name="copy_grades">
                                    <small>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                                </div>
                                <div class="form-field">
                                    <label for="school_id">Upload Scanned Copy of School ID</label>
                                    <input type="file" id="school_id" name="school_id" required>
                                    <small>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</small>
                                </div>
                            </div>
                            <div class="buttons">
                                <button type="button" class="btn btn-previous" onclick="prevStep()">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </button>
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-check"></i> Submit Application
                                </button>
                            </div>
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
                        </div>
                    </div>
                </form>
            </section>
<<<<<<< Updated upstream

            <div class="decorative-container">
                <img src="assets/images/decorative-img.png" alt="Education Illustration" class="decorative-image">
                <div class="decorative-text">
                    <h2>Welcome to CCIS Qualifying Examination</h2>
                    <p>Take the next step in your academic journey. Complete your registration to proceed with the qualifying examination for the College of Computing and Information Sciences.</p>
                </div>
=======
        </div>
    </div>
    

    <!-- Add this before the closing body tag -->

    <!-- Add this modal for OCR preview -->
    <div id="ocrPreviewModal" class="ocr-modal">
        <div class="modal-content ocr-preview-modal">
            <span class="close-modal" onclick="closeOCRPreview()">&times;</span>
            
            <div class="modal-header">
                <p>Review and edit the automatically extracted subjects from your transcript. You can add, edit, or delete subjects before submitting your application.</p>
            </div>
            
            <div class="modal-body" id="ocrResultsContent">
                <!-- OCR results will appear here -->
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeOCRPreview()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmAndSubmit()">
                    <i class="fas fa-check"></i> Confirm and Submit
                </button>
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
            </div>
        </div>
    </div>
    
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream

    <!-- Add this before the closing body tag -->

    <!-- Add this modal for OCR preview -->
    <div id="ocrPreviewModal" class="ocr-modal">
        <div class="modal-content ocr-preview-modal">
            <span class="close-modal" onclick="closeOCRPreview()">&times;</span>
            
            <div class="modal-header">
                <h2>OCR Results Preview</h2>
                <p>Review and edit the automatically extracted subjects from your transcript. You can add, edit, or delete subjects before submitting your application.</p>
            </div>
            
            <div class="modal-body" id="ocrResultsContent">
                <!-- OCR results will appear here -->
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeOCRPreview()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmAndSubmit()">
                    <i class="fas fa-check"></i> Confirm and Submit
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="submission-loading-overlay">
        <div class="spinner"></div>
        <p>Processing your registration...</p>
        <small>Please wait while we submit your application</small>
    </div>
    
    <!-- OCR loading overlay -->
    <div id="ocr-loading-overlay">
        <div class="loading-indicator">
            <h3>Processing Your Documents</h3>
            <p>Extracting data from your transcript using OCR technology</p>
            
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
            
            <div class="spinner"></div>
            <p>This may take a moment...</p>
            <small>Please wait while we process your documents</small>
        </div>
    </div>
=======
    <!-- Loading Overlay -->
    <div id="submission-loading-overlay">
        <div class="spinner"></div>
        <p>Processing your registration...</p>
        <small>Please wait while we submit your application</small>
    </div>
    
    <!-- OCR loading overlay -->
    <div id="ocr-loading-overlay">
        <div class="loading-indicator">
            <h3>Processing Your Documents</h3>
            <p>Extracting data from your transcript using OCR technology</p>
            
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
            
            <div class="spinner"></div>
            <p>This may take a moment...</p>
            <small>Please wait while we process your documents</small>
        </div>
    </div>
>>>>>>> Stashed changes
=======
    <!-- Loading Overlay -->
    <div id="submission-loading-overlay">
        <div class="spinner"></div>
        <p>Processing your registration...</p>
        <small>Please wait while we submit your application</small>
    </div>
    
    <!-- OCR loading overlay -->
    <div id="ocr-loading-overlay">
        <div class="loading-indicator">
            <h3>Processing Your Documents</h3>
            <p>Extracting data from your transcript using OCR technology</p>
            
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
            
            <div class="spinner"></div>
            <p>This may take a moment...</p>
            <small>Please wait while we process your documents</small>
        </div>
    </div>
>>>>>>> Stashed changes
=======
    <!-- Loading Overlay -->
    <div id="submission-loading-overlay">
        <div class="spinner"></div>
        <p>Processing your registration...</p>
        <small>Please wait while we submit your application</small>
    </div>
    
    <!-- OCR loading overlay -->
    <div id="ocr-loading-overlay">
        <div class="loading-indicator">
            <h3>Processing Your Documents</h3>
            <p>Extracting data from your transcript using OCR technology</p>
            
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
            
            <div class="spinner"></div>
            <p>This may take a moment...</p>
            <small>Please wait while we process your documents</small>
        </div>
    </div>
>>>>>>> Stashed changes
=======
    <!-- Loading Overlay -->
    <div id="submission-loading-overlay">
        <div class="spinner"></div>
        <p>Processing your registration...</p>
        <small>Please wait while we submit your application</small>
    </div>
    
    <!-- OCR loading overlay -->
    <div id="ocr-loading-overlay">
        <div class="loading-indicator">
            <h3>Processing Your Documents</h3>
            <p>Extracting data from your transcript using OCR technology</p>
            
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
            
            <div class="spinner"></div>
            <p>This may take a moment...</p>
            <small>Please wait while we process your documents</small>
        </div>
    </div>
>>>>>>> Stashed changes

    <!-- OCR Modal styles are now in registerForm.css -->

    <!-- Include the registration success modal -->
    <?php 
    // Define constant to protect against direct access
    define('INCLUDE_MODAL', true);
    
    // Include the success modal file
    include('registration_success_modal.php'); 
    
    // Include the error modal file
    include('registration_error_modal.php');
    ?>

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
    <!-- Load JavaScript from external file -->
=======
    <!-- Load Awesomplete JS before custom JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>
>>>>>>> Stashed changes
=======
    <!-- Load Awesomplete JS before custom JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>
>>>>>>> Stashed changes
=======
    <!-- Load Awesomplete JS before custom JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>
>>>>>>> Stashed changes
=======
    <!-- Load Awesomplete JS before custom JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>
>>>>>>> Stashed changes
    <script src="assets/js/qualiexam_register.js"></script>
    
    <!-- Initialize form functions -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize student type handler
        if (typeof handleStudentTypeChange === 'function') {
            handleStudentTypeChange();
        }
    });
    </script>
    
    <!-- Debugging script to verify modals -->
    <script>
    // Set flags for modal display based on PHP session data
    window.shouldShowSuccessModal = <?php echo isset($_SESSION['show_success_modal']) && $_SESSION['show_success_modal'] ? 'true' : 'false'; ?>;
    window.shouldShowErrorModal = <?php echo isset($_SESSION['show_error_modal']) && $_SESSION['show_error_modal'] ? 'true' : 'false'; ?>;
    
    // Clear session flags once they've been assigned to JavaScript variables
    <?php
    if (isset($_SESSION['show_success_modal'])) {
        unset($_SESSION['show_success_modal']);
    }
    if (isset($_SESSION['show_error_modal'])) {
        unset($_SESSION['show_error_modal']);
    }
    ?>
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        
        // Check if success modal exists
        const successModal = document.getElementById('registrationSuccessModal');
        console.log('Success modal exists:', !!successModal);
        
        // Check if error modal exists
        const errorModal = document.getElementById('registrationErrorModal');
        console.log('Error modal exists:', !!errorModal);
        
        // Check if the showSuccessModal function is defined
        console.log('showSuccessModal function exists:', typeof showSuccessModal === 'function');
        
        // Check if the showErrorModal function is defined
        console.log('showErrorModal function exists:', typeof showErrorModal === 'function');
        
        // Check session flags (now in JavaScript variables)
        console.log('JavaScript flags:', {
            shouldShowSuccessModal: window.shouldShowSuccessModal,
            shouldShowErrorModal: window.shouldShowErrorModal
        });
        
        // Make sure OCR preview modal is hidden
        const ocrPreviewModal = document.getElementById('ocrPreviewModal');
        if (ocrPreviewModal) {
            ocrPreviewModal.style.cssText = "display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -1 !important;";
        }
        
        // If the JavaScript flag is set but modal wasn't displayed yet, try showing it manually
        if (window.shouldShowSuccessModal && typeof showSuccessModal === 'function' && successModal) {
            console.log('Showing success modal from debugging script');
            setTimeout(showSuccessModal, 500);
        }
        
        if (window.shouldShowErrorModal && typeof showErrorModal === 'function' && errorModal) {
            console.log('Showing error modal from debugging script');
            setTimeout(showErrorModal, 500);
        }
    });
    </script>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Previous School
        var universityList = <?php
            $universities = [];
            $query = "SELECT university_name FROM universities ORDER BY university_name";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $universities[] = $row['university_name'];
                }
            }
            echo json_encode($universities);
        ?>;
        new Awesomplete(document.getElementById("previous_school"), {
            list: universityList,
            minChars: 1,
            maxItems: 10,
            autoFirst: true
        });

        // Previous Program
        var programList = <?php
            $programs = [];
            $query = "SELECT program_name FROM university_programs ORDER BY program_name";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $programs[] = $row['program_name'];
                }
            }
            echo json_encode($programs);
        ?>;
        new Awesomplete(document.getElementById("previous_program"), {
            list: programList,
            minChars: 1,
            maxItems: 10,
            autoFirst: true
        });
    });
    </script>
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
</body>
</html>
