document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("campoForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");
  const formTitle = document.getElementById("form-title");

  const nombre = document.getElementById("nombre");
  const ubicacion = document.getElementById("ubicacion");
  const superficie = document.getElementById("superficie");

  const modal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

  const tableBody = document.querySelector(".table-modern tbody");

  const API = "../../../backend/controladores/campoController.php";

  // UI helpers
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

  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar";
    formTitle.textContent = "Registrar Campo";
    cancelarEdicion.style.display = "none";
    idInput.value = "";
    form.reset();
    ["nombre", "ubicacion", "superficie"].forEach((k) => {
      const el = document.getElementById("error-" + k);
      if (el) el.style.display = "none";
    });
  }

  function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar";
    formTitle.textContent = "Modificar Campo";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;
    nombre.value = data.nombre;
    ubicacion.value = data.ubicacion;
    superficie.value = data.superficie;
    nombre.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function extractDataFromRow(tr) {
    return {
      id: tr.dataset.id,
      nombre: tr.dataset.nombre,
      ubicacion: tr.dataset.ubicacion,
      superficie: tr.dataset.superficie,
    };
  }

  async function refrescarTabla() {
    const resp = await fetch(`${API}?action=list`, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    const campos = await resp.json();
    console.log("RESPUESTA BACKEND:", campos); // <-- DEBUG

    tableBody.innerHTML = "";

    // ✅ CONDICIÓN BLINDADA TOTAL
    if (
      !campos ||
      typeof campos !== "object" ||
      !Array.isArray(campos) ||
      campos.length === 0
    ) {
      tableBody.innerHTML = `
    <tr>
      <td colspan="5" style="text-align:center; color:#666;">No hay campos registrados.</td>
    </tr>`;
      return;
    }

    for (const c of campos) {
      const tr = document.createElement("tr");
      tr.dataset.id = c.id;
      tr.dataset.nombre = c.nombre;
      tr.dataset.ubicacion = c.ubicacion;
      tr.dataset.superficie = c.superficie;

      tr.innerHTML = `
        <td>${c.id}</td>
        <td>${c.nombre}</td>
        <td>${c.ubicacion}</td>
        <td>${c.superficie}</td>
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon edit js-edit" title="Modificar" aria-label="Modificar">✏️</button>
            <button type="button" class="btn-icon delete js-delete" title="Eliminar" aria-label="Eliminar">🗑️</button>
          </div>
        </td>
      `;
      tableBody.appendChild(tr);
    }
  }

  // Delegación de eventos para botones de la tabla (soporta filas nuevas)
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
      confirmText.textContent = `¿Seguro que deseas eliminar el campo "${data.nombre}"?`;
      modal.dataset.id = data.id; // guardamos el ID a borrar
      modal.style.display = "flex";
      return;
    }
  });

  // Confirmación de eliminación por AJAX
  confirmYes.addEventListener("click", async () => {
    const id = modal.dataset.id;
    modal.style.display = "none";
    delete modal.dataset.id;
    if (!id) return;

    const fd = new FormData();
    fd.append("accion", "eliminar");
    fd.append("id", id);

    try {
      const resp = await fetch(API, {
        method: "POST",
        body: fd,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const data = await resp.json();
      flash(data.tipo, data.mensaje);
      if (data.tipo === "success") {
        await refrescarTabla();
        setRegistrarMode();
      }
    } catch (err) {
      console.error(err);
      flash("error", "Error al eliminar el campo.");
    }
  });

  confirmNo.addEventListener("click", () => {
    modal.style.display = "none";
    delete modal.dataset.id;
  });

  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
      delete modal.dataset.id;
    }
  });

  cancelarEdicion.addEventListener("click", setRegistrarMode);

  // Submit del formulario por AJAX (registrar / modificar)
  form.addEventListener("submit", async function (e) {
    e.preventDefault(); // evita recarga de página

    // Validación simple (igual que antes)
    let ok = true;
    document.getElementById("error-nombre").style.display = nombre.value.trim()
      ? "none"
      : "block";
    if (!nombre.value.trim()) ok = false;

    document.getElementById("error-ubicacion").style.display =
      ubicacion.value.trim() ? "none" : "block";
    if (!ubicacion.value.trim()) ok = false;

    const supNum = Number(superficie.value);
    const supValida = Number.isInteger(supNum) && supNum > 0;
    document.getElementById("error-superficie").style.display = supValida
      ? "none"
      : "block";
    if (!supValida) ok = false;

    if (!ok) return;

    const fd = new FormData(form);
    try {
      const resp = await fetch(API, {
        method: "POST",
        body: fd,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const data = await resp.json();
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
