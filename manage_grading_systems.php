<?php
session_start();
require 'config/config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_bulk':
                $grading_name = $_POST['grading_name'];
                $success = true;
                $conn->begin_transaction();

                try {
                    // Debug log
                    error_log("Received POST data: " . print_r($_POST, true));

                    // Add regular grades
                    if (isset($_POST['grades']) && is_array($_POST['grades'])) {
                        foreach ($_POST['grades'] as $grade) {
                            error_log("Processing grade: " . print_r($grade, true));
                            
                            if (!isset($grade['value'], $grade['description'], $grade['min'], $grade['max'])) {
                                throw new Exception("Invalid grade data provided");
                            }

                            $stmt = $conn->prepare("INSERT INTO university_grading_systems 
                                (grading_name, grade_value, description, min_percentage, max_percentage, is_special_grade) 
                                VALUES (?, ?, ?, ?, ?, 0)");
                            
                            if (!$stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }

                            $stmt->bind_param("sssdd", 
                                $grading_name, 
                                $grade['value'], 
                                $grade['description'], 
                                $grade['min'], 
                                $grade['max']
                            );
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Error adding grade: " . $stmt->error);
                            }
                            
                            error_log("Successfully added grade: " . $grade['value']);
                        }
                    } else {
                        error_log("No grades data found in POST");
                    }

                    // Add special grades if any are selected
                    if (isset($_POST['special_grades']) && is_array($_POST['special_grades'])) {
                        $specialGradesMap = [
                            'DRP' => ['value' => 'DRP', 'desc' => 'Dropped'],
                            'OD' => ['value' => 'OD', 'desc' => 'Officially Dropped'],
                            'UD' => ['value' => 'UD', 'desc' => 'Unofficially Dropped'],
                            'NA' => ['value' => '*', 'desc' => 'No Attendance']
                        ];

                        foreach ($_POST['special_grades'] as $code => $value) {
                            if (isset($specialGradesMap[$code])) {
                                $stmt = $conn->prepare("INSERT INTO university_grading_systems 
                                    (grading_name, grade_value, description, is_special_grade) 
                                    VALUES (?, ?, ?, 1)");
                                
                                if (!$stmt) {
                                    throw new Exception("Prepare failed: " . $conn->error);
                                }

                                $gradeValue = $specialGradesMap[$code]['value'];
                                $description = $specialGradesMap[$code]['desc'];
                                
                                $stmt->bind_param("sss", $grading_name, $gradeValue, $description);
                                
                                if (!$stmt->execute()) {
                                    throw new Exception("Error adding special grade: " . $stmt->error);
                                }
                                
                                error_log("Successfully added special grade: " . $code);
                            }
                        }
                    }

                    $conn->commit();
                    $_SESSION['success'] = "Grading system added successfully.";
                } catch (Exception $e) {
                    $conn->rollback();
                    error_log("Error in transaction: " . $e->getMessage());
                    $_SESSION['error'] = $e->getMessage();
                }
                break;

            case 'delete':
                if (!isset($_POST['id'])) {
                    $_SESSION['error'] = "No grade ID provided for deletion.";
                    break;
                }

                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM university_grading_systems WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Grade deleted successfully.";
                } else {
                    $_SESSION['error'] = "Error deleting grade: " . $conn->error;
                }
                break;
        }
        
        header("Location: manage_grading_systems.php");
        exit();
    }
}

// Get all grading systems
$result = $conn->query("SELECT * FROM university_grading_systems ORDER BY grading_name, is_special_grade, grade_value DESC");
$grading_systems = $result->fetch_all(MYSQLI_ASSOC);

