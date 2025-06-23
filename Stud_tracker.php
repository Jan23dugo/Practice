<?php
    // Include admin session management
    require_once 'config/admin_session.php';
    
    // Check admin session and handle timeout
    checkAdminSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Linking Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style> 
 /* Apply styles ONLY to the "Registered Students" title */
 .registered-students-title {
    font-size: 22px;
    font-weight: bold;
    color: #0a192f; /* Dark blue to match the table */
    text-align: left;
    padding: 10px 0;
    
}
   /* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
}

/* Table Header */
th {
    background: #75343A ;
    color: white;
    padding: 12px;
    text-align: left;
    
}

/* Table Rows */
td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

/* Alternate Row Color */
tbody tr:nth-child(even) {
    background-color: #FCFCFC ;
}

/* Hover Effect */
tbody tr:hover {
    background-color: #f1f1f1;
}

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

.status-enrolled {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.status-not-enrolled {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

/* Modal Background */
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

.modal-content h5 {
    color: #800000;
    font-size: 1.4rem;
    font-weight: 600;
    margin: 25px 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #800000;
}

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

.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
}

h5 {
    margin-bottom: 15px;
    font-weight: bold;
}

.row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.col-md-4, .col-md-6 {
    flex: 1;
    min-width: 250px;
}

.card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.card-header {
    background: #75343A;
    color: white;
    padding: 8px;
    font-weight: bold;
    text-align: center;
}

.doc-preview {
    height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
}

.doc-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.view-details-btn {
    background: #75343A;
    color: white;
    border: none;
    padding: 6px 12px;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.3s;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-details-btn:hover {
    background:rgb(255, 229, 231);
    color: #75343A;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.text-primary {
    color: #75343A;
    font-weight: bold;
    font-size: 20px;
}

.text-center {
    text-align: center;
    margin-top: 20px;
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

.page-title {
    font-size: 36px;
    color: #75343A;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-shadow: 0 1px 1px rgba(0,0,0,0.1);
    border-bottom: 2px solid #f0f0f0;
}

    </style>
</head>
<body>

<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
    <h2 class="page-title registered-students-title">
        <i class="fas fa-users"></i> Student Tracker
    </h2>

    <div class="filter-section">
        <select name="status" id="statusFilter" onchange="applyFilters()">
            <option value="">All Status</option>
            <option value="enrolled">Enrolled</option>
            <option value="not-enrolled">Not Enrolled</option>
        </select>
        <input type="text" 
               id="searchInput" 
               placeholder="Search by name or email"
               oninput="applyFilters()">
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Student Type</th>
                <th>Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Juan Dela Cruz</td>
                <td>Transferee</td>
                <td>juan@email.com</td>
                <td>
                    <select class="status status-enrolled" onchange="updateStatusClass(this)">
                        <option value="enrolled" selected>Enrolled</option>
                        <option value="not-enrolled">Not - Enrolled</option>
                    </select>
                </td>
                <td><button class="view-details-btn" onclick="openModal({
        ref_id: '20241234',
        first_name: 'Juan',
        middle_name: 'Dela',
        last_name: 'Cruz',
        gender: 'Male',
        dob: '1999-05-21',
        email: 'juan@email.com',
        contact: '09123456789',
        address: 'Manila, Philippines',
        student_type: 'Transferee',
        prev_school: 'ABC University',
        year_level: '3rd Year',
        prev_program: 'BS Computer Science',
        desired_program: 'BS Information Systems',
        is_tech: 'Yes',
        tor: 'img/tor/tor.png',
        school_id: 'img/school_id/school_id.png'
    })">
        View Details
    </button>
</td>
            </tr>
        </tbody>
    </table>
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
                <p><strong>Address:</strong> <span id="address"></span></p>
            </div>
            <div class="col-md-4">
                <p><strong>Gender:</strong> <span id="gender"></span></p>
                <p><strong>Date of Birth:</strong> <span id="dob"></span></p>
                <p><strong>Email:</strong> <span id="email"></span></p>
                <p><strong>Contact:</strong> <span id="contact"></span></p>
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
                </div>
                <div class="text-center mt-2">
                    <button class="view-details-btn" onclick="viewDocument('tor')">View Full Size</button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">School ID</div>
                    <div class="card-body doc-preview">
                        <img id="school_id_preview" src="" alt="School ID">
                    </div>
                </div>
                <div class="text-center mt-2">
                    <button class="view-details-btn" onclick="viewDocument('school_id')">View Full Size</button>
                </div>
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
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const statusCell = row.querySelector('.status').value;
        const nameCell = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
        const emailCell = row.querySelector('td:nth-child(3)').textContent.toLowerCase();

        const statusMatch = status === '' || statusCell === status;
        const searchMatch = search === '' || 
                          nameCell.includes(search) || 
                          emailCell.includes(search);

        row.style.display = statusMatch && searchMatch ? '' : 'none';
    });
}

// Function to update status class when changed
function updateStatusClass(selectElement) {
    selectElement.className = `status status-${selectElement.value}`;
}
</script>

</body>
</html>
