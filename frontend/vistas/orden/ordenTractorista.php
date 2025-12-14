<?php

session_start();

// Validar sesión
if (!isset($_SESSION['usuarioId'])) {
  echo "<p style='color:red;'>Error: No se pudo obtener el ID del usuario. Inicie sesión nuevamente.</p>";
  exit;
}

// Validar rol Tractorista (Rol ID = 3)
if (!isset($_SESSION['rolId']) || $_SESSION['rolId'] != 3) {
  echo "<p style='color:red;'>Acceso denegado. Solo los tractoristas pueden acceder a esta sección.</p>";
  exit;
}

$usuarioId = (int) $_SESSION['usuarioId'];
$rolId = (int) $_SESSION['rolId'];


// 1. Cargar controladores necesarios
require_once __DIR__ . '../../../../backend/controladores/ordenController.php';

// 2. Instanciar controladores
$controllerOrden = new OrdenController();

// 3. Obtener datos (solo la tabla necesita datos)
$alimentos = $controllerOrden->obtenerTodosLosAlimentos(); // Se mantiene para compatibilidad con JS
$usuarioLogueadoId = $usuarioId;


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
  </style>

</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <div class="form-container table">
    <h2>Ordenes Pendientes / En Preparación</h2>
    <div id="system-message-container" style="margin-bottom: 15px;"></div>
    <div class="table-wrapper">
      <table id="tablaOrdenPrincipal" class="table-modern" aria-label="Listado de Ordenes">
        <thead>
          <tr>
            <th>Campo Origen</th>
            <th>Categoría (Potrero)</th>
            <th>Alimento</th>
            <th>Cantidad</th>
            <td class="estado">
              <span style="${estadoStyle}">Estado</span>
            </td>
            <th>Fecha y Hora</th>
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
      <h3>Confirmar acción</h3>
      <p id="confirmText">¿Seguro que deseas realizar esta acción?</p>
      <div class="modal-actions">
        <button id="confirmYes" class="btn btn-danger">Confirmar</button>
        <button id="confirmNo" class="btn btn-cancel" style="background:#777; color:white">Cancelar</button>
      </div>
    </div>
  </div>

  <div id="modalModificarOrden" class="modal">
    <div class="modal-content" style="max-width: 500px;">
      <h3>Modificar Orden</h3>

      <input type="hidden" id="modOrdenId">

      <div class="form-group">
        <label>Cantidad</label>
        <input type="number" id="modCantidad" class="form-control" min="1">
        <small class="error-message" id="error-modCantidad" style="display:none;color:red;">
          La cantidad debe ser mayor a 0
        </small>
      </div>
      <div class="form-group">
        <small id="info-stock" style="color:#555;"></small>
      </div>

      <div class="form-group">
        <label>Motivo de la modificación</label>
        <textarea id="modMotivo" class="form-control" rows="3"></textarea>
        <small class="error-message" id="error-modMotivo" style="display:none;color:red;">
          El motivo es obligatorio
        </small>
      </div>

      <div id="modal-error-stock" class="error-message" style="display:none; margin-bottom:10px;">
      </div>

      <div class="modal-actions" style="margin-top:15px;">
        <button id="btnConfirmarModificar" class="btn btn-primary">Guardar</button>
        <button id="btnCancelarModificar" class="btn btn-cancel">Cancelar</button>
      </div>
    </div>
  </div>

  <div id="modalCancelarOrden" class="modal">
    <div class="modal-content" style="max-width: 480px;">
      <h3>Cancelar Orden</h3>

      <input type="hidden" id="cancelOrdenId">

      <div class="form-group">
        <label>Motivo de la cancelación</label>
        <textarea id="cancelMotivo" class="form-control" rows="3"></textarea>
        <small class="error-message" id="error-cancelMotivo" style="display:none;color:red;">
          El motivo es obligatorio
        </small>
      </div>

      <div class="modal-actions" style="margin-top:15px;">
        <button id="btnConfirmarCancelar" class="btn btn-danger">Cancelar Orden</button>
        <button id="btnCerrarCancelar" class="btn btn-cancel">Volver</button>
      </div>
    </div>
  </div>


  <script>
    window.ALL_ALIMENTOS = <?= json_encode($alimentos) ?>;
    window.ROL_ID = <?= (int) $rolId ?>;
    window.USER_ID = <?= (int) $usuarioId ?>;
    window.ROL_TRACTORISTA = 3;
  </script>
  <script src="../../javascript/ordenTractorista.js"></script>
</body>

</html>