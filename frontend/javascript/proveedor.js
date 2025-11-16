document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("proveedorForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");
  const formTitle = document.getElementById("form-title");

  const denominacion = document.getElementById("denominacion");
  const emailP = document.getElementById("emailP");
  const telefono = document.getElementById("telefono");

  const tableBody = document.querySelector(".table-modern tbody");
  const modal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

  const API = "../../../backend/controladores/proveedorController.php";

  // ===== Helpers =====
function flash(tipo, mensaje) {
    let alertBox = document.querySelector(".form .alert");
    if (!alertBox) {
      alertBox = document.createElement("div");
      alertBox.className = "alert";
      const h2 = document.getElementById("form-title");
      h2.insertAdjacentElement("afterend", alertBox); 
    }
    
    // 1. Configura la alerta y la hace completamente visible (opacity: 1)
    alertBox.className =
      "alert " + (tipo === "success" ? "alert-success" : "alert-danger");
    alertBox.textContent = mensaje;
    alertBox.style.display = "block"; // Asegura que esté en el flujo
    alertBox.style.opacity = "1"; // Establece opacidad a 1 para empezar visible
    
    // 2. Espera 3 segundos y luego INICIA la atenuación (fade out)
    setTimeout(() => {
      alertBox.style.opacity = "0"; // Esto activa la transición CSS
      
      // 3. Oculta COMPLETAMENTE el elemento después de que la transición CSS termine (0.5s)
      setTimeout(() => {
        alertBox.style.display = "none";
      }, 500); // 500ms es el tiempo de la transición definida en campo.css
      
    }, 3000); // Muestra por 3 segundos antes de empezar a desvanecerse
  }

  function buildLovMap(selectEl) {
    const map = {};
    Array.from(selectEl.options).forEach((opt) => {
      if (opt.value) map[opt.value] = opt.textContent.trim();
    });
    return map;
  }

  async function fetchJSON(url, options = {}) {
    const resp = await fetch(url, options);
    const ct = resp.headers.get("content-type") || "";
    const text = await resp.text();

    // 🔹 Si no viene JSON, mostramos un preview para debug
    if (!ct.includes("application/json")) {
      console.error("[Backend NON-JSON]", {
        url,
        status: resp.status,
        contentType: ct,
        preview: text.slice(0, 400),
      });
      throw new Error("Respuesta no JSON del backend.");
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      console.error("[JSON Parse Error]", text.slice(0, 400));
      throw e;
    }
  }

  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar";
    formTitle.textContent = "Registrar Proveedor";
    cancelarEdicion.style.display = "none";
    idInput.value = "";
    form.reset();
  }

  function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar";
    formTitle.textContent = "Modificar Proveedor";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;
    denominacion.value = data.denominacion || "";
    emailP.value = data.emailP || "";
    telefono.value = data.telefono || "";

    denominacion.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function extractDataFromRow(tr) {
    return {
      id: tr.dataset.id,
      denominacion: tr.dataset.denominacion,
      emailP: tr.dataset.emailP,
      telefono: tr.dataset.telefono,
    };
  }

  async function refrescarTabla() {
    try {
      const proveedores = await fetchJSON(`${API}?action=list`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      tableBody.innerHTML = "";

      if (!Array.isArray(proveedores) || proveedores.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#666;">No hay proveedors registrados.</td></tr>`;
        return;
      }

      for (const p of proveedores) {
        const tr = document.createElement("tr");

        const _id = p.id ?? "";
        const _denominacion = p.denominacion ?? "";
        const _emailP = String(p.emailP ?? "");
        const _telefono = String(p.telefono ?? "");

        tr.dataset.id = _id;
        tr.dataset.denominacion = _denominacion;
        tr.dataset.emailP = _emailP;
        tr.dataset.telefono = _telefono;

        tr.innerHTML = `
            <td>${_id}</td>
            <td>${_denominacion}</td>
            <td>${_emailP}</td>
            <td>${_telefono}</td>
            <td>
              <div class="table-actions">
                <button type="button" class="btn-icon edit js-edit" title="Modificar">✏️</button>
                <button type="button" class="btn-icon delete js-delete" title="Eliminar">🗑️</button>
              </div>
            </td>
          `;
        tableBody.appendChild(tr);
      }
    } catch (err) {
      console.error(err);
      tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#a00;">Error cargando tabla. Revisá la consola.</td></tr>`;
    }
  }

  // Delegación de eventos para editar y eliminar
  tableBody.addEventListener("click", (e) => {
    const editBtn = e.target.closest(".js-edit");
    const delBtn = e.target.closest(".js-delete");

    if (editBtn) {
      const tr = editBtn.closest("tr");
      setEditarMode(extractDataFromRow(tr));
      return;
    }

    if (delBtn) {
      const tr = delBtn.closest("tr");
      const data = extractDataFromRow(tr);
      confirmText.textContent = `¿Seguro que deseás eliminar el proveedor "${data.denominacion}"?`;
      modal.dataset.id = data.id;
      modal.style.display = "flex";
      return;
    }
  });

  // Confirmar eliminación
  if (confirmYes) {
    confirmYes.addEventListener("click", async () => {
      const id = modal.dataset.id;
      modal.style.display = "none";
      delete modal.dataset.id;
      if (!id) return;

      const fd = new FormData();
      fd.append("accion", "eliminar");
      fd.append("id", id);

      try {
        const data = await fetchJSON(API, {
          method: "POST",
          body: fd,
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        flash(data.tipo, data.mensaje);
        if (data.tipo === "success") {
          await refrescarTabla();
          setRegistrarMode();
        }
      } catch (err) {
        console.error(err);
        flash("error", "Error al eliminar el proveedor. Revisá la consola.");
      }
    });
  }

  if (confirmNo) {
    confirmNo.addEventListener("click", () => {
      modal.style.display = "none";
      delete modal.dataset.id;
    });
  }

  cancelarEdicion.addEventListener("click", setRegistrarMode);

  // Submit del formulario
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Obtenemos los valores de los campos
    const denominacionValue = denominacion.value.trim();
    const emailPValue = emailP.value.trim();
    const telefonoValue = telefono.value.trim();

    // Refs para los mensajes de error
    const denominacionError = document.getElementById("error-denominacion");
    const emailPError = document.getElementById("error-emailP");
    const telefonoError = document.getElementById("error-telefono");

    // Limpieza de errores anteriores
    denominacionError.style.display = "none";
    emailPError.style.display = "none";
    telefonoError.style.display = "none";

    let valid = true;

    // Validación del campo denominación
    if (!denominacionValue) {
      denominacionError.style.display = "block"; // Muestra el error específico
      valid = false;
    }

    // Validación del email
    const emailPRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPRegex.test(emailPValue)) {
      emailPError.style.display = "block"; // Muestra el error específico
      valid = false;
    }

    // Validación del teléfono
    const telefonoRegex = /^\d{7,11}$/;
    if (!telefonoRegex.test(telefonoValue)) {
      telefonoError.style.display = "block"; // Muestra el error específico
      valid = false;
    }

    // Si hay errores, no enviamos el formulario
    if (!valid) {
      return;
    }

    // Si todas las validaciones son correctas, enviamos el formulario
    const fd = new FormData(form);
    try {
      const data = await fetchJSON(API, {
        method: "POST",
        body: fd,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      flash(data.tipo, data.mensaje);
      if (data.tipo === "success") {
        await refrescarTabla();
        setRegistrarMode();
      }
    } catch (err) {
      console.error(err);
      flash("error", "Error al procesar la solicitud.");
    }
  });

  // Estado inicial
  setRegistrarMode();
  refrescarTabla();
});
