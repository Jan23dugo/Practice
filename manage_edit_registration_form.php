<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include('config/config.php');

$editRow = null;
$type = isset($_GET['type']) ? filter_var($_GET['type'], FILTER_SANITIZE_STRING) : null;
$action = isset($_GET['action']) ? filter_var($_GET['action'], FILTER_SANITIZE_STRING) : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error_message = '';
$success_message = '';

$tableMap = [
    'universities' => ['table' => 'universities', 'id' => 'university_id', 'code' => 'university_code', 'name' => 'university_name'],
    'university_programs' => ['table' => 'university_programs', 'id' => 'university_program_id', 'code' => 'program_code', 'name' => 'program_name'],
    'programs' => ['table' => 'programs', 'id' => 'program_id', 'code' => 'program_code', 'name' => 'program_name']
];

if ($type && isset($tableMap[$type])) {
    $table = $tableMap[$type]['table'];
    $idKey = $tableMap[$type]['id'];
    $codeKey = $tableMap[$type]['code'];
    $nameKey = $tableMap[$type]['name'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize inputs
        $code = filter_var($_POST['code'], FILTER_SANITIZE_STRING);
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);

        if (empty($code) || empty($name)) {
            $error_message = "Both code and name are required.";
        } else {
            try {
                if ($action === 'edit' && $id) {
                    // Use prepared statement to prevent SQL injection
                    $stmt = $conn->prepare("UPDATE `$table` SET `$codeKey`=?, `$nameKey`=? WHERE $idKey=?");
                    $stmt->bind_param("ssi", $code, $name, $id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Record updated successfully!";
                        // Redirect after a delay using JavaScript
                        echo "<script>
                            setTimeout(function() {
                                window.location.href = 'manage_edit_registration_form.php';
                            }, 1500);
                        </script>";
                    } else {
                        $error_message = "Error updating record.";
                    }
                } else {
                    // Check if code already exists
                    $check_stmt = $conn->prepare("SELECT * FROM `$table` WHERE `$codeKey` = ?");
                    $check_stmt->bind_param("s", $code);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error_message = "Code already exists. Please use a different code.";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO `$table` (`$codeKey`, `$nameKey`) VALUES (?, ?)");
                        $stmt->bind_param("ss", $code, $name);
                        
                        if ($stmt->execute()) {
                            $success_message = "Record added successfully!";
                            // Redirect after a delay using JavaScript
                            echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'manage_edit_registration_form.php';
                                }, 1500);
                            </script>";
                        } else {
                            $error_message = "Error adding record.";
                        }
                    }
                }
            } catch (Exception $e) {
                // Log the error for administrators
                error_log("Database error: " . $e->getMessage());
                $error_message = "An error occurred while processing your request. Please try again later.";
            }
        }
    }

    if ($action === 'delete' && $id) {
        try {
            // Check if the record exists and can be deleted (no dependencies)
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE $idKey = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Record deleted successfully!";
                // Redirect after a delay using JavaScript
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'manage_edit_registration_form.php';
                    }, 1500);
                </script>";
            } else {
                $error_message = "Error deleting record. It may be referenced by other records.";
            }
        } catch (Exception $e) {
            // Log the error for administrators
            error_log("Database error: " . $e->getMessage());
            $error_message = "An error occurred while deleting the record. It might be referenced by other records.";
        }
    }

    if ($action === 'edit' && $id) {
        try {
            $stmt = $conn->prepare("SELECT * FROM `$table` WHERE $idKey = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $editRow = $result->fetch_assoc();
            
            if (!$editRow) {
                $error_message = "Record not found.";
            }
        } catch (Exception $e) {
            // Log the error for administrators
            error_log("Database error: " . $e->getMessage());
            $error_message = "An error occurred while retrieving the record.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exam Registration Form</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .edit-title {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }

        .edit-date {
            font-size: 18px;
            color: #555;
            font-weight: 500;
        }

        /* Notification Messages */
        .notification {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notification.error {
            background-color: #ffebee;
            color: #d32f2f;
            border-left: 4px solid #d32f2f;
        }

        .notification.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .notification-close {
            cursor: pointer;
            font-weight: bold;
            padding: 0 5px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 10px 20px;
            background: #f3f3f3;
            border: none;
            cursor: pointer;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            background: #e0e0e0;
        }

        .tab-btn.active {
            background: #75343A;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
            gap: 20px;
        }

        .search-container {
            flex: 1;
            max-width: 400px;
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #75343A;
        }

        .search-box input {
            width: 100%;
            padding: 10px 20px 10px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .search-box input:focus {
            border-color: #75343A;
            box-shadow: 0 0 0 4px rgba(117, 52, 58, 0.1);
            outline: none;
        }

        .search-box input::placeholder {
            color: #999;
        }

        .add-btn {
            padding: 8px 15px;
            background-color: #75343A;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background-color: #622c31;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .edit-btn,
        .delete-btn {
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
        }

        .edit-btn {
            background-color: #2e7d32;
        }

        .edit-btn:hover {
            background-color: #1b5e20;
        }

        .delete-btn {
            background-color: #75343A;
        }

        .delete-btn:hover {
            background-color: #622c31;
        }

        /* Table Styling */
        .styled-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }

        /* Table Header */
        th {
            background: #75343A;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* Table Rows */
        td {
            padding: 15px;
            border-bottom: 1px solid #eef0f3;
            color: #333;
            font-size: 14px;
            vertical-align: middle;
        }

        /* Alternate Row Color */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Hover Effect */
        tbody tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s ease;
        }

        /* Empty Table Message */
        .empty-table-message {
            text-align: center;
            padding: 40px;
            color: #777;
            font-style: italic;
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
            max-width: 500px;
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
            padding: 20px 25px;
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
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close {
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
            padding: 0;
        }

        .close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 25px;
            background: #f8f9fa;
        }

        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: white;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        /* Enhanced Form Elements in Modal */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus {
            border-color: #75343A;
            box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
            outline: none;
        }

        /* Modal Footer Buttons */
        .modal-footer button {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .modal-footer .btn-primary {
            background: #75343A;
            color: white;
        }

        .modal-footer .btn-primary:hover {
            background: #8B4448;
            transform: translateY(-1px);
        }

        .modal-footer .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }

        .modal-footer .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-1px);
        }

        /* Scrollbar Styling for Modal Body */
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #75343A;
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #8B4448;
        }

        /* Delete Modal Specific Styles */
        #deleteModal .modal-body {
            text-align: center;
            padding: 30px 25px;
        }

        #deleteModal .modal-body p {
            margin: 0;
            color: #333;
            font-size: 15px;
            line-height: 1.5;
        }

        #deleteModal .btn-primary {
            background: #dc3545;
        }

        #deleteModal .btn-primary:hover {
            background: #c82333;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .edit-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .table-actions {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .search-container {
                width: 100%;
            }
            
            .add-btn {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <div class="main">
            <div class="edit-header">
                <h1 class="edit-title">Manage Exam Registration Form</h1>
            </div>

            <?php if (!empty($error_message)): ?>
            <div class="notification error" id="error-notification">
                <span><?php echo $error_message; ?></span>
                <span class="notification-close" onclick="this.parentElement.style.display='none'">×</span>
            </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
            <div class="notification success" id="success-notification">
                <span><?php echo $success_message; ?></span>
                <span class="notification-close" onclick="this.parentElement.style.display='none'">×</span>
            </div>
            <?php endif; ?>

            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="tab1">Previous University Programs</button>
                    <button class="tab-btn" data-tab="tab2">Previous Universities</button>
                    <button class="tab-btn" data-tab="tab3">Applied Programs</button>
                </div>

                <!-- Tab 1: University Programs -->
                <div id="tab1" class="tab-content active">
                    <div class="table-actions">
                        <div class="search-container">
                            <div class="search-box">
                                <i class="material-symbols-rounded">search</i>
                                <input type="text" id="search-programs" placeholder="Search programs..." onkeyup="searchTable(this, 'programs-table')">
                            </div>
                        </div>
                        <a href="javascript:void(0);" onclick="openAddModal('university_programs')" class="add-btn">
                            <span class="material-symbols-rounded">add</span>Add Program
                        </a>
                    </div>
                    <table class="styled-table" id="programs-table">
                        <thead>
                            <tr>
                                <th width="20%">Program Code</th>
                                <th>Program Name</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query1 = "SELECT * FROM university_programs ORDER BY program_code ASC";
                            $result1 = mysqli_query($conn, $query1);
                            if (mysqli_num_rows($result1) > 0) {
                                while ($row = mysqli_fetch_assoc($result1)) {
                                    echo "<tr>
                                        <td>{$row['program_code']}</td>
                                        <td>{$row['program_name']}</td>
                                        <td class='action-buttons'>
                                            <a href='javascript:void(0);' onclick=\"openEditModal('university_programs', {$row['university_program_id']}, '{$row['program_code']}', '{$row['program_name']}')\" class='edit-btn'>
                                                <span class='material-symbols-rounded'>edit</span>Edit
                                            </a>
                                            <a href='javascript:void(0);' onclick=\"openDeleteModal('university_programs', {$row['university_program_id']})\" class='delete-btn'>
                                                <span class='material-symbols-rounded'>delete</span>Delete
                                            </a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='empty-table-message'>No university programs found. Click 'Add Program' to create one.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 2: Universities -->
                <div id="tab2" class="tab-content">
                    <div class="table-actions">
                        <div class="search-container">
                            <div class="search-box">
                                <i class="material-symbols-rounded">search</i>
                                <input type="text" id="search-universities" placeholder="Search universities..." onkeyup="searchTable(this, 'universities-table')">
                            </div>
                        </div>
                        <a href="javascript:void(0);" onclick="openAddModal('universities')" class="add-btn">
                            <span class="material-symbols-rounded">add</span>Add University
                        </a>
                    </div>
                    <table class="styled-table" id="universities-table">
                        <thead>
                            <tr>
                                <th width="20%">University Code</th>
                                <th>University Name</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query2 = "SELECT * FROM universities ORDER BY university_code ASC";
                            $result2 = mysqli_query($conn, $query2);
                            if (mysqli_num_rows($result2) > 0) {
                                while ($row = mysqli_fetch_assoc($result2)) {
                                    echo "<tr>
                                        <td>{$row['university_code']}</td>
                                        <td>{$row['university_name']}</td>
                                        <td class='action-buttons'>
                                            <a href='javascript:void(0);' onclick=\"openEditModal('universities', {$row['university_id']}, '{$row['university_code']}', '{$row['university_name']}')\" class='edit-btn'>
                                                <span class='material-symbols-rounded'>edit</span>Edit
                                            </a>
                                            <a href='javascript:void(0);' onclick=\"openDeleteModal('universities', {$row['university_id']})\" class='delete-btn'>
                                                <span class='material-symbols-rounded'>delete</span>Delete
                                            </a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='empty-table-message'>No universities found. Click 'Add University' to create one.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 3: Applied Programs -->
                <div id="tab3" class="tab-content">
                    <div class="table-actions">
                        <div class="search-container">
                            <div class="search-box">
                                <i class="material-symbols-rounded">search</i>
                                <input type="text" id="search-applied" placeholder="Search applied programs..." onkeyup="searchTable(this, 'applied-table')">
                            </div>
                        </div>
                        <a href="javascript:void(0);" onclick="openAddModal('programs')" class="add-btn">
                            <span class="material-symbols-rounded">add</span>Add Program
                        </a>
                    </div>
                    <table class="styled-table" id="applied-table">
                        <thead>
                            <tr>
                                <th width="20%">Program Code</th>
                                <th>Program Name</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query3 = "SELECT * FROM programs ORDER BY program_code ASC";
                            $result3 = mysqli_query($conn, $query3);
                            if (mysqli_num_rows($result3) > 0) {
                                while ($row = mysqli_fetch_assoc($result3)) {
                                    echo "<tr>
                                        <td>{$row['program_code']}</td>
                                        <td>{$row['program_name']}</td>
                                        <td class='action-buttons'>
                                            <a href='javascript:void(0);' onclick=\"openEditModal('programs', {$row['program_id']}, '{$row['program_code']}', '{$row['program_name']}')\" class='edit-btn'>
                                                <span class='material-symbols-rounded'>edit</span>Edit
                                            </a>
                                            <a href='javascript:void(0);' onclick=\"openDeleteModal('programs', {$row['program_id']})\" class='delete-btn'>
                                                <span class='material-symbols-rounded'>delete</span>Delete
                                            </a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='empty-table-message'>No applied programs found. Click 'Add Program' to create one.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
                <!-- Add/Edit Modal -->
                <div id="formModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="modalTitle">Add Item</h2>
                        <button type="button" class="close" onclick="closeModal('formModal')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="itemForm" method="POST" action="">
                            <div class="form-group">
                                <label for="codeInput" class="form-label" id="codeLabel">Code:</label>
                                <input type="text" id="codeInput" name="code" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="nameInput" class="form-label" id="nameLabel">Name:</label>
                                <input type="text" id="nameInput" name="name" class="form-control" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeModal('formModal')">Cancel</button>
                        <button type="submit" form="itemForm" class="btn-primary" id="submitBtn">Save</button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Confirm Deletion</h2>
                        <button type="button" class="close" onclick="closeModal('deleteModal')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                        <button type="button" class="btn-primary" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
    <script src="assets/js/side.js"></script>
    <script>
        // Tab handling
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                tabButtons.forEach(b => b.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));

                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });

        // Search functionality
        function searchTable(input, tableId) {
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? "" : "none";
            });

            // Check if there are any visible rows after filtering
            const visibleRows = Array.from(rows).filter(row => row.style.display !== "none");
            const emptyMessage = table.querySelector(".empty-search-message");
            
            if (visibleRows.length === 0 && !emptyMessage) {
                const tbody = table.querySelector("tbody");
                const tr = document.createElement("tr");
                tr.className = "empty-search-message";
                tr.innerHTML = `<td colspan="3" class="empty-table-message">No matching records found.</td>`;
                tbody.appendChild(tr);
            } else if (visibleRows.length > 0 && emptyMessage) {
                emptyMessage.remove();
            }
        }

        // Enhanced Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
            
            // Add keyboard event listener for Escape key
            document.addEventListener('keydown', handleEscapeKey);
            
            // Add click outside listener
            modal.addEventListener('click', handleOutsideClick);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
            
            // Remove event listeners
            document.removeEventListener('keydown', handleEscapeKey);
            modal.removeEventListener('click', handleOutsideClick);
            
            // Reset form if exists
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                clearFormErrors(form);
            }
        }

        function handleEscapeKey(e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
        }

        function handleOutsideClick(e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target.id);
            }
        }

        function clearFormErrors(form) {
            const inputs = form.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.classList.remove('error');
            });
            const errorMessages = form.querySelectorAll('.error-message');
            errorMessages.forEach(message => {
                message.style.display = 'none';
            });
        }

        function showFormError(input, message) {
            input.classList.add('error');
            const errorDiv = input.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('error-message')) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }

        function setLoading(button, isLoading) {
            if (isLoading) {
                button.classList.add('loading');
                button.disabled = true;
            } else {
                button.classList.remove('loading');
                button.disabled = false;
            }
        }

        function openAddModal(type) {
            const modal = document.getElementById('formModal');
            const form = document.getElementById('itemForm');
            const title = document.getElementById('modalTitle');
            const codeLabel = document.getElementById('codeLabel');
            const nameLabel = document.getElementById('nameLabel');
            const submitBtn = document.getElementById('submitBtn');

            // Set up modal based on type
            if (type === 'universities') {
                title.textContent = 'Add University';
                codeLabel.textContent = 'University Code:';
                nameLabel.textContent = 'University Name:';
            } else if (type === 'university_programs') {
                title.textContent = 'Add University Program';
                codeLabel.textContent = 'Program Code:';
                nameLabel.textContent = 'Program Name:';
            } else if (type === 'programs') {
                title.textContent = 'Add Applied Program';
                codeLabel.textContent = 'Program Code:';
                nameLabel.textContent = 'Program Name:';
            }
            
            // Reset form fields
            form.reset();
            
            // Set form action for adding
            form.action = `?action=add&type=${type}`;
            
            // Set button text
            submitBtn.textContent = 'Add';
            
            // Show modal
            openModal('formModal');
        }
        
        function openEditModal(type, id, code, name) {
            const modal = document.getElementById('formModal');
            const form = document.getElementById('itemForm');
            const title = document.getElementById('modalTitle');
            const codeInput = document.getElementById('codeInput');
            const nameInput = document.getElementById('nameInput');
            const codeLabel = document.getElementById('codeLabel');
            const nameLabel = document.getElementById('nameLabel');
            const submitBtn = document.getElementById('submitBtn');
            
            // Set up modal based on type
            if (type === 'universities') {
                title.textContent = 'Edit University';
                codeLabel.textContent = 'University Code:';
                nameLabel.textContent = 'University Name:';
            } else if (type === 'university_programs') {
                title.textContent = 'Edit University Program';
                codeLabel.textContent = 'Program Code:';
                nameLabel.textContent = 'Program Name:';
            } else if (type === 'programs') {
                title.textContent = 'Edit Applied Program';
                codeLabel.textContent = 'Program Code:';
                nameLabel.textContent = 'Program Name:';
            }
            
            // Set form values
            codeInput.value = code;
            nameInput.value = name;
            
            // Set form action for editing
            form.action = `?action=edit&type=${type}&id=${id}`;
            
            // Set button text
            submitBtn.textContent = 'Save Changes';
            
            // Show modal
            openModal('formModal');
        }
        
        function openDeleteModal(type, id) {
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Set up delete button action
            confirmBtn.onclick = function() {
                window.location.href = `?action=delete&type=${type}&id=${id}`;
            };
            
            // Show modal
            openModal('deleteModal');
        }

        // Form validation
        document.getElementById('itemForm').addEventListener('submit', function(e) {
            const codeInput = document.getElementById('codeInput');
            const nameInput = document.getElementById('nameInput');
            const submitBtn = document.getElementById('submitBtn');
            let isValid = true;

            // Clear previous errors
            clearFormErrors(this);

            // Validate code
            if (!codeInput.value.trim()) {
                showFormError(codeInput, 'This field is required');
                isValid = false;
            }

            // Validate name
            if (!nameInput.value.trim()) {
                showFormError(nameInput, 'This field is required');
                isValid = false;
            }

            if (isValid) {
                setLoading(submitBtn, true);
            } else {
                e.preventDefault();
            }
        });
    </script>
    </body>
    </html>