<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

require_once __DIR__ . '../../../../backend/controladores/campoController.php';

$controller = new CampoController();
$campos = $controller->obtenerCampos();

$modoEdicion = isset($_GET['accion']) && $_GET['accion'] === 'editar';
$campoEditar = null;

if ($modoEdicion && isset($_GET['id'])) {
  $campoEditar = $campoDAO->getCampoById($_GET['id']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambosoft: Campos</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="../../css/campo.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>
  <script src="../../javascript/campo.js"></script>
  <script src="../../javascript/header.js"></script>
</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <!-- ===== Formulario ===== -->
  <div class="form-container form">
    <h2 id="form-title"><i class="fas fa-map"></i>Registrar Campo</h2>

    <?php if (!empty($mensaje)): ?>
      <div class="alert <?= $mensaje['tipo'] === 'success' ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($mensaje['mensaje']) ?>
      </div>
    <?php endif; ?>

    <form id="campoForm" method="POST" action="" novalidate>
      <input type="hidden" id="id" name="id" value="">
      <input type="hidden" id="accion" name="accion" value="registrar">

      <div class="form-group">
        <label for="nombre">Nombre del campo</label>
        <input type="text" id="nombre" name="nombre" required>
        <span class="error-message" id="error-nombre">El nombre es obligatorio.</span>
      </div>

      <div class="form-group">
        <label for="ubicacion">Ubicación</label>
        <input type="text" id="ubicacion" name="ubicacion" required>
        <span class="error-message" id="error-ubicacion">La ubicación es obligatoria.</span>
      </div>

      <div class="form-group">
        <label for="superficie">Superficie (ha)</label>
        <input type="number" id="superficie" name="superficie" min="1" step="1" required>
        <span class="error-message" id="error-superficie">Ingresá un número entero mayor a 0.</span>
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" id="submitBtn" class="btn-usuario">Registrar</button>
        <button type="button" id="cancelarEdicion" class="btn-usuario" style="display:none; background:#777;">
          Cancelar edición
        </button>
      </div>
    </form>
  </div>

  <div id="confirmModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
      <h3>Confirmar eliminación</h3>
      <p id="confirmText">¿Seguro que deseas eliminar este campo?</p>
      <div class="modal-actions">
        <button type="button" id="confirmYes" class="btn-usuario" style="background:#c0392b;">Eliminar</button>
        <button type="button" id="confirmNo" class="btn-usuario" style="background:#777;">Cancelar</button>
      </div>
    </div>
  </div>

  <!-- ===== Tabla ===== -->
  <div class="form-container table">
    <h2>Campos Registrados</h2>

    <div class="table-wrapper">
      <table class="table-modern">
        <thead>
          <tr>
            <th>Id</th>
            <th>Nombre</th>
            <th>Ubicación</th>
            <th>Superficie (ha)</th>
            <th style="width:120px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($campos as $campo): ?>
            <tr data-id="<?= htmlspecialchars($campo->getId()) ?>"
              data-nombre="<?= htmlspecialchars($campo->getNombre()) ?>"
              data-ubicacion="<?= htmlspecialchars($campo->getUbicacion()) ?>"
              data-superficie="<?= htmlspecialchars($campo->getSuperficie()) ?>">
              <td><?= htmlspecialchars($campo->getId()) ?></td>
              <td><?= htmlspecialchars($campo->getNombre()) ?></td>
              <td><?= htmlspecialchars($campo->getUbicacion()) ?></td>
              <td><?= htmlspecialchars($campo->getSuperficie()) ?></td>
              <td>
                <div class="table-actions">
                  <button type="button" class="btn-icon edit js-edit" title="Modificar" aria-label="Modificar">✏️</button>
                  <button type="button" class="btn-icon delete js-delete" title="Eliminar"
                    aria-label="Eliminar">🗑️</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($campos)): ?>
            <tr>
              <td colspan="5" style="text-align:center; color:#666;">No hay campos registrados.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <form id="deleteForm" method="POST" action="" style="display:none;">
      <input type="hidden" name="accion" value="eliminar">
      <input type="hidden" name="id" id="deleteId" value="">
    </form>
  </div>

  <script src="../../javascript/campo.js"></script>
</body>

</html>