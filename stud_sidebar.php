<aside class="sidebar" id="sidebar">
    <div class="sidebar-profile">
        <div class="profile-image">
            <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                <img src="<?php echo $student['profile_picture']; ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
            <?php else: ?>
                <?php echo substr($_SESSION['firstname'], 0, 1); ?>
            <?php endif; ?>
        </div>
        <h3><?php echo $_SESSION['firstname'] . ' ' . $_SESSION['lastname']; ?></h3>
        <p>Student</p>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="stud_dashboard.php" class="<?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">dashboard</span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="exam_instructions.php" class="<?php echo $activePage == 'take_exam' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">quiz</span>
                Take Exam
            </a>
        </li>
        <li>
            <a href="exam_registration_status.php" class="<?php echo $activePage == 'registration' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">app_registration</span>
                Exam Registration Status
            </a>
        </li>
        <li>
            <a href="stud_result.php" class="<?php echo $activePage == 'results' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">grade</span>
                Exam Results
            </a>
        </li>
        <li>
            <a href="stud_profile.php" class="<?php echo $activePage == 'profile' ? 'active' : ''; ?>">
                <span class="material-symbols-rounded">person</span>
                Profile
            </a>
        </li>
        <li>
            <a href="stud_logout.php">
                <span class="material-symbols-rounded">logout</span>
                Logout
            </a>
        </li>
    </ul>
</aside> 