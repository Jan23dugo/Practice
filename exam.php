

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam List</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style> 
        .create-btn {
            background: #75343A;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s ease;
            display: inline-block;
            margin-bottom: 15px;
        }
        .create-btn:hover {
            background: #5c2a2f;
        }

        /* Grid Layout for Cards */
        .exam-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            transition: 0.4s ease;
        }

        /* Card Styles */
        .exam-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            transition: 0.3s ease;
        }

        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .exam-card h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            color: #333;
        }

        .exam-card p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .exam-card .actions a {
            text-decoration: none;
            color: #75343A;
            font-weight: bold;
            margin-right: 10px;
        }

        .exam-card .actions a.delete {
            color: red;
        }

    </style>
</head>
<body>
<div class="container">
<?php include 'sidebar.php'; ?>
    <div class="main">
        <h2 class="page-title registered-students-title">
            <i class="fas fa-users"></i> Exams
        </h2>
        <a href="create_exam.php" class="create-btn">+ Create Exam</a>

        <!-- Card View for Exams -->
        <div class="exam-container">
            <!-- Sample Cards -->
            <div class="exam-card">
                <h3>Computer Science Entrance Exam</h3>
                <p>Date Created: 2024-02-28</p>
                <div class="actions">
                    <a href="#">Edit</a> | 
                    <a href="#" class="delete" onclick="return confirm('Are you sure you want to delete this exam?')">Delete</a>
                </div>
            </div>

            <div class="exam-card">
                <h3>Programming Fundamentals Test</h3>
                <p>Date Created: 2024-02-27</p>
                <div class="actions">
                    <a href="#">Edit</a> | 
                    <a href="#" class="delete" onclick="return confirm('Are you sure you want to delete this exam?')">Delete</a>
                </div>
            </div>

            <div class="exam-card">
                <h3>Database Management Exam</h3>
                <p>Date Created: 2024-02-25</p>
                <div class="actions">
                    <a href="#">Edit</a> | 
                    <a href="#" class="delete" onclick="return confirm('Are you sure you want to delete this exam?')">Delete</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/side.js"></script>
</body>
</html>
