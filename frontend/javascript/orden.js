let currentFiltros = {}; // Variable global para almacenar el estado actual de los filtros

document.addEventListener("DOMContentLoaded", () => {
  console.log("orden.js cargado correctamente");

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

  // Nuevo elemento para mostrar el potrero asociado
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
            option.textContent = `${alimento.nombre} (Disp: ${alimento.cantidad})`;
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

    if (potreroNombre) {
      potreroAsignadoDisplay.textContent = `Potrero asignado: ${potreroNombre}`;
    } else {
      potreroAsignadoDisplay.textContent = "";
    }
  }

  // ----------------------------------------------------
  // FUNCIÓN: OBTENER Y MOSTRAR STOCK (AJAX)
  // ----------------------------------------------------
  async function obtenerYMostrarStock() {
    const almacenSeleccionado = almacenId.value;
    const alimentoSeleccionado = alimentoId.value;
    const tipoAlimentoSeleccionado = tipoAlimentoId.value;

    stockDisplay.textContent = "Cargando...";
    errorStockInsuficiente.style.display = "none";

    // Obtener stock desde el atributo dataset del <option> si existe
    const selectedOption = alimentoId.options[alimentoId.selectedIndex];
    const stockPrecargado = selectedOption
      ? selectedOption.dataset.stock
      : undefined;

    // Solo obtenemos stock directamente del dataset si estamos en modo 'registrar'
    if (stockPrecargado !== undefined && accionInput.value === "registrar") {
      stockDisplay.dataset.stock = Number(stockPrecargado);
      stockDisplay.textContent = `Stock: ${stockPrecargado}`;
      return;
    }

    // Si no hay opción seleccionada o estamos en modo edición, hacer la llamada AJAX
    if (
      !almacenSeleccionado ||
      !alimentoSeleccionado ||
      !tipoAlimentoSeleccionado
    ) {
      stockDisplay.textContent = "Stock: -";
      stockDisplay.dataset.stock = 0;
      return;
    }

    try {
      const params = new URLSearchParams({
        action: "getStock",
        almacenId: almacenSeleccionado,
        alimentoId: alimentoSeleccionado,
        tipoAlimentoId: tipoAlimentoSeleccionado,
      });

      const data = await fetchJSON(`${API}?${params.toString()}`);

      if (data && data.stock !== undefined) {
        stockDisplay.textContent = `Stock: ${data.stock}`;
        stockDisplay.dataset.stock = data.stock;
      } else {
        stockDisplay.textContent = "Stock: Error";
        stockDisplay.dataset.stock = 0;
      }
    } catch (e) {
      console.error("Error al obtener stock:", e);
      stockDisplay.textContent = "Stock: Error";
      stockDisplay.dataset.stock = 0;
    }
  }

  // ----------------------------------------------------
  // EVENT LISTENERS PARA FILTRAR ALIMENTO Y MOSTRAR STOCK
  // ----------------------------------------------------
  almacenId.addEventListener("change", () => {
    // Resetear tipo y alimento para forzar la carga de los disponibles en el nuevo almacén
    tipoAlimentoId.value = "";
    alimentoId.value = "";
    cargarAlimentosDisponibles();
    obtenerYMostrarStock();
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
  // FUNCIÓN: REFRESCAR TABLA (LISTAR ORDENES)
  // ----------------------------------------------------
  async function refrescarTabla() {
    console.log("Listando ordenes...");

    try {
      const resp = await fetch(`${API}?action=obtenerOrden`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      const data = await fetchJSON(resp.url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      tableBody.innerHTML = "";

      // Colspan ajustado a 12 (11 columnas de datos + 1 de Acciones)
      if (!Array.isArray(data) || data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="12" style="text-align:center;">No hay ordenes registradas.</td></tr>`;
        return;
      }

      data.forEach((o) => {
        const tr = document.createElement("tr");

        // Determinar el color de fondo para el estado
        const estadoColor = o.estadoColor || "#ccc";
        const estadoStyle = `background-color: ${estadoColor}; color: white; border-radius: 4px; padding: 2px 5px;`;

        // Generar las celdas en el nuevo orden: Campo, Categoría, Potrero, Almacén, Tipo Alimento, Alimento, Cantidad, Tractorista, Estado, Fecha, Hora, Acciones
        tr.innerHTML = `
        <td>${o.campoNombre}</td>
        <td>${o.categoriaNombre}</td>
        <td>${o.potreroNombre}</td>
        <td>${o.almacenNombre}</td>
        <td>${o.tipoAlimentoNombre}</td>
        <td>${o.alimentoNombre}</td>
        <td>${o.cantidad}</td>
        <td>${o.usuarioNombre}</td>
        <td><span style="${estadoStyle}">${o.estadoDescripcion}</span></td>
        <td>${o.fechaCreacion}</td>
        <td>${o.horaCreacion}</td>
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon edit js-editar" data-id="${o.id}" title="Modificar">✏️</button>
            <button type="button" class="btn-icon delete js-eliminar" data-id="${o.id}" title="Eliminar">🗑️</button>
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
    const defaultUserId = usuarioId.dataset.defaultUserId;
    if (defaultUserId) {
      usuarioId.value = defaultUserId;
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
    document
      .querySelectorAll(".error-message")
      .forEach((el) => (el.style.display = "none"));

    errorStockInsuficiente.style.display = "none";

    // Limpiar mensajes anteriores al intentar enviar
    mostrarMensaje("", "");

    const mostrarError = (id) => {
      const el = document.getElementById(id);
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

    if (!ok) {
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
        await refrescarTabla();
        setRegistrarMode();
      } else {
        // En caso de que el backend falle la validación de potrero asociado a la categoría
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

      // Obtener datos de la orden
      const ordenData = await fetchJSON(`${API}?action=getOrdenById&id=${id}`);

      if (ordenData) {
        idInput.value = ordenData.id;
        almacenId.value = ordenData.almacenId;
        tipoAlimentoId.value = ordenData.tipoAlimentoId;

        // Cargar categoriaId y mostrar el potrero asociado
        categoriaId.value = ordenData.categoriaId;
        mostrarPotreroAsignado();

        // Cargar alimentos disponibles para el almacén y tipo. Usamos await
        await cargarAlimentosDisponibles();
        alimentoId.value = ordenData.alimentoId;

        cantidad.value = ordenData.cantidad;
        usuarioId.value = ordenData.usuarioId;

        // Mostrar stock actual (sólo informativo en edición)
        obtenerYMostrarStock();
      }
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
        await refrescarTabla();
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
  refrescarTabla();
});
