<?php
session_start();
include('config/config.php');
include('get_exam.php'); // Include the file with exam functions

// Get all exams
$exams = getAllExams();

// Add this to your save_exam.php file where you handle file uploads
$upload_dir = 'uploads/covers/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Add this near the top of exam.php, after session_start()
function getImageUrl($imagePath) {
    if (empty($imagePath)) {
        return 'assets/images/default-exam-cover.jpg';
    }
    
    // If the path starts with 'uploads/', it's a relative path
    if (strpos($imagePath, 'uploads/') === 0) {
        return $imagePath;
    }
    
    // If it's an absolute path, convert it to relative
    $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $imagePath);
    return $relativePath;
}
?>
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
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            transition: 0.4s ease;
        }

        /* Card Styles */
        .exam-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .exam-card-header {
            position: relative;
            height: 160px;
            background-color: #f5f5f5;
            overflow: hidden;
        }
        
        .exam-card-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
            display: block;
        }
        
        .exam-card-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0) 100%);
            pointer-events: none;
        }
        
        .exam-card:hover .exam-card-header img {
            transform: scale(1.05);
        }
        
        .exam-type {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(212, 99, 99, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 1;
        }
        
        .exam-card-body {
            padding: 15px;
            flex-grow: 1;
        }

        .exam-card h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            color: #333;
        }

        .exam-card-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .exam-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .exam-card-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: #666;
        }
        
        .exam-card-meta-item .material-symbols-rounded {
            font-size: 1rem;
        }
        
        .scheduled-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #e6f7ff;
            color: #0070c0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .exam-card .actions {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }

        .exam-card .actions a {
            text-decoration: none;
            color: #75343A;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .exam-card .actions a.delete {
            color: #dc3545;
        }
        
        .exam-card .actions a:hover {
            text-decoration: underline;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .empty-state h3 {
            margin-bottom: 15px;
            color: #6c757d;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        /* Question count badge */
        .question-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #555;
        }

        /* Search and Filter Styles */
        .search-filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
            display: flex;
        }

        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #75343A;
        }

        .search-box button {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            padding: 0 15px;
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .search-box button:hover {
            color: #75343A;
        }

        .filter-box {
            min-width: 150px;
        }

        .filter-box select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background-color: white;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .filter-box select:focus {
            outline: none;
            border-color: #75343A;
        }

        /* Add this for no results message */
        .no-results {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            grid-column: 1 / -1;
        }

        .no-results p {
            color: #666;
            margin-bottom: 15px;
        }

        .no-results button {
            background: none;
            border: none;
            color: #75343A;
            text-decoration: underline;
            cursor: pointer;
            padding: 5px 10px;
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
            <i class="fas fa-users"></i> Exams
        </h2><br>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <a href="quiz_editor.php?new=1" class="create-btn">+ Create Exam</a>

        <!-- Add this right after the create-btn and before the exam-container -->
        <div class="search-filter-container">
            <div class="search-box">
                <input type="text" id="examSearch" placeholder="Search exams...">
                <button type="button" id="searchButton">
                    <span class="material-symbols-rounded">search</span>
                </button>
            </div>
            <div class="filter-box">
                <select id="examTypeFilter">
                    <option value="">All Exam Types</option>
                    <option value="tech">Tech</option>
                    <option value="non-tech">Non-Tech</option>
                </select>
            </div>
        </div>

        <!-- Card View for Exams -->
        <div class="exam-container">
            <?php if (empty($exams)): ?>
                <div class="empty-state">
                    <h3>No exams found</h3>
                    <p>Create your first exam by clicking the "Create Exam" button above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($exams as $exam): 
                    // Get question count for this exam
                    $query = "SELECT COUNT(*) as question_count FROM questions WHERE exam_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $exam['exam_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $question_count = $result->fetch_assoc()['question_count'];
                ?>
                    <div class="exam-card">
                        <div class="exam-card-header">
                            <?php if (!empty($exam['cover_image']) && file_exists($exam['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($exam['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($exam['title']); ?>"
                                     onerror="this.src='assets/images/default-exam-cover.jpg'">
                            <?php else: ?>
                                <img src="assets/images/default-exam-cover.jpg" 
                                     alt="Default exam cover">
                            <?php endif; ?>
                            <div class="exam-type"><?php echo ucfirst(htmlspecialchars($exam['exam_type'])); ?></div>
                        </div>
                        
                        <div class="exam-card-body">
                            <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                            
                            <?php if (!empty($exam['description'])): ?>
                                <p class="exam-card-description"><?php echo htmlspecialchars($exam['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="exam-card-meta">
                                <div class="exam-card-meta-item">
                                    <span class="material-symbols-rounded">calendar_today</span>
                                    <?php echo date('M d, Y', strtotime($exam['created_at'])); ?>
                                </div>
                                
                                <div class="exam-card-meta-item">
                                    <span class="material-symbols-rounded">quiz</span>
                                    <?php echo $question_count; ?> question<?php echo $question_count != 1 ? 's' : ''; ?>
                                </div>
                            </div>
                            
                            <?php if ($exam['is_scheduled'] && $exam['scheduled_date']): ?>
                                <div class="scheduled-badge">
                                    <span class="material-symbols-rounded">event</span>
                                    Scheduled: <?php echo date('M d, Y - h:i A', strtotime($exam['scheduled_date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="actions">
                            <a href="quiz_editor.php?exam_id=<?php echo $exam['exam_id']; ?>">
                                <span class="material-symbols-rounded">edit</span> Edit
                            </a>
                            <a href="#" class="delete" onclick="deleteExam(<?php echo $exam['exam_id']; ?>)">
                                <span class="material-symbols-rounded">delete</span> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Exam Form (hidden) -->
<form id="deleteExamForm" action="delete_exam.php" method="POST" style="display: none;">
    <input type="hidden" name="exam_id" id="delete_exam_id">
</form>

<script src="assets/js/side.js"></script>
<script>
function deleteExam(examId) {
    if (confirm('Are you sure you want to delete this exam? This action cannot be undone.')) {
        document.getElementById('delete_exam_id').value = examId;
        document.getElementById('deleteExamForm').submit();
    }
}

// Search and Filter Functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('examSearch');
    const searchButton = document.getElementById('searchButton');
    const typeFilter = document.getElementById('examTypeFilter');
    const examCards = document.querySelectorAll('.exam-card');
    const examContainer = document.querySelector('.exam-container');

    function filterExams() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = typeFilter.value.toLowerCase();
        let hasResults = false;

        examCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const description = card.querySelector('.exam-card-description')?.textContent.toLowerCase() || '';
            const type = card.querySelector('.exam-type').textContent.toLowerCase();
            
            const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
            const matchesType = selectedType === '' || type === selectedType;
            
            if (matchesSearch && matchesType) {
                card.style.display = 'flex';
                hasResults = true;
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide no results message
        const existingNoResults = document.querySelector('.no-results');
        if (existingNoResults) {
            existingNoResults.remove();
        }

        if (!hasResults) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results';
            noResults.innerHTML = `
                <p>No exams found matching your criteria</p>
                <button onclick="clearFilters()">Clear filters</button>
            `;
            examContainer.appendChild(noResults);
        }
    }

    // Event listeners
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            filterExams();
        }
    });

    searchButton.addEventListener('click', filterExams);
    typeFilter.addEventListener('change', filterExams);

    // Check for success message in sessionStorage
    const successMessage = sessionStorage.getItem('examSuccess');
    if (successMessage) {
        // Create success alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success';
        alertDiv.textContent = successMessage;
        
        // Insert alert at the top of the main content
        const mainContent = document.querySelector('.main');
        const pageTitle = mainContent.querySelector('.page-title');
        mainContent.insertBefore(alertDiv, pageTitle.nextSibling);
        
        // Remove message from sessionStorage
        sessionStorage.removeItem('examSuccess');
        
        // Automatically remove the alert after 5 seconds
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            alertDiv.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    }
});

// Function to clear filters
function clearFilters() {
    document.getElementById('examSearch').value = '';
    document.getElementById('examTypeFilter').value = '';
    
    // Show all exam cards
    document.querySelectorAll('.exam-card').forEach(card => {
        card.style.display = 'flex';
    });
    
    // Remove no results message if it exists
    const noResults = document.querySelector('.no-results');
    if (noResults) {
        noResults.remove();
    }
}
</script>
</body>
</html>
