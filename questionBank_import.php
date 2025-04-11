<?php
// Include database connection
include('config/config.php');

// Initialize variables
$preview_data = [];
$headers = [];
$error = null;
$success = null;
$preview_questions = []; // Array to store formatted question previews

// At the top of your file, add:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create a debug log function
function debug_log($message) {
    error_log("[Question Import Debug] " . $message);
}

// Handle file upload for preview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $original_filename = $_FILES['csv_file']['name'];
        
        // Store the original filename in session for display
        $_SESSION['uploaded_filename'] = $original_filename;
        
        // Check if it's a CSV file
        $file_info = pathinfo($original_filename);
        if (strtolower($file_info['extension']) !== 'csv') {
            $error = "Please upload a CSV file.";
        } else {
            // Save the uploaded file to a temporary location
            $temp_file = tempnam(sys_get_temp_dir(), 'csv_import_');
            move_uploaded_file($file, $temp_file);
            $_SESSION['csv_file_path'] = $temp_file;
            
            // Open the file
            if (($handle = fopen($temp_file, "r")) !== FALSE) {
                // Get headers
                $headers = fgetcsv($handle, 1000, ",");
                
                // Map headers to column indices
                $header_map = array_flip($headers);
                
                // Get up to 5 rows for preview
                $row_count = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && $row_count < 5) {
                    $preview_data[] = $data;
                    
                    // Format the data as a question preview
                    $question = [
                        'question_text' => $data[$header_map['question_text']] ?? 'Missing question text',
                        'question_type' => $data[$header_map['question_type']] ?? 'multiple-choice',
                        'category' => $data[$header_map['category']] ?? '',
                        'points' => (int)($data[$header_map['points']] ?? 1),
                        'answers' => []
                    ];
                    
                    // Process answers based on question type
                    if ($question['question_type'] === 'multiple-choice') {
                        // Handle multiple choice answers
                        for ($i = 1; $i <= 4; $i++) {
                            $answer_key = "answer_$i";
                            $correct_key = "correct_$i";
                            
                            if (isset($header_map[$answer_key]) && !empty($data[$header_map[$answer_key]])) {
                                $answer_text = $data[$header_map[$answer_key]];
                                $is_correct = (isset($header_map[$correct_key]) && isset($data[$header_map[$correct_key]]) && $data[$header_map[$correct_key]] == 1) ? 1 : 0;
                                
                                $question['answers'][] = [
                                    'answer_text' => $answer_text,
                                    'is_correct' => $is_correct
                                ];
                            }
                        }
                    } elseif ($question['question_type'] === 'true-false') {
                        // Handle true/false answers
                        $correct_answer = isset($header_map['correct_answer']) ? strtolower($data[$header_map['correct_answer']]) : '';
                        
                        $question['answers'][] = [
                            'answer_text' => 'True',
                            'is_correct' => ($correct_answer === 'true') ? 1 : 0
                        ];
                        
                        $question['answers'][] = [
                            'answer_text' => 'False',
                            'is_correct' => ($correct_answer === 'false') ? 1 : 0
                        ];
                    } elseif ($question['question_type'] === 'programming') {
                        // Handle programming question
                        $question['language'] = isset($header_map['language']) ? $data[$header_map['language']] : 'python';
                        $question['starter_code'] = isset($header_map['starter_code']) ? $data[$header_map['starter_code']] : '';
                    }
                    
                    $preview_questions[] = $question;
                    $row_count++;
                }
                
                fclose($handle);
                
                // Store the file path in session for later import
                $_SESSION['csv_file_path'] = $temp_file;
            } else {
                $error = "Failed to open the file.";
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Process the uploaded CSV file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    debug_log("Import process started");
    
    // Check if we have a file path in session
    if (isset($_SESSION['csv_file_path']) && file_exists($_SESSION['csv_file_path'])) {
        debug_log("Using file from session: " . $_SESSION['csv_file_path']);
        $file = $_SESSION['csv_file_path'];
        
        // Open the file
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Get the header row
            $header = fgetcsv($handle, 1000, ",");
            
            // Map headers to column indices for easier access
            $header_map = array_flip($header);
                
            // Check if the CSV has the required columns
            $required_columns = ['question_text', 'question_type'];
            $missing_columns = array_diff($required_columns, $header);
                
            if (!empty($missing_columns)) {
                $error = "CSV file is missing required columns: " . implode(', ', $missing_columns);
            } else {
                $conn->begin_transaction();
                
                try {
                    $imported_count = 0;
                    $skipped_count = 0;
                    
                    // Process each row
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        // Map data to column names
                        $row = [];
                        foreach ($header as $index => $column) {
                            $row[$column] = $data[$index] ?? '';
                        }
                        
                        // Validate required fields
                        if (empty($row['question_text']) || empty($row['question_type'])) {
                            $skipped_count++;
                            continue;
                        }
                        
                        // Set default values if not provided
                        $row['category'] = !empty($row['category']) ? $row['category'] : '';
                        $row['points'] = !empty($row['points']) ? (int)$row['points'] : 1;
                        
                        // Insert question
                        $query = "INSERT INTO question_bank (question_text, question_type, category, points) 
                                VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssi", $row['question_text'], $row['question_type'], $row['category'], $row['points']);
                        $stmt->execute();
                        
                        $question_id = $conn->insert_id;
                        
                        // Process answers based on question type
                        if ($row['question_type'] === 'multiple-choice') {
                            // Check if answers are provided
                            $answers = [];
                            $correct_answers = [];
                            
                            // Look for answer columns (answer_1, answer_2, etc.)
                            for ($i = 1; $i <= 10; $i++) { // Support up to 10 answers
                                $answer_key = "answer_$i";
                                $correct_key = "correct_$i";
                                
                                if (isset($header_map[$answer_key]) && isset($data[$header_map[$answer_key]]) && !empty($data[$header_map[$answer_key]])) {
                                    $answers[] = $data[$header_map[$answer_key]];
                                    
                                    // Check if this answer is marked as correct
                                    if (isset($header_map[$correct_key]) && 
                                        isset($data[$header_map[$correct_key]]) && 
                                        $data[$header_map[$correct_key]] == 1) {
                                        $correct_answers[] = count($answers) - 1; // 0-based index
                                    }
                                }
                            }
                            
                            // Insert answers
                            for ($i = 0; $i < count($answers); $i++) {
                                $is_correct = in_array($i, $correct_answers) ? 1 : 0;
                                
                                $query = "INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) 
                                        VALUES (?, ?, ?, ?)";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("isii", $question_id, $answers[$i], $is_correct, $i);
                                $stmt->execute();
                            }
                        } elseif ($row['question_type'] === 'true-false') {
                            $correct_answer = isset($header_map['correct_answer']) && isset($data[$header_map['correct_answer']]) 
                                ? strtolower($data[$header_map['correct_answer']]) : 'true';
                            
                            // Add True answer
                            $is_true_correct = ($correct_answer === 'true') ? 1 : 0;
                            $query = "INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) 
                                    VALUES (?, 'True', ?, 0)";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ii", $question_id, $is_true_correct);
                            $stmt->execute();
                            
                            // Add False answer
                            $is_false_correct = ($correct_answer === 'false') ? 1 : 0;
                            $query = "INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) 
                                    VALUES (?, 'False', ?, 1)";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ii", $question_id, $is_false_correct);
                            $stmt->execute();
                        } elseif ($row['question_type'] === 'programming') {
                            $starter_code = isset($header_map['starter_code']) && isset($data[$header_map['starter_code']]) 
                                ? $data[$header_map['starter_code']] : '';
                            $language = isset($header_map['language']) && isset($data[$header_map['language']]) 
                                ? $data[$header_map['language']] : 'python';
                            
                            // Insert programming details
                            $query = "INSERT INTO question_bank_programming (question_id, starter_code, language) 
                                    VALUES (?, ?, ?)";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("iss", $question_id, $starter_code, $language);
                            $stmt->execute();
                            
                            $programming_id = $conn->insert_id;
                            
                            // Look for test case columns (test_input_1, test_output_1, etc.)
                            for ($i = 1; $i <= 10; $i++) { // Support up to 10 test cases
                                $input_key = "test_input_$i";
                                $output_key = "test_output_$i";
                                $hidden_key = "test_hidden_$i";
                                $desc_key = "test_description_$i";
                                
                                // Only process if we have an output defined
                                if (isset($header_map[$output_key]) && 
                                    isset($data[$header_map[$output_key]]) && 
                                    !empty($data[$header_map[$output_key]])) {
                                    
                                    $input = isset($header_map[$input_key]) && isset($data[$header_map[$input_key]]) 
                                        ? $data[$header_map[$input_key]] : '';
                                    $expected_output = $data[$header_map[$output_key]];
                                    $is_hidden = isset($header_map[$hidden_key]) && isset($data[$header_map[$hidden_key]]) && 
                                        $data[$header_map[$hidden_key]] == 1 ? 1 : 0;
                                    $description = isset($header_map[$desc_key]) && isset($data[$header_map[$desc_key]]) 
                                        ? $data[$header_map[$desc_key]] : null;
                                    
                                    $query = "INSERT INTO question_bank_test_cases (programming_id, input, expected_output, is_hidden, description) 
                                            VALUES (?, ?, ?, ?, ?)";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("issis", $programming_id, $input, $expected_output, $is_hidden, $description);
                                    $stmt->execute();
                                }
                            }
                        }
                        
                        $imported_count++;
                    }
                    
                    $conn->commit();
                    $success = "Successfully imported $imported_count questions" . ($skipped_count > 0 ? " (skipped $skipped_count invalid rows)" : "");
                    
                    // Clean up the temporary file after successful import
                    if (!empty($success)) {
                        @unlink($file);
                        unset($_SESSION['csv_file_path']);
                        unset($_SESSION['uploaded_filename']);
                        
                        // Redirect to question bank page after successful import
                        // This ensures the page refreshes and doesn't stay in the loading state
                        header("Location: question_bank.php?import_success=1");
                        exit;
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
            }
            
            fclose($handle);
        } else {
            $error = "Could not open the CSV file";
        }
    } else if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        debug_log("Using newly uploaded file");
        // If no file in session but a new file was uploaded, process it
        // This is a fallback in case the session data is lost
        
        $file = $_FILES['csv_file']['tmp_name'];
        $original_filename = $_FILES['csv_file']['name'];
        
        // Store the original filename in session for display
        $_SESSION['uploaded_filename'] = $original_filename;
        
        // Check if it's a CSV file
        $file_info = pathinfo($original_filename);
        if (strtolower($file_info['extension']) !== 'csv') {
            $error = "Please upload a CSV file";
        } else {
            // Open the file
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Get the header row
                $header = fgetcsv($handle, 1000, ",");
                
                // Map headers to column indices for easier access
                $header_map = array_flip($header);
                
                // Check if the CSV has the required columns
                $required_columns = ['question_text', 'question_type'];
                $missing_columns = array_diff($required_columns, $header);
                
                if (!empty($missing_columns)) {
                    $error = "CSV file is missing required columns: " . implode(', ', $missing_columns);
                } else {
                        $conn->begin_transaction();
                        
                        try {
                            $imported_count = 0;
                            $skipped_count = 0;
                            
                            // Process each row
                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            // Map data to column names
                                $row = [];
                                foreach ($header as $index => $column) {
                                    $row[$column] = $data[$index] ?? '';
                                }
                                
                                // Validate required fields
                                if (empty($row['question_text']) || empty($row['question_type'])) {
                                    $skipped_count++;
                                    continue;
                                }
                                
                                // Set default values if not provided
                                $row['category'] = !empty($row['category']) ? $row['category'] : '';
                                $row['points'] = !empty($row['points']) ? (int)$row['points'] : 1;
                                
                                // Insert question
                                $query = "INSERT INTO question_bank (question_text, question_type, category, points) 
                                        VALUES (?, ?, ?, ?)";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("sssi", $row['question_text'], $row['question_type'], $row['category'], $row['points']);
                                $stmt->execute();
                                
                                $question_id = $conn->insert_id;
                                
                                // Process answers based on question type
                                if ($row['question_type'] === 'multiple-choice') {
                                    // Check if answers are provided
                                    $answers = [];
                                    $correct_answers = [];
                                    
                                    // Look for answer columns (answer_1, answer_2, etc.)
                                for ($i = 1; $i <= 10; $i++) { // Support up to 10 answers
                                    $answer_key = "answer_$i";
                                    $correct_key = "correct_$i";
                                    
                                    if (isset($header_map[$answer_key]) && isset($data[$header_map[$answer_key]]) && !empty($data[$header_map[$answer_key]])) {
                                        $answers[] = $data[$header_map[$answer_key]];
                                            
                                            // Check if this answer is marked as correct
                                        if (isset($header_map[$correct_key]) && 
                                            isset($data[$header_map[$correct_key]]) && 
                                            $data[$header_map[$correct_key]] == 1) {
                                            $correct_answers[] = count($answers) - 1; // 0-based index
                                            }
                                        }
                                    }
                                    
                                    // Insert answers
                                    for ($i = 0; $i < count($answers); $i++) {
                                        $is_correct = in_array($i, $correct_answers) ? 1 : 0;
                                        
                                        $query = "INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) 
                                                VALUES (?, ?, ?, ?)";
                                        $stmt = $conn->prepare($query);
                                        $stmt->bind_param("isii", $question_id, $answers[$i], $is_correct, $i);
                                        $stmt->execute();
                                    }
                                } elseif ($row['question_type'] === 'true-false') {
                                $correct_answer = isset($header_map['correct_answer']) && isset($data[$header_map['correct_answer']]) 
                                    ? strtolower($data[$header_map['correct_answer']]) : 'true';
                                    
                                    // Add True answer
                                    $is_true_correct = ($correct_answer === 'true') ? 1 : 0;
                                    $query = "INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) 
                                            VALUES (?, 'True', ?, 0)";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("ii", $question_id, $is_true_correct);
                                    $stmt->execute();
                                    
                                    // Add False answer
                                    $is_false_correct = ($correct_answer === 'false') ? 1 : 0;
                                    $query = "INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) 
                                            VALUES (?, 'False', ?, 1)";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("ii", $question_id, $is_false_correct);
                                    $stmt->execute();
                                } elseif ($row['question_type'] === 'programming') {
                                $starter_code = isset($header_map['starter_code']) && isset($data[$header_map['starter_code']]) 
                                    ? $data[$header_map['starter_code']] : '';
                                $language = isset($header_map['language']) && isset($data[$header_map['language']]) 
                                    ? $data[$header_map['language']] : 'python';
                                    
                                    // Insert programming details
                                    $query = "INSERT INTO question_bank_programming (question_id, starter_code, language) 
                                            VALUES (?, ?, ?)";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("iss", $question_id, $starter_code, $language);
                                    $stmt->execute();
                                    
                                    $programming_id = $conn->insert_id;
                                    
                                    // Look for test case columns (test_input_1, test_output_1, etc.)
                                for ($i = 1; $i <= 10; $i++) { // Support up to 10 test cases
                                    $input_key = "test_input_$i";
                                    $output_key = "test_output_$i";
                                    $hidden_key = "test_hidden_$i";
                                    $desc_key = "test_description_$i";
                                    
                                    // Only process if we have an output defined
                                    if (isset($header_map[$output_key]) && 
                                        isset($data[$header_map[$output_key]]) && 
                                        !empty($data[$header_map[$output_key]])) {
                                        
                                        $input = isset($header_map[$input_key]) && isset($data[$header_map[$input_key]]) 
                                            ? $data[$header_map[$input_key]] : '';
                                        $expected_output = $data[$header_map[$output_key]];
                                        $is_hidden = isset($header_map[$hidden_key]) && isset($data[$header_map[$hidden_key]]) && 
                                            $data[$header_map[$hidden_key]] == 1 ? 1 : 0;
                                        $description = isset($header_map[$desc_key]) && isset($data[$header_map[$desc_key]]) 
                                            ? $data[$header_map[$desc_key]] : null;
                                        
                                        $query = "INSERT INTO question_bank_test_cases (programming_id, input, expected_output, is_hidden, description) 
                                                VALUES (?, ?, ?, ?, ?)";
                                        $stmt = $conn->prepare($query);
                                        $stmt->bind_param("issis", $programming_id, $input, $expected_output, $is_hidden, $description);
                                        $stmt->execute();
                                    }
                                    }
                                }
                                
                                $imported_count++;
                            }
                            
                            $conn->commit();
                            $success = "Successfully imported $imported_count questions" . ($skipped_count > 0 ? " (skipped $skipped_count invalid rows)" : "");
                            
                            // Add similar redirect after successful import
                            if (!empty($success)) {
                                header("Location: question_bank.php?import_success=1");
                                exit;
                            }
                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = "Error: " . $e->getMessage();
                    }
                }
                
                fclose($handle);
            } else {
                $error = "Could not open the CSV file";
            }
        }
    } else {
        debug_log("No valid file found for import");
        $error = "Please select a CSV file to upload";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Questions - Question Bank</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* General Styles */
        body, html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .container {
            display: flex;
        }

        .main {
            flex: 1;
            padding: 20px;
        }

        /* Main container styles */
        .import-container {
            max-width: auto;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .import-section {
            margin-bottom: 30px;
        }
        
        .import-section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
            font-weight: 500;
        }
        
        /* File upload styles */
        .file-upload-container {
            margin-bottom: 20px;
        }

        .file-upload-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #333;
        }

        .file-input-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .file-name {
            margin-left: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .file-upload-help {
            margin-top: 10px;
            color: #666;
            font-size: 14px;
        }
        
        /* Preview container styles */
        .preview-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            max-height: 300px; /* Limit the height */
            overflow-y: auto; /* Allow vertical scrolling */
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px; /* Smaller font size */
        }
        
        .preview-table th,
        .preview-table td {
            padding: 6px 10px; /* Reduced padding */
            border: 1px solid #e0e0e0;
            white-space: nowrap;
            max-width: 150px; /* Reduced max width */
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .preview-table th {
            background-color: #f8f9fa;
            font-weight: 500;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        /* Button styles */
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .preview-btn, .import-btn {
            padding: 10px 16px;
            border-radius: 4px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s, transform 0.1s;
        }
        
        .preview-btn {
            background-color: #f0f0f0;
            color: #333;
        }

        .preview-btn:hover {
            background-color: #e0e0e0;
        }
        
        .preview-btn:active {
            transform: translateY(1px);
        }
        
        .import-btn {
            background-color: #4caf50;
            color: white;
        }

        .import-btn:hover:not(:disabled) {
            background-color: #43a047;
        }
        
        .import-btn:active:not(:disabled) {
            transform: translateY(1px);
        }
        
        .import-btn:disabled {
            background-color: #a5d6a7;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        /* Loading state for preview button */
        .preview-btn.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }
        
        .preview-btn.loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-top: -8px;
            margin-left: -8px;
            border-radius: 50%;
            border: 2px solid #333;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Template section styles */
        .template-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .template-section h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .template-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .template-columns {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .template-column {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            color: #495057;
        }
        
        .download-template-btn {
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Add these styles for the back button */
        .header-navigation {
            margin-bottom: 15px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #75343A;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: color 0.2s;
            padding: 6px 0;
        }
        
        .back-link:hover {
            color: #75343A;
        }
        
        .back-link .material-symbols-rounded {
            font-size: 18px;
            margin-right: 6px;
        }
        
        /* Update page header styles */
        .page-header {
            margin-bottom: 25px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 500;
            color: #333;
        }
        
        /* Question Preview Styles */
        .preview-section {
            margin-top: 30px;
            margin-bottom: 30px;
        }
        
        .preview-info {
            margin-bottom: 20px;
            color: #666;
            font-size: 15px;
        }
        
        .questions-preview {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .question-preview-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .question-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .question-number {
            font-weight: 500;
            color: #333;
        }
        
        .question-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .question-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .question-type-badge.multiple-choice {
            background-color: #e3f2fd;
            color: #0070c0;
        }
        
        .question-type-badge.true-false {
            background-color: #e8f5e9;
            color: #28a745;
        }
        
        .question-type-badge.programming {
            background-color: #fff3e0;
            color: #ff9800;
        }
        
        .category-badge, .points-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            color: #666;
        }
        
        .category-badge .material-symbols-rounded,
        .points-badge .material-symbols-rounded {
            font-size: 16px;
        }
        
        .question-preview-body {
            padding: 16px;
        }
        
        .question-text {
            margin-bottom: 16px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .answer-preview {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .answer-choice {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #f8f9fa;
            font-size: 14px;
        }
        
        .answer-choice.correct {
            background-color: #e6f7e6;
            border-left: 3px solid #28a745;
        }
        
        .answer-choice.incorrect {
            background-color: #f8f9fa;
            border-left: 3px solid #e0e0e0;
        }
        
        .choice-icon {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .answer-choice.correct .choice-icon {
            color: #28a745;
        }
        
        .programming-preview {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .programming-language {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
        }
        
        .code-preview {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .code-preview span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
        }
        
        .code-block {
            background-color: #f5f5f5;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.4;
            overflow-x: auto;
            margin: 0;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
        }
        
        .preview-actions {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .preview-note {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .preview-note .material-symbols-rounded {
            color: #8e68cc;
            font-size: 20px;
        }
        
        /* Raw data section (if needed) */
        .raw-data-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .raw-data-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }

        
    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="import-container" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); padding: 2rem; margin: 20px;">
            <div class="page-header" style="margin-bottom: 2rem;">
                <div class="header-navigation" style="margin-bottom: 1rem;">
                    <a href="question_bank.php" class="back-link" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #666; text-decoration: none; font-size: 0.9rem; transition: color 0.2s;">
                        <span class="material-symbols-rounded">arrow_back</span>
                        <span>Back to Question Bank</span>
                    </a>
                </div>
                <h1 style="font-size: 36px; color: #75343A; font-weight: 700; letter-spacing: 0.5px; text-shadow: 0 1px 1px rgba(0,0,0,0.1); margin: 0;">Import Questions</h1>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="import-section" style="background: #f8f9fa; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; max-width: 2000px; margin-left: auto; margin-right: auto;">
                <h2 style="color: #333; margin-bottom: 1.5rem; font-size: 1.2rem; text-align: center;">Select CSV File</h2>
                <form id="csv-upload-form" method="POST" enctype="multipart/form-data" action="questionBank_import.php">
                    <div class="file-upload-container" style="background: white; border: 2px dashed #ddd; border-radius: 8px; padding: 2rem; text-align: center; transition: border-color 0.3s; max-width: 700px; margin: 0 auto;">
                        <label for="csv-file" class="file-upload-label" style="display: block; margin-bottom: 1rem; color: #333; font-weight: 500;">Choose a CSV file:</label>
                        <div class="file-input-wrapper" style="position: relative; margin-bottom: 1rem; display: flex; justify-content: center;">
                            <input type="file" id="csv-file" name="csv_file" accept=".csv" <?php echo !isset($_SESSION['csv_file_path']) ? 'required' : ''; ?> 
                                   style="position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer;">
                            <div class="file-name" id="file-name" style="display: inline-block; padding: 0.5rem 1rem; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; color: #666; min-width: 200px; text-align: center;">
                                <?php 
                                if (isset($_SESSION['uploaded_filename'])) {
                                    echo htmlspecialchars($_SESSION['uploaded_filename']);
                                } else {
                                    echo 'No file chosen';
                                }
                                ?>
                            </div>
                        </div>
                        <p class="file-upload-help" style="color: #666; font-size: 0.9rem; margin-top: 1rem;">Please upload a CSV file with the required columns. See the template section below for details.</p>
                    </div>

                    <div class="button-container" style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: center;">
                        <button type="submit" name="preview" id="preview-btn" class="preview-btn" 
                                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #f8f9fa; color: #333; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.3s;">
                            <span class="material-symbols-rounded">visibility</span>
                            Preview
                        </button>
                        <button type="submit" name="import" id="import-btn" class="import-btn" 
                                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #75343A; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.3s;"
                                <?php echo (!isset($_FILES['csv_file']) && !isset($_SESSION['csv_file_path'])) ? 'disabled' : ''; ?>>
                            <span class="material-symbols-rounded">upload</span>
                            Import Questions
                        </button>
                    </div>
                </form>
            </div>

            <div class="template-section" style="background: #f8f9fa; border-radius: 8px; padding: 2rem; margin-top: 2rem;">
                <h2 style="color: #333; margin-bottom: 1.5rem; font-size: 1.2rem;">CSV Template</h2>
                <div class="template-content" style="background: white; border-radius: 8px; padding: 1.5rem;">
                    <table class="template-table" style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem;">
                        <thead>
                            <tr>
                                <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left; background: #f8f9fa; font-weight: 500;">Column Name</th>
                                <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left; background: #f8f9fa; font-weight: 500;">Description</th>
                                <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left; background: #f8f9fa; font-weight: 500;">Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">question_text</td>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">The question text</td>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">Yes</td>
                            </tr>
                            <tr style="background: #f8f9fa;">
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">question_type</td>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">Type of question (multiple-choice, true-false, programming)</td>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">Yes</td>
                            </tr>
                            <tr>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">category</td>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">Question category</td>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">No</td>
                            </tr>
                            <tr style="background: #f8f9fa;">
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">points</td>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">Points for the question</td>
                                <td style="padding: 0.75rem; border: 1px solid #ddd;">No</td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="download-template-btn" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #75343A; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: background 0.3s;">
                        <span class="material-symbols-rounded">download</span>
                        Download Template
                    </button>
                </div>
            </div>

            <?php if (!empty($preview_questions)): ?>
            <div class="preview-section" style="margin-top: 2rem;">
                <h2 style="color: #333; margin-bottom: 1.5rem; font-size: 1.2rem;">Preview Questions</h2>
                <div class="preview-questions" style="display: grid; gap: 1rem;">
                    <?php foreach ($preview_questions as $question): ?>
                        <div class="preview-question" style="background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                            <div class="question-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div class="question-type" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem; background: #f8f9fa; border-radius: 4px; font-size: 0.9rem; color: #666;">
                                    <span class="material-symbols-rounded">
                                        <?php 
                                        switch($question['question_type']) {
                                            case 'multiple-choice':
                                                echo 'radio_button_checked';
                                                break;
                                            case 'true-false':
                                                echo 'check_circle';
                                                break;
                                            case 'programming':
                                                echo 'code';
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <?php echo ucfirst($question['question_type']); ?>
                                </div>
                            </div>
                            <div class="question-text" style="margin-bottom: 1rem; color: #333; line-height: 1.5;">
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </div>
                            <?php if ($question['question_type'] === 'multiple-choice'): ?>
                                <div class="answers-preview" style="display: grid; gap: 0.5rem;">
                                    <?php foreach ($question['answers'] as $answer): ?>
                                        <div class="answer-item <?php echo $answer['is_correct'] ? 'correct' : ''; ?>" 
                                             style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: <?php echo $answer['is_correct'] ? '#d4edda' : '#f8f9fa'; ?>; border-radius: 4px; color: <?php echo $answer['is_correct'] ? '#155724' : '#666'; ?>;">
                                            <span class="material-symbols-rounded">
                                                <?php echo $answer['is_correct'] ? 'check_circle' : 'radio_button_unchecked'; ?>
                                            </span>
                                            <?php echo htmlspecialchars($answer['answer_text']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="preview-actions" style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <p class="preview-note" style="display: flex; align-items: center; gap: 0.5rem; color: #666; font-size: 0.9rem;">
                        <span class="material-symbols-rounded">info</span>
                        These are the first <?php echo count($preview_questions); ?> questions from your CSV file. 
                        Click "Import Questions" to add all questions to your question bank.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="assets/js/side.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('csv-file');
    const fileName = document.getElementById('file-name');
    const previewBtn = document.getElementById('preview-btn');
    const importBtn = document.getElementById('import-btn');
    const uploadForm = document.getElementById('csv-upload-form');
    
    // Update file name display when a file is selected and enable import button
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            // Enable import button as soon as a file is selected
            importBtn.disabled = false;
        } else {
            fileName.textContent = 'No file chosen';
            importBtn.disabled = true;
        }
    });
    
    // Show loading state when preview button is clicked
    previewBtn.addEventListener('click', function(e) {
        if (fileInput.files && fileInput.files[0] || <?php echo isset($_SESSION['csv_file_path']) ? 'true' : 'false'; ?>) {
            this.classList.add('loading');
            // Form submission will happen naturally
        } else {
            e.preventDefault();
            alert('Please select a CSV file first.');
        }
    });
    
    // Enable import button if a file is selected or if we have a file in session
    if (<?php echo isset($_SESSION['csv_file_path']) ? 'true' : 'false'; ?>) {
        importBtn.disabled = false;
    }
    
    // Handle form submission for import
    importBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default button behavior
        
        if (!this.disabled) {
            console.log("Import button clicked");
            this.innerHTML = '<span class="material-symbols-rounded">hourglass_empty</span> Importing...';
            this.disabled = true;
            
            // Create a hidden input to indicate this is an import action
            const importInput = document.createElement('input');
            importInput.type = 'hidden';
            importInput.name = 'import';
            importInput.value = '1';
            uploadForm.appendChild(importInput);
            
            console.log("Submitting form for import");
            uploadForm.submit();
        }
    });
    
    // Add template download functionality
    const downloadTemplateBtn = document.querySelector('.download-template-btn');
    if (downloadTemplateBtn) {
        downloadTemplateBtn.addEventListener('click', function() {
            // Create CSV content
            const csvContent = [
                'question_text,question_type,category,points,answer_1,answer_2,answer_3,answer_4,correct_1,correct_2,correct_3,correct_4,correct_answer,language,starter_code,test_input_1,test_output_1,test_hidden_1,test_description_1',
                '"What is 2+2?",multiple-choice,Math,1,"4","3","5","6",1,0,0,0,,,,,,',
                '"The sky is blue.",true-false,Science,1,,,,,,,,"true",,,,,,',
                '"Write a function that returns the sum of two numbers.",programming,Programming,2,,,,,,,,,"python","def add_numbers(a, b):\n    # Your code here\n    pass","5,7","12",0,"Basic addition test"'
            ].join('\n');
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', 'question_bank_template.csv');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    }
});
</script>
</body>
</html>
