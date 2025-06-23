<?php
    // Include admin session management
    require_once 'config/admin_session.php';
    include('config/config.php');

    // Check admin session and handle timeout
    checkAdminSession();

    // Fetch all applicants with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 10;
    $offset = ($page - 1) * $records_per_page;

    // Get total number of records
    $total_query = "SELECT COUNT(*) as total FROM register_studentsqe";
    $total_result = $conn->query($total_query);
    $total_records = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch applicants with pagination
    $query = "SELECT rsq.*, 
    u.university_name AS previous_school_name, 
    dp.program_name AS desired_program_name, 
    pp.program_name AS previous_program_name
FROM register_studentsqe rsq
LEFT JOIN universities u ON rsq.previous_school = u.university_code COLLATE utf8mb4_unicode_ci
LEFT JOIN programs dp ON rsq.desired_program = dp.program_code COLLATE utf8mb4_unicode_ci
LEFT JOIN programs pp ON rsq.previous_program = pp.program_code COLLATE utf8mb4_unicode_ci
ORDER BY registration_date DESC 
LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Linking Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style> 
 /* Apply styles ONLY to the "Registered Students" title */
 .registered-students-title {
    font-size: 22px;
    font-weight: 500;
    color: #75343A;
    text-align: left;
    padding: 10px 0;
    margin-bottom: 20px;
}
   /* Table Styling */
table {
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
    background: #75343A; /* PUP's maroon color */
    color: white;
    padding: 12px 15px;
    text-align: left;
    font-weight: 500;
    font-size: 14px;
    text-transform: uppercase;
}

/* Table Rows */
td {
    padding: 12px 15px;
    border-bottom: 1px solid #eef0f3;
    color: #333;
    font-size: 14px;
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
}

.modal.show {
    display: block; /* Changed from flex to block */
}

/* Modal Content Container */
.modal-content {
    position: relative;
    background-color: #ffffff;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 1000px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    margin: 20px auto;
    animation: modalFade 0.3s ease-in-out;
}

/* Modal Animation */
@keyframes modalFade {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Modal Header Styling */
.modal-content h5 {
    color: #800000;
    font-size: 1.4rem;
    font-weight: 600;
    margin: 25px 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #800000;
}

/* Modal Close Button */
.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #800000;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close:hover {
    background-color: #f8d7da;
    color: #721c24;
}

/* Information Layout */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
    padding: 10px 0;
    gap: 15px;
}

.col-md-4, .col-md-6 {
    padding: 0 15px;
    flex: 1;
    min-width: 300px;
    margin-bottom: 15px;
}

/* Information Text Styling */
.modal-content p {
    margin: 12px 0;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 10px;
}

.modal-content strong {
    color: #444;
    min-width: 160px;
    max-width: 160px;
    font-weight: 600;
    flex-shrink: 0;
}

.modal-content span:not(.close) {
    color: #666;
    flex: 1;
    min-width: 200px;
    word-break: break-word;
    line-height: 1.5;
}

/* Document Cards */
.card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Scrollbar Styling */
.modal-content::-webkit-scrollbar {
    width: 8px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: #800000;
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #660000;
}

/* Document Preview Improvements */
.card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Document Preview Container */
.doc-preview {
    height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
    background-color: #f8f9fa;
}

.doc-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Filter Section */
.filter-section {
    margin-bottom: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    display: flex;
    gap: 10px;
}

.filter-section select, 
.filter-section input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filter-section input {
    width: 250px;
}

.filter-button {
    background: #75343A;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.filter-button:hover {
    background: #5c2930;
}

/* Pagination */
.pagination {
    margin-top: 20px;
    text-align: center;
    padding: 10px;
}

.pagination a {
    padding: 8px 16px;
    margin: 0 4px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #75343A;
    border-radius: 4px;
    transition: all 0.3s;
}

.pagination a.active {
    background-color: #75343A;
    color: white;
    border-color: #75343A;
}

.pagination a:hover:not(.active) {
    background-color: #f5f5f5;
}

