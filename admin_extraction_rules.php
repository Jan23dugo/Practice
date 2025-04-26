<?php
session_start();
include('config/config.php');

// Check if user is logged in as admin
//if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    //header("Location: admin_login.php");
    //exit();
//}

// Fetch extraction rules from DB
$rules = [];
try {
    $query = "SELECT * FROM extraction_rules ORDER BY priority ASC, id DESC";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rules[] = $row;
        }
    }
} catch (Exception $e) {
    $rules = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extraction Rules Admin</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        .rules-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .rules-title {
            font-size: 32px;
            color: #75343A;
            font-weight: 700;
        }
        .add-rule-btn {
            background: #75343A;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .add-rule-btn:hover {
            background: #5c2930;
        }
        .rules-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }
        .rules-table th {
            background: #75343A;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
        }
        .rules-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eef0f3;
            color: #333;
            font-size: 14px;
        }
        .rules-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .rules-table tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s ease;
        }
        .action-btn {
            background: #8e68cc;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .action-btn.edit { background: #00b894; }
        .action-btn.delete { background: #d63031; }
        .action-btn:hover { opacity: 0.85; }
        /* Modal styles (reuse from create_exam.php) */
        .modal-overlay {
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
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .modal-title {
            margin: 0;
            font-size: 20px;
            color: #333;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #75343A;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>
    <div class="main">
        <div class="rules-header">
            <h1 class="rules-title">Extraction Rules</h1>
            <button class="add-rule-btn" id="openAddRuleModal">+ Add Rule</button>
        </div>
        <table class="rules-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Keyword/Pattern</th>
                    <th>Field</th>
                    <th>School</th>
                    <th>Priority</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rules)): ?>
                    <tr><td colspan="8" style="text-align:center; color:#888;">No extraction rules found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rules as $rule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rule['id']); ?></td>
                            <td><?php echo htmlspecialchars($rule['rule_type']); ?></td>
                            <td><?php echo htmlspecialchars($rule['keyword_or_pattern']); ?></td>
                            <td><?php echo htmlspecialchars($rule['field']); ?></td>
                            <td><?php echo htmlspecialchars($rule['school'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($rule['priority']); ?></td>
                            <td><?php echo $rule['active'] ? '<span style="color:green;">Yes</span>' : '<span style="color:#d63031;">No</span>'; ?></td>
                            <td>
                                <button class="action-btn edit" title="Edit" disabled>Edit</button>
                                <button class="action-btn delete" title="Delete" disabled>Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Add Rule Modal -->
    <div class="modal-overlay" id="addRuleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Extraction Rule</h3>
                <button class="close-modal" id="closeAddRuleModal">&times;</button>
            </div>
            <form class="modal-body">
                <div class="form-group">
                    <label for="rule_type">Rule Type</label>
                    <select id="rule_type" name="rule_type" required>
                        <option value="header_keyword">Header Keyword</option>
                        <option value="regex">Regex</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="keyword_or_pattern">Keyword or Pattern</label>
                    <input type="text" id="keyword_or_pattern" name="keyword_or_pattern" required>
                </div>
                <div class="form-group">
                    <label for="field">Field</label>
                    <select id="field" name="field" required>
                        <option value="subject_code">Subject Code</option>
                        <option value="subject_description">Subject Description</option>
                        <option value="units">Units</option>
                        <option value="grade">Grade</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="school">School (optional)</label>
                    <input type="text" id="school" name="school">
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <input type="number" id="priority" name="priority" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label for="active">Active</label>
                    <select id="active" name="active">
                        <option value="1" selected>Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="add-rule-btn" style="background:#6c757d;" id="cancelAddRule">Cancel</button>
                    <button type="submit" class="add-rule-btn">Add Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="assets/js/side.js"></script>
<script>
// Modal open/close logic
const addRuleModal = document.getElementById('addRuleModal');
document.getElementById('openAddRuleModal').onclick = function() {
    addRuleModal.style.display = 'flex';
};
document.getElementById('closeAddRuleModal').onclick = function() {
    addRuleModal.style.display = 'none';
};
document.getElementById('cancelAddRule').onclick = function() {
    addRuleModal.style.display = 'none';
};
// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target === addRuleModal) {
        addRuleModal.style.display = 'none';
    }
};
// Prevent form submit (no backend yet)
document.querySelector('#addRuleModal form').onsubmit = function(e) {
    e.preventDefault();
    alert('Add Rule functionality coming soon!');
    addRuleModal.style.display = 'none';
};
</script>
</body>
</html> 