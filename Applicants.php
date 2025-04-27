<?php
    session_start(); // Start session if needed
    include('config/config.php');

    // Check if admin is logged in
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header("Location: admin_login.php");
        exit();
    }

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
    $query = "SELECT * FROM register_studentsqe 
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
    margin: 70px auto;
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
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 20px;
    padding-left: 30px;
    font-size: 15px;
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
    width: 140px;
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
                <th>Reference ID</th>
                <th>Name</th>
                <th>Student Type</th>
                <th>Email</th>
                <th>Registration Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows == 0): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #666;">
                        No registered students found.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['reference_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($row['registration_date'])); ?></td>
                        <td>
                            <select class="status status-<?php echo strtolower($row['status']); ?>" 
                                    onchange="confirmStatusChange(this, '<?php echo $row['reference_id']; ?>')"
                                    <?php echo ($row['status'] !== 'pending') ? 'disabled' : ''; ?>>
                                <option value="pending" <?php echo $row['status'] == 'pending' ? 'selected' : ''; ?> disabled>Pending</option>
                                <option value="accepted" <?php echo $row['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="rejected" <?php echo $row['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
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
                                school_id: '<?php echo $row['school_id']; ?>'
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

        <!-- Personal Information -->
        <h5 class="text-primary">Personal Information</h5>
        <div class="row">
            <div class="col-md-4">
                <p><strong>Last Name:</strong> <span id="last_name"></span></p>
                <p><strong>Reference ID:</strong> <span id="ref_id"></span></p>
                <p><strong>Email:</strong> <span id="email"></span></p>
            </div>
            <div class="col-md-4">
                <p><strong>First Name:</strong> <span id="first_name"></span></p>
                <p><strong>Date of Birth:</strong> <span id="dob"></span></p>
                <p><strong>Contact:</strong> <span id="contact"></span></p>
            </div>
            <div class="col-md-4">
                <p><strong>Middle Name:</strong> <span id="middle_name"></span></p>
                <p><strong>Gender:</strong> <span id="gender"></span></p>
                <p><strong>Address:</strong> <span id="address"></span></p>
            </div>
        </div>

        <!-- Academic Information -->
        <h5 class="text-primary">Academic Information</h5>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Student Type:</strong> <span id="student_type"></span></p>
                <p><strong>Tech Student:</strong> <span id="is_tech"></span></p>
            </div>
            <div class="col-md-6">
                <p><strong>Previous Program:</strong> <span id="prev_program"></span></p>
                <p><strong>Desired Program:</strong> <span id="desired_program"></span></p>
            </div>
            <div class="col-md-6">
                <p><strong>Previous School:</strong> <span id="prev_school"></span></p>
                <p><strong>Year Level:</strong> <span id="year_level"></span></p> 
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
                <p id="statusMessage"></p>
                <div id="reasonField" style="display: none; margin-bottom: 20px;">
                    <label for="rejectionReason" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500;">Reason for Rejection:</label>
                    <textarea id="rejectionReason" 
                             rows="4" 
                             style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"
                             placeholder="Please provide a reason for rejecting this application..."></textarea>
                    <small style="color: #666; margin-top: 5px; display: block;">This reason will be visible to the student.</small>
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
let currentReferenceId = '';
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
    document.getElementById("prev_school").textContent = details.prev_school;
    document.getElementById("year_level").textContent = details.year_level;
    document.getElementById("prev_program").textContent = details.prev_program;
    document.getElementById("desired_program").textContent = details.desired_program;
    document.getElementById("is_tech").textContent = details.is_tech;

    // Set document previews
    document.getElementById("tor_preview").src = details.tor;
    document.getElementById("school_id_preview").src = details.school_id;

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

// Function to open the status update modal and display the appropriate message
function confirmStatusChange(selectElement, referenceId) {
    currentStatus = selectElement.value;
    currentReferenceId = referenceId;

    // Show the confirmation message based on the status
    let confirmationMessage = `Are you sure you want to mark this student as ${currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)}?`;
    document.getElementById('statusMessage').textContent = confirmationMessage;

    // Show the rejection reason field only if the status is "Rejected"
    if (currentStatus === 'rejected') {
        document.getElementById('reasonField').style.display = 'block';
    } else {
        document.getElementById('reasonField').style.display = 'none';
    }

    // Show the modal
    document.getElementById('statusModal').classList.add('show');
}

// Function to close the modal
function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('show');
    document.getElementById('rejectionReason').value = ''; // Clear rejection reason

     // Reset status to "Pending" when canceling the modal
     if (currentStatus !== 'confirmed') {
        const selectElement = document.querySelector(`select[onchange*="${currentReferenceId}"]`);
        if (selectElement) {
            selectElement.value = 'pending'; // Reset the dropdown to "Pending"
            selectElement.className = "status status-pending";
        }
    }
}

// Function to handle the status update when confirmed
function confirmStatusUpdate() {
    let rejectionReason = '';

    // If the status is "rejected", ensure a reason is provided
    if (currentStatus === 'rejected') {
        rejectionReason = document.getElementById('rejectionReason').value;
        if (!rejectionReason.trim()) {
            alert('Please provide a reason for rejection.');
            return;
        }
    }

    // Prepare the data to be sent to the backend
    const formData = new FormData();
    formData.append('reference_id', currentReferenceId);
    formData.append('status', currentStatus);

    if (currentStatus === 'rejected') {
        formData.append('rejection_reason', rejectionReason);
    }

    // Send the update request via fetch
    fetch('update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showModal('Status updated successfully!');
            location.reload();  // Refresh the page to reflect the updated status
        } else {
            alert(`Error: ${data.message}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the status.');
    });

    // Close the modal after the update
    closeStatusModal();
}

// Function to close the modal when clicking outside the modal
window.onclick = function(event) {
    const modal = document.getElementById("statusModal");
    if (event.target === modal) {
        closeStatusModal();
    }
};

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById("infoModal");
    if (event.target === modal) {
        closeModal();
    }
};

// Close modal function
function closeModal() {
    document.getElementById("infoModal").classList.remove("show");
}

// Add event listener for the modal close button
document.addEventListener('DOMContentLoaded', function() {
    const closeModalBtn = document.querySelector('#statusModal .close');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeStatusModal);
    }
});

function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const statusCell = row.querySelector('.status').value;
        const nameCell = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const refIdCell = row.querySelector('td:nth-child(1)').textContent.toLowerCase();

        const statusMatch = status === '' || statusCell === status;
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

document.getElementById('viewDetailsButton').addEventListener('click', function() {
    showDetails(); // Function to display the details
});

// Add loading state to View Details button
function viewDetails(button, details) {
    button.innerHTML = '<span class="spinner"></span> Loading...';
    button.disabled = true;
    
    openModal(details);
    
    setTimeout(() => {
        button.innerHTML = 'View Details';
        button.disabled = false;
    }, 500);
}

// Function to show the success modal pop-up
function showModal(message) {
    // Create the modal structure dynamically
    const modal = document.createElement('div');
    modal.classList.add('modal');
    
    const modalContent = document.createElement('div');
    modalContent.classList.add('modal-content');
    
    const modalMessage = document.createElement('p');
    modalMessage.textContent = message;
    
    const closeButton = document.createElement('button');
    closeButton.textContent = 'Close';
    closeButton.classList.add('close-button');
    closeButton.onclick = function() {
        document.body.removeChild(modal); // Close the modal when the button is clicked
    };

    modalContent.appendChild(modalMessage);
    modalContent.appendChild(closeButton);
    modal.appendChild(modalContent);

    document.body.appendChild(modal);
}

// Styling for the modal
const style = document.createElement('style');
style.textContent = `
    .modal {
        display: flex;
        justify-content: center;
        align-items: center;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
    }
    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: center;
        max-width: 400px;
        width: 80%;
    }
    .close-button {
        background-color: #007BFF;
        color: white;
        border: none;
        padding: 10px;
        cursor: pointer;
        border-radius: 5px;
        margin-top: 10px;
    }
    .close-button:hover {
        background-color: #0056b3;
    }
`;
</script>

</body>
</html>
