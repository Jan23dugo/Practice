<?php
// Start session to access session variables
session_start();

// Include necessary files
include 'config/config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/test/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Create logs directory if it doesn't exist
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Process uploaded file
    $file = $_FILES['test_image'];
    $fileName = basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        $error = "Invalid file type. Only JPG, PNG, and PDF files are allowed.";
    } elseif ($file['size'] > 5000000) { // 5MB limit
        $error = "File is too large. Maximum size is 5MB.";
    } else {
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Include the OCR function from the main file
            require_once 'vendor/autoload.php';
            
            // Define the OCR function if it's not already included
            if (!function_exists('performOCR')) {
                function performOCR($imagePath) {
                    require_once 'config/google_cloud_config.php';
                    
                    try {
                        // Create temp log directory if it doesn't exist
                        $logDir = __DIR__ . '/logs';
                        if (!file_exists($logDir)) {
                            mkdir($logDir, 0755, true);
                        }
                        
                        // Log the OCR process
                        $logFile = $logDir . '/ocr_process_' . date('Y-m-d_H-i-s') . '.log';
                        file_put_contents($logFile, "Starting OCR process for: $imagePath\n", FILE_APPEND);
                        
                        // Read the image file and encode as base64
                        $imageContent = file_get_contents($imagePath);
                        $base64Image = base64_encode($imageContent);
                        file_put_contents($logFile, "Image loaded, size: " . strlen($imageContent) . " bytes\n", FILE_APPEND);
                        
                        // Prepare request payload
                        $payload = [
                            'requests' => [
                                [
                                    'image' => [
                                        'content' => $base64Image
                                    ],
                                    'features' => [
                                        [
                                            'type' => 'DOCUMENT_TEXT_DETECTION',
                                            'maxResults' => 1
                                        ]
                                    ]
                                ]
                            ]
                        ];
                        
                        // Initialize HTTP client
                        $client = new GuzzleHttp\Client([
                            // Use the system-configured CA certificate
                            // Remove the 'verify' => false line once you've set up curl.cainfo
                            'verify' => ini_get('curl.cainfo') ?: false
                        ]);
                        
                        // Make API request
                        $response = $client->post(GOOGLE_VISION_API_ENDPOINT . '?key=' . GOOGLE_API_KEY, [
                            'json' => $payload,
                            'headers' => [
                                'Content-Type' => 'application/json'
                            ]
                        ]);
                        
                        // Process response
                        $result = json_decode($response->getBody(), true);
                        file_put_contents($logFile, "API Response received\n", FILE_APPEND);
                        
                        // Check if we have text annotations
                        if (!isset($result['responses'][0]['textAnnotations']) || empty($result['responses'][0]['textAnnotations'])) {
                            file_put_contents($logFile, "No text detected in the image\n", FILE_APPEND);
                            return '';
                        }
                        
                        // Get the full text from the first annotation (which contains all text)
                        $fullText = $result['responses'][0]['textAnnotations'][0]['description'];
                        file_put_contents($logFile, "Raw OCR Text:\n$fullText\n", FILE_APPEND);
                        
                        return $fullText;
                        
                    } catch (\Exception $e) {
                        error_log("Google Cloud Vision OCR Error: " . $e->getMessage());
                        file_put_contents($logFile, "OCR Error: " . $e->getMessage() . "\n", FILE_APPEND);
                        throw $e;
                    }
                }
            }
            
            // Perform OCR on the uploaded image
            try {
                $ocrText = performOCR($targetPath);
                $_SESSION['ocr_test_result'] = $ocrText;
                
                // Also try to extract subjects
                if (function_exists('extractSubjects')) {
                    $subjects = extractSubjects($ocrText);
                    $_SESSION['extracted_subjects'] = $subjects;
                } else {
                    $_SESSION['extracted_subjects'] = "extractSubjects function not available";
                }
                
                $success = "OCR processing completed successfully. View the results in the OCR Log Viewer.";
            } catch (Exception $e) {
                $error = "OCR Error: " . $e->getMessage();
            }
        } else {
            $error = "Failed to upload file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCR Test Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #75343A;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .panel {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #75343A;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #5a2930;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .result {
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            white-space: pre-wrap;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
        }
        .nav-links {
            margin-top: 20px;
            padding: 10px;
            background: #f1f1f1;
            border-radius: 4px;
        }
        .nav-links a {
            color: #75343A;
            margin-right: 15px;
            text-decoration: none;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>OCR Test Tool</h1>
        <p>Use this tool to test the OCR functionality with your transcript image.</p>
        
        <div class="panel">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="test_image">Upload Transcript Image (JPG, PNG, or PDF)</label>
                    <input type="file" id="test_image" name="test_image" required>
                    <small>Maximum file size: 5MB</small>
                </div>
                <button type="submit">Process Image</button>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['ocr_test_result']) && !empty($_SESSION['ocr_test_result'])): ?>
            <h2>OCR Result Preview</h2>
            <div class="result"><?php echo htmlspecialchars(substr($_SESSION['ocr_test_result'], 0, 1000)); ?>
                <?php if (strlen($_SESSION['ocr_test_result']) > 1000): ?>
                    ... (truncated, see full result in OCR Log Viewer)
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="nav-links">
            <a href="ocr_log_viewer.php">View Full OCR Logs</a>
            <a href="qualiexam_register.php">Back to Registration</a>
        </div>
    </div>
</body>
</html> 