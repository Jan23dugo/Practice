<?php
// Replace with your own details
$endpoint = "https://streamsocr.cognitiveservices.azure.com/";
$apiKey = "7YOiSya9zTZO2WkLje6TdmiSaoG0kKLvcWy2kdFuMXqzKcu9Jr0XJQQJ99BDACqBBLyXJ3w3AAALACOGbhSw";
$modelId = "transcript_extractor_v1";

// Function to poll for results
function pollForResults($operationLocation, $apiKey, $maxAttempts = 10) {
    $resultHeaders = [
        "Ocp-Apim-Subscription-Key: $apiKey"
    ];
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $ch = curl_init($operationLocation);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $resultHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        $json = json_decode($result, true);
        
        // If completed or error, return results
        if (isset($json['status']) && ($json['status'] === 'succeeded' || $json['status'] === 'failed')) {
            return $json;
        }
        
        // Wait longer between each attempt
        $waitTime = $attempt * 2;
        echo "<p>Processing document... (Attempt $attempt of $maxAttempts, waiting $waitTime seconds)</p>";
        echo "<script>setTimeout(function() { window.location.reload(); }, " . ($waitTime * 1000) . ");</script>";
        ob_flush();
        flush();
        exit;
    }
    
    return ['status' => 'timeout', 'message' => 'Operation timed out after ' . $maxAttempts . ' attempts'];
}

// For storing operation location between refreshes
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $file = $_FILES["file"]["tmp_name"];
    $fileData = file_get_contents($file);

    // Use the correct format for 2024-11-30 API version
    $url = $endpoint . "documentintelligence/documentModels/$modelId:analyze?api-version=2024-11-30";

    $headers = [
        "Content-Type: application/octet-stream", // This handles both images and PDFs
        "Ocp-Apim-Subscription-Key: $apiKey"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); // To capture operation-location header

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers_str = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    // For debugging
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (preg_match('/operation-location: (.*)/i', $headers_str, $matches)) {
        $operationLocation = trim($matches[1]);
        
        // Store operation location in session
        $_SESSION['operation_location'] = $operationLocation;
        
        // Start polling
        $results = pollForResults($operationLocation, $apiKey);
        displayResults($results);
    } else {
        echo "❌ Error: Failed to get operation-location.<br>";
        echo "HTTP Status Code: $http_code<br>";
        echo "<pre>$response</pre>";
    }

    curl_close($ch);
} elseif (isset($_SESSION['operation_location'])) {
    // Continue polling from previous operation
    $results = pollForResults($_SESSION['operation_location'], $apiKey);
    displayResults($results);
}

