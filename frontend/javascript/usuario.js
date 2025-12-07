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

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    let ok = true;
    document
      .querySelectorAll(".error-message")
      .forEach((el) => (el.style.display = "none"));
    mostrarMensaje("", ""); // Limpiar mensajes anteriores

    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const rolId = document.getElementById("rolId").value;

    const mostrarError = (id) => {
      const el = document.getElementById(id);
      if (el) el.style.display = "block";
      ok = false;
    };

    if (!username) mostrarError("error-username");
    if (!email) mostrarError("error-email");
    if (!password) mostrarError("error-password");
    if (!rolId) mostrarError("error-rolId");

    if (!ok) {
      mostrarMensaje("error", "Por favor, corrija los errores del formulario.");
      return;
    }

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
      } else {
        mostrarMensaje(data.tipo, data.mensaje);
      }
    } catch (err) {
      console.error(err);
      mostrarMensaje("error", "Error al procesar la solicitud.");
    }
  });
});
