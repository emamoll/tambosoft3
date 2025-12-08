<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

$rolId = $_SESSION['rolId'] ?? 0;
// Redirigir si no es Tractorista (Rol ID 3) - Mesura de seguretat
if ($rolId != 3) {
  header('Location: orden.php');
  exit;
}

// 1. Cargar controladores necesarios
require_once __DIR__ . '../../../../backend/controladores/ordenController.php';

// 2. Instanciar controladores
$controllerOrden = new OrdenController();

// 3. Obtener datos (solo la tabla necesita datos)
$alimentos = $controllerOrden->obtenerTodosLosAlimentos(); // Se mantiene para compatibilidad con JS
$usuarioLogueadoId = $_SESSION['usuarioId'] ?? ($_SESSION['id'] ?? 0); // OBTENEMOS EL ID DEL USUARIO LOGUEADO

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

  <script>
    // ** Pasar los datos del backend a JS **
    const ALL_ALIMENTOS = <?= json_encode($alimentos) ?>;
    const ROL_ID = <?= $rolId ?>;
    const USER_ID = <?= $usuarioLogueadoId ?>; // <--- ID DEL USUARIO AÑADIDO Y DISPONIBLE GLOBALMENTE
    const ROL_TRACTORISTA = 3;
    // Se agregan variables vacías para evitar errores de referencia en el JS
    const categoriaId = null;
  </script>

  <div class="form-container table">
    <h2>Ordenes Pendientes / En Preparación</h2>
    <div id="system-message-container" style="margin-bottom: 15px;"></div>
    <div class="table-wrapper">
      <table id="tablaOrdenPrincipal" class="table-modern" aria-label="Listado de Ordenes">
        <thead>
          <tr>
            <th>Categoría (Potrero)</th>
            <th>Almacén</th>
            <th>Alimento (Tipo)</th>
            <th>Cantidad</th>
            <th>Tractorista</th>
            <th>Estado</th>
            <th>Fecha Creacion (dd/mm/yy)</th>
            <th>Hora Creacion (HH:mm)</th>
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

  <script src="../../javascript/ordenTractorista.js"></script>
</body>

</html>