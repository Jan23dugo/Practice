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

// Get admin data from session and database
$admin_id = $_SESSION['admin_id'];

// Fetch admin details from database
$query = "SELECT * FROM admin WHERE admin_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

// If admin found, get data
if ($result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
    $admin_email = $admin_data['email'];
    $created_at = $admin_data['created_at'];
} else {
    // If admin data not found (shouldn't happen normally)
    $admin_email = $_SESSION['email'] ?? 'Unknown';
    $created_at = 'N/A';
}

// Handle profile message
$profile_message = '';
$message_class = '';

if (isset($_SESSION['profile_message'])) {
    $profile_message = $_SESSION['profile_message'];
    $message_class = $_SESSION['message_class'] ?? 'success';
    // Clear the message after displaying it
    unset($_SESSION['profile_message']);
    unset($_SESSION['message_class']);
}

// Get last login info
$query = "SELECT * FROM admin_login_logs WHERE admin_id = ? ORDER BY created_at DESC LIMIT 2";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$login_logs = $stmt->get_result();

$last_login = 'N/A';
$previous_login = 'N/A';
$last_ip = 'N/A';

if ($login_logs->num_rows > 0) {
    $log_entries = $login_logs->fetch_all(MYSQLI_ASSOC);
    
    if (isset($log_entries[0])) {
        $last_login = date('F j, Y, g:i a', strtotime($log_entries[0]['created_at']));
        $last_ip = $log_entries[0]['ip_address'];
    }
    
    if (isset($log_entries[1])) {
        $previous_login = date('F j, Y, g:i a', strtotime($log_entries[1]['created_at']));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - CCIS Qualifying Exam System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Linking Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* Profile Page Styles */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .profile-title {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        
        .profile-date {
            font-size: 18px;
            color: #555;
            font-weight: 500;
        }
        
        /* Two-column layout */
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Profile Card Styles */
        .profile-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 0;
            overflow: hidden;
        }
        
        .profile-info {
            padding: 30px 20px;
            text-align: center;
            background: linear-gradient(135deg, #75343A, #5a2930);
            color: white;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #f8f0e3;
            color: #75343A;
            font-size: 48px;
            font-weight: 700;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid rgba(255, 255, 255, 0.2);
        }
        
        .profile-name {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-role {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .profile-email {
            font-size: 15px;
            opacity: 0.8;
            margin-bottom: 20px;
            word-break: break-all;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .sidebar-menu li:last-child {
            border-bottom: none;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .sidebar-menu a:hover {
            background-color: #f8f9fa;
            color: #75343A;
        }
        
        .sidebar-menu a.active {
            background-color: #f8f9fa;
            color: #75343A;
            border-left: 4px solid #75343A;
        }
        
        /* Main Content Area */
        .profile-main {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .profile-section {
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .section-title {
            font-size: 20px;
            color: #333;
            font-weight: 600;
        }
        
        .profile-data {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .data-item {
            margin-bottom: 20px;
        }
        
        .data-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            display: block;
        }
        
        .data-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        /* Security Section */
        .security-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .security-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .security-title {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }
        
        .security-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        /* Login History */
        .login-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            margin-bottom: 10px;
        }
        
        .login-details {
            display: flex;
            flex-direction: column;
        }
        
        .login-date {
            font-size: 15px;
            color: #333;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .login-ip {
            font-size: 13px;
            color: #666;
        }
        
        .login-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background-color: #d4edda;
            color: #155724;
        }
        
        /* Form Styles */
        .profile-form {
            display: none;
            margin-top: 20px;
        }
        
        .profile-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
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
        
        .btn-danger {
            background-color: #F44336;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="profile-header">
        <h1 class="profile-title">Admin Profile</h1>
        <div class="profile-date">
            <?php echo date('l, F j, Y'); ?>
        </div>
    </div>
    
    <?php if (!empty($profile_message)): ?>
        <div class="alert alert-<?php echo $message_class; ?>">
            <?php echo $profile_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-container">
        <!-- Profile Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-info">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($admin_email, 0, 1)); ?>
                </div>
                <div class="profile-name">Admin Account</div>
                <div class="profile-role">System Administrator</div>
                <div class="profile-email"><?php echo htmlspecialchars($admin_email); ?></div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="#" class="active" id="profile-info-link">
                        <span class="material-symbols-rounded">person</span>
                        Profile Information
                    </a>
                </li>
                <li>
                    <a href="#" id="security-link">
                        <span class="material-symbols-rounded">security</span>
                        Security Settings
                    </a>
                </li>
                <li>
                    <a href="#" id="login-history-link">
                        <span class="material-symbols-rounded">history</span>
                        Login History
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <span class="material-symbols-rounded">logout</span>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content Area -->
        <div class="profile-main">
            <!-- Profile Information Section -->
            <div class="profile-section active" id="profile-info-section">
                <div class="section-header">
                    <h2 class="section-title">Profile Information</h2>
                    <button class="btn btn-primary" id="edit-profile-btn">
                        <span class="material-symbols-rounded">edit</span>
                        Edit Profile
                    </button>
                </div>
                
                <div class="profile-data">
                    <div class="data-item">
                        <span class="data-label">Email Address</span>
                        <span class="data-value"><?php echo htmlspecialchars($admin_email); ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Role</span>
                        <span class="data-value">System Administrator</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Account Created</span>
                        <span class="data-value"><?php echo date('F j, Y', strtotime($created_at)); ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Last Login</span>
                        <span class="data-value"><?php echo $last_login; ?></span>
                    </div>
                </div>
                
                <!-- Edit Profile Form (Hidden by default) -->
                <div class="profile-form" id="edit-profile-form">
                    <form action="update_profile.php" method="POST">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" class="btn btn-secondary" id="cancel-edit-btn">
                                <span class="material-symbols-rounded">close</span>
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-rounded">save</span>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Security Settings Section -->
            <div class="profile-section" id="security-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Security Settings</h2>
                </div>
                
                <div class="security-item">
                    <div class="security-header">
                        <h3 class="security-title">Password</h3>
                        <button class="btn btn-primary" id="change-password-btn">
                            <span class="material-symbols-rounded">lock_reset</span>
                            Change Password
                        </button>
                    </div>
                    <p class="security-text">Your password was last changed on <strong>
                        <?php echo date('F j, Y', strtotime($created_at)); ?>
                    </strong></p>
                </div>
                
                <!-- Change Password Form (Hidden by default) -->
                <div class="profile-form" id="change-password-form">
                    <form action="update_password.php" method="POST">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" class="btn btn-secondary" id="cancel-password-btn">
                                <span class="material-symbols-rounded">close</span>
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-rounded">save</span>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Login History Section -->
            <div class="profile-section" id="login-history-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Login History</h2>
                </div>
                
                <div class="login-history">
                    <div class="login-item">
                        <div class="login-details">
                            <span class="login-date"><?php echo $last_login; ?></span>
                            <span class="login-ip">IP Address: <?php echo $last_ip; ?></span>
                        </div>
                        <span class="login-status">Current Session</span>
                    </div>
                    
                    <?php if ($previous_login !== 'N/A'): ?>
                    <div class="login-item">
                        <div class="login-details">
                            <span class="login-date"><?php echo $previous_login; ?></span>
                            <span class="login-ip">IP Address: <?php echo isset($log_entries[1]) ? $log_entries[1]['ip_address'] : 'N/A'; ?></span>
                        </div>
                        <span class="login-status">Successful</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="assets/js/side.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Profile navigation
        const profileInfoLink = document.getElementById('profile-info-link');
        const securityLink = document.getElementById('security-link');
        const loginHistoryLink = document.getElementById('login-history-link');
        
        const profileInfoSection = document.getElementById('profile-info-section');
        const securitySection = document.getElementById('security-section');
        const loginHistorySection = document.getElementById('login-history-section');
        
        // Navigation functions
        profileInfoLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active link
            document.querySelector('.sidebar-menu a.active').classList.remove('active');
            this.classList.add('active');
            
            // Show profile section, hide others
            profileInfoSection.style.display = 'block';
            securitySection.style.display = 'none';
            loginHistorySection.style.display = 'none';
        });
        
        securityLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active link
            document.querySelector('.sidebar-menu a.active').classList.remove('active');
            this.classList.add('active');
            
            // Show security section, hide others
            profileInfoSection.style.display = 'none';
            securitySection.style.display = 'block';
            loginHistorySection.style.display = 'none';
        });
        
        loginHistoryLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active link
            document.querySelector('.sidebar-menu a.active').classList.remove('active');
            this.classList.add('active');
            
            // Show login history section, hide others
            profileInfoSection.style.display = 'none';
            securitySection.style.display = 'none';
            loginHistorySection.style.display = 'block';
        });
        
        // Edit profile toggle
        const editProfileBtn = document.getElementById('edit-profile-btn');
        const editProfileForm = document.getElementById('edit-profile-form');
        const cancelEditBtn = document.getElementById('cancel-edit-btn');
        
        editProfileBtn.addEventListener('click', function() {
            editProfileForm.classList.add('active');
            this.style.display = 'none';
        });
        
        cancelEditBtn.addEventListener('click', function() {
            editProfileForm.classList.remove('active');
            editProfileBtn.style.display = 'inline-flex';
        });
        
        // Change password toggle
        const changePasswordBtn = document.getElementById('change-password-btn');
        const changePasswordForm = document.getElementById('change-password-form');
        const cancelPasswordBtn = document.getElementById('cancel-password-btn');
        
        changePasswordBtn.addEventListener('click', function() {
            changePasswordForm.classList.add('active');
            this.style.display = 'none';
        });
        
        cancelPasswordBtn.addEventListener('click', function() {
            changePasswordForm.classList.remove('active');
            changePasswordBtn.style.display = 'inline-flex';
        });
    });
</script>
</body>
</html>
