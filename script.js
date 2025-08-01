document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("attendance-form");
  const nameInput = document.getElementById("name");
  const departmentInput = document.getElementById("department");
  const tableBody = document.getElementById("attendance-table").querySelector("tbody");

  form.addEventListener("submit", function(e) {
    e.preventDefault();

    const name = nameInput.value.trim();
    const department = departmentInput.value;
    const now = new Date();
    const date = now.toLocaleDateString();
    const time = now.toLocaleTimeString();

    // Enhanced validation
    if (!name || !department) {
      showMessage("Please fill all fields!", "error");
      return;
    }

    // Add new row
    const row = tableBody.insertRow();
    row.insertCell(0).innerText = name;
    row.insertCell(1).innerText = department;
    row.insertCell(2).innerText = date;
    row.insertCell(3).innerText = time;

    // Reset form and focus name input
    form.reset();
    nameInput.focus();

    showMessage("Attendance marked!", "success");
  });

  // Simple feedback message
  function showMessage(msg, type) {
    let msgDiv = document.getElementById("form-message");
    if (!msgDiv) {
      msgDiv = document.createElement("div");
      msgDiv.id = "form-message";
      form.parentNode.insertBefore(msgDiv, form);
    }
    msgDiv.style.display = "block";
    msgDiv.textContent = msg;
    msgDiv.style.color = type === "success" ? "#28a745" : "#d9534f";
    setTimeout(() => {
      msgDiv.textContent = "";
      msgDiv.style.display = "none";
    }, 1800);
  }
});
