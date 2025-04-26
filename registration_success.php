<?php
session_start();

// If user directly accesses this page without registration, redirect to registration page
if (!isset($_SESSION['last_registration'])) {
    header("Location: qualiexam_register.php");
    exit();
}

// Function to safely get session storage data via JavaScript
function getSessionStorageData() {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we have registration data in session storage
            if (sessionStorage.getItem('registration_success')) {
                document.getElementById('referenceId').textContent = sessionStorage.getItem('reference_id') || 'N/A';
                document.getElementById('studentName').textContent = sessionStorage.getItem('student_name') || 'N/A';
                document.getElementById('studentEmail').textContent = sessionStorage.getItem('email') || 'N/A';

                // Clear session storage after displaying
                sessionStorage.removeItem('registration_success');
                sessionStorage.removeItem('student_name');
                sessionStorage.removeItem('reference_id');
                sessionStorage.removeItem('email');
            } else {
                // If no session storage data, check PHP session
                document.getElementById('referenceId').textContent = '" . ($_SESSION['reference_id'] ?? 'N/A') . "';
                document.getElementById('studentName').textContent = '" . ($_SESSION['student_name'] ?? 'N/A') . "';
                document.getElementById('studentEmail').textContent = '" . ($_SESSION['email'] ?? 'N/A') . "';
            }
        });
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .success-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            margin: 0 auto;
        }
        .success-icon {
            color: #28a745;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .reference-id {
            background: #e9ecef;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-family: monospace;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card text-center">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
            </div>
            <h1 class="mb-4">Registration Successful!</h1>
            <div id="registrationDetails">
                <p class="lead mb-4">Thank you for registering. Your application has been submitted successfully.</p>
                <div class="mb-3">
                    <strong>Reference ID:</strong><br>
                    <span class="reference-id" id="referenceId"></span>
                </div>
                <div class="mb-3">
                    <strong>Name:</strong><br>
                    <span id="studentName"></span>
                </div>
                <div class="mb-4">
                    <strong>Email:</strong><br>
                    <span id="studentEmail"></span>
                </div>
                <p class="text-muted small">A confirmation email has been sent to your email address with further instructions.</p>
            </div>
            <div class="mt-4">
                <a href="stud_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
            </div>
        </div>
    </div>

    <?php 
    // Output the session storage handling script
    getSessionStorageData();
    
    // Clear the PHP session data after displaying
    unset($_SESSION['reference_id']);
    unset($_SESSION['student_name']);
    unset($_SESSION['email']);
    ?>
</body>
</html>
<?php
// Clear the registration session data
unset($_SESSION['last_registration']);
?>
