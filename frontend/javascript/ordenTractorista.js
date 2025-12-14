let currentFiltros = {}; // Variable global para almacenar el estado actual de los filtros

document.addEventListener("DOMContentLoaded", () => {
  console.log("ordenTractorista.js cargado correctamente");

  const API = "../../../backend/controladores/ordenController.php";

  // ROL_ID, USER_ID, ROL_TRACTORISTA son variables GLOBALES definidas en ordenTractorista.php
  const ROL_TRACTORISTA = 3;
  // No se redeclaran ROL_ID y USER_ID aquí para usar los valores globales de PHP

  // --- TABLA PRINCIPAL ---
  const tableBody = document.querySelector("#tablaOrdenPrincipal tbody");

  // Contenedor de mensajes de sistema
  const systemMessageContainer = document.getElementById(
    "system-message-container"
  );

  // --- MODAL CONFIRMACIÓN ---
  const modal = document.getElementById("confirmModal");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");
  const confirmText = document.getElementById("confirmText");
  const modalModificar = document.getElementById("modalModificarOrden");
  const modOrdenId = document.getElementById("modOrdenId");
  const modCantidad = document.getElementById("modCantidad");
  const modMotivo = document.getElementById("modMotivo");
  const btnConfirmarModificar = document.getElementById(
    "btnConfirmarModificar"
  );
  const btnCancelarModificar = document.getElementById("btnCancelarModificar");

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
  // FUNCIÓN: REFRESCAR TABLA (LISTAR ORDENES FILTRADAS)
  // ----------------------------------------------------
  async function refrescarTabla() {
    console.log("Listando ordenes para Tractorista...");

    // VERIFICACIÓN: Ahora se usa la variable global (USER_ID)
    if (typeof window.USER_ID === "undefined" || window.USER_ID === 0) {
      mostrarMensaje(
        "error",
        "Error de sesión: ID de usuario no disponible para filtrar órdenes."
      );
      tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;">Error: No se pudo obtener el ID del usuario. Inicie sesión nuevamente.</td></tr>`;
      return;
    }

    // ENVÍO: Parámetro de filtro por ID de usuario
    const url = `${API}?action=obtenerOrden&usuarioId=${window.USER_ID}`;

    try {
      const resp = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      const data = await fetchJSON(resp.url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      tableBody.innerHTML = "";

      // Colspan ajustado a 9
      if (!Array.isArray(data) || data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;">No hay órdenes asignadas a este tractorista.</td></tr>`;
        return;
      }

      data.forEach((o) => {
        const tr = document.createElement("tr");

        // Determinar el color de fondo para el estado
        const estadoColor = o.estadoColor || "#ccc";
        const estadoStyle = `background-color: ${estadoColor}; color: white; border-radius: 4px; padding: 2px 5px;`;

        let accionesHtml = "";

        // Botón Modificar (solo visual)
        accionesHtml += `<button type="button" class="btn-icon edit js-modificar-placeholder" data-id="${o.id}" title="Ver/Modificar">✏️</button>`;

        // Botón En Preparación (solo si está Pendiente - estadoId = 1)
        if (o.estadoId == 1) {
          // Estado 2 = En preparación
          accionesHtml += `<button type="button" class="btn-icon prepare js-preparar" data-id="${o.id}" data-estado-id="2" title="Marcar En Preparación">⏱️</button>`;
        }

        // Generar las celdas en el orden simplificado
        tr.innerHTML = `
        <td>${o.almacenNombre}</td>
        <td>${o.categoriaNombre} (${o.potreroNombre})</td>
        <td>${o.tipoAlimentoNombre} ${o.alimentoNombre}</td>
        <td>${o.cantidad}</td>
        <td>${o.usuarioNombre}</td>
        <td><span style="${estadoStyle}">${o.estadoDescripcion}</span></td>
        <td>${o.fechaCreacion}</td>
        <td>${o.horaCreacion}</td>
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
      mostrarMensaje(
        "error",
        "Error al listar las órdenes. Revise la conexión al backend."
      );
    }
  }

  // ------------------------------
  // ACCIONES EN TABLA (preparar)
  // ------------------------------
  tableBody.addEventListener("click", async (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;

    const id = btn.dataset.id;
    mostrarMensaje("", "");

    // CAMBIAR A EN PREPARACIÓN (NUEVA ACCIÓN)
    if (btn.classList.contains("js-preparar")) {
      const nuevoEstadoId = btn.dataset.estadoId;

      // Mostrar modal de confirmación
      confirmText.textContent = `¿Seguro que deseas pasar la orden #${id} a estado "En preparación"?`;
      confirmYes.dataset.ordenId = id;
      confirmYes.dataset.nuevoEstadoId = nuevoEstadoId;
      confirmYes.dataset.action = "cambiarEstado";
      modal.style.display = "flex";
      return;
    }

    // MODIFICAR PLACEHOLDER
    if (btn.classList.contains("js-modificar-placeholder")) {
      abrirModalModificar(id);
      return;
    }
  });

  async function abrirModalModificar(ordenId) {
    document.getElementById("error-modCantidad").style.display = "none";
    document.getElementById("error-modMotivo").style.display = "none";

    const data = await fetchJSON(`${API}?action=getOrdenById&id=${ordenId}`, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    if (!data || data.tipo === "error") {
      mostrarMensaje("error", "No se pudo cargar la orden.");
      return;
    }

    modOrdenId.value = data.id;
    modCantidad.value = data.cantidad;

    // 🔒 límite duro
    modCantidad.max = data.maxCantidad;

    // 📦 info de stock visible
    document.getElementById(
      "info-stock"
    ).textContent = `Stock disponible: ${data.stockDisponible}`;

    modMotivo.value = "";
    modalModificar.style.display = "flex";
  }

  btnConfirmarModificar.addEventListener("click", async () => {
    const ordenId = parseInt(modOrdenId.value);
    const cantidad = parseInt(modCantidad.value);
    const motivo = modMotivo.value.trim();

    let ok = true;

    document.getElementById("error-modCantidad").style.display = "none";
    document.getElementById("error-modMotivo").style.display = "none";

    if (!cantidad || cantidad <= 0) {
      document.getElementById("error-modCantidad").style.display = "block";
      ok = false;
    }

    if (!motivo) {
      document.getElementById("error-modMotivo").style.display = "block";
      ok = false;
    }

    if (!ok) return;

    const errorStockModal = document.getElementById("modal-error-stock");

    // limpiar antes
    errorStockModal.style.display = "none";
    errorStockModal.textContent = "";

    if (cantidad > parseInt(modCantidad.max)) {
      errorStockModal.textContent = `Stock insuficiente. Máximo permitido: ${modCantidad.max}`;
      errorStockModal.style.display = "block";
      return;
    }

    const fd = new FormData();
    fd.append("accion", "modificar");
    fd.append("id", ordenId);
    fd.append("cantidad", cantidad);
    fd.append("motivo", motivo);

    const res = await fetchJSON(API, {
      method: "POST",
      body: fd,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    if (res.tipo === "success") {
      mostrarMensaje("success", res.mensaje);
      modalModificar.style.display = "none";
      refrescarTabla();
    } else {
      mostrarMensaje("error", res.mensaje);
    }
  });

  modCantidad.addEventListener("input", () => {
    document.getElementById("modal-error-stock").style.display = "none";
  });

  btnCancelarModificar.addEventListener("click", () => {
    modalModificar.style.display = "none";
  });

  // ------------------------------
  // CONFIRMAR ACCIÓN (cambiar estado)
  // ------------------------------
  confirmYes.addEventListener("click", async () => {
    const id = confirmYes.dataset.ordenId;
    const accion = confirmYes.dataset.action;
    const nuevoEstadoId = confirmYes.dataset.nuevoEstadoId;

    if (accion === "cambiarEstado") {
      const fd = new FormData();
      fd.append("accion", "cambiarEstado");
      fd.append("id", id);
      fd.append("nuevoEstadoId", nuevoEstadoId);

      try {
        const res = await fetchJSON(API, {
          method: "POST",
          body: fd,
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });

        if (res.tipo === "success") {
          mostrarMensaje(res.tipo, res.mensaje);
          await refrescarTabla();
        } else {
          mostrarMensaje(res.tipo, res.mensaje);
        }
      } catch (err) {
        console.error("Error al cambiar el estado:", err);
        mostrarMensaje("error", "Error al cambiar el estado de la orden.");
      }
    }

    modal.style.display = "none";
  });

  confirmNo.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // ------------------------------
  // INICIAR
  // ------------------------------
  refrescarTabla();
});
