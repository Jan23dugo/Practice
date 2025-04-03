<?php
// Start session
session_start();

// Include config if needed
require_once 'config/google_cloud_config.php';

// Function to perform OCR using direct cURL
function performSimpleOCR($imagePath) {
    global $GOOGLE_API_KEY;
    
    // Create logs directory if it doesn't exist
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Log file
    $logFile = $logDir . '/simple_ocr_' . date('Y-m-d_H-i-s') . '.log';
    file_put_contents($logFile, "Starting Simple OCR test for: $imagePath\n", FILE_APPEND);
    
    try {
        // Read image and encode as base64
        $imageContent = file_get_contents($imagePath);
        $base64Image = base64_encode($imageContent);
        
        // Prepare JSON payload
        $payload = json_encode([
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
        ]);
        
        // API endpoint
        $url = "https://vision.googleapis.com/v1/images:annotate?key=" . $GOOGLE_API_KEY;
        
        // Initialize cURL
        $ch = curl_init($url);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable host verification
        
        // Execute cURL request
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            file_put_contents($logFile, "cURL Error: $error\n", FILE_APPEND);
            curl_close($ch);
            return "cURL Error: $error";
        }
        
        curl_close($ch);
        
        // Process response
        $result = json_decode($response, true);
        
        // Check if we have text annotations
        if (!isset($result['responses'][0]['textAnnotations']) || empty($result['responses'][0]['textAnnotations'])) {
            file_put_contents($logFile, "No text detected in the image\n", FILE_APPEND);
            return "No text detected in the image.";
        }
        
        // Get the full text
        $fullText = $result['responses'][0]['textAnnotations'][0]['description'];
        file_put_contents($logFile, "OCR Text:\n$fullText\n", FILE_APPEND);
        
        return $fullText;
        
    } catch (Exception $e) {
        file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return "Error: " . $e->getMessage();
    }
}

// Process form submission
$ocrResult = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/test/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Process uploaded file
    $file = $_FILES['image'];
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
            // Perform OCR
            $ocrResult = performSimpleOCR($targetPath);
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
    <title>Simple OCR Test</title>
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
        .result {
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            white-space: pre-wrap;
            font-family: monospace;
            max-height: 400px;
            overflow-y: auto;
        }
        .note {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple OCR Test</h1>
        <p>This is a simplified OCR test tool that bypasses SSL verification.</p>
        
        <div class="note">
            <strong>Note:</strong> This tool disables SSL certificate verification for testing purposes only. This approach is less secure but helps diagnose SSL-related issues.
        </div>
        
        <div class="panel">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="image">Upload Image (JPG, PNG, or PDF)</label>
                    <input type="file" id="image" name="image" required>
                    <small>Maximum file size: 5MB</small>
                </div>
                <button type="submit">Process Image</button>
            </form>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($ocrResult)): ?>
            <h2>OCR Result</h2>
            <div class="result"><?php echo htmlspecialchars($ocrResult); ?></div>
        <?php endif; ?>
    </div>
</body>
</html> 