<nav class="stud-navbar">
    <div class="navbar-container">
        <div class="navbar-logo">
            <img src="img/streams-logo.png" alt="PUP Logo">
            <div class="logo-text">
                <h1>STREAMS</h1>
                <p>Student Dashboard</p>
            </div>
        </div>
        <button class="navbar-toggle" id="navbarToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="navbar-links" id="navbarLinks">
            <a href="stud_dashboard.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">dashboard</span> Dashboard
            </a>
            <a href="exam_instructions.php" class="<?php echo $activePage == 'take_exam' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">quiz</span> Take Exam
            </a>
            <a href="exam_registration_status.php" class="<?php echo $activePage == 'registration' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">app_registration</span> Exam Registration Status
            </a>
            <a href="stud_result.php" class="<?php echo $activePage == 'results' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">grade</span> Exam Results
            </a>
            <a href="stud_profile.php" class="<?php echo $activePage == 'profile' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">person</span> Profile
            </a>
            <a href="stud_logout.php">
                <span class="material-symbols-rounded">logout</span> Logout
            </a>
        </div>
        <div class="navbar-profile">
            <a href="#" id="profile-menu">
                <div class="profile-icon">
                    <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                        <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($_SESSION['firstname'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
            </a>
            <div class="dropdown-menu">
                <a href="stud_dashboard.php" class="dropdown-item">
                    <span class="material-symbols-rounded">dashboard</span>
                    Dashboard
                </a>
                <a href="stud_profile.php" class="dropdown-item">
                    <span class="material-symbols-rounded">person</span>
                    Profile
                </a>
                <a href="stud_logout.php" class="dropdown-item">
                    <span class="material-symbols-rounded">logout</span>
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav> 