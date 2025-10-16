<?php
require_once __DIR__ . '../../../../backend/controladores/usuarioController.php';

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
  header("Location:  index.php");
  exit();
}

// Verificar si es administrador
if ($_SESSION['rolId'] != 1) {
  header("Location: index.php");
  exit();
}

$mensaje = "";
$mensajeExito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confPassword = $_POST['confirmar'];
  $rolId = isset($_POST['rolId']) ? (int) $_POST['rolId'] : 0;

  // Manejo de imagen (campo obligatorio)
  $imagen = null;
  if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $dir = __DIR__ . "../../../img/";
    if (!is_dir($dir)) {
      @mkdir($dir, 0777, true);
    }

    // usamos directamente el nombre original
    $nombreArchivo = basename($_FILES['imagen']['name']);
    $rutaDestino = $dir . $nombreArchivo;

    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
      $imagen = $nombreArchivo; 
    }
  }
  $controller = new UsuarioController();

  if (!empty($username) && !empty($email) && !empty($password) && !empty($confPassword) && !empty($rolId) && !empty($imagen)) {
    // 🔹 Validación unificada
    if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/', $password)) {
      $mensaje = "La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.";
    } elseif ($password !== $confPassword) {
      $mensaje = "Las contraseñas no coinciden.";
    } elseif ($rolId === 0) {
      $mensaje = "Debe seleccionar un rol válido.";
    } else {
      $token = bin2hex(random_bytes(32));
      $respuesta = $controller->registrarUsuario($username, $email, $password, $rolId, $imagen, $token);
      if (is_array($respuesta) && isset($respuesta['success']) && $respuesta['success'] === true) {
        $mensajeExito = true;
        $_POST = [];
      } elseif (is_array($respuesta) && isset($respuesta['message'])) {
        $mensaje = $respuesta['message'];
      } else {
        $mensaje = "Error inesperado al registrar el usuario.";
      }
    }
  } else {
    $mensaje = "Todos los campos son obligatorios (incluida la imagen).";
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Tambosoft: Registrar usuario</title>
  <link rel="icon" href="../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="../../css/usuario.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>
  <script src="../../javascript/header.js"></script>
  <script src="../../javascript/usuario.js"></script>
</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <div class="form-container">
    <h2>Registrar Usuario</h2>

    <?php if ($mensajeExito): ?>
      <div class="mensaje-exito">Usuario registrado correctamente.</div>
    <?php elseif (!empty($mensaje)): ?>
      <div class="mensaje-error"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form id="registroForm" action="" method="POST" enctype="multipart/form-data" novalidate>
      <!-- Username -->
      <div class="form-group">
        <label for="username">Usuario</label>
        <input type="text" id="username" name="username">
        <span class="error-message" id="error-username">El usuario es obligatorio.</span>
      </div>

      <!-- Email -->
      <div class="form-group">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email">
        <span class="error-message" id="error-email">Ingrese un correo válido.</span>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password">
        <span class="error-message" id="error-password">
          La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.
        </span>
      </div>

      <!-- Confirmar -->
      <div class="form-group">
        <label for="confirmar">Confirmar Contraseña</label>
        <input type="password" id="confirmar" name="confirmar">
        <span class="error-message" id="error-confirmar">Las contraseñas no coinciden.</span>
      </div>

      <!-- Rol -->
      <div class="form-group">
        <label for="rolId">Rol</label>
        <select id="rolId" name="rolId">
          <option value="">-- Seleccioná un rol --</option>
          <option value="1">Administrador</option>
          <option value="2">Gerente</option>
          <option value="3">Tractorista</option>
          <option value="4">Administrador de Campos</option>
          <option value="5">Administrador de Usuarios</option>
          <option value="6">Administrador de Alimentos</option>
        </select>
        <span class="error-message" id="error-rol">Seleccioná un rol.</span>
      </div>

      <!-- Imagen obligatoria -->
      <div class="form-group">
        <label for="imagen">Imagen</label>
        <input type="file" id="imagen" name="imagen" accept="image/*" required>
        <span class="error-message" id="error-imagen">Debe seleccionar una imagen.</span>
      </div>

      <button type="submit" class="btn-usuario">Registrar</button>
    </form>
  </div>
</body>

</html>