<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId']) || $_SESSION['rolId'] != 1) {
  header('Location: ../usuario/login.php');
  exit;
}


require_once __DIR__ . '../../../../backend/controladores/pasturaController.php';

$controller = new PasturaController();
$pasturas = $controller->obtenerPasturas();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambosoft: Pasturas</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="../../css/pastura.css">
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
    <h2 id="form-title"><i class="fas fa-seedling"></i> Registrar Pastura</h2>

    <form id="pasturaForm" method="POST" novalidate>
      <input type="hidden" id="id" name="id" value="">
      <input type="hidden" id="accion" name="accion" value="registrar">

      <div class="form-group">
        <label for="nombre">Nombre de la Pastura</label>
        <input type="text" id="nombre" name="nombre" required>
        <span class="error-message" id="error-nombre">El nombre es obligatorio</span>
      </div>

      <div class="form-group">
        <label for="fechaSiembra">Fecha de Siembra</label>
        <input type="date" id="fechaSiembra" name="fechaSiembra" required>
        <span class="error-message" id="error-fechaSiembra">La fecha es obligatoria</span>
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
      <p id="confirmText">¿Seguro que deseas eliminar esta pastura?</p>
      <div class="modal-actions">
        <button type="button" id="confirmYes" class="btn-usuario" style="background:#c0392b;">Eliminar</button>
        <button type="button" id="confirmNo" class="btn-usuario" style="background:#777;">Cancelar</button>
      </div>
    </div>
  </div>

  <!-- ===== Tabla ===== -->
  <div class="form-container table">
    <h2>Pasturas Registradas</h2>

    <div class="table-wrapper">
      <table class="table-modern">
        <thead>
          <tr>
            <th>Id</th>
            <th>Nombre</th>
            <th>Fecha de Siembra</th>
            <th style="width:120px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pasturas as $pastura): ?>
            <tr data-id="<?= htmlspecialchars($pastura->getId()) ?>"
              data-nombre="<?= htmlspecialchars($pastura->getNombre()) ?>"
              data-fechaSiembra="<?= htmlspecialchars($pastura->getFechaSiembra()) ?>">
              <td><?= htmlspecialchars($pastura->getId()) ?></td>
              <td><?= htmlspecialchars($pastura->getNombre()) ?></td>
              <td><?= htmlspecialchars(date('d-m-Y', strtotime($pastura->getFechaSiembra()))) ?></td>
              <td>
                <div class="table-actions">
                  <button type="button" class="btn-icon edit js-edit" title="Modificar" aria-label="Modificar">✏️</button>
                  <button type="button" class="btn-icon delete js-delete" title="Eliminar"
                    aria-label="Eliminar">🗑️</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($pasturas)): ?>
            <tr>
              <td colspan="4" style="text-align:center; color:#666;">No hay pasturas registradas</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../../javascript/pastura.js"></script>
</body>

</html>