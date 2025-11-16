document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("stockForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");
  const formTitle = document.getElementById("form-title");

  const almacenId = document.getElementById("almacenId");
  const tipoAlimentoId = document.getElementById("tipoAlimentoId");
  const alimentoId = document.getElementById("alimentoId");
  const cantidad = document.getElementById("cantidad");
  const produccionInternaCheck = document.getElementById(
    "produccionInternaCheck"
  );
  const produccionInternaValor = document.getElementById(
    "produccionInternaValor"
  );
  const proveedorId = document.getElementById("proveedorId");
  const precio = document.getElementById("precio");
  const fechaIngreso = document.getElementById("fechaIngreso");

  const tableBody = document.querySelector(".table-modern tbody");

  // Modal eliminar
  const modal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

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
    if (!selectEl) return map;
    Array.from(selectEl.options).forEach((opt) => {
      if (opt.value) map[opt.value] = opt.textContent.trim();
    });
    return map;
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
      throw new Error("Respuesta no JSON del backend.");
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      console.error("[JSON Parse Error]", text.slice(0, 400));
      throw e;
    }
  }

  // ===== LOV maps (Única y correcta inicialización) =====
  const LOVS = {
    almacen: buildLovMap(almacenId),
    tipoAlimento: buildLovMap(tipoAlimentoId),
    alimento: buildLovMap(alimentoId),
    proveedor: buildLovMap(proveedorId),
  };
  // ==============================================

  // ===== Función para habilitar/deshabilitar campos según Producción Propia =====
  function toggleProveedorYPrecio() {
    const isChecked = produccionInternaCheck.checked;
    proveedorId.disabled = isChecked;
    precio.disabled = isChecked;

    if (isChecked) {
      proveedorId.classList.add("disabled");
      precio.classList.add("disabled");
      // Si se deshabilitan, limpia los errores visuales
      const errorProveedor = document.getElementById("error-proveedorId");
      if (errorProveedor) {
        errorProveedor.style.display = "none";
      }
      const errorPrecio = document.getElementById("error-precio");
      if (errorPrecio) {
        errorPrecio.style.display = "none";
      }
    } else {
      proveedorId.classList.remove("disabled");
      precio.classList.remove("disabled");
    }
  }

  // Ejecutar al cargar la página para revisar si el checkbox está marcado
  toggleProveedorYPrecio();

  // Escuchar el cambio en el checkbox
  produccionInternaCheck.addEventListener("change", toggleProveedorYPrecio);

  // ===== Filtros (Resto de funciones de filtro se mantienen iguales) =====
  // ...
  const abrirFiltrosBtn = document.getElementById("abrirFiltros");
  const filtroModal = document.getElementById("filtroModal");
  const aplicarFiltrosBtn = document.getElementById("aplicarFiltros");
  const limpiarFiltrosBtn = document.getElementById("limpiarFiltros");
  const cerrarFiltrosBtn = document.getElementById("cerrarFiltros");
  const resumenFiltros = document.getElementById("resumenFiltros");

  const FILTROS = {
    campoIds: [],
    tiposAlimentosIds: [],
    alimentosIds: [],
    proveedoresIds: [],
    produccionesInternas: false,
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
    selectedValues = []
  ) {
    if (!selectEl || !containerEl) return;
    const selectedSet = new Set((selectedValues || []).map(String));
    // NO agregamos "Cualquiera"
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
    const campoGroup = document.getElementById("filtroAlmacenGroup");
    const tipoAlimentoGroup = document.getElementById(
      "filtroTipoAlimentoGroup"
    );
    const alimentoGroup = document.getElementById("filtroAlimentoGroup");
    const proveedorGroup = document.getElementById("filtroProveedorGroup");

    if (
      !campoGroup ||
      !tipoAlimentoGroup ||
      !alimentoGroup ||
      !proveedorGroup
    ) {
      console.error("Faltan contenedores de filtros");
      return;
    }

    campoGroup.innerHTML = "";
    tipoAlimentoGroup.innerHTML = "";
    alimentoGroup.innerHTML = "";
    proveedorGroup.innerHTML = "";

    buildCheckGroupFromSelect(
      almacenId,
      campoGroup,
      "filtro_almacen",
      FILTROS.campoIds
    );
    buildCheckGroupFromSelect(
      tipoAlimentoId,
      tipoAlimentoGroup,
      "filtro_tipoAlimento",
      FILTROS.tiposAlimentosIds
    );
    buildCheckGroupFromSelect(
      alimentoId,
      alimentoGroup,
      "filtro_alimento",
      FILTROS.alimentosIds
    );
    buildCheckGroupFromSelect(
      proveedorId,
      proveedorGroup,
      "filtro_proveedor",
      FILTROS.proveedoresIds
    );
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

    // Suponiendo que LOVS.campo se refiere a los almacenes
    if (FILTROS.campoIds.length) {
      const nombres = FILTROS.campoIds.map((id) => LOVS.almacen[id] || id);
      partes.push(`Campo: ${nombres.join(", ")}`);
    }
    if (FILTROS.tiposAlimentosIds.length) {
      const nombres = FILTROS.tiposAlimentosIds.map(
        (id) => LOVS.tipoAlimento[id] || id
      );
      partes.push(`Tipo alimento: ${nombres.join(", ")}`);
    }
    if (FILTROS.alimentosIds.length) {
      const nombres = FILTROS.alimentosIds.map((id) => LOVS.alimento[id] || id);
      partes.push(`Alimento: ${nombres.join(", ")}`);
    }
    if (FILTROS.proveedoresIds.length) {
      const nombres = FILTROS.proveedoresIds.map(
        (id) => LOVS.proveedor[id] || id
      );
      partes.push(`Proveedor: ${nombres.join(", ")}`);
    }

    resumenFiltros.textContent = partes.length
      ? `Filtros → ${partes.join(" · ")}`
      : "";
  }

  limpiarFiltrosBtn?.addEventListener("click", async () => {
    FILTROS.campoIds = [];
    FILTROS.tiposAlimentosIds = [];
    FILTROS.alimentosIds = [];
    FILTROS.proveedoresIds = [];
    prepararChecksModal();
    pintarResumenFiltros();
    await refrescarTabla();
  });

  aplicarFiltrosBtn?.addEventListener("click", async () => {
    FILTROS.campoIds = getCheckedValues("filtro_almacen"); // Usar el nombre correcto del filtro de almacén
    FILTROS.tiposAlimentosIds = getCheckedValues("filtro_tipoAlimento");
    FILTROS.alimentosIds = getCheckedValues("filtro_alimento");
    FILTROS.proveedoresIds = getCheckedValues("filtro_proveedor");
    cerrarModalFiltros();
    pintarResumenFiltros();
    await refrescarTabla();
  });

  // ===== Modo form =====
  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar";
    formTitle.textContent = "Registrar Stock";
    cancelarEdicion.style.display = "none";
    idInput.value = "";
    form.reset();
    toggleProveedorYPrecio(); // Restaurar el estado inicial (Proveedor/Precio activado por defecto)
    // Inicializar el valor oculto a '0' (Comprado) al registrar
    if (produccionInternaValor) produccionInternaValor.value = "0";

    // Ocultar mensajes de error al registrar
    document
      .querySelectorAll(".error-message")
      .forEach((el) => (el.style.display = "none"));
  }

  function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar";
    formTitle.textContent = "Modificar Stock";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;
    almacenId.value = data.almacenId || "";
    tipoAlimentoId.value = data.tipoAlimentoId || "";
    alimentoId.value = data.alimentoId || "";
    cantidad.value = data.cantidad || "";
    produccionInternaCheck.checked = data.produccionInterna == 1;
    proveedorId.value = data.proveedorId || "";
    precio.value = data.precio || "";
    fechaIngreso.value = data.fechaIngreso || "";

    toggleProveedorYPrecio(); // Re-aplicar la lógica de deshabilitar/activar campos

    // Sincronizar el valor oculto con el estado del checkbox al entrar en edición
    if (produccionInternaValor)
      produccionInternaValor.value = data.produccionInterna == 1 ? "1" : "0";

    cantidad.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  cancelarEdicion.addEventListener("click", setRegistrarMode);

  function extractDataFromRow(tr) {
    return {
      id: tr.dataset.id,
      tipoAlimentoId: tr.dataset.tipoAlimentoId,
      almacenId: tr.dataset.almacenId,
      alimentoId: tr.dataset.alimentoId,
      cantidad: tr.dataset.cantidad,
      produccionInterna: tr.dataset.produccionInterna,
      proveedorId: tr.dataset.proveedorId,
      precio: tr.dataset.precio,
      fechaIngreso: tr.dataset.fechaIngreso,
    };
  }

  // ===== Tabla (Manteniendo la funcionalidad de refrescar) =====
  async function refrescarTabla() {
    try {
      // ... (Código para refrescar tabla, mantenido igual)
      const stocks = await fetchJSON(`${API}?action=list`, {
        // Cambiado a 'list' para ser coherente con el controlador
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      tableBody.innerHTML = "";

      if (!Array.isArray(stocks) || stocks.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:#666;">No hay stock registrado.</td></tr>`;
        return;
      }

      for (const stock of stocks) {
        const tr = document.createElement("tr");

        tr.dataset.id = stock.id ?? "";
        tr.dataset.almacenId = stock.almacenId ?? "";
        tr.dataset.tipoAlimentoId = stock.tipoAlimentoId ?? "";
        tr.dataset.alimentoId = stock.alimentoId ?? "";
        tr.dataset.cantidad = stock.cantidad ?? "";
        tr.dataset.produccionInterna = stock.produccionInterna ?? "";
        tr.dataset.proveedorId = stock.proveedorId ?? "";
        tr.dataset.precio = stock.precio ?? "";
        tr.dataset.fechaIngreso = stock.fechaIngreso ?? "";

        tr.innerHTML = `
          <td>${stock.id}</td>
          <td>${stock.almacenNombre}</td>
          <td>${stock.tipoAlimentoNombre}</td>
          <td>${stock.alimentoNombre}</td>
          <td>${stock.cantidad}</td>
          <td>${stock.produccionInterna == 1 ? "Propia" : "Proveedor"}</td>
          <td>${stock.proveedorNombre || "N/A"}</td>
          <td>${stock.precio}</td>
          <td>${stock.fechaIngreso}</td>
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
      tableBody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:#a00;">Error cargando tabla.</td></tr>`;
    }
  }
  // ... (Editar / Eliminar se mantienen iguales)

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
      confirmText.textContent = `¿Seguro que deseas eliminar el stock de "${data.alimentoNombre}"?`;
      modal.dataset.id = data.id;
      modal.style.display = "flex";
      return;
    }
  });

  confirmYes?.addEventListener("click", async () => {
    const id = modal.dataset.id;
    modal.style.display = "none";
    delete modal.dataset.id;
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
      flash("error", "Error al eliminar el stock.");
    }
  });

  confirmNo?.addEventListener("click", () => {
    modal.style.display = "none";
    delete modal.dataset.id;
  });

  // ==== Submit Form (Lógica de validación principal) ====
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // 🎯 Sincronizar el checkbox con el input hidden antes de la validación y el envío
    if (produccionInternaValor) {
      produccionInternaValor.value = produccionInternaCheck.checked ? "1" : "0";
    }
    const isProduccionInterna = produccionInternaValor.value === "1";

    // 🔹 Validaciones específicas del formulario
    let ok = true;
    let hasUniversalError = false;

    // --- 1. Validaciones universales (Almacén, Tipo Alimento, Alimento, Cantidad, Fecha) ---

    const universalFields = [
      { el: almacenId, errId: "error-almacenId" },
      { el: tipoAlimentoId, errId: "error-tipoAlimentoId" },
      { el: alimentoId, errId: "error-alimentoId" },
      { el: fechaIngreso, errId: "error-fecha" },
    ];

    // Validación de selects/inputs de texto/fecha
    universalFields.forEach(({ el, errId }) => {
      const errorEl = document.getElementById(errId);
      if (!el.value.trim()) {
        errorEl.style.display = "block";
        hasUniversalError = true;
      } else {
        errorEl.style.display = "none";
      }
    });

    // Validación de Cantidad (debe ser número entero > 0)
    const cantNum = Number(cantidad.value);
    const cantValida = Number.isInteger(cantNum) && cantNum > 0;
    const cantidadError = document.getElementById("error-cantidad");
    if (!cantidad.value.trim() || !cantValida) {
      cantidadError.style.display = "block";
      hasUniversalError = true;
    } else {
      cantidadError.style.display = "none";
    }

    // Actualizar el estado general de ok
    if (hasUniversalError) ok = false;

    // --- 2. Validaciones condicionales (Proveedor y Precio) ---

    const proveedorError = document.getElementById("error-proveedorId");
    const precioError = document.getElementById("error-precio");
    let hasConditionalError = false;

    if (!isProduccionInterna) {
      // Validación de Proveedor
      if (!proveedorId.value.trim()) {
        proveedorError.style.display = "block";
        hasConditionalError = true;
      } else {
        proveedorError.style.display = "none";
      }

      // Validación de Precio (debe ser un número >= 0)
      const precioValido =
        precio.value.trim() &&
        !isNaN(Number(precio.value)) &&
        Number(precio.value) >= 0;
      if (!precioValido) {
        precioError.style.display = "block";
        hasConditionalError = true;
      } else {
        precioError.style.display = "none";
      }

      if (hasConditionalError) ok = false;
    } else {
      // Si es producción interna, ocultamos errores de proveedor/precio
      proveedorError.style.display = "none";
      precioError.style.display = "none";
    }

    // --- 3. Finalización de validación y envío ---

    if (!ok) {
      // Mostrar un mensaje de error general si falló alguna validación
      if (hasUniversalError) {
        flash("error", "Revisá los campos obligatorios marcados (*).");
      } else if (hasConditionalError) {
        flash(
          "error",
          "Para producción comprada, Proveedor y Precio son obligatorios."
        );
      }
      return;
    }

    // Si todo está bien, procede con el envío
    const fd = new FormData(form);
    try {
      const data = await fetchJSON(API, {
        method: "POST",
        body: fd,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      // Si el servidor retorna un error, se muestra.
      // Ya no necesitamos la lógica para detectar el error de "Completá los campos obligatorios"
      // porque ahora JS atrapa todos los casos de campos vacíos.
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

  // ===== Inicializar =====
  setRegistrarMode();
  refrescarTabla();
});
