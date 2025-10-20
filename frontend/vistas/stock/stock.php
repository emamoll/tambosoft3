<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

require_once __DIR__ . '../../../../backend/controladores/stockController.php';

$controller = new StockController();

// Precargar Alimentos y Proveedores para los combos
$alimentos = $controller->obtenerAlimentos();
$proveedores = $controller->obtenerProveedores();

$mensaje = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mensaje = $controller->procesarFormularios();
}

function esc($s)
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="es-ar">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambosoft: Stocks</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="../../css/stock.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>
  <script src="../../javascript/header.js"></script>
</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <main>
    <div style="display:flex; justify-content: center; flex-wrap: wrap; gap: 30px; padding: 20px;">

      <!-- ===== FORMULARIO ===== -->
      <div class="form-container form" style="flex: 1; max-width: 600px;">
        <h2 id="form-title"><i class="fas fa-boxes"></i> Registrar Lote de Stock</h2>

        <form id="stockForm">
          <input type="hidden" name="accion" id="accion" value="registrar" />
          <input type="hidden" name="id" id="id" />

          <div class="form-group">
            <label for="alimentoId">Alimento:</label>
            <select id="alimentoId" name="alimentoId" required>
              <option value="">-- Seleccioná un alimento --</option>
              <?php foreach ($alimentos as $a): ?>
                <option value="<?= esc($a['id']) ?>"><?= esc($a['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="cantidad">Cantidad:</label>
            <input type="number" id="cantidad" name="cantidad" min="0" step="0.01" required />
          </div>

          <div class="form-group row-checkbox">
            <input type="checkbox" id="produccionInterna" name="produccionInterna" />
            <label for="produccionInterna" class="label-checkbox">Producción interna</label>
          </div>

          <div class="form-group" id="proveedorGroup">
            <label for="proveedorId">Proveedor:</label>
            <select id="proveedorId" name="proveedorId">
              <option value="">-- Seleccioná un proveedor --</option>
              <?php foreach ($proveedores as $p): ?>
                <option value="<?= esc($p['id']) ?>"><?= esc($p['denominacion']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="fechaIngreso">Fecha de Ingreso:</label>
            <input type="date" id="fechaIngreso" name="fechaIngreso" required />
          </div>

          <!-- ===== BOTONES Y FILTRO (igual que potrero) ===== -->
          <div class="botonera"
            style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
            <button type="submit" id="submitBtn" class="btn-usuario">Registrar Lote</button>
            <button type="button" id="cancelarEdicion" class="btn-usuario"
              style="display: none; background-color: #c0392b;">Cancelar edición</button>
            <button type="button" id="abrirFiltros" class="btn btn-primary">🔍 Filtrar</button>
            <span id="resumenFiltros" style="color: #084a83; font-weight: 600; margin-left: 8px;"></span>
          </div>
        </form>
      </div>

      <!-- ===== TABLA ===== -->
      <div class="form-container table">
        <div class="table-wrapper">
          <table class="table-modern">
            <thead>
              <tr>
                <th>ID</th>
                <th>Alimento</th>
                <th>Cantidad</th>
                <th>Tipo</th>
                <th>Proveedor</th>
                <th>Fecha Ingreso</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <!-- ===== MODAL FILTROS ===== -->
      <div id="filtroModal" class="modal">
        <div class="modal-content">
          <h3>Filtrar Lotes de Stock</h3>

          <div class="filtro-grupo">
            <h4>Alimentos</h4>
            <div id="filtroAlimentoGroup" class="radio-group"></div>
          </div>

          <div class="filtro-grupo">
            <h4>Producción</h4>
            <div id="filtroProduccionGroup" class="radio-group"></div>
          </div>

          <div class="filtro-grupo">
            <h4>Proveedores</h4>
            <div id="filtroProveedorGroup" class="radio-group"></div>
          </div>

          <div class="modal-actions">
            <button id="aplicarFiltros" class="btn btn-primary">Aplicar</button>
            <button id="limpiarFiltros" class="btn btn-cancel">Limpiar</button>
            <button id="cerrarFiltros" class="btn btn-cancel">Cerrar</button>
          </div>
        </div>
      </div>

      <!-- ===== MODAL CONFIRMACIÓN ===== -->
      <div id="confirmModal" class="modal">
        <div class="modal-content" style="max-width: 420px; text-align: center;">
          <h3>Confirmar Eliminación</h3>
          <p id="confirmText">¿Estás seguro de eliminar este registro?</p>
          <div class="modal-actions">
            <button id="confirmYes" class="btn btn-danger">Eliminar</button>
            <button id="confirmNo" class="btn btn-cancel">Cancelar</button>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- ===== JAVASCRIPT ===== -->
  <script src="../../javascript/stock.js"></script>
</body>

</html>