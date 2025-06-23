<?php
// Include admin session management
require_once 'config/admin_session.php';
include 'config/config.php';

// Check admin session and handle timeout
checkAdminSession();

// Handle marking notifications as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = mysqli_real_escape_string($conn, $_POST['notification_id']);
    $update_query = "UPDATE admin_notifications SET is_read = 1 WHERE notification_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
}

// Handle marking all as read
if (isset($_POST['mark_all_read'])) {
    $update_all_query = "UPDATE admin_notifications SET is_read = 1 WHERE target_admin_id IS NULL OR target_admin_id = ?";
    $stmt = $conn->prepare($update_all_query);
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
}

// Handle deleting notifications
if (isset($_POST['delete']) && isset($_POST['notification_id'])) {
    $notification_id = mysqli_real_escape_string($conn, $_POST['notification_id']);
    $delete_query = "DELETE FROM admin_notifications WHERE notification_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
}

// Fetch notifications with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total notifications count
$count_query = "SELECT COUNT(*) as total FROM admin_notifications WHERE target_admin_id IS NULL OR target_admin_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$total_result = $stmt->get_result()->fetch_assoc();
$total_notifications = $total_result['total'];
$total_pages = ceil($total_notifications / $limit);

// Fetch notifications
$query = "SELECT * FROM admin_notifications 
          WHERE target_admin_id IS NULL OR target_admin_id = ? 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $_SESSION['admin_id'], $limit, $offset);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* Your existing CSS variables */
        :root {
            --primary: #75343A;
            --primary-dark: #5a2930;
            --primary-light: #9e4a52;
            --secondary: #f8f0e3;
            --accent: #d4af37;
            --text-dark: #333333;
            --text-light: #ffffff;
            --gray-light: #f5f5f5;
            --gray: #e0e0e0;
        }

        .container-wrapper {
            padding: 20px;
        }

        .notifications-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .notifications-header {
            background: var(--primary);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notifications-header h2 {
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .notifications-filters {
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray);
            display: flex;
            gap: 15px;
        }

        .filter-btn {
            background: none;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            color: #666;
            transition: all 0.3s;
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
        }

        .notification-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 20px;
            border-bottom: 1px solid var(--gray);
            display: flex;
            gap: 15px;
            transition: background-color 0.3s;
        }

        .notification-item:hover {
            background-color: var(--gray-light);
        }

        .notification-item.unread {
            background-color: rgba(117, 52, 58, 0.05);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-dark);
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .notification-message {
            color: #666;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #888;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .meta-btn {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 13px;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .meta-btn:hover {
            background: rgba(117, 52, 58, 0.1);
        }

        .notification-time {
            color: #888;
            font-size: 13px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            padding: 20px;
            background: white;
            border-top: 1px solid var(--gray);
        }

        .page-btn {
            padding: 8px 12px;
            border: 1px solid var(--gray);
            background: white;
            color: var(--text-dark);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .page-btn:hover,
        .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }

        .empty-state .material-symbols-rounded {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .priority-high {
            color: #dc3545;
        }

        .priority-medium {
            color: #ffc107;
        }

        .priority-low {
            color: #28a745;
        }

        @media (max-width: 768px) {
            .notifications-filters {
                overflow-x: auto;
                padding: 10px;
            }

            .notification-item {
                padding: 15px;
            }

            .notification-icon {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <div class="main">
            <div class="container-wrapper">
                <div class="notifications-container">
                    <div class="notifications-header">
                        <h2>
                            <span class="material-symbols-rounded">notifications</span>
                            Notifications
                        </h2>
                        <div class="header-actions">
                            <?php if ($total_notifications > 0): ?>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="mark_all_read" class="action-btn">
                                    <span class="material-symbols-rounded">mark_email_read</span>
                                    Mark all as read
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="notifications-filters">
                        <button class="filter-btn active" data-filter="all">All</button>
                        <button class="filter-btn" data-filter="unread">Unread</button>
                        <button class="filter-btn" data-filter="registration">Registration</button>
                        <button class="filter-btn" data-filter="exam">Exam</button>
                        <button class="filter-btn" data-filter="system">System</button>
                    </div>

                    <div class="notification-list">
                        <?php if ($notifications->num_rows > 0): ?>
                            <?php while ($notification = $notifications->fetch_assoc()): 
                                $timestamp = strtotime($notification['created_at']);
                                $now = time();
                                $diff = $now - $timestamp;
                                
                                if ($diff < 60) {
                                    $time = "Just now";
                                } elseif ($diff < 3600) {
                                    $time = floor($diff/60) . " minutes ago";
                                } elseif ($diff < 86400) {
                                    $time = floor($diff/3600) . " hours ago";
                                } else {
                                    $time = date('M d, Y h:i A', $timestamp);
                                }
                            ?>
                                <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" data-type="<?php echo $notification['type']; ?>">
                                    <div class="notification-icon">
                                        <span class="material-symbols-rounded">
                                            <?php
                                            switch ($notification['type']) {
                                                case 'registration':
                                                    echo 'app_registration';
                                                    break;
                                                case 'exam':
                                                    echo 'quiz';
                                                    break;
                                                case 'system':
                                                    echo 'admin_panel_settings';
                                                    break;
                                                case 'analytics':
                                                    echo 'analytics';
                                                    break;
                                                default:
                                                    echo 'notifications';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php if ($notification['priority'] === 'high'): ?>
                                                <span class="material-symbols-rounded priority-high">priority_high</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-message">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </div>
                                        <div class="notification-meta">
                                            <span class="notification-time"><?php echo $time; ?></span>
                                            <div class="notification-actions">
                                                <?php if (!$notification['is_read']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                        <button type="submit" name="mark_read" class="meta-btn">Mark as read</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                    <button type="submit" name="delete" class="meta-btn" onclick="return confirm('Are you sure you want to delete this notification?')">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <span class="material-symbols-rounded">notifications_off</span>
                                <h3>No notifications</h3>
                                <p>You're all caught up! Check back later for new updates.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>" class="page-btn">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo $page == $i ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?>" class="page-btn">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/side.js"></script>
    <script>
        // Filter notifications
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                document.querySelectorAll('.notification-item').forEach(item => {
                    if (filter === 'all') {
                        item.style.display = 'flex';
                    } else if (filter === 'unread') {
                        item.style.display = item.classList.contains('unread') ? 'flex' : 'none';
                    } else {
                        item.style.display = item.dataset.type === filter ? 'flex' : 'none';
                    }
                });
            });
        });

        // Auto-hide success messages
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html> 