// Function to display results in a readable format
function displayResults($results) {
    if ($results['status'] === 'succeeded') {
        echo "<h3>Analysis Complete!</h3>";
        
        if (isset($results['analyzeResult']['documents']) && count($results['analyzeResult']['documents']) > 0) {
            $document = $results['analyzeResult']['documents'][0]; // Get the first document
            
            if (isset($document['fields'])) {
                // Format data for output
                $formattedData = [];
                
                // Extract and clean the subject codes
                $subjectCodesStr = isset($document['fields']['subject_code']['content']) ? 
                    $document['fields']['subject_code']['content'] : '';
                // Split by -M and clean up
                $subjectCodes = array_filter(explode('-M', $subjectCodesStr), 'strlen');
                $subjectCodes = array_map(function($code) {
                    return trim($code) . '-M';
                }, $subjectCodes);
                
                // Get units and convert to array (remove 'CREDITS' and split)
                $unitsStr = isset($document['fields']['units']['content']) ? 
                    str_replace('CREDITS', '', $document['fields']['units']['content']) : '';
                $units = array_values(array_filter(array_map('trim', explode(' ', $unitsStr))));
                
                // Get grades (remove prefix and split)
                $gradesStr = isset($document['fields']['grade']['content']) ? 
                    preg_replace('/^GRADES\s+Final\s+Completion\s+/', '', $document['fields']['grade']['content']) : '';
                $grades = array_values(array_filter(array_map('trim', explode(' ', $gradesStr))));
                
                // Get and parse subject descriptions
                $descriptionsStr = isset($document['fields']['subject_description']['content']) ? 
                    $document['fields']['subject_description']['content'] : '';
                
                // Split descriptions by common delimiters
                $descriptions = [];
                $descParts = preg_split('/(?<=\w)(?=[A-Z][a-z])|\s+(?=[A-Z][a-z]+\s)/', $descriptionsStr);
                foreach ($descParts as $part) {
                    if (!empty(trim($part))) {
                        $descriptions[] = trim($part);
                    }
                }
                
                // Create structured JSON object
                $formattedData['subjects'] = [];
                
                // Process each subject
                foreach ($subjectCodes as $index => $code) {
                    $subject = [
                        'subject_code' => trim($code),
                        'subject_description' => isset($descriptions[$index]) ? trim($descriptions[$index]) : '',
                        'units' => isset($units[$index]) ? floatval($units[$index]) : 0,
                        'grade' => isset($grades[$index]) ? floatval($grades[$index]) : 0
                    ];
                    
                    $formattedData['subjects'][] = $subject;
                }
                
                // Display formatted JSON
                echo "<h4>Structured Data Output:</h4>";
                echo "<pre>";
                echo json_encode($formattedData, JSON_PRETTY_PRINT);
                echo "</pre>";
                
                // Display data in table format
                echo "<h4>Tabular Format:</h4>";
                echo "<div style='margin-left: 20px; overflow-x: auto;'>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; min-width: 800px;'>";
                echo "<tr style='background-color: #f2f2f2;'>";
                echo "<th>Subject Code</th>";
                echo "<th>Description</th>";
                echo "<th>Units</th>";
                echo "<th>Grade</th>";
                echo "</tr>";
                
                foreach ($formattedData['subjects'] as $subject) {
                    echo "<tr>";
                    echo "<td>{$subject['subject_code']}</td>";
                    echo "<td>{$subject['subject_description']}</td>";
                    echo "<td>{$subject['units']}</td>";
                    echo "<td>{$subject['grade']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                echo "</div>";
                
                // Download buttons
                echo "<div style='margin: 20px 0;'>";
                echo "<a href='data:application/json;charset=utf-8," . urlencode(json_encode($formattedData)) . "' download='transcript_data.json' class='download-btn'>Download JSON</a>";
                
                // Add CSV download option
                $csv = "Subject Code,Description,Units,Grade\n";
                foreach ($formattedData['subjects'] as $subject) {
                    $csv .= "\"{$subject['subject_code']}\",\"{$subject['subject_description']}\",{$subject['units']},{$subject['grade']}\n";
                }
                echo " <a href='data:text/csv;charset=utf-8," . urlencode($csv) . "' download='transcript_data.csv' class='download-btn'>Download CSV</a>";
                echo "</div>";
                
                // Display debug information
                echo "<h4>Debug Information:</h4>";
                echo "<pre>";
                echo "Number of subjects found: " . count($subjectCodes) . "\n";
                echo "Number of descriptions matched: " . count($descriptions) . "\n";
                echo "Number of units found: " . count($units) . "\n";
                echo "Number of grades found: " . count($grades) . "\n\n";
                
                echo "Subject Codes:\n";
                print_r($subjectCodes);
                echo "\nUnits:\n";
                print_r($units);
                echo "\nGrades:\n";
                print_r($grades);
                echo "</pre>";
            }
        } else {
            echo "<p>No documents or fields found in the results.</p>";
        }
        
        // Clear the session
        unset($_SESSION['operation_location']);
    } elseif ($results['status'] === 'failed') {
        echo "<h3>❌ Analysis Failed</h3>";
        echo "<pre>";
        print_r($results);
        echo "</pre>";
        
        // Clear the session
        unset($_SESSION['operation_location']);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Azure OCR Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h2, h3, h4 {
            color: #333;
        }
        form {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        input[type="file"] {
            margin-bottom: 10px;
        }
        button, .download-btn {
            padding: 8px 15px;
            background-color: #0078d4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .download-btn:hover {
            background-color: #005a9e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #f2f2f2;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['operation_location'])): ?>
    <h2>Upload Transcript (Image or PDF)</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <br><br>
        <button type="submit">Analyze</button>
    </form>
    <?php else: ?>
    <h2>Processing Document...</h2>
    <p>Please wait while your document is being analyzed.</p>
    <?php endif; ?>
</body>
</html>
