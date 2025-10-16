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

        const _id = p.id ?? "";
        const _nombre = p.nombre ?? "";
        const _pasturaId = String(p.pasturaId ?? "");
        const _categoriaId = String(p.categoriaId ?? "");
        const _cantidad = p.cantidadCategoria ?? "";
        const _campoId = String(p.campoId ?? "");

        const pasturaNombre = LOVS.pastura[_pasturaId] || "";
        const categoriaNombre = LOVS.categoria[_categoriaId] || "";
        const campoNombre = LOVS.campo[_campoId] || "";

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
              <button type="button" class="btn-icon edit js-edit" title="Modificar">✏️</button>
              <button type="button" class="btn-icon delete js-delete" title="Eliminar">🗑️</button>
              ${
                _categoriaId &&
                _categoriaId !== "null" &&
                parseInt(_categoriaId) > 0
                  ? `<button type="button" class="btn-icon move js-move" title="Mover categoría">🐄</button>`
                  : ""
              }
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
      confirmText.textContent = `¿Seguro que deseás eliminar el potrero "${data.nombre}"?`;
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
        flash("error", "Error al eliminar el potrero. Revisá la consola.");
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

    // 🔹 Nueva validación para categoría y cantidad
    const categoriaIdValue = categoriaId.value
      ? parseInt(categoriaId.value)
      : null;
    const cantidadCategoriaValue = cantidadCategoria.value
      ? parseInt(cantidadCategoria.value)
      : null;

    // 1. Validar: si se ingresó categoría, debe ingresarse una cantidad
    if (categoriaIdValue && !cantidadCategoriaValue) {
      flash("error", "Si ingresas una categoría, debes ingresar la cantidad.");
      return;
    }

    // 2. Validar: si se ingresó cantidad, debe ingresarse una categoría
    if (!categoriaIdValue && cantidadCategoriaValue) {
      flash(
        "error",
        "Si ingresas una cantidad, debes seleccionar una categoría."
      );
      return;
    }

    // 3. Validar: la cantidad debe ser un número positivo si se ingresa
    if (cantidadCategoriaValue && cantidadCategoriaValue <= 0) {
      flash("error", "La cantidad debe ser mayor a 0.");
      return;
    }

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
      flash("error", "Error al procesar la solicitud. Revisá la consola.");
    }
  });

  // Estado inicial
  setRegistrarMode();
  refrescarTabla();

  // ==== Modal mover categoría ====
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".js-move");
    if (!btn) return;

    const tr = btn.closest("tr");
    const potreroOrigen = tr.dataset.id;
    const modalMover = document.getElementById("moverModal");
    const selectDestino = document.getElementById("potreroDestino");
    const btnConfirm = document.getElementById("confirmMover");
    const btnCancel = document.getElementById("cancelarMover");

    modalMover.style.display = "flex";
    selectDestino.innerHTML = `<option value="">-- Seleccioná potrero destino --</option>`;

    try {
      const potreros = await fetchJSON(`${API}?action=list`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      potreros
        .filter((p) => p.id != potreroOrigen && p.categoriaId == null)
        .forEach((p) => {
          const opt = document.createElement("option");
          opt.value = p.id;
          opt.textContent = p.nombre;
          selectDestino.appendChild(opt);
        });

      btnConfirm.onclick = async () => {
        const destino = selectDestino.value;
        if (!destino) return alert("Seleccioná un potrero destino");
        btnConfirm.disabled = true;

        try {
          const resp = await fetchJSON(`${API}`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
              accion: "moverCategoria",
              idOrigen: potreroOrigen,
              idDestino: destino,
            }),
          });
          flash(resp.tipo, resp.mensaje);
          modalMover.style.display = "none";
          if (resp.tipo === "success") {
            await refrescarTabla();
          }
        } catch (err) {
          console.error(err);
          alert("Error al mover la categoría.");
        } finally {
          btnConfirm.disabled = false;
        }
      };

      btnCancel.onclick = () => {
        modalMover.style.display = "none";
        selectDestino.innerHTML = "";
      };
    } catch (err) {
      console.error(err);
      modalMover.style.display = "none";
      alert("No se pudo cargar la lista de potreros.");
    }
  });

  // 🔹 Escuchar clic fuera del modal para cerrarlo
  document.getElementById("moverModal").addEventListener("click", (e) => {
    if (e.target.id === "moverModal") {
      document.getElementById("cancelarMover").click();
    }
  });
});
