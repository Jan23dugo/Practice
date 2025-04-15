<?php
session_start(); // Required to access $_SESSION

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Editor</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* Quiz Editor Styles */
        .quiz-editor-container {
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .editor-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background-color: white;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .back-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            font-size: 16px;
            gap: 8px;
        }
        
        .quiz-title {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-settings {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .btn-preview {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .btn-publish {
            background-color: #75343A;
            color: white;
        }
        
        .editor-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 20px;
        }
        
        .questions-panel {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .sidebar-panel {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 16px;
            color: #333;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .search-button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .sidebar-section {
            margin-bottom: 24px;
        }
        
        .sidebar-section h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #333;
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .sidebar-item:hover {
            background-color: #f5f0ff;
        }
        
        .sidebar-item-icon {
            margin-right: 12px;
            color: #666;
        }
        
        .add-question-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 16px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 16px;
            width: 100%;
        }
        
        /* Question Card Styles */
        .question-card {
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
        
        .question-number {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .question-type {
            color: #666;
            font-size: 14px;
        }
        
        .question-settings {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .time-setting, .points-setting {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .question-body {
            padding: 16px;
        }
        
        .question-text {
            margin-bottom: 16px;
            font-size: 16px;
        }
        
        .answer-choices {
            margin-bottom: 16px;
        }
        
        .answer-choice {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        
        .answer-choice.correct {
            background-color: #e6f7e6;
            border-left: 3px solid #28a745;
        }
        
        .answer-choice.incorrect {
            background-color: #ffeaea;
            border-left: 3px solid #dc3545;
        }
        
        .choice-icon {
            margin-right: 12px;
            color: #666;
        }
        
        .choice-icon.correct {
            color: #28a745;
        }
        
        .choice-icon.incorrect {
            color: #dc3545;
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
        
        .action-btn.duplicate {
            color: #666;
        }
        
        .action-btn.delete {
            color: #dc3545;
        }

        /* Settings Modal Styles */
        .settings-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .settings-modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
        }

        .settings-modal-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .settings-modal-title {
            display: flex;
            align-items: flex-start;
            position: relative;
        }

        .settings-icon {
            background-color: #f0f0f0;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }

        .settings-icon .material-symbols-rounded {
            font-size: 24px;
            color: #666;
        }

        .settings-text h2 {
            margin: 0 0 4px 0;
            font-size: 20px;
            color: #333;
        }

        .settings-text p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .close-modal {
            position: absolute;
            top: 0;
            right: 0;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .settings-modal-body {
            padding: 20px;
            flex-grow: 1;
        }

        .settings-form {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 30px;
        }

        .settings-left {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .settings-input, .settings-select {
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
        }

        .settings-input:focus, .settings-select:focus {
            outline: none;
            border-color: #75343A;
            box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
        }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
           
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 12px;
            pointer-events: none;
        }

        .error-message {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #dc3545;
            font-size: 14px;
        }

        .error-message .material-symbols-rounded {
            font-size: 16px;
        }

        .cover-image-container {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .cover-image-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .cover-image-preview {
            position: relative;
            width: 100%;
            height: 180px;
            border-radius: 8px;
            border: 2px dashed #e0e0e0;
            overflow: hidden;
            cursor: pointer;
            background-color: #f0f0f0;
        }

        #cover-image-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cover-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.3);
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-size: 16px;
            font-weight: 500;
        }

        .cover-image-preview:hover .cover-image-overlay {
            opacity: 1;
        }

        .remove-image-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #f0f0f0;
            color: #dc3545;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            width: auto;
            max-width: 180px;
        }

        .remove-image-button:hover {
            background-color: #ffeaea;
        }

        .remove-image-button .material-symbols-rounded {
            font-size: 18px;
        }

        /* Hide the old components */
        .cover-image-placeholder,
        .add-cover-btn,
        .image-actions,
        .image-preview-wrapper,
        .image-upload-placeholder {
            display: none;
        }

        .publish-btn {
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 36px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s, transform 0.2s;
            min-width: 200px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .publish-btn:hover {
            background-color: #7d5bb9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .publish-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .publish-btn .material-symbols-rounded {
            font-size: 20px;
        }

        .settings-textarea {
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
        }

        .settings-textarea:focus {
            outline: none;
            border-color: #75343A;
            box-shadow: 0 0 0 2px rgba(142, 104, 204, 0.2);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .settings-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #75343Ab;
        }

        .date-picker-wrapper, .time-picker-wrapper {
            margin-top: 10px;
        }

        #schedule-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }

        /* Question Type Modal Styles */
        .question-types-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            padding: 10px;
        }
        
        .question-type-card {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .question-type-card:hover {
            background-color: #f5f0ff;
            border-color: #75343A;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(142, 104, 204, 0.15);
        }
        
        .question-type-icon {
            background-color: #f0f0f0;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            transition: all 0.3s ease;
        }
        
        .question-type-card:hover .question-type-icon {
            background-color: #75343A;
        }
        
        .question-type-icon .material-symbols-rounded {
            font-size: 24px;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .question-type-card:hover .question-type-icon .material-symbols-rounded {
            color: white;
        }
        
        .question-type-info h3 {
            margin: 0 0 4px 0;
            font-size: 18px;
            color: #333;
        }
        
        .question-type-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .starter-code {
            margin: 15px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .starter-code h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }

        .code-block {
            background-color: #fff;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.4;
            overflow-x: auto;
            margin: 0;
        }

        .test-cases-summary {
            margin: 15px 0;
        }

        .test-cases-summary h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }

        .test-cases-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .test-case-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            color: #666;
            font-size: 13px;
        }

        .test-case-item.hidden-test-case {
            color: #999;
        }

        .test-case-item .material-symbols-rounded {
            font-size: 16px;
        }

        /* Auto Generate Modal Styles */
        #auto-generate-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .generation-options {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        
        .generation-options h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            color: #333;
        }
        
        .generation-summary {
            background-color: #f0f7ff;
            border: 1px solid #cce5ff;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .summary-title {
            font-weight: 500;
            color: #0070c0;
            margin-bottom: 10px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .question-bank-stats {
            margin-bottom: 20px;
        }

        .stats-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-loading {
            flex: 1;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 4px;
            text-align: center;
        }

        .stats-content {
            flex: 1;
            padding: 10px;
            background-color: #fff;
            border-radius: 4px;
            display: none;
        }

        .stat-item {
            margin-bottom: 10px;
        }

        .stat-label {
            font-weight: 500;
            color: #333;
        }

        .stat-value {
            font-weight: 500;
            color: #75343A;
        }

        .stat-breakdown {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        /* Search filter styles */
        .filter-container {
            margin-top: 10px;
        }

        .filter-container label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #666;
        }

        .clear-search-button:hover {
            color: #75343A;
        }

        .no-search-results {
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
            padding: 30px;
            text-align: center;
        }

        .image-preview-wrapper {
            position: relative;
            width: 100%;
            height: 200px;
            border-radius: 8px;
            overflow: hidden;
        }

        .image-upload-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .image-upload-placeholder:hover {
            background-color: rgba(255, 255, 255, 0.9);
        }

        .image-upload-placeholder span {
            color: #666;
            font-size: 24px;
        }

        .image-actions {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border-radius: 0 8px 0 8px;
            background-color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .remove-image-btn {
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

        .remove-image-btn:hover {
            background-color: #f0f0f0;
        }

        /* Update the settings-modal-footer and publish-btn styles */
        .settings-modal-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: center;
            background-color: #f8f9fa;
        }

        .publish-btn {
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 36px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s, transform 0.2s;
            min-width: 200px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .publish-btn:hover {
            background-color: #7d5bb9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .publish-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .publish-btn .material-symbols-rounded {
            font-size: 20px;
        }
        
        /* Alert Modal Styles */
        .alert-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .alert-modal-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }
        
        .alert-modal-content {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .alert-modal-overlay.show .alert-modal-content {
            transform: translateY(0);
        }
        
        .alert-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f0f9ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .alert-icon .material-symbols-rounded {
            font-size: 32px;
            color: #0070c0;
        }
        
        .alert-icon.success {
            background-color: #f0fff0;
        }
        
        .alert-icon.success .material-symbols-rounded {
            color: #28a745;
        }
        
        .alert-icon.error {
            background-color: #fff0f0;
        }
        
        .alert-icon.error .material-symbols-rounded {
            color: #dc3545;
        }
        
        .alert-icon.warning {
            background-color: #fffcf0;
        }
        
        .alert-icon.warning .material-symbols-rounded {
            color: #ffc107;
        }
        
        .alert-message {
            font-size: 18px;
            color: #333;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        
        .alert-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }
        
        .alert-btn {
            padding: 10px 30px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #75343A;
            color: white;
            border: none;
            min-width: 100px;
        }
        
        .alert-btn:hover {
            background-color: #7d5bb9;
            transform: translateY(-2px);
        }
        
        .alert-btn.secondary {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .alert-btn.secondary:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="editor-header">
            <a href="exam.php" class="back-link">
                <span class="material-symbols-rounded">arrow_back</span>
                <h2 class="quiz-title">Untitled Quiz</h2>
            </a>
            <div class="header-actions">
                <button class="btn btn-settings">
                    <span class="material-symbols-rounded">settings</span>
                    Settings
                </button>
                <button type="button" class="btn btn-outline" onclick="previewExam()">
                    <span class="material-symbols-rounded">visibility</span>
                    Preview Exam
                </button>
                <button class="btn btn-publish">
                    <span class="material-symbols-rounded">publish</span>
                    Publish
                </button>
            </div>
        </div>

        <div class="quiz-editor-container">
            <div class="editor-content">
                <div class="questions-panel">
                    <?php
                    // Include database connection
                    include('config/config.php');
                    
                    // Get exam ID from URL parameter
                    $is_new_exam = isset($_GET['new']) && $_GET['new'] == 1;
                    $exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
                    
                    if ($is_new_exam) {
                        // Set up for a new exam
                        $exam_title = "Untitled Exam";
                        $exam_description = "";
                        // Other default values
                    } else if ($exam_id > 0) {
                        // Fetch exam details
                        $query = "SELECT title FROM exams WHERE exam_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $exam_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $exam = $result->fetch_assoc();
                            // Update the quiz title in the header
                            echo "<script>document.querySelector('.quiz-title').textContent = '" . htmlspecialchars($exam['title'], ENT_QUOTES) . "';</script>";
                        }
                        
                        // Fetch questions for this exam
                        $query = "SELECT q.*, COUNT(a.answer_id) as answer_count 
                                  FROM questions q 
                                  LEFT JOIN answers a ON q.question_id = a.question_id 
                                  WHERE q.exam_id = ? 
                                  GROUP BY q.question_id 
                                  ORDER BY q.position ASC";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $exam_id);
                        $stmt->execute();
                        $questions_result = $stmt->get_result();
                        
                        if ($questions_result->num_rows > 0) {
                            $question_number = 1;
                            
                            while ($question = $questions_result->fetch_assoc()) {
                                // Get answers for this question
                                $query = "SELECT * FROM answers WHERE question_id = ? ORDER BY position ASC";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("i", $question['question_id']);
                                $stmt->execute();
                                $answers_result = $stmt->get_result();
                                
                                // For programming questions, get additional details
                                $programming_details = null;
                                $test_cases = [];
                                if ($question['question_type'] === 'programming') {
                                    $prog_query = "SELECT * FROM programming_questions WHERE question_id = ?";
                                    $stmt = $conn->prepare($prog_query);
                                    $stmt->bind_param("i", $question['question_id']);
                                    $stmt->execute();
                                    $programming_details = $stmt->get_result()->fetch_assoc();
                                    
                                    // Get test cases
                                    if ($programming_details) {
                                        $test_query = "SELECT * FROM test_cases WHERE programming_id = ?";
                                        $stmt = $conn->prepare($test_query);
                                        $stmt->bind_param("i", $programming_details['programming_id']);
                                        $stmt->execute();
                                        $test_cases_result = $stmt->get_result();
                                        while ($test_case = $test_cases_result->fetch_assoc()) {
                                            $test_cases[] = $test_case;
                                        }
                                    }
                                }
                                
                                // Start question card
                                echo '<div class="question-card">';
                                echo '<div class="question-header">';
                                echo '<div class="question-number">';
                                echo '<span>' . $question_number . '.</span>';
                                echo '<span class="question-type">' . ucfirst($question['question_type']) . '</span>';
                                echo '</div>';
                                echo '<div class="question-settings">';
                                echo '<div class="points-setting">';
                                echo '<span class="material-symbols-rounded">star</span>';
                                echo $question['points'] . ' point' . ($question['points'] > 1 ? 's' : '');
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                                
                                echo '<div class="question-body">';
                                echo '<div class="question-text">';
                                echo $question['question_text'];
                                echo '</div>';
                                
                                // Display different content based on question type
                                if ($question['question_type'] === 'programming') {
                                    // Show starter code if exists
                                    if ($programming_details && !empty($programming_details['starter_code'])) {
                                        echo '<div class="starter-code">';
                                        echo '<h4>Starter Code:</h4>';
                                        echo '<pre class="code-block">' . htmlspecialchars($programming_details['starter_code']) . '</pre>';
                                        echo '</div>';
                                    }
                                    
                                    // Show test cases summary
                                    if (!empty($test_cases)) {
                                        echo '<div class="test-cases-summary">';
                                        echo '<h4>Test Cases (' . count($test_cases) . '):</h4>';
                                        echo '<ul class="test-cases-list">';
                                        foreach ($test_cases as $index => $test_case) {
                                            $is_hidden = $test_case['is_hidden'] == 1;
                                            echo '<li class="test-case-item ' . ($is_hidden ? 'hidden-test-case' : '') . '">';
                                            echo '<span class="material-symbols-rounded">' . ($is_hidden ? 'visibility_off' : 'visibility') . '</span>';
                                            echo 'Test Case ' . ($index + 1);
                                            if ($is_hidden && !empty($test_case['hidden_description'])) {
                                                echo ' - ' . htmlspecialchars($test_case['hidden_description']);
                                            }
                                            echo '</li>';
                                        }
                                        echo '</ul>';
                                        echo '</div>';
                                    }
                                } else if ($answers_result->num_rows > 0) {
                                    // Existing code for multiple choice/true-false answers
                                    echo '<div class="answer-choices">';
                                    while ($answer = $answers_result->fetch_assoc()) {
                                        $is_correct = $answer['is_correct'] == 1;
                                        $class = $is_correct ? 'correct' : 'incorrect';
                                        $icon = $is_correct ? 'check_circle' : 'cancel';
                                        
                                        echo '<div class="answer-choice ' . $class . '">';
                                        echo '<span class="choice-icon ' . $class . ' material-symbols-rounded">' . $icon . '</span>';
                                        echo '<span>' . htmlspecialchars($answer['answer_text']) . '</span>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                                
                                echo '<div class="question-actions">';
                                echo '<button class="action-btn edit" data-question-id="' . $question['question_id'] . '">';
                                echo '<span class="material-symbols-rounded">edit</span>';
                                echo 'Edit';
                                echo '</button>';
                                echo '<button class="action-btn duplicate" data-question-id="' . $question['question_id'] . '">';
                                echo '<span class="material-symbols-rounded">content_copy</span>';
                                echo 'Duplicate';
                                echo '</button>';
                                echo '<button class="action-btn delete" data-question-id="' . $question['question_id'] . '">';
                                echo '<span class="material-symbols-rounded">delete</span>';
                                echo 'Delete';
                                echo '</button>';
                                echo '</div>';
                                echo '</div>';
                                
                                $question_number++;
                            }
                        } else {
                            // No questions found
                            echo '<div class="no-questions">';
                            echo '<p>No questions added yet. Click the button below to add your first question.</p>';
                            echo '</div>';
                        }
                    } // Add this closing brace to match the else if ($exam_id > 0) statement
                    ?>

                    <button class="add-question-btn" id="add-question-btn">
                        <span class="material-symbols-rounded">add</span>
                        Add question
                    </button>
                </div>

                <div class="sidebar-panel">

                    <div class="sidebar-section">
                        <h3>Search questions</h3>
                        <div class="search-container">
                            <span class="search-icon material-symbols-rounded">search</span>
                            <input type="text" class="search-input" placeholder="Search question...">
                            <button class="search-button">Search</button>
                        </div>
                        
                        <!-- New Question Type Filter -->
                        <div class="filter-container" style="margin-top: 10px;">
                            <label for="sidebar-question-type" style="display: block; margin-bottom: 5px; font-size: 14px;">Filter by type:</label>
                            <div class="select-wrapper" style="width: 100%;">
                                <select id="sidebar-question-type" class="settings-select">
                                    <option value="">All Question Types</option>
                                    <option value="multiple-choice">Multiple Choice</option>
                                    <option value="true-false">True/False</option>
                                    <option value="programming">Programming</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-section">
                        <h3>Import from</h3>
                        <div class="sidebar-item" id="import-from-question-bank">
                            <span class="sidebar-item-icon material-symbols-rounded">inventory_2</span>
                            <span>Question Bank</span>
                        </div>
                        <div class="sidebar-item" id="auto-generate-questions">
                            <span class="sidebar-item-icon material-symbols-rounded">auto_awesome</span>
                            <span>Auto Generate Questions</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="settings-modal-overlay" id="settings-modal">
    <div class="settings-modal-content">
        <form id="examForm" action="save_exam.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="exam_id" id="exam_id" value="<?php echo isset($_GET['exam_id']) ? $_GET['exam_id'] : ''; ?>">
            
            <div class="settings-modal-header">
                <div class="settings-modal-title">
                    <div class="settings-icon">
                        <span class="material-symbols-rounded">settings</span>
                    </div>
                    <div class="settings-text">
                        <h2>Great, you're almost done</h2>
                        <p>Review quiz settings and you're good to go</p>
                    </div>
                    <button type="button" class="close-modal" id="close-settings-modal">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
            </div>
            <div class="settings-modal-body">
                <div class="settings-form">
                    <div class="settings-left">
                        <div class="form-group">
                            <label for="quiz-name">Name</label>
                            <input type="text" id="quiz-name" name="quiz-name" class="settings-input" value="Untitled Quiz">
                            <div class="error-message" id="name-error">
                                <span class="material-symbols-rounded">error</span>
                                Name should be at least 4 characters long
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="quiz-description">Description (optional)</label>
                            <textarea id="quiz-description" name="quiz-description" class="settings-textarea" placeholder="Enter a description for your quiz"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="exam-type">Exam Type</label>
                            <div class="select-wrapper">
                                <select id="exam-type" name="exam-type" class="settings-select">
                                    <option value="" selected disabled>Select exam type</option>
                                    <option value="tech">Tech</option>
                                    <option value="non-tech">Non-Tech</option>
                                </select>
                            </div>
                            <div class="error-message" id="exam-type-error">
                                <span class="material-symbols-rounded">error</span>
                                Please select the exam type
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="exam-duration">Duration (in minutes)</label>
                            <input type="number" 
                                   id="exam-duration" 
                                   name="duration" 
                                   class="settings-input" 
                                   min="1" 
                                   max="480" 
                                   value="60" 
                                   required>
                            <div class="error-message" id="duration-error" style="display: none;">
                                <span class="material-symbols-rounded">error</span>
                                Duration must be between 1 and 480 minutes
                            </div>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                                Set the time limit for completing the exam (maximum 8 hours)
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="schedule-exam" name="is_scheduled" class="settings-checkbox" value="1">
                                <label for="schedule-exam">Schedule Exam (required for student assignment)</label>
                            </div>
                            <div id="schedule-container" style="display: none;">
                                <div class="date-picker-wrapper">
                                    <label for="scheduled_date">Exam Date</label>
                                    <input type="date" id="scheduled_date" name="scheduled_date" class="settings-input">
                                </div>
                                <div class="time-picker-wrapper">
                                    <label for="scheduled_time">Exam Time</label>
                                    <input type="time" id="scheduled_time" name="scheduled_time" class="settings-input">
                                </div>
                                <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                                    <strong>Note:</strong> Scheduling is required for the exam to be automatically assigned to eligible students
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Randomization Options (Optional)</label>
                            <div class="randomization-options" style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 10px;">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="randomize-questions" name="randomize-questions" class="settings-checkbox">
                                    <label for="randomize-questions">Randomize Question Order</label>
                                    <p style="margin: 5px 0 0 26px; font-size: 12px; color: #666;">Questions will appear in a different order for each student</p>
                                </div>
                                
                                <div class="checkbox-wrapper" style="margin-top: 15px;">
                                    <input type="checkbox" id="randomize-choices" name="randomize-choices" class="settings-checkbox">
                                    <label for="randomize-choices">Randomize Answer Choices</label>
                                    <p style="margin: 5px 0 0 26px; font-size: 12px; color: #666;">For multiple choice questions, answer options will be shuffled</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Passing Score (Optional)</label>
                            <div class="passing-score-options" style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 10px;">
                                <div class="select-wrapper" style="margin-bottom: 15px;">
                                    <label for="passing-score-type" style="display: block; margin-bottom: 5px; font-size: 14px;">Passing Score Type</label>
                                    <select id="passing-score-type" name="passing_score_type" class="settings-select">
                                        <option value="">No passing score</option>
                                        <option value="percentage">Percentage</option>
                                        <option value="points">Points</option>
                                    </select>
                                </div>
                                
                                <div id="passing-score-container" style="display: none;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="passing-score" style="display: block; margin-bottom: 5px; font-size: 14px;">
                                            Passing Score (<span id="passing-score-unit">points</span>)
                                        </label>
                                        <input type="number" 
                                               id="passing-score" 
                                               name="passing_score" 
                                               class="settings-input" 
                                               min="0"
                                               max="100"
                                               step="1"
                                               placeholder="Enter passing score">
                                        <p id="passing-score-hint" style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                                            Leave blank if no passing score is required
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="settings-right">
                        <div class="cover-image-container">
                            <div class="cover-image-wrapper">
                                <?php
                                // Check if there's an existing cover image
                                $hasCoverImage = !empty($examSettings['cover_image']);
                                ?>
                                <div class="cover-image-preview">
                                    <img id="cover-image-preview" src="<?php echo $hasCoverImage ? $examSettings['cover_image'] : ''; ?>" 
                                         style="<?php echo $hasCoverImage ? 'display: block;' : 'display: none;'; ?>">
                                    
                                    <div class="cover-image-overlay" id="cover-image-overlay">
                                        <span id="cover-image-text">Change cover image</span>
                                        <input type="file" id="cover-image" name="cover-image" style="display: none;" accept="image/*">
                                    </div>
                                </div>
                                
                                <button type="button" id="remove-image-btn" class="remove-image-button">
                                    <span class="material-symbols-rounded">delete</span>
                                    Remove Image
                                </button>
                            </div>
                            <!-- Hidden input to track if we should remove the image -->
                            <input type="hidden" id="remove-cover-image" name="remove-cover-image" value="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="settings-modal-footer">
                <button type="submit" class="publish-btn" id="confirm-publish">
                    <span class="material-symbols-rounded">publish</span>
                    Publish
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Question Type Modal -->
<div class="settings-modal-overlay" id="question-type-modal">
    <div class="settings-modal-content" style="max-width: 600px;">
        <div class="settings-modal-header">
            <div class="settings-modal-title">
                <div class="settings-icon">
                    <span class="material-symbols-rounded">quiz</span>
                </div>
                <div class="settings-text">
                    <h2>Select Question Type</h2>
                    <p>Choose the type of question you want to add</p>
                </div>
                <button class="close-modal" id="close-question-type-modal">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
        </div>
        <div class="settings-modal-body">
            <div class="question-types-grid">
                <div class="question-type-card" data-type="multiple_choice">
                    <div class="question-type-icon">
                        <span class="material-symbols-rounded">checklist</span>
                    </div>
                    <div class="question-type-info">
                        <h3>Multiple Choice</h3>
                        <p>Create a question with multiple options and one or more correct answers</p>
                    </div>
                </div>
                
                <div class="question-type-card" data-type="true_false">
                    <div class="question-type-icon">
                        <span class="material-symbols-rounded">rule</span>
                    </div>
                    <div class="question-type-info">
                        <h3>True/False</h3>
                        <p>Create a statement that students will mark as either true or false</p>
                    </div>
                </div>
                
                <div class="question-type-card" data-type="programming">
                    <div class="question-type-icon">
                        <span class="material-symbols-rounded">code</span>
                    </div>
                    <div class="question-type-info">
                        <h3>Programming</h3>
                        <p>Create a coding challenge with test cases to evaluate solutions</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question Bank Modal -->
<div class="settings-modal-overlay" id="question-bank-modal">
    <div class="settings-modal-content" style="max-width: 900px;">
        <div class="settings-modal-header">
            <div class="settings-modal-title">
                <div class="settings-icon">
                    <span class="material-symbols-rounded">inventory_2</span>
                </div>
                <div class="settings-text">
                    <h2>Question Bank</h2>
                    <p>Select questions to import into your exam</p>
                </div>
                <button class="close-modal">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
        </div>
        <div class="settings-modal-body">
            <div class="search-container" style="margin-bottom: 20px;">
                <span class="search-icon material-symbols-rounded">search</span>
                <input type="text" id="bank-search-input" class="search-input" placeholder="Search questions...">
                <button id="bank-search-button" class="search-button">Search</button>
            </div>
            
            <div class="filter-container" style="display: flex; gap: 15px; margin-bottom: 20px;">
                <div class="select-wrapper" style="flex: 1;">
                    <select id="question-type-filter" class="settings-select">
                        <option value="">All Question Types</option>
                        <option value="multiple-choice">Multiple Choice</option>
                        <option value="true-false">True/False</option>
                        <option value="programming">Programming</option>
                    </select>
                </div>
                <div class="select-wrapper" style="flex: 1;">
                    <select id="category-filter" class="settings-select">
                        <option value="">All Categories</option>
                        <!-- Categories will be populated from database -->
                    </select>
                </div>
            </div>
            
            <div id="question-bank-list" style="max-height: 400px; overflow-y: auto;">
                <!-- Questions will be loaded here -->
                <div class="loading-indicator" style="text-align: center; padding: 20px;">
                    <p>Loading questions...</p>
                </div>
            </div>
        </div>
        <div class="settings-modal-footer">
            <button type="button" class="publish-btn" id="import-questions-btn">Import Selected</button>
        </div>
    </div>
</div>

<!-- Auto Generate Questions Modal -->
<div class="settings-modal-overlay" id="auto-generate-modal">
    <div class="settings-modal-content" style="max-width: 600px;">
        <div class="settings-modal-header">
            <div class="settings-modal-title">
                <div class="settings-icon">
                    <span class="material-symbols-rounded">auto_awesome</span>
                </div>
                <div class="settings-text">
                    <h2>Auto Generate Questions</h2>
                    <p>Quickly add questions from the question bank</p>
                </div>
                <button class="close-modal" id="close-auto-generate-modal">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
        </div>
        <div class="settings-modal-body">
            <form id="auto-generate-form">
                <!-- Question Bank Stats -->
                <div class="question-bank-stats">
                    <h3>Available Questions</h3>
                    <div class="stats-container">
                        <div class="stats-loading">Loading question counts...</div>
                        <div class="stats-content" style="display: none;">
                            <div class="stat-item total-questions">
                                <span class="stat-label">Total Available:</span>
                                <span class="stat-value" id="total-available-questions">0</span>
                            </div>
                            <div class="stat-breakdown">
                                <div class="stat-item">
                                    <span class="stat-label">Multiple Choice:</span>
                                    <span class="stat-value" id="multiple-choice-count">0</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">True/False:</span>
                                    <span class="stat-value" id="true-false-count">0</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Programming:</span>
                                    <span class="stat-value" id="programming-count">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="total-questions">Number of Questions</label>
                    <input type="number" id="total-questions" name="total-questions" class="settings-input" min="1" max="50" value="10">
                    <small style="color: #666; font-size: 12px;">Maximum 50 questions</small>
                </div>
                
                <div class="form-group">
                    <label>Question Types</label>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="include-multiple-choice" name="question-types[]" value="multiple_choice" class="settings-checkbox question-type-filter" checked>
                        <label for="include-multiple-choice">Multiple Choice</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="include-true-false" name="question-types[]" value="true_false" class="settings-checkbox question-type-filter" checked>
                        <label for="include-true-false">True/False</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="include-programming" name="question-types[]" value="programming" class="settings-checkbox question-type-filter" checked>
                        <label for="include-programming">Programming</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="category-select">Category (Optional)</label>
                    <div class="select-wrapper">
                        <select id="category-select" name="category" class="settings-select category-filter">
                            <option value="" selected>All Categories</option>
                            <!-- Categories will be populated from database -->
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="points-per-question">Points per Question</label>
                    <input type="number" id="points-per-question" name="points-per-question" class="settings-input" min="1" max="100" value="10">
                </div>
            </form>
        </div>
        <div class="settings-modal-footer">
            <button type="button" class="btn btn-settings" id="cancel-auto-generate-btn">Cancel</button>
            <button type="button" class="publish-btn" id="confirm-auto-generate-btn">Generate Questions</button>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div class="alert-modal-overlay" id="alert-modal" style="display: none;">
    <div class="alert-modal-content">
        <div class="alert-icon">
            <span class="material-symbols-rounded" id="alert-icon-symbol">check_circle</span>
        </div>
        <div class="alert-message" id="alert-message">
            Operation completed successfully!
        </div>
        <div class="alert-actions">
            <button class="alert-btn" id="alert-confirm-btn">OK</button>
        </div>
    </div>
</div>

<script src="assets/js/side.js"></script>
<script src="assets/quiz_editor/editor.js"></script>
<script src="assets/quiz_editor/preview_modal.js"></script>

<script>
function previewExam() {
    const examId = new URLSearchParams(window.location.search).get('exam_id');
    if (!examId) {
        alert('Please save the exam first before previewing.');
        return;
    }
    window.location.href = `preview_exam.php?exam_id=${examId}`;
}

// Add event listeners to populate the form with existing exam data when editing
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const examId = urlParams.get('exam_id');
    
    if (examId) {
        // Load exam settings if we're editing an existing exam
        fetch(`get_exam_settings.php?exam_id=${examId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const exam = data.exam;
                    
                    // Populate form fields
                    document.getElementById('quiz-name').value = exam.title || 'Untitled Quiz';
                    document.getElementById('quiz-description').value = exam.description || '';
                    
                    // Set exam type
                    const examTypeSelect = document.getElementById('exam-type');
                    if (examTypeSelect && exam.exam_type) {
                        for (let i = 0; i < examTypeSelect.options.length; i++) {
                            if (examTypeSelect.options[i].value === exam.exam_type) {
                                examTypeSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                    
                    // Set duration
                    if (exam.duration) {
                        document.getElementById('exam-duration').value = exam.duration;
                    }
                    
                    // Set scheduling
                    const scheduleCheckbox = document.getElementById('schedule-exam');
                    const scheduleContainer = document.getElementById('schedule-container');
                    
                    if (exam.is_scheduled == 1) {
                        scheduleCheckbox.checked = true;
                        scheduleContainer.style.display = 'block';
                        
                        if (exam.scheduled_date) {
                            document.getElementById('scheduled_date').value = exam.scheduled_date;
                        }
                        
                        if (exam.scheduled_time) {
                            document.getElementById('scheduled_time').value = exam.scheduled_time;
                        }
                    }
                    
                    // Set randomization
                    document.getElementById('randomize-questions').checked = exam.randomize_questions == 1;
                    document.getElementById('randomize-choices').checked = exam.randomize_choices == 1;
                    
                    // Set passing score
                    const passingScoreTypeSelect = document.getElementById('passing-score-type');
                    const passingScoreContainer = document.getElementById('passing-score-container');
                    
                    if (exam.passing_score_type) {
                        // Set the passing score type dropdown
                        for (let i = 0; i < passingScoreTypeSelect.options.length; i++) {
                            if (passingScoreTypeSelect.options[i].value === exam.passing_score_type) {
                                passingScoreTypeSelect.selectedIndex = i;
                                passingScoreContainer.style.display = 'block';
                                
                                // Update the passing score unit text
                                const passingScoreUnit = document.getElementById('passing-score-unit');
                                if (passingScoreUnit) {
                                    passingScoreUnit.textContent = exam.passing_score_type === 'percentage' ? 'percentage' : 'points';
                                }
                                
                                break;
                            }
                        }
                        
                        // Set the passing score value
                        if (exam.passing_score !== null) {
                            document.getElementById('passing-score').value = exam.passing_score;
                        }
                    }
                    
                    // Load cover image if it exists
                    if (exam.cover_image && exam.cover_image !== 'assets/images/default-exam-cover.jpg') {
                        const previewImg = document.getElementById('cover-image-preview');
                        if (previewImg) {
                            previewImg.src = exam.cover_image;
                            previewImg.style.display = 'block';
                            document.getElementById('cover-image-text').textContent = 'Change cover image';
                            document.getElementById('remove-image-btn').style.display = 'block';
                        }
                    }
                    
                    console.log('Exam settings loaded:', exam);
                }
            })
            .catch(error => {
                console.error('Error loading exam settings:', error);
            });
    }
});

// Event listener for passing score type selection
document.getElementById('passing-score-type').addEventListener('change', function() {
    const passingScoreContainer = document.getElementById('passing-score-container');
    const passingScoreUnit = document.getElementById('passing-score-unit');
    
    if (this.value) {
        passingScoreContainer.style.display = 'block';
        if (passingScoreUnit) {
            passingScoreUnit.textContent = this.value === 'percentage' ? 'percentage' : 'points';
        }
    } else {
        passingScoreContainer.style.display = 'none';
    }
});
</script>

</body>
</html>
