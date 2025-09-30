document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registroForm");
  const input = document.getElementById("imagen");

  if (form) {
    form.addEventListener("submit", function (e) {
      let valido = true;

      // Usuario
      const username = document.getElementById("username").value.trim();
      if (username === "") {
        document.getElementById("error-username").style.display = "block";
        valido = false;
      } else {
        document.getElementById("error-username").style.display = "none";
      }

      // Email
      const email = document.getElementById("email").value.trim();
      const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!regexEmail.test(email)) {
        document.getElementById("error-email").style.display = "block";
        valido = false;
      } else {
        document.getElementById("error-email").style.display = "none";
      }

      // Password (igual que PHP)
      const password = document.getElementById("password").value;
      const regexPass = /^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/;
      if (!regexPass.test(password)) {
        document.getElementById("error-password").style.display = "block";
        valido = false;
      } else {
        document.getElementById("error-password").style.display = "none";
      }

      // Confirmar Password
      const confirmar = document.getElementById("confirmar").value;
      if (password !== confirmar) {
        document.getElementById("error-confirmar").style.display = "block";
        valido = false;
      } else {
        document.getElementById("error-confirmar").style.display = "none";
      }

      // Rol
      const rolId = document.getElementById("rolId").value;
      if (!rolId) {
        document.getElementById("error-rol").style.display = "block";
        valido = false;
      } else {
        document.getElementById("error-rol").style.display = "none";
      }

      // Imagen (obligatoria)
      if (!input || input.files.length === 0) {
        document.getElementById("error-imagen").style.display = "block";
        valido = false;
      } else {
        document.getElementById("error-imagen").style.display = "none";
      }

      if (!valido) e.preventDefault();
    });
  }
});
