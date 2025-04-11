<?php
require_once 'config/config.php';
require_once 'config/google_cloud_config.php';

/**
 * Transcript of Records Scanner Class
 * 
 * This class uses Google Cloud Vision OCR to scan transcript of records,
 * identify column headers, and extract the relevant data (subject codes,
 * descriptions, grades, and units).
 */
class TORScanner {
    private $apiKey;
    private $apiEndpoint;
    private $conn;
    
    /**
     * Class constructor
     * 
     * @param object $conn Database connection
     */
    public function __construct($conn) {
        $this->apiKey = GOOGLE_CLOUD_API_KEY;
        $this->apiEndpoint = GOOGLE_CLOUD_VISION_ENDPOINT;
        $this->conn = $conn;
    }
    
    /**
     * Process an image of a Transcript of Records
     * 
     * @param string $imagePath Path to the image file
     * @return array Extracted transcript data
     */
    public function processImage($imagePath) {
        // Step 1: Get image content and encode as base64
        $imageContent = file_get_contents($imagePath);
        if ($imageContent === false) {
            throw new Exception("Failed to read image file: $imagePath");
        }
        
        $base64Image = base64_encode($imageContent);
        
        // Step 2: Prepare the request payload for Google Cloud Vision API
        $requestPayload = [
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
        
        // Step 3: Send the request to Google Cloud Vision API
        $response = $this->callGoogleCloudVisionAPI($requestPayload);
        
        // Step 4: Process the OCR response to extract transcript data
        return $this->extractTranscriptData($response);
    }
    
    /**
     * Call Google Cloud Vision API
     * 
     * @param array $requestPayload API request data
     * @return array API response
     */
    private function callGoogleCloudVisionAPI($requestPayload) {
        $ch = curl_init();
        
        $url = $this->apiEndpoint . '?key=' . $this->apiKey;
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestPayload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // In production, set to true
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Extract transcript data from OCR response
     * 
     * @param array $ocrResponse Google Cloud Vision API response
     * @return array Structured transcript data
     */
    private function extractTranscriptData($ocrResponse) {
        // Check if we have valid text annotations
        if (empty($ocrResponse['responses'][0]['fullTextAnnotation']['text'])) {
            throw new Exception('No text detected in the image');
        }
        
        // Get the extracted text content
        $extractedText = $ocrResponse['responses'][0]['fullTextAnnotation']['text'];
        
        // For debugging
        // file_put_contents('debug_output.txt', $extractedText);
        
        // Process the text to identify column headers and extract data
        return $this->parseTranscriptText($extractedText);
    }
    
    /**
     * Parse transcript text to extract structured data
     * 
     * @param string $text Raw OCR text
     * @return array Structured transcript data
     */
    private function parseTranscriptText($text) {
        // Split text into lines
        $lines = explode("\n", $text);
        
        // Initialize variables to hold header positions
        $subjectCodeHeader = -1;
        $subjectDescHeader = -1;
        $unitsHeader = -1;
        $gradesHeader = -1;
        
        // Search for column headers
        foreach ($lines as $index => $line) {
            // Convert to lowercase for case-insensitive matching
            $lowerLine = strtolower($line);
            
            // Look for common header variations
            if (strpos($lowerLine, 'subject code') !== false || 
                strpos($lowerLine, 'course code') !== false || 
                strpos($lowerLine, 'subj code') !== false) {
                $subjectCodeHeader = $index;
            }
            
            if (strpos($lowerLine, 'subject description') !== false || 
                strpos($lowerLine, 'course description') !== false || 
                strpos($lowerLine, 'subject title') !== false || 
                strpos($lowerLine, 'course title') !== false || 
                strpos($lowerLine, 'descriptive title') !== false) {
                $subjectDescHeader = $index;
            }
            
            if (strpos($lowerLine, 'units') !== false || 
                strpos($lowerLine, 'credit') !== false || 
                strpos($lowerLine, 'lec') !== false) {
                $unitsHeader = $index;
            }
            
            if (strpos($lowerLine, 'grade') !== false || 
                strpos($lowerLine, 'rating') !== false || 
                strpos($lowerLine, 'remarks') !== false) {
                $gradesHeader = $index;
            }
            
            // If we found all headers, break early
            if ($subjectCodeHeader >= 0 && $subjectDescHeader >= 0 && 
                $unitsHeader >= 0 && $gradesHeader >= 0) {
                break;
            }
        }
        
        // If we couldn't find all the headers, we try a different approach
        if ($subjectCodeHeader < 0 || $subjectDescHeader < 0 || $unitsHeader < 0 || $gradesHeader < 0) {
            // Try to determine headers by looking for a pattern in the text
            return $this->detectHeadersFromPattern($lines);
        }
        
        // Determine the starting line for data (after all headers)
        $dataStartLine = max($subjectCodeHeader, $subjectDescHeader, $unitsHeader, $gradesHeader) + 1;
        
        // Parse the data
        $subjectData = [];
        
        for ($i = $dataStartLine; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Try to extract subject code
            $subjectCode = $this->extractSubjectCode($line);
            
            if (!empty($subjectCode)) {
                // If we found a valid subject code, extract other data
                $subjectDesc = $this->extractSubjectDescription($lines, $i);
                $units = $this->extractUnits($lines, $i);
                $grade = $this->extractGrade($lines, $i);
                
                // Add to our data array
                $subjectData[] = [
                    'subject_code' => $subjectCode,
                    'description' => $subjectDesc,
                    'units' => $units,
                    'grade' => $grade
                ];
            }
        }
        
        return $subjectData;
    }
    
    /**
     * Try to detect headers from pattern in data
     * 
     * @param array $lines Lines of text from OCR
     * @return array Structured transcript data
     */
    private function detectHeadersFromPattern($lines) {
        $subjectData = [];
        $patterns = [
            'subject_code' => '/^[A-Z]{2,4}\s*\d{3,4}[A-Z]?/i',
            'units' => '/\b\d{1,2}(\.\d)?\b/',
            'grade' => '/\b[0-9.]{1,4}\b|\b(PASSED|FAILED|INC|DROP)\b/i'
        ];
        
        foreach ($lines as $i => $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Check if line matches subject code pattern
            if (preg_match($patterns['subject_code'], $line, $codeMatches)) {
                $subjectCode = trim($codeMatches[0]);
                
                // Extract subject description (assuming it's in the same line after the code)
                $subjectDesc = trim(preg_replace($patterns['subject_code'], '', $line));
                
                // Look for units in current line or next lines
                $units = '';
                for ($j = $i; $j < min($i + 3, count($lines)); $j++) {
                    if (preg_match($patterns['units'], $lines[$j], $unitMatches)) {
                        $units = $unitMatches[0];
                        break;
                    }
                }
                
                // Look for grade in current line or next lines
                $grade = '';
                for ($j = $i; $j < min($i + 3, count($lines)); $j++) {
                    if (preg_match($patterns['grade'], $lines[$j], $gradeMatches)) {
                        $grade = $gradeMatches[0];
                        break;
                    }
                }
                
                // If description is too short, try to combine with next line
                if (strlen($subjectDesc) < 10 && isset($lines[$i + 1])) {
                    $nextLine = trim($lines[$i + 1]);
                    if (!preg_match($patterns['subject_code'], $nextLine)) {
                        $subjectDesc .= ' ' . $nextLine;
                    }
                }
                
                // Clean extracted description by removing potential grade and units
                $subjectDesc = preg_replace($patterns['units'], '', $subjectDesc);
                $subjectDesc = preg_replace($patterns['grade'], '', $subjectDesc);
                $subjectDesc = trim($subjectDesc);
                
                // Add to our data array if we have valid data
                if (!empty($subjectCode) && !empty($subjectDesc)) {
                    $subjectData[] = [
                        'subject_code' => $subjectCode,
                        'description' => $subjectDesc,
                        'units' => $units,
                        'grade' => $grade
                    ];
                }
            }
        }
        
        return $subjectData;
    }
    
    /**
     * Extract subject code from line
     * 
     * @param string $line Line of text
     * @return string Extracted subject code
     */
    private function extractSubjectCode($line) {
        // Common patterns for subject codes (e.g., CS101, MATH 202, ENG 101A)
        $pattern = '/^[A-Z]{2,4}\s*\d{3,4}[A-Z]?/i';
        
        if (preg_match($pattern, $line, $matches)) {
            return trim($matches[0]);
        }
        
        return '';
    }
    
    /**
     * Extract subject description
     * 
     * @param array $lines Lines of text
     * @param int $lineIndex Current line index
     * @return string Extracted subject description
     */
    private function extractSubjectDescription($lines, $lineIndex) {
        $line = $lines[$lineIndex];
        
        // Remove subject code from line
        $description = preg_replace('/^[A-Z]{2,4}\s*\d{3,4}[A-Z]?/i', '', $line);
        
        // Remove potential grade and units
        $description = preg_replace('/\b\d{1,2}(\.\d)?\b/', '', $description);
        $description = preg_replace('/\b[0-9.]{1,4}\b|\b(PASSED|FAILED|INC|DROP)\b/i', '', $description);
        
        // Clean up
        $description = trim($description);
        
        // If description is too short, try to combine with next line
        if (strlen($description) < 10 && isset($lines[$lineIndex + 1])) {
            $nextLine = trim($lines[$lineIndex + 1]);
            if (!preg_match('/^[A-Z]{2,4}\s*\d{3,4}[A-Z]?/i', $nextLine)) {
                $description .= ' ' . $nextLine;
            }
        }
        
        return trim($description);
    }
    
    /**
     * Extract units value
     * 
     * @param array $lines Lines of text
     * @param int $lineIndex Current line index
     * @return string Extracted units
     */
    private function extractUnits($lines, $lineIndex) {
        $line = $lines[$lineIndex];
        
        // Look for numbers that might represent units (typically 1-5)
        if (preg_match('/\b\d{1,2}(\.\d)?\b/', $line, $matches)) {
            return $matches[0];
        }
        
        // Check next line if not found in current line
        if (isset($lines[$lineIndex + 1])) {
            if (preg_match('/\b\d{1,2}(\.\d)?\b/', $lines[$lineIndex + 1], $matches)) {
                return $matches[0];
            }
        }
        
        return '';
    }
    
    /**
     * Extract grade value
     * 
     * @param array $lines Lines of text
     * @param int $lineIndex Current line index
     * @return string Extracted grade
     */
    private function extractGrade($lines, $lineIndex) {
        $line = $lines[$lineIndex];
        
        // Look for possible grade formats (1.0, 2.5, PASSED, FAILED, etc.)
        if (preg_match('/\b[0-9.]{1,4}\b|\b(PASSED|FAILED|INC|DROP)\b/i', $line, $matches)) {
            return $matches[0];
        }
        
        // Check next line if not found in current line
        if (isset($lines[$lineIndex + 1])) {
            if (preg_match('/\b[0-9.]{1,4}\b|\b(PASSED|FAILED|INC|DROP)\b/i', $lines[$lineIndex + 1], $matches)) {
                return $matches[0];
            }
        }
        
        return '';
    }
    
    /**
     * Save transcript data to database
     * 
     * @param array $transcriptData Array of subject data
     * @param int $studentId Student ID
     * @return bool Success status
     */
    public function saveToDatabase($transcriptData, $studentId) {
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Prepare statement for inserting transcript data
            $stmt = $this->conn->prepare(
                "INSERT INTO transcript_subjects 
                (student_id, subject_code, description, units, grade) 
                VALUES (?, ?, ?, ?, ?)"
            );
            
            // Insert each subject
            foreach ($transcriptData as $subject) {
                $stmt->bind_param(
                    "issss", 
                    $studentId, 
                    $subject['subject_code'], 
                    $subject['description'], 
                    $subject['units'], 
                    $subject['grade']
                );
                
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            throw $e;
        }
    }
}

// API endpoint handling - only process if this is a POST request with submit parameter
if (isset($_POST['submit']) && isset($_FILES['tor_image'])) {
    try {
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($_FILES["tor_image"]["name"]);
        
        // Check if directory exists, create if not
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES["tor_image"]["tmp_name"], $targetFile)) {
            // Initialize TOR scanner
            $scanner = new TORScanner($conn);
            
            // Process the image
            $transcriptData = $scanner->processImage($targetFile);
            
            // Return the extracted data as JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $transcriptData
            ]);
            
