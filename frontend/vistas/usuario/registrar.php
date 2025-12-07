<?php
require_once __DIR__ . '../../../../backend/controladores/usuarioController.php';

// Si existe un error de conexión, se detiene antes de crear el controlador
if (isset($_GET['connError'])) {
  $connError = htmlspecialchars($_GET['connError']);
} else {
  $controller = new UsuarioController();
  $roles = $controller->obtenerRoles();
}

$mensaje = $_GET['mensaje'] ?? '';
$tipoMensaje = $_GET['tipo'] ?? '';
$nombreUsuario = $_SESSION['username'] ?? '';

?>
<!DOCTYPE html>
<html lang="es-ar">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambosoft: Registrar Usuario</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="../../css/usuario.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

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

<body>
  <?php if (isset($_SESSION['username'])): ?>
    <?php require_once __DIR__ . '../../secciones/header.php'; ?>
    <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>
  <?php endif; ?>

  <div class="form-container form-usuario">
    <h2>Registrar Nuevo Usuario</h2>

    <div id="system-message-container" style="margin-bottom: 15px;">
      <?php if (isset($connError)): ?>
        <div class="alert-error">
          Error de conexión: <?= htmlspecialchars($connError) ?>
        </div>
      <?php elseif (!empty($mensaje)): ?>
        <div class="alert-<?= htmlspecialchars($tipoMensaje) ?>">
          <?= htmlspecialchars($mensaje) ?>
        </div>
      <?php endif; ?>
    </div>

    <form id="registroForm" action="../../../backend/controladores/usuarioController.php" method="POST">
      <input type="hidden" name="accion" value="registrar">

      <div class="form-group">
        <label for="username">Nombre de Usuario *</label>
        <input type="text" id="username" name="username" required>
        <div id="error-username" class="error-message">El campo es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" required>
        <div id="error-email" class="error-message">El campo es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="password">Contraseña *</label>
        <input type="password" id="password" name="password" required>
        <div id="error-password" class="error-message">El campo es obligatorio</div>
      </div>

      <div class="form-group">
        <label for="rolId">Rol *</label>
        <select id="rolId" name="rolId" required>
          <option value="">-- Seleccioná un Rol --</option>
          <?php if (isset($roles)): ?>
            <?php foreach ($roles as $rol): ?>
              <option value="<?= htmlspecialchars($rol['id']) ?>">
                <?= htmlspecialchars($rol['nombre']) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-rolId" class="error-message">El campo es obligatorio</div>
      </div>

      <button type="submit" class="btn-usuario">Registrar</button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
  <script src="../../javascript/header.js"></script>
  <script src="../../javascript/usuario.js"></script>
</body>

</html>