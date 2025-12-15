let currentFiltros = {}; // Variable global para almacenar el estado actual de los filtros

document.addEventListener("DOMContentLoaded", () => {

  const API = "../../../backend/controladores/ordenController.php";

  // --- FORM ---
  const form = document.getElementById("ordenForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");

  // Reemplazado potreroId por categoriaId
  const categoriaId = document.getElementById("categoriaId");
  const almacenId = document.getElementById("almacenId");
  const tipoAlimentoId = document.getElementById("tipoAlimentoId");
  const alimentoId = document.getElementById("alimentoId");
  const cantidad = document.getElementById("cantidad");
  const usuarioId = document.getElementById("usuarioId");
  const btnCerrarAuditoria = document.getElementById("btnCerrarAuditoria");
  const modalAuditoria = document.getElementById("modalAuditoriaOrden");
  const grupoMotivo = document.getElementById("grupoMotivo");
  const motivoInput = document.getElementById("motivo");

  // Elemento para mostrar el potrero
  const potreroAsignadoDisplay = document.getElementById(
    "potreroAsignadoDisplay"
  );

  const stockDisplay = document.getElementById("stockDisplay");
  const errorStockInsuficiente = document.getElementById(
    "error-stock-insuficiente"
  );

  // Contenedor de mensajes de sistema
  const systemMessageContainer = document.getElementById(
    "system-message-container"
  );

  // --- TABLA PRINCIPAL ---
  const tableBody = document.querySelector("#tablaOrdenPrincipal tbody");

  // --- MODAL ELIMINAR ---
  const modal = document.getElementById("confirmModal");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");
  const confirmText = document.getElementById("confirmText");

  // --- MODAL FILTRO ---
  const filtroModal = document.getElementById("filtroModal");
  const cerrarFiltrosBtn = document.getElementById("cerrarFiltros");
  const abrirFiltrosBtn = document.getElementById("abrirFiltros");
  const aplicarFiltrosBtn = document.getElementById("aplicarFiltros");
  const limpiarFiltrosBtn = document.getElementById("limpiarFiltros");

  // --- RESUMEN FILTROS ---
  const resumenFiltros = document.getElementById("resumenFiltros");

  // ----------------------------------------------------
  // HELPER PARA LLAMAR AL BACKEND Y ASEGURAR JSON
  // ----------------------------------------------------
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
      // Devolver una respuesta de error para ser consistente
      return { tipo: "error", mensaje: "Respuesta inválida del servidor." };
    }

    try {
      return JSON.parse(raw);
    } catch (e) {
      console.error("[JSON Parse Error]", raw.slice(0, 400), e);
      // Devolver una respuesta de error para ser consistente
      return {
        tipo: "error",
        mensaje: "Error al procesar la respuesta del servidor.",
      };
    }
  }

  // ----------------------------------------------------
  // HELPERS PARA LOVs (mapas id → texto)
  // ----------------------------------------------------
  function buildLovMap(arr, idKey, nameKey) {
    const map = {};
    if (typeof arr !== "undefined" && Array.isArray(arr)) {
      arr.forEach((item) => {
        const id = item[idKey];
        let name = item[nameKey];

        // Caso especial para Categoría, incluir Potrero
        if (idKey === "id" && nameKey === "nombre" && item.potreroNombre) {
          name += ` (${item.potreroNombre})`;
        }

        if (id) map[String(id)] = name.trim();
      });
    }
    return map;
  }

  const LOVS = {
    // Estas variables globales se definen en orden.php
    almacen: buildLovMap(
      typeof ALL_ALMACENES !== "undefined" ? ALL_ALMACENES : [],
      "id",
      "nombre"
    ),
    categoria: buildLovMap(
      typeof ALL_CATEGORIAS !== "undefined" ? ALL_CATEGORIAS : [],
      "id",
      "nombre"
    ),
    tipoAlimento: buildLovMap(
      typeof ALL_TIPOS_ALIMENTOS !== "undefined" ? ALL_TIPOS_ALIMENTOS : [],
      "id",
      "tipoAlimento"
    ),
    alimento: buildLovMap(
      typeof ALL_ALIMENTOS !== "undefined" ? ALL_ALIMENTOS : [],
      "id",
      "nombre"
    ),
    tractorista: buildLovMap(
      typeof ALL_TRACTORISTAS !== "undefined" ? ALL_TRACTORISTAS : [],
      "id",
      "username"
    ),
    estado: {
      P: "Pendiente", // ID 1
      A: "En preparación", // ID 2
      T: "Transportando", // ID 3
      F: "Entregada", // ID 4
      C: "Cancelada", // ID 5
    },
  };

  // ----------------------------------------------------
  // FUNCIÓN: PINTAR RESUMEN DE FILTROS
  // ----------------------------------------------------
  function pintarResumenFiltros() {
    if (!resumenFiltros) return;

    const partes = [];
    const cf = currentFiltros || {};

    
    // 1. Campo Origen (almacenId)
    if (Array.isArray(cf.almacenId) && cf.almacenId.length) {
      const nombres = cf.almacenId.map((id) => LOVS.almacen[String(id)] || id);
      
      partes.push(`Campo: ${nombres.join(", ")}`);
    }

    // 2. Categoría (categoriaId)
    if (Array.isArray(cf.categoriaId) && cf.categoriaId.length) {
      const nombres = cf.categoriaId.map(
        (id) => LOVS.categoria[String(id)] || id
      );
      
      partes.push(`Categoría: ${nombres.join(", ")}`);
    }

    // 3. Tipo de alimento (tipoAlimentoId)
    if (Array.isArray(cf.tipoAlimentoId) && cf.tipoAlimentoId.length) {
      const nombres = cf.tipoAlimentoId.map(
        (id) => LOVS.tipoAlimento[String(id)] || id
      );

      partes.push(`Tipo: ${nombres.join(", ")}`);
    }

    // 4. Alimento (alimentoId)
    if (Array.isArray(cf.alimentoId) && cf.alimentoId.length) {
      const nombres = cf.alimentoId.map(
        (id) => LOVS.alimento[String(id)] || id
      );
      
      partes.push(`Alimento: ${nombres.join(", ")}`);
    }

    // 5. Tractorista (usuarioId)
    if (Array.isArray(cf.usuarioId) && cf.usuarioId.length) {
      const nombres = cf.usuarioId.map(
        (id) => LOVS.tractorista[String(id)] || id
      );
      
      partes.push(`Tractorista: ${nombres.join(", ")}`);
    }

    // 6. Estado (estado)
    if (Array.isArray(cf.estado) && cf.estado.length) {
      const nombres = cf.estado.map((v) => LOVS.estado[String(v)] || v);
      partes.push(`Estado: ${nombres.join(", ")}`);
    }

    // 7. Fechas (fechaMin/Max)
    if (cf.fechaMin || cf.fechaMax) {
      if (cf.fechaMin && cf.fechaMax) {
        partes.push(`Fecha: ${cf.fechaMin} a ${cf.fechaMax}`);
      } else if (cf.fechaMin) {
        partes.push(`Fecha desde: ${cf.fechaMin}`);
      } else if (cf.fechaMax) {
        partes.push(`Fecha hasta: ${cf.fechaMax}`);
      }
    }

    // Actualizar el div de filtros con el resumen de los filtros seleccionados
    resumenFiltros.textContent = partes.length
      ? `Filtros aplicados → ${partes.join(" · ")}`
      : "";
  }

  // ----------------------------------------------------
  // FUNCIÓN: LLENAR FILTROS (ALIMENTOS Y REMARCAR SELECCIONES)
  // ----------------------------------------------------
  function llenarFiltros() {
    const filtroAlimentoGroup = document.getElementById("filtroAlimentoGroup");

    // Limpia SOLO el grupo de alimentos que se genera por JS
    if (filtroAlimentoGroup) filtroAlimentoGroup.innerHTML = "";

    // ------------------------------------------
    // 1) LLENAR ALIMENTOS
    // ------------------------------------------
    if (typeof ALL_ALIMENTOS !== "undefined" && filtroAlimentoGroup) {
      ALL_ALIMENTOS.forEach((a) => {
        const option = document.createElement("label");
        option.classList.add("radio-card");
        option.innerHTML = `
          <input type="checkbox" name="filtro_alimentoId" value="${a.id}">
          <span class="radio-label">${a.nombre}</span>
        `;
        filtroAlimentoGroup.appendChild(option);
      });
    }

    // ======================================================
    // 2) REMARCAR FILTROS ANTERIORES
    // ======================================================
    const grupos = {
      almacenId: "filtro_almacenId",
      categoriaId: "filtro_categoriaId",
      tipoAlimentoId: "filtro_tipoAlimentoId",
      alimentoId: "filtro_alimentoId",
      usuarioId: "filtro_usuarioId",
      estado: "filtro_estado",
    };

    Object.keys(grupos).forEach((key) => {
      const name = grupos[key];
      const checkboxes = document.querySelectorAll(`input[name="${name}"]`);

      checkboxes.forEach((checkbox) => {
        if (
          currentFiltros[key] &&
          Array.isArray(currentFiltros[key]) &&
          currentFiltros[key].includes(checkbox.value)
        ) {
          checkbox.checked = true;
        } else {
          checkbox.checked = false; // Asegurar que los no seleccionados estén desmarcados
        }
      });
    });

    // ------------------------------------------
    // 3) REMARCAR FECHAS
    // ------------------------------------------
    const fMin = document.getElementById("filtroFechaMin");
    const fMax = document.getElementById("filtroFechaMax");

    if (fMin) fMin.value = currentFiltros.fechaMin || "";
    if (fMax) fMax.value = currentFiltros.fechaMax || "";
  }

  // ----------------------------------------------------
  // FUNCIÓN: MOSTRAR MENSAJE DE SISTEMA (USANDO CLASES CSS)
  // ----------------------------------------------------
  function mostrarMensaje(tipo, mensaje) {
    // Limpia cualquier mensaje anterior
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

  // ----------------------------------------------------
  // FUNCIÓN: CARGAR TIPOS DE ALIMENTO POR ALMACÉN (AJAX)
  // ----------------------------------------------------
  async function cargarTiposAlimentoPorAlmacen() {
    const almacenSeleccionado = almacenId.value;

    // Resetear dependientes
    tipoAlimentoId.innerHTML =
      '<option value="">-- Seleccioná un Tipo de Alimento --</option>';
    alimentoId.innerHTML =
      '<option value="">-- Seleccioná un Alimento --</option>';

    stockDisplay.textContent = "Stock: -";
    stockDisplay.dataset.stock = 0;

    if (!almacenSeleccionado) return;

    try {
      const params = new URLSearchParams({
        action: "getTiposAlimentoPorAlmacen",
        almacenId: almacenSeleccionado,
      });

      const tipos = await fetchJSON(`${API}?${params.toString()}`);

      if (Array.isArray(tipos)) {
        tipos.forEach((t) => {
          const opt = document.createElement("option");
          opt.value = t.id;
          opt.textContent = t.tipoAlimento; // 👈 NOMBRE CORRECTO
          tipoAlimentoId.appendChild(opt);
        });
      }
    } catch (e) {
      console.error("Error al cargar tipos de alimento:", e);
    }
  }

  // ----------------------------------------------------
  // FUNCIÓN: FILTRAR Y CARGAR ALIMENTOS por tipo y ALMACÉN (AJAX)
  // ----------------------------------------------------
  async function cargarAlimentosDisponibles() {
    const tipoSeleccionado = tipoAlimentoId.value;
    const almacenSeleccionado = almacenId.value;
    const alimentoSeleccionadoActual = alimentoId.value;

    // Limpiar el select de alimentos, manteniendo la opción por defecto
    alimentoId.innerHTML =
      '<option value="">-- Seleccioná un Alimento --</option>';

    // Resetea el stock mostrado
    stockDisplay.textContent = "Stock: -";
    stockDisplay.dataset.stock = 0;
    errorStockInsuficiente.style.display = "none";

    if (tipoSeleccionado && almacenSeleccionado) {
      try {
        const params = new URLSearchParams({
          action: "getAlimentosConStock",
          almacenId: almacenSeleccionado,
          tipoAlimentoId: tipoSeleccionado,
        });

        const alimentosConStock = await fetchJSON(
          `${API}?${params.toString()}`
        );

        if (Array.isArray(alimentosConStock)) {
          alimentosConStock.forEach((alimento) => {
            const option = document.createElement("option");
            // Mostrar stock disponible al lado del nombre
            option.textContent = `${alimento.nombre}`;
            option.value = alimento.id;

            // Asignar dataset de stock para la validación rápida en el submit
            option.dataset.stock = alimento.cantidad;

            if (String(alimento.id) === alimentoSeleccionadoActual) {
              option.selected = true;
              stockDisplay.dataset.stock = alimento.cantidad;
              stockDisplay.textContent = `Stock: ${alimento.cantidad}`;
            }

            alimentoId.appendChild(option);
          });
        }
      } catch (e) {
        console.error("Error al cargar alimentos disponibles:", e);
      }
    }
  }

  // ----------------------------------------------------
  // FUNCIÓN: MOSTRAR POTRERO ASIGNADO (a la categoría)
  // ----------------------------------------------------
  function mostrarPotreroAsignado() {
    const selectedOption = categoriaId.options[categoriaId.selectedIndex];
    const potreroNombre = selectedOption
      ? selectedOption.dataset.potrero
      : null;

    // MOSTRAR POTRERO
    if (potreroNombre) {
      potreroAsignadoDisplay.textContent = `Potrero asignado: ${potreroNombre}`;
    } else {
      potreroAsignadoDisplay.textContent = "";
    }
  }

  // ----------------------------------------------------
  // FUNCIÓN: OBTENER Y MOSTRAR STOCK
  // ----------------------------------------------------
  async function obtenerYMostrarStock() {
    const selectedOption = alimentoId.options[alimentoId.selectedIndex];

    // 1️⃣ PRIORIDAD ABSOLUTA: usar stock precargado en la LOV
    if (selectedOption && selectedOption.dataset.stock !== undefined) {
      const stock = Number(selectedOption.dataset.stock) || 0;
      stockDisplay.dataset.stock = stock;
      stockDisplay.textContent = `Stock: ${stock}`;
      return;
    }

    // 2️⃣ Si no hay alimento seleccionado
    if (!alimentoId.value || !almacenId.value || !tipoAlimentoId.value) {
      stockDisplay.textContent = "Stock: -";
      stockDisplay.dataset.stock = 0;
      return;
    }

    // 3️⃣ Fallback REAL (raro, casi nunca se usa)
    try {
      const params = new URLSearchParams({
        action: "getStock",
        almacenId: almacenId.value,
        alimentoId: alimentoId.value,
        tipoAlimentoId: tipoAlimentoId.value,
      });

      const data = await fetchJSON(`${API}?${params.toString()}`);

      if (data && data.stock !== undefined) {
        stockDisplay.textContent = `Stock: ${data.stock}`;
        stockDisplay.dataset.stock = data.stock;
      } else {
        stockDisplay.textContent = "Stock: 0";
        stockDisplay.dataset.stock = 0;
      }
    } catch (e) {
      console.error("Error al obtener stock:", e);
      stockDisplay.textContent = "Stock: 0";
      stockDisplay.dataset.stock = 0;
    }
  }

  // ----------------------------------------------------
  // EVENT LISTENERS PARA FILTRAR ALIMENTO Y MOSTRAR STOCK
  // ----------------------------------------------------
  almacenId.addEventListener("change", () => {
    cargarTiposAlimentoPorAlmacen();
  });

  tipoAlimentoId.addEventListener("change", () => {
    alimentoId.value = ""; // Resetear alimento al cambiar tipo
    cargarAlimentosDisponibles(); // Cargar alimentos disponibles para el almacén y tipo seleccionado
    obtenerYMostrarStock();
  });

  // NUEVO: Evento para mostrar el potrero asignado a la categoría
  categoriaId.addEventListener("change", mostrarPotreroAsignado);

  // Si el alimento cambia, recalcular stock
  alimentoId.addEventListener("change", obtenerYMostrarStock);

  cantidad.addEventListener("input", () => {
    errorStockInsuficiente.style.display = "none"; // Ocultar al cambiar cantidad
  });

  // ----------------------------------------------------
  // FUNCIÓN: REFRESCAR TABLA (LISTAR ORDENES) - MODIFICADA PARA FILTROS
  // ----------------------------------------------------
  async function refrescarTabla(filtros = {}) {

    try {
      const params = new URLSearchParams();
      let action = "obtenerOrden"; // Acción por defecto (la original)
      let hasFilters = false;

      // Construir parámetros de filtro
      Object.entries(filtros).forEach(([key, value]) => {
        if (Array.isArray(value)) {
          if (value.length > 0) {
            hasFilters = true;
            value.forEach((v) => {
              if (v !== null && v !== undefined && v !== "") {
                params.append(key + "[]", v);
              }
            });
          }
        } else if (value !== null && value !== undefined && value !== "") {
          hasFilters = true;
          params.append(key, value);
        }
      });

      if (hasFilters) {
        // Si hay filtros, usamos la nueva acción
        action = "obtenerOrdenesFiltradas";
      }

      // Añadir la acción al inicio de los parámetros
      params.set("action", action);

      const queryString = params.toString();
      const url = `${API}?${queryString}`;

      const resp = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      const data = await fetchJSON(resp.url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      tableBody.innerHTML = "";

      if (!Array.isArray(data) || data.length === 0) {
        tableBody.innerHTML = `
        <tr>
          <td colspan="9" style="text-align:center;">
            No hay órdenes registradas.
          </td>
        </tr>`;
        return;
      }

      data.forEach((o) => {
        const tr = document.createElement("tr");

        // 🎨 estado
        const estadoColor = o.estadoColor || "#ccc";
        const estadoStyle = `
        background-color:${estadoColor};
        color:white;
        border-radius:4px;
        padding:2px 5px;
      `;

        let accionesHtml = "";

        // ✏️ MODIFICAR → solo Pendiente
        if (parseInt(o.estadoId) === 1) {
          accionesHtml += `
          <button
            type="button"
            class="btn-icon edit js-editar"
            data-id="${o.id}"
            title="Modificar orden"
          >✏️</button>`;
        }

        // 🗑️ ELIMINAR → siempre permitido para admin
        if (parseInt(o.estadoId) === 1) {
          accionesHtml += `
          <button
            type="button"
            class="btn-icon delete js-eliminar"
            data-id="${o.id}"
            title="Eliminar orden"
          >🗑️</button>`;
        }

        // 📋 VER AUDITORÍA → solo si existe
        if (o.tieneAuditoria == 1) {
          accionesHtml += `
          <button
            type="button"
            class="btn-icon info js-ver-auditoria"
            data-id="${o.id}"
            title="Ver historial de orden"
          >📋</button>`;
        }

        tr.innerHTML = `
        <td>${o.almacenNombre}</td>
        <td>${o.categoriaNombre} (${o.potreroNombre})</td>
        <td>${o.tipoAlimentoNombre} ${o.alimentoNombre}</td>
        <td>${o.cantidad}</td>
        <td>${o.usuarioNombre}</td>
        <td><span style="${estadoStyle}">${o.estadoDescripcion}</span></td>
        <td>${o.fechaCreacion} - ${o.horaCreacion}</td>     
        <td>${o.fechaActualizacion} - ${o.horaActualizacion}</td>
        <td>
          <div class="table-actions">
            ${accionesHtml}
          </div>
        </td>
      `;

        tableBody.appendChild(tr);
      });
    } catch (e) {
      console.error("Error listando:", e);
      mostrarMensaje("error", "Error al listar las órdenes.");
    }
  }

  // ------------------------------
  // MODO REGISTRAR
  // ------------------------------
  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar";
    cancelarEdicion.style.display = "none";
    document.getElementById("form-title").textContent = "Registrar Orden";
    grupoMotivo.style.display = "none";
    motivoInput.value = "";

    idInput.value = "";
    form.reset();

    // Restablecer valores a vacío y forzar la carga de alimentos (después de resetear)
    almacenId.value = "";
    categoriaId.value = "";
    tipoAlimentoId.value = "";
    alimentoId.value = "";

    mostrarPotreroAsignado(); // Resetear texto de potrero
    cargarAlimentosDisponibles();

    // Reestablecer la selección de usuario logueado por defecto (si existe) y es obligatorio.
    const userOption = usuarioId.querySelector("option:checked");
    if (userOption) {
      usuarioId.dataset.defaultUserId = userOption.value;
      usuarioId.value = userOption.value;
    } else {
      usuarioId.value = ""; // Si no hay default, dejar vacío
    }

    stockDisplay.textContent = "Stock: -";
    stockDisplay.dataset.stock = 0;
    errorStockInsuficiente.style.display = "none";

    document
      .querySelectorAll(".error-message")
      .forEach((el) => (el.style.display = "none"));
  }

  // ------------------------------
  // SUBMIT FORM → registrar / modificar
  // ------------------------------
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    let ok = true;
    // IMPORTANTE: Limpiar todos los mensajes de error de campo antes de validar
    document
      .querySelectorAll(".error-message")
      .forEach((el) => (el.style.display = "none"));

    errorStockInsuficiente.style.display = "none";

    // Limpiar mensajes anteriores al intentar enviar
    mostrarMensaje("", "");

    const mostrarError = (id) => {
      const el = document.getElementById(id);
      // Forzar la visibilidad del mensaje de error de campo
      if (el) el.style.display = "block";
      ok = false;
    };

    // Validación básica: Orden de los campos en el formulario modificado
    if (!almacenId.value.trim()) mostrarError("error-almacenId"); // 1. Almacén
    if (!categoriaId.value.trim()) mostrarError("error-categoriaId"); // 2. Categoría
    if (!tipoAlimentoId.value.trim()) mostrarError("error-tipoAlimentoId"); // 3. Tipo Alimento
    if (!alimentoId.value.trim()) mostrarError("error-alimentoId"); // 4. Alimento

    const cant = Number(cantidad.value);
    if (!cantidad.value.trim() || !Number.isInteger(cant) || cant <= 0) {
      mostrarError("error-cantidad");
    }

    if (!usuarioId.value.trim()) mostrarError("error-usuarioId"); // 5. Tractorista (Obligatorio)

    // Validación de Stock Suficiente (solo para acción 'registrar')
    if (accionInput.value === "registrar" && ok) {
      const stockDisponible = Number(stockDisplay.dataset.stock) || 0;
      if (cant > stockDisponible) {
        errorStockInsuficiente.textContent = `Stock insuficiente en el almacén. Solo hay ${stockDisponible} unidades disponibles.`;
        errorStockInsuficiente.style.display = "block";
        ok = false;
      }
    }

    // Validación de motivo si es modificar
    if (accionInput.value === "modificar" && ok) {
      if (!motivoInput.value.trim()) {
        mostrarError("error-motivo");
      }
    }

    if (!ok) {
      // Mostrar mensaje global SÓLO si la validación interna falló
      mostrarMensaje("error", "Por favor, corrija los errores del formulario.");
      return;
    }

    const fd = new FormData(form);

    try {
      const data = await fetchJSON(API, {
        method: "POST",
        body: fd,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      if (data.tipo === "success") {
        mostrarMensaje(data.tipo, data.mensaje);
        // Usar currentFiltros para refrescar la tabla
        await refrescarTabla(currentFiltros);
        setRegistrarMode();
      } else {
        // Mapeo de errores del backend para que aparezcan bajo el campo correcto
        if (
          data.mensaje &&
          data.mensaje.includes("Categoría (con potrero asignado)")
        ) {
          mostrarError("error-categoriaId");
        }
        if (
          data.mensaje &&
          data.mensaje.includes("Tractorista es obligatorio")
        ) {
          mostrarError("error-usuarioId");
        }
        if (data.mensaje && data.mensaje.includes("motivo")) {
          mostrarError("error-motivo");
        }
        // Mostrar mensaje principal del sistema, que también contiene los errores de stock/DB
        mostrarMensaje(data.tipo, data.mensaje);
      }
    } catch (err) {
      console.error(err);
      mostrarMensaje("error", "Error al procesar la solicitud.");
    }
  });

  cancelarEdicion.addEventListener("click", setRegistrarMode);

  // ------------------------------
  // ACCIONES EN TABLA (editar / eliminar)
  // ------------------------------
  tableBody.addEventListener("click", async (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;

    const id = btn.dataset.id;

    // Limpiar mensajes de sistema
    mostrarMensaje("", "");

    // EDITAR ORDEN
    if (btn.classList.contains("js-editar")) {
      document.getElementById("form-title").textContent = "Modificar Orden";
      accionInput.value = "modificar";
      submitBtn.textContent = "Modificar";
      cancelarEdicion.style.display = "inline-block";
      grupoMotivo.style.display = "block";
      motivoInput.value = "";

      const ordenData = await fetchJSON(`${API}?action=getOrdenById&id=${id}`);

      if (!ordenData || ordenData.tipo === "error") {
        mostrarMensaje("error", "No se pudo cargar la orden para editar.");
        return;
      }

      idInput.value = ordenData.id;
      almacenId.value = ordenData.almacenId;

      await cargarTiposAlimentoPorAlmacen();
      tipoAlimentoId.value = ordenData.tipoAlimentoId;

      categoriaId.value = ordenData.categoriaId;
      mostrarPotreroAsignado();

      await cargarAlimentosDisponibles();
      alimentoId.value = ordenData.alimentoId;

      cantidad.value = ordenData.cantidad;
      usuarioId.value = ordenData.usuarioId;

      obtenerYMostrarStock();
      return;
    }

    // VER AUDITORÍA
    if (btn.classList.contains("js-ver-auditoria")) {
      abrirModalAuditoria(id);
      return;
    }

    // ELIMINAR ORDEN
    if (btn.classList.contains("js-eliminar")) {
      confirmText.textContent = `¿Seguro que deseas eliminar la orden #${id}?`;
      confirmYes.dataset.deleteId = id;
      confirmYes.dataset.deleteTipo = "individual";
      modal.style.display = "flex";
      return;
    }
  });

  // ------------------------------
  // CONFIRMAR ELIMINAR
  // ------------------------------
  confirmYes.addEventListener("click", async () => {
    const id = confirmYes.dataset.deleteId;

    const fd = new FormData();
    fd.append("accion", "eliminar");
    fd.append("id", id);

    try {
      const res = await fetchJSON(API, {
        method: "POST",
        body: fd,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      if (res.tipo === "success") {
        mostrarMensaje(res.tipo, res.mensaje);
        // Usar currentFiltros para refrescar la tabla
        await refrescarTabla(currentFiltros);
        // Si se elimina una orden, resetear el modo de registro.
        setRegistrarMode();
      } else {
        mostrarMensaje(res.tipo, res.mensaje);
      }
    } catch (err) {
      console.error("Error al eliminar la orden:", err);
      mostrarMensaje("error", "Error al eliminar la orden.");
    }

    modal.style.display = "none";
  });

  confirmNo.addEventListener("click", () => {
    modal.style.display = "none";
  });

  async function abrirModalAuditoria(ordenId) {
    const modalAuditoria = document.getElementById("modalAuditoriaOrden");
    const auditoriaBody = document.getElementById("auditoriaBody");

    auditoriaBody.innerHTML = `
    <tr>
      <td colspan="5" style="text-align:center;">Cargando...</td>
    </tr>
  `;

    modalAuditoria.style.display = "flex";

    const data = await fetchJSON(
      `${API}?action=obtenerAuditoriaOrden&id=${ordenId}`,
      { headers: { "X-Requested-With": "XMLHttpRequest" } }
    );

    auditoriaBody.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
      auditoriaBody.innerHTML = `
      <tr>
        <td colspan="5" style="text-align:center;">
          No hay modificaciones registradas.
        </td>
      </tr>
    `;
      return;
    }

    data.forEach((a) => {
      const fechaObj = new Date(a.fecha);

      const fechaFormateada =
        String(fechaObj.getDate()).padStart(2, "0") +
        "/" +
        String(fechaObj.getMonth() + 1).padStart(2, "0") +
        "/" +
        String(fechaObj.getFullYear()).slice(-2) +
        " " +
        String(fechaObj.getHours()).padStart(2, "0") +
        ":" +
        String(fechaObj.getMinutes()).padStart(2, "0");

      const tr = document.createElement("tr");
      tr.innerHTML = `
    <td>${fechaFormateada}</td>
    <td>${a.usuarioNombre}</td>
    <td>${a.cantidadAnterior ?? "-"}</td>
    <td>${a.cantidadNueva ?? "-"}</td>
    <td>${a.motivo || "-"}</td>
  `;
      auditoriaBody.appendChild(tr);
    });
    
  }

  btnCerrarAuditoria.addEventListener("click", () => {
    modalAuditoria.style.display = "none";
  });

  // ----------------------------------------------------
  // EVENT LISTENERS PARA FILTROS
  // ----------------------------------------------------

  // 1. Abrir modal
  abrirFiltrosBtn.addEventListener("click", () => {
    llenarFiltros();
    filtroModal.style.display = "flex";
  });

  // 2. Cerrar modal
  if (cerrarFiltrosBtn) {
    cerrarFiltrosBtn.addEventListener("click", () => {
      filtroModal.style.display = "none";
    });
  }

  // 3. Aplicar filtros
  aplicarFiltrosBtn.addEventListener("click", () => {
    const filtrosSeleccionados = {
      almacenId: Array.from(
        document.querySelectorAll('input[name="filtro_almacenId"]:checked')
      ).map((input) => input.value),
      categoriaId: Array.from(
        document.querySelectorAll('input[name="filtro_categoriaId"]:checked')
      ).map((input) => input.value),
      tipoAlimentoId: Array.from(
        document.querySelectorAll('input[name="filtro_tipoAlimentoId"]:checked')
      ).map((input) => input.value),
      alimentoId: Array.from(
        document.querySelectorAll('input[name="filtro_alimentoId"]:checked')
      ).map((input) => input.value),
      usuarioId: Array.from(
        document.querySelectorAll('input[name="filtro_usuarioId"]:checked')
      ).map((input) => input.value),
      estado: Array.from(
        document.querySelectorAll('input[name="filtro_estado"]:checked')
      ).map((input) => input.value),
      fechaMin: document.getElementById("filtroFechaMin").value,
      fechaMax: document.getElementById("filtroFechaMax").value,
    };

    const filtrosParaEnvio = {};
    currentFiltros = {}; // Reinicia el estado guardado

    for (const key in filtrosSeleccionados) {
      if (Array.isArray(filtrosSeleccionados[key])) {
        if (filtrosSeleccionados[key].length > 0) {
          filtrosParaEnvio[key] = filtrosSeleccionados[key];
          currentFiltros[key] = filtrosSeleccionados[key];
        }
      } else if (filtrosSeleccionados[key]) {
        filtrosParaEnvio[key] = filtrosSeleccionados[key];
        currentFiltros[key] = filtrosSeleccionados[key];
      }
    }

    refrescarTabla(filtrosParaEnvio);
    pintarResumenFiltros();
    filtroModal.style.display = "none";
  });

  // 4. Limpiar filtros
  limpiarFiltrosBtn.addEventListener("click", () => {
    document
      .querySelectorAll("#filtroModal input[type=checkbox]")
      .forEach((checkbox) => (checkbox.checked = false));

    const fechaMinInput = document.getElementById("filtroFechaMin");
    const fechaMaxInput = document.getElementById("filtroFechaMax");
    if (fechaMinInput) fechaMinInput.value = "";
    if (fechaMaxInput) fechaMaxInput.value = "";

    currentFiltros = {};
    refrescarTabla({});
    pintarResumenFiltros();
  });

  // ------------------------------
  // INICIAR
  // ------------------------------
  // Guardar el ID del usuario logueado (si es Tractorista)
  const userOption = usuarioId.querySelector("option:checked");
  if (userOption) {
    usuarioId.dataset.defaultUserId = userOption.value;
  }

  // Llamar a mostrarPotreroAsignado al inicio
  mostrarPotreroAsignado();

  setRegistrarMode();
  pintarResumenFiltros(); // Pintar el resumen al inicio
  refrescarTabla(currentFiltros); // Usar filtros vacíos inicialmente
});
