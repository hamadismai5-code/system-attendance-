<?php
// admin_footer.php - reusable admin footer component
?>
      </div> <!-- Close .page-content -->
    </main> <!-- Close .admin-content -->
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
            labels: [<?php echo isset($weekly_trend) ? implode(',', array_map(function($item) { return "'" . date('D', strtotime($item['day'])) . "'"; }, $weekly_trend)) : ''; ?>],
            datasets: [{
              label: 'Users Present',
              data: [<?php echo isset($weekly_trend) ? implode(',', array_column($weekly_trend, 'users')) : ''; ?>],
              borderColor: 'rgb(54, 162, 235)',
              backgroundColor: 'rgba(54, 162, 235, 0.1)',
              tension: 0.3,
              fill: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'top' },
              title: { display: true, text: 'Last 7 Days Attendance' }
            }
          }
        });
      }
    
      const deptChartCanvas = document.getElementById('departmentChart');
      if (deptChartCanvas) {
        const deptCtx = deptChartCanvas.getContext('2d');
        const deptChart = new Chart(deptCtx, {
          type: 'doughnut',
          data: {
            labels: [<?php echo isset($dept_stats) ? implode(',', array_map(function($item) { return "'" . addslashes($item['department']) . "'"; }, $dept_stats)) : ''; ?>],
            datasets: [{
              data: [<?php echo isset($dept_stats) ? implode(',', array_column($dept_stats, 'count')) : ''; ?>],
              backgroundColor: ['#6366f1', '#06b6d4', '#fbbf24', '#10b981', '#ef4444', '#8b5cf6']
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'right' },
              title: { display: true, text: 'Today by Department' }
            }
          }
        });
      }
    });
  </script>
</body>
</html>