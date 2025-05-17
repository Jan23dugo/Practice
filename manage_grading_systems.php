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
                $university_name = $_POST['university_name'];
                $university_code = $_POST['university_code'];
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
                                (university_name, university_code, grade_value, description, min_percentage, max_percentage, is_special_grade) 
                                VALUES (?, ?, ?, ?, ?, ?, 0)");
                            
                            if (!$stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }

                            $stmt->bind_param("ssssdd", 
                                $university_name, 
                                $university_code,
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
                                    (university_name, university_code, grade_value, description, is_special_grade) 
                                    VALUES (?, ?, ?, ?, 1)");
                                
                                if (!$stmt) {
                                    throw new Exception("Prepare failed: " . $conn->error);
                                }

                                $gradeValue = $specialGradesMap[$code]['value'];
                                $description = $specialGradesMap[$code]['desc'];
                                
                                $stmt->bind_param("ssss", $university_name, $university_code, $gradeValue, $description);
                                
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

            case 'edit_bulk':
                $university_name = $_POST['university_name'];
                $original_university_name = $_POST['edit_original_university_name'];
                $success = true;
                $conn->begin_transaction();

                try {
                    // Delete existing grades for this university
                    $stmt = $conn->prepare("DELETE FROM university_grading_systems WHERE university_name = ?");
                    $stmt->bind_param("s", $original_university_name);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting existing grades: " . $stmt->error);
                    }

                    // Add regular grades
                    if (isset($_POST['edit_grades']) && is_array($_POST['edit_grades'])) {
                        foreach ($_POST['edit_grades'] as $grade) {
                            if (!isset($grade['value'], $grade['description'], $grade['min'], $grade['max'])) {
                                throw new Exception("Invalid grade data provided");
                            }

                            $stmt = $conn->prepare("INSERT INTO university_grading_systems 
                                (university_name, university_code, grade_value, description, min_percentage, max_percentage, is_special_grade) 
                                VALUES (?, ?, ?, ?, ?, ?, 0)");
                            
                            if (!$stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }

                            $stmt->bind_param("ssssdd", 
                                $university_name,
                                $grade['code'],
                                $grade['value'],
                                $grade['description'],
                                $grade['min'],
                                $grade['max']
                            );
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Error adding grade: " . $stmt->error);
                            }
                        }
                    }

                    // Add special grades
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
                                    (university_name, university_code, grade_value, description, is_special_grade) 
                                    VALUES (?, ?, ?, ?, 1)");
                                
                                if (!$stmt) {
                                    throw new Exception("Prepare failed: " . $conn->error);
                                }

                                $gradeValue = $specialGradesMap[$code]['value'];
                                $description = $specialGradesMap[$code]['desc'];
                                
                                $stmt->bind_param("ssss", $university_name, $grade['code'], $gradeValue, $description);
                                
                                if (!$stmt->execute()) {
                                    throw new Exception("Error adding special grade: " . $stmt->error);
                                }
                            }
                        }
                    }

                    $conn->commit();
                    $_SESSION['success'] = "Grading system updated successfully.";
                } catch (Exception $e) {
                    $conn->rollback();
                    error_log("Error in transaction: " . $e->getMessage());
                    $_SESSION['error'] = $e->getMessage();
                }
                break;

            case 'delete':
                if (!isset($_POST['university_name'])) {
                    $_SESSION['error'] = "No university name provided for deletion.";
                    break;
                }

                $university_name = $_POST['university_name'];
                $stmt = $conn->prepare("DELETE FROM university_grading_systems WHERE university_name = ?");
                $stmt->bind_param("s", $university_name);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Grading system deleted successfully.";
                } else {
                    $_SESSION['error'] = "Error deleting grading system: " . $conn->error;
                }
                break;
        }
        
        header("Location: manage_grading_systems.php");
        exit();
    }
}

// Get all grading systems
$result = $conn->query("SELECT * FROM university_grading_systems ORDER BY university_name, is_special_grade, grade_value DESC");
$grading_systems = $result->fetch_all(MYSQLI_ASSOC);

