<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto; }
        .section { margin-bottom: 20px; border: 1px solid #ccc; padding: 10px; }
        h2 { margin-top: 0; }
    </style>
</head>
<body>
    <h1>Registration Debug Information</h1>
    
    <div class="section">
        <h2>Session Data</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="section">
        <h2>Server Variables</h2>
        <pre><?php print_r($_SERVER); ?></pre>
    </div>
    
    <?php if (isset($_SESSION['ocr_raw_text'])): ?>
    <div class="section">
        <h2>OCR Raw Text</h2>
        <pre><?php echo htmlspecialchars($_SESSION['ocr_raw_text']); ?></pre>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['extracted_subjects'])): ?>
    <div class="section">
        <h2>Extracted Subjects</h2>
        <pre><?php print_r($_SESSION['extracted_subjects']); ?></pre>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['debug_info'])): ?>
    <div class="section">
        <h2>Debug Info</h2>
        <pre><?php print_r($_SESSION['debug_info']); ?></pre>
    </div>
    <?php endif; ?>
    
    <div class="section">
        <h2>Actions</h2>
        <p><a href="registerFront.php">Go to Registration Form</a></p>
        <p><a href="registration_success.php">Go to Registration Success</a></p>
        <p><a href="?clear_session=1">Clear Session Data</a></p>
    </div>
    
    <?php
    // Clear session if requested
    if (isset($_GET['clear_session'])) {
        session_unset();
        session_destroy();
        echo "<script>alert('Session cleared!'); window.location.href = 'debug_registration.php';</script>";
    }
    ?>
</body>
</html>
