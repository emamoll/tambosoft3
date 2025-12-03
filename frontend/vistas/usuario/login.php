<?php

session_start();
// Solo requerimos el controlador de usuario por ahora
require_once __DIR__ . '../../../../backend/controladores/usuarioController.php';

$error = "";

// 1. Manejar el POST de LOGIN inmediatamente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $controller = new UsuarioController();

  // Llama directamente al método de login
  $usuario = $controller->loginUsuario($username, $password);

  if ($usuario) {
    // Si el login es exitoso, establece la sesión y redirige
    $_SESSION['username'] = $usuario->getUsername();
    $_SESSION['rolId'] = $usuario->getRolId();
    $_SESSION['imagen'] = $usuario->getImagen();
    $_SESSION['token'] = $usuario->getToken();
    header('Location: ../usuario/index.php');
    exit;
  } else {
    // Si el login falla, establece el mensaje de error
    $error = "Usuario o contraseña incorrectos.";
  }
}

// 2. Lógica de Creación de Tablas y Requisitos de Controladores (Solo corre si no hubo redirección)
require_once __DIR__ . '../../../../backend/controladores/alimentoController.php';
require_once __DIR__ . '../../../../backend/controladores/almacenController.php';
require_once __DIR__ . '../../../../backend/controladores/campoController.php';
require_once __DIR__ . '../../../../backend/controladores/categoriaController.php';
require_once __DIR__ . '../../../../backend/controladores/potreroController.php';
require_once __DIR__ . '../../../../backend/controladores/pasturaController.php';
require_once __DIR__ . '../../../../backend/controladores/stockController.php';
require_once __DIR__ . '../../../../backend/servicios/databaseFactory.php'; // Necesario para DatabaseFactory::createDatabaseConnection

try {
  // Instanciar controladores para forzar la creación de tablas (si sus constructores lo hacen)
  // Nota: Estas instanciaciones no deberían tener side effects de procesamiento HTTP.
  new UsuarioController();
  new AlimentoController();
  new AlmacenController();
  new CampoController();
  new CategoriaController();
  new PotreroController();
  new PasturaController();
   new StockController();

  $db = DatabaseFactory::createDatabaseConnection('mysql');
  // ... cualquier otra lógica de configuración de tablas
} catch (Exception $e) {
  error_log("Error al crear tablas: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambosoft: Iniciar sesión</title>
  <link rel="icon" href="../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css" />
  <link rel="stylesheet" href="../../css/usuario.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
    crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <div class="form-container">
    <div class="logo-login"><img src="../../img/logo2.png" alt="Icono Tambosoft" class="logoIndex"></div>
    <h2>Iniciar Sesión</h2>
    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Usuario</label>
        <input type="text" id="username" name="username" class="form-control" required autocomplete="username" />
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password" class="form-control" required
            autocomplete="current-password" />
          <span class="toggle-password">
            <i class="fa-solid fa-eye" id="togglePassword"></i>
          </span>
        </div>
      </div>
      <?php if (isset($error) && $error !== ""): ?>
        <p class="error-message" style="display:block;"><?= $error ?></p>
      <?php endif; ?>
      <button type="submit" class="btn-usuario">Ingresar</button>
    </form>
    <script src="../../javascript/usuario.js?v=<?php echo time(); ?>"></script>
  </div>
</body>

</html>