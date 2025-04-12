<Style>
    .top-nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    transition: all 0.3s ease;
}

.nav-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    background: #75343A;
    color: white;
}

.header-logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: white;
}

.mobile-nav-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

.nav-container {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 2rem;
    background: white;
}

.nav-list {
    display: flex;
    list-style: none;
    gap: 1rem;
    margin: 0;
    padding: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: #666;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: #f5f5f5;
    color: #75343A;
}

@media (max-width: 992px) {
    .mobile-nav-toggle {
        display: block;
    }

    .nav-container {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        padding: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .nav-container.active {
        display: block;
    }

    .nav-list {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<body>
    <aside class="sidebar">
        <!-- Sidebar Header -->
        <header class="sidebar-header">
            <a href="#" class="header-logo">
                <img src="img/Logo.png" alt="CodingGujarat">
            </a>
            <button class="toggler sidebar-toggler">
                <span class="nav-icon material-symbols-rounded">
                    chevron_left
                </span>
            </button>
            <button class="toggler menu-toggler">
                <span class="nav-icon material-symbols-rounded">
                    menu
                </span>
            </button>
        </header>

        <nav class="sidebar-nav">
            <!-- Primary-nav -->
            <ul class="nav-list primary-nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">
                            dashboard
                        </span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                    <span class="nav-tooltip">Dashboard</span>
                </li>
                <li class="nav-item has-dropdown">
                    <a href="#" class="nav-link dropdown-toggle">
                        <span class="nav-icon material-symbols-rounded">
                            group
                        </span>
                        <span class="nav-label">Students</span>
                        <span class="dropdown-arrow material-symbols-rounded">chevron_right</span>
                    </a>
                    <span class="nav-tooltip">Students</span>
                    <ul class="dropdown-menu">
                    <li class="nav-item">
                            <a href="Applicants.php" class="nav-link">
                                <span class="nav-label">Applicants</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="Qualified_stud.php" class="nav-link">
                                <span class="nav-label">Qualified Students</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="Stud_tracker.php" class="nav-link">
                                <span class="nav-label">Student Tracker</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item has-dropdown">
                    <a href="#" class="nav-link dropdown-toggle">
                        <span class="nav-icon material-symbols-rounded">
                            group
                        </span>
                        <span class="nav-label">Exam</span>
                        <span class="dropdown-arrow material-symbols-rounded">chevron_right</span>
                    </a>
                    <span class="nav-tooltip">Exam</span>
                    <ul class="dropdown-menu">
                    <li class="nav-item">
                            <a href="exam.php" class="nav-link">
                                <span class="nav-label">Create Exam</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="question_bank.php" class="nav-link">
                                <span class="nav-label">Question Bank</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="manage_results.php" class="nav-link">
                                <span class="nav-label">Exam Results</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="calendar.php" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">
                            calendar_today
                        </span>
                        <span class="nav-label">Calendar</span>
                    </a>
                    <span class="nav-tooltip">Calendar</span>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">
                            notifications
                        </span>
                        <span class="nav-label">Notification</span>
                    </a>
                    <span class="nav-tooltip">Notification</span>
                </li>
                <li class="nav-item">
                    <a href="analytics.php" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">
                            analytics
                        </span>
                        <span class="nav-label">Analytics</span>
                    </a>
                    <span class="nav-tooltip">Analytics</span>
                </li>
                <li class="nav-item">
                    <a href="announcement.php" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">
                            announcement
                        </span>
                        <span class="nav-label">Announcement</span>
                    </a>
                    <span class="nav-tooltip">Announcement</span>
                </li>
            </ul>
            <!-- Secondary-Nav -->
            <ul class="nav-list secondary-nav">
                <li class="nav-item">
                    <a href="admin_profile.php" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">
                            account_circle
                        </span>
                        <span class="nav-label">Profile</span>
                    </a>
                    <span class="nav-tooltip">Profile</span>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">
                            logout
                        </span>
                        <span class="nav-label">Logout</span>
                    </a>
                    <span class="nav-tooltip">Logout</span>
                </li>
            </ul>
        </nav>
    </aside>
  
</body>
