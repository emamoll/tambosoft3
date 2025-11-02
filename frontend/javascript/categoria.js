document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("categoriaForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");
  const formTitle = document.getElementById("form-title");

  const nombre = document.getElementById("nombre");
  const cantidad = document.getElementById("cantidad")

  const modal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

  const tableBody = document.querySelector(".table-modern tbody");

  const API = "../../../backend/controladores/categoriaController.php";

  // UI helpers
  function flash(tipo, mensaje) {
    let alertBox = document.querySelector(".form .alert");
    if (!alertBox) {
      alertBox = document.createElement("div");
      alertBox.className = "alert";
      const h2 = document.getElementById("form-title");
      h2.insertAdjacentElement("afterend", alertBox);
    }
    alertBox.className =
      "alert " + (tipo === "success" ? "alert-success" : "alert-danger");
    alertBox.textContent = mensaje;
  }

  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar";
    formTitle.textContent = "Registrar Categoría";
    cancelarEdicion.style.display = "none";
    idInput.value = "";
    form.reset();
    ["nombre"].forEach((k) => {
      const el = document.getElementById("error-" + k);
      if (el) el.style.display = "none";
    });
    form.reset();
    ["cantidad"].forEach((k) => {
      const el = document.getElementById("error-" + k);
      if (el) el.style.display = "none";
    });
  }

  function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar";
    formTitle.textContent = "Modificar Categoriía";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;
    nombre.value = data.nombre;
    cantidad.value = data.cantidad;
    nombre.focus({ preventScroll: true });
    cantidad.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function extractDataFromRow(tr) {
    return {
      id: tr.dataset.id,
      nombre: tr.dataset.nombre,
      cantidad: tr.dataset.cantidad,
    };
  }

  async function refrescarTabla() {
    try {
      const resp = await fetch(`${API}?action=list`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const categorias = await resp.json();

      tableBody.innerHTML = "";
      if (!Array.isArray(categorias) || categorias.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#666;">No hay categorías registradas.</td></tr>`;
        return;
      }

      for (const c of categorias) {
        const tr = document.createElement("tr");
        tr.dataset.id = c.id;
        tr.dataset.nombre = c.nombre;
        tr.dataset.cantidad = c.cantidad;

        tr.innerHTML = `
          <td>${c.id}</td>
          <td>${c.nombre}</td>
          <td>${c.cantidad}</td>
          <td>
            <div class="table-actions">
              <button type="button" class="btn-icon edit js-edit" title="Modificar" aria-label="Modificar">✏️</button>
              <button type="button" class="btn-icon delete js-delete" title="Eliminar" aria-label="Eliminar">🗑️</button>
            </div>
          </td>
        `;
        tableBody.appendChild(tr);
      }
    } catch (err) {
      console.error("Error al refrescar la tabla:", err);
      flash("error", "No se pudo actualizar la lista de categorías.");
    }
  }

  // Delegación de eventos
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
      confirmText.textContent = `¿Seguro que deseas eliminar la categoría "${data.nombre}"?`;
      modal.dataset.id = data.id;
      modal.style.display = "flex";
      return;
    }
  });

  // Confirmación de eliminación
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
      flash("error", "Error al eliminar la categoría");
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

  // Submit del formulario
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    let ok = true;
    document.getElementById("error-nombre").style.display = nombre.value.trim()
      ? "none"
      : "block";
    if (!nombre.value.trim()) ok = false;

    const cantNum = Number(cantidad.value);
    const canValida = Number.isInteger(cantNum) && cantNum > 0;
    document.getElementById("error-cantidad").style.display = canValida
      ? "none"
      : "block";
    if (!canValida) ok = false;

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
