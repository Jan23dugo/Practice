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
        }

        .search-container {
            display: flex;
            align-items: center;
            width: 250px;
            position: relative;
        }

        .search-input {
            padding: 8px 30px 8px 12px;
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #75343A;
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.2);
        }

        .search-icon {
            position: absolute;
            right: 10px;
            color: #75343A;
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

        /* Modal Background Overlay */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: block;
            opacity: 1;
        }

        /* Modal Content Container */
        .modal-content {
            position: relative;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin: 70px auto;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 20px;
            color: #75343A;
            margin: 0;
        }

        .close {
            color: #75343A;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .close:hover {
            color: #622c31;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #75343A;
            box-shadow: 0 0 0 2px rgba(117, 52, 58, 0.2);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }

        .button {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #75343A;
            color: white;
        }

        .btn-primary:hover {
            background-color: #622c31;
        }

        .btn-secondary {
            background-color: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #c0c0c0;
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
                            <input type="text" class="search-input" id="search-programs" placeholder="Search programs..." onkeyup="searchTable(this, 'programs-table')">
                            <span class="material-symbols-rounded search-icon">search</span>
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
                            <input type="text" class="search-input" id="search-universities" placeholder="Search universities..." onkeyup="searchTable(this, 'universities-table')">
                            <span class="material-symbols-rounded search-icon">search</span>
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
                            <input type="text" class="search-input" id="search-applied" placeholder="Search applied programs..." onkeyup="searchTable(this, 'applied-table')">
                            <span class="material-symbols-rounded search-icon">search</span>
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
                        <span class="close" onclick="closeModal('formModal')">&times;</span>
                    </div>
                    <form id="itemForm" method="POST" action="">
                        <div class="form-group">
                            <label for="codeInput" class="form-label" id="codeLabel">Code:</label>
                            <input type="text" id="codeInput" name="code" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="nameInput" class="form-label" id="nameLabel">Name:</label>
                            <input type="text" id="nameInput" name="name" class="form-control" required>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="button btn-secondary" onclick="closeModal('formModal')">Cancel</button>
                            <button type="submit" class="button btn-primary" id="submitBtn">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Confirm Deletion</h2>
                        <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                    </div>
                    <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                    <div class="form-actions">
                        <button class="button btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                        <button class="button btn-primary" id="confirmDeleteBtn">Delete</button>
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

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
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
    </script>
    </body>
    </html>