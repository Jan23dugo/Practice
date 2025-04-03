<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Function to send registration confirmation email
function sendRegistrationEmail($student_email, $reference_id) {
    // Ensure logs directory exists
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
    }

    // Create a log file for email debugging
    $emailLogFile = __DIR__ . '/logs/email_log_' . date('Y-m-d_H-i-s') . '.log';
    file_put_contents($emailLogFile, "=== EMAIL SENDING LOG ===\n");
    file_put_contents($emailLogFile, "Timestamp: " . date('Y-m-d H:i:s') . "\n");
    file_put_contents($emailLogFile, "Sending email to: $student_email\n", FILE_APPEND);
    file_put_contents($emailLogFile, "Reference ID: $reference_id\n", FILE_APPEND);
    
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ccisqualifyingexam@gmail.com'; // Updated email
        $mail->Password = 'zbjv xljt nwyz gqdk'; // Updated app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS
        $mail->Port = 587; // Gmail SMTP port
        
        // Enable debug output (set to 0 for production)
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function($str, $level) use ($emailLogFile) {
            file_put_contents($emailLogFile, "DEBUG[$level]: $str\n", FILE_APPEND);
        };

        file_put_contents($emailLogFile, "SMTP settings configured\n", FILE_APPEND);

        // Recipients
        $mail->setFrom('ccisqualifyingexam@gmail.com', 'PUP CCIS Exam'); // Updated sender email
        $mail->addAddress($student_email); // Student email
        
        file_put_contents($emailLogFile, "Recipients configured\n", FILE_APPEND);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Registration is Successful!';
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
                .header { background-color: #800000; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; }
                .reference { font-size: 24px; font-weight: bold; color: #800000; text-align: center; padding: 10px; }
                .footer { background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Registration Confirmation</h2>
                </div>
                <div class='content'>
                    <p>Dear Student,</p>
                    <p>Your registration has been successfully processed. Please keep your reference ID for future inquiries:</p>
                    <div class='reference'>$reference_id</div>
                    <p>If you have any questions, please contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from PUP CCIS Faculty. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Set plain text version
        $mail->AltBody = "Dear Student,\n\nYour registration is complete. Your Reference ID is: $reference_id.\n\nBest regards,\nPUP CCIS Faculty";
        
        file_put_contents($emailLogFile, "Email content prepared\n", FILE_APPEND);

        // Send email
        $mail->send();
        file_put_contents($emailLogFile, "Email sent successfully\n", FILE_APPEND);
        return true;

    } catch (Exception $e) {
        $errorMessage = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        file_put_contents($emailLogFile, "ERROR: $errorMessage\n", FILE_APPEND);
        error_log($errorMessage);
        return false;
    }
}
?>
