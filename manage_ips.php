<?php
// Include admin session management
require_once 'config/admin_session.php';
require_once 'config/config.php';
require_once 'config/ip_config.php';

// Check admin session and handle timeout
checkAdminSession();

// Handle IP address management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['ip_address']) && isset($_POST['description'])) {
                    $ip = filter_var($_POST['ip_address'], FILTER_VALIDATE_IP);
                    $description = trim($_POST['description']);
                    
                    if ($ip) {
                        // Check if IP already exists
                        $check_query = "SELECT id FROM verified_ips WHERE ip_address = ?";
                        $check_stmt = $conn->prepare($check_query);
                        $check_stmt->bind_param("s", $ip);
                        $check_stmt->execute();
                        $existing = $check_stmt->get_result();
                        
                        if ($existing->num_rows > 0) {
                            $error_message = "This IP address is already in the verified list.";
                        } else {
                            $query = "INSERT INTO verified_ips (ip_address, description) VALUES (?, ?)";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ss", $ip, $description);
                            if ($stmt->execute()) {
                                $success_message = "Student access IP address added successfully!";
                            } else {
                                $error_message = "Failed to add IP address.";
                            }
                        }
                    } else {
                        $error_message = "Invalid IP address format.";
                    }
                }
                break;
                
            case 'delete':
                if (isset($_POST['ip_id'])) {
                    $query = "DELETE FROM verified_ips WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $_POST['ip_id']);
                    if ($stmt->execute()) {
                        $success_message = "Student access IP address deleted successfully!";
                    } else {
                        $error_message = "Failed to delete IP address.";
                    }
                }
                break;
                
            case 'toggle':
                if (isset($_POST['ip_id'])) {
                    $query = "UPDATE verified_ips SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $_POST['ip_id']);
                    if ($stmt->execute()) {
                        $success_message = "Student access IP address status updated successfully!";
                    } else {
                        $error_message = "Failed to update IP address status.";
                    }
                }
                break;
        }
    }
}

// Fetch all verified IPs
$query = "SELECT * FROM verified_ips ORDER BY created_at DESC";
$result = $conn->query($query);
$verified_ips = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Verified IPs - Admin Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
       
    

        /* Header Styling - Match analytics.php */
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .analytics-title {
            font-size: 36px;
            color: #75343A;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        
        .analytics-date {
            font-size: 18px;
            color: #555;
            font-weight: 500;
        }

        .analytics-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 20px;
            color: #333;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            color: #333;
            font-weight: 500;
            font-size: 16px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #75343A;
            outline: none;
            box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
        }

        .btn {
            padding: 16px 32px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: #75343A;
            color: white;
        }

        .btn-primary:hover {
            background: #5c2930;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Action Buttons - Match analytics.php */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }

        /* Table Styling */
        .ip-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .ip-table th {
            background: #75343A;
            color: white;
            padding: 20px 25px;
            text-align: left;
            font-weight: 500;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ip-table td {
            padding: 20px 25px;
            border-bottom: 1px solid #eef0f3;
            color: #333;
            font-size: 16px;
            line-height: 1.5;
        }

        .ip-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .ip-table tbody tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s ease;
        }

        /* Status Badge */
        .status-badge {
            padding: 8px 16px;
            border-radius: 18px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Action Buttons */
        .action-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            margin-right: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .toggle-btn {
            background: #6c757d;
            color: white;
        }

        .toggle-btn:hover {
            background: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 30px;
            color: #666;
            font-style: italic;
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .analytics-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .analytics-title {
                font-size: 28px;
            }
            
            .ip-table {
                font-size: 12px;
            }
            
            .ip-table th,
            .ip-table td {
                padding: 12px 10px;
            }
            
            .action-btn {
                padding: 6px 12px;
                font-size: 12px;
                margin-bottom: 5px;
            }
        }

      
        /* Success/Error Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <div class="main">
            <div class="analytics-header">
                    <h1 class="analytics-title">
                        <span class="material-symbols-rounded">security</span>
                        Manage Student Access IP Addresses
                    </h1>
                    <div class="analytics-date">
                        <?php echo date('l, F j, Y'); ?>
                    </div>
                </div>
                
                <!-- Display Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 10px;">check_circle</span>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 10px;">error</span>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <!-- Add New IP Address Card -->
                <div class="analytics-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span class="material-symbols-rounded">add_circle</span>
                        Add New Student Access IP Address
                    </h2>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="ip_address">IP Address:</label>
                        <input type="text" 
                               id="ip_address" 
                               name="ip_address" 
                               required 
                               pattern="^(\d{1,3}\.){3}\d{1,3}$"
                               placeholder="e.g., 192.168.1.100">
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <input type="text" 
                               id="description" 
                               name="description" 
                               required
                               placeholder="e.g., Office Network, Admin Computer">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 5px; font-size: 16px;">add</span>
                        Add IP Address
                    </button>
                </form>
            </div>
            
            <!-- Current IP Addresses Card -->
            <div class="analytics-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span class="material-symbols-rounded">list</span>
                        Current Verified IP Addresses
                    </h2>
                </div>
                <?php if (empty($verified_ips)): ?>
                    <div class="empty-state">
                        <span class="material-symbols-rounded" style="font-size: 48px; color: #ccc; margin-bottom: 10px; display: block;">security</span>
                        <p>No verified IP addresses found.</p>
                        <p>Add IP addresses above to allow access from specific locations.</p>
                    </div>
                <?php else: ?>
                    <table class="ip-table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($verified_ips as $ip): ?>
                            <tr>
                                <td>
                                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 8px; font-size: 16px; color: #75343A;">computer</span>
                                    <?php echo htmlspecialchars($ip['ip_address']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($ip['description']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $ip['status']; ?>">
                                        <?php echo ucfirst($ip['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($ip['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                                        <button type="submit" class="action-btn toggle-btn">
                                            <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 3px; font-size: 14px;">
                                                <?php echo $ip['status'] === 'active' ? 'toggle_off' : 'toggle_on'; ?>
                                            </span>
                                            <?php echo $ip['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student access IP address?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                                        <button type="submit" class="action-btn delete-btn">
                                            <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 3px; font-size: 14px;">delete</span>
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/side.js"></script>
    <script>
        // Add form validation
        document.getElementById('ip_address').addEventListener('input', function() {
            const value = this.value;
            const pattern = /^(\d{1,3}\.){3}\d{1,3}$/;
            
            if (value && !pattern.test(value)) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });

        // Enhanced confirmation for delete
        function confirmDelete(form) {
            const ipAddress = form.closest('tr').querySelector('td:first-child').textContent.trim();
            return confirm(`Are you sure you want to delete IP address: ${ipAddress}?\n\nThis action cannot be undone.`);
        }

        // Update all delete forms to use enhanced confirmation
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
            form.onsubmit = function() {
                return confirmDelete(this);
            };
        });
    </script>
</body>
</html> 