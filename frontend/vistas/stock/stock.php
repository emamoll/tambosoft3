<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

// 1. Cargar controladores necesarios
require_once __DIR__ . '../../../../backend/controladores/stockController.php';
require_once __DIR__ . '../../../../backend/controladores/almacenController.php';
require_once __DIR__ . '../../../../backend/controladores/alimentoController.php';
require_once __DIR__ . '../../../../backend/controladores/proveedorController.php';

// 2. Instanciar controladores
$controllerStock = new StockController();
$controllerAlmacen = new AlmacenController();
$controllerAlimento = new AlimentoController();
$controllerProveedor = new ProveedorController();

// 3. Obtener datos para SELECTs y listado
$almacenes = $controllerAlmacen->obtenerAlmacenes();
$alimentos = $controllerAlimento->obtenerAlimentos();
$proveedores = $controllerProveedor->obtenerProveedores();
$stocks = $controllerStock->obtenerStock();

function esc($s)
{
  // Función de escape (asegura que los datos sean seguros para HTML)
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Helper para obtener datos si son objetos (Modelos) o arrays
function getPropertyValue($obj, $prop)
{
  if (is_object($obj) && method_exists($obj, 'get' . ucfirst($prop))) {
    return $obj->{'get' . ucfirst($prop)}();
  } elseif (is_array($obj) && isset($obj[$prop])) {
    return $obj[$prop];
  } elseif (is_object($obj) && isset($obj->$prop)) {
    return $obj->$prop;
  }
  return '';
}
?>

<!DOCTYPE html>
<html lang="es-ar">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambosoft: Stock</title>
  <link rel="icon" href="../../../img/logo2.png" type="image/png" />
  <link rel="stylesheet" href="../../css/estilos.css" />
  <link rel="stylesheet" href="../../css/stock.css" />
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
    <h2 id="form-title"><i class="fas fa-warehouse"></i> Registrar Stock</h2>

    <form id="stockForm" method="post" action="../../../backend/controladores/stockController.php" novalidate>
      <input type="hidden" id="id" name="id" />
      <input type="hidden" id="accion" name="accion" value="registrar" />
      <input type="hidden" id="produccionInternaValor" name="produccionInterna" value="0" />

      <div class="form-group">
        <label for="almacenId">Campo *</label>
        <select id="almacenId" name="almacenId" class="campo-input">
          <option value="">-- Seleccioná un Campo --</option>
          <?php if (is_array($almacenes)): ?>
            <?php foreach ($almacenes as $almacen): ?>
              <option value="<?= esc(getPropertyValue($almacen, 'id')) ?>">
                <?= esc(getPropertyValue($almacen, 'nombre')) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-almacenId" class="error-message">El campo es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="tipoAlimentoId">Tipo de Alimento *</label>
        <select id="tipoAlimentoId" name="tipoAlimentoId" class="campo-input">
          <option value="">-- Seleccioná un Tipo de Alimento --</option>
          <option value="1">Fardo</option>
          <option value="2">Silopack</option>
        </select>
        <div id="error-tipoAlimentoId" class="error-message">El alimento es obligatorio</div>
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

      <div class="form-group check-group">
        <label class="label-check">
          <input type="checkbox" id="produccionInternaCheck" value="1" />
          Producción Propia
        </label>
        <div id="error-produccionInterna" class="error-message">Debes indicar el origen (propio o proveedor)</div>
      </div>

      <div class="form-group" id="proveedorGroup">
        <label for="proveedorId">Proveedor</label>
        <select id="proveedorId" name="proveedorId" class="campo-input">
          <option value="">-- Seleccioná un Proveedor --</option>
          <?php if (is_array($proveedores)): ?>
            <?php foreach ($proveedores as $proveedor): ?>
              <option value="<?= esc(getPropertyValue($proveedor, 'id')) ?>">
                <?= esc(getPropertyValue($proveedor, 'denominacion')) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-proveedorId" class="error-message">El proveedor es obligatorio.</div>
      </div>

      <div class="form-group">
        <label for="precio">Precio $</label>
        <input type="number" step="0.01" id="precio" name="precio" />
        <div id="error-precio" class="error-message">El precio es obligatorio y debe ser un número.</div>
      </div>

      <div class="form-group">
        <label for="fechaIngreso">Fecha de Ingreso</label>
        <input type="date" id="fechaIngreso" name="fechaIngreso" value="<?= date('Y-m-d') ?>" />
        <div id="error-fecha" class="error-message">La fecha es obligatoria.</div>
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

  <div class="form-container table">
    <h2>Stock Registrado</h2>
    <div class="table-wrapper">
      <table class="table-modern" aria-label="Listado de Stock">
        <thead>
          <tr>
            <th>ID</th>
            <th>Campo</th>
            <th>Tipo de Alimento</th>
            <th>Alimento</th>
            <th>Cantidad</th>
            <th>Origen</th>
            <th>Proveedor</th>
            <th>Precio</th>
            <th>F. Ingreso</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div id="filtroModal" class="modal">
    <div class="modal-content">
      <h3>Filtrar Stock</h3>

      <div class="filtro-grupo">
        <h4>Campo</h4>
        <div id="filtroAlmacenGroup" class="radio-group">
          <?php if (is_array($almacenes)): ?>
            <?php foreach ($almacenes as $almacen): ?>
              <label class="radio-card">
                <input type="checkbox" name="filtro_almacenId" value="<?= esc(getPropertyValue($almacen, 'id')) ?>" />
                <span class="radio-label"><?= esc(getPropertyValue($almacen, 'nombre')) ?></span>
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="filtro-grupo">
        <h4>Tipo de Alimento</h4>
        <div id="filtroTipoAlimentoGroup" class="radio-group"></div>
      </div>

      <div class="filtro-grupo">
        <h4>Alimento</h4>
        <div id="filtroAlimentoGroup" class="radio-group"></div>
      </div>


      <div class="filtro-grupo">
        <h4>Origen</h4>
        <div id="filtroProduccionInternaGroup" class="radio-group">
          <label class="radio-card">
            <input type="checkbox" name="filtro_produccionInterna" value="1" />
            <span class="radio-label">Producción Propia</span>
          </label>
          <label class="radio-card">
            <input type="checkbox" name="filtro_produccionInterna" value="0" />
            <span class="radio-label">Comprado</span>
          </label>
        </div>
      </div>

      <div class="filtro-grupo">
        <h4>Proveedor</h4>
        <div id="filtroProveedorGroup" class="radio-group">
        </div>
      </div>

      <div class="modal-actions" style="display:flex; gap:10px;">
        <button id="aplicarFiltros" class="btn-usuario">Aplicar</button>
        <button id="limpiarFiltros" class="btn btn-secondary">Limpiar</button>
        <button id="cerrarFiltros" class="btn btn-cancel" style="background:#777; color:white">Cerrar</button>
      </div>
    </div>
  </div>

  <div id="confirmModal" class="modal">
    <div class="modal-content" style="max-width: 420px; text-align: center; padding: 25px 35px;">
      <h3>Confirmar eliminación</h3>
      <p id="confirmText">¿Seguro que deseas eliminar este registro de stock?</p>
      <div class="modal-actions">
        <button id="confirmYes" class="btn btn-danger">Eliminar</button>
        <button id="confirmNo" class="btn btn-cancel" style="background:#777; color:white">Cancelar</button>
      </div>
    </div>
  </div>

  <script src="../../javascript/stock.js"></script>
</body>

</html>