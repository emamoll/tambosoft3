// frontend/javascript/stock.js
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("stockForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");
  const formTitle = document.getElementById("form-title");

  // Campos del formulario CRUD de lotes
  const alimentoId = document.getElementById("alimentoId");
  const cantidad = document.getElementById("cantidad");
  const produccionInterna = document.getElementById("produccionInterna");
  const proveedorId = document.getElementById("proveedorId");
  const proveedorGroup = document.getElementById("proveedorGroup");
  const almacenId = document.getElementById("almacenId");
  const fechaIngreso = document.getElementById("fechaIngreso");

  // Elementos de STOCK TOTAL (para consulta de stock disponible)
  const alimentoStockTotal = document.getElementById("alimentoStockTotal");
  const stockActualInfo = document.getElementById("stockActualInfo");

  // Elementos de FILTRADO (Modal estilo Potrero)
  const abrirFiltrosBtn = document.getElementById("abrirFiltros");
  const filtroModal = document.getElementById("filtroModal");
  const aplicarFiltrosBtn = document.getElementById("aplicarFiltros");
  const limpiarFiltrosBtn = document.getElementById("limpiarFiltros");
  const cerrarFiltrosBtn = document.getElementById("cerrarFiltros");
  const resumenFiltros = document.getElementById("resumenFiltros");

  const filtroAlimentoGroup = document.getElementById("filtroAlimentoGroup");
  const filtroAlmacenGroup = document.getElementById("filtroAlmacenGroup");
  const filtroOrigenGroup = document.getElementById("filtroOrigenGroup");

  // Modal Eliminar
  const confirmModal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

  const tableBody = document.querySelector(".table-modern tbody");

  const API = "../../../backend/controladores/stockController.php";

  // Almacenar el estado actual de los filtros (similar a potrero.js)
  let FILTROS = {
    alimentoIds: [], // array de IDs (multi-select)
    produccionInternaIds: [], // array de IDs ('1' o '0')
    almacenIds: [], // array de IDs
  };

  // ===== Helpers =====
  function flash(tipo, mensaje) {
    let alertContainer = document
      .getElementById("stockForm")
      .closest(".form-container");
    let alertBox = alertContainer.querySelector(".alert");
    if (!alertBox) {
      alertBox = document.createElement("div");
      alertBox.className = "alert";
      const h2 = alertContainer.querySelector("h2");
      h2.insertAdjacentElement("afterend", alertBox);
    }
    alertBox.className =
      "alert " + (tipo === "success" ? "alert-success" : "alert-danger");
    alertBox.textContent = mensaje;
  }

  async function fetchJSON(url, options = {}) {
    const resp = await fetch(url, options);
    const ct = resp.headers.get("content-type") || "";
    const text = await resp.text();

    if (!ct.includes("application/json")) {
      console.error("[Backend NON-JSON]", {
        url,
        status: resp.status,
        contentType: ct,
        preview: text.slice(0, 400),
      });
      try {
        const jsonError = JSON.parse(text);
        throw new Error(jsonError.mensaje || "Respuesta no JSON del backend.");
      } catch (e) {
        throw new Error("Respuesta no JSON/JSON inválido del backend.");
      }
    }

    return JSON.parse(text);
  }

  // Construye un mapa de {value: text} desde un select
  function buildLovMap(selectEl) {
    const map = {};
    if (!selectEl) return map;
    Array.from(selectEl.options).forEach((opt) => {
      if (opt.value) map[opt.value] = opt.textContent.trim();
    });
    return map;
  }

  // ===== LOV maps (Para el resumen de filtros) =====
  const LOVS = {
    alimento: buildLovMap(alimentoId),
    almacen: buildLovMap(almacenId),
    origen: {
      1: "Interna",
      0: "Compra/Movimiento",
    },
  };

  // Controla la editabilidad del Proveedor.
  function toggleProveedorFields(isInterna) {
    if (isInterna) {
      proveedorId.value = "";
      proveedorId.disabled = true;
      proveedorId.required = false;

      proveedorGroup.style.opacity = 0.5;
      proveedorGroup.style.pointerEvents = "none";
    } else {
      proveedorId.disabled = false;
      proveedorId.required = true;

      proveedorGroup.style.opacity = 1;
      proveedorGroup.style.pointerEvents = "auto";
    }
  }

  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar Lote";
    formTitle.textContent = "Registrar Lote de Stock";
    cancelarEdicion.style.display = "none";
    idInput.value = "";
    form.reset();

    almacenId.disabled = false;

    produccionInterna.checked = false;
    toggleProveedorFields(false);

    document
      .querySelectorAll(".error-message")
      .forEach((el) => (el.style.display = "none"));
  }

  function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar Lote";
    formTitle.textContent = "Modificar Lote de Stock";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;
    alimentoId.value = data.alimentoId;
    cantidad.value = data.cantidad;
    fechaIngreso.value = data.fechaIngreso;
    almacenId.value = data.almacenId;

    almacenId.disabled = true;

    const isInterna = data.produccionInterna === 1;
    produccionInterna.checked = isInterna;

    toggleProveedorFields(isInterna);

    proveedorId.value = data.proveedorId || "";

    alimentoId.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function extractDataFromRow(tr) {
    return {
      id: tr.dataset.id,
      alimentoId: tr.dataset.alimentoId,
      cantidad: tr.dataset.cantidad,
      produccionInterna: parseInt(tr.dataset.produccionInterna),
      proveedorId: tr.dataset.proveedorId,
      almacenId: tr.dataset.almacenId,
      fechaIngreso: tr.dataset.fechaIngreso,
      alimentoNombre: tr.dataset.alimentoNombre,
      proveedorNombre: tr.dataset.proveedorNombre,
      almacenNombre: tr.dataset.almacenNombre,
    };
  }

  produccionInterna.addEventListener("change", () => {
    toggleProveedorFields(produccionInterna.checked);
  });

  // ===== Lógica de Filtros Estilo Potrero (Funciones de Soporte) =====

  function createCheck(name, value, label, checked = false) {
    const wrap = document.createElement("label");
    wrap.className = "radio-card";

    const input = document.createElement("input");
    input.type = "checkbox"; // Usamos checkbox para permitir la simulación de "todos" y multi-select.
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
    selectedValues = []
  ) {
    if (!selectEl || !containerEl) return;
    containerEl.innerHTML = ""; // Limpiar

    const selectedSet = new Set((selectedValues || []).map(String));

    // 1. Opción "Todos"
    const allIsSelected = selectedValues.length === 0;
    containerEl.appendChild(
      createCheck(name, "0", `-- Todos --`, allIsSelected)
    );

    // 2. Opciones del Select
    Array.from(selectEl.options)
      .filter((o) => o.value)
      .forEach((o) => {
        const checked = selectedSet.has(String(o.value));
        containerEl.appendChild(
          createCheck(name, String(o.value), o.textContent.trim(), checked)
        );
      });
  }

  function buildOrigenGroup(containerEl, selectedValues = []) {
    containerEl.innerHTML = "";
    const selectedSet = new Set((selectedValues || []).map(String));

    // 1. Opción "Todos" (valor -1)
    const allIsSelected = selectedValues.length === 0;
    containerEl.appendChild(
      createCheck("filtro_origen", "-1", `-- Todos --`, allIsSelected)
    );

    // 2. Opción Interna (valor 1)
    containerEl.appendChild(
      createCheck("filtro_origen", "1", LOVS.origen["1"], selectedSet.has("1"))
    );

    // 3. Opción Compra/Movimiento (valor 0)
    containerEl.appendChild(
      createCheck("filtro_origen", "0", LOVS.origen["0"], selectedSet.has("0"))
    );
  }

  function prepararChecksModal() {
    filtroAlmacenGroup.innerHTML = "";
    filtroAlimentoGroup.innerHTML = "";
    filtroOrigenGroup.innerHTML = "";

    // 1. Almacén
    buildCheckGroupFromSelect(
      almacenId,
      filtroAlmacenGroup,
      "filtro_almacen",
      FILTROS.almacenIds
    );

    // 2. Alimento
    buildCheckGroupFromSelect(
      alimentoId,
      filtroAlimentoGroup,
      "filtro_alimento",
      FILTROS.alimentoIds
    );

    // 3. Origen
    buildOrigenGroup(filtroOrigenGroup, FILTROS.produccionInternaIds);
  }

  function getCheckedValues(name) {
    return Array.from(
      document.querySelectorAll(`#filtroModal input[name="${name}"]:checked`)
    ).map((i) => i.value);
  }

  function pintarResumenFiltros() {
    const partes = [];

    // 1. Almacén
    const almacenFiltro = FILTROS.almacenIds;
    if (almacenFiltro.length === 0) {
      partes.push("Almacén: Todos");
    } else {
      const nombres = almacenFiltro.map((id) => LOVS.almacen[id] || id);
      partes.push(`Almacén: ${nombres.join(", ")}`);
    }

    // 2. Alimento
    const alimentoFiltro = FILTROS.alimentoIds;
    if (alimentoFiltro.length === 0) {
      partes.push("Alimento: Todos");
    } else {
      const nombres = alimentoFiltro.map((id) => LOVS.alimento[id] || id);
      partes.push(`Alimento: ${nombres.join(", ")}`);
    }

    // 3. Origen
    const origenFiltro = FILTROS.produccionInternaIds;
    if (origenFiltro.length === 0) {
      partes.push("Origen: Todos");
    } else {
      const nombres = origenFiltro.map((id) => LOVS.origen[id] || id);
      partes.push(`Origen: ${nombres.join(", ")}`);
    }

    resumenFiltros.textContent = partes.length
      ? `Filtros → ${partes.join(" · ")}`
      : "No hay filtros aplicados";
  }

  /**
   * Refresca la tabla aplicando los filtros almacenados en FILTROS.
   */
  async function refrescarTabla() {
    const filterParams = new URLSearchParams();
    filterParams.append("action", "list");

    // Se envían múltiples IDs si están seleccionados (ej: alimentoId=1&alimentoId=5)
    (FILTROS.alimentoIds || []).forEach((id) =>
      filterParams.append("alimentoId", id)
    );
    (FILTROS.almacenIds || []).forEach((id) =>
      filterParams.append("almacenId", id)
    );

    // Producción Interna solo se envía si hay una selección simple ('0' o '1')
    if (FILTROS.produccionInternaIds.length === 1) {
      filterParams.append("produccionInterna", FILTROS.produccionInternaIds[0]);
    } else {
      // Si se selecciona "Todos" o si se seleccionan "Interna" y "Compra/Movimiento",
      // o si no se selecciona nada, se pasa un valor que el controlador ignora (-1).
      filterParams.append("produccionInterna", "-1");
    }

    try {
      const url = `${API}?${filterParams.toString()}`;
      const stocks = await fetchJSON(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      tableBody.innerHTML = "";

      pintarResumenFiltros();

      // Colspan ajustado a 7
      if (!Array.isArray(stocks) || stocks.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#666;">No hay lotes de stock registrados con los filtros aplicados.</td></tr>`;
        return;
      }

      function formatearFecha(iso) {
        if (!iso) return "";
        const [y, m, d] = iso.split("-");
        return `${d}-${m}-${y}`;
      }

      for (const s of stocks) {
        const tr = document.createElement("tr");

        const _id = s.id ?? "";
        const _cantidad = s.cantidad ?? 0;
        const _isInterna = s.produccionInterna == 1;
        const _proveedorNombre = s.proveedorNombre ?? "";
        const _almacenNombre = s.almacenNombre ?? "N/A";

        // Almacenamos todos los data-set
        tr.dataset.id = _id;
        tr.dataset.alimentoId = s.alimentoId;
        tr.dataset.cantidad = _cantidad;
        tr.dataset.produccionInterna = s.produccionInterna;
        tr.dataset.proveedorId = s.proveedorId;
        tr.dataset.almacenId = s.almacenId;
        tr.dataset.fechaIngreso = s.fechaIngreso;
        tr.dataset.alimentoNombre = s.alimentoNombre;
        tr.dataset.proveedorNombre = _proveedorNombre;
        tr.dataset.almacenNombre = _almacenNombre;

        const cantidadColor =
          _cantidad < 0
            ? "color: #c0392b; font-weight: bold;"
            : "color: #27ae60; font-weight: bold;";

        tr.innerHTML = `
          <td>${_id}</td>
          <td>${s.alimentoNombre ?? "N/A"}</td>
          <td style="${cantidadColor}">${_cantidad}</td>
          <td>${_almacenNombre}</td>
          <td>${_isInterna ? "Interna" : "Compra/Movimiento"}</td>
          <td>${_isInterna ? "N/A" : _proveedorNombre || "N/A"}</td>
          <td>${formatearFecha(s.fechaIngreso)}</td>
          <td>
            <div class="table-actions">
              ${
                _cantidad > 0
                  ? `
                <button type="button" class="btn-icon edit js-edit" title="Modificar">✏️</button>
                <button type="button" class="btn-icon delete js-delete" title="Eliminar">🗑️</button>
              `
                  : `
                <span title="Los movimientos de salida/ajuste (cant. negativa) no se editan.">➖</span>
              `
              }
            </div>
          </td>
        `;
        tableBody.appendChild(tr);
      }

      await actualizarStockActual();
    } catch (err) {
      console.error(err);
      flash("error", "Error cargando la tabla de stock.");
    }
  }

  // ==== Eventos de Filtros (Modal) ====

  abrirFiltrosBtn?.addEventListener("click", () => {
    prepararChecksModal(); // Llenar el modal con el estado actual de FILTROS
    filtroModal.style.display = "flex";
  });

  cerrarFiltrosBtn?.addEventListener("click", () => {
    filtroModal.style.display = "none";
  });

  // Manejo de clic fuera del modal para cerrar
  filtroModal?.addEventListener("click", (e) => {
    if (e.target === filtroModal) {
      filtroModal.style.display = "none";
    }
  });

  limpiarFiltrosBtn?.addEventListener("click", async () => {
    // Restablecer el estado del objeto FILTROS
    FILTROS.alimentoIds = [];
    FILTROS.produccionInternaIds = [];
    FILTROS.almacenIds = [];

    cerrarFiltrosBtn.click(); // Cerrar el modal
    pintarResumenFiltros();
    await refrescarTabla();
  });

  aplicarFiltrosBtn?.addEventListener("click", async () => {
    // 1. Obtener valores checked (Multi-select)
    let nuevosAlmacenes = getCheckedValues("filtro_almacen");
    let nuevosAlimentos = getCheckedValues("filtro_alimento");
    let nuevosOrigenes = getCheckedValues("filtro_origen");

    // 2. Limpiar valores "Todos" ('0' y '-1')
    FILTROS.almacenIds = nuevosAlmacenes.filter((id) => id !== "0");
    FILTROS.alimentoIds = nuevosAlimentos.filter((id) => id !== "0");
    FILTROS.produccionInternaIds = nuevosOrigenes.filter((id) => id !== "-1"); // '-1' es "Todos" para Origen

    cerrarFiltrosBtn.click();
    await refrescarTabla();
  });

  // ==== Lógica de Stock Total (Consulta) ====
  async function actualizarStockActual() {
    const selectedId = alimentoStockTotal.value;
    if (!selectedId) {
      stockActualInfo.textContent = "Stock total: N/A";
      return;
    }
    try {
      const data = await fetchJSON(
        `${API}?action=getStockTotal&alimentoId=${selectedId}`,
        {
          headers: { "X-Requested-With": "XMLHttpRequest" },
        }
      );
      stockActualInfo.textContent = `Stock total: ${data.total}`;
    } catch (err) {
      stockActualInfo.textContent = "Stock total: Error";
      console.error("Error al obtener stock total:", err);
    }
  }

  alimentoStockTotal.addEventListener("change", actualizarStockActual);

  // ==== Eventos CRUD Lotes (Formulario principal) ====
  tableBody.addEventListener("click", (e) => {
    const editBtn = e.target.closest(".js-edit");
    const delBtn = e.target.closest(".js-delete");

    if (editBtn) {
      const tr = editBtn.closest("tr");
      const data = extractDataFromRow(tr);
      if (parseInt(data.cantidad) <= 0) {
        flash("error", "Solo puedes modificar lotes de entrada (> 0).");
        return;
      }
      setEditarMode(data);
      return;
    }

    if (delBtn) {
      const tr = delBtn.closest("tr");
      const data = extractDataFromRow(tr);
      if (parseInt(data.cantidad) <= 0) {
        flash(
          "error",
          "Los movimientos de salida (cantidad negativa) no pueden eliminarse directamente. Deben corregirse con un nuevo registro de anulación si fuera necesario."
        );
        return;
      }
      confirmText.textContent = `¿Seguro que deseás eliminar el lote de ${data.alimentoNombre} (ID ${data.id})? Esta acción no se puede deshacer.`;
      confirmModal.dataset.id = data.id;
      confirmModal.style.display = "flex";
      return;
    }
  });

  confirmYes?.addEventListener("click", async () => {
    const id = confirmModal.dataset.id;
    confirmModal.style.display = "none";
    delete confirmModal.dataset.id;
    if (!id) return;

    const fd = new FormData();
    fd.append("accion", "eliminar");
    fd.append("id", id);
    try {
      const data = await fetchJSON(API, { method: "POST", body: fd });
      flash(data.tipo, data.mensaje);
      if (data.tipo === "success") {
        await refrescarTabla();
        setRegistrarMode();
      }
    } catch (err) {
      console.error(err);
      flash("error", "Error al eliminar el lote de stock.");
    }
  });

  confirmNo?.addEventListener("click", () => {
    confirmModal.style.display = "none";
    delete confirmModal.dataset.id;
  });

  cancelarEdicion.addEventListener("click", setRegistrarMode);

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    let ok = true;
    const isInterna = produccionInterna.checked;

    // Validación: campos básicos
    if (
      !alimentoId.value ||
      parseInt(cantidad.value) <= 0 ||
      !fechaIngreso.value ||
      !almacenId.value
    ) {
      flash(
        "error",
        "Alimento, Cantidad (> 0), Fecha de Ingreso y Almacén son obligatorios."
      );
      ok = false;
    }

    // Validación de proveedor solo si NO es producción interna
    if (!isInterna && !proveedorId.value) {
      flash(
        "error",
        "Si no es producción interna, el proveedor es obligatorio."
      );
      ok = false;
    }

    if (!ok) return;

    const fd = new FormData(form);

    // Limpiamos proveedorId si es interno
    if (isInterna) {
      fd.set("proveedorId", "");
    }
    // Si estamos editando, nos aseguramos de que el campo de almacén, que es disabled, se envíe
    if (accionInput.value === "modificar" && almacenId.disabled) {
      fd.set("almacenId", almacenId.value);
    }
    fd.set("produccionInterna", isInterna ? "on" : "");

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
      flash("error", err.message || "Error al procesar la solicitud del lote.");
    }
  });

  // ==== Inicialización ====
  setRegistrarMode();
  refrescarTabla();
});
