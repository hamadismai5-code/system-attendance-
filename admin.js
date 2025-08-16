document.addEventListener('DOMContentLoaded', function() {
    // Initialize department chart
    const departmentCtx = document.getElementById('departmentChart').getContext('2d');
    
    // Get department data from PHP (we'll need to pass this to JS)
    const departmentData = JSON.parse('<?php echo json_encode($department_stats); ?>');
    
    const labels = departmentData.map(item => item.department);
    const data = departmentData.map(item => item.count);
    
    const departmentChart = new Chart(departmentCtx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e',
                    '#e74a3b'
                ],
                hoverBackgroundColor: [
                    '#2e59d9',
                    '#17a673',
                    '#2c9faf',
                    '#dda20a',
                    '#be2617'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                },
            }
        }
    });
    
    // Add any other admin-specific JS functionality here
});