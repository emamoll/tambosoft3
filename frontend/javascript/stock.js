document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("stockForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");
  const formTitle = document.getElementById("form-title");

  // Campos principales
  const alimentoId = document.getElementById("alimentoId");
  const cantidad = document.getElementById("cantidad");
  const produccionInterna = document.getElementById("produccionInterna");
  const proveedorId = document.getElementById("proveedorId");
  const fechaIngreso = document.getElementById("fechaIngreso");

  // Tabla
  const tableBody = document.querySelector(".table-modern tbody");

  // Modales
  const confirmModal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

  // Filtros
  const abrirFiltrosBtn = document.getElementById("abrirFiltros");
  const filtroModal = document.getElementById("filtroModal");
  const aplicarFiltrosBtn = document.getElementById("aplicarFiltros");
  const limpiarFiltrosBtn = document.getElementById("limpiarFiltros");
  const cerrarFiltrosBtn = document.getElementById("cerrarFiltros");
  const resumenFiltros = document.getElementById("resumenFiltros");

  const API = "../../../backend/controladores/stockController.php";

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

  async function fetchJSON(url, options = {}) {
    try {
      const resp = await fetch(url, options);
      const text = await resp.text();

      if (!text.trim()) {
        console.warn("⚠️ Respuesta vacía del servidor:", url);
        return [];
      }

      // Intentar parsear como JSON
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error("❌ Respuesta no JSON del servidor:", text);
        flash("error", "Error en la respuesta del servidor (no JSON).");
        return [];
      }
    } catch (err) {
      console.error("❌ Error en fetch:", err);
      flash("error", "Error de conexión con el servidor.");
      return [];
    }
  }

  // ===== Filtros =====
  const FILTROS = {
    alimentoIds: [],
    produccionInterna: null,
    proveedorIds: [],
  };

  function createCheck(name, value, label, checked = false) {
    const wrap = document.createElement("label");
    wrap.className = "radio-card";

    const input = document.createElement("input");
    input.type = "checkbox";
    input.name = name;
    input.value = value;
    input.checked = !!checked;

    const span = document.createElement("span");
    span.className = "radio-label";
    span.textContent = label;

    wrap.appendChild(input);
    wrap.appendChild(span);
    return wrap;
  }

  function buildCheckGroupFromSelect(
    selectEl,
    containerEl,
    name,
    selected = []
  ) {
    if (!selectEl || !containerEl) return;
    containerEl.innerHTML = "";
    const selectedSet = new Set(selected.map(String));

    Array.from(selectEl.options)
      .filter((o) => o.value)
      .forEach((o) => {
        const checked = selectedSet.has(String(o.value));
        containerEl.appendChild(
          createCheck(name, String(o.value), o.textContent.trim(), checked)
        );
      });
  }

  function prepararChecksModal() {
    const alimentoGroup = document.getElementById("filtroAlimentoGroup");
    const proveedorGroup = document.getElementById("filtroProveedorGroup");
    const produccionGroup = document.getElementById("filtroProduccionGroup");

    if (alimentoGroup) {
      buildCheckGroupFromSelect(
        alimentoId,
        alimentoGroup,
        "filtro_alimento",
        FILTROS.alimentoIds
      );
    }

    if (proveedorGroup) {
      buildCheckGroupFromSelect(
        proveedorId,
        proveedorGroup,
        "filtro_proveedor",
        FILTROS.proveedorIds
      );
    }

    // Producción Interna: dos opciones (Sí / No)
    if (produccionGroup) {
      produccionGroup.innerHTML = "";
      ["Interna", "Externa"].forEach((tipo) => {
        const val = tipo === "Interna" ? "1" : "0";
        produccionGroup.appendChild(
          createCheck(
            "filtro_produccion",
            val,
            tipo,
            FILTROS.produccionInterna === val
          )
        );
      });
    }
  }

  function abrirModalFiltros() {
    prepararChecksModal();
    filtroModal.style.display = "flex";
  }

  function cerrarModalFiltros() {
    filtroModal.style.display = "none";
  }

  abrirFiltrosBtn?.addEventListener("click", abrirModalFiltros);
  cerrarFiltrosBtn?.addEventListener("click", cerrarModalFiltros);
  filtroModal?.addEventListener("click", (e) => {
    if (e.target === filtroModal) cerrarModalFiltros();
  });

  function getCheckedValues(name) {
    return Array.from(
      document.querySelectorAll(`input[name="${name}"]:checked`)
    ).map((i) => i.value);
  }

  function pintarResumenFiltros() {
    const partes = [];
    if (FILTROS.alimentoIds.length)
      partes.push(
        "Alimentos: " +
          FILTROS.alimentoIds
            .map(
              (v) =>
                alimentoId.querySelector(`option[value="${v}"]`)?.textContent
            )
            .join(", ")
      );
    if (FILTROS.proveedorIds.length)
      partes.push(
        "Proveedores: " +
          FILTROS.proveedorIds
            .map(
              (v) =>
                proveedorId.querySelector(`option[value="${v}"]`)?.textContent
            )
            .join(", ")
      );
    if (FILTROS.produccionInterna)
      partes.push(
        "Producción: " +
          (FILTROS.produccionInterna === "1" ? "Interna" : "Externa")
      );

    resumenFiltros.textContent = partes.length
      ? `Filtros → ${partes.join(" · ")}`
      : "";
  }

  limpiarFiltrosBtn?.addEventListener("click", async () => {
    FILTROS.alimentoIds = [];
    FILTROS.proveedorIds = [];
    FILTROS.produccionInterna = null;
    prepararChecksModal();
    pintarResumenFiltros();
    await refrescarTabla();
  });

  aplicarFiltrosBtn?.addEventListener("click", async () => {
    FILTROS.alimentoIds = getCheckedValues("filtro_alimento");
    FILTROS.proveedorIds = getCheckedValues("filtro_proveedor");
    const prod = getCheckedValues("filtro_produccion");
    FILTROS.produccionInterna = prod.length ? prod[0] : null;
    cerrarModalFiltros();
    pintarResumenFiltros();
    await refrescarTabla();
  });

  // ===== Tabla =====
  async function refrescarTabla() {
    const params = new URLSearchParams({ action: "list" });
    FILTROS.alimentoIds.forEach((id) => params.append("alimentoId[]", id));
    FILTROS.proveedorIds.forEach((id) => params.append("proveedorId[]", id));
    if (FILTROS.produccionInterna !== null)
      params.append("produccionInterna", FILTROS.produccionInterna);

    const stocks = await fetchJSON(`${API}?${params.toString()}`, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    tableBody.innerHTML = "";
    if (!Array.isArray(stocks) || stocks.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#666;">No hay lotes de stock para los filtros aplicados.</td></tr>`;
      return;
    }

    function formatearFecha(iso) {
      if (!iso) return "";
      const [y, m, d] = iso.split("-");
      return `${d}/${m}/${y}`;
    }

    for (const s of stocks) {
      const tr = document.createElement("tr");
      tr.dataset.id = s.id;
      tr.dataset.alimentoId = s.alimentoId;
      tr.dataset.cantidad = s.cantidad;
      tr.dataset.produccionInterna = s.produccionInterna;
      tr.dataset.proveedorId = s.proveedorId;
      tr.dataset.fechaIngreso = s.fechaIngreso;

      tr.innerHTML = `
        <td>${s.id}</td>
        <td>${s.alimentoNombre || "-"}</td>
        <td>${s.cantidad}</td>
        <td>${s.produccionInterna == 1 ? "Interna" : "Externa"}</td>
        <td>${s.proveedorNombre || "N/A"}</td>
        <td>${formatearFecha(s.fechaIngreso)}</td>
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon edit js-edit" title="Modificar">✏️</button>
            <button type="button" class="btn-icon delete js-delete" title="Eliminar">🗑️</button>
          </div>
        </td>`;
      tableBody.appendChild(tr);
    }
  }

  // ===== CRUD =====
  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar Lote";
    formTitle.textContent = "Registrar Lote de Stock";
    cancelarEdicion.style.display = "none";
    form.reset();
  }

  function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar Lote";
    formTitle.textContent = "Modificar Lote de Stock";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;
    alimentoId.value = data.alimentoId;
    cantidad.value = data.cantidad;
    produccionInterna.checked = data.produccionInterna == 1;
    proveedorId.value = data.proveedorId;
    fechaIngreso.value = data.fechaIngreso;
  }

  cancelarEdicion.addEventListener("click", setRegistrarMode);

  tableBody.addEventListener("click", (e) => {
    const editBtn = e.target.closest(".js-edit");
    const delBtn = e.target.closest(".js-delete");
    if (editBtn) {
      const tr = editBtn.closest("tr");
      setEditarMode({
        id: tr.dataset.id,
        alimentoId: tr.dataset.alimentoId,
        cantidad: tr.dataset.cantidad,
        produccionInterna: tr.dataset.produccionInterna,
        proveedorId: tr.dataset.proveedorId,
        fechaIngreso: tr.dataset.fechaIngreso,
      });
      return;
    }
    if (delBtn) {
      const tr = delBtn.closest("tr");
      confirmText.textContent = `¿Seguro que deseás eliminar el lote de ID ${tr.dataset.id}?`;
      confirmModal.dataset.id = tr.dataset.id;
      confirmModal.style.display = "flex";
      return;
    }
  });

  confirmYes.addEventListener("click", async () => {
    const id = confirmModal.dataset.id;
    confirmModal.style.display = "none";
    if (!id) return;
    const fd = new FormData();
    fd.append("accion", "eliminar");
    fd.append("id", id);
    const data = await fetchJSON(API, { method: "POST", body: fd });
    if (data.tipo) flash(data.tipo, data.mensaje);
    if (data.tipo === "success") await refrescarTabla();
  });

  confirmNo.addEventListener(
    "click",
    () => (confirmModal.style.display = "none")
  );

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!alimentoId.value || !cantidad.value || !fechaIngreso.value)
      return flash("error", "Completá todos los campos obligatorios.");
    const fd = new FormData(form);
    const data = await fetchJSON(API, {
      method: "POST",
      body: fd,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    if (data.tipo) flash(data.tipo, data.mensaje);
    if (data.tipo === "success") {
      await refrescarTabla();
      setRegistrarMode();
    }
  });

  // ===== Inicialización =====
  setRegistrarMode();
  pintarResumenFiltros();
  refrescarTabla();
});
