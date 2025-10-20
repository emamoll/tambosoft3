<?php
require_once __DIR__ . '../../../../backend/controladores/proveedorController.php';

session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

$controllerProveedor = new ProveedorController();

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mensaje = $controllerProveedor->procesarFormularios();
}

$proveedores = $controllerProveedor->obtenerProveedores();

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
  <title>Tambosoft: Proveedores</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="../../css/proveedor.css">
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
    <h2 id="form-title"><i class="fas fa-seedling"></i> Registrar Proveedor</h2>

    <form id="proveedorForm" method="post" action="../../../backend/controladores/proveedorController.php" novalidate>
      <!-- Hidden -->
      <input type="hidden" id="id" name="id" />
      <input type="hidden" id="accion" name="accion" value="registrar" />

      <div class="form-group">
        <label for="denominacion">Denominación</label>
        <input type="text" id="denominacion" name="denominacion" />
        <div id="error-denominacion" class="error-message">Ingresá la denominación</div>
      </div>

      <div class="form-group">
        <label for="emailP">Email</label>
        <input type="emailP" id="emailP" name="emailP">
        <span class="error-message" id="error-emailP">Ingrese un correo válido.</span>
      </div>

      <div class="form-group">
        <label for="telefono">Teléfono</label>
        <input type="number" id="telefono" name="telefono" />
        <div id="error-telefono" class="error-message">Ingresá el teléfono.</div>
      </div>

      <div class="form-group" style="display:flex; gap:10px;">
        <button type="submit" id="submitBtn" class="btn-usuario">Registrar</button>
        <button type="button" id="cancelarEdicion" class="btn-usuario" style="display:none; background:#888;">Cancelar
          edición</button>
      </div>
    </form>
  </div>

  <!-- ===== Tabla ===== -->
  <div class="form-container table">
    <h2>Proveedores Registrados</h2>
    <div class="table-wrapper">
      <table class="table-modern" aria-label="Listado de Proveedores">
        <thead>
          <tr>
            <th>ID</th>
            <th>Denominación</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ===== Modal de confirmación ===== -->
  <div id="confirmModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
      <h3>Confirmar eliminación</h3>
      <p id="confirmText">¿Seguro que deseas eliminar este proveedor?</p>
      <div class="modal-actions">
        <button type="button" id="confirmYes" class="btn-usuario" style="background:#c0392b;">Eliminar</button>
        <button type="button" id="confirmNo" class="btn-usuario" style="background:#777;">Cancelar</button>
      </div>
    </div>
  </div>

  <script src="../../javascript/proveedor.js"></script>
</body>

</html>