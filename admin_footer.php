<?php
// admin_footer.php - reusable admin footer component
?>
      </div> <!-- Close .page-content -->
    </main> <!-- Close .admin-content -->
  </div> <!-- Close .admin-main -->
</div> <!-- Close .admin-container -->

<!-- Include Chart.js for charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
      // Menu toggle functionality for sidebar
      const menuToggle = document.getElementById('menuToggle');
      if (menuToggle) {
        menuToggle.addEventListener('click', function() {
          document.querySelector('.admin-sidebar').classList.toggle('collapsed');
          document.querySelector('.admin-content').classList.toggle('expanded');
        });
      }

      // Interactive user menu in header
      const userMenuToggle = document.getElementById('userMenuToggle');
      const userActions = document.getElementById('userActions');
      if (userMenuToggle && userActions) {
        userMenuToggle.addEventListener('click', function(event) {
          event.stopPropagation();
          userActions.classList.toggle('show');
        });
        // Close dropdown if clicking outside
        document.addEventListener('click', function() {
          if (userActions.classList.contains('show')) {
            userActions.classList.remove('show');
          }
        });
      }
    
      // ==================== Chart Initialization (only if elements exist) ====================
      const weeklyChartCanvas = document.getElementById('weeklyTrendChart');
      if (weeklyChartCanvas) {
        const weeklyCtx = weeklyChartCanvas.getContext('2d');

        // Gradient for the bar chart
        const gradient = weeklyCtx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.8)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.2)');

        const weeklyChart = new Chart(weeklyCtx, {
          type: 'bar', // Change chart type to bar
          data: {
            labels: <?php echo json_encode($chart_labels ?? []); ?>,
            datasets: [
              {
                label: 'On-Time Arrivals',
                data: <?php echo json_encode($chart_on_time ?? []); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4,
                hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
              },
              {
                label: 'Late Arrivals',
                data: <?php echo json_encode($chart_late ?? []); ?>,
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1,
                borderRadius: 4,
                hoverBackgroundColor: 'rgba(245, 158, 11, 0.9)',
              },
              {
                label: 'Early Departures',
                data: <?php echo json_encode($chart_early ?? []); ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.6)',
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 1,
                borderRadius: 4,
                hoverBackgroundColor: 'rgba(239, 68, 68, 0.8)',
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'top', align: 'end' },
              title: { display: false },
              tooltip: {
                mode: 'index',
                intersect: false,
              }
            },
            scales: {
              x: {
                stacked: false, // Set to false for grouped bars
              },
              y: { 
                stacked: false, // Set to false for grouped bars
                beginAtZero: true, 
                grace: '10%',
                grid: {
                  color: '#e9ecef',
                  drawBorder: false,
                }
              }
            }
          }
        });
      }
    
      const deptChartCanvas = document.getElementById('departmentChart');
      if (deptChartCanvas) {
        const deptCtx = deptChartCanvas.getContext('2d');
        const deptChart = new Chart(deptCtx, {
          type: 'bar',
          data: {
            labels: <?php echo json_encode(array_column($dept_stats, 'department_name')); ?>,
            datasets: [
              {
                label: 'On-Time',
                data: <?php echo json_encode(array_column($dept_stats, 'on_time_count')); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
              },
              {
                label: 'Late',
                data: <?php echo json_encode(array_column($dept_stats, 'late_count')); ?>,
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                hoverBackgroundColor: 'rgba(245, 158, 11, 0.9)',
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { 
                position: 'top',
                align: 'end'
              },
              title: { 
                display: false, 
                text: 'Department Punctuality (Last 30 Days)' 
              },
              tooltip: {
                mode: 'index',
                intersect: false,
              },
            },
            scales: {
              x: {
                stacked: false,
              },
              y: {
                stacked: false,
                beginAtZero: true,
                grace: '10%'
              }
            }
          }
        });
      }
    });
  </script>
</body>
</html>