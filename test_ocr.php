<?php
/*
 * This code sample shows Custom Model operations with the Azure Document Intelligence API.
 * Converted from the JavaScript SDK example to PHP.
 */

// Replace with your own details
$endpoint = "https://streamsocr.cognitiveservices.azure.com/";
$apiKey = "7YOiSya9zTZO2WkLje6TdmiSaoG0kKLvcWy2kdFuMXqzKcu9Jr0XJQQJ99BDACqBBLyXJ3w3AAALACOGbhSw";
$modelId = "transcript_extractor_v3";

function analyzeDocument($endpoint, $apiKey, $modelId, $fileData) {
    // Create the URL for the analyze operation
    $url = $endpoint . "documentintelligence/documentModels/" . $modelId . ":analyze?api-version=2024-11-30";
    
    // Set up the headers
    $headers = [
        "Content-Type: application/octet-stream",
        "Ocp-Apim-Subscription-Key: " . $apiKey
    ];

    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    // Execute the request
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers_str = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Get the operation-location header
    if (preg_match('/operation-location: (.*)/i', $headers_str, $matches)) {
        return trim($matches[1]);
    } else {
        throw new Exception("Failed to get operation location. HTTP Status: " . $http_code . "\nResponse: " . $body);
    }
}

function getResults($operationLocation, $apiKey, $maxAttempts = 30) {
    $headers = [
        "Ocp-Apim-Subscription-Key: " . $apiKey
    ];
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $ch = curl_init($operationLocation);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        $resultData = json_decode($result, true);
        
        if (isset($resultData['status'])) {
            if ($resultData['status'] === 'succeeded') {
                return $resultData;
            } else if ($resultData['status'] === 'failed') {
                throw new Exception("Analysis failed: " . json_encode($resultData));
            }
        }
        
        // Wait before next attempt
        $waitTime = min($attempt, 4); // Cap the wait time at 4 seconds
        sleep($waitTime);
    }
    
    throw new Exception("Operation timed out after " . $maxAttempts . " attempts");
}

