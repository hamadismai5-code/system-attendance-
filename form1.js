 const form = document.getElementById('loginForm');

    form.addEventListener('submit', function(event) {
      event.preventDefault(); // Zuia behavior ya form ya kawaida

      const username = form.username.value;
      const password = form.password.value;

      if (username && password) {
        // Redirect kwa mfano kwenye 'home.html'
        window.location.href = "./project.html";
      } else {
        alert("Tafadhali jaza username na password!");
      }
    });