            // If student_id is provided, save to database
            if (isset($_POST['student_id'])) {
                $scanner->saveToDatabase($transcriptData, $_POST['student_id']);
            }
            
        } else {
            throw new Exception("Failed to upload file.");
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    // Important: Exit after JSON output to prevent HTML rendering
    exit;
}
?>

<!-- HTML form for file upload (optional, can be included in another file) -->
<!DOCTYPE html>
<html>
<head>
    <title>TOR Scanner</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"], input[type="text"] {
            padding: 8px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        #resultContainer {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            display: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Transcript of Records Scanner</h1>
        
        <form id="torForm" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="tor_image">Upload TOR Image:</label>
                <input type="file" name="tor_image" id="tor_image" accept="image/*" required>
            </div>
            
            <div class="form-group">
                <label for="student_id">Student ID (optional):</label>
                <input type="text" name="student_id" id="student_id">
            </div>
            
            <button type="submit" name="submit">Scan TOR</button>
        </form>
        
        <div id="resultContainer">
            <h2>Extracted Data</h2>
            <div id="resultContent"></div>
        </div>
    </div>
    
    <script>
        document.getElementById('torForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('tor_scanner.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultContainer = document.getElementById('resultContainer');
                const resultContent = document.getElementById('resultContent');
                
                if (data.success) {
                    let tableHtml = '<table>';
                    tableHtml += '<tr><th>Subject Code</th><th>Description</th><th>Units</th><th>Grade</th></tr>';
                    
                    data.data.forEach(subject => {
                        tableHtml += `<tr>
                            <td>${subject.subject_code || '-'}</td>
                            <td>${subject.description || '-'}</td>
                            <td>${subject.units || '-'}</td>
                            <td>${subject.grade || '-'}</td>
                        </tr>`;
                    });
                    
                    tableHtml += '</table>';
                    resultContent.innerHTML = tableHtml;
                } else {
                    resultContent.innerHTML = `<p style="color: red">Error: ${data.error}</p>`;
                }
                
                resultContainer.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('resultContent').innerHTML = `<p style="color: red">Error: ${error.message}</p>`;
                document.getElementById('resultContainer').style.display = 'block';
            });
        });
    </script>
</body>
</html> 