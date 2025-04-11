<?php
require_once 'tor_training.php';

// Handle training form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'train') {
    // Check if required parameters are provided
    if (!isset($_POST['image_path']) || !isset($_POST['courses']) || !isset($_POST['university'])) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    $imagePath = $_POST['image_path'];
    $universityName = $_POST['university'];
    
    // Prepare courses data
    $courses = [];
    foreach ($_POST['courses'] as $course) {
        if (!empty($course['code'])) {
            $courses[] = [
                'code' => $course['code'],
                'description' => $course['description'] ?? '',
                'grade' => $course['grade'] ?? null,
                'units' => $course['units'] ?? null
            ];
        }
    }
    
    $correctData = ['courses' => $courses];
    
    // Train the system
    $trainer = new TORTraining();
    $result = $trainer->trainWithExample($imagePath, $correctData, $universityName);
    
    if ($result) {
        $successMessage = 'Training data saved successfully';
    } else {
        $errorMessage = 'Failed to save training data';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>TOR Scanner Training Interface</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #333;
        }
        form {
            margin: 20px 0;
            padding: 20px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        label, input, textarea, button {
            margin: 10px 0;
            display: block;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 3px;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border: 1px solid #ddd;
            overflow: auto;
        }
        .course-container {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            background: #fff;
        }
        .course-inputs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .course-inputs input {
            flex: 1;
            min-width: 100px;
        }
        .controls {
            margin: 15px 0;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        .success {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
        }
        .info {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
    </style>
</head>
<body>
    <h1>TOR Scanner Training Interface</h1>
    <p>Use this interface to train the system with new transcripts. Upload a transcript image, verify or correct the extracted data, and submit to train the system.</p>
    
    <?php if (isset($successMessage)): ?>
    <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
    <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>
    
    <?php
    // Handle the first step - scan a transcript
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
        // Debug file upload information
        echo '<div class="message info">Debug information:</div>';
        echo '<pre>';
        print_r($_FILES);
        echo '</pre>';
        
        $uploadDir = 'uploads/training_photos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            echo '<div class="message info">Created directory: ' . $uploadDir . '</div>';
        }
        
        // Check for upload errors
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = array(
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            );
            $errorMessage = isset($uploadErrors[$_FILES['image']['error']]) ? 
                            $uploadErrors[$_FILES['image']['error']] : 
                            'Unknown upload error';
            echo '<div class="message error">Upload error: ' . $errorMessage . '</div>';
        } else {
            // Handle file upload
            $tempFile = $_FILES['image']['tmp_name'];
            $targetFile = $uploadDir . basename($_FILES['image']['name']);
            
            echo '<div class="message info">Attempting to move uploaded file from ' . $tempFile . ' to ' . $targetFile . '</div>';
            
            if (move_uploaded_file($tempFile, $targetFile)) {
                echo '<div class="message success">File moved successfully.</div>';
                // Check if file is readable
                if (!is_readable($targetFile)) {
                    echo '<div class="message error">File is not readable after upload. Check permissions.</div>';
                } else {
                    // Process the image
                    try {
                        $torScanner = new TORScanner();
                        $torScanner->setDebug(true);
                        echo '<div class="message info">Processing image with TORScanner...</div>';
                        $result = $torScanner->processImage($targetFile);
                        
                        if (isset($result['error'])) {
                            echo '<div class="message error">Scanning error: ' . htmlspecialchars($result['error']) . '</div>';
                        } else {
                            echo '<div class="message success">Image uploaded and processed successfully.</div>';
                            
                            // Display form to verify and correct data
                            echo '<h2>Verify Extraction Results</h2>';
                            echo '<p>Review the extracted data below. Make any necessary corrections before submitting to train the system.</p>';
                            
                            echo '<form method="post" action="train.php">';
                            echo '<input type="hidden" name="action" value="train">';
                            echo '<input type="hidden" name="image_path" value="' . htmlspecialchars($targetFile) . '">';
                            
                            echo '<label for="university">University/Institution Name:</label>';
                            $institution = isset($result['metadata']['institution']) ? $result['metadata']['institution'] : '';
                            echo '<input type="text" id="university" name="university" value="' . htmlspecialchars($institution) . '" required>';
                            
                            echo '<h3>Courses</h3>';
                            echo '<div id="courses-container">';
                            
                            if (isset($result['courses']) && !empty($result['courses'])) {
                                foreach ($result['courses'] as $i => $course) {
                                    echo '<div class="course-container">';
                                    echo '<div class="course-inputs">';
                                    echo '<input type="text" name="courses[' . $i . '][code]" placeholder="Course Code" value="' . htmlspecialchars($course['code'] ?? '') . '" required>';
                                    echo '<input type="text" name="courses[' . $i . '][description]" placeholder="Description" value="' . htmlspecialchars($course['description'] ?? '') . '">';
                                    echo '<input type="text" name="courses[' . $i . '][grade]" placeholder="Grade" value="' . htmlspecialchars($course['grade'] ?? '') . '">';
                                    echo '<input type="text" name="courses[' . $i . '][units]" placeholder="Units" value="' . htmlspecialchars($course['units'] ?? '') . '">';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                // If no courses extracted, provide empty form fields
                                echo '<div class="course-container">';
                                echo '<div class="course-inputs">';
                                echo '<input type="text" name="courses[0][code]" placeholder="Course Code" required>';
                                echo '<input type="text" name="courses[0][description]" placeholder="Description">';
                                echo '<input type="text" name="courses[0][grade]" placeholder="Grade">';
                                echo '<input type="text" name="courses[0][units]" placeholder="Units">';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            
                            echo '<div class="controls">';
                            echo '<button type="button" id="add-course">Add Another Course</button>';
                            echo '</div>';
                            
                            echo '<button type="submit">Submit Training Data</button>';
                            echo '</form>';
                            
                            // Show the raw extraction results
                            echo '<h2>Raw Extraction Results</h2>';
                            echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                            
                            // Add JavaScript for dynamic form functionality
                            echo '<script>
                                document.getElementById("add-course").addEventListener("click", function() {
                                    const container = document.getElementById("courses-container");
                                    const courseCount = container.children.length;
                                    
                                    const courseDiv = document.createElement("div");
                                    courseDiv.className = "course-container";
                                    
                                    const inputsDiv = document.createElement("div");
                                    inputsDiv.className = "course-inputs";
                                    
                                    const codeInput = document.createElement("input");
                                    codeInput.type = "text";
                                    codeInput.name = "courses[" + courseCount + "][code]";
                                    codeInput.placeholder = "Course Code";
                                    codeInput.required = true;
                                    
                                    const descInput = document.createElement("input");
                                    descInput.type = "text";
                                    descInput.name = "courses[" + courseCount + "][description]";
                                    descInput.placeholder = "Description";
                                    
                                    const gradeInput = document.createElement("input");
                                    gradeInput.type = "text";
                                    gradeInput.name = "courses[" + courseCount + "][grade]";
                                    gradeInput.placeholder = "Grade";
                                    
                                    const unitsInput = document.createElement("input");
                                    unitsInput.type = "text";
                                    unitsInput.name = "courses[" + courseCount + "][units]";
                                    unitsInput.placeholder = "Units";
                                    
                                    inputsDiv.appendChild(codeInput);
                                    inputsDiv.appendChild(descInput);
                                    inputsDiv.appendChild(gradeInput);
                                    inputsDiv.appendChild(unitsInput);
                                    
                                    courseDiv.appendChild(inputsDiv);
                                    container.appendChild(courseDiv);
                                });
                            </script>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="message error">Exception during processing: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                }
            }
        }
    } else if (!isset($_POST['action'])) {
        // Display the initial upload form
    ?>
    <form method="post" enctype="multipart/form-data">
        <div>
            <label for="image">Upload Transcript Image:</label>
            <input type="file" name="image" id="image" accept="image/*" required>
        </div>
        <button type="submit">Scan Image</button>
    </form>
    
    <h2>View Training Examples</h2>
    <p>View the existing training examples to see what the system has learned.</p>
    
    <?php
        // Display existing training examples if any
        $trainingDir = 'training_data';
        if (file_exists($trainingDir)) {
            $files = glob($trainingDir . '/*.json');
            if (!empty($files)) {
                echo '<ul>';
                foreach ($files as $file) {
                    $example = json_decode(file_get_contents($file), true);
                    if ($example) {
                        echo '<li>';
                        echo '<strong>' . htmlspecialchars($example['university']) . '</strong> - ';
                        echo 'Courses: ' . (isset($example['correct_data']['courses']) ? count($example['correct_data']['courses']) : 0);
                        echo ' <a href="view_training.php?file=' . urlencode(basename($file)) . '">View Details</a>';
                        echo '</li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<p>No training examples found. Upload and train with some examples first.</p>';
            }
        } else {
            echo '<p>No training data directory found. Training data will be created when you submit your first example.</p>';
        }
    }
    ?>
</body>
</html> 