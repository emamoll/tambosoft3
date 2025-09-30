<?php

session_start();
require_once __DIR__ . '../../../../backend/controladores/usuarioController.php';
// require_once __DIR__ . '../backend/controladores/alimentoController.php';
require_once __DIR__ . '../../../../backend/controladores/almacenController.php';
require_once __DIR__ . '../../../../backend/controladores/campoController.php';
// require_once __DIR__ . '../backend/controladores/categoriaController.php';
// require_once __DIR__ . '../backend/controladores/estadoController.php';
// require_once __DIR__ . '../backend/controladores/ordenController.php';
require_once __DIR__ . '../../../../backend/controladores/pasturaController.php';
// require_once __DIR__ . '../backend/controladores/potreroController.php';
// require_once __DIR__ . '../backend/controladores/stock_almacenController.php';
// require_once __DIR__ . '../backend/modelos//orden_cancelada/orden_canceladaTabla.php';

try {
  new UsuarioController();
  // new AlimentoController();
  new AlmacenController();
  new CampoController();
  // new CategoriaController();
  // new EstadoController();
  // new OrdenController();
  new PasturaController();
  // new PotreroController();
  // new Stock_almacenController();

  $db = DatabaseFactory::createDatabaseConnection('mysql');
  // $orden_canceladaCrearTabla = new Orden_canceladaCrearTabla($db);
  // $orden_canceladaCrearTabla->crearTablaOrdenes_canceladas();
  // No es necesario guardar las instancias en variables si solo es para ejecutar el constructor.
} catch (Exception $e) {
  error_log("Error al crear tablas: " . $e->getMessage());
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $controller = new UsuarioController();
  $usuario = $controller->loginUsuario($_POST['username'], $_POST['password']);

  if ($usuario) {
    $_SESSION['username'] = $usuario->getUsername();
    $_SESSION['rolId'] = $usuario->getRolId();
    $_SESSION['imagen'] = $usuario->getImagen();
    $_SESSION['token'] = $usuario->getToken();
    header('Location: ../usuario/index.php');
    exit;
  } else {
    $error = "Usuario o contraseña incorrectos.";
  }
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
  <script src="../../javascript/usuario.js"></script>
</head>

<body>
  <div class="form-container">
    <div class="logo-login"><img src="../../img/logo2.png" alt="Icono Tambosoft" class="logoIndex"></div>
    <h2>Iniciar Sesión</h2>
    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Usuario</label>
        <input type="text" id="username" name="username" required>
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password" required>
          <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
        </div>
      </div>
      <?php if (isset($error)): ?>
        <p class="error-message" style="display:block;"><?= $error ?></p>
      <?php endif; ?>
      <button type="submit" class="btn-usuario">Ingresar</button>
    </form>
    <script src="../../javascript/usuario.js"></script>
  </div>
</body>

</html>