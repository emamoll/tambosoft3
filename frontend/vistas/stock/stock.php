<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

require_once __DIR__ . '../../../../backend/controladores/stockController.php';
require_once __DIR__ . '../../../../backend/controladores/alimentoController.php';
require_once __DIR__ . '../../../../backend/controladores/almacenController.php';
require_once __DIR__ . '../../../../backend/controladores/proveedorController.php';

$controllertock = new StockController();
$controllerAlimento = new AlimentoController();
$controllerAlmacen = new AlmacenController();
$controllerProveedor = new ProveedorController();

$alimentos = $controllerAlimento->obtenerAlimentos();
$almacenes = $controllerAlmacen->obtenerAlmacenes();
$proveedores = $controllerProveedor->obtenerProveedores();

// $mensaje = null;
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//   $mensaje = $controllerStock->procesarFormularios();
// }

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
    
      <div class="form-container form" style="flex: 1; max-width: 600px;">
        <h2 id="form-title"> Registrar Stock</h2>

        <form id="stockForm" method="POST" novalidate>
          <input type="hidden" id="id" name="id" value="">
          <input type="hidden" id="accion" name="accion" value="registrar">

          <div class="form-group">
            <label for="almacenId">Campo</label>
            <select id="almacenId" name="almacenId" required>
              <option value="">-- Seleccioná un Campo --</option>
              <?php foreach ($almacenes as $almacen): ?>
                <option value="<?= esc($almacen->getId()) ?>"><?= esc($almacen->getNombre()) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="error-message" id="error-almacenId">El campo es obligatorio.</span>
          </div>

          <div class="form-group">
            <label for="alimentoId">Alimento</label>
            <select id="alimentoId" name="alimentoId" required>
              <option value="">-- Seleccioná un Alimento --</option>
              <?php foreach ($alimentos as $alimento): ?>
                <option value="<?= esc($alimento->getId()) ?>"><?= esc($alimento->getNombre()) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="error-message" id="error-alimentoId">El alimento es obligatorio.</span>
          </div>

          <div class="form-group">
            <label for="cantidad">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" min="1" step="1" required>
            <span class="error-message" id="error-cantidad">La cantidad debe ser mayor a 0.</span>
          </div>

          <div class="form-group">
            <label for="fechaIngreso">Fecha de Ingreso</label>
            <input type="date" id="fechaIngreso" name="fechaIngreso" required>
            <span class="error-message" id="error-fechaIngreso">La fecha es obligatoria.</span>
          </div>

          <div class="form-group row-checkbox">
            <input type="checkbox" id="produccionInterna" name="produccionInterna">
            <label for="produccionInterna" class="label-checkbox">Producción Interna</label>
          </div>

          <div class="form-group" id="proveedorGroup">
            <label for="proveedorId">Proveedor</label>
            <select id="proveedorId" name="proveedorId">
              <option value="">-- Seleccioná un Proveedor --</option>
              <?php foreach ($proveedores as $proveedor): ?>
                <option value="<?= esc($proveedor->getId()) ?>"><?= esc($proveedor->getDenominacion()) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="error-message" id="error-proveedorId">El proveedor es obligatorio si no es producción
              interna.</span>
          </div>

          <div class="form-group" style="display:flex; gap:10px; align-items:center;">
            <button type="submit" id="submitBtn" class="btn-usuario">Registrar</button>

            <button type="button" id="abrirFiltros" class="btn-usuario">Filtrar</button>
            <div id="resumenFiltros" style="margin-left:auto; font-size:.9rem; color:#084a83;"></div>

            <button type="button" id="cancelarEdicion" class="btn-usuario" style="display:none; background:#888;">
              Cancelar edición
            </button>
          </div>
        </form>
      </div>

    </div>


    <div id="filtroModal" class="modal">
      <div class="modal-content">
        <h3>Filtrar Stock</h3>

        <div class="filtro-grupo">
          <h4>Campo</h4>
          <div id="filtroAlmacenGroup" class="radio-group">
          </div>
        </div>

        <div class="filtro-grupo">
          <h4>Alimento</h4>
          <div id="filtroAlimentoGroup" class="radio-group">
          </div>
        </div>

        <div class="filtro-grupo">
          <h4>Origen</h4>
          <div id="filtroOrigenGroup" class="radio-group">
          </div>
        </div>

        <div class="modal-actions" style="display:flex; gap:10px;">
          <button id="aplicarFiltros" class="btn-usuario">Aplicar</button>
          <button id="limpiarFiltros" class="btn btn-secondary">Limpiar</button>
          <button id="cerrarFiltros" class="btn btn-cancel" style="background:#777; color:white">Cerrar</button>
        </div>
      </div>
    </div>

    <div id="confirmModal" class="modal-overlay" style="display:none;">
      <div class="modal-box">
        <h3>Confirmar eliminación</h3>
        <p id="confirmText">¿Seguro que deseas eliminar este lote de stock?</p>
        <div class="modal-actions">
          <button type="button" id="confirmYes" class="btn-usuario" style="background:#c0392b;">Eliminar</button>
          <button type="button" id="confirmNo" class="btn-usuario" style="background:#777;">Cancelar</button>
        </div>
      </div>
    </div>

    <div class="form-container table">
      <h2>Stock Registrados</h2>

      <div class="table-wrapper">
        <table class="table-modern">
          <thead>
            <tr>
              <th>Id</th>
              <th>Alimento</th>
              <th>Cantidad</th>
              <th>Almacén</th>
              <th>Tipo</th>
              <th>Proveedor</th>
              <th>Fec. Ingreso</th>
              <th style="width:120px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>

  <script src="../../javascript/stock.js"></script>
</body>

</html>