function displayResults($results) {
    if ($results['status'] === 'succeeded') {
        echo "<h3>Analysis Complete!</h3>";
        
        $formattedData = ['subjects' => []];
        
        // Add debug output
        echo "<h4>Raw Analysis Result:</h4>";
        echo "<pre>";
        print_r($results);
        echo "</pre>";
        
        if (isset($results['analyzeResult']['documents']) && !empty($results['analyzeResult']['documents'])) {
            $document = $results['analyzeResult']['documents'][0];
            
            if (isset($document['fields'])) {
                echo "<h4>Available Fields in Model:</h4>";
                echo "<pre>";
                print_r(array_keys($document['fields']));
                echo "</pre>";
                
                $fields = $document['fields'];
                
                // Extract the table data from the 'extractTable' field
                if (isset($fields['extractTable']) && isset($fields['extractTable']['valueArray'])) {
                    echo "<h4>Found table data in field: extractTable</h4>";
                    
                    $rows = $fields['extractTable']['valueArray'];
                    
                    // Skip the first row if it's a header
                    for ($i = 1; $i < count($rows); $i++) {
                        if (isset($rows[$i]['valueObject'])) {
                            $rowData = $rows[$i]['valueObject'];
                            
                            // Output row structure for debugging
                            echo "<pre>Row structure: ";
                            print_r(array_keys($rowData));
                            echo "</pre>";
                            
                            // Map the semantic column names from your trained model
                            $subject_code = isset($rowData['Code']['valueString']) ? trim($rowData['Code']['valueString']) : '';
                            $subject_description = isset($rowData['Description']['valueString']) ? trim($rowData['Description']['valueString']) : '';
                            $grade = isset($rowData['Grades']['valueString']) ? trim($rowData['Grades']['valueString']) : '';
                            $units = isset($rowData['Units']['valueString']) ? floatval($rowData['Units']['valueString']) : 0;
                            
                            // Check if this is a continuation line (no subject code but has description)
                            if (empty($subject_code) && !empty($subject_description) && !empty($formattedData['subjects'])) {
                                // Append this description to the previous subject
                                $lastIndex = count($formattedData['subjects']) - 1;
                                $formattedData['subjects'][$lastIndex]['subject_description'] .= ' ' . $subject_description;
                                
                                // If grade or units were empty in previous entry but present in this one, use these values
                                if (empty($formattedData['subjects'][$lastIndex]['grade']) && !empty($grade)) {
                                    $formattedData['subjects'][$lastIndex]['grade'] = $grade;
                                }
                                
                                if ($formattedData['subjects'][$lastIndex]['units'] == 0 && $units > 0) {
                                    $formattedData['subjects'][$lastIndex]['units'] = $units;
                                }
                            } 
                            // Only add as new entry if we have some data to work with
                            else if (!empty($subject_code) || !empty($subject_description)) {
                                $subject = [
                                    'subject_code' => $subject_code,
                                    'subject_description' => $subject_description,
                                    'grade' => $grade,
                                    'units' => $units
                                ];
                                
                                $formattedData['subjects'][] = $subject;
                            }
                        }
                    }
                }
            }
        }
        
        // Display the results
        echo "<h4>Structured Data Output:</h4>";
        echo "<pre>";
        echo json_encode($formattedData, JSON_PRETTY_PRINT);
        echo "</pre>";
        
        if (!empty($formattedData['subjects'])) {
            displayTable($formattedData['subjects']);
            
            // Add download button
            $jsonData = json_encode($formattedData);
            echo "<div style='margin: 20px 0;'>";
            echo "<a href='data:application/json;charset=utf-8," . urlencode($jsonData) . "' download='transcript_data.json' class='download-btn'>Download JSON</a>";
            echo "</div>";
        }
        
        // Add refresh button
        echo "<div style='margin: 20px 0;'>";
        echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
        echo "<input type='hidden' name='clear_session' value='1'>";
        echo "<button type='submit' class='download-btn'>Process New Document</button>";
        echo "</form>";
        echo "</div>";
        
        unset($_SESSION['operation_location']);
    }
}

function displayTable($subjects) {
    echo "<h4>Data Alignment Check:</h4>";
    echo "<div style='margin-left: 20px;'>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>
            <th style='background-color: #f2f2f2;'>Index</th>
            <th style='background-color: #f2f2f2;'>Subject Code</th>
            <th style='background-color: #f2f2f2;'>Description</th>
            <th style='background-color: #f2f2f2;'>Units</th>
            <th style='background-color: #f2f2f2;'>Grade</th>
        </tr>";

    foreach ($subjects as $index => $subject) {
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>{$subject['subject_code']}</td>";
        echo "<td>{$subject['subject_description']}</td>";
        echo "<td>{$subject['units']}</td>";
        echo "<td>{$subject['grade']}</td>";
        echo "</tr>";
    }

    echo "</table>";
    echo "</div>";
}

// Start the session for managing the operation state
session_start();

// Handle session cleanup
if (isset($_POST['clear_session'])) {
    session_unset();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Main processing logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    try {
        $file = $_FILES["file"]["tmp_name"];
        $fileData = file_get_contents($file);

        // Start the analysis
        $operationLocation = analyzeDocument($endpoint, $apiKey, $modelId, $fileData);
        $_SESSION['operation_location'] = $operationLocation;
        
        // Get the results
        $results = getResults($operationLocation, $apiKey);
        displayResults($results);
    } catch (Exception $e) {
        echo "<h3>❌ Error</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        unset($_SESSION['operation_location']);
    }
} elseif (isset($_SESSION['operation_location'])) {
    try {
        $results = getResults($_SESSION['operation_location'], $apiKey);
        displayResults($results);
    } catch (Exception $e) {
        echo "<h3>❌ Error</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        unset($_SESSION['operation_location']);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Azure Document Intelligence Upload</title>
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
            margin-right: 10px;
        }
        button:hover, .download-btn:hover {
            background-color: #005a9e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
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
    <h2>Upload Transcript</h2>
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
