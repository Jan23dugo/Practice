<?php
session_start(); 
// Include database connection
include('config/config.php');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Not logged in as admin, redirect to admin login page
    header("Location: admin_login.php");
    exit();
}

// Handle form submissions for adding/editing questions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_question' || $_POST['action'] === 'edit_question') {
            $question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
            $question_text = trim($_POST['question_text']);
            $question_type = $_POST['question_type'];
            $category = trim($_POST['category']);
            $points = (int)$_POST['points'];
            
            // Validate inputs
            if (empty($question_text)) {
                $error = "Question text cannot be empty";
            } elseif (empty($question_type)) {
                $error = "Please select a question type";
            } elseif ($points < 1) {
                $error = "Points must be at least 1";
            } else {
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    if ($_POST['action'] === 'add_question') {
                        // Insert new question
                        $query = "INSERT INTO question_bank (question_text, question_type, category, points) 
                                VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssi", $question_text, $question_type, $category, $points);
                        $stmt->execute();
                        
                        $question_id = $conn->insert_id;
                    } else {
                        // Update existing question
                        $query = "UPDATE question_bank SET question_text = ?, question_type = ?, category = ?, points = ? 
                                WHERE question_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssii", $question_text, $question_type, $category, $points, $question_id);
                        $stmt->execute();
                        
                        // Delete existing answers
                        $query = "DELETE FROM question_bank_answers WHERE question_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $question_id);
                        $stmt->execute();
                    }
                    
                    // Handle answers based on question type
                    if ($question_type === 'multiple-choice') {
                        $answers = $_POST['answers'];
                        $correct_answers = isset($_POST['correct_answers']) ? $_POST['correct_answers'] : [];
                        
                        for ($i = 0; $i < count($answers); $i++) {
                            if (!empty(trim($answers[$i]))) {
                                $is_correct = in_array($i, $correct_answers) ? 1 : 0;
                                
                                $query = "INSERT INTO question_bank_answers (question_id, answer_text, is_correct, position) 
                                        VALUES (?, ?, ?, ?)";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("isii", $question_id, $answers[$i], $is_correct, $i);
                                $stmt->execute();
                            }
                        }
                    } elseif ($question_type === 'true-false') {
                        $correct_answer = $_POST['correct_tf_answer'];
                        
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
                    } elseif ($question_type === 'programming') {
                        // Handle programming question
                        $starter_code = isset($_POST['starter_code']) ? trim($_POST['starter_code']) : '';
                        $language = $_POST['language'];
                        
                        // If editing, delete existing programming details and test cases
                        if ($_POST['action'] === 'edit_question') {
                            // Get the programming_id
                            $query = "SELECT programming_id FROM question_bank_programming WHERE question_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $question_id);
                            $stmt->execute();
                            $programming_result = $stmt->get_result();
                            
                            if ($programming_result->num_rows > 0) {
                                $programming_row = $programming_result->fetch_assoc();
                                $programming_id = $programming_row['programming_id'];
                                
                                // Delete existing test cases
                                $query = "DELETE FROM question_bank_test_cases WHERE programming_id = ?";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("i", $programming_id);
                                $stmt->execute();
                                
                                // Delete existing programming details
                                $query = "DELETE FROM question_bank_programming WHERE question_id = ?";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("i", $question_id);
                                $stmt->execute();
                            }
                        }
                        
                        // Insert programming details
                        $query = "INSERT INTO question_bank_programming (question_id, starter_code, language) 
                                VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("iss", $question_id, $starter_code, $language);
                        $stmt->execute();
                        
                        $programming_id = $conn->insert_id;
                        
                        // Handle test cases
                        if (isset($_POST['test_cases']) && is_array($_POST['test_cases'])) {
                            foreach ($_POST['test_cases'] as $test_case) {
                                if (!empty($test_case['expected_output'])) {
                                    $input = $test_case['input'] ?? '';
                                    $expected_output = $test_case['expected_output'];
                                    $is_hidden = isset($test_case['is_hidden']) ? 1 : 0;
                                    $description = isset($test_case['description']) ? trim($test_case['description']) : null;
                                    
                                    $query = "INSERT INTO question_bank_test_cases (programming_id, input, expected_output, is_hidden, description) 
                                            VALUES (?, ?, ?, ?, ?)";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("issis", $programming_id, $input, $expected_output, $is_hidden, $description);
                                    $stmt->execute();
                                }
                            }
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $success = "Question successfully " . ($_POST['action'] === 'add_question' ? "added to" : "updated in") . " the question bank";
                    
                    // After successful submission, redirect to prevent form resubmission
                    header("Location: question_bank.php?success=" . urlencode($success));
                    exit();
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'delete_question') {
            $question_id = (int)$_POST['question_id'];
            
            // Delete question (cascade will handle related records)
            $conn->begin_transaction();
            
            try {
                $query = "DELETE FROM question_bank WHERE question_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $question_id);
                $stmt->execute();
                
                $conn->commit();
                $success = "Question successfully deleted from the question bank";
                
                // After successful deletion, redirect to prevent form resubmission
                header("Location: question_bank.php?success=" . urlencode($success));
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get success message from URL parameter if it exists
$success = isset($_GET['success']) ? $_GET['success'] : null;

// Get categories for filter
$categories = [];
$query = "SELECT DISTINCT category FROM question_bank WHERE category IS NOT NULL AND category != '' ORDER BY category";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Get filter values
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build query for questions
$query = "SELECT q.*, COUNT(a.answer_id) as answer_count 
          FROM question_bank q 
          LEFT JOIN question_bank_answers a ON q.question_id = a.question_id";

$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($filter_type)) {
    $where_clauses[] = "q.question_type = ?";
    $params[] = $filter_type;
    $param_types .= "s";
}

if (!empty($filter_category)) {
    $where_clauses[] = "q.category = ?";
    $params[] = $filter_category;
    $param_types .= "s";
}

if (!empty($search_query)) {
    $where_clauses[] = "(q.question_text LIKE ? OR q.category LIKE ?)";
    $search_param = "%" . $search_query . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " GROUP BY q.question_id ORDER BY q.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$questions_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Bank</title>
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

        /* Question Bank Styles */
        .question-bank-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .import-question-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 16px;
            font-size: 14px;
            cursor: pointer;
        }

        .add-question-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 16px;
            font-size: 14px;
            cursor: pointer;
        }

        .filters-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .filter-input, .filter-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .filter-btn {
            padding: 10px 16px;
            border-radius: 4px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            height: 40px;
        }

        .filter-btn-primary {
            background-color: #75343A;
            color: white;
        }

        .filter-btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }

        .questions-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            min-height: 300px;
        }

        .question-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .question-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .question-type-badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            font-size: 12px;
            color: #333;
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

        .question-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #666;
            font-size: 14px;
        }

        .question-body {
            padding: 16px;
        }

        .question-text {
            margin-bottom: 16px;
            font-size: 16px;
        }

        .question-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid #e0e0e0;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            padding: 6px 10px;
            border-radius: 4px;
        }

        .action-btn:hover {
            background-color: #f0f0f0;
        }

        .action-btn.edit {
            color: #0070c0;
        }

        .action-btn.delete {
            color: #dc3545;
        }

        .no-questions {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                flex-direction: column;
                width: 100%;
            }

            .filter-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
        }
        
        .modal-content {
            width: 100%;
            max-width: 600px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 1;
        }
        
        .modal-title {
            margin: 0;
            font-size: 20px;
            font-weight: 500;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .modal-body {
            padding: 16px;
        }
        
        .modal-footer {
            padding: 16px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            position: sticky;
            bottom: 0;
            background-color: #fff;
            z-index: 1;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .question-type-selector {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        
        .type-option {
            flex: 1;
            cursor: pointer;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .type-option:hover {
            background-color: #f8f9fa;
        }
        
        .type-option.selected {
            background-color: #e3f2fd;
            border-color: #0070c0;
            color: #0070c0;
        }
        
        .type-icon {
            font-size: 24px;
            margin-bottom: 4px;
        }
        
        .answer-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .answer-checkbox {
            min-width: 20px;
        }
        
        .answer-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }
        
        .remove-answer-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }
        
        .add-answer-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            margin-top: 8px;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
        }
        
        .radio-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .help-text {
            margin-top: 4px;
            color: #666;
            font-size: 14px;
        }

        /* Test Case Styles */
        .test-case {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        
        .test-case-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .test-case-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }
        
        .test-case-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .hidden-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .test-case-body {
            padding: 16px;
        }
        
        .remove-test-case-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 18px;
        }
        
        .add-test-case-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            margin-top: 8px;
        }

        /* Answer Preview Styles */
        .answer-preview {
            margin-top: 10px;
            border-top: 1px dashed #e0e0e0;
            padding-top: 10px;
        }
        
        .answer-choice {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            padding: 6px 10px;
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
            margin-right: 8px;
            font-size: 16px;
        }
        
        .answer-choice.correct .choice-icon {
            color: #28a745;
        }
        
        .more-answers {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        
        /* Programming Preview Styles */
        .programming-preview {
            margin-top: 10px;
            border-top: 1px dashed #e0e0e0;
            padding-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .programming-language, .test-case-summary, .code-preview {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
        }
        
        .programming-language .material-symbols-rounded,
        .test-case-summary .material-symbols-rounded,
        .code-preview .material-symbols-rounded {
            font-size: 16px;
            color: #666;
        }
        
        .code-preview code {
            background-color: #f5f5f5;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 400px;
            display: inline-block;
            vertical-align: middle;
        }
        
        /* Truncate long question text */
        .question-text {
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }
        
        .question-text.expanded {
            max-height: none;
        }
        
        .question-text:not(.expanded)::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30px;
            background: linear-gradient(transparent, white);
            pointer-events: none;
        }
        
        .expand-btn {
            color: #75343A;
            font-size: 13px;
            cursor: pointer;
            margin-top: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="question-bank-container">
            <div class="page-header">
                <h1>Question Bank</h1>
                <div class="header-actions">
                    <button class="import-question-btn" id="import-question-btn">
                        <span class="material-symbols-rounded">upload</span>
                        Import CSV
                    </button>
                    <button class="add-question-btn" id="add-question-btn">
                        <span class="material-symbols-rounded">add</span>
                        Add Question
                    </button>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="filters-container">
                <form id="filter-form" method="GET" action="question_bank.php" class="filter-form">
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="filter-input" placeholder="Search questions..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="type">Question Type</label>
                        <select id="type" name="type" class="filter-select">
                            <option value="">All Types</option>
                            <option value="multiple-choice" <?php echo $filter_type === 'multiple-choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                            <option value="true-false" <?php echo $filter_type === 'true-false' ? 'selected' : ''; ?>>True/False</option>
                            <option value="programming" <?php echo $filter_type === 'programming' ? 'selected' : ''; ?>>Programming</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $filter_category === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="filter-btn filter-btn-primary">
                            <span class="material-symbols-rounded">filter_alt</span>
                            Apply Filters
                        </button>
                        <button type="button" id="clear-filters" class="filter-btn filter-btn-secondary">
                            <span class="material-symbols-rounded">clear</span>
                            Clear Filters
                        </button>
                    </div>
                </form>
            </div>

            <div class="questions-list">
                <?php if ($questions_result->num_rows > 0): ?>
                    <?php while ($question = $questions_result->fetch_assoc()): ?>
                        <div class="question-item">
                            <div class="question-header">
                                <span class="question-type-badge <?php echo str_replace('_', '-', $question['question_type']); ?>">
                                    <?php 
                                    $type_label = '';
                                    switch ($question['question_type']) {
                                        case 'multiple-choice':
                                            $type_label = 'Multiple Choice';
                                            break;
                                        case 'true-false':
                                            $type_label = 'True/False';
                                            break;
                                        case 'programming':
                                            $type_label = 'Programming';
                                            break;
                                        default:
                                            $type_label = ucfirst($question['question_type']);
                                    }
                                    echo $type_label;
                                    ?>
                                </span>
                                <div class="question-meta">
                                    <?php if (!empty($question['category'])): ?>
                                        <span>
                                            <span class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">folder</span>
                                            <?php echo htmlspecialchars($question['category']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>
                                        <span class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">star</span>
                                        <?php echo $question['points']; ?> point<?php echo $question['points'] !== '1' ? 's' : ''; ?>
                                    </span>
                                    <span>
                                        <span class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">calendar_today</span>
                                        <?php echo date('M j, Y', strtotime($question['created_at'])); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="question-body">
                                <div class="question-text">
                                    <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                                </div>
                                
                                <?php
                                // Fetch answers for this question
                                $answers_query = "SELECT * FROM question_bank_answers WHERE question_id = ? ORDER BY position ASC";
                                $stmt = $conn->prepare($answers_query);
                                $stmt->bind_param("i", $question['question_id']);
                                $stmt->execute();
                                $answers_result = $stmt->get_result();
                                
                                // For programming questions, get additional details
                                $programming_details = null;
                                $test_cases = [];
                                if ($question['question_type'] === 'programming') {
                                    $prog_query = "SELECT * FROM question_bank_programming WHERE question_id = ?";
                                    $stmt = $conn->prepare($prog_query);
                                    $stmt->bind_param("i", $question['question_id']);
                                    $stmt->execute();
                                    $programming_details = $stmt->get_result()->fetch_assoc();
                                    
                                    // Get test cases
                                    if ($programming_details) {
                                        $test_query = "SELECT * FROM question_bank_test_cases WHERE programming_id = ?";
                                        $stmt = $conn->prepare($test_query);
                                        $stmt->bind_param("i", $programming_details['programming_id']);
                                        $stmt->execute();
                                        $test_cases_result = $stmt->get_result();
                                        while ($test_case = $test_cases_result->fetch_assoc()) {
                                            $test_cases[] = $test_case;
                                        }
                                    }
                                }
                                
                                // Display different content based on question type
                                if ($question['question_type'] === 'multiple-choice' && $answers_result->num_rows > 0) {
                                    echo '<div class="answer-preview">';
                                    $answer_count = 0;
                                    while ($answer = $answers_result->fetch_assoc()) {
                                        $is_correct = $answer['is_correct'] == 1;
                                        $class = $is_correct ? 'correct' : 'incorrect';
                                        $icon = $is_correct ? 'check_circle' : 'radio_button_unchecked';
                                        
                                        echo '<div class="answer-choice ' . $class . '">';
                                        echo '<span class="choice-icon material-symbols-rounded">' . $icon . '</span>';
                                        echo '<span>' . htmlspecialchars($answer['answer_text']) . '</span>';
                                        echo '</div>';
                                        
                                        $answer_count++;
                                        // Show only first 2 answers if there are more than 3
                                        if ($answer_count >= 2 && $answers_result->num_rows > 3) {
                                            echo '<div class="more-answers">+' . ($answers_result->num_rows - 2) . ' more answers</div>';
                                            break;
                                        }
                                    }
                                    echo '</div>';
                                } else if ($question['question_type'] === 'true-false' && $answers_result->num_rows > 0) {
                                    echo '<div class="answer-preview">';
                                    while ($answer = $answers_result->fetch_assoc()) {
                                        if ($answer['is_correct'] == 1) {
                                            echo '<div class="answer-choice correct">';
                                            echo '<span class="choice-icon material-symbols-rounded">check_circle</span>';
                                            echo '<span>Correct answer: ' . htmlspecialchars($answer['answer_text']) . '</span>';
                                            echo '</div>';
                                            break; // Only show the correct answer
                                        }
                                    }
                                    echo '</div>';
                                } else if ($question['question_type'] === 'programming') {
                                    echo '<div class="programming-preview">';
                                    
                                    // Show language
                                    if ($programming_details) {
                                        echo '<div class="programming-language">';
                                        echo '<span class="material-symbols-rounded">code</span>';
                                        echo '<span>Language: ' . ucfirst(htmlspecialchars($programming_details['language'])) . '</span>';
                                        echo '</div>';
                                    }
                                    
                                    // Show test case count
                                    $visible_count = 0;
                                    $hidden_count = 0;
                                    foreach ($test_cases as $test_case) {
                                        if ($test_case['is_hidden'] == 1) {
                                            $hidden_count++;
                                        } else {
                                            $visible_count++;
                                        }
                                    }
                                    
                                    if (count($test_cases) > 0) {
                                        echo '<div class="test-case-summary">';
                                        echo '<span class="material-symbols-rounded">assignment</span>';
                                        echo '<span>' . count($test_cases) . ' test case' . (count($test_cases) !== 1 ? 's' : '') . ' ';
                                        if ($hidden_count > 0) {
                                            echo '(' . $visible_count . ' visible, ' . $hidden_count . ' hidden)';
                                        }
                                        echo '</span>';
                                        echo '</div>';
                                    }
                                    
                                    // Show starter code preview if exists
                                    if ($programming_details && !empty($programming_details['starter_code'])) {
                                        $code_preview = substr($programming_details['starter_code'], 0, 100);
                                        if (strlen($programming_details['starter_code']) > 100) {
                                            $code_preview .= '...';
                                        }
                                        
                                        echo '<div class="code-preview">';
                                        echo '<span class="material-symbols-rounded">description</span>';
                                        echo '<span>Starter code: <code>' . htmlspecialchars($code_preview) . '</code></span>';
                                        echo '</div>';
                                    }
                                    
                                    echo '</div>';
                                }
                                ?>
                            </div>

                            <div class="question-actions">
                                <button class="action-btn edit" data-id="<?php echo $question['question_id']; ?>">
                                    <span class="material-symbols-rounded">edit</span>
                                    Edit
                                </button>
                                <button class="action-btn delete" data-id="<?php echo $question['question_id']; ?>">
                                    <span class="material-symbols-rounded">delete</span>
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-questions">
                        <p>No questions found in the question bank. Click the "Add Question" button to create your first question.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Question Modal -->
<div class="modal-overlay" id="question-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-title">Add Question</h2>
            <button type="button" class="modal-close" id="close-modal">&times;</button>
        </div>
        <form id="question-form" method="POST" action="question_bank.php">
            <input type="hidden" name="action" id="form-action" value="add_question">
            <input type="hidden" name="question_id" id="question_id" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="question_text">Question Text</label>
                    <textarea id="question_text" name="question_text" class="form-control" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Question Type</label>
                    <div class="question-type-selector">
                        <div class="type-option selected" data-type="multiple-choice">
                            <div class="type-icon material-symbols-rounded">checklist</div>
                            <div class="type-label">Multiple Choice</div>
                        </div>
                        <div class="type-option" data-type="true-false">
                            <div class="type-icon material-symbols-rounded">rule</div>
                            <div class="type-label">True/False</div>
                        </div>
                        <div class="type-option" data-type="programming">
                            <div class="type-icon material-symbols-rounded">code</div>
                            <div class="type-label">Programming</div>
                        </div>
                    </div>
                    <input type="hidden" id="question_type" name="question_type" value="multiple-choice">
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" class="form-control" list="category-list" placeholder="Enter or select a category">
                    <datalist id="category-list">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="form-group">
                    <label for="points">Points</label>
                    <input type="number" id="points" name="points" class="form-control" min="1" value="1" required>
                </div>
                
                <!-- Multiple Choice Options -->
                <div id="multiple-choice-options">
                    <div class="form-group">
                        <label>Answer Choices</label>
                        <p class="help-text">Check the box next to correct answer(s).</p>
                        
                        <div class="answer-container">
                            <div class="answer-item">
                                <input type="checkbox" name="correct_answers[]" value="0" class="answer-checkbox">
                                <input type="text" name="answers[]" class="answer-input" placeholder="Answer choice" required>
                                <button type="button" class="remove-answer-btn material-symbols-rounded">close</button>
                            </div>
                            <div class="answer-item">
                                <input type="checkbox" name="correct_answers[]" value="1" class="answer-checkbox">
                                <input type="text" name="answers[]" class="answer-input" placeholder="Answer choice" required>
                                <button type="button" class="remove-answer-btn material-symbols-rounded">close</button>
                            </div>
                        </div>
                        
                        <button type="button" id="add-answer-btn" class="add-answer-btn">
                            <span class="material-symbols-rounded">add</span>
                            Add Answer Choice
                        </button>
                    </div>
                </div>
                
                <!-- True/False Options -->
                <div id="true-false-options">
                    <div class="form-group">
                        <label>Correct Answer</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="true-option" name="correct_tf_answer" value="true" class="radio-input" checked>
                                <label for="true-option">True</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="false-option" name="correct_tf_answer" value="false" class="radio-input">
                                <label for="false-option">False</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Programming Options -->
                <div id="programming-options" style="display: none; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="language">Programming Language</label>
                        <select id="language" name="language" class="form-control" required>
                            <option value="python">Python</option>
                            <option value="java">Java</option>
                            <option value="javascript">JavaScript</option>
                            <option value="c">C</option>
                            <option value="cpp">C++</option>
                            <option value="php">PHP</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="starter_code">Starter Code (Optional)</label>
                        <textarea id="starter_code" name="starter_code" class="form-control" placeholder="Provide starter code for students"></textarea>
                        <p class="help-text">This code will be provided to students as a starting point.</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Test Cases</label>
                        <p class="help-text">Add test cases to validate student answers. At least one test case is required.</p>
                        
                        <div id="test-cases-container">
                            <div class="test-case">
                                <div class="test-case-header">
                                    <h4>Test Case 1</h4>
                                    <div class="test-case-controls">
                                        <label class="hidden-toggle">
                                            <input type="checkbox" name="test_cases[0][is_hidden]" class="is-hidden-checkbox">
                                            Hidden
                                        </label>
                                        <button type="button" class="remove-test-case-btn material-symbols-rounded">close</button>
                                    </div>
                                </div>
                                <div class="test-case-body">
                                    <div class="form-group">
                                        <label>Input</label>
                                        <textarea name="test_cases[0][input]" class="form-control test-input" placeholder="Input for this test case (leave empty if no input is needed)"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Expected Output</label>
                                        <textarea name="test_cases[0][expected_output]" class="form-control test-output" placeholder="Expected output for this test case" required></textarea>
                                    </div>
                                    <div class="hidden-description" style="display: none;">
                                        <div class="form-group">
                                            <label>Description (Optional)</label>
                                            <input type="text" name="test_cases[0][description]" class="form-control test-description" placeholder="Description of what this test case is checking">
                                            <p class="help-text">This will be shown to students instead of the actual test case.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="add-test-case-btn" class="add-test-case-btn">
                            <span class="material-symbols-rounded">add</span>
                            Add Test Case
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="filter-btn filter-btn-secondary" id="cancel-btn">Cancel</button>
                <button type="submit" class="filter-btn filter-btn-primary" id="save-btn">Save Question</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="delete-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Delete Question</h2>
            <button type="button" class="modal-close" id="close-delete-modal">&times;</button>
        </div>
        <form id="delete-form" method="POST" action="question_bank.php">
            <input type="hidden" name="action" value="delete_question">
            <input type="hidden" name="question_id" id="delete_question_id" value="">
            
            <div class="modal-body">
                <p>Are you sure you want to delete this question? This action cannot be undone.</p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="filter-btn filter-btn-secondary" id="cancel-delete-btn">Cancel</button>
                <button type="submit" class="filter-btn" style="background-color: #dc3545; color: white;">Delete</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/side.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const questionModal = document.getElementById('question-modal');
    const deleteModal = document.getElementById('delete-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const closeDeleteModalBtn = document.getElementById('close-delete-modal');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    
    // Form elements
    const questionForm = document.getElementById('question-form');
    const formAction = document.getElementById('form-action');
    const questionId = document.getElementById('question_id');
    const modalTitle = document.getElementById('modal-title');
    const questionText = document.getElementById('question_text');
    const questionType = document.getElementById('question_type');
    const category = document.getElementById('category');
    const points = document.getElementById('points');
    const expectedOutput = document.getElementById('expected_output');
    
    // Question type options
    const typeOptions = document.querySelectorAll('.type-option');
    const multipleChoiceOptions = document.getElementById('multiple-choice-options');
    const trueFalseOptions = document.getElementById('true-false-options');
    const programmingOptions = document.getElementById('programming-options');
    
    // Multiple choice answer elements
    const answerContainer = document.querySelector('#multiple-choice-options .answer-container');
    const addAnswerBtn = document.getElementById('add-answer-btn');
    
    // Clear filters button
    const clearFiltersBtn = document.getElementById('clear-filters');
    
    // Show add question modal
    addQuestionBtn.addEventListener('click', function() {
        resetForm();
        formAction.value = 'add_question';
        modalTitle.textContent = 'Add Question';
        questionModal.style.display = 'flex';
    });
    
    // Close question modal
    closeModalBtn.addEventListener('click', function() {
        questionModal.style.display = 'none';
    });
    
    // Close delete modal
    closeDeleteModalBtn.addEventListener('click', function() {
        deleteModal.style.display = 'none';
    });
    
    // Cancel button in question modal
    cancelBtn.addEventListener('click', function() {
        questionModal.style.display = 'none';
    });
    
    // Cancel button in delete modal
    cancelDeleteBtn.addEventListener('click', function() {
        deleteModal.style.display = 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === questionModal) {
            questionModal.style.display = 'none';
        }
        if (e.target === deleteModal) {
            deleteModal.style.display = 'none';
        }
    });
    
    // Handle question type selection
    typeOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            typeOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Update hidden input value
            const type = this.getAttribute('data-type');
            questionType.value = type;
            
            // Show/hide appropriate options and handle required attributes
            if (type === 'multiple-choice') {
                multipleChoiceOptions.style.display = 'block';
                trueFalseOptions.style.display = 'none';
                programmingOptions.style.display = 'none';
                
                // Enable required on multiple choice inputs
                const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
                answerInputs.forEach(input => input.setAttribute('required', ''));
                
                // Disable required on programming inputs
                const testOutputs = document.querySelectorAll('.test-output');
                testOutputs.forEach(output => output.removeAttribute('required'));
            } else if (type === 'true-false') {
                multipleChoiceOptions.style.display = 'none';
                trueFalseOptions.style.display = 'block';
                programmingOptions.style.display = 'none';
                
                // Disable required on multiple choice inputs
                const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
                answerInputs.forEach(input => input.removeAttribute('required'));
                
                // Disable required on programming inputs
                const testOutputs = document.querySelectorAll('.test-output');
                testOutputs.forEach(output => output.removeAttribute('required'));
            } else if (type === 'programming') {
                multipleChoiceOptions.style.display = 'none';
                trueFalseOptions.style.display = 'none';
                programmingOptions.style.display = 'block';
                
                // Disable required on multiple choice inputs
                const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
                answerInputs.forEach(input => input.removeAttribute('required'));
                
                // Enable required on test outputs that are visible
                const testOutputs = document.querySelectorAll('.test-output');
                testOutputs.forEach(output => {
                    if (programmingOptions.style.display !== 'none') {
                        output.setAttribute('required', '');
                    } else {
                        output.removeAttribute('required');
                    }
                });
            }
        });
    });
    
    // Add answer choice
    if (addAnswerBtn) {
        addAnswerBtn.addEventListener('click', function() {
            if (!answerContainer) return;
            
            const answerItems = answerContainer.querySelectorAll('.answer-item');
            const newIndex = answerItems.length;
            
            const answerItem = document.createElement('div');
            answerItem.className = 'answer-item';
            
            answerItem.innerHTML = `
                <input type="checkbox" name="correct_answers[]" value="${newIndex}" class="answer-checkbox">
                <input type="text" name="answers[]" class="answer-input" placeholder="Answer choice" required>
                <button type="button" class="remove-answer-btn material-symbols-rounded">close</button>
            `;
            
            answerContainer.appendChild(answerItem);
            
            // Add event listener to new remove button
            const removeBtn = answerItem.querySelector('.remove-answer-btn');
            removeBtn.addEventListener('click', function() {
                removeAnswer(this);
            });
        });
    }
    
    // Remove answer choice
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-answer-btn')) {
            removeAnswer(e.target);
        }
    });
    
    function removeAnswer(button) {
        if (!answerContainer) return;
        
        const answerItems = answerContainer.querySelectorAll('.answer-item');
        
        // Don't remove if it's the last answer
        if (answerItems.length <= 1) {
            return;
        }
        
        const answerItem = button.closest('.answer-item');
        answerItem.remove();
        
        // Update indices for remaining answers
        updateAnswerIndices();
    }
    
    function updateAnswerIndices() {
        if (!answerContainer) return;
        
        const checkboxes = answerContainer.querySelectorAll('.answer-checkbox');
        checkboxes.forEach((checkbox, index) => {
            checkbox.value = index;
        });
    }
    
    // Edit question
    const editButtons = document.querySelectorAll('.action-btn.edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            
            // Fetch question details
            fetch(`get_question_details.php?question_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateForm(data.question);
                        formAction.value = 'edit_question';
                        modalTitle.textContent = 'Edit Question';
                        questionModal.style.display = 'flex';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching question details.');
                });
        });
    });
    
    // Delete question
    const deleteButtons = document.querySelectorAll('.action-btn.delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('delete_question_id').value = id;
            deleteModal.style.display = 'flex';
        });
    });
    
    // Clear filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            document.getElementById('search').value = '';
            document.getElementById('type').value = '';
            document.getElementById('category').value = '';
            document.getElementById('filter-form').submit();
        });
    }
    
    // Reset form to default state
    function resetForm() {
        if (!questionForm) return;
        
        questionForm.reset();
        questionId.value = '';
        
        // Set default question type
        typeOptions.forEach(opt => opt.classList.remove('selected'));
        const defaultTypeOption = document.querySelector('.type-option[data-type="multiple-choice"]');
        if (defaultTypeOption) {
            defaultTypeOption.classList.add('selected');
        }
        questionType.value = 'multiple-choice';
        
        // Show multiple choice options by default
        if (multipleChoiceOptions) multipleChoiceOptions.style.display = 'block';
        if (trueFalseOptions) trueFalseOptions.style.display = 'none';
        if (programmingOptions) programmingOptions.style.display = 'none';
        
        // Handle required attributes
        const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
        answerInputs.forEach(input => input.setAttribute('required', ''));
        
        const testOutputs = document.querySelectorAll('.test-output');
        testOutputs.forEach(output => output.removeAttribute('required'));
        
        // Reset answer choices
        try {
            if (answerContainer) {
                // Keep at least one answer choice
                const answerItems = answerContainer.querySelectorAll('.answer-item');
                
                // Remove all but the first answer choice
                for (let i = answerItems.length - 1; i > 0; i--) {
                    answerContainer.removeChild(answerItems[i]);
                }
                
                // Reset the first answer choice if it exists
                if (answerItems.length > 0) {
                    const firstAnswerCheckbox = answerItems[0].querySelector('.answer-checkbox');
                    const firstAnswerInput = answerItems[0].querySelector('.answer-input');
                    
                    if (firstAnswerCheckbox) firstAnswerCheckbox.checked = false;
                    if (firstAnswerInput) firstAnswerInput.value = '';
                } else {
                    // If no answer items exist, create a default one
                    const answerItem = document.createElement('div');
                    answerItem.className = 'answer-item';
                    
                    answerItem.innerHTML = `
                        <input type="checkbox" name="correct_answers[]" value="0" class="answer-checkbox">
                        <input type="text" name="answers[]" class="answer-input" placeholder="Answer choice" required>
                        <button type="button" class="remove-answer-btn material-symbols-rounded">close</button>
                    `;
                    
                    answerContainer.appendChild(answerItem);
                }
            }
            
            // Reset test cases
            if (testCasesContainer) {
                // Clear existing test cases
                while (testCasesContainer.firstChild) {
                    testCasesContainer.removeChild(testCasesContainer.firstChild);
                }
                
                // Add a default test case
                const testCase = document.createElement('div');
                testCase.className = 'test-case';
                
                testCase.innerHTML = `
                    <div class="test-case-header">
                        <h4>Test Case 1</h4>
                        <div class="test-case-controls">
                            <label class="hidden-toggle">
                                <input type="checkbox" name="test_cases[0][is_hidden]" class="is-hidden-checkbox">
                                Hidden
                            </label>
                            <button type="button" class="remove-test-case-btn material-symbols-rounded">close</button>
                        </div>
                    </div>
                    <div class="test-case-body">
                        <div class="form-group">
                            <label>Input</label>
                            <textarea name="test_cases[0][input]" class="form-control test-input" placeholder="Input for this test case (leave empty if no input is needed)"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Expected Output</label>
                            <textarea name="test_cases[0][expected_output]" class="form-control test-output" placeholder="Expected output for this test case"></textarea>
                        </div>
                        <div class="hidden-description" style="display: none;">
                            <div class="form-group">
                                <label>Description (Optional)</label>
                                <input type="text" name="test_cases[0][description]" class="form-control test-description" placeholder="Description of what this test case is checking">
                                <p class="help-text">This will be shown to students instead of the actual test case.</p>
                            </div>
                        </div>
                    </div>
                `;
                
                testCasesContainer.appendChild(testCase);
            }
        } catch (error) {
            console.error('Error resetting form:', error);
        }
    }
    
    // Populate form with question data
    function populateForm(question) {
        if (!questionForm) return;
        
        questionId.value = question.question_id;
        questionText.value = question.question_text;
        category.value = question.category || '';
        points.value = question.points || 1;
        
        // Set question type
        typeOptions.forEach(opt => opt.classList.remove('selected'));
        const typeOption = document.querySelector(`.type-option[data-type="${question.question_type}"]`);
        if (typeOption) {
            typeOption.classList.add('selected');
        }
        questionType.value = question.question_type;
        
        // Show appropriate options and handle required attributes
        if (question.question_type === 'multiple-choice') {
            if (multipleChoiceOptions) multipleChoiceOptions.style.display = 'block';
            if (trueFalseOptions) trueFalseOptions.style.display = 'none';
            if (programmingOptions) programmingOptions.style.display = 'none';
            
            // Enable required on multiple choice inputs
            const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
            answerInputs.forEach(input => input.setAttribute('required', ''));
            
            // Disable required on programming inputs
            const testOutputs = document.querySelectorAll('.test-output');
            testOutputs.forEach(output => output.removeAttribute('required'));
            
            try {
                // Clear existing answers
                if (answerContainer) {
                    while (answerContainer.firstChild) {
                        answerContainer.removeChild(answerContainer.firstChild);
                    }
                    
                    // Add answer choices
                    if (question.answers && question.answers.length > 0) {
                        question.answers.forEach((answer, index) => {
                            const answerItem = document.createElement('div');
                            answerItem.className = 'answer-item';
                            
                            answerItem.innerHTML = `
                                <input type="checkbox" name="correct_answers[]" value="${index}" class="answer-checkbox" ${answer.is_correct == 1 ? 'checked' : ''}>
                                <input type="text" name="answers[]" class="answer-input" placeholder="Answer choice" value="${answer.answer_text}" required>
                                <button type="button" class="remove-answer-btn material-symbols-rounded">close</button>
                            `;
                            
                            answerContainer.appendChild(answerItem);
                        });
                    } else {
                        // Add a default empty answer
                        const answerItem = document.createElement('div');
                        answerItem.className = 'answer-item';
                        
                        answerItem.innerHTML = `
                            <input type="checkbox" name="correct_answers[]" value="0" class="answer-checkbox">
                            <input type="text" name="answers[]" class="answer-input" placeholder="Answer choice" required>
                            <button type="button" class="remove-answer-btn material-symbols-rounded">close</button>
                        `;
                        
                        answerContainer.appendChild(answerItem);
                    }
                }
            } catch (error) {
                console.error('Error populating answer choices:', error);
            }
        } else if (question.question_type === 'true-false') {
            if (multipleChoiceOptions) multipleChoiceOptions.style.display = 'none';
            if (trueFalseOptions) trueFalseOptions.style.display = 'block';
            if (programmingOptions) programmingOptions.style.display = 'none';
            
            // Disable required on other inputs
            const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
            answerInputs.forEach(input => input.removeAttribute('required'));
            
            const testOutputs = document.querySelectorAll('.test-output');
            testOutputs.forEach(output => output.removeAttribute('required'));
            
            // Set correct answer
            if (question.answers && question.answers.length > 0) {
                const trueOption = document.getElementById('true-option');
                const falseOption = document.getElementById('false-option');
                
                const trueAnswer = question.answers.find(a => a.answer_text === 'True');
                if (trueAnswer && trueAnswer.is_correct == 1 && trueOption) {
                    trueOption.checked = true;
                } else if (falseOption) {
                    falseOption.checked = true;
                }
            }
        } else if (question.question_type === 'programming') {
            if (multipleChoiceOptions) multipleChoiceOptions.style.display = 'none';
            if (trueFalseOptions) trueFalseOptions.style.display = 'none';
            if (programmingOptions) programmingOptions.style.display = 'block';
            
            // Disable required on multiple choice inputs
            const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
            answerInputs.forEach(input => input.removeAttribute('required'));
            
            // Enable required on test outputs that are visible
            const testOutputs = document.querySelectorAll('.test-output');
            testOutputs.forEach(output => {
                if (programmingOptions.style.display !== 'none') {
                    output.setAttribute('required', '');
                } else {
                    output.removeAttribute('required');
                }
            });
            
            // Set language
            const languageSelect = document.getElementById('language');
            if (languageSelect && question.programming && question.programming.language) {
                languageSelect.value = question.programming.language;
            }
            
            // Set starter code
            const starterCode = document.getElementById('starter_code');
            if (starterCode && question.programming && question.programming.starter_code) {
                starterCode.value = question.programming.starter_code;
            }
            
            // Set test cases
            const testCasesContainer = document.getElementById('test-cases-container');
            if (testCasesContainer) {
                // Clear existing test cases
                while (testCasesContainer.firstChild) {
                    testCasesContainer.removeChild(testCasesContainer.firstChild);
                }
                
                // Add test cases from question data
                if (question.test_cases && question.test_cases.length > 0) {
                    question.test_cases.forEach((testCase, index) => {
                        const testCaseElement = document.createElement('div');
                        testCaseElement.className = 'test-case';
                        
                        const isHidden = testCase.is_hidden == 1;
                        
                        testCaseElement.innerHTML = `
                            <div class="test-case-header">
                                <h4>Test Case ${index + 1}</h4>
                                <div class="test-case-controls">
                                    <label class="hidden-toggle">
                                        <input type="checkbox" name="test_cases[${index}][is_hidden]" class="is-hidden-checkbox" ${isHidden ? 'checked' : ''}>
                                        Hidden
                                    </label>
                                    <button type="button" class="remove-test-case-btn material-symbols-rounded">close</button>
                                </div>
                            </div>
                            <div class="test-case-body">
                                <div class="form-group">
                                    <label>Input</label>
                                    <textarea name="test_cases[${index}][input]" class="form-control test-input" placeholder="Input for this test case (leave empty if no input is needed)">${testCase.input || ''}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Expected Output</label>
                                    <textarea name="test_cases[${index}][expected_output]" class="form-control test-output" placeholder="Expected output for this test case" required>${testCase.expected_output || ''}</textarea>
                                </div>
                                <div class="hidden-description" style="display: ${isHidden ? 'block' : 'none'};">
                                    <div class="form-group">
                                        <label>Description (Optional)</label>
                                        <input type="text" name="test_cases[${index}][description]" class="form-control test-description" placeholder="Description of what this test case is checking" value="${testCase.description || ''}">
                                        <p class="help-text">This will be shown to students instead of the actual test case.</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        testCasesContainer.appendChild(testCaseElement);
                    });
                } else {
                    // If no test cases, add a default one
                    addTestCase();
                }
            }
        }
    }

    // Programming test cases
    const testCasesContainer = document.getElementById('test-cases-container');
    const addTestCaseBtn = document.getElementById('add-test-case-btn');
    
    if (addTestCaseBtn) {
        addTestCaseBtn.addEventListener('click', function() {
            addTestCase();
        });
    }
    
    // Handle hidden test case toggle
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('is-hidden-checkbox')) {
            const testCase = e.target.closest('.test-case');
            const hiddenDescription = testCase.querySelector('.hidden-description');
            
            if (hiddenDescription) {
                hiddenDescription.style.display = e.target.checked ? 'block' : 'none';
            }
        }
    });
    
    // Remove test case
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-test-case-btn')) {
            const testCase = e.target.closest('.test-case');
            const testCases = document.querySelectorAll('.test-case');
            
            // Don't remove if it's the last test case
            if (testCases.length <= 1) {
                return;
            }
            
            testCase.remove();
            
            // Update test case numbers
            updateTestCaseIndices();
        }
    });
    
    function addTestCase() {
        if (!testCasesContainer) return;
        
        const testCases = testCasesContainer.querySelectorAll('.test-case');
        const newIndex = testCases.length;
        
        const testCase = document.createElement('div');
        testCase.className = 'test-case';
        
        testCase.innerHTML = `
            <div class="test-case-header">
                <h4>Test Case ${newIndex + 1}</h4>
                <div class="test-case-controls">
                    <label class="hidden-toggle">
                        <input type="checkbox" name="test_cases[${newIndex}][is_hidden]" class="is-hidden-checkbox">
                        Hidden
                    </label>
                    <button type="button" class="remove-test-case-btn material-symbols-rounded">close</button>
                </div>
            </div>
            <div class="test-case-body">
                <div class="form-group">
                    <label>Input</label>
                    <textarea name="test_cases[${newIndex}][input]" class="form-control test-input" placeholder="Input for this test case (leave empty if no input is needed)"></textarea>
                </div>
                <div class="form-group">
                    <label>Expected Output</label>
                    <textarea name="test_cases[${newIndex}][expected_output]" class="form-control test-output" placeholder="Expected output for this test case" required></textarea>
                </div>
                <div class="hidden-description" style="display: none;">
                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <input type="text" name="test_cases[${newIndex}][description]" class="form-control test-description" placeholder="Description of what this test case is checking">
                        <p class="help-text">This will be shown to students instead of the actual test case.</p>
                    </div>
                </div>
            </div>
        `;
        
        testCasesContainer.appendChild(testCase);
    }
    
    function updateTestCaseIndices() {
        if (!testCasesContainer) return;
        
        const testCases = testCasesContainer.querySelectorAll('.test-case');
        
        testCases.forEach((testCase, index) => {
            // Update header
            const header = testCase.querySelector('h4');
            if (header) {
                header.textContent = `Test Case ${index + 1}`;
            }
            
            // Update input names
            const isHiddenCheckbox = testCase.querySelector('.is-hidden-checkbox');
            const inputTextarea = testCase.querySelector('.test-input');
            const outputTextarea = testCase.querySelector('.test-output');
            const descriptionInput = testCase.querySelector('.test-description');
            
            if (isHiddenCheckbox) {
                isHiddenCheckbox.name = `test_cases[${index}][is_hidden]`;
            }
            
            if (inputTextarea) {
                inputTextarea.name = `test_cases[${index}][input]`;
            }
            
            if (outputTextarea) {
                outputTextarea.name = `test_cases[${index}][expected_output]`;
            }
            
            if (descriptionInput) {
                descriptionInput.name = `test_cases[${index}][description]`;
            }
        });
    }

    // Add form submission handler
    questionForm.addEventListener('submit', function(e) {
        const currentQuestionType = document.getElementById('question_type').value;
        
        // Validate based on question type
        if (currentQuestionType === 'multiple-choice') {
            // Check if at least one answer is marked as correct
            const correctAnswers = document.querySelectorAll('#multiple-choice-options .answer-checkbox:checked');
            if (correctAnswers.length === 0) {
                e.preventDefault();
                alert('Please mark at least one answer as correct.');
                return false;
            }
            
            // Disable required attribute on hidden fields to prevent validation errors
            const testOutputs = document.querySelectorAll('.test-output');
            testOutputs.forEach(output => output.removeAttribute('required'));
        } else if (currentQuestionType === 'true-false') {
            // Disable required attribute on hidden fields to prevent validation errors
            const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
            answerInputs.forEach(input => input.removeAttribute('required'));
            
            const testOutputs = document.querySelectorAll('.test-output');
            testOutputs.forEach(output => output.removeAttribute('required'));
        } else if (currentQuestionType === 'programming') {
            // Disable required attribute on hidden fields to prevent validation errors
            const answerInputs = document.querySelectorAll('#multiple-choice-options .answer-input');
            answerInputs.forEach(input => input.removeAttribute('required'));
            
            // Check if at least one test case is added
            const testCases = document.querySelectorAll('.test-case');
            if (testCases.length === 0) {
                e.preventDefault();
                alert('Please add at least one test case.');
                return false;
            }
            
            // Check if all required fields are filled
            let isValid = true;
            document.querySelectorAll('.test-output').forEach(output => {
                if (output.hasAttribute('required') && !output.value.trim()) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields for test cases.');
                return false;
            }
        }
    });

    // Add this to your existing JavaScript in question_bank.php
    const importQuestionBtn = document.getElementById('import-question-btn');
    if (importQuestionBtn) {
        importQuestionBtn.addEventListener('click', function() {
            window.location.href = 'questionBank_import.php';
        });
    }

    // Add expand functionality for long question text
    const questionTexts = document.querySelectorAll('.question-text');
    
    questionTexts.forEach(text => {
        if (text.scrollHeight > 100) {
            const expandBtn = document.createElement('span');
            expandBtn.className = 'expand-btn';
            expandBtn.textContent = 'Show more';
            expandBtn.addEventListener('click', function() {
                if (text.classList.contains('expanded')) {
                    text.classList.remove('expanded');
                    expandBtn.textContent = 'Show more';
                } else {
                    text.classList.add('expanded');
                    expandBtn.textContent = 'Show less';
                }
            });
            
            text.parentNode.insertBefore(expandBtn, text.nextSibling);
        }
    });
});
</script>

</body>
</html>