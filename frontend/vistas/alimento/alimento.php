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
        <label for="tipoAlimentoId">Tipo de Alimentos</label>
        <select id="tipoAlimentoId" name="tipoAlimentoId">
          <option value="">-- Seleccioná un tipo de Alimento --</option>
          <option value="1">Fardo</option>
          <option value="2">Silopack</option>
        </select>
        <span class="error-message" id="error-tipoAlimentoId">Seleccioná un tipo de alimento.</span>
      </div>

      <div class="form-group">
        <label for="nombre">Nombre del Alimento</label>
        <input type="text" id="nombre" name="nombre" required>
        <span class="error-message" id="error-nombre">El nombre es obligatorio</span>
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" id="submitBtn" class="btn-usuario">Registrar</button>

        <button type="button" id="abrirFiltros" class="btn-usuario">Filtrar</button>
        <div id="resumenFiltros" style="margin-left:auto; font-size:.9rem; color:#084a83;"></div>

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

  <!-- ===== TABLA DE Alimentos ===== -->
  <div class="form-container table">
    <h2>Alimentos Registrados</h2>
    <div class="table-wrapper">
      <table class="table-modern" aria-label="Listado de Alimentos">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tipo de Alimento</th>
            <th>Nombre</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <!-- ===== MODAL DE FILTROS (RADIOS) ===== -->
  <div id="filtroModal" class="modal">
    <div class="modal-content">
      <h3>Filtrar Alimentos</h3>

      <div class="filtro-grupo">
        <h4>Tipo de Alimento</h4>
        <div id="filtroTipoAlimentoGroup" class="radio-group"></div>
      </div>

      <div class="filtro-grupo">
        <h4>Nombre</h4>
        <div id="filtroNombreGroup" class="radio-group"></div>
      </div>

      <div class="modal-actions" style="display:flex; gap:10px;">
        <button id="aplicarFiltros" class="btn-usuario">Aplicar</button>
        <button id="limpiarFiltros" class="btn btn-secondary">Limpiar</button>
        <button id="cerrarFiltros" class="btn btn-cancel" style="background:#777; color:white">Cerrar</button>
      </div>
    </div>
  </div>

  <script src="../../javascript/alimento.js"></script>
</body>

</html>