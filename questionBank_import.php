<?php
// Include database connection
include('config/config.php');

// Initialize variables
$error = null;
$success = null;

// Set reasonable memory limit
ini_set('memory_limit', '256M');

// Set maximum file size (10MB)
$max_file_size = 10 * 1024 * 1024; // 10MB in bytes

// Process the uploaded CSV file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['csv_file']['name']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        try {
            if ($_FILES['csv_file']['size'] > $max_file_size) {
                throw new Exception("File size exceeds maximum limit of 10MB.");
            }

            $original_filename = $_FILES['csv_file']['name'];
            $tmp_file = $_FILES['csv_file']['tmp_name'];
            
            // Validate file extension
            $file_info = pathinfo($original_filename);
            if (strtolower($file_info['extension']) !== 'csv') {
                throw new Exception("Please upload a CSV file.");
            }

            // Read file content
            $content = file_get_contents($tmp_file);
            if ($content === false) {
                throw new Exception("Could not read the CSV file.");
            }

            // Create a temporary stream
            $stream = fopen('php://temp', 'r+');
            if ($stream === false) {
                throw new Exception("Could not create temporary stream.");
            }

            // Write content to stream
            fwrite($stream, $content);
            rewind($stream);

            // Get and validate headers
            $header = fgetcsv($stream, 1000, ",");
            if ($header === false || count($header) < 2) {
                throw new Exception("Invalid CSV format");
            }

            // Map headers to column indices
            $header_map = array_flip($header);

            // Validate required columns
            $required_columns = ['question_text', 'question_type'];
            $missing_columns = array_diff($required_columns, $header);
            if (!empty($missing_columns)) {
                throw new Exception("CSV file is missing required columns: " . implode(', ', $missing_columns));
            }

            // Begin database transaction
            $conn->begin_transaction();

            $imported_count = 0;
            $skipped_count = 0;
            $chunk_size = 100;
            $processed_rows = 0;

            // Process each row
            while (($data = fgetcsv($stream, 1000, ",")) !== FALSE) {
                // Validate row data
                if (count($data) !== count($header)) {
                    $skipped_count++;
                    continue;
                }

                // Map data to column names
                $row = array_combine($header, $data);

                // Validate required fields
                if (empty($row['question_text']) || empty($row['question_type'])) {
                    $skipped_count++;
                    continue;
                }

                // Set default values
                $row['category'] = $row['category'] ?? '';
                $row['points'] = !empty($row['points']) ? (int)$row['points'] : 1;

                // Store values in variables for binding
                $question_text = $row['question_text'];
                $question_type = $row['question_type'];
                $category = $row['category'];
                $points = $row['points'];

                // Insert question
                $stmt = $conn->prepare("INSERT INTO question_bank (question_text, question_type, category, points) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $question_text, $question_type, $category, $points);
                $stmt->execute();

                $question_id = $conn->insert_id;

                // Process answers based on question type
                switch ($row['question_type']) {
                    case 'multiple-choice':
                        // Handle multiple choice answers
                        for ($i = 1; $i <= 4; $i++) {
                            $answer_key = "answer_$i";
                            $correct_key = "correct_$i";
                            
                            if (!empty($row[$answer_key])) {
                                $answer_text = $row[$answer_key];
                                $is_correct = !empty($row[$correct_key]) && $row[$correct_key] == 1 ? 1 : 0;
                                $position = $i - 1;
                                
                                $stmt = $conn->prepare("INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) VALUES (?, ?, ?, ?)");
                                $stmt->bind_param("isii", $question_id, $answer_text, $is_correct, $position);
                                $stmt->execute();
                            }
                        }
                        break;

                    case 'true-false':
                        // Handle true-false answers
                        $correct_answer = strtolower($row['correct_answer'] ?? 'true');
                        
                        // Insert True option
                        $answer_text = 'True';
                        $is_true_correct = ($correct_answer === 'true') ? 1 : 0;
                        $position = 0;
                        $stmt = $conn->prepare("INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isii", $question_id, $answer_text, $is_true_correct, $position);
                        $stmt->execute();
                        
                        // Insert False option
                        $answer_text = 'False';
                        $is_false_correct = ($correct_answer === 'false') ? 1 : 0;
                        $position = 1;
                        $stmt = $conn->prepare("INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isii", $question_id, $answer_text, $is_false_correct, $position);
                        $stmt->execute();
                        break;

                    case 'programming':
                        // Handle programming questions
                        $starter_code = $row['starter_code'] ?? '';
                        $language = $row['language'] ?? 'python';
                        
                        $stmt = $conn->prepare("INSERT INTO question_bank_programming (question_id, starter_code, language) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $question_id, $starter_code, $language);
                        $stmt->execute();
                        break;
                }

                $imported_count++;
                $processed_rows++;

                // Commit every chunk_size rows
                if ($processed_rows % $chunk_size === 0) {
                    $conn->commit();
                    $conn->begin_transaction();
                }
            }

            // Commit remaining rows
            $conn->commit();

            // Close the stream
            fclose($stream);

            $success = "Successfully imported $imported_count questions" . ($skipped_count > 0 ? " (skipped $skipped_count invalid rows)" : "");
            header("Location: question_bank.php?import_success=1");
            exit;

        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollback();
            }
            if (isset($stream) && is_resource($stream)) {
                fclose($stream);
            }
            $error = "Error: " . $e->getMessage();
        }
    } else {
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

        /* Add these styles to your existing CSS */
        .preview-pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            align-items: center;
        }

        .pagination-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            background: #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination-btn:hover {
            background: #e0e0e0;
        }

        .pagination-btn.active {
            background: #75343A;
            color: white;
        }

        .pagination-btn .material-symbols-rounded {
            font-size: 20px;
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
                <h2 style="color: #333; margin-bottom: 1.5rem; font-size: 1.2rem;">Select CSV File</h2>
                <form id="csv-upload-form" method="POST" enctype="multipart/form-data" action="questionBank_import.php">
                    <div class="file-upload-container" style="background: white; border: 2px dashed #ddd; border-radius: 8px; padding: 2rem; text-align: center; transition: border-color 0.3s; max-width: 700px; margin: 0 auto;">
                        <label for="csv-file" class="file-upload-label" style="display: block; margin-bottom: 1rem; color: #333; font-weight: 500;">Choose a CSV file:</label>
                        <div class="file-input-wrapper" style="position: relative; margin-bottom: 1rem; display: flex; justify-content: center;">
                            <input type="file" id="csv-file" name="csv_file" accept=".csv" required style="position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer;">
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
                        <button type="button" id="preview-btn" class="preview-btn" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #f0f0f0; color: #333; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.3s;" disabled>
                            <span class="material-symbols-rounded">preview</span>
                            Preview
                        </button>
                        <button type="submit" name="import" id="import-btn" class="import-btn" 
                                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #75343A; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.3s;"
                                disabled>
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
        </div>
    </div>
</div>

<script src="assets/js/side.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('csv-file');
    const fileName = document.getElementById('file-name');
    const importBtn = document.getElementById('import-btn');
    const previewBtn = document.getElementById('preview-btn');
    const uploadForm = document.getElementById('csv-upload-form');
    
    let allQuestions = []; // Store all questions
    const questionsPerPage = 5; // Number of questions per page
    let currentPage = 1;

    // Update file name display and enable/disable buttons when a file is selected
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            fileName.textContent = file.name;
            importBtn.disabled = false;
            previewBtn.disabled = false;

            // Clear any existing preview
            const existingPreview = document.querySelector('.preview-section');
            if (existingPreview) {
                existingPreview.remove();
            }

            // Clear any existing error messages
            const existingError = document.querySelector('.alert-danger');
            if (existingError) {
                existingError.remove();
            }
        } else {
            fileName.textContent = 'No file chosen';
            importBtn.disabled = true;
            previewBtn.disabled = true;
        }
    });

    // Handle preview button click
    previewBtn.addEventListener('click', function() {
        const file = fileInput.files[0];
        if (!file) return;

        const reader = new FileReader();
        previewBtn.classList.add('loading');

        reader.onload = function(e) {
            const content = e.target.result;
            const lines = content.split(/\r\n|\n/).filter(line => line.trim()); // Remove empty lines
            const headers = lines[0].split(',').map(h => h.trim());
            
            // Validate required columns
            const requiredColumns = ['question_text', 'question_type'];
            const missingColumns = requiredColumns.filter(col => !headers.includes(col));
            
            if (missingColumns.length > 0) {
                alert('Missing required columns: ' + missingColumns.join(', '));
                previewBtn.classList.remove('loading');
                return;
            }

            // Store all questions
            allQuestions = lines.slice(1)
                .map(line => {
                    const values = line.split(',').map(v => v.trim());
                    const rowData = {};
                    headers.forEach((header, i) => {
                        rowData[header] = values[i] || '';
                    });
                    return rowData;
                })
                .filter(question => question.question_text && question.question_type); // Only keep questions that have required fields

            if (allQuestions.length === 0) {
                alert('No valid questions found in the CSV file. Please check the file format.');
                previewBtn.classList.remove('loading');
                return;
            }

            // Show first page
            showPreviewPage(1);
            previewBtn.classList.remove('loading');
        };

        reader.onerror = function() {
            alert('Error reading file');
            previewBtn.classList.remove('loading');
        };

        reader.readAsText(file);
    });

    function showPreviewPage(page) {
        currentPage = page;
        const startIdx = (page - 1) * questionsPerPage;
        const endIdx = startIdx + questionsPerPage;
        const questionsToShow = allQuestions.slice(startIdx, endIdx);
        const totalPages = Math.ceil(allQuestions.length / questionsPerPage);

        let previewHTML = '<div class="preview-section">';
        previewHTML += '<h2>Preview Questions</h2>';
        previewHTML += '<div class="questions-preview">';

        questionsToShow.forEach((rowData, index) => {
            // Skip if question is empty
            if (!rowData.question_text || !rowData.question_type) return;

            const questionNumber = startIdx + index + 1;
            previewHTML += `
                <div class="question-preview-card">
                    <div class="question-preview-header">
                        <span class="question-number">Question ${questionNumber}</span>
                        <div class="question-meta">
                            <span class="question-type-badge ${rowData.question_type}">
                                ${rowData.question_type.charAt(0).toUpperCase() + rowData.question_type.slice(1)}
                            </span>
                            ${rowData.category ? `
                                <span class="category-badge">
                                    <span class="material-symbols-rounded">folder</span>
                                    ${rowData.category}
                                </span>
                            ` : ''}
                            <span class="points-badge">
                                <span class="material-symbols-rounded">stars</span>
                                ${rowData.points || 1} point${(rowData.points || 1) !== 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>
                    <div class="question-preview-body">
                        <div class="question-text">${rowData.question_text}</div>
                        ${getAnswersPreviewHTML(rowData)}
                    </div>
                </div>
            `;
        });

        previewHTML += '</div>';

        // Only show pagination if there are multiple pages of valid questions
        if (totalPages > 1) {
            previewHTML += '<div class="preview-pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">';
            
            // Previous button
            if (currentPage > 1) {
                previewHTML += `
                    <button onclick="window.previewPage(${currentPage - 1})" class="pagination-btn" 
                            style="padding: 8px 16px; border: none; border-radius: 4px; background: #f0f0f0; cursor: pointer;">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>`;
            }

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                previewHTML += `
                    <button onclick="window.previewPage(${i})" 
                            class="pagination-btn ${i === currentPage ? 'active' : ''}"
                            style="padding: 8px 16px; border: none; border-radius: 4px; 
                                   background: ${i === currentPage ? '#75343A' : '#f0f0f0'}; 
                                   color: ${i === currentPage ? 'white' : 'black'}; 
                                   cursor: pointer;">
                        ${i}
                    </button>`;
            }

            // Next button
            if (currentPage < totalPages) {
                previewHTML += `
                    <button onclick="window.previewPage(${currentPage + 1})" class="pagination-btn"
                            style="padding: 8px 16px; border: none; border-radius: 4px; background: #f0f0f0; cursor: pointer;">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>`;
            }
            
            previewHTML += '</div>';
        }

        // Update the preview note to be more accurate
        previewHTML += '<div class="preview-actions">';
        previewHTML += '<p class="preview-note">';
        previewHTML += '<span class="material-symbols-rounded">info</span>';
        if (allQuestions.length === 1) {
            previewHTML += 'Showing 1 question. ';
        } else {
            previewHTML += `Showing ${questionsToShow.length} of ${allQuestions.length} questions. `;
        }
        previewHTML += 'Click "Import Questions" to process all questions.';
        previewHTML += '</p>';
        previewHTML += '</div>';
        previewHTML += '</div>';

        // Insert preview before the template section
        const existingPreview = document.querySelector('.preview-section');
        if (existingPreview) {
            existingPreview.remove();
        }
        const templateSection = document.querySelector('.template-section');
        templateSection.insertAdjacentHTML('beforebegin', previewHTML);
    }

    // Make the previewPage function available globally
    window.previewPage = showPreviewPage;

    function getAnswersPreviewHTML(rowData) {
        if (rowData.question_type === 'multiple-choice') {
            let answersHTML = '<div class="answer-preview">';
            for (let i = 1; i <= 4; i++) {
                const answerKey = `answer_${i}`;
                const correctKey = `correct_${i}`;
                if (rowData[answerKey]) {
                    const isCorrect = rowData[correctKey] === '1';
                    answersHTML += `
                        <div class="answer-choice ${isCorrect ? 'correct' : 'incorrect'}">
                            <span class="material-symbols-rounded choice-icon">
                                ${isCorrect ? 'check_circle' : 'radio_button_unchecked'}
                            </span>
                            ${rowData[answerKey]}
                        </div>
                    `;
                }
            }
            answersHTML += '</div>';
            return answersHTML;
        } else if (rowData.question_type === 'true-false') {
            const correctAnswer = rowData.correct_answer?.toLowerCase() || 'true';
            return `
                <div class="answer-preview">
                    <div class="answer-choice ${correctAnswer === 'true' ? 'correct' : 'incorrect'}">
                        <span class="material-symbols-rounded choice-icon">
                            ${correctAnswer === 'true' ? 'check_circle' : 'radio_button_unchecked'}
                        </span>
                        True
                    </div>
                    <div class="answer-choice ${correctAnswer === 'false' ? 'correct' : 'incorrect'}">
                        <span class="material-symbols-rounded choice-icon">
                            ${correctAnswer === 'false' ? 'check_circle' : 'radio_button_unchecked'}
                        </span>
                        False
                    </div>
                </div>
            `;
        } else if (rowData.question_type === 'programming') {
            return `
                <div class="programming-preview">
                    <div class="programming-language">
                        <span class="material-symbols-rounded">code</span>
                        Language: ${rowData.language || 'python'}
                    </div>
                    ${rowData.starter_code ? `
                        <div class="code-preview">
                            <span>
                                <span class="material-symbols-rounded">terminal</span>
                                Starter Code:
                            </span>
                            <pre class="code-block">${rowData.starter_code}</pre>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        return '';
    }

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
