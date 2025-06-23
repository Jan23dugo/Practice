<?php
// Admin page to view tech status distribution

// Include database configuration
require_once 'config/config.php';

// Check if the user is logged in as an administrator
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Access denied. Please login as an administrator.");
}

// Function to get tech status counts
function getTechStatusCounts($conn) {
    $query = "SELECT 
                CASE 
                    WHEN is_tech = 0 THEN 'Non-Tech' 
                    WHEN is_tech = 1 THEN 'Tech' 
                    WHEN is_tech = 2 THEN 'Ladderized' 
                    ELSE 'Unknown' 
                END as tech_status,
                COUNT(*) as count
              FROM register_studentsqe
              GROUP BY is_tech
              ORDER BY is_tech";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Error retrieving tech status counts: " . $conn->error);
    }
    
    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[] = $row;
    }
    
    return $counts;
}

// Function to get student details by tech status
function getStudentsByTechStatus($conn, $techStatus) {
    $query = "SELECT 
                student_id,
                CONCAT(first_name, ' ', last_name) as full_name,
                email,
                student_type,
                previous_program,
                desired_program,
                status
              FROM register_studentsqe
              WHERE is_tech = ?
              ORDER BY student_id DESC
              LIMIT 100";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $techStatus);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    return $students;
}

// Handle filter submission
$selectedTechStatus = isset($_GET['tech_status']) ? (int)$_GET['tech_status'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Status Distribution</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f5f5f5;
        }
        
        h1, h2 {
            color: #800000;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .back-link {
            color: #800000;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            width: 30%;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.non-tech {
            border-left: 4px solid #dc3545;
        }
        
        .stat-card.tech {
            border-left: 4px solid #28a745;
        }
        
        .stat-card.ladderized {
            border-left: 4px solid #007bff;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #555;
        }
        
        .stat-card .count {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card.non-tech .count {
            color: #dc3545;
        }
        
        .stat-card.tech .count {
            color: #28a745;
        }
        
        .stat-card.ladderized .count {
            color: #007bff;
        }
        
        .stat-card a {
            display: inline-block;
            margin-top: 10px;
            color: #800000;
            text-decoration: none;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #800000;
            color: white;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .status-approved {
            background-color: #28a745;
            color: white;
        }
        
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }
        
        .status-needs-review {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tech Status Distribution</h1>
            <a href="admin_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php
        try {
            // Get tech status counts
            $techStatusCounts = getTechStatusCounts($conn);
            
            // Calculate total
            $total = 0;
            $countsByStatus = [
                'Non-Tech' => 0,
                'Tech' => 0,
                'Ladderized' => 0
            ];
            
            foreach ($techStatusCounts as $status) {
                $total += $status['count'];
                $countsByStatus[$status['tech_status']] = $status['count'];
            }
        ?>
        
        <div class="stats-container">
            <div class="stat-card non-tech">
                <h3>Non-Tech Students</h3>
                <div class="count"><?php echo $countsByStatus['Non-Tech']; ?></div>
                <div class="percentage"><?php echo $total > 0 ? round(($countsByStatus['Non-Tech'] / $total) * 100, 1) : 0; ?>% of total</div>
                <a href="?tech_status=0">View Details</a>
            </div>
            
            <div class="stat-card tech">
                <h3>Tech Students</h3>
                <div class="count"><?php echo $countsByStatus['Tech']; ?></div>
                <div class="percentage"><?php echo $total > 0 ? round(($countsByStatus['Tech'] / $total) * 100, 1) : 0; ?>% of total</div>
                <a href="?tech_status=1">View Details</a>
            </div>
            
            <div class="stat-card ladderized">
                <h3>Ladderized Students</h3>
                <div class="count"><?php echo $countsByStatus['Ladderized']; ?></div>
                <div class="percentage"><?php echo $total > 0 ? round(($countsByStatus['Ladderized'] / $total) * 100, 1) : 0; ?>% of total</div>
                <a href="?tech_status=2">View Details</a>
            </div>
        </div>
        
        <?php
            // Display student list if a tech status is selected
            if ($selectedTechStatus !== null) {
                $statusLabels = [
                    0 => 'Non-Tech',
                    1 => 'Tech',
                    2 => 'Ladderized'
                ];
                
                $students = getStudentsByTechStatus($conn, $selectedTechStatus);
        ?>
        
        <h2><?php echo $statusLabels[$selectedTechStatus]; ?> Students (<?php echo count($students); ?>)</h2>
        
        <?php if (empty($students)): ?>
            <p>No students found with this tech status.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Student Type</th>
                        <th>Previous Program</th>
                        <th>Desired Program</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_type']); ?></td>
                            <td><?php echo htmlspecialchars($student['previous_program']); ?></td>
                            <td><?php echo htmlspecialchars($student['desired_program']); ?></td>
                            <td>
                                <?php 
                                    $statusClass = '';
                                    switch ($student['status']) {
                                        case 'pending':
                                            $statusClass = 'status-pending';
                                            break;
                                        case 'approved':
                                            $statusClass = 'status-approved';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'status-rejected';
                                            break;
                                        case 'needs_review':
                                            $statusClass = 'status-needs-review';
                                            break;
                                    }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $student['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <?php
            }
        } catch (Exception $e) {
            echo '<div class="error-message">' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html> 