<?php
// test_email_phpmailer.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the email function
require_once 'send_email.php';

echo "<h1>PHPMailer Email Test</h1>";

// Create necessary directories
if (!file_exists(__DIR__ . '/logs/')) {
    mkdir(__DIR__ . '/logs/', 0777, true);
}

// Test PHPMailer
if (isset($_POST['send_test'])) {
    $to = $_POST['email'];
    $reference_id = "TEST-" . date('YmdHis');
    
    echo "<h2>Attempting to send email using PHPMailer</h2>";
    echo "<p>To: $to</p>";
    echo "<p>Reference ID: $reference_id</p>";
    
    try {
        $result = sendRegistrationEmail($to, $reference_id);
        
        if ($result) {
            echo "<p style='color:green;'>Email sent successfully!</p>";
            echo "<p>Check your inbox (and spam folder) for the email.</p>";
        } else {
            echo "<p style='color:red;'>Failed to send email.</p>";
            echo "<p>Check the logs directory for error details.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<p>Check the logs directory for detailed information.</p>";
}
?>

<form method="post">
    <h2>Send Test Email</h2>
    <p>
        <label for="email">Email address:</label>
        <input type="email" name="email" id="email" required>
    </p>
    <p>
        <button type="submit" name="send_test" style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
            Send Test Email
        </button>
    </p>
</form>

<div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 5px solid #17a2b8;">
    <h3>Troubleshooting Tips</h3>
    <ul>
        <li>Make sure the PHPMailer library is correctly installed in the vendor directory</li>
        <li>Check that your Gmail account has "Less secure app access" enabled or is using an App Password</li>
        <li>Verify that your SMTP credentials are correct</li>
        <li>Check the logs directory for detailed error messages</li>
    </ul>
</div>