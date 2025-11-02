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
  const campoId = document.getElementById("campoId");

  const tableBody = document.querySelector(".table-modern tbody");

  // Modal eliminar
  const modal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

  // Modal mover
  const moverModal = document.getElementById("moverModal");
  const origenInfo = document.getElementById("origenInfo");
  const potreroDestino = document.getElementById("potreroDestino");
  const confirmMover = document.getElementById("confirmMover");
  const cancelarMover = document.getElementById("cancelarMover");

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

  // ===== LOV maps =====
  const LOVS = {
    pastura: buildLovMap(pasturaId),
    categoria: buildLovMap(categoriaId),
    campo: buildLovMap(campoId),
  };

  // ===== Filtros =====
  const abrirFiltrosBtn = document.getElementById("abrirFiltros");
  const filtroModal = document.getElementById("filtroModal");
  const aplicarFiltrosBtn = document.getElementById("aplicarFiltros");
  const limpiarFiltrosBtn = document.getElementById("limpiarFiltros");
  const cerrarFiltrosBtn = document.getElementById("cerrarFiltros");
  const resumenFiltros = document.getElementById("resumenFiltros");

  const FILTROS = {
    campoIds: [],
    pasturaIds: [],
    categoriaIds: [],
    soloConCategoria: false,
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
    const campoGroup = document.getElementById("filtroCampoGroup");
    const pasturaGroup = document.getElementById("filtroPasturaGroup");
    const categoriaGroup = document.getElementById("filtroCategoriaGroup");

    if (!campoGroup || !pasturaGroup || !categoriaGroup) {
      console.error("Faltan contenedores de filtros");
      return;
    }

    campoGroup.innerHTML = "";
    pasturaGroup.innerHTML = "";
    categoriaGroup.innerHTML = "";

    buildCheckGroupFromSelect(
      campoId,
      campoGroup,
      "filtro_campo",
      FILTROS.campoIds
    );
    buildCheckGroupFromSelect(
      pasturaId,
      pasturaGroup,
      "filtro_pastura",
      FILTROS.pasturaIds
    );

    // Categorías: Eliminamos "Todas las categorías".
    // Solo mostramos "Sólo con categoría", + categorías reales

    // Checkbox "Sólo con categoría"
    categoriaGroup.appendChild(
      createCheck(
        "filtro_categoria_solo",
        "__CON_CATEGORIA__",
        "Sólo con categoría",
        FILTROS.soloConCategoria
      )
    );

    // Checkboxes de categorías reales
    buildCheckGroupFromSelect(
      categoriaId,
      categoriaGroup,
      "filtro_categoria",
      FILTROS.categoriaIds
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
    if (FILTROS.campoIds.length) {
      const nombres = FILTROS.campoIds.map((id) => LOVS.campo[id] || id);
      partes.push(`Campo: ${nombres.join(", ")}`);
    }
    if (FILTROS.pasturaIds.length) {
      const nombres = FILTROS.pasturaIds.map((id) => LOVS.pastura[id] || id);
      partes.push(`Pastura: ${nombres.join(", ")}`);
    }

    // Lógica de Categoría simplificada y sin "cualquiera"
    if (FILTROS.soloConCategoria) {
      partes.push("Categoría: sólo con categoría");
    } else if (FILTROS.categoriaIds.length) {
      const nombres = FILTROS.categoriaIds.map(
        (id) => LOVS.categoria[id] || id
      );
      partes.push(`Categoría: ${nombres.join(", ")}`);
    }
    // Si no hay filtro, no se añade nada (se elimina "Categoría: cualquiera")

    resumenFiltros.textContent = partes.length
      ? `Filtros → ${partes.join(" · ")}`
      : "";
  }

  limpiarFiltrosBtn?.addEventListener("click", async () => {
    FILTROS.campoIds = [];
    FILTROS.pasturaIds = [];
    FILTROS.categoriaIds = [];
    FILTROS.soloConCategoria = false;
    prepararChecksModal();
    pintarResumenFiltros();
    await refrescarTabla();
  });

  aplicarFiltrosBtn?.addEventListener("click", async () => {
    FILTROS.campoIds = getCheckedValues("filtro_campo");
    FILTROS.pasturaIds = getCheckedValues("filtro_pastura");
    FILTROS.categoriaIds = getCheckedValues("filtro_categoria");
    FILTROS.soloConCategoria = getCheckedValues(
      "filtro_categoria_solo"
    ).includes("__CON_CATEGORIA__");
    cerrarModalFiltros();
    pintarResumenFiltros();
    await refrescarTabla();
  });

  // ===== Modo form =====
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

  cancelarEdicion.addEventListener("click", setRegistrarMode);

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

  // ===== Tabla =====
  async function refrescarTabla() {
    try {
      const params = new URLSearchParams({ action: "list" });

      (FILTROS.campoIds || []).forEach((id) => params.append("campoId[]", id));
      (FILTROS.pasturaIds || []).forEach((id) =>
        params.append("pasturaId[]", id)
      );

      // Lógica de filtro de categoría
      if (FILTROS.soloConCategoria) {
        params.append("conCategoria", "1");
        // No se envían IDs de categoría si "solo con categoría" está activo
      } else {
        // Se envían IDs específicos si hay
        (FILTROS.categoriaIds || []).forEach((id) =>
          params.append("categoriaId[]", id)
        );
      }

      const potreros = await fetchJSON(`${API}?${params.toString()}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      tableBody.innerHTML = "";
      if (!Array.isArray(potreros) || potreros.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#666;">No hay potreros para los filtros aplicados.</td></tr>`;
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

        const pasturaNombre = p.pasturaNombre || LOVS.pastura[_pasturaId] || "";
        const categoriaNombre =
          p.categoriaNombre || LOVS.categoria[_categoriaId] || "";
        const campoNombre = p.campoNombre || LOVS.campo[_campoId] || "";

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
          </td>`;
        tableBody.appendChild(tr);
      }
    } catch (err) {
      console.error(err);
      tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#a00;">Error cargando tabla.</td></tr>`;
    }
  }

  // ==== Editar / Eliminar ====
  tableBody.addEventListener("click", (e) => {
    const editBtn = e.target.closest(".js-edit");
    const delBtn = e.target.closest(".js-delete");
    const moveBtn = e.target.closest(".js-move");

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

    if (moveBtn) {
      const tr = moveBtn.closest("tr");
      abrirModalMoverCategoria(tr);
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
      flash("error", "Error al eliminar el potrero.");
    }
  });

  confirmNo?.addEventListener("click", () => {
    modal.style.display = "none";
    delete modal.dataset.id;
  });

  // ==== Submit Form ====
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const categoriaIdValue = categoriaId.value
      ? parseInt(categoriaId.value)
      : null;
    const cantidadCategoriaValue = cantidadCategoria.value
      ? parseInt(cantidadCategoria.value)
      : null;

    if (categoriaIdValue && !cantidadCategoriaValue) {
      flash("error", "Si ingresas una categoría, debes ingresar la cantidad.");
      return;
    }
    if (!categoriaIdValue && cantidadCategoriaValue) {
      flash(
        "error",
        "Si ingresas una cantidad, debes seleccionar una categoría."
      );
      return;
    }
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
      flash("error", "Error al procesar la solicitud.");
    }
  });

  // ===== Mover Categoría (Total) =====
  async function abrirModalMoverCategoria(tr) {
    const potreroOrigen = tr.dataset.id;
    const categoriaOrigen = tr.dataset.categoriaId;
    const cantidadOrigenTotal = tr.dataset.cantidadCategoria;
    const categoriaNombre = LOVS.categoria[categoriaOrigen] || "N/A";

    if (!moverModal || !potreroDestino || !confirmMover || !origenInfo) {
      console.error("Faltan elementos del modal mover categoría.");
      flash("error", "Error interno al cargar el modal de movimiento.");
      return;
    }

    moverModal.querySelector("h3").textContent = "Mover Categoría (Total)";
    moverModal.querySelector("p").textContent =
      "Seleccioná a qué potrero querés mover toda la categoría:";

    origenInfo.innerHTML = `
      <strong>Potrero Origen:</strong> ${tr.dataset.nombre}<br>
      <strong>Categoría:</strong> ${categoriaNombre}<br>
      <strong>Cantidad Total a Mover:</strong> ${cantidadOrigenTotal}
    `;

    moverModal.style.display = "flex";
    potreroDestino.innerHTML = `<option value="">Cargando potreros...</option>`;

    try {
      // Nota: Aquí se llama a la API con solo action=list, sin filtros
      const potreros = await fetchJSON(`${API}?action=list`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      potreroDestino.innerHTML = `<option value="">-- Seleccioná potrero destino --</option>`;
      potreros
        .filter(
          (p) =>
            String(p.id) !== String(potreroOrigen) &&
            (p.categoriaId == null || p.categoriaId === 0)
        )
        .forEach((p) => {
          const opt = document.createElement("option");
          opt.value = p.id;
          opt.textContent = p.nombre;
          potreroDestino.appendChild(opt);
        });

      confirmMover.onclick = async () => {
        const destino = potreroDestino.value;
        if (!destino) return flash("error", "Seleccioná un potrero destino.");

        confirmMover.disabled = true;
        try {
          const resp = await fetchJSON(API, {
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
          moverModal.style.display = "none";
          if (resp.tipo === "success") await refrescarTabla();
        } catch (err) {
          console.error(err);
          flash("error", "Error al mover la categoría.");
        } finally {
          confirmMover.disabled = false;
        }
      };

      cancelarMover.onclick = () => {
        moverModal.style.display = "none";
        potreroDestino.innerHTML = "";
        origenInfo.innerHTML = "";
      };
    } catch (err) {
      console.error(err);
      potreroDestino.innerHTML = `<option value="">No se pudo cargar la lista (backend no JSON)</option>`;
      flash("error", "No se pudo cargar la lista de potreros.");
    }
  }

  moverModal?.addEventListener("click", (e) => {
    if (e.target.id === "moverModal") {
      cancelarMover?.click();
    }
  });

  // ===== Inicializar =====
  setRegistrarMode();
  pintarResumenFiltros();
  refrescarTabla();
});
