<?php
    session_start(); // Start session if needed
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
    background: #062575 ;
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

/* Status Dropdown */
.status {
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background-color: white;
    cursor: pointer;
}

/* View Button */
.view-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.3s;
}

.view-btn:hover {
    background: #218838;
}

/* Modal Background */
.modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    padding: 20px;
    width: 80%;
    max-width: 800px;
    height: 80vh;
    max-height: 600px;
    overflow-y: auto;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.modal.show {
    display: flex;
    opacity: 1;
}

.modal-content {
    position: relative;
    padding: 20px;
    border-radius: 10px;
    text-align: left;
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
    background: #062575;
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


    </style>
</head>
<body>

<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="main">
    <h2 class="page-title registered-students-title">
        <i class="fas fa-users"></i> Student Tracker
    </h2>
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
                        <select class="status">
                            <option value="pending">Enrolled</option>
                            <option value="accepted">Not - Enrolled</option>
                        </select>
                    </td>
                    <td><button class="btn btn-primary btn-sm" onclick="openModal({
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
                </div>
                <div class="text-center mt-2">
                    <button class="btn btn-primary btn-sm" onclick="viewDocument('tor')">View Full Size</button>
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
                    <button class="btn btn-primary btn-sm" onclick="viewDocument('school_id')">View Full Size</button>
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
</script>

</body>
</html>
