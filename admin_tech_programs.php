<?php
// Include admin session management
require_once 'config/admin_session.php';

// Check admin session and handle timeout
checkAdminSession();

// Include database connection
include('config/config.php');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $program_name = $_POST['program_name'];
                $program_code = $_POST['program_code'];
                $stmt = $conn->prepare("INSERT INTO tech_programs (program_name, program_code) VALUES (?, ?)");
                $stmt->bind_param("ss", $program_name, $program_code);
                $stmt->execute();
                break;

            case 'update':
                $id = $_POST['id'];
                $program_name = $_POST['program_name'];
                $program_code = $_POST['program_code'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $stmt = $conn->prepare("UPDATE tech_programs SET program_name = ?, program_code = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("ssii", $program_name, $program_code, $is_active, $id);
                $stmt->execute();
                break;

            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM tech_programs WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;
        }
    }
}

// Fetch all tech programs
$query = "SELECT * FROM tech_programs ORDER BY program_name";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tech Programs - CCIS Qualifying Exam System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        .tech-programs-container {
            padding: 20px;
        }

        .tech-programs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .tech-programs-title {
            font-size: 24px;
            color: #75343A;
            font-weight: 700;
        }

        .add-program-btn {
            background-color: #75343A;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .add-program-btn:hover {
            background-color: #5a2930;
        }

        .tech-programs-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .tech-programs-table th,
        .tech-programs-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .tech-programs-table th {
            background-color: #75343A;
            color: white;
            font-weight: 500;
        }

        .tech-programs-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }

        .edit-btn {
            background-color: #17a2b8;
            color: white;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 20px;
            color: #75343A;
            font-weight: 600;
        }

        .close-btn {
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .submit-btn, .cancel-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .submit-btn {
            background-color: #75343A;
            color: white;
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="tech-programs-container">
            <div class="tech-programs-header">
                <h1 class="tech-programs-title">Manage Tech Programs</h1>
                <button class="add-program-btn" onclick="openAddModal()">
                    <span class="material-symbols-rounded">add</span>
                    Add New Program
                </button>
            </div>

            <table class="tech-programs-table">
                <thead>
                    <tr>
                        <th>Program Name</th>
                        <th>Program Code</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['program_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['program_code']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button class="edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                Edit
                            </button>
                            <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Program Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add New Tech Program</h2>
            <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="program_name">Program Name</label>
                <input type="text" id="program_name" name="program_name" required>
            </div>
            <div class="form-group">
                <label for="program_code">Program Code</label>
                <input type="text" id="program_code" name="program_code" required>
            </div>
            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="submit-btn">Add Program</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Program Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Tech Program</h2>
            <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label for="edit_program_name">Program Name</label>
                <input type="text" id="edit_program_name" name="program_name" required>
            </div>
            <div class="form-group">
                <label for="edit_program_code">Program Code</label>
                <input type="text" id="edit_program_code" name="program_code" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" id="edit_is_active">
                    Active
                </label>
            </div>
            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="submit-btn">Update Program</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function openEditModal(program) {
    document.getElementById('edit_id').value = program.id;
    document.getElementById('edit_program_name').value = program.program_name;
    document.getElementById('edit_program_code').value = program.program_code;
    document.getElementById('edit_is_active').checked = program.is_active == 1;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this program?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>

<script src="assets/js/side.js"></script>
</body>
</html> 