document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("potreroForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");
  const formTitle = document.getElementById("form-title");

  const nombre = document.getElementById("nombre");
  const pasturaId = document.getElementById("pasturaId");
  const categoriaId = document.getElementById("categoriaId");
  const cantidadCategoria = document.getElementById("cantidadCategoria");
  const campoId = document.getElementById("campoId");

  const tableBody = document.querySelector(".table-modern tbody");
  const modal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

  const API = "../../../backend/controladores/potreroController.php";

  // ===== Helpers =====
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

  function buildLovMap(selectEl) {
    const map = {};
    Array.from(selectEl.options).forEach((opt) => {
      if (opt.value) map[opt.value] = opt.textContent.trim();
    });
    return map;
  }

  async function fetchJSON(url, options) {
    const resp = await fetch(url, options);
    const ct = resp.headers.get("content-type") || "";
    const text = await resp.text();

    // Si el servidor no marcó JSON, igual intentamos parsear por contenido
    if (!ct.includes("application/json")) {
      try {
        const parsed = JSON.parse(text);
        console.warn(
          "[Aviso] Content-Type no es JSON pero el cuerpo sí lo es. Corregí el backend para mandar application/json."
        );
        return parsed;
      } catch (e) {
        console.error("[Backend NON-JSON]", {
          status: resp.status,
          contentType: ct,
          preview: text.slice(0, 600),
        });
        throw new Error(
          `Respuesta NO JSON (status ${resp.status}). Revisá la consola para ver el HTML/error devuelto.`
        );
      }
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      console.error("[JSON parse error] cuerpo recibido:", text.slice(0, 600));
      throw e;
    }
  }

  // Mapas ID -> Nombre tomados de las LOV del formulario
  const LOVS = {
    pastura: buildLovMap(pasturaId),
    categoria: buildLovMap(categoriaId),
    campo: buildLovMap(campoId),
  };

  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar";
    formTitle.textContent = "Registrar Potrero";
    cancelarEdicion.style.display = "none";
    idInput.value = "";
    form.reset();
    [
      "nombre",
      "pasturaId",
      "categoriaId",
      "cantidadCategoria",
      "campoId",
    ].forEach((k) => {
      const el = document.getElementById("error-" + k);
      if (el) el.style.display = "none";
    });
  }

  function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar";
    formTitle.textContent = "Modificar Potrero";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;
    nombre.value = data.nombre || "";
    pasturaId.value = data.pasturaId || "";
    categoriaId.value = data.categoriaId || "";
    cantidadCategoria.value = data.cantidadCategoria || "";
    campoId.value = data.campoId || "";

    nombre.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function extractDataFromRow(tr) {
    return {
      id: tr.dataset.id,
      nombre: tr.dataset.nombre,
      pasturaId: tr.dataset.pasturaId,
      categoriaId: tr.dataset.categoriaId,
      cantidadCategoria: tr.dataset.cantidadCategoria,
      campoId: tr.dataset.campoId,
    };
  }

  async function refrescarTabla() {
    try {
      const potreros = await fetchJSON(`${API}?action=list`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      tableBody.innerHTML = "";
      if (!Array.isArray(potreros) || potreros.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#666;">No hay potreros registrados.</td></tr>`;
        return;
      }

      for (const p of potreros) {
        const tr = document.createElement("tr");

        // Aseguramos existencia de campos esperados
        const _id = p.id ?? p.potreroId ?? "";
        const _nombre = p.nombre ?? "";
        const _pasturaId = String(p.pasturaId ?? "");
        const _categoriaId = String(p.categoriaId ?? "");
        const _cantidad = p.cantidadCategoria ?? p.cantCategoria ?? "";
        const _campoId = String(p.campoId ?? "");

        // Mapear IDs -> Nombres para mostrar en la tabla
        const pasturaNombre = LOVS.pastura[_pasturaId] || "";
        const categoriaNombre = LOVS.categoria[_categoriaId] || "";
        const campoNombre = LOVS.campo[_campoId] || "";

        // Persistir datos crudos en data-* para la edición
        tr.dataset.id = _id;
        tr.dataset.nombre = _nombre;
        tr.dataset.pasturaId = _pasturaId;
        tr.dataset.categoriaId = _categoriaId;
        tr.dataset.cantidadCategoria = _cantidad;
        tr.dataset.campoId = _campoId;

        tr.innerHTML = `
          <td>${_id}</td>
          <td>${_nombre}</td>
          <td>${pasturaNombre}</td>
          <td>${categoriaNombre}</td>
          <td>${_cantidad}</td>
          <td>${campoNombre}</td>
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
      console.error(err);
      tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#a00;">Error cargando tabla. Revisá la consola.</td></tr>`;
    }
  }

  // Delegación de eventos para acciones de tabla
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
      confirmText.textContent = `¿Seguro que deseás eliminar el potrero "${data.nombre}"?`;
      modal.dataset.id = data.id; // guardo el ID a borrar
      modal.style.display = "flex";
      return;
    }
  });

  // Confirmación eliminar
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
      flash("error", "Error al eliminar el potrero. Revisá la consola.");
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

  // Submit del formulario (registrar / modificar)
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Validación simple
    let ok = true;
    const setErr = (id, cond) => {
      const el = document.getElementById("error-" + id);
      if (el) el.style.display = cond ? "none" : "block";
      if (!cond) ok = false;
    };

    setErr("nombre", !!nombre.value.trim());
    setErr("pasturaId", !!pasturaId.value);
    setErr("categoriaId", !!categoriaId.value);
    const cantNum = Number(cantidadCategoria.value);
    setErr("cantidadCategoria", Number.isInteger(cantNum) && cantNum > 0);
    setErr("campoId", !!campoId.value);

    if (!ok) return;

    try {
      const data = await fetchJSON(API, {
        method: "POST",
        body: new FormData(form),
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      flash(data.tipo, data.mensaje);
      if (data.tipo === "success") {
        await refrescarTabla();
        setRegistrarMode();
      }
    } catch (err) {
      console.error(err);
      flash("error", "Error al procesar la solicitud. Revisá la consola.");
    }
  });

  // Estado inicial
  setRegistrarMode();
  // Llenado inicial de tabla (con mapeo de nombres)
  refrescarTabla();
});
