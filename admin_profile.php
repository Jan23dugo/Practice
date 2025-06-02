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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <!-- Add Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #75343A;
            --primary-light: #8a3d44;
            --primary-dark: #5a2930;
            --secondary-color: #f8f0e3;
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-light: #718096;
            --success-color: #48bb78;
            --error-color: #f56565;
            --warning-color: #ed8936;
            --border-color: #e2e8f0;
            --bg-light: #f7fafc;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .profile-title {
            font-size: 2rem;
            color: var(--primary-color);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .profile-date {
            font-size: 1rem;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Profile Container */
        .profile-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Sidebar */
        .profile-sidebar {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            height: fit-content;
        }

        .profile-info {
            padding: 2rem;
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: var(--primary-color);
            font-size: 3rem;
            font-weight: 700;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-lg);
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .profile-email {
            font-size: 0.875rem;
            opacity: 0.8;
            word-break: break-all;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
        }

        .sidebar-menu li {
            margin: 0.25rem 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            text-decoration: none;
            color: var(--text-secondary);
            transition: all 0.3s ease;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background-color: var(--bg-light);
            color: var(--primary-color);
        }

        .sidebar-menu a.active {
            background-color: var(--bg-light);
            color: var(--primary-color);
            border-left-color: var(--primary-color);
        }

        .sidebar-menu .material-symbols-rounded {
            font-size: 1.25rem;
        }

        /* Main Content */
        .profile-main {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            padding: 2rem;
        }

        .profile-section {
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        /* Profile Data Grid */
        .profile-data {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .data-item {
            background: var(--bg-light);
            padding: 1.25rem;
            border-radius: 0.75rem;
            transition: transform 0.3s ease;
        }

        .data-item:hover {
            transform: translateY(-2px);
        }

        .data-label {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
            display: block;
        }

        .data-value {
            font-size: 1.125rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Security Section */
        .security-item {
            background: var(--bg-light);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .security-item:hover {
            transform: translateY(-2px);
        }

        .security-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .security-title {
            font-size: 1.125rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .security-text {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        /* Login History */
        .login-item {
            background: var(--bg-light);
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .login-item:hover {
            transform: translateY(-2px);
        }

        .login-details {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .login-date {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .login-ip {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .login-status {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: var(--success-color);
            color: white;
        }

        /* Form Styles */
        .profile-form {
            display: none;
            margin-top: 1.5rem;
            background: var(--bg-light);
            padding: 1.5rem;
            border-radius: 0.75rem;
        }

        .profile-form.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: white;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--bg-light);
            transform: translateY(-1px);
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 1000;
        }

        .toast {
            background: white;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease-out;
            min-width: 300px;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .toast-success {
            border-left: 4px solid var(--success-color);
        }

        .toast-error {
            border-left: 4px solid var(--error-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .profile-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .profile-title {
                font-size: 1.5rem;
            }

            .form-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
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
                    <form action="update_profile.php" method="POST" id="profile-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($admin_email); ?>" 
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                   required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" class="btn btn-secondary" id="cancel-edit-btn">
                                <span class="material-symbols-rounded">close</span>
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="save-profile-btn">
                                <span class="material-symbols-rounded">save</span>
                                Save Changes
                                <div class="loading-spinner"></div>
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
                    <form action="update_password.php" method="POST" id="password-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                            <div class="invalid-feedback">Please enter your current password.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" 
                                   minlength="<?php echo $password_requirements['min_length']; ?>"
                                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
                                   required>
                            <div class="password-strength"></div>
                            <div class="invalid-feedback">
                                Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <div class="invalid-feedback">Passwords do not match.</div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" class="btn btn-secondary" id="cancel-password-btn">
                                <span class="material-symbols-rounded">close</span>
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="save-password-btn">
                                <span class="material-symbols-rounded">save</span>
                                Update Password
                                <div class="loading-spinner"></div>
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

<!-- Add Toast Container -->
<div class="toast-container"></div>

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

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <span class="material-symbols-rounded">${type === 'success' ? 'check_circle' : 'error'}</span>
                ${message}
            `;
            document.querySelector('.toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        // Profile form submission
        const profileForm = document.getElementById('profile-form');
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.loading-spinner');
            
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            
            try {
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Profile updated successfully');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(result.message || 'Failed to update profile', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        });

        // Password form submission
        const passwordForm = document.getElementById('password-form');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const currentPasswordInput = document.getElementById('current_password');

        // Password validation function
        function validatePassword(password) {
            const minLength = 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

            return {
                isValid: password.length >= minLength && hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar,
                errors: {
                    length: password.length < minLength,
                    upperCase: !hasUpperCase,
                    lowerCase: !hasLowerCase,
                    numbers: !hasNumbers,
                    specialChar: !hasSpecialChar
                }
            };
        }

        // Real-time password validation
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const validation = validatePassword(password);
            const feedback = this.nextElementSibling.nextElementSibling;
            
            if (password) {
                if (!validation.isValid) {
                    this.classList.add('is-invalid');
                    let errorMessage = 'Password must contain:';
                    if (validation.errors.length) errorMessage += ' at least 8 characters,';
                    if (validation.errors.upperCase) errorMessage += ' uppercase letter,';
                    if (validation.errors.lowerCase) errorMessage += ' lowercase letter,';
                    if (validation.errors.numbers) errorMessage += ' number,';
                    if (validation.errors.specialChar) errorMessage += ' special character';
                    feedback.textContent = errorMessage;
                } else {
                    this.classList.remove('is-invalid');
                    feedback.textContent = '';
                }
            } else {
                this.classList.remove('is-invalid');
                feedback.textContent = '';
            }
        });

        // Confirm password validation
        confirmPasswordInput.addEventListener('input', function() {
            const feedback = this.nextElementSibling;
            if (this.value !== newPasswordInput.value) {
                this.classList.add('is-invalid');
                feedback.textContent = 'Passwords do not match';
            } else {
                this.classList.remove('is-invalid');
                feedback.textContent = '';
            }
        });

        // Password form submission
        passwordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Reset previous validation states
            const inputs = this.querySelectorAll('input');
            inputs.forEach(input => input.classList.remove('is-invalid'));
            
            // Validate current password
            if (!currentPasswordInput.value) {
                currentPasswordInput.classList.add('is-invalid');
                currentPasswordInput.nextElementSibling.textContent = 'Please enter your current password';
                return;
            }

            // Validate new password
            const passwordValidation = validatePassword(newPasswordInput.value);
            if (!passwordValidation.isValid) {
                newPasswordInput.classList.add('is-invalid');
                let errorMessage = 'Password must contain:';
                if (passwordValidation.errors.length) errorMessage += ' at least 8 characters,';
                if (passwordValidation.errors.upperCase) errorMessage += ' uppercase letter,';
                if (passwordValidation.errors.lowerCase) errorMessage += ' lowercase letter,';
                if (passwordValidation.errors.numbers) errorMessage += ' number,';
                if (passwordValidation.errors.specialChar) errorMessage += ' special character';
                newPasswordInput.nextElementSibling.nextElementSibling.textContent = errorMessage;
                return;
            }

            // Validate password confirmation
            if (newPasswordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.classList.add('is-invalid');
                confirmPasswordInput.nextElementSibling.textContent = 'Passwords do not match';
                return;
            }

            // If all validations pass, submit the form
            const submitBtn = this.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.loading-spinner');
            
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';

            try {
                const formData = new FormData(this);
                
                // Log form data for debugging
                console.log('Submitting password update with data:', {
                    current_password: formData.get('current_password'),
                    new_password: formData.get('new_password'),
                    confirm_password: formData.get('confirm_password')
                });

                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Log response for debugging
                console.log('Response status:', response.status);
                
                let result;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    result = await response.json();
                } else {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                }

                console.log('Response data:', result);

                if (result.success) {
                    showToast('Password updated successfully');
                    this.reset();
                    setTimeout(() => {
                        changePasswordForm.classList.remove('active');
                        changePasswordBtn.style.display = 'inline-flex';
                    }, 1500);
                } else {
                    let errorMessage = 'Failed to update password. ';
                    if (result.message) {
                        errorMessage += result.message;
                    } else if (result.error) {
                        errorMessage += result.error;
                    } else {
                        errorMessage += 'Please check your current password.';
                    }
                    showToast(errorMessage, 'error');
                }
            } catch (error) {
                console.error('Error details:', error);
                let errorMessage = 'An error occurred while updating your password. ';
                if (error.message) {
                    errorMessage += error.message;
                }
                showToast(errorMessage, 'error');
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>