/* Enhanced View Details Button */
.view-btn {
    background: #75343A;  /* Darker blue */
    color: white;
    border: none;
    padding: 8px 16px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-btn:hover {
    background:rgb(255, 229, 231);
    color: #75343A;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Enhanced Status Dropdown */
.status {
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: 500;
    width: 320px;
    border: 2px solid transparent;
    background-color: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
}

/* Status-specific styles */
.status-pending {
    background-color: #fff3cd;
    color: #856404;
    border-color: #ffeeba;
}

.status-accepted {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

/* Enhanced Table Spacing */
td, th {
    padding: 16px 20px;  /* Increased padding */
    line-height: 1.5;
}

/* Make status column wider */
th:nth-child(4), td:nth-child(4) {
    width: 35%;
    min-width: 350px;
}

/* Improved Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 24px 0;
}

.pagination a {
    min-width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 20px;
    border: 1px solid #dee2e6;
    color: #75343A;
    font-weight: 500;
    transition: all 0.3s ease;
}

.pagination a.active {
    background: #75343A;
    color: white;
    border-color: #75343A;
}

.pagination a:hover:not(.active) {
    background: #f8f9fa;
    border-color: #75343A;
}

/* Enhanced Filter Section */
.filter-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 24px;
    display: flex;
    gap: 16px;
    align-items: center;
}

.filter-section select,
.filter-section input {
    padding: 10px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.filter-section select:focus,
.filter-section input:focus {
    border-color: #75343A;
    outline: none;
    box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
}

.filter-section input {
    width: 300px;
}

/* Remove filter button as we'll make it dynamic */
.filter-button {
    display: none;
}

/* Loading Spinner */
.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 0.8s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Academic Information specific styling */
.modal-content .academic-info span:not(.close) {
    max-width: calc(100% - 170px);
}

/* Document View Button */
.doc-view-btn {
    background-color: #75343A;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.doc-view-btn:hover {
    background:rgb(255, 229, 231);
    color: #75343A;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
}

.page-title {
    font-size: 36px;
    color: #75343A;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-shadow: 0 1px 1px rgba(0,0,0,0.1);
    border-bottom: 2px solid #f0f0f0;
}

@media (max-width: 1024px) {
    .main {
        margin-left: 0px;
    }
    
}

/* Add this to your existing styles */
.status-needs_review {
    background-color: #fef8e8;
    color: #f6a803;
    border-color: #feeeba;
}

/* Student name container */
.student-name-container {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.student-name {
    margin-bottom: 4px;
}

/* Tech Status Badges */
.tech-status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
}

.non-tech-badge {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.tech-badge {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.ladderized-badge {
    background-color: #cce5ff;
    color: #004085;
    border: 1px solid #b8daff;
}

.unknown-badge {
    background-color: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
.review-badge {
    display: inline-flex;
    align-items: center;
    background: #fff8e1;
    border: 1px solid #ffe082;
    border-radius: 12px;
    padding: 3px 10px 3px 6px;
    font-size: 13px;
    cursor: pointer;
    transition: box-shadow 0.2s;
    box-shadow: 0 1px 2px rgba(246,168,3,0.08);
}

.review-badge:hover {
    box-shadow: 0 2px 8px rgba(246,168,3,0.18);
}

.manual-review-row {
    background-color: #fff8e1 !important;
}

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
</style>
</head>
<body>

<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
    <h2 class="page-title registered-students-title">
        <i class="fas fa-users"></i> Registered Students
    </h2>

    <div class="filter-section">
        <select name="status" id="statusFilter" onchange="applyFilters()">
            <option value="">All Status</option>
            <option value="needs_review">Manual Review Required</option>
            <option value="pending">Pending</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
        </select>
        <input type="text" 
               id="searchInput" 
               placeholder="Search by name or reference ID"
               oninput="applyFilters()">
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Student Type</th>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                <th>Email</th>
                <th>Registration Date</th>
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                <th>Category</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows == 0): ?>
                <tr>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                    <td colspan="8" style="text-align: center; padding: 20px; color: #666;">
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
=======
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
>>>>>>> Stashed changes
                        No registered students found.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                        // Determine tech status badge class
                        $techStatus = "Unknown";
                        $techBadgeClass = "unknown-badge";
                        
                        switch((int)$row['is_tech']) {
                            case 0:
                                $techStatus = "Non-Tech";
                                $techBadgeClass = "non-tech-badge";
                                break;
                            case 1:
                                $techStatus = "Tech";
                                $techBadgeClass = "tech-badge";
                                break;
                            case 2:
                                $techStatus = "Ladderized";
                                $techBadgeClass = "ladderized-badge";
                                break;
                        }
                    ?>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                    <tr>
                        <td><?php echo htmlspecialchars($row['reference_id']); ?></td>
                        <td>
                            <div class="student-name-container">
                                <div class="student-name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($row['student_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($row['registration_date'])); ?></td>
                        <td><span class="tech-status-badge <?php echo $techBadgeClass; ?>"><?php echo $techStatus; ?></span></td>
                        <td>
                            <select class="status status-<?php echo (string)$row['status'] === '0' ? 'needs_review' : strtolower((string)$row['status']); ?>" 
                                    onchange="updateStatus(this.value, '<?php echo $row['reference_id']; ?>')">
                                <option value="pending" <?php echo (string)$row['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="needs_review" <?php echo (string)$row['status'] === '0' || (string)$row['status'] === 'needs_review' ? 'selected' : ''; ?>>Manual Review Required</option>
                                <option value="accepted" <?php echo (string)$row['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="rejected" <?php echo (string)$row['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
=======
                    <tr<?php if (!empty($row['admin_notes'])) echo ' class="manual-review-row"'; ?>>
                        <td>
>>>>>>> Stashed changes
                            <div class="student-name-container">
                                <div class="student-name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name'] ?? ''); ?></div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($row['student_type'] ?? ''); ?></td>
                        <td><span class="tech-status-badge <?php echo $techBadgeClass; ?>"><?php echo $techStatus; ?></span></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <select class="status status-<?php echo strtolower((string)$row['status']); ?>" 
                                        onchange="updateStatus(this.value, '<?php echo $row['student_id']; ?>')"
                                        <?php if (in_array(strtolower((string)$row['status']), ['accepted','rejected'])) echo 'disabled'; ?>>
                                    <?php if (strtolower((string)$row['status']) === 'pending'): ?>
                                        <option value="pending" selected><?php echo !empty($row['admin_notes']) ? 'Recommended to take the Qualifying Exam (Manual Review Required)' : 'Recommended to take the Qualifying Exam'; ?></option>
                                        <option value="accepted">Accepted</option>
                                        <option value="rejected">Rejected</option>
                                    <?php elseif (strtolower((string)$row['status']) === 'needs_review'): ?>
                                        <option value="needs_review" selected>Needs Manual Review</option>
                                        <option value="accepted">Accepted</option>
                                        <option value="rejected">Rejected</option>
                                    <?php elseif (strtolower((string)$row['status']) === 'accepted'): ?>
                                        <option value="accepted" selected>Accepted</option>
                                    <?php elseif (strtolower((string)$row['status']) === 'rejected'): ?>
                                        <option value="rejected" selected>Rejected</option>
                                    <?php endif; ?>
                                </select>
                            </div>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                        </td>
                        <td>
                            <button class="view-btn" onclick="viewDetails(this, {
                                ref_id: '<?php echo $row['reference_id']; ?>',
                                first_name: '<?php echo $row['first_name']; ?>',
                                middle_name: '<?php echo $row['middle_name']; ?>',
                                last_name: '<?php echo $row['last_name']; ?>',
                                gender: '<?php echo $row['gender']; ?>',
                                dob: '<?php echo $row['dob']; ?>',
                                email: '<?php echo $row['email']; ?>',
                                contact: '<?php echo $row['contact_number']; ?>',
                                address: '<?php echo $row['street']; ?>',
                                student_type: '<?php echo $row['student_type']; ?>',
                                prev_school: '<?php echo $row['previous_school']; ?>',
                                year_level: '<?php echo $row['year_level']; ?>',
                                prev_program: '<?php echo $row['previous_program']; ?>',
                                desired_program: '<?php echo $row['desired_program']; ?>',
                                is_tech: '<?php echo $row['is_tech']; ?>',
                                tor: '<?php echo $row['tor']; ?>',
                                school_id: '<?php echo $row['school_id']; ?>',
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                                admin_notes: '<?php echo !empty($row['admin_notes']) ? addslashes($row['admin_notes']) : ""; ?>'
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                                admin_notes: '<?php echo !empty($row['admin_notes']) ? addslashes($row['admin_notes']) : ""; ?>',
                                previous_school_name: '<?php echo htmlspecialchars($row['previous_school_name'] ?? ''); ?>',
                                desired_program_name: '<?php echo htmlspecialchars($row['desired_program_name'] ?? ''); ?>',
                                previous_program_name: '<?php echo htmlspecialchars($row['previous_program_name'] ?? ''); ?>'
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                            })">View Details</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_records > $records_per_page): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" <?php echo $page == $i ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<div id="infoModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>

        <!-- Admin Notes (Only shown when present) -->
        <div id="admin-notes-section" style="display:none;">
            <h5 class="text-warning">Admin Notes</h5>
            <div class="row">
                <div class="col-md-12">
                    <div style="background-color: #fef8e8; border-left: 4px solid #f6a803; padding: 15px; margin-bottom: 20px;">
                        <p id="admin_notes" style="margin: 0;"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <h5 class="text-primary">Personal Information</h5>
        <div class="row">
            <div class="col-md-4">
                <p><strong>Reference ID:</strong> <span id="ref_id"></span></p>
                <p><strong>First Name:</strong> <span id="first_name"></span></p>
                <p><strong>Middle Name:</strong> <span id="middle_name"></span></p>
                <p><strong>Last Name:</strong> <span id="last_name"></span></p>
            </div>
            <div class="col-md-4">
                <p><strong>Gender:</strong> <span id="gender"></span></p>
                <p><strong>Date of Birth:</strong> <span id="dob"></span></p>
                <p><strong>Email:</strong> <span id="email"></span></p>
                <p><strong>Contact:</strong> <span id="contact"></span></p>
            </div>
            <div class="col-md-4">
                <p><strong>Address:</strong> <span id="address"></span></p>
            </div>
        </div>

        <!-- Academic Information -->
        <h5 class="text-primary">Academic Information</h5>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Student Type:</strong> <span id="student_type"></span></p>
                <p><strong>Previous School:</strong> <span id="prev_school"></span></p>
                <p id="year_level_row"><strong>Year Level:</strong> <span id="year_level"></span></p>
            </div>
            <div class="col-md-6">
                <p><strong>Previous Program:</strong> <span id="prev_program"></span></p>
                <p><strong>Desired Program:</strong> <span id="desired_program"></span></p>
                <p><strong>Student Category:</strong> <span id="is_tech"></span></p>
            </div>
        </div>

        <!-- Uploaded Documents -->
        <h5 class="text-primary">Uploaded Documents</h5>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Transcript of Records</div>
                    <div class="card-body doc-preview">
                        <img id="tor_preview" src="" alt="TOR">
                    </div>
                    <div style="text-align: center; padding: 10px;">
                        <button class="doc-view-btn" onclick="viewDocument('tor')">View Full Size</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">School ID</div>
                    <div class="card-body doc-preview">
                        <img id="school_id_preview" src="" alt="School ID">
                    </div>
                    <div style="text-align: center; padding: 10px;">
                        <button class="doc-view-btn" onclick="viewDocument('school_id')">View Full Size</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close" onclick="closeStatusModal()">&times;</span>
        <h5 class="text-primary">Status Update</h5>
        <div class="row">
            <div class="col-md-12">
                <div id="reasonField" style="display: none; margin-bottom: 20px;">
                    <label for="rejectionReason" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500;">Reason for Rejection:</label>
                    <textarea id="rejectionReason" 
                             rows="4" 
                             style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"
                             placeholder="Please provide a reason for rejecting this application..."></textarea>
                    <small style="color: #666; margin-top: 5px; display: block;">This reason will be visible to the student.</small>
                </div>
                <p id="statusMessage"></p>
                <!-- Inline status spinner -->
                <div id="status-inline-spinner" style="display:none; justify-content:center; align-items:center; margin-top:16px;">
                  <div class="spinner" style="width:32px; height:32px; border:4px solid #f3e6e8; border-top:4px solid #75343A; border-radius:50%; animation:spin 1s linear infinite;"></div>
                  <span style="margin-left:12px; color:#75343A; font-weight:500; font-size:1rem;">Updating status...</span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12" style="text-align: right; margin-top: 20px;">
                <button class="view-btn" id="confirmStatusBtn" onclick="confirmStatusUpdate()">Update Status</button>
                <button class="view-btn" style="background: #6c757d;" onclick="closeStatusModal()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/side.js"></script>
<script>
let currentStudentId = '';
let currentStatus = '';

function openModal(details) {
    const modal = document.getElementById("infoModal");

    // Populate fields
    document.getElementById("ref_id").textContent = details.ref_id;
    document.getElementById("first_name").textContent = details.first_name;
    document.getElementById("middle_name").textContent = details.middle_name;
    document.getElementById("last_name").textContent = details.last_name;
    document.getElementById("gender").textContent = details.gender;
    document.getElementById("dob").textContent = details.dob;
    document.getElementById("email").textContent = details.email;
    document.getElementById("contact").textContent = details.contact;
    document.getElementById("address").textContent = details.address;
    document.getElementById("student_type").textContent = details.student_type;
    document.getElementById("prev_school").textContent = details.previous_school_name || details.prev_school;
    document.getElementById("year_level").textContent = details.year_level;
    document.getElementById("prev_program").textContent = details.previous_program_name || details.prev_program;
    document.getElementById("desired_program").textContent = details.desired_program_name || details.desired_program;
    document.getElementById("is_tech").textContent = details.is_tech;

    // Hide Year Level if student is ladderized
    const yearLevelRow = document.getElementById("year_level_row");
    if (details.student_type && details.student_type.toLowerCase() === "ladderized") {
        yearLevelRow.style.display = "none";
    } else {
        yearLevelRow.style.display = "";
    }

    // Set document previews
    document.getElementById("tor_preview").src = details.tor;
    document.getElementById("school_id_preview").src = details.school_id;
    
    // Handle admin notes if present
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
    const adminNotesSection = document.getElementById("admin-notes-section");
    if (details.admin_notes && details.admin_notes.trim() !== '') {
        // Clean up the admin notes to remove technical error details
        let cleanedNotes = details.admin_notes;
        
        // Remove specific technical error messages
        cleanedNotes = cleanedNotes.replace(/Error retrieving grading rules: Unknown column 'is_default' in 'where clause'/g, 
                                          "Grading system configuration issue. Please add this university to the system.");
        
        document.getElementById("admin_notes").textContent = cleanedNotes;
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    document.getElementById("admin_notes").textContent = "";
    const adminNotesSection = document.getElementById("admin-notes-section");
    if (details.admin_notes && details.admin_notes.trim() !== '') {
        document.getElementById("admin_notes").textContent = details.admin_notes;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        adminNotesSection.style.display = "block";
    } else {
        adminNotesSection.style.display = "none";
    }

    // Show modal with the "show" class
    modal.classList.add("show");
}

function closeModal() {
    document.getElementById("infoModal").classList.remove("show");
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById("infoModal");
    if (event.target === modal) {
        closeModal();
    }
    
    const statusModal = document.getElementById("statusModal");
    if (event.target === statusModal) {
        closeStatusModal();
    }
};


  // Function to view documents in full size
  function viewDocument(docType) {
      let imageSrc = document.getElementById(docType + "_preview").src;
      if (imageSrc) {
          window.open(imageSrc, "_blank");
      }
  }

function updateStatus(status, studentId) {
    currentStudentId = studentId;
    currentStatus = status;
    
    const reasonField = document.getElementById('reasonField');
    const statusMessage = document.getElementById('statusMessage');
    
    // Show/hide reason field based on status
    reasonField.style.display = status === 'rejected' ? 'block' : 'none';
    statusMessage.textContent = `Are you sure you want to mark this student as ${status}?`;
    
    // Show the modal
    document.getElementById("statusModal").classList.add("show");
}

function confirmStatusUpdate() {
    const reason = document.getElementById('rejectionReason').value;
    
    // Validate reason if status is rejected
    if (currentStatus === 'rejected' && !reason.trim()) {
        document.getElementById('statusMessage').innerHTML = '<span style="color: #dc3545;">Please provide a reason for rejection.</span>';
        return;
    }
    
    // Prepare the data
    const formData = new FormData();
    formData.append('status', currentStatus);
    formData.append('student_id', currentStudentId);
    if (currentStatus === 'rejected') {
        formData.append('rejection_reason', reason);
    }
    
    // Show inline spinner
    document.getElementById('status-inline-spinner').style.display = 'flex';
    fetch('update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Hide inline spinner
        document.getElementById('status-inline-spinner').style.display = 'none';
        if (data.success) {
            // Update the select element's class
            const select = document.querySelector(`select[onchange*="${currentStudentId}"]`);
            select.className = `status status-${currentStatus}`;
            select.value = currentStatus;
            
            // Show success message in the status message element
            document.getElementById('statusMessage').innerHTML = '<span style="color: #28a745;">Status updated successfully!</span>';
            setTimeout(() => {
                closeStatusModal();
                // Optionally refresh the page or update the UI
                location.reload();
            }, 1500);
        } else {
            document.getElementById('statusMessage').innerHTML = '<span style="color: #dc3545;">Failed to update status: ' + (data.message || 'Unknown error') + '</span>';
        }
    })
    .catch(error => {
        document.getElementById('status-inline-spinner').style.display = 'none';
        console.error('Error:', error);
        document.getElementById('statusMessage').innerHTML = '<span style="color: #dc3545;">An error occurred while updating the status: ' + error.message + '</span>';
    });
}

function closeStatusModal() {
    document.getElementById("statusModal").classList.remove("show");
    // Reset fields
    document.getElementById('rejectionReason').value = '';
    document.getElementById('statusMessage').textContent = '';
    currentStudentId = '';
    currentStatus = '';
}

// Add event listener for the status modal close button
document.addEventListener('DOMContentLoaded', function() {
    const statusModalCloseBtn = document.querySelector('#statusModal .close');
    if (statusModalCloseBtn) {
        statusModalCloseBtn.addEventListener('click', closeStatusModal);
    }
});

function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const statusSelect = row.querySelector('.status');
        const statusCell = statusSelect ? statusSelect.value : '';
        const nameCell = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const refIdCell = row.querySelector('td:nth-child(1)').textContent.toLowerCase();

        // Special handling for needs_review which could be '0' in the database
        let statusMatch = status === '';
        if (status === 'needs_review') {
            statusMatch = statusCell === 'needs_review' || statusSelect.className.includes('status-needs_review');
        } else {
            statusMatch = statusCell === status;
        }

        const searchMatch = search === '' || 
                          nameCell.includes(search) || 
                          refIdCell.includes(search);

        row.style.display = statusMatch && searchMatch ? '' : 'none';
    });
}

// Enhance status dropdown interaction
document.querySelectorAll('.status').forEach(select => {
    select.addEventListener('change', function() {
        this.className = `status status-${this.value}`;
    });
});

// Add loading state to View Details button
function viewDetails(button, details) {
    const modal = document.getElementById("infoModal");
    
    // Populate fields
    document.getElementById("ref_id").textContent = details.ref_id;
    document.getElementById("first_name").textContent = details.first_name;
    document.getElementById("middle_name").textContent = details.middle_name;
    document.getElementById("last_name").textContent = details.last_name;
    document.getElementById("gender").textContent = details.gender;
    document.getElementById("dob").textContent = details.dob;
    document.getElementById("email").textContent = details.email;
    document.getElementById("contact").textContent = details.contact;
    document.getElementById("address").textContent = details.address;
    document.getElementById("student_type").textContent = details.student_type;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
    document.getElementById("prev_school").textContent = details.prev_school;
    document.getElementById("year_level").textContent = details.year_level;
    document.getElementById("prev_program").textContent = details.prev_program;
    document.getElementById("desired_program").textContent = details.desired_program;
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    document.getElementById("prev_school").textContent = details.previous_school_name || details.prev_school;
    document.getElementById("year_level").textContent = details.year_level;
    document.getElementById("prev_program").textContent = details.previous_program_name || details.prev_program;
    document.getElementById("desired_program").textContent = details.desired_program_name || details.desired_program;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    document.getElementById("is_tech").textContent = details.is_tech;
    
    // Hide Year Level if student is ladderized
    const yearLevelRow = document.getElementById("year_level_row");
    if (details.student_type && details.student_type.toLowerCase() === "ladderized") {
        yearLevelRow.style.display = "none";
    } else {
        yearLevelRow.style.display = "";
    }
    
    // Convert is_tech numeric value to descriptive text
    let techStatus = "Unknown";
    let techStatusClass = "";
    
    switch(parseInt(details.is_tech)) {
        case 0:
            techStatus = "Non-Tech";
            techStatusClass = "non-tech-badge";
            break;
        case 1:
            techStatus = "Tech";
            techStatusClass = "tech-badge";
            break;
        case 2:
            techStatus = "Ladderized";
            techStatusClass = "ladderized-badge";
            break;
        default:
            techStatus = "Unknown";
            techStatusClass = "unknown-badge";
    }
    
    // Create a badge element for the tech status
    const techElement = document.getElementById("is_tech");
    techElement.innerHTML = `<span class="tech-status-badge ${techStatusClass}">${techStatus}</span>`;

    // Set document previews
    document.getElementById("tor_preview").src = details.tor;
    document.getElementById("school_id_preview").src = details.school_id;
    
    // Handle admin notes if present
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
    const adminNotesSection = document.getElementById("admin-notes-section");
    if (details.admin_notes && details.admin_notes.trim() !== '') {
        // Clean up the admin notes to remove technical error details
        let cleanedNotes = details.admin_notes;
        
        // Remove specific technical error messages
        cleanedNotes = cleanedNotes.replace(/Error retrieving grading rules: Unknown column 'is_default' in 'where clause'/g, 
                                          "Grading system configuration issue. Please add this university to the system.");
        
        document.getElementById("admin_notes").textContent = cleanedNotes;
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    document.getElementById("admin_notes").textContent = "";
    const adminNotesSection = document.getElementById("admin-notes-section");
    if (details.admin_notes && details.admin_notes.trim() !== '') {
        document.getElementById("admin_notes").textContent = details.admin_notes;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
        adminNotesSection.style.display = "block";
    } else {
        adminNotesSection.style.display = "none";
    }

    // Show modal with the "show" class
    modal.classList.add("show");
    
    button.innerHTML = 'View Details';
    button.disabled = false;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

    // Hide Reference ID if status is pending or needs_review
    const status = details.status ? details.status.toLowerCase() : '';
    const refIdRow = document.getElementById("ref_id").parentElement;
    if (status === 'pending' || status === 'needs_review' || !details.ref_id) {
        refIdRow.style.display = "none";
    } else {
        refIdRow.style.display = "";
    }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
}
</script>
<script src="assets/js/admin-session.js"></script>

</body>
</html>
