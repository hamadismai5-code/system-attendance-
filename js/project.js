document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('attendance-form');
    const messageDiv = document.getElementById('message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        
        fetch('attendance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Osha ukurasa upya kuonyesha rekodi mpya
            location.reload();
        })
        .catch(error => {
            messageDiv.textContent = "Hitilafu: " + error;
            messageDiv.style.color = 'red';
        });
    });
});

