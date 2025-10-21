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
        const weeklyChart = new Chart(weeklyCtx, {
          type: 'line',
          data: {
            labels: <?php echo json_encode($chart_labels ?? []); ?>,
            datasets: [
              {
                label: 'Present Users',
                data: <?php echo json_encode($chart_present ?? []); ?>,
                borderColor: 'var(--primary)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.3,
                fill: true,
                yAxisID: 'y'
              },
              {
                label: 'Late Arrivals',
                data: <?php echo json_encode($chart_late ?? []); ?>,
                borderColor: 'var(--warning)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.3,
                fill: false,
                yAxisID: 'y'
              },
              {
                label: 'Early Departures',
                data: <?php echo json_encode($chart_early ?? []); ?>,
                borderColor: 'var(--accent)',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.3,
                fill: false,
                yAxisID: 'y'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'top' },
              title: { display: false },
              tooltip: {
                mode: 'index',
                intersect: false,
              }
            },
            scales: {
              y: { beginAtZero: true, grace: '5%' }
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
                backgroundColor: 'var(--success)',
              },
              {
                label: 'Late',
                data: <?php echo json_encode(array_column($dept_stats, 'late_count')); ?>,
                backgroundColor: 'var(--warning)',
              }
            ]
          },
          options: {
            indexAxis: 'y', // This makes it a horizontal bar chart
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
                stacked: true,
              },
              y: {
                stacked: true,
              }
            }
          }
        });
      }
    });
  </script>
</body>
</html>