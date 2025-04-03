<?php
// No need to manually require PHPMailer files if using Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class AdminMailConfig {
    public static function setupMailer() {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';  // Use your email provider's SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'pupccisfaculty@gmail.com';  // Your new admin email
            $mail->Password   = 'ylpk vege rinx fhcd';  // App password if using Gmail with 2FA
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Sender settings
            $mail->setFrom('pupccisfaculty@gmail.com', 'PUP Admin System');
            $mail->isHTML(true);
            
            return $mail;
        } catch (Exception $e) {
            error_log("Mail setup error: " . $e->getMessage());
            return false;
        }
    }
} 