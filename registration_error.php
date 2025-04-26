<?php
session_start();

// If user directly accesses this page without an error, redirect to registration page
if (!isset($_SESSION['registration_error'])) {
    header("Location: qualiexam_register.php");
    exit();
}

$error_message = $_SESSION['registration_error'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Error</title>
    <style>
        :root {
            --error-color: #f44336;
            --error-dark: #d32f2f;
            --background-color: #f9f9f9;
            --text-color: #333;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .error-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 4px solid var(--error-color);
        }

        .error-icon {
            color: var(--error-color);
            font-size: 48px;
            margin-bottom: 20px;
        }

        h1 {
            color: var(--error-color);
            margin-bottom: 20px;
        }

        p {
            margin-bottom: 25px;
            color: #666;
        }

        .error-details {
            background-color: #fff5f5;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            text-align: left;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--error-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
            margin: 0 10px;
        }

        .button:hover {
            background-color: var(--error-dark);
        }

        .button.secondary {
            background-color: #757575;
        }

        .button.secondary:hover {
            background-color: #616161;
        }

        .troubleshooting {
            margin-top: 30px;
            text-align: left;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }

        .troubleshooting h2 {
            color: var(--text-color);
            font-size: 18px;
            margin-bottom: 15px;
        }

        .troubleshooting ul {
            margin: 0;
            padding-left: 20px;
        }

        .troubleshooting li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">✕</div>
        <h1>Registration Error</h1>
        <p>We encountered an error while processing your registration.</p>
        
        <div class="error-details">
            <?php echo htmlspecialchars($error_message); ?>
        </div>

        <div class="troubleshooting">
            <h2>Troubleshooting Steps:</h2>
            <ul>
                <li>Check that all required fields are filled out correctly</li>
                <li>Ensure your uploaded documents are in the correct format (JPG, JPEG, or PNG)</li>
                <li>Verify that your file sizes are under 5MB</li>
                <li>Make sure your documents are clear and readable</li>
            </ul>
        </div>

        <div style="margin-top: 30px;">
            <a href="qualiexam_register.php" class="button">Try Again</a>
            <a href="dashboard.php" class="button secondary">Go to Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php
// Clear the error message from session
unset($_SESSION['registration_error']);
?> 