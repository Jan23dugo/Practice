<?php
// Include database connection if not already included
if (!isset($conn)) {
    include('config/config.php');
}

// Fetch exam results
$results_query = "
SELECT 
    e.exam_id,
    e.title AS exam_title,
    COUNT(DISTINCT ea.student_id) AS total_students,
    SUM(CASE WHEN ea.passed = 1 THEN 1 ELSE 0 END) AS pass_count,
    SUM(CASE WHEN ea.passed = 0 THEN 1 ELSE 0 END) AS fail_count,
    AVG(ea.final_score) AS average_score,
    MAX(ea.final_score) AS highest_score,
    MIN(ea.final_score) AS lowest_score
FROM exams e
JOIN exam_assignments ea ON e.exam_id = ea.exam_id
WHERE ea.completion_status = 'completed'";

if ($selected_exam_id) {
    $results_query .= " AND e.exam_id = " . $selected_exam_id;
}

$results_query .= "
GROUP BY e.exam_id
ORDER BY e.exam_id DESC
LIMIT 10";

$results_result = $conn->query($results_query);
$exam_results = [];

if ($results_result && $results_result->num_rows > 0) {
    while ($row = $results_result->fetch_assoc()) {
        $total_students = (int)$row['total_students'];
        $pass_count = (int)$row['pass_count'];
        
        $exam_results[] = [
            'exam_id' => $row['exam_id'],
            'exam_title' => $row['exam_title'],
            'total_students' => $total_students,
            'pass_count' => $pass_count,
            'fail_count' => (int)$row['fail_count'],
            'pass_rate' => $total_students > 0 ? round(($pass_count / $total_students) * 100) : 0,
            'average_score' => round($row['average_score'], 1),
            'highest_score' => round($row['highest_score']),
            'lowest_score' => round($row['lowest_score'])
        ];
    }
}
?>

<style>
.no-results-message {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.no-results-message .message-content {
    text-align: center;
    padding: 30px;
}

.no-results-message .material-symbols-rounded {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 15px;
}

.no-results-message h3 {
    color: #343a40;
    margin-bottom: 10px;
    font-size: 1.5rem;
}

.no-results-message p {
    color: #6c757d;
    margin: 0;
    font-size: 1.1rem;
}
</style>

<!-- Exam Results Tab Content -->
<div id="exam-results" class="tab-content">
    <div class="analytics-card">
        <div class="card-header">
            <h2 class="card-title">Exam Results Summary</h2>
            <div class="action-buttons">
                <button class="btn btn-secondary" id="downloadSelectedBtn" disabled>
                    <span class="material-symbols-rounded">download</span> Download Selected
                </button>
                <button class="btn btn-primary" id="downloadAllBtn">
                    <span class="material-symbols-rounded">download</span> Download All
                </button>
            </div>
        </div>
        
        <?php if (empty($exam_results)): ?>
            <div class="no-results-message">
                <div class="message-content">
                    <span class="material-symbols-rounded">info</span>
                    <h3>No Exam Results Available</h3>
                    <p>There are no exam results to display at this time.</p>
                </div>
            </div>
        <?php else: ?>
        <table class="analytics-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Exam Title</th>
                    <th>Total Students</th>
                    <th>Pass Rate</th>
                    <th>Average Score</th>
                    <th>Score Range</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exam_results as $index => $result): ?>
                    <tr>
                        <td><input type="checkbox" class="examCheckbox" value="<?php echo $result['exam_id']; ?>"></td>
                        <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                        <td><?php echo $result['total_students']; ?></td>
                        <td>
                            <?php echo $result['pass_rate']; ?>%
                            <div class="progress-bar">
                                <div class="progress progress-pass" style="width: <?php echo $result['pass_rate']; ?>%"></div>
                            </div>
                        </td>
                        <td><?php echo $result['average_score']; ?></td>
                        <td><?php echo $result['lowest_score']; ?> - <?php echo $result['highest_score']; ?></td>
                        <td>
                            <button class="btn btn-secondary view-details-btn" 
                                    data-exam-id="<?php echo $result['exam_id']; ?>" 
                                    data-exam-title="<?php echo htmlspecialchars($result['exam_title']); ?>">
                                <span class="material-symbols-rounded">visibility</span> Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Detailed Results Panel (hidden by default) -->
        <div id="exam-detail-panel" class="detail-panel">
            <div class="detail-header">
                <h3 class="detail-title" id="detail-exam-title">Technical Assessment Exam Results</h3>
                <button class="btn btn-secondary" id="close-detail-btn">
                    <span class="material-symbols-rounded">close</span> Close
                </button>
            </div>
            
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value" id="detail-total-students">0</div>
                    <div class="metric-label">Total Students</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="detail-pass-count">0</div>
                    <div class="metric-label">Passed</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="detail-fail-count">0</div>
                    <div class="metric-label">Failed</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="detail-pass-rate">0%</div>
                    <div class="metric-label">Pass Rate</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="detail-avg-score">0</div>
                    <div class="metric-label">Average Score</div>
                </div>
            </div>
            
            <div class="chart-container">
                <canvas id="scoreDistributionChart"></canvas>
            </div>
            
            <h4 style="margin-top: 30px; margin-bottom: 15px;">Score Distribution</h4>
            <div class="distribution-grid" id="score-distribution-grid">
                <!-- Distribution items will be populated via JavaScript -->
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-secondary">
                    <span class="material-symbols-rounded">print</span> Print Results
                </button>
                <button class="btn btn-primary">
                    <span class="material-symbols-rounded">download</span> Download Full Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkboxes functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const examCheckboxes = document.querySelectorAll('.examCheckbox');
    const downloadSelectedBtn = document.getElementById('downloadSelectedBtn');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            examCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDownloadButtonState();
        });
    }

    examCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateDownloadButtonState);
    });

    function updateDownloadButtonState() {
        const checkedBoxes = document.querySelectorAll('.examCheckbox:checked');
        if (downloadSelectedBtn) {
            downloadSelectedBtn.disabled = checkedBoxes.length === 0;
        }
    }

    // View details button functionality
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    const detailPanel = document.getElementById('exam-detail-panel');
    const closeDetailBtn = document.getElementById('close-detail-btn');

    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const examId = this.dataset.examId;
            const examTitle = this.dataset.examTitle;
            
            // Update detail panel title
            document.getElementById('detail-exam-title').textContent = examTitle;
            
            // Show the detail panel
            if (detailPanel) {
                detailPanel.style.display = 'block';
            }
            
            // Here you would typically fetch and display the detailed exam data
            // This is a placeholder for the actual data fetching
            updateDetailPanelData({
                totalStudents: 100,
                passCount: 75,
                failCount: 25,
                passRate: 75,
                avgScore: 82.5
            });
        });
    });

    if (closeDetailBtn) {
        closeDetailBtn.addEventListener('click', function() {
            detailPanel.style.display = 'none';
        });
    }

    function updateDetailPanelData(data) {
        document.getElementById('detail-total-students').textContent = data.totalStudents;
        document.getElementById('detail-pass-count').textContent = data.passCount;
        document.getElementById('detail-fail-count').textContent = data.failCount;
        document.getElementById('detail-pass-rate').textContent = data.passRate + '%';
        document.getElementById('detail-avg-score').textContent = data.avgScore;
    }
});
</script> 