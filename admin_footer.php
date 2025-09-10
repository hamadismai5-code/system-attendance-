<?php
// admin_footer.php - reusable admin footer component
?>
      </div> <!-- Close admin-content -->
    </main>
  </div>
  
  <!-- Include Chart.js for charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <script>
    // Menu toggle functionality
    document.getElementById('menuToggle').addEventListener('click', function() {
      document.querySelector('.admin-sidebar').classList.toggle('collapsed');
    });
    
    // Weekly Trend Chart
    const weeklyCtx = document.getElementById('weeklyTrendChart').getContext('2d');
    const weeklyChart = new Chart(weeklyCtx, {
      type: 'line',
      data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('D', strtotime($item['day'])) . "'"; }, $weekly_trend)); ?>],
        datasets: [{
          label: 'Users Present',
          data: [<?php echo implode(',', array_column($weekly_trend, 'users')); ?>],
          borderColor: 'rgb(54, 162, 235)',
          backgroundColor: 'rgba(54, 162, 235, 0.1)',
          tension: 0.3,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
          },
          title: {
            display: true,
            text: 'Last 7 Days Attendance'
          }
        }
      }
    });
    
    // Department Chart
    const deptCtx = document.getElementById('departmentChart').getContext('2d');
    const deptChart = new Chart(deptCtx, {
      type: 'doughnut',
      data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['department'] . "'"; }, $dept_stats)); ?>],
        datasets: [{
          data: [<?php echo implode(',', array_column($dept_stats, 'count')); ?>],
          backgroundColor: [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)',
            'rgb(255, 205, 86)',
            'rgb(75, 192, 192)',
            'rgb(153, 102, 255)'
          ]
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'right',
          },
          title: {
            display: true,
            text: 'Today by Department'
          }
        }
      }
    });
  </script>
</body>
</html>