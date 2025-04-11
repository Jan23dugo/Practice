<?php
// Include database connection if not already included
if (!isset($conn)) {
    include('config/config.php');
}

// Fetch student demographics
$demographics_query = "
SELECT 
    student_type,
    COUNT(*) as count
FROM register_studentsqe 
GROUP BY student_type";

$demographics_result = $conn->query($demographics_query);
$demographics = [
    'transferee' => 0,
    'shiftee' => 0,
    'ladderized' => 0,
    'total' => 0
];

if ($demographics_result && $demographics_result->num_rows > 0) {
    while ($row = $demographics_result->fetch_assoc()) {
        $type = strtolower($row['student_type']);
        if (isset($demographics[$type])) {
            $demographics[$type] = (int)$row['count'];
            $demographics['total'] += (int)$row['count'];
        }
    }
}
?>

<!-- Demographics Tab Content -->
<div id="demographics" class="tab-content">
    <div class="analytics-card">
        <div class="card-header">
            <h2 class="card-title">Student Demographics</h2>
        </div>
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo $demographics['total']; ?></div>
                <div class="metric-label">Total Students</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $demographics['transferee']; ?></div>
                <div class="metric-label">Transferee Students</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $demographics['shiftee']; ?></div>
                <div class="metric-label">Shiftee Students</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $demographics['ladderized']; ?></div>
                <div class="metric-label">Ladderized Students</div>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="demographicsChart" 
                data-transferee="<?php echo $demographics['transferee']; ?>"
                data-shiftee="<?php echo $demographics['shiftee']; ?>"
                data-ladderized="<?php echo $demographics['ladderized']; ?>">
            </canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Demographics Pie Chart
    const demographicsCtx = document.getElementById('demographicsChart').getContext('2d');
    const demoLabels = [];
    const demoData = [];
    
    <?php foreach (['transferee', 'shiftee', 'ladderized'] as $type): ?>
        <?php if ($demographics[$type] > 0): ?>
            demoLabels.push('<?php echo ucfirst($type); ?>');
            demoData.push(<?php echo $demographics[$type]; ?>);
        <?php endif; ?>
    <?php endforeach; ?>
    
    new Chart(demographicsCtx, {
        type: 'doughnut',
        data: {
            labels: demoLabels,
            datasets: [{
                data: demoData,
                backgroundColor: ['#2196F3', '#FFC107', '#9C27B0'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Student Type Distribution',
                    font: {
                        size: 16
                    }
                }
            }
        }
    });
});

// Function to export demographics data
function exportDemographics() {
    // Create CSV content
    let csvContent = "Student Type,Count\n";
    <?php foreach (['transferee', 'shiftee', 'ladderized'] as $type): ?>
        <?php if ($demographics[$type] > 0): ?>
            csvContent += "<?php echo ucfirst($type); ?>,<?php echo $demographics[$type]; ?>\n";
        <?php endif; ?>
    <?php endforeach; ?>
    
    // Create blob and download
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', 'student_demographics.csv');
    a.click();
    window.URL.revokeObjectURL(url);
}
</script> 