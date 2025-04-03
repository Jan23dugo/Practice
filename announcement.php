<?php
    session_start(); // Start session if needed

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Not logged in as admin, redirect to admin login page
    header("Location: admin_login.php");
    exit();
}

// Include database connection
include('config/config.php');

// Initialize variables for form handling
$announcement_title = '';
$announcement_content = '';
$announcement_status = 'active';
$form_mode = 'add';
$editing_id = 0;

// Process form submission for adding/editing announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Sanitize inputs
    $announcement_title = trim($_POST['title']);
    $announcement_content = trim($_POST['content']);
    $announcement_status = isset($_POST['status']) ? $_POST['status'] : 'active';
    
    // Validation
    $errors = [];
    
    if (empty($announcement_title)) {
        $errors[] = "Announcement title is required";
    } elseif (strlen($announcement_title) > 255) {
        $errors[] = "Title cannot exceed 255 characters";
    }
    
    if (empty($announcement_content)) {
        $errors[] = "Announcement content is required";
    }
    
    // If no errors, process the submission
    if (empty($errors)) {
        if ($_POST['action'] === 'add') {
            // Insert new announcement
            $query = "INSERT INTO announcements (title, content, status) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $announcement_title, $announcement_content, $announcement_status);
            
            if ($stmt->execute()) {
                $_SESSION['announcement_message'] = "Announcement added successfully";
                $_SESSION['message_type'] = "success";
                
                // Reset form fields after successful submission
                $announcement_title = '';
                $announcement_content = '';
                $announcement_status = 'active';
            } else {
                $_SESSION['announcement_message'] = "Error adding announcement: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            // Update existing announcement
            $id = (int)$_POST['id'];
            $query = "UPDATE announcements SET title = ?, content = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $announcement_title, $announcement_content, $announcement_status, $id);
            
            if ($stmt->execute()) {
                $_SESSION['announcement_message'] = "Announcement updated successfully";
                $_SESSION['message_type'] = "success";
                
                // Reset form fields and mode
                $announcement_title = '';
                $announcement_content = '';
                $announcement_status = 'active';
                $form_mode = 'add';
            } else {
                $_SESSION['announcement_message'] = "Error updating announcement: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
        }
        
        // Redirect to refresh the page and avoid form resubmission
        header("Location: announcement.php");
        exit();
    }
}

// Handle edit request from GET parameters
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM announcements WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $announcement_title = $row['title'];
        $announcement_content = $row['content'];
        $announcement_status = $row['status'];
        $form_mode = 'edit';
        $editing_id = $id;
    }
}

// Handle delete request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "DELETE FROM announcements WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['announcement_message'] = "Announcement deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['announcement_message'] = "Error deleting announcement: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect to refresh the page
    header("Location: announcement.php");
    exit();
}

// Fetch existing announcements for display
$query = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($query);
$announcements = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Handle display message from session
$display_message = '';
$message_type = '';