// Group grading systems by grading name
$systems = [];
foreach ($grading_systems as $system) {
    $systems[$system['grading_name']][] = $system;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grading Systems</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-title {
            font-size: 36px;
            color: #75343A;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        .add-button {
            background: #75343A;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-button:hover {
            background: #5c2930;
            transform: translateY(-2px);
        }

        .grading-systems-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .university-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .university-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .university-header {
            background: #75343A;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .university-name {
            font-size: 18px;
            font-weight: 500;
            margin: 0;
        }

        .grades-container {
            padding: 15px;
        }

        .grade-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eef0f3;
        }

        .grade-item:last-child {
            border-bottom: none;
        }

        .grade-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .grade-value-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .grade-value {
            font-size: 20px;
            font-weight: 600;
            color: #75343A;
            min-width: 50px;
        }

        .grade-description {
            font-size: 15px;
            color: #444;
        }

        .grade-range {
            font-size: 13px;
            color: #666;
        }

        .grade-actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            padding: 6px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: #75343A;
            color: white;
        }

        .btn-edit:hover {
            background: #5c2930;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #75343A;
            margin-bottom: 15px;
        }

        /* Alert Styles */
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            position: relative;
            animation: modalSlide 0.3s ease;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eef0f3;
        }

        .modal-title {
            margin: 0;
            color: #75343A;
            font-size: 20px;
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: #75343A;
            outline: none;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eef0f3;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: white;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            position: sticky;
            bottom: 0;
        }

        .grade-input-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .grade-input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .grade-input-row:last-child {
            margin-bottom: 0;
        }

        .grade-input-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sub-label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }

        .special-grades-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        .special-grade-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 10px;
        }

        .special-grade-row:last-child {
            margin-bottom: 0;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
            margin: 0;
        }

        .form-check-label {
            font-size: 14px;
            color: #333;
        }

        .special-grade {
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
            color: #666;
        }

        @media (max-width: 768px) {
            .grading-systems-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        /* Update modal size and grades table styles */
        .modal-lg {
            max-width: 800px !important;
            max-height: 90vh !important;
            margin: 20px auto;
        }

        .modal-body {
            max-height: calc(90vh - 120px);
            overflow-y: auto;
            padding: 20px;
        }

        .grades-container {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            max-height: none;
            display: flex;
            flex-direction: column;
        }

        .grades-header {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .grades-table {
            overflow-y: auto;
            max-height: none;
            padding: 0;
        }

        .grades-table-header {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 5;
            display: grid;
            grid-template-columns: 120px 1fr 200px 60px;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 500;
            color: #666;
            font-size: 13px;
        }

        .grades-rows-container {
            max-height: 60vh;
            overflow-y: auto;
            padding: 0 15px;
        }

        .grade-row {
            display: grid;
            grid-template-columns: 120px 1fr 200px 60px;
            gap: 15px;
            padding: 10px 0;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .grade-row:last-child {
            border-bottom: none;
        }

        /* Add scrollbar styling for the rows container */
        .grades-rows-container::-webkit-scrollbar {
            width: 8px;
        }

        .grades-rows-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .grades-rows-container::-webkit-scrollbar-thumb {
            background: #75343A;
            border-radius: 4px;
        }

        .grades-rows-container::-webkit-scrollbar-thumb:hover {
            background: #5c2930;
        }

        /* Special Grades Section */
        .special-grades-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px 20px;
        }

        .special-grades-section h6 {
            margin: 0 0 15px;
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }

        .special-grades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .special-grade-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .special-grade-check {
            width: 18px;
            height: 18px;
        }

        .special-grade-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .special-grade-label {
            font-weight: 600;
            color: #75343A;
            min-width: 40px;
        }

        .special-grade-desc {
            color: #666;
            font-size: 14px;
        }

        /* Form Validation Styles */
        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
        }

        .form-control.is-invalid + .invalid-feedback {
            display: block;
        }

        .empty-grades-message {
            padding: 30px;
            text-align: center;
            color: #666;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 10px 0;
        }

        .grades-container {
            min-height: 200px;
        }

        .btn-add-grade {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: #75343A;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-add-grade:hover {
            background: #5c2930;
            transform: translateY(-1px);
        }

        .btn-add-grade i {
            font-size: 20px;
        }

        /* Accordion Styles */
        .accordion {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 20px 0;
        }

        .accordion-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }

        .accordion-item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .accordion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #75343A;
            color: white;
            cursor: pointer;
            user-select: none;
        }

        .accordion-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .accordion-title h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
        }

        .accordion-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .accordion-icon {
            transition: transform 0.3s ease;
        }

        .accordion-item.active .accordion-icon {
            transform: rotate(180deg);
        }

        .accordion-content {
            display: none;
            padding: 20px;
            background: white;
        }

        .accordion-item.active .accordion-content {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Grades Section Styles */
        .grades-section, .special-grades-section {
            margin-bottom: 30px;
        }

        .grades-section h4, .special-grades-section h4 {
            color: #333;
            font-size: 16px;
            margin: 0 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .grades-table {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }

        .grades-table-header {
            display: grid;
            grid-template-columns: 120px 1fr 200px 100px;
            gap: 15px;
            padding: 12px 20px;
            background: #f1f1f1;
            font-weight: 500;
            color: #666;
        }

        .grade-row {
            display: grid;
            grid-template-columns: 120px 1fr 200px 100px;
            gap: 15px;
            padding: 12px 20px;
            align-items: center;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }

        .grade-row:last-child {
            border-bottom: none;
        }

        .grade-row:hover {
            background-color: #f8f9fa;
        }

        .grade-value {
            font-weight: 600;
            color: #75343A;
        }

        /* Special Grades Grid */
        .special-grades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .special-grade-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .special-grade-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .special-grade-value {
            font-weight: 600;
            color: #75343A;
            padding: 4px 8px;
            background: rgba(117, 52, 58, 0.1);
            border-radius: 4px;
        }

        .special-grade-desc {
            color: #666;
        }

        .special-grade-actions {
            display: flex;
            gap: 8px;
        }

        /* Button Styles */
        .btn-edit, .btn-delete {
            padding: 6px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-edit:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .accordion-content .btn-edit {
            background: #75343A;
            color: white;
        }

        .accordion-content .btn-edit:hover {
            background: #5c2930;
        }

        .btn-delete {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-delete:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .accordion-content .btn-delete {
            background: #dc3545;
            color: white;
        }

        .accordion-content .btn-delete:hover {
            background: #c82333;
        }

        .table-responsive {
            overflow-x: auto;
            margin: 20px 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .grading-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 14px;
        }

        .grading-table th {
            background: #75343A;
            color: white;
            font-weight: 500;
            text-align: left;
            padding: 15px 20px;
            white-space: nowrap;
        }

        .grading-table th:first-child {
            border-top-left-radius: 12px;
        }

        .grading-table th:last-child {
            border-top-right-radius: 12px;
        }

        .grading-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #eef0f3;
            vertical-align: middle;
        }

        .grading-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .grading-system-cell {
            min-width: 200px;
        }

        .grading-system-name {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }

        .grading-system-name i {
            color: #75343A;
            font-size: 20px;
        }

        .grade-value-cell {
            white-space: nowrap;
            font-weight: 500;
        }

        .regular-grade-value {
            color: #75343A;
        }

        .special-grade-badge {
            background: rgba(117, 52, 58, 0.1);
            color: #75343A;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #6c757d;
            font-style: italic;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            padding: 6px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: #75343A;
            color: white;
        }

        .btn-edit:hover {
            background: #5c2930;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .special-grade {
            background-color: #f8f9fa;
        }

        /* Add visual separation between grading systems */
        .grading-table tbody tr:not(:first-child) td.grading-system-cell {
            border-top: 1px solid #eef0f3;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .grading-table {
                font-size: 13px;
            }

            .grading-table td, .grading-table th {
                padding: 12px 15px;
            }

            .grading-system-cell {
                min-width: 150px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="page-header">
            <h1 class="page-title">
                Manage Grading Systems
            </h1>
            <button type="button" class="add-button" onclick="openModal('addModal')">
                <i class="material-symbols-rounded">add</i>
                Add New Grading System
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="material-symbols-rounded">check_circle</i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="material-symbols-rounded">error</i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($systems)): ?>
            <div class="empty-state">
                <i class="material-symbols-rounded">grade</i>
                <h3>No Grading Systems Found</h3>
                <p>Start by adding a new grading system.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="grading-table">
                    <thead>
                        <tr>
                            <th>Grading System</th>
                            <th>Grade Value</th>
                            <th>Description</th>
                            <th>Percentage Range</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($systems as $grading_name => $grades):
                            // Sort grades: regular grades first (by value), then special grades
                            usort($grades, function($a, $b) {
                                if ($a['is_special_grade'] !== $b['is_special_grade']) {
                                    return $a['is_special_grade'] - $b['is_special_grade'];
                                }
                                return strcmp($a['grade_value'], $b['grade_value']);
                            });

                            foreach ($grades as $grade):
                        ?>
                            <tr class="<?php echo $grade['is_special_grade'] ? 'special-grade' : ''; ?>">
                                <td class="grading-system-cell">
                                    <div class="grading-system-name">
                                        <i class="material-symbols-rounded">grade</i>
                                        <?php echo htmlspecialchars($grading_name); ?>
                                    </div>
                                </td>
                                <td class="grade-value-cell">
                                    <?php if ($grade['is_special_grade']): ?>
                                        <span class="special-grade-badge">
                                            <?php echo htmlspecialchars($grade['grade_value']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="regular-grade-value">
                                            <?php echo htmlspecialchars($grade['grade_value']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($grade['description']); ?></td>
                                <td>
                                    <?php if (!$grade['is_special_grade']): ?>
                                        <?php echo number_format($grade['min_percentage'], 2); ?>% - 
                                        <?php echo number_format($grade['max_percentage'], 2); ?>%
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="editGrade(<?php echo $grade['id']; ?>)">
                                            <i class="material-symbols-rounded">edit</i>
                                        </button>
                                        <button class="btn-delete" onclick="deleteGrade(<?php echo $grade['id']; ?>)">
                                            <i class="material-symbols-rounded">delete</i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endforeach;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal" id="addModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">Add New Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form action="" method="POST" id="addGradingForm">
            <input type="hidden" name="action" value="add_bulk">
            <div class="modal-body">
                <div class="form-group mb-4">
                    <label class="form-label" for="grading_name">Grading System Name</label>
                    <input type="text" class="form-control" id="grading_name" name="grading_name" required>
                </div>

                <div class="grades-container">
                    <div class="grades-header">
                        <h6>Regular Grades</h6>
                        <button type="button" class="btn-add-grade" onclick="addGradeRow()">
                            <i class="material-symbols-rounded">add</i>
                            Add Grade
                        </button>
                    </div>
                    
                    <div class="grades-table">
                        <div class="grades-table-header">
                            <div class="col-grade">Grade Value</div>
                            <div class="col-desc">Description</div>
                            <div class="col-range">Percentage Range</div>
                            <div class="col-action">Action</div>
                        </div>
                        <div class="grades-rows-container" id="gradesRows">
                            <!-- Empty state message will be added here -->
                        </div>
                    </div>
                </div>

                <div class="special-grades-section mt-4">
                    <h6>Special Grades</h6>
                    <div class="special-grades-grid">
                        <div class="special-grade-item">
                            <input type="checkbox" id="grade_drp" name="special_grades[DRP]" class="special-grade-check">
                            <label for="grade_drp">
                                <span class="special-grade-label">DRP</span>
                                <span class="special-grade-desc">Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="grade_od" name="special_grades[OD]" class="special-grade-check">
                            <label for="grade_od">
                                <span class="special-grade-label">OD</span>
                                <span class="special-grade-desc">Officially Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="grade_ud" name="special_grades[UD]" class="special-grade-check">
                            <label for="grade_ud">
                                <span class="special-grade-label">UD</span>
                                <span class="special-grade-desc">Unofficially Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="grade_na" name="special_grades[NA]" class="special-grade-check">
                            <label for="grade_na">
                                <span class="special-grade-label">*</span>
                                <span class="special-grade-desc">No Attendance</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-delete" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn-edit">Save Grading System</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form action="" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label" for="edit_grading_name">Grading System Name</label>
                    <input type="text" class="form-control" id="edit_grading_name" name="grading_name" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_grade_value">Grade Value</label>
                    <input type="number" step="0.01" class="form-control" id="edit_grade_value" name="grade_value" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_min_percentage">Minimum Percentage</label>
                    <input type="number" step="0.01" class="form-control" id="edit_min_percentage" name="min_percentage" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_max_percentage">Maximum Percentage</label>
                    <input type="number" step="0.01" class="form-control" id="edit_max_percentage" name="max_percentage" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-delete" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-edit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Delete Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <form action="" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <p>Are you sure you want to delete this grading system?</p>
                <p><strong>Grading System:</strong> <span id="delete_grading_name"></span></p>
                <p><strong>Grade Value:</strong> <span id="delete_grade"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-edit" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/side.js"></script>
<script>
function editGradingSystem(system) {
    document.getElementById('edit_id').value = system.id;
    document.getElementById('edit_grading_name').value = system.grading_name;
    document.getElementById('edit_grade_value').value = system.grade_value;
    document.getElementById('edit_min_percentage').value = system.min_percentage;
    document.getElementById('edit_max_percentage').value = system.max_percentage;
    openModal('editModal');
}

function deleteGradingSystem(id, grading_name, grade) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_grading_name').textContent = grading_name;
    document.getElementById('delete_grade').textContent = grade;
    openModal('deleteModal');
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modalId === 'addModal') {
        // Reset the form
        document.getElementById('addGradingForm').reset();
        // Clear all grade rows
        document.getElementById('gradesRows').innerHTML = '';
        gradeRowCount = 0;
    }
    modal.classList.add('show');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    if (modalId === 'addModal') {
        document.getElementById('addGradingForm').reset();
        document.getElementById('gradesRows').innerHTML = '';
        gradeRowCount = 0;
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
}

let gradeRowCount = 0;

function addGradeRow() {
    const template = `
        <div class="grade-row" data-index="${gradeRowCount}">
            <div class="col-grade">
                <input type="number" step="0.25" class="form-control" 
                       name="grades[${gradeRowCount}][value]" placeholder="e.g., 1.00" required
                       min="1" max="5" onchange="validateGradeValue(this)">
            </div>
            <div class="col-desc">
                <input type="text" class="form-control" 
                       name="grades[${gradeRowCount}][description]" 
                       placeholder="e.g., Excellent, Satisfactory, etc." required>
            </div>
            <div class="col-range">
                <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                    <input type="number" step="0.01" class="form-control" 
                           name="grades[${gradeRowCount}][min]" placeholder="e.g., 75" required
                           min="0" max="100" style="width: 45%">
                    <span>-</span>
                    <input type="number" step="0.01" class="form-control" 
                           name="grades[${gradeRowCount}][max]" placeholder="e.g., 100" required
                           min="0" max="100" style="width: 45%">
                </div>
            </div>
            <div class="col-action">
                <button type="button" class="btn-delete" onclick="removeGradeRow(this)">
                    <i class="material-symbols-rounded">delete</i>
                </button>
            </div>
        </div>
    `;
    
    const gradesContainer = document.getElementById('gradesRows');
    gradesContainer.insertAdjacentHTML('beforeend', template);
    gradeRowCount++;
    
    // Remove empty state message if it exists
    const emptyMessage = document.querySelector('.empty-grades-message');
    if (emptyMessage) {
        emptyMessage.remove();
    }
    
    // Scroll to the newly added row
    const newRow = gradesContainer.lastElementChild;
    newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function validateGradeValue(input) {
    const value = parseFloat(input.value);
    if (value < 1 || value > 5) {
        alert('Grade value must be between 1.00 and 5.00');
        input.value = '';
        return false;
    }
    
    // If it's a 5.0 (failing grade), auto-fill the description and range
    if (value === 5.0) {
        const row = input.closest('.grade-row');
        const descInput = row.querySelector('input[name*="[description]"]');
        const minInput = row.querySelector('input[name*="[min]"]');
        const maxInput = row.querySelector('input[name*="[max]"]');
        
        descInput.value = 'Failure';
        minInput.value = '0';
        maxInput.value = '74';
    }
    
    return true;
}

function removeGradeRow(button) {
    const row = button.closest('.grade-row');
    row.remove();
    
    // Show empty state message if no rows left
    const rows = document.querySelectorAll('.grade-row');
    if (rows.length === 0) {
        const template = `
            <div class="empty-grades-message">
                <p>No grades added yet. Click "Add Grade" to begin.</p>
            </div>
        `;
        document.getElementById('gradesRows').insertAdjacentHTML('beforeend', template);
    }
}

// Form validation
document.getElementById('addGradingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const rows = document.querySelectorAll('.grade-row');
    if (rows.length === 0) {
        alert('Please add at least one grade.');
        return;
    }

    // Validate percentage ranges don't overlap
    const grades = [];
    let isValid = true;
    
    rows.forEach(row => {
        const min = parseFloat(row.querySelector('input[name*="[min]"]').value);
        const max = parseFloat(row.querySelector('input[name*="[max]"]').value);
        const value = parseFloat(row.querySelector('input[name*="[value]"]').value);
        const desc = row.querySelector('input[name*="[description]"]').value;
        
        if (isNaN(min) || isNaN(max) || isNaN(value)) {
            alert('Please enter valid numbers for all grade values and percentages.');
            isValid = false;
            return;
        }
        
        // Special handling for failing grade (5.0)
        if (value === 5.0) {
            if (max !== 74 || min !== 0) {
                alert('For grade 5.0 (Failure), the range should be 0-74');
                isValid = false;
                return;
            }
        } else if (min >= max) {
            alert('Minimum percentage must be less than maximum percentage for each grade.');
            isValid = false;
            return;
        }
        
        grades.push({ min, max, value, desc });
    });
    
    if (!isValid) return;
    
    // Sort by min percentage in descending order (highest grade first)
    grades.sort((a, b) => b.min - a.min);
    
    // Check for gaps and overlaps
    for (let i = 0; i < grades.length - 1; i++) {
        const current = grades[i];
        const next = grades[i + 1];
        
        // Skip overlap check if next grade is 5.0 (failing grade)
        if (next.value === 5.0) continue;
        
        // Check for overlap
        if (current.min <= next.max) {
            alert(`Grades cannot have overlapping ranges. Please check the ranges between "${current.desc}" and "${next.desc}".`);
            return;
        }
        
        // Check for significant gaps (more than 1%)
        const gap = current.min - next.max;
        if (next.value !== 5.0 && gap > 1) {
            const proceed = confirm(
                `There is a gap of ${gap.toFixed(2)}% between grades:\n\n` +
                `${next.desc}: ${next.max}%\n` +
                `${current.desc}: ${current.min}%\n\n` +
                `Do you want to proceed anyway?`
            );
            if (!proceed) return;
        }
    }
    
    // Special grades handling
    const specialGrades = document.querySelectorAll('.special-grade-check:checked');
    let hasOD = false;
    let hasUD = false;
    
    specialGrades.forEach(grade => {
        if (grade.id === 'grade_od') hasOD = true;
        if (grade.id === 'grade_ud') hasUD = true;
    });
    
    // If UD is selected but OD isn't, show warning
    if (hasUD && !hasOD) {
        const proceed = confirm('You have selected Unofficially Dropped (UD) but not Officially Dropped (OD). Do you want to proceed?');
        if (!proceed) return;
    }
    
    // If validation passes, submit the form
    this.submit();
});

// Update the grades container to show empty state message when no grades
function updateEmptyState() {
    const gradesContainer = document.getElementById('gradesRows');
    if (gradesContainer.children.length === 0) {
        gradesContainer.innerHTML = `
            <div class="empty-grades-message">
                <p>No grades added yet. Click "Add Grade" to begin.</p>
            </div>
        `;
    }
}

// Call updateEmptyState when opening modal
document.addEventListener('DOMContentLoaded', function() {
    updateEmptyState();
});

function toggleAccordion(header) {
    const item = header.closest('.accordion-item');
    const wasActive = item.classList.contains('active');
    
    // Close all accordion items
    document.querySelectorAll('.accordion-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // If the clicked item wasn't active, open it
    if (!wasActive) {
        item.classList.add('active');
    }
}

function editUniversity(grading_name) {
    // Implement university edit functionality
    console.log('Edit university:', grading_name);
}

function deleteUniversity(grading_name) {
    if (confirm(`Are you sure you want to delete all grading systems for ${grading_name}?`)) {
        // Implement university deletion functionality
        console.log('Delete university:', grading_name);
    }
}

function editGrade(gradeId) {
    // Implement grade edit functionality
    console.log('Edit grade:', gradeId);
}

function deleteGrade(gradeId) {
    if (confirm('Are you sure you want to delete this grade?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${gradeId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html> 