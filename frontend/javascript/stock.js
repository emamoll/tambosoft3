document.addEventListener("DOMContentLoaded", () => {
  console.log("stock.js cargado correctamente");

  // NOTA: Se asume que la variable global ALL_ALIMENTOS está definida en stock.php
  // y contiene un array de objetos {id, nombre, tipoAlimentoId} de todos los alimentos.

  const API = "../../../backend/controladores/stockController.php";

  // --- FORM ---
  const form = document.getElementById("stockForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");

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

  // --- TABLA AGRUPADA ---
  const tableBody = document.querySelector(".table-modern tbody");

  // --- MODAL DETALLE ---
  const modalDetalle = document.getElementById("detalleModal");
  const detalleBody = document.getElementById("detalleBody");
  const detalleCerrar = document.getElementById("detalleCerrar");

  // --- MODAL ELIMINAR (AGRUPADO) ---
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
      console.error("[Backend NON-JSON]", raw);
      throw new Error("El backend no devolvió JSON");
    }

    return JSON.parse(raw);
  }

  // ----------------------------------------------------
  // FUNCIÓN: FILTRAR Y CARGAR ALIMENTOS
  // ----------------------------------------------------
  function cargarAlimentosPorTipo() {
    const tipoSeleccionado = tipoAlimentoId.value;
    const alimentoSeleccionadoActual = alimentoId.value;

    // Limpiar el select de alimentos, manteniendo la opción por defecto
    alimentoId.innerHTML =
      '<option value="">-- Seleccioná un Alimento --</option>';

    if (tipoSeleccionado && typeof ALL_ALIMENTOS !== "undefined") {
      // Filtra los alimentos según el tipo de alimento seleccionado
      const alimentosFiltrados = ALL_ALIMENTOS.filter(
        (a) => String(a.tipoAlimentoId) === tipoSeleccionado
      );

      alimentosFiltrados.forEach((alimento) => {
        const option = document.createElement("option");
        option.value = alimento.id;
        option.textContent = alimento.nombre;

        // Mantener la selección actual si estamos en modo edición
        if (String(alimento.id) === alimentoSeleccionadoActual) {
          option.selected = true;
        }

        alimentoId.appendChild(option);
      });
    }
  }

  // ----------------------------------------------------
  // EVENT LISTENER PARA TIPO DE ALIMENTO
  // ----------------------------------------------------
  tipoAlimentoId.addEventListener("change", () => {
    // 1. Resetear el valor de Alimento
    alimentoId.value = "";
    // 2. Cargar las opciones filtradas
    cargarAlimentosPorTipo();
  });

  // ------------------------------
  // MODO REGISTRAR
  // ------------------------------
  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar";
    cancelarEdicion.style.display = "none";

    idInput.value = "";
    form.reset();
    produccionInternaValor.value = "0"; // Comprado por defecto
    produccionInternaCheck.checked = false;

    // Al resetear el formulario, aseguramos que la lista de Alimentos esté filtrada (o vacía)
    // El Tipo de Alimento estará en "" (Seleccioná un Tipo), por lo que cargará solo la opción por defecto.
    cargarAlimentosPorTipo();
  }

  // ------------------------------
  // MODO EDITAR (desde detalle)
  // ------------------------------
  async function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;

    almacenId.value = data.almacenId;
    tipoAlimentoId.value = data.tipoAlimentoId;

    // 1. CRUCIAL: Cargar la lista de alimentos filtrada por el tipo del registro
    await cargarAlimentosPorTipo();

    // 2. Ahora seleccionamos el alimentoId
    alimentoId.value = data.alimentoId;

    cantidad.value = data.cantidad;

    produccionInternaCheck.checked = String(data.produccionInterna) === "1";
    produccionInternaValor.value = data.produccionInterna;

    proveedorId.value = data.proveedorId ?? "";
    precio.value = data.precio ?? "";
    fechaIngreso.value = data.fechaIngreso ?? "";
  }

  // ------------------------------
  // LISTAR STOCK (AGRUPADO)
  // ------------------------------
  // ----------------------------------------------------
  // FUNCIÓN: REFRESCAR TABLA CON FILTROS
  // ----------------------------------------------------
  async function refrescarTabla(filtros = {}) {
    console.log("Listando stock con filtros...");

    try {
      // Usar URLSearchParams para agregar los filtros a la URL de la solicitud
      const params = new URLSearchParams(filtros);

      const resp = await fetch(`${API}?action=list&${params.toString()}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      const raw = await resp.text();
      let data;
      try {
        data = JSON.parse(raw);
      } catch (e) {
        console.error("❌ ERROR PARSEANDO JSON", e);
        return;
      }

      tableBody.innerHTML = "";

      if (!Array.isArray(data) || data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No hay stock registrado.</td></tr>`;
        return;
      }

      data.forEach((s) => {
        const tr = document.createElement("tr");

        // clave del grupo: Campo + Tipo + Alimento + Origen + Proveedor
        const idAgrupado = [
          s.almacenId,
          s.tipoAlimentoId,
          s.alimentoId,
          s.produccionInterna,
          s.proveedorId ?? "0",
        ].join("-");

        tr.dataset.idAgrupado = idAgrupado;

        tr.dataset.almacenId = s.almacenId;
        tr.dataset.tipoAlimentoId = s.tipoAlimentoId;
        tr.dataset.alimentoId = s.alimentoId;
        tr.dataset.produccionInterna = s.produccionInterna;
        tr.dataset.proveedorId = s.proveedorId ?? "";

        tr.innerHTML = `
        <td>${s.almacenNombre}</td>    <td>${s.tipoAlimentoNombre}</td>  
        <td>${s.alimentoNombre}</td> <td>${s.cantidad}</td> 
        <td>${s.produccionInterna == 1 ? "Prod. Interna" : "Proveedor"}</td> 
        <td>${s.produccionInterna == 1 ? "-" : s.proveedorNombre}</td> 
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon js-verDetalle" title="Ver detalle">📋</button>
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
  // CARGAR DETALLE EN MODAL
  // ------------------------------
  async function cargarDetalleGrupo(
    almacenId,
    tipoAlimentoId,
    alimentoId,
    produccionInterna,
    proveedorId
  ) {
    // armamos query para action=detalleGrupo (nuevo endpoint en el controller)
    const params = new URLSearchParams({
      action: "detalleGrupo",
      almacenId: almacenId,
      tipoAlimentoId: tipoAlimentoId,
      alimentoId: alimentoId,
      produccionInterna: produccionInterna,
    });

    if (proveedorId && proveedorId !== "0" && proveedorId !== "null") {
      params.append("proveedorId", proveedorId);
    }

    const data = await fetchJSON(`${API}?${params.toString()}`, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    detalleBody.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
      detalleBody.innerHTML = `<tr><td colspan="10" style="text-align:center;">No hay filas para este grupo.</td></tr>`;
    } else {
      data.forEach((r) => {
        const esPropia = String(r.produccionInterna) === "1";
        const proveedorTexto = !esPropia ? r.proveedorNombre || "—" : "—";
        const precioTexto = !esPropia ? r.precio ?? "—" : "—";

        const tr = document.createElement("tr");

        tr.innerHTML = `
        <td>${r.id}</td> <td>${r.almacenNombre}</td> <td>${
          r.tipoAlimentoNombre
        }</td> <td>${r.alimentoNombre}</td> <td>${r.cantidad}</td> <td>${
          esPropia ? "Prod. Interna" : "Proveedor"
        }</td> <td>${proveedorTexto}</td> <td>${precioTexto}</td> <td>${
          r.fechaIngreso
        }</td> <td>
            <div class="table-actions">
                <button type="button" class="btn-icon edit detalle-edit" data-id="${
                  r.id
                }" title="Modificar">✏️</button>
                <button type="button" class="btn-icon delete detalle-delete" data-id="${
                  r.id
                }" title="Eliminar">🗑️</button>
            <div>
          </td>
      `;
        detalleBody.appendChild(tr);
      });
    }

    // guardo claves del grupo en el modal para recargar luego de eliminar
    modalDetalle.dataset.almacenId = almacenId;
    modalDetalle.dataset.tipoAlimentoId = tipoAlimentoId;
    modalDetalle.dataset.alimentoId = alimentoId;
    modalDetalle.dataset.produccionInterna = produccionInterna;
    modalDetalle.dataset.proveedorId = proveedorId || "";

    modalDetalle.style.display = "flex";
  }

  // ------------------------------
  // CLICK EN TABLA AGRUPADA → ABRIR DETALLE
  // ------------------------------
  tableBody.addEventListener("click", (e) => {
    if (!e.target.classList.contains("js-verDetalle")) return;

    const tr = e.target.closest("tr");

    cargarDetalleGrupo(
      tr.dataset.almacenId,
      tr.dataset.tipoAlimentoId,
      tr.dataset.alimentoId,
      tr.dataset.produccionInterna,
      tr.dataset.proveedorId
    );
  });

  // ------------------------------
  // ACCIONES EN DETALLE (editar / eliminar)
  // ------------------------------
  detalleBody.addEventListener("click", async (e) => {
    const btn = e.target;

    // EDITAR DESDE MODAL
    if (btn.classList.contains("detalle-edit")) {
      const id = btn.dataset.id;

      // nuevo endpoint en el controller: action=get&id=...
      const data = await fetchJSON(`${API}?action=get&id=${id}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      await setEditarMode(data);
      modalDetalle.style.display = "none";
      return;
    }

    // ELIMINAR DESDE MODAL
    if (btn.classList.contains("detalle-delete")) {
      const id = btn.dataset.id;

      confirmText.textContent = `¿Eliminar el registro #${id}?`;
      confirmYes.dataset.deleteId = id;
      confirmYes.dataset.deleteTipo = "individual";

      modal.style.display = "flex";
      return;
    }
  });

  // ------------------------------
  // CONFIRMAR ELIMINAR INDIVIDUAL
  // ------------------------------
  confirmYes.addEventListener("click", async () => {
    const id = confirmYes.dataset.deleteId;
    const tipo = confirmYes.dataset.deleteTipo;

    const fd = new FormData();
    fd.append("accion", tipo === "individual" ? "eliminar" : "eliminarGrupo");
    fd.append("id", id);

    const res = await fetchJSON(API, {
      method: "POST",
      body: fd,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    if (res.tipo === "success") {
      // recargar el detalle del grupo
      await cargarDetalleGrupo(
        modalDetalle.dataset.almacenId,
        modalDetalle.dataset.tipoAlimentoId,
        modalDetalle.dataset.alimentoId,
        modalDetalle.dataset.produccionInterna,
        modalDetalle.dataset.proveedorId
      );
      // y la tabla principal agrupada
      await refrescarTabla();
    } else {
      alert(res.mensaje);
    }

    modal.style.display = "none";
  });

  confirmNo.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // ------------------------------
  // CERRAR MODAL DETALLE
  // ------------------------------
  if (detalleCerrar) {
    detalleCerrar.addEventListener("click", () => {
      modalDetalle.style.display = "none";
    });
  }

  // ----------------------------------------------------
  // FUNCIÓN: LLENAR LOS FILTROS DE ALIMENTOS Y PROVEEDORES
  // ----------------------------------------------------
  function llenarFiltros() {
    const filtroAlimentoGroup = document.getElementById("filtroAlimentoGroup");
    const filtroProveedorGroup = document.getElementById(
      "filtroProveedorGroup"
    );

    // Limpiar los filtros actuales
    filtroAlimentoGroup.innerHTML = "";
    filtroProveedorGroup.innerHTML = "";

    // Llenar los alimentos disponibles
    ALL_ALIMENTOS.forEach((alimento) => {
      const option = document.createElement("label");
      option.classList.add("radio-card");
      option.innerHTML = `
      <input type="checkbox" name="filtro_alimentoId" value="${alimento.id}" />
      <span class="radio-label">${alimento.nombre}</span>
    `;
      filtroAlimentoGroup.appendChild(option);
    });

    // Llenar los proveedores disponibles
    proveedores.forEach((proveedor) => {
      const option = document.createElement("label");
      option.classList.add("radio-card");
      option.innerHTML = `
      <input type="checkbox" name="filtro_proveedorId" value="${proveedor.id}" />
      <span class="radio-label">${proveedor.denominacion}</span>
    `;
      filtroProveedorGroup.appendChild(option);
    });
  }

  // ----------------------------------------------------
  // FUNCIÓN: APLICAR FILTROS Y REFRESCAR LA TABLA
  // ----------------------------------------------------
  document.getElementById("aplicarFiltros").addEventListener("click", () => {
    const filtros = {
      almacenIds: Array.from(
        document.querySelectorAll('input[name="filtro_almacenId"]:checked')
      ).map((input) => input.value),
      tipoAlimentoIds: Array.from(
        document.querySelectorAll('input[name="filtro_tipoAlimentoId"]:checked')
      ).map((input) => input.value),
      alimentoIds: Array.from(
        document.querySelectorAll('input[name="filtro_alimentoId"]:checked')
      ).map((input) => input.value),
      produccionInterna: Array.from(
        document.querySelectorAll(
          'input[name="filtro_produccionInterna"]:checked'
        )
      ).map((input) => input.value),
      proveedorIds: Array.from(
        document.querySelectorAll('input[name="filtro_proveedorId"]:checked')
      ).map((input) => input.value),
      fechaMin: document.getElementById("filtroFechaMin").value,
      fechaMax: document.getElementById("filtroFechaMax").value,
    };

    // Llamar la función para refrescar la tabla con los filtros aplicados
    refrescarTabla(filtros);
    document.getElementById("filtroModal").style.display = "none";
  });

  // ----------------------------------------------------
  // FUNCIÓN: LIMPIAR LOS FILTROS
  // ----------------------------------------------------
  document.getElementById("limpiarFiltros").addEventListener("click", () => {
    // Limpiar los filtros de checkbox
    document
      .querySelectorAll("input[type=checkbox]")
      .forEach((checkbox) => (checkbox.checked = false));

    // Limpiar los campos de fecha
    document.getElementById("filtroFechaMin").value = "";
    document.getElementById("filtroFechaMax").value = "";
  });
  // ------------------------------
  // SUBMIT FORM → registrar / modificar
  // ------------------------------
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    produccionInternaValor.value = produccionInternaCheck.checked ? "1" : "0";
    const esPropia = produccionInternaValor.value === "1";

    let ok = true;
    document
      .querySelectorAll(".error-message")
      .forEach((el) => (el.style.display = "none"));

    const mostrarError = (id) => {
      const el = document.getElementById(id);
      if (el) el.style.display = "block";
      ok = false;
    };

    if (!almacenId.value.trim()) mostrarError("error-almacenId");
    if (!tipoAlimentoId.value.trim()) mostrarError("error-tipoAlimentoId");
    if (!alimentoId.value.trim()) mostrarError("error-alimentoId");

    const cant = Number(cantidad.value);
    if (!cantidad.value.trim() || !Number.isInteger(cant) || cant <= 0)
      mostrarError("error-cantidad");

    if (!fechaIngreso.value.trim()) mostrarError("error-fecha");

    if (!esPropia) {
      if (!proveedorId.value.trim()) mostrarError("error-proveedorId");

      const prec = Number(precio.value);
      if (!precio.value.trim() || isNaN(prec) || prec < 0)
        mostrarError("error-precio");
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
        await refrescarTabla();
        setRegistrarMode();
      } else {
        alert(data.mensaje);
      }
    } catch (err) {
      console.error(err);
      alert("Error al procesar la solicitud.");
    }
  });

  cancelarEdicion.addEventListener("click", setRegistrarMode);

  // Llamada al abrir el modal de filtros para llenar los campos dinámicamente
  document.getElementById("abrirFiltros").addEventListener("click", () => {
    llenarFiltros();
    document.getElementById("filtroModal").style.display = "flex";
  });

  // ------------------------------
  // INICIAR
  // ------------------------------
  setRegistrarMode();
  refrescarTabla();
});
