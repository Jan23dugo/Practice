<?php
// Include Composer's autoloader
require 'vendor/autoload.php';

// Include your config
require 'config/admin_mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Test email
$mail = AdminMailConfig::setupMailer();
if ($mail) {
    try {
        $mail->addAddress('your-personal-email@example.com');  // Where to send the test
        $mail->Subject = 'Admin Email Test';
        $mail->Body    = 'This is a test email from the PUP Admin System.';
        
        if ($mail->send()) {
            echo "Test email sent successfully!";
        } else {
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } catch (Exception $e) {
        echo "Email error: {$e->getMessage()}";
    }
} else {
    echo "Failed to set up email configuration.";
} 