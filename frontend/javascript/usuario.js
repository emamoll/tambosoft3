document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registroForm");
  const systemMessageContainer = document.getElementById(
    "system-message-container"
  );

  // Función para mostrar mensajes de sistema (éxito o error)
  function mostrarMensaje(tipo, mensaje) {
    systemMessageContainer.innerHTML = "";

    if (!mensaje) return;

    const alertDiv = document.createElement("div");

    if (tipo === "success") {
      alertDiv.className = "alert-success";
    } else {
      alertDiv.className = "alert-error";
    }

    alertDiv.textContent = mensaje;

    systemMessageContainer.appendChild(alertDiv);

    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
      alertDiv.style.display = "none";
      systemMessageContainer.innerHTML = "";
    }, 5000);
  }

  // Helper para realizar peticiones y obtener JSON
  async function fetchJSON(url, options = {}) {
    const resp = await fetch(url, options);
    const ct = resp.headers.get("content-type") || "";
    const raw = await resp.text();

    if (!ct.includes("application/json")) {
      console.error("[Backend NON-JSON]", {
        url,
        status: resp.status,
        contentType: ct,
        preview: raw.slice(0, 400),
      });
      return { tipo: "error", mensaje: "Respuesta inválida del servidor." };
    }

    try {
      return JSON.parse(raw);
    } catch (e) {
      console.error("[JSON Parse Error]", raw.slice(0, 400), e);
      return {
        tipo: "error",
        mensaje: "Error al procesar la respuesta del servidor.",
      };
    }
  }

  // LÓGICA DE IMAGEN: Actualiza la etiqueta del input de archivo cuando se selecciona una imagen
  const fileInput = document.getElementById("imagen");

  if (fileInput) {
    fileInput.addEventListener("change", (e) => {
      // Obtiene el nombre del archivo. Si no hay archivos, usa el texto por defecto.
      const fileName =
        e.target.files.length > 0
          ? e.target.files[0].name
          : "Seleccionar archivo";
      const nextSibling = e.target.nextElementSibling;

      // Busca la etiqueta adyacente (custom-file-label) y actualiza su texto.
      if (nextSibling && nextSibling.classList.contains("custom-file-label")) {
        nextSibling.textContent = fileName;
      }
    });
  }

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      let ok = true;
      document.querySelectorAll(".error-message").forEach((el) => {
        el.style.display = "none";
        // Restaurar mensajes por defecto para campos obligatorios
        el.textContent = "El campo es obligatorio";
      });
      mostrarMensaje("", ""); // Limpiar mensajes de sistema anteriores

      const username = document.getElementById("username").value.trim();
      const email = document.getElementById("email").value.trim();
      const password = document.getElementById("password").value.trim();
      const password2 = document.getElementById("password2").value.trim();
      const rolId = document.getElementById("rolId").value;

      // Helper function para mostrar el error en línea y marcar como fallido
      const mostrarError = (id, message = "El campo es obligatorio") => {
        const el = document.getElementById(id);
        if (el) {
          el.textContent = message;
          el.style.display = "block";
        }
        ok = false;
      };

      // Validaciones de campos obligatorios (mostrarán errores en línea)
      if (!username) mostrarError("error-username");
      if (!email) mostrarError("error-email");
      if (!password) mostrarError("error-password");
      if (!password2) mostrarError("error-password2");
      if (!rolId) mostrarError("error-rolId");

      // Validación de coincidencia de contraseñas (CLIENTE)
      if (ok && password !== password2) {
        // Muestra un error específico en la línea de confirmación de contraseña
        mostrarError("error-password2", "Las contraseñas no coinciden.");
      }

      if (!ok) {
        return; // Detener el envío del formulario si hay errores
      }

      // IMPORTANTE: FormData incluye el archivo si fue seleccionado
      const fd = new FormData(form);

      try {
        const data = await fetchJSON(form.action, {
          method: "POST",
          body: fd,
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });

        if (data.tipo === "success") {
          mostrarMensaje(data.tipo, data.mensaje);
          form.reset(); // Limpiar formulario
          // Restablecer el texto de la etiqueta de archivo después del reset
          const fileLabel = document.querySelector(".custom-file-label");
          if (fileLabel) {
            fileLabel.textContent = "Seleccionar archivo";
          }
        } else {
          // Los errores del servidor (e.g., usuario ya existe, error de imagen) se muestran en el contenedor superior
          mostrarMensaje(data.tipo, data.mensaje);
        }
      } catch (err) {
        console.error(err);
        mostrarMensaje("error", "Error al procesar la solicitud.");
      }
    });
  }

  // Lógica para toggle password visibility (asumido que es para login.php)
  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function () {
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.classList.toggle("fa-eye-slash");
    });
  }
});
