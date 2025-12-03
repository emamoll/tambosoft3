<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

// 1. Cargar controladores necesarios
require_once __DIR__ . '../../../../backend/controladores/ordenController.php';
require_once __DIR__ . '../../../../backend/controladores/stockController.php';
require_once __DIR__ . '../../../../backend/controladores/almacenController.php';
require_once __DIR__ . '../../../../backend/controladores/alimentoController.php';
require_once __DIR__ . '../../../../backend/controladores/usuarioController.php';

// 2. Instanciar controladores
$controllerOrden = new OrdenController();
$controllerStock = new StockController();
$controllerAlmacen = new AlmacenController();
$controllerAlimento = new AlimentoController();
$controllerUsuario = new UsuarioController();

// 3. Obtener datos para SELECTs y listado
$ordenes = $controllerOrden->obtenerOrden();
$almacenes = $controllerAlmacen->obtenerAlmacenes();
$alimentos = $controllerAlimento->obtenerAlimentos();
$usuarios = $controllerUsuario->obtenerUsuarios();
$stocks = $controllerStock->obtenerStock();

function esc($s)
{
  // Función de escape (asegura que los datos sean seguros para HTML)
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="es-ar">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambosoft: Ordenes</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css" />
  <link rel="stylesheet" href="../../css/orden.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>
  <script src="../../javascript/header.js"></script>
</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <div class="form-container form">
    <h2 id="form-title">Registrar Orden</h2>

    <form id="ordenForm" method="post" action="../../../backend/controladores/ordenController.php" novalidate>
      <input type="hidden" id="id" name="id" />
      <input type="hidden" id="accion" name="accion" value="registrar" />

      <div class="form-group">
        <label for="potreroId">Potrero *</label>
        <select id="potreroId" name="potreroId" class="campo-input">
          <option value="">-- Seleccioná un Potrero --</option>
          <?php if (is_array($potreros)): ?>
            <?php foreach ($potreros as $potrero): ?>
              <option value="<?= esc(getPropertyValue($potrero, 'id')) ?>">
                <?= esc(getPropertyValue($potrero, 'nombre')) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-potreroId" class="error-message">El campo es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="tipoAlimentoId">Tipo de Alimento *</label>
        <select id="tipoAlimentoId" name="tipoAlimentoId" class="campo-input">
          <option value="">-- Seleccioná un Tipo de Alimento --</option>
          <option value="1">Fardo</option>
          <option value="2">Silopack</option>
        </select>
        <div id="error-tipoAlimentoId" class="error-message">El tipo de alimento es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="alimentoId">Alimento *</label>
        <select id="alimentoId" name="alimentoId" class="campo-input">
          <option value="">-- Seleccioná un Alimento --</option>
          <?php if (is_array($alimentos)): ?>
            <?php foreach ($alimentos as $alimento): ?>
              <option value="<?= esc(getPropertyValue($alimento, 'id')) ?>">
                <?= esc(getPropertyValue($alimento, 'nombre')) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-alimentoId" class="error-message">El alimento es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="cantidad">Cantidad *</label>
        <input type="number" id="cantidad" name="cantidad" required min="1" />
        <div id="error-cantidad" class="error-message">La cantidad es obligatoria</div>
      </div>

      <div class="form-group" style="display:flex; gap:10px; align-items:center;">
        <button type="submit" id="submitBtn" class="btn-usuario">Registrar</button>

        <!-- <button type="button" id="abrirFiltros" class="btn-usuario">Filtrar</button>
        <div id="resumenFiltros" style="margin-left:auto; font-size:.9rem; color:#084a83;"></div> -->

        <button type="button" id="cancelarEdicion" class="btn-usuario" style="display:none; background:#888;">
          Cancelar edición
        </button>
      </div>
    </form>
  </div>

  <div class="form-container table">
    <h2>Ordenes Registradas</h2>
    <div class="table-wrapper">
      <table id="tablaOrdenPrincipal" class="table-modern" aria-label="Listado de Ordenes">
        <thead>
          <tr>
            <th>Potrero</th>
            <th>Tipo de Alimento</th>
            <th>Alimento</th>
            <th>Cantidad</th>
            <th>Fecha Creacion</th>
            <th>Hora Creacion</th>
            <th>Fecha Actualización</th>
            <th>Hora Actualizacion</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div id="confirmModal" class="modal">
    <div class="modal-content" style="max-width: 420px; text-align: center; padding: 25px 35px;">
      <h3>Confirmar eliminación</h3>
      <p id="confirmText">¿Seguro que deseas eliminar este registro de orden?</p>
      <div class="modal-actions">
        <button id="confirmYes" class="btn btn-danger">Eliminar</button>
        <button id="confirmNo" class="btn btn-cancel" style="background:#777; color:white">Cancelar</button>
      </div>
    </div>
  </div>

  <script src="../../javascript/orden.js"></script>
</body>

</html>