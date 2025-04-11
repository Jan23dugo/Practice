<?php
    session_start(); // Start session if needed
    include('config/config.php');

    // Check if user is logged in as admin
//if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Not logged in as admin, redirect to admin login page
//    header("Location: admin_login.php");
//    exit();
//}
    // Fetch all qualified students (with accepted status)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 10;
    $offset = ($page - 1) * $records_per_page;

    // Get total number of qualified records
    $total_query = "SELECT COUNT(*) as total FROM register_studentsqe WHERE status = 'accepted'";
    $total_result = $conn->query($total_query);
    $total_records = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch qualified students with pagination
    $query = "SELECT * FROM register_studentsqe 
              WHERE status = 'accepted'
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
    <title>Qualified Students</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Linking Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style> 
 /* Apply styles ONLY to the "Qualified Students" title */
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

/* View Button */
.view-btn {
    background: #2c3e50;  /* Darker blue */
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
    background: #34495e;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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
    display: block;
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

/* Card Headers */
.card-header {
    background: #800000;
    color: white;
    padding: 12px;
    font-weight: 600;
    text-align: center;
    font-size: 16px;
}

/* Document View Button */
.doc-view-btn {
    background-color: #007BFF; /* Primary Blue color */
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
    background-color: #0056b3; /* Darker blue on hover */
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
}

/* Filter Section */
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

.filter-section input {
    padding: 10px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    width: 300px;
}

.filter-section input:focus {
    border-color: #75343A;
    outline: none;
    box-shadow: 0 0 0 3px rgba(117, 52, 58, 0.1);
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
    text-decoration: none;
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

/* View Accredited Subjects Button */
.subjects-btn {
    background: #006400;  /* Dark Green */
    color: white;
    border: none;
    padding: 8px 16px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-left: 5px;
}

.subjects-btn:hover {
    background: #008000;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Accredited Subjects Modal Table */
.subjects-table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
}

.subjects-table th {
    background: #75343A;
    color: white;
    padding: 12px;
    text-align: left;
    font-size: 14px;
}

.subjects-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
}

.subjects-table tr:nth-child(even) {
    background-color: #f8f9fa;
}

.no-subjects-message {
    text-align: center;
    padding: 30px;
    color: #666;
    font-style: italic;
}

/* Loading State */
.loading-subjects {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(117, 52, 58, 0.2);
    border-radius: 50%;
    border-top-color: #75343A;
    animation: spin 1s linear infinite;
}

    </style>
</head>
<body>

<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
    <h2 class="page-title registered-students-title">
        <i class="fas fa-users"></i> Qualified Students
    </h2>

    <div class="filter-section">
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
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows == 0): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
                        No qualified students found.
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
                                student_id: '<?php echo $row['student_id']; ?>'
                            })">View Details</button>
                            <button class="subjects-btn" onclick="viewAccreditedSubjects(<?php echo $row['student_id']; ?>)">View Accredited Subjects</button>
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
                <p><strong>Year Level:</strong> <span id="year_level"></span></p>
            </div>
            <div class="col-md-6">
                <p><strong>Previous Program:</strong> <span id="prev_program"></span></p>
                <p><strong>Desired Program:</strong> <span id="desired_program"></span></p>
                <p><strong>Tech Student:</strong> <span id="is_tech"></span></p>
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

<div id="subjectsModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSubjectsModal()">&times;</span>
        <h5 class="text-primary">Accredited Subjects</h5>
        <div id="subjectsContent">
            <div class="loading-subjects">
                <div class="loading-spinner"></div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/side.js"></script>
<script>
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
};

// Function to view documents in full size
function viewDocument(docType) {
    let imageSrc = document.getElementById(docType + "_preview").src;
    if (imageSrc) {
        window.open(imageSrc, "_blank");
    }
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const nameCell = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const refIdCell = row.querySelector('td:nth-child(1)').textContent.toLowerCase();

        const searchMatch = search === '' || 
                          nameCell.includes(search) || 
                          refIdCell.includes(search);

        row.style.display = searchMatch ? '' : 'none';
    });
}

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

function closeSubjectsModal() {
    document.getElementById("subjectsModal").classList.remove("show");
}

function viewAccreditedSubjects(studentId) {
    const modal = document.getElementById("subjectsModal");
    const content = document.getElementById("subjectsContent");
    
    // Show loading spinner
    content.innerHTML = '<div class="loading-subjects"><div class="loading-spinner"></div></div>';
    
    // Display the modal
    modal.classList.add("show");
    
    // Fetch accredited subjects data
    fetch('get_accredited_subjects.php?student_id=' + studentId)
        .then(response => response.json())
        .then(data => {
            // Check if data is an array
            if (!Array.isArray(data)) {
                // Handle error response or convert to array if needed
                if (data.error) {
                    // If we received an error object
                    content.innerHTML = `<div class="no-subjects-message">Error: ${data.error}</div>`;
                } else {
                    // Force it to be an empty array if it's something else
                    data = [];
                    content.innerHTML = '<div class="no-subjects-message">Invalid data format received. No subjects available.</div>';
                }
                return;
            }

            if (data.length === 0) {
                content.innerHTML = '<div class="no-subjects-message">No accredited subjects found for this student.</div>';
            } else {
                let tableHTML = `
                    <table class="subjects-table">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Original Code</th>
                                <th>Subject Description</th>
                                <th>Units</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.forEach(subject => {
                    tableHTML += `
                        <tr>
                            <td>${subject.subject_code}</td>
                            <td>${subject.original_code || '-'}</td>
                            <td>${subject.subject_description}</td>
                            <td>${subject.units}</td>
                            <td>${subject.grade}</td>
                        </tr>
                    `;
                });
                
                tableHTML += '</tbody></table>';
                content.innerHTML = tableHTML;
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="no-subjects-message">Error loading accredited subjects. Please try again.</div>';
            console.error('Error fetching accredited subjects:', error);
        });
}

// Add this to the existing window.onclick function
window.onclick = function (event) {
    const infoModal = document.getElementById("infoModal");
    const subjectsModal = document.getElementById("subjectsModal");
    
    if (event.target === infoModal) {
        closeModal();
    }
    
    if (event.target === subjectsModal) {
        closeSubjectsModal();
    }
};
</script>

</body>
</html>
