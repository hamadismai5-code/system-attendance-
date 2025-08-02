
const form = document.getElementById('attendance-form');
const messageDiv = document.getElementById('message');
const attendanceTableBody = document.querySelector('#attendance-table tbody');

const attendanceRecords = {}; // key = name + department + date

function formatDate(date) {
  return date.toISOString().split('T')[0];
}

function formatTime(date) {
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

form.addEventListener('submit', function(e) {
  e.preventDefault();

  const name = document.getElementById('name').value.trim();
  const department = document.getElementById('department').value;
  const now = new Date();
  const today = formatDate(now);

  if (!name || !department) {
    messageDiv.textContent = "Please enter both name and department.";
    messageDiv.style.color = 'red';
    return;
  }

  const key = `${name}_${department}_${today}`;

  if (!attendanceRecords[key]) {
    // First time entry - record Time In
    attendanceRecords[key] = {
      name,
      department,
      date: today,
      timeIn: formatTime(now),
      timeOut: ''
    };

    // Add row to table
    const row = document.createElement('tr');
    row.setAttribute('data-key', key);
    row.innerHTML = `
      <td>${name}</td>
      <td>${department}</td>
      <td>${today}</td>
      <td class="time-in">${attendanceRecords[key].timeIn}</td>
      <td class="time-out">--</td>
    `;
    attendanceTableBody.appendChild(row);

    messageDiv.textContent = `Time In recorded for ${name} at ${attendanceRecords[key].timeIn}`;
    messageDiv.style.color = 'green';
  } else if (attendanceRecords[key] && attendanceRecords[key].timeOut === '') {
    // Mark Time Out
    attendanceRecords[key].timeOut = formatTime(now);

    // Update row in table
    const row = document.querySelector(`tr[data-key="${key}"]`);
    if (row) {
      row.querySelector('.time-out').textContent = attendanceRecords[key].timeOut;
    }

    messageDiv.textContent = `Time Out recorded for ${name} at ${attendanceRecords[key].timeOut}`;
    messageDiv.style.color = 'green';
  } else {
    // Both Time In and Time Out already recorded
    messageDiv.textContent = `Attendance for ${name} already completed today.`;
    messageDiv.style.color = 'orange';
  }

  form.reset();
});