// Group grading systems by university name
$systems = [];
foreach ($grading_systems as $system) {
    $systems[$system['university_name']][] = $system;
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

        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 800px;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #f0f0f0;
            background: linear-gradient(135deg, #75343A 0%, #8B4448 100%);
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            margin: 0;
            color: white;
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 20px;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
            background: #f8f9fa;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            background: white;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        /* Enhanced Form Elements in Modal */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
            font-size: 15px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus {
            border-color: #75343A;
            box-shadow: 0 0 0 4px rgba(117, 52, 58, 0.1);
            outline: none;
        }

        /* Enhanced Grade Input Container in Modal */
        .grades-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .grades-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grades-header h6 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .btn-add-grade {
            background: #75343A;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .btn-add-grade:hover {
            background: #8B4448;
            transform: translateY(-2px);
        }

        /* Enhanced Special Grades Section in Modal */
        .special-grades-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .special-grades-section h6 {
            margin: 0 0 20px;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .special-grades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .special-grade-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            transition: all 0.2s ease;
        }

        .special-grade-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .special-grade-check {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            accent-color: #75343A;
        }

        .special-grade-label {
            font-weight: 600;
            color: #75343A;
            margin-right: 8px;
        }

        .special-grade-desc {
            color: #666;
            font-size: 14px;
        }

        /* Modal Footer Buttons */
        .modal-footer button {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-footer .btn-edit {
            background: #75343A;
            color: white;
            border: none;
        }

        .modal-footer .btn-edit:hover {
            background: #8B4448;
            transform: translateY(-2px);
        }

        .modal-footer .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
        }

        .modal-footer .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Empty State in Modal */
        .empty-grades-message {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px dashed #e0e0e0;
        }

        .empty-grades-message i {
            font-size: 48px;
            color: #75343A;
            opacity: 0.5;
            margin-bottom: 15px;
        }

        /* Scrollbar Styling for Modal Body */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #75343A;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #8B4448;
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

        .add-new-row:hover {
            background: #ececec !important;
            color: #75343A;
            transition: background 0.2s;
        }
        .add-new-row span {
            pointer-events: none; /* So clicking anywhere triggers the td's onclick */
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="page-header">
            <h1 class="page-title">
                MANAGE GRADING SYSTEM
            </h1>
            <button class="add-button" onclick="openModal('addModal')">
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

        <div class="table-responsive">
            <table class="grading-table" style="width:100%">
                <thead>
                    <tr style="background:#8B4448;color:#fff;">
                        <th>University Name</th>
                        <th>University Code</th>
                        <th>Type</th>
                        <th>Number of Ranges</th>
                        <th>Last Modified</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                // Group by university name
                foreach ($systems as $university_name => $grades):
                    $num_ranges = count(array_filter($grades, function($g){ return !$g['is_special_grade']; }));
                    $last_modified = '';
                    $type = '';
                    $updated_ats = array_column($grades, 'updated_at');
                    if (!empty($updated_ats)) {
                        $last_modified = max($updated_ats);
                    }
                    // Optionally, infer type from university name
                    $type = (stripos($university_name, 'gpa') !== false) ? '4.0 Scale' : '1.0–5.0';
                    // Get university code from the first grade entry
                    $university_code = $grades[0]['university_code'] ?? '';
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($university_name); ?></td>
                        <td><?php echo htmlspecialchars($university_code); ?></td>
                        <td><?php echo htmlspecialchars($type); ?></td>
                        <td><?php echo $num_ranges; ?></td>
                        <td><?php echo $last_modified ? date('y/m/d', strtotime($last_modified)) : '-'; ?></td>
                        <td class="text-center">
                            <div class="action-buttons">
                                <button class="btn-edit" onclick="viewGradingSystem('<?php echo htmlspecialchars($university_name); ?>')">
                                    <i class="material-symbols-rounded">visibility</i>
                                </button>
                                <button class="btn-edit" onclick="openEditModal('<?php echo htmlspecialchars($university_name); ?>')">
                                    <i class="material-symbols-rounded">edit</i>
                                </button>
                                <button class="btn-delete" onclick="deleteGradingSystem('<?php echo htmlspecialchars($university_name); ?>')">
                                    <i class="material-symbols-rounded">delete</i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                </tbody>
            </table>
        </div>
        <!-- Modals will be updated next -->
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
                    <label class="form-label" for="university_name">University Name</label>
                    <input type="text" class="form-control" id="university_name" name="university_name" required
                           placeholder="Enter university name (e.g., University of the Philippines)">
                </div>

                <div class="form-group mb-4">
                    <label class="form-label" for="university_code">University Code</label>
                    <input type="text" class="form-control" id="university_code" name="university_code" required
                           placeholder="Enter university code (e.g., UP, DLSU, ADMU)"
                           pattern="[A-Za-z0-9]+" title="Please enter only letters and numbers">
                    <small class="form-text text-muted">Enter a unique code for the university (letters and numbers only)</small>
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
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">Edit Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form action="" method="POST" id="editGradingForm">
            <input type="hidden" name="action" value="edit_bulk">
            <input type="hidden" name="edit_original_university_name" id="edit_original_university_name">
            <div class="modal-body">
                <div class="form-group mb-4">
                    <label class="form-label" for="edit_university_name">University Name</label>
                    <input type="text" class="form-control" id="edit_university_name" name="university_name" required
                           placeholder="Enter university name (e.g., University of the Philippines)">
                </div>

                <div class="form-group mb-4">
                    <label class="form-label" for="edit_university_code">University Code</label>
                    <input type="text" class="form-control" id="edit_university_code" name="university_code" required
                           placeholder="Enter university code (e.g., UP, DLSU, ADMU)"
                           pattern="[A-Za-z0-9]+" title="Please enter only letters and numbers">
                    <small class="form-text text-muted">Enter a unique code for the university (letters and numbers only)</small>
                </div>

                <div class="grades-container">
                    <div class="grades-header">
                        <h6>Regular Grades</h6>
                        <button type="button" class="btn-add-grade" onclick="addEditGradeRow()">
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
                        <div class="grades-rows-container" id="editGradesRows">
                            <!-- Grade rows will be populated here -->
                        </div>
                    </div>
                </div>
                <div class="special-grades-section mt-4">
                    <h6>Special Grades</h6>
                    <div class="special-grades-grid">
                        <div class="special-grade-item">
                            <input type="checkbox" id="edit_grade_drp" name="special_grades[DRP]" class="special-grade-check">
                            <label for="edit_grade_drp">
                                <span class="special-grade-label">DRP</span>
                                <span class="special-grade-desc">Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="edit_grade_od" name="special_grades[OD]" class="special-grade-check">
                            <label for="edit_grade_od">
                                <span class="special-grade-label">OD</span>
                                <span class="special-grade-desc">Officially Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="edit_grade_ud" name="special_grades[UD]" class="special-grade-check">
                            <label for="edit_grade_ud">
                                <span class="special-grade-label">UD</span>
                                <span class="special-grade-desc">Unofficially Dropped</span>
                            </label>
                        </div>
                        <div class="special-grade-item">
                            <input type="checkbox" id="edit_grade_na" name="special_grades[NA]" class="special-grade-check">
                            <label for="edit_grade_na">
                                <span class="special-grade-label">*</span>
                                <span class="special-grade-desc">No Attendance</span>
                            </label>
                        </div>
                    </div>
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
                <input type="hidden" name="university_name" id="delete_university_name">
                <p>Are you sure you want to delete this grading system?</p>
                <p><strong>University Name:</strong> <span id="delete_university_name_display"></span></p>
                <p><strong>University Code:</strong> <span id="delete_university_code_display"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-edit" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn-delete">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div class="modal" id="viewModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">View Grading System</h5>
            <button type="button" class="btn-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group mb-3">
                <label class="form-label">University Name:</label>
                <div id="view_university_name" style="font-weight:bold;font-size:18px;"></div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">University Code:</label>
                <div id="view_university_code" style="font-weight:bold;font-size:18px;"></div>
            </div>
            <div class="form-group mb-3" style="display:flex;gap:30px;flex-wrap:wrap;">
                <div><span class="form-label">Type:</span> <span id="view_type"></span></div>
                <div><span class="form-label">Number of Ranges:</span> <span id="view_num_ranges"></span></div>
                <div><span class="form-label">Last Modified:</span> <span id="view_last_modified"></span></div>
            </div>
            <div class="grades-container mb-4">
                <h6>Regular Grades</h6>
                <div class="grades-table">
                    <div class="grades-table-header">
                        <div class="col-grade">Grade Value</div>
                        <div class="col-desc">Description</div>
                        <div class="col-range">Percentage Range</div>
                    </div>
                    <div class="grades-rows-container" id="viewGradesRows">
                        <!-- Grade rows will be populated here -->
                    </div>
                </div>
            </div>
            <div class="special-grades-section">
                <h6>Special Grades</h6>
                <div class="special-grades-grid" id="viewSpecialGrades">
                    <!-- Special grades will be populated here -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-edit" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<script src="assets/js/side.js"></script>
<script>
// Build a JS object of all grading systems and their grades for modal use
const gradingSystemsData = <?php echo json_encode($systems); ?>;

function editGradingSystem(system) {
    document.getElementById('edit_id').value = system.id;
    document.getElementById('edit_university_name').value = system.university_name;
    document.getElementById('edit_grade_value').value = system.grade_value;
    document.getElementById('edit_min_percentage').value = system.min_percentage;
    document.getElementById('edit_max_percentage').value = system.max_percentage;
    openModal('editModal');
}

function deleteGradingSystem(university_name) {
    document.getElementById('delete_university_name').value = university_name;
    document.getElementById('delete_university_name_display').textContent = university_name;
    
    // Get university code from the data
    const grades = gradingSystemsData[university_name] || [];
    const university_code = grades[0]?.university_code || '-';
    document.getElementById('delete_university_code_display').textContent = university_code;
    
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
                <input type="text" class="form-control" 
                       name="grades[${gradeRowCount}][value]" placeholder="e.g., 1.00 or A" required
                       pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                       oninput="validateGradeValue(this)">
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
    const value = input.value.trim();
    const numberPattern = /^(1(\.00|\.25|\.50|\.75)?|2(\.00|\.25|\.50|\.75)?|3(\.00|\.25|\.50|\.75)?|4(\.00|\.25|\.50|\.75)?|5(\.00|\.25|\.50|\.75)?)$/;
    const letterPattern = /^[A-Ea-e]$/;
    if (!numberPattern.test(value) && !letterPattern.test(value)) {
        input.setCustomValidity('Grade value must be 1.00-5.00 or A-E');
        input.reportValidity();
        return false;
    } else {
        input.setCustomValidity('');
    }
    // If it's a 5.0 (failing grade), auto-fill the description and range
    if (value === '5' || value === '5.00') {
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
        const value = row.querySelector('input[name*="[value]"]').value;
        const desc = row.querySelector('input[name*="[description]"]').value;
        
        if (isNaN(min) || isNaN(max)) {
            alert('Please enter valid numbers for all grade percentages.');
            isValid = false;
            return;
        }
        
        // Special handling for failing grade (5.0)
        if (value === '5' || value === '5.00') {
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
        if (next.value === '5' || next.value === '5.00') continue;
        
        // Check for overlap
        if (current.min <= next.max) {
            alert(`Grades cannot have overlapping ranges. Please check the ranges between "${current.desc}" and "${next.desc}".`);
            return;
        }
        
        // Check for significant gaps (more than 1%)
        const gap = current.min - next.max;
        if (gap > 1) {
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

function editUniversity(university_name) {
    // Implement university edit functionality
    console.log('Edit university:', university_name);
}

function deleteUniversity(university_name) {
    if (confirm(`Are you sure you want to delete all grading systems for ${university_name}?`)) {
        // Implement university deletion functionality
        console.log('Delete university:', university_name);
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

function openEditModal(university_name) {
    // Get all grades for this system
    const grades = gradingSystemsData[university_name] || [];
    // Separate regular and special grades
    const regularGrades = grades.filter(g => g.is_special_grade == 0);
    const specialGrades = grades.filter(g => g.is_special_grade == 1);

    // Set grading system name and code
    document.getElementById('edit_university_name').value = university_name;
    document.getElementById('edit_university_code').value = grades[0]?.university_code || '';
    document.getElementById('edit_original_university_name').value = university_name;

    // Clear and repopulate grade rows
    const editGradesRows = document.getElementById('editGradesRows');
    editGradesRows.innerHTML = '';
    let editGradeRowCount = 0;
    regularGrades.forEach(grade => {
        const template = `
            <div class="grade-row" data-index="${editGradeRowCount}">
                <div class="col-grade">
                    <input type="text" class="form-control" 
                        name="edit_grades[${editGradeRowCount}][value]" value="${grade.grade_value}" required
                        pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                        oninput="validateGradeValue(this)">
                </div>
                <div class="col-desc">
                    <input type="text" class="form-control" 
                        name="edit_grades[${editGradeRowCount}][description]" value="${grade.description}" required>
                </div>
                <div class="col-range">
                    <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                        <input type="number" step="0.01" class="form-control" 
                            name="edit_grades[${editGradeRowCount}][min]" value="${grade.min_percentage}" required
                            min="0" max="100" style="width: 45%">
                        <span>-</span>
                        <input type="number" step="0.01" class="form-control" 
                            name="edit_grades[${editGradeRowCount}][max]" value="${grade.max_percentage}" required
                            min="0" max="100" style="width: 45%">
                    </div>
                </div>
                <div class="col-action">
                    <button type="button" class="btn-delete" onclick="removeEditGradeRow(this)">
                        <i class="material-symbols-rounded">delete</i>
                    </button>
                </div>
            </div>
        `;
        editGradesRows.insertAdjacentHTML('beforeend', template);
        editGradeRowCount++;
    });
    // If no grades, show empty state
    if (editGradeRowCount === 0) {
        editGradesRows.innerHTML = `<div class='empty-grades-message'><p>No grades added yet. Click "Add Grade" to begin.</p></div>`;
    }
    window.editGradeRowCount = editGradeRowCount;

    // Set special grades checkboxes
    document.getElementById('edit_grade_drp').checked = specialGrades.some(g => g.grade_value === 'DRP');
    document.getElementById('edit_grade_od').checked = specialGrades.some(g => g.grade_value === 'OD');
    document.getElementById('edit_grade_ud').checked = specialGrades.some(g => g.grade_value === 'UD');
    document.getElementById('edit_grade_na').checked = specialGrades.some(g => g.grade_value === '*');

    // Show modal
    openModal('editModal');
}

function addEditGradeRow() {
    let editGradeRowCount = window.editGradeRowCount || 0;
    const template = `
        <div class="grade-row" data-index="${editGradeRowCount}">
            <div class="col-grade">
                <input type="text" class="form-control" 
                    name="edit_grades[${editGradeRowCount}][value]" placeholder="e.g., 1.00 or A" required
                    pattern="^([1-5](\\.00|\\.25|\\.50|\\.75)?|[A-Ea-e])$"
                    oninput="validateGradeValue(this)">
            </div>
            <div class="col-desc">
                <input type="text" class="form-control" 
                    name="edit_grades[${editGradeRowCount}][description]" 
                    placeholder="e.g., Excellent, Satisfactory, etc." required>
            </div>
            <div class="col-range">
                <div class="range-inputs" style="display: flex; gap: 5px; align-items: center;">
                    <input type="number" step="0.01" class="form-control" 
                        name="edit_grades[${editGradeRowCount}][min]" placeholder="e.g., 75" required
                        min="0" max="100" style="width: 45%">
                    <span>-</span>
                    <input type="number" step="0.01" class="form-control" 
                        name="edit_grades[${editGradeRowCount}][max]" placeholder="e.g., 100" required
                        min="0" max="100" style="width: 45%">
                </div>
            </div>
            <div class="col-action">
                <button type="button" class="btn-delete" onclick="removeEditGradeRow(this)">
                    <i class="material-symbols-rounded">delete</i>
                </button>
            </div>
        </div>
    `;
    const editGradesRows = document.getElementById('editGradesRows');
    // Remove empty state if present
    const emptyMessage = editGradesRows.querySelector('.empty-grades-message');
    if (emptyMessage) emptyMessage.remove();
    editGradesRows.insertAdjacentHTML('beforeend', template);
    window.editGradeRowCount = editGradeRowCount + 1;
}

function removeEditGradeRow(button) {
    const row = button.closest('.grade-row');
    row.remove();
    // Show empty state message if no rows left
    const rows = document.querySelectorAll('#editGradesRows .grade-row');
    if (rows.length === 0) {
        document.getElementById('editGradesRows').innerHTML = `<div class='empty-grades-message'><p>No grades added yet. Click \"Add Grade\" to begin.</p></div>`;
    }
}

function viewGradingSystem(university_name) {
    fetch('get_grading_system.php?name=' + encodeURIComponent(university_name))
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            // Separate regular and special grades
            const regularGrades = data.filter(g => !g.is_special_grade);
            const specialGrades = data.filter(g => g.is_special_grade);

            // Set main info
            document.getElementById('view_university_name').textContent = university_name;
            document.getElementById('view_university_code').textContent = data[0]?.university_code || '-';
            document.getElementById('view_type').textContent = (university_name.toLowerCase().includes('gpa')) ? '4.0 Scale' : '1.0–5.0';
            document.getElementById('view_num_ranges').textContent = regularGrades.length;
            document.getElementById('view_last_modified').textContent = data[0]?.updated_at
                ? new Date(data[0].updated_at).toLocaleDateString('en-GB', {year: '2-digit', month: '2-digit', day: '2-digit'}).replace(/\//g, '/')
                : '-';

            // Populate regular grades
            const viewGradesRows = document.getElementById('viewGradesRows');
            viewGradesRows.innerHTML = '';
            if (regularGrades.length === 0) {
                viewGradesRows.innerHTML = `<div class='empty-grades-message'><p>No regular grades.</p></div>`;
            } else {
                regularGrades.forEach(grade => {
                    const row = document.createElement('div');
                    row.className = 'grade-row';
                    row.innerHTML = `
                        <div class='col-grade'>${grade.grade_value}</div>
                        <div class='col-desc'>${grade.description}</div>
                        <div class='col-range'>${Number(grade.min_percentage).toFixed(2)}% - ${Number(grade.max_percentage).toFixed(2)}%</div>
                    `;
                    viewGradesRows.appendChild(row);
                });
            }
            // Populate special grades
            const viewSpecialGrades = document.getElementById('viewSpecialGrades');
            viewSpecialGrades.innerHTML = '';
            if (specialGrades.length === 0) {
                viewSpecialGrades.innerHTML = `<div class='empty-grades-message'><p>No special grades.</p></div>`;
            } else {
                specialGrades.forEach(grade => {
                    const item = document.createElement('div');
                    item.className = 'special-grade-item';
                    item.innerHTML = `
                        <span class='special-grade-label'>${grade.grade_value}</span>
                        <span class='special-grade-desc'>${grade.description}</span>
                    `;
                    viewSpecialGrades.appendChild(item);
                });
            }
            openModal('viewModal');
        })
        .catch(err => {
            alert('Failed to fetch grading system data.');
        });
}
</script>
</body>
</html> 