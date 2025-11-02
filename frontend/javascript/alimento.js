document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("alimentoForm");
  const idInput = document.getElementById("id");
  const accionInput = document.getElementById("accion");
  const submitBtn = document.getElementById("submitBtn");
  const cancelarEdicion = document.getElementById("cancelarEdicion");
  const formTitle = document.getElementById("form-title");

  const tipoAlimentoId = document.getElementById("tipoAlimentoId");
  const nombre = document.getElementById("nombre");

  const tableBody = document.querySelector(".table-modern tbody");

  // Modal eliminar
  const modal = document.getElementById("confirmModal");
  const confirmText = document.getElementById("confirmText");
  const confirmYes = document.getElementById("confirmYes");
  const confirmNo = document.getElementById("confirmNo");

  const API = "../../../backend/controladores/alimentoController.php";

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
    tipoAlimento: buildLovMap(tipoAlimentoId),
  };

  // ===== Filtros =====
  const abrirFiltrosBtn = document.getElementById("abrirFiltros");
  const filtroModal = document.getElementById("filtroModal");
  const aplicarFiltrosBtn = document.getElementById("aplicarFiltros");
  const limpiarFiltrosBtn = document.getElementById("limpiarFiltros");
  const cerrarFiltrosBtn = document.getElementById("cerrarFiltros");
  const resumenFiltros = document.getElementById("resumenFiltros");

  const FILTROS = {
    tipoAlimentosIds: [],
    nombres: [],
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
    const tipoAlimentoGroup = document.getElementById(
      "filtrotipoAlimentoGroup"
    );
    const nombreGroup = document.getElementById("filtronombreGroup");

    if (!tipoAlimentoGroup || !nombreGroup) {
      console.error("Faltan contenedores de filtros");
      return;
    }

    tipoAlimentoGroup.innerHTML = "";
    nombreGroup.innerHTML = "";

    // Tipos de alimento (desde el combo principal)
    buildCheckGroupFromSelect(
      tipoAlimentoId,
      tipoAlimentoGroup,
      "filtro_tipoAlimentoId",
      FILTROS.tipoAlimentosIds
    );

    // Nombres (desde lo que ya existe en la tabla)
    const nombresSet = new Set();
    document.querySelectorAll(".table-modern tbody tr").forEach((tr) => {
      const n = tr.dataset.nombre;
      if (n) nombresSet.add(n);
    });

    [...nombresSet].forEach((n) => {
      nombreGroup.appendChild(
        createCheck("filtro_nombre", n, n, FILTROS.nombres.includes(n))
      );
    });
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
    if (FILTROS.tipoAlimentosIds.length) {
      const nombres = FILTROS.tipoAlimentosIds.map(
        (id) => LOVS.tipoAlimento[id] || id
      );
      partes.push(`Tipo de Alimento: ${nombres.join(", ")}`);
    }
    if (FILTROS.nombres.length) {
      partes.push(`Nombre: ${FILTROS.nombres.join(", ")}`);
    }

    resumenFiltros.textContent = partes.length
      ? `Filtros → ${partes.join(" · ")}`
      : "";
  }

  limpiarFiltrosBtn?.addEventListener("click", async () => {
    FILTROS.tipoAlimentosIds = [];
    FILTROS.nombres = [];
    prepararChecksModal();
    pintarResumenFiltros();
    await refrescarTabla();
  });

  aplicarFiltrosBtn?.addEventListener("click", async () => {
    FILTROS.tipoAlimentosIds = getCheckedValues("filtro_tipoAlimentoId");
    FILTROS.nombres = getCheckedValues("filtro_nombre");
    cerrarModalFiltros();
    pintarResumenFiltros();
    await refrescarTabla();
  });

  // ===== Modo form =====
  function setRegistrarMode() {
    accionInput.value = "registrar";
    submitBtn.textContent = "Registrar";
    formTitle.textContent = "Registrar Alimento";
    cancelarEdicion.style.display = "none";
    idInput.value = "";
    form.reset();
  }

  function setEditarMode(data) {
    accionInput.value = "modificar";
    submitBtn.textContent = "Modificar";
    formTitle.textContent = "Modificar Alimento";
    cancelarEdicion.style.display = "inline-block";

    idInput.value = data.id;
    tipoAlimentoId.value = data.tipoAlimentoId || "";
    nombre.value = data.nombre || "";

    nombre.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  cancelarEdicion.addEventListener("click", setRegistrarMode);

  function extractDataFromRow(tr) {
    return {
      id: tr.dataset.id,
      tipoAlimentoId: tr.dataset.tipoAlimentoId,
      nombre: tr.dataset.nombre,
    };
  }

  // ===== Tabla =====
  async function refrescarTabla() {
    try {
      const params = new URLSearchParams({ action: "listar" });

      (FILTROS.tipoAlimentosIds || []).forEach((id) =>
        params.append("tipoAlimentoId[]", id)
      );
      (FILTROS.nombres || []).forEach((n) => params.append("nombre[]", n));

      const alimentos = await fetchJSON(`${API}?${params.toString()}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      tableBody.innerHTML = "";
      if (!Array.isArray(alimentos) || alimentos.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#666;">No hay alimentos para los filtros aplicados.</td></tr>`;
        return;
      }

      for (const alimento of alimentos) {
        const tr = document.createElement("tr");

        const _id = alimento.id ?? "";
        const _tipoAlimentoId = String(alimento.tipoAlimentoId ?? "");
        const _nombre = alimento.nombre ?? "";

        const tipoAlimentoNombre =
          alimento.tipoAlimentoNombre ||
          LOVS.tipoAlimento[_tipoAlimentoId] ||
          "";

        tr.dataset.id = _id;
        tr.dataset.tipoAlimentoId = _tipoAlimentoId;
        tr.dataset.nombre = _nombre;

        tr.innerHTML = `
          <td>${_id}</td>
          <td>${tipoAlimentoNombre}</td>
          <td>${_nombre}</td>
          <td>
            <div class="table-actions">
              <button type="button" class="btn-icon edit js-edit" title="Modificar">✏️</button>
              <button type="button" class="btn-icon delete js-delete" title="Eliminar">🗑️</button>
            </div>
          </td>`;
        tableBody.appendChild(tr);
      }
    } catch (err) {
      console.error(err);
      tableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#a00;">Error cargando tabla.</td></tr>`;
    }
  }

  // ==== Editar / Eliminar ====
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
      confirmText.textContent = `¿Seguro que deseás eliminar el alimento "${data.nombre}"?`;
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
      flash("error", "Error al eliminar el alimento.");
    }
  });

  confirmNo?.addEventListener("click", () => {
    modal.style.display = "none";
    delete modal.dataset.id;
  });

  // ==== Submit Form ====
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

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

  // ===== Inicializar =====
  setRegistrarMode();
  pintarResumenFiltros();
  refrescarTabla();
});
