let currentFiltros = {}; // Variable global para almacenar el estado actual de los filtros

document.addEventListener("DOMContentLoaded", () => {
  console.log("stock.js cargado correctamente");

  const API = "../../../backend/controladores/stockController.php";

  // --- FORM ---
  const form = document.getElementById("ordenForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");

  const potreroId = document.getElementById("potreroId");
  const tipoAlimentoId = document.getElementById("tipoAlimentoId");
  const alimentoId = document.getElementById("alimentoId");
  const cantidad = document.getElementById("cantidad");

  const stockDisplay = document.getElementById("stockDisplay");
  const errorStockInsuficiente = document.getElementById(
    "error-stock-insuficiente"
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
  // FUNCIÓN: FILTRAR Y CARGAR ALIMENTOS por tipo
  // ----------------------------------------------------
  function cargarAlimentosPorTipo() {
    const tipoSeleccionado = tipoAlimentoId.value;
    const alimentoSeleccionadoActual = alimentoId.value;

    // Limpiar el select de alimentos, manteniendo la opción por defecto
    alimentoId.innerHTML =
      '<option value="">-- Seleccioná un Alimento --</option>';

    // Resetea el stock mostrado
    stockDisplay.textContent = "Stock: -";
    errorStockInsuficiente.style.display = "none";

    if (tipoSeleccionado && typeof ALL_ALIMENTOS !== "undefined") {
      const alimentosFiltrados = ALL_ALIMENTOS.filter(
        (a) => String(a.tipoAlimentoId) === tipoSeleccionado
      );

      alimentosFiltrados.forEach((alimento) => {
        const option = document.createElement("option");
        option.value = alimento.id;
        option.textContent = alimento.nombre;

        if (String(alimento.id) === alimentoSeleccionadoActual) {
          option.selected = true;
        }

        alimentoId.appendChild(option);
      });
    }
  }

  // ----------------------------------------------------
  // FUNCIÓN: OBTENER Y MOSTRAR STOCK (AJAX)
  // ----------------------------------------------------
  async function obtenerYMostrarStock() {
    const alimentoSeleccionado = alimentoId.value;
    const tipoAlimentoSeleccionado = tipoAlimentoId.value;

    stockDisplay.textContent = "Cargando...";
    errorStockInsuficiente.style.display = "none";

    if (!alimentoSeleccionado || !tipoAlimentoSeleccionado) {
      stockDisplay.textContent = "Stock: -";
      stockDisplay.dataset.stock = 0;
      return;
    }

    try {
      const params = new URLSearchParams({
        action: "getStock",
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
  tipoAlimentoId.addEventListener("change", () => {
    alimentoId.value = "";
    cargarAlimentosPorTipo();
    obtenerYMostrarStock();
  });

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

      if (!Array.isArray(data) || data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;">No hay ordenes registradas.</td></tr>`;
        return;
      }

      data.forEach((o) => {
        const tr = document.createElement("tr");

        // Determinar el color de fondo para el estado
        const estadoColor = o.estadoColor || "#ccc";
        const estadoStyle = `background-color: ${estadoColor}; color: white; border-radius: 4px; padding: 2px 5px;`;

        tr.innerHTML = `
        <td>${o.potreroNombre}</td>
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
    cargarAlimentosPorTipo();
    stockDisplay.textContent = "Stock: -";
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

    const mostrarError = (id) => {
      const el = document.getElementById(id);
      if (el) el.style.display = "block";
      ok = false;
    };

    // Validación básica
    if (!potreroId.value.trim()) mostrarError("error-potreroId");
    if (!tipoAlimentoId.value.trim()) mostrarError("error-tipoAlimentoId");
    if (!alimentoId.value.trim()) mostrarError("error-alimentoId");

    const cant = Number(cantidad.value);
    if (!cantidad.value.trim() || !Number.isInteger(cant) || cant <= 0) {
      mostrarError("error-cantidad");
    }

    // Validación de Stock Suficiente (solo para acción 'registrar')
    if (accionInput.value === "registrar" && ok) {
      const stockDisponible = Number(stockDisplay.dataset.stock) || 0;
      if (cant > stockDisponible) {
        errorStockInsuficiente.textContent = `Stock insuficiente. Solo hay ${stockDisponible} unidades disponibles.`;
        errorStockInsuficiente.style.display = "block";
        ok = false;
      }
    }

    if (!ok) return;

    const fd = new FormData(form);

    try {
      const data = await fetchJSON(API, {
        method: "POST",
        body: fd,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      if (data.tipo === "success") {
        alert(data.mensaje);
        await refrescarTabla();
        setRegistrarMode();
        obtenerYMostrarStock(); // Actualiza el stock después de la creación
      } else {
        alert(data.mensaje);
      }
    } catch (err) {
      console.error(err);
      alert("Error al procesar la solicitud.");
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
        potreroId.value = ordenData.potreroId;
        tipoAlimentoId.value = ordenData.tipoAlimentoId;

        // Recargar alimentos y luego asignar el alimentoId
        cargarAlimentosPorTipo();
        alimentoId.value = ordenData.alimentoId;

        cantidad.value = ordenData.cantidad;

        // Mostrar stock después de cargar el alimento (solo informativo en edición)
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
        alert(res.mensaje);
        await refrescarTabla();
      } else {
        alert(res.mensaje);
      }
    } catch (err) {
      console.error("Error al eliminar la orden:", err);
      alert("Error al eliminar la orden.");
    }

    modal.style.display = "none";
  });

  confirmNo.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // ------------------------------
  // INICIAR
  // ------------------------------
  setRegistrarMode();
  cargarAlimentosPorTipo();
  refrescarTabla();
});
