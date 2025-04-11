<?php
// Start output buffering and session
ob_start();
session_start();

// Include necessary files
include('config/config.php');
require 'vendor/autoload.php'; // For Google Cloud Vision
include('config/google_cloud_config.php');

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0777, true);
}

// Create uploads directories if they don't exist
$uploadDirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/tor',
    __DIR__ . '/uploads/school_id'
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Define required functions from qualiexam_registerBack.php
// Defining the functions manually instead of requiring the entire file

// Helper function for debug messages
function addDebugOutput($message, $data = null) {
    if (!isset($_SESSION['debug_output'])) {
        $_SESSION['debug_output'] = '';
    }
    $_SESSION['debug_output'] .= "<div style='background: #f5f5f5; margin: 10px 0; padding: 10px; border-left: 4px solid #008CBA;'>";
    $_SESSION['debug_output'] .= "<strong>$message</strong><br>";
    if ($data !== null) {
        $_SESSION['debug_output'] .= "<pre>" . print_r($data, true) . "</pre>";
    }
    $_SESSION['debug_output'] .= "</div>";
}

// Import the necessary functions only, without executing the main script
function importFunctionsFromFile($file) {
    // Extract only function definitions, not the procedural code
    $content = file_get_contents($file);
    
    // Extract all function definitions
    preg_match_all('/function\s+(\w+)\s*\([^)]*\)\s*{(?:[^{}]+|(?R))*}/', $content, $matches);
    
    // Create the import code - we'll use eval safely just for function definitions
    if (!empty($matches[0])) {
        foreach ($matches[0] as $function) {
            // Extract function name to check if it already exists
            preg_match('/function\s+(\w+)/', $function, $nameMatch);
            if (!empty($nameMatch[1]) && !function_exists($nameMatch[1])) {
                eval($function);
            }
        }
        return true;
    }
    return false;
}

// Import functions from the main file
if (!importFunctionsFromFile('qualiexam_registerBack.php')) {
    die('Failed to import functions from qualiexam_registerBack.php');
}

// Process form submission
$scanResults = [];
$error = '';
$subjects = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['tor'])) {
    try {
        // Check if file was uploaded without errors
        if ($_FILES['tor']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed with error code: ' . $_FILES['tor']['error']);
        }

        // Check file type
        $allowedTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'application/pdf'
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['tor']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
        }

        // Save the uploaded file
        $tor_path = 'uploads/tor/' . basename($_FILES['tor']['name']);
        move_uploaded_file($_FILES['tor']['tmp_name'], __DIR__ . '/' . $tor_path);

        // Process with OCR
        $ocr_output = performOCR($tor_path);
        if (empty($ocr_output)) {
            throw new Exception("The system could not read any text from the uploaded document.");
        }

        // Extract subjects using the functions from qualiexam_registerBack.php
        $subjects = extractSubjects($ocr_output);

        // Log the results
        $logFile = __DIR__ . '/logs/test_scan_' . date('Y-m-d_H-i-s') . '.txt';
        $logContent = "=== TEST SCAN RESULTS ===\n\n";
        $logContent .= "OCR Output:\n" . $ocr_output . "\n\n";
        $logContent .= "Extracted Subjects (" . count($subjects) . "):\n" . print_r($subjects, true);
        file_put_contents($logFile, $logContent);

        $scanResults = [
            'subject_count' => count($subjects),
            'log_file' => basename($logFile),
            'subjects' => $subjects
        ];

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOR Scanner Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .result-container {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .subject-table {
            margin-top: 20px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">TOR Scanner Test Tool</h1>
        <p class="text-center">Upload a Transcript of Records (TOR) to test the extraction functionality</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="tor" class="form-label">Upload TOR Image</label>
                <input type="file" class="form-control" id="tor" name="tor" required>
                <div class="form-text">Upload a clear image of the transcript (JPEG, PNG, or PDF)</div>
            </div>
            <button type="submit" class="btn btn-primary">Scan TOR</button>
        </form>

        <?php if (!empty($scanResults)): ?>
            <div class="result-container">
                <h3>Scan Results</h3>
                <p><strong>Extracted Subjects:</strong> <?php echo $scanResults['subject_count']; ?></p>
                <p><strong>Log File:</strong> <?php echo $scanResults['log_file']; ?> (check /logs directory)</p>
                
                <?php if (!empty($subjects)): ?>
                    <div class="subject-table">
                        <h4>Extracted Subjects</h4>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Description</th>
                                    <th>Grade</th>
                                    <th>Units</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['grade']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['units']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <h4>Raw Data</h4>
                        <pre><?php echo htmlspecialchars(print_r($subjects, true)); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 