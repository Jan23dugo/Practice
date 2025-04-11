document.addEventListener('DOMContentLoaded', function() {
    // Demographics Pie Chart
    const demographicsCtx = document.getElementById('demographicsPieChart').getContext('2d');
    const demoLabels = [];
    const demoData = [];
    
    // Data will be populated from PHP
    if (typeof demographicsData !== 'undefined') {
        Object.entries(demographicsData).forEach(([type, count]) => {
            if (type !== 'total' && count > 0) {
                demoLabels.push(type.charAt(0).toUpperCase() + type.slice(1));
                demoData.push(count);
            }
        });
    }
    
    new Chart(demographicsCtx, {
        type: 'doughnut',
        data: {
            labels: demoLabels,
            datasets: [{
                data: demoData,
                backgroundColor: ['#4a6cf7', '#6c5ce7', '#00b894', '#75343A'],
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
    if (typeof demographicsData === 'undefined') {
        console.error('Demographics data not available');
        return;
    }

    // Create CSV content
    let csvContent = "Student Type,Count\n";
    Object.entries(demographicsData).forEach(([type, count]) => {
        if (type !== 'total' && count > 0) {
            csvContent += `${type.charAt(0).toUpperCase() + type.slice(1)},${count}\n`;
        }
    });
    
    // Create blob and download
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', 'student_demographics.csv');
    a.click();
    window.URL.revokeObjectURL(url);
} 