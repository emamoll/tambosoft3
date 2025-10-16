<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

require_once __DIR__ . '../../../../backend/controladores/alimentoController.php';

$controller = new AlimentoController();
$alimentos = $controller->obtenerAlimentos();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambosoft: alimentos</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="../../css/alimento.css">
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

  <!-- ===== Formulario ===== -->
  <div class="form-container form">
    <h2 id="form-title"><i class="fas fa-seedling"></i> Registrar Alimento</h2>

    <form id="alimentoForm" method="POST" novalidate>
      <input type="hidden" id="id" name="id" value="">
      <input type="hidden" id="accion" name="accion" value="registrar">

      <div class="form-group">
        <label for="nombre">Nombre del Alimento</label>
        <input type="text" id="nombre" name="nombre" required>
        <span class="error-message" id="error-nombre">El nombre es obligatorio</span>
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" id="submitBtn" class="btn-usuario">Registrar</button>
        <button type="button" id="cancelarEdicion" class="btn-usuario" style="display:none; background:#777;">
          Cancelar edición
        </button>
      </div>
    </form>
  </div>

  <!-- ===== Modal confirmación ===== -->
  <div id="confirmModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
      <h3>Confirmar eliminación</h3>
      <p id="confirmText">¿Seguro que deseas eliminar este alimento?</p>
      <div class="modal-actions">
        <button type="button" id="confirmYes" class="btn-usuario" style="background:#c0392b;">Eliminar</button>
        <button type="button" id="confirmNo" class="btn-usuario" style="background:#777;">Cancelar</button>
      </div>
    </div>
  </div>

  <!-- ===== Tabla ===== -->
  <div class="form-container table">
    <h2>ALimentos Registrados</h2>

    <div class="table-wrapper">
      <table class="table-modern">
        <thead>
          <tr>
            <th>Id</th>
            <th>Nombre</th>
            <th style="width:120px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($alimentos as $alimento): ?>
            <tr data-id="<?= htmlspecialchars($alimento->getId()) ?>"
              data-nombre="<?= htmlspecialchars($alimento->getNombre()) ?>">
              <td><?= htmlspecialchars($alimento->getId()) ?></td>
              <td><?= htmlspecialchars($alimento->getNombre()) ?></td>
              <td>
                <div class="table-actions">
                  <button type="button" class="btn-icon edit js-edit" title="Modificar" aria-label="Modificar">✏️</button>
                  <button type="button" class="btn-icon delete js-delete" title="Eliminar"
                    aria-label="Eliminar">🗑️</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($alimentos)): ?>
            <tr>
              <td colspan="3" style="text-align:center; color:#666;">No hay alimentos registrados</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../../javascript/alimento.js"></script>
</body>

</html>