if (isset($_SESSION['announcement_message'])) {
    $display_message = $_SESSION['announcement_message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    
    // Clear the message after displaying
    unset($_SESSION['announcement_message']);
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - CCIS Qualifying Exam System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Linking Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* Announcements Page Styles */
        .announcements-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .announcements-title {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        
        .page-date {
            font-size: 18px;
            color: #555;
            font-weight: 500;
        }
        
        .page-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .page-layout {
                grid-template-columns: 1fr;
            }
        }
        
        /* Lists and Cards */
        .announcements-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .list-title {
            font-size: 20px;
            color: #333;
            font-weight: 600;
        }
        
        .announcement-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #75343A;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .announcement-title {
            font-size: 18px;
            color: #333;
            font-weight: 600;
            margin: 0;
        }
        
        .announcement-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background-color: white;
            color: #555;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .edit-btn:hover {
            background-color: #e3f2fd;
            color: #2196F3;
        }
        
        .delete-btn:hover {
            background-color: #ffebee;
            color: #F44336;
        }
        
        .announcement-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .announcement-date {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .announcement-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #4CAF50;
        }
        
        .status-inactive {
            background-color: #f8f9fa;
            color: #7b7b7b;
        }
        
        .announcement-content {
            font-size: 14px;
            color: #333;
            line-height: 1.6;
            white-space: pre-line;
        }
        
        /* Form Styles */
        .announcement-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .form-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .form-title {
            font-size: 20px;
            color: #333;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #75343A;
            outline: none;
            box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .form-check-input {
            margin-right: 10px;
        }
        
        .form-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: #75343A;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5a2930;
        }
        
        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .alert-info {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }
        
        /* Empty State */
        .empty-state {
            padding: 30px;
            text-align: center;
            color: #666;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .empty-icon {
            font-size: 48px;
            color: #aaa;
            margin-bottom: 10px;
        }
        
        .empty-text {
            font-size: 16px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="announcements-header">
        <h1 class="announcements-title">Announcements</h1>
        <div class="page-date">
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>
    
    <?php if (!empty($display_message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <span class="material-symbols-rounded">
                <?php echo $message_type === 'success' ? 'check_circle' : ($message_type === 'error' ? 'error' : 'info'); ?>
            </span>
            <?php echo $display_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span class="material-symbols-rounded">error</span>
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="page-layout">
        <!-- Announcements List -->
        <div class="announcements-list">
            <div class="list-header">
                <h2 class="list-title">All Announcements</h2>
            </div>
            
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <span class="material-symbols-rounded">campaign</span>
                    </div>
                    <div class="empty-text">No announcements have been created yet.</div>
                    <p>Create your first announcement using the form.</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <div class="announcement-actions">
                                <a href="announcement.php?action=edit&id=<?php echo $announcement['id']; ?>" class="action-btn edit-btn" title="Edit">
                                    <span class="material-symbols-rounded">edit</span>
                                </a>
                                <button class="action-btn delete-btn" title="Delete" 
                                     onclick="confirmDelete(<?php echo $announcement['id']; ?>, '<?php echo addslashes(htmlspecialchars($announcement['title'])); ?>')">
                                    <span class="material-symbols-rounded">delete</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="announcement-meta">
                            <div class="announcement-date">
                                <span class="material-symbols-rounded">calendar_today</span>
                                <?php echo date('F j, Y', strtotime($announcement['created_at'])); ?>
                                <?php if ($announcement['updated_at'] !== $announcement['created_at']): ?>
                                    (Updated: <?php echo date('F j, Y', strtotime($announcement['updated_at'])); ?>)
                                <?php endif; ?>
                            </div>
                            <span class="announcement-status status-<?php echo $announcement['status']; ?>">
                                <?php echo ucfirst($announcement['status']); ?>
                            </span>
                        </div>
                        
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Announcement Form -->
        <div class="announcement-form">
            <div class="form-header">
                <h2 class="form-title">
                    <?php echo $form_mode === 'edit' ? 'Edit Announcement' : 'Create New Announcement'; ?>
                </h2>
            </div>
            
            <form action="announcement.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $form_mode; ?>">
                <?php if ($form_mode === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $editing_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title" class="form-label">Announcement Title</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($announcement_title); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="content" class="form-label">Announcement Content</label>
                    <textarea id="content" name="content" class="form-control form-textarea" required><?php echo htmlspecialchars($announcement_content); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <div class="form-check">
                        <input type="radio" id="status-active" name="status" value="active" class="form-check-input"
                               <?php echo $announcement_status === 'active' ? 'checked' : ''; ?>>
                        <label for="status-active">Active (Visible to students)</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" id="status-inactive" name="status" value="inactive" class="form-check-input"
                               <?php echo $announcement_status === 'inactive' ? 'checked' : ''; ?>>
                        <label for="status-inactive">Inactive (Hidden from students)</label>
                    </div>
                </div>
                
                <div class="form-buttons">
                    <?php if ($form_mode === 'edit'): ?>
                        <a href="announcement.php" class="btn btn-secondary">
                            <span class="material-symbols-rounded">cancel</span> Cancel
                        </a>
                    <?php else: ?>
                        <button type="reset" class="btn btn-secondary">
                            <span class="material-symbols-rounded">refresh</span> Reset
                        </button>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded">
                            <?php echo $form_mode === 'edit' ? 'save' : 'add_circle'; ?>
                        </span>
                        <?php echo $form_mode === 'edit' ? 'Save Changes' : 'Add Announcement'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script src="assets/js/side.js"></script>
<script>
    // Confirmation dialog for deleting announcements
    function confirmDelete(id, title) {
        if (confirm('Are you sure you want to delete the announcement: "' + title + '"?\nThis action cannot be undone.')) {
            window.location.href = 'announcement.php?action=delete&id=' + id;
        }
    }
</script>
</body>
</html>
