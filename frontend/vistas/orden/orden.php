<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

// 1. Cargar controladores necesarios
require_once __DIR__ . '../../../../backend/controladores/ordenController.php';

// 2. Instanciar controladores
$controllerOrden = new OrdenController();

// 3. Obtener datos para SELECTs y listado
// $ordenes = $controllerOrden->obtenerOrden(); // Se obtendrá por AJAX en JS
$categorias = $controllerOrden->obtenerCategoriasConPotrero(); // NUEVO: Obtener categorías con potrero asignado
$almacenes = $controllerOrden->obtenerTodosLosAlmacenes();
$tiposAlimentos = $controllerOrden->obtenerTiposAlimentos();
$alimentos = $controllerOrden->obtenerTodosLosAlimentos(); // Se mantiene para compatibilidad con JS
$tractoristas = $controllerOrden->obtenerTractoristas();
$usuarioLogueadoId = $_SESSION['usuarioId'] ?? 0;

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

  <style>
    .alert-success {
      padding: 10px;
      margin: 10px 0;
      border-radius: 4px;
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      padding: 10px;
      margin: 10px 0;
      border-radius: 4px;
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .error-message {
      /* Estilo base para los mensajes de error */
      color: #721c24;
      font-size: 0.85em;
      margin-top: 5px;
    }

    #potreroAsignadoDisplay {
      font-size: 0.85em;
      margin-top: 5px;
      color: #084a83;
      font-weight: 500;
    }
  </style>

</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <div class="form-container form">
    <h2 id="form-title">Registrar Orden</h2>

    <div id="system-message-container" style="margin-bottom: 15px;"></div>

    <form id="ordenForm" method="post" action="../../../backend/controladores/ordenController.php" novalidate>
      <input type="hidden" id="id" name="id" />
      <input type="hidden" id="accion" name="accion" value="registrar" />

      <div class="form-group">
        <label for="almacenId">Campo de Origen *</label>
        <select id="almacenId" name="almacenId" class="campo-input">
          <option value="">-- Seleccioná un Campo --</option>
          <?php if (is_array($almacenes)): ?>
            <?php foreach ($almacenes as $almacen): ?>
              <option value="<?= esc($almacen['id']) ?>">
                <?= esc($almacen['nombre']) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-almacenId" class="error-message" style="display:none;">El almacén de origen es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="categoriaId">Categoría *</label>
        <select id="categoriaId" name="categoriaId" class="campo-input">
          <option value="">-- Seleccioná una Categoría --</option>
          <?php if (is_array($categorias)): ?>
            <?php foreach ($categorias as $categoria): ?>
              <option value="<?= esc($categoria['id']) ?>" data-potrero="<?= esc($categoria['potreroNombre']) ?>">
                <?= esc($categoria['nombre']) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="potreroAsignadoDisplay"></div>
        <div id="error-categoriaId" class="error-message" style="display:none;">La categoría es obligatoria</div>
        <input type="hidden" id="potreroId" name="potreroId" />
      </div>

      <div class="form-group">
        <label for="tipoAlimentoId">Tipo de Alimento *</label>
        <select id="tipoAlimentoId" name="tipoAlimentoId" class="campo-input">
          <option value="">-- Seleccioná un Tipo de Alimento --</option>
          <?php if (is_array($tiposAlimentos)): ?>
            <?php foreach ($tiposAlimentos as $tipo): ?>
              <option value="<?= esc($tipo['id']) ?>">
                <?= esc($tipo['tipoAlimento']) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-tipoAlimentoId" class="error-message" style="display:none;">El tipo de alimento es obligatorio
        </div>
      </div>

      <div class="form-group">
        <label for="alimentoId">Alimento *</label>
        <div style="display: flex; gap: 10px; align-items: center;">
          <select id="alimentoId" name="alimentoId" class="campo-input" style="flex-grow: 1;">
            <option value="">-- Seleccioná un Alimento --</option>
          </select>
          <span id="stockDisplay" style="font-size: 0.9em; font-weight: bold; color: #084a83;">Stock: -</span>
        </div>
        <div id="error-alimentoId" class="error-message" style="display:none;">El alimento es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="cantidad">Cantidad *</label>
        <input type="number" id="cantidad" name="cantidad" required min="1" />
        <div id="error-cantidad" class="error-message" style="display:none;">La cantidad es obligatoria</div>
        <div id="error-stock-insuficiente" class="error-message" style="display:none; color:red;">Stock insuficiente.
        </div>
      </div>

      <div class="form-group">
        <label for="usuarioId">Tractorista *</label>
        <select id="usuarioId" name="usuarioId" class="campo-input">
          <option value="">-- Seleccioná un Tractorista --</option>
          <?php if (is_array($tractoristas)): ?>
            <?php foreach ($tractoristas as $user): ?>
              <option value="<?= esc($user['id']) ?>" <?= $usuarioLogueadoId == $user['id'] ? 'selected' : '' ?>>
                <?= esc($user['username']) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-usuarioId" class="error-message" style="display:none;">El tractorista es obligatorio</div>
      </div>

      <div class="form-group" id="grupoMotivo" style="display:none;">
        <label for="motivo">Motivo de la modificación *</label>
        <textarea id="motivo" name="motivo" class="campo-input" rows="3"
          placeholder="Ej: Corrección de cantidad por error de carga"></textarea>
        <div id="error-motivo" class="error-message" style="display:none;">
          El motivo es obligatorio
        </div>
      </div>

      <div class="form-group" style="display:flex; gap:10px; align-items:center;">
        <button type="submit" id="submitBtn" class="btn-usuario">Registrar</button>

        <button type="button" id="cancelarEdicion" class="btn-usuario" style="display:none; background:#888;">
          Cancelar edición
        </button>
      </div>
    </form>
  </div>

  <script>
    // ** Pasar los datos del backend a JS **
    const ALL_ALIMENTOS = <?= json_encode($alimentos) ?>;
  </script>

  <div class="form-container table">
    <h2>Ordenes Registradas</h2>
    <div class="table-wrapper">
      <table id="tablaOrdenPrincipal" class="table-modern" aria-label="Listado de Ordenes">
        <thead>
          <tr>
            <th>Campo Origen</th>
            <th>Categoría (Potrero)</th>
            <th>Alimento</th>
            <th>Cantidad</th>
            <th>Tractorista</th>
            <th class="estado">Estado</th>
            <th class="fecha">Fecha y Hora</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
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

  <div id="modalAuditoriaOrden" class="modal">
    <div class="modal-content" style="max-width:800px;">
      <h3>Historial de modificaciones</h3>

      <table class="table-modern" style="margin-top:15px;">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Usuario</th>
            <th>Cantidad anterior</th>
            <th>Cantidad nueva</th>
            <th>Motivo</th>
          </tr>
        </thead>
        <tbody id="auditoriaBody">
          <tr>
            <td colspan="5" style="text-align:center;">Cargando...</td>
          </tr>
        </tbody>
      </table>

      <div class="modal-actions" style="margin-top:15px;">
        <button id="btnCerrarAuditoria" class="btn btn-cancel">
          Cerrar
        </button>
      </div>
    </div>
  </div>

  <script src="../../javascript/orden.js"></script>
</body>

</html>