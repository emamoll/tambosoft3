document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registroForm");
  if (form) {
    const inputImagen = document.getElementById("imagen");
    form.addEventListener("submit", (e) => {
      let valido = true;

      const username = document.getElementById("username").value.trim();
      const email = document.getElementById("email").value.trim();
      const password = document.getElementById("password").value;
      const confirmar = document.getElementById("confirmar")?.value || "";
      const rolId = document.getElementById("rolId")?.value || "";

      const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      const regexPass = /^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/;

      if (username === "") {
        document.getElementById("error-username").style.display = "block";
        valido = false;
      } else document.getElementById("error-username").style.display = "none";

      if (!regexEmail.test(email)) {
        document.getElementById("error-email").style.display = "block";
        valido = false;
      } else document.getElementById("error-email").style.display = "none";

      if (!regexPass.test(password)) {
        document.getElementById("error-password").style.display = "block";
        valido = false;
      } else document.getElementById("error-password").style.display = "none";

      if (confirmar && password !== confirmar) {
        document.getElementById("error-confirmar").style.display = "block";
        valido = false;
      } else if (document.getElementById("error-confirmar")) {
        document.getElementById("error-confirmar").style.display = "none";
      }

      if (rolId && document.getElementById("error-rol")) {
        document.getElementById("error-rol").style.display = "none";
      } else if (document.getElementById("error-rol")) {
        document.getElementById("error-rol").style.display = "block";
        valido = false;
      }

      if (
        inputImagen &&
        inputImagen.files.length === 0 &&
        document.getElementById("error-imagen")
      ) {
        document.getElementById("error-imagen").style.display = "block";
        valido = false;
      } else if (document.getElementById("error-imagen")) {
        document.getElementById("error-imagen").style.display = "none";
      }

      if (!valido) e.preventDefault();
    });
  }

  const passwordInput = document.getElementById("password");
  const toggleEye = document.getElementById("togglePassword");

  if (passwordInput && toggleEye) {
    toggleEye.addEventListener("click", () => {
      const isHidden = passwordInput.type === "password";
      passwordInput.type = isHidden ? "text" : "password";
      toggleEye.classList.toggle("fa-eye", !isHidden);
      toggleEye.classList.toggle("fa-eye-slash", isHidden);
    });
  }
});
