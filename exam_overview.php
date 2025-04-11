<?php
// Include database connection if not already included
if (!isset($conn)) {
    include('config/config.php');
}

// Fetch exam statistics
$exams_query = "
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN exam_type = 'tech' THEN 1 ELSE 0 END) as technical,
    SUM(CASE WHEN exam_type != 'tech' THEN 1 ELSE 0 END) as non_technical,
    SUM(CASE WHEN is_scheduled = 1 THEN 1 ELSE 0 END) as scheduled,
    COUNT(DISTINCT exam_id) as unique_exams
FROM exams";

$exams_result = $conn->query($exams_query);
$exams = [
    'total' => 0,
    'technical' => 0,
    'non_technical' => 0,
    'scheduled' => 0,
    'completed' => 0
];

if ($exams_result && $exams_result->num_rows > 0) {
    $row = $exams_result->fetch_assoc();
    $exams['total'] = (int)$row['total'];
    $exams['technical'] = (int)$row['technical'];
    $exams['non_technical'] = (int)$row['non_technical'];
    $exams['scheduled'] = (int)$row['scheduled'];
    
    // Fetch completed exams count
    $completed_query = "
    SELECT COUNT(DISTINCT e.exam_id) as completed
    FROM exams e
    JOIN exam_assignments ea ON e.exam_id = ea.exam_id
    WHERE ea.completion_status = 'completed'";
    
    $completed_result = $conn->query($completed_query);
    if ($completed_result && $completed_result->num_rows > 0) {
        $completed_row = $completed_result->fetch_assoc();
        $exams['completed'] = (int)$completed_row['completed'];
    }
}
?>

<!-- Exam Overview Tab Content -->
<div id="exam-overview" class="tab-content">
    <div class="analytics-card">
        <div class="card-header">
            <h2 class="card-title">Exam Overview</h2>
            <a href="exams.php" class="card-action">
                <span class="material-symbols-rounded">list</span> View All Exams
            </a>
        </div>
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo $exams['total']; ?></div>
                <div class="metric-label">Total Exams</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $exams['technical']; ?></div>
                <div class="metric-label">Technical Exams</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $exams['non_technical']; ?></div>
                <div class="metric-label">Non-Technical Exams</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $exams['scheduled']; ?></div>
                <div class="metric-label">Scheduled Exams</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $exams['completed']; ?></div>
                <div class="metric-label">Completed Exams</div>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="examTypeChart" 
                data-technical="<?php echo $exams['technical']; ?>"
                data-non-technical="<?php echo $exams['non_technical']; ?>">
            </canvas>
        </div>
        
        <div class="card-footer">
            <p>The exam overview provides a snapshot of the current exam database, including technical and non-technical assessments. Click "View All Exams" to see the comprehensive list and manage individual exams.</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Exam Types Chart
    const examTypesCtx = document.getElementById('examTypesChart').getContext('2d');
    new Chart(examTypesCtx, {
        type: 'bar',
        data: {
            labels: ['Total Exams', 'Technical', 'Non-Technical', 'Scheduled', 'Completed'],
            datasets: [{
                data: [
                    <?php echo $exams['total']; ?>,
                    <?php echo $exams['technical']; ?>,
                    <?php echo $exams['non_technical']; ?>,
                    <?php echo $exams['scheduled']; ?>,
                    <?php echo $exams['completed']; ?>
                ],
                backgroundColor: [
                    '#75343A',
                    '#4CAF50',
                    '#2196F3',
                    '#FFC107',
                    '#9C27B0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script> 