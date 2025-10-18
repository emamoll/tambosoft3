<?php
require_once __DIR__ . '../../../../backend/controladores/campoController.php';
require_once __DIR__ . '../../../../backend/controladores/categoriaController.php';
require_once __DIR__ . '../../../../backend/controladores/potreroController.php';
require_once __DIR__ . '../../../../backend/controladores/pasturaController.php';

session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId'])) {
  header('Location: ../usuario/login.php');
  exit;
}

$controllerPotrero = new PotreroController();
$controllerPastura = new PasturaController();
$controllerCategoria = new CategoriaController();
$controllerCampo = new CampoController();

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mensaje = $controllerPotrero->procesarFormularios();
}

$potreros = $controllerPotrero->obtenerPotreros();
$pasturas = $controllerPastura->obtenerPasturas();
$categorias = $controllerCategoria->obtenerCategorias();
$campos = $controllerCampo->obtenerCampos();

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
  <title>Tambosoft: Potreros</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="../../css/potrero.css">
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
    <h2 id="form-title"><i class="fas fa-seedling"></i> Registrar Potrero</h2>

    <form id="potreroForm" method="post" action="../../../backend/controladores/potreroController.php" novalidate>
      <!-- Hidden -->
      <input type="hidden" id="id" name="id" />
      <input type="hidden" id="accion" name="accion" value="registrar" />

      <div class="form-group">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" />
        <div id="error-nombre" class="error-message">Ingresá el nombre</div>
      </div>

      <div class="form-group">
        <label for="campoId">Campo</label>
        <select id="campoId" name="campoId" class="campo-input">
          <option value="">-- Seleccioná un campo --</option>
          <?php if (is_array($campos)): ?>
            <?php foreach ($campos as $campo): ?>
              <option value="<?= esc($campo->getId()) ?>"><?= esc($campo->getNombre()) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-campoId" class="error-message">Seleccioná el campo.</div>
      </div>

      <div class="form-group">
        <label for="pasturaId">Pastura</label>
        <select id="pasturaId" name="pasturaId" class="campo-input">
          <option value="">-- Seleccioná una pastura --</option>
          <?php if (is_array($pasturas)): ?>
            <?php foreach ($pasturas as $pastura): ?>
              <option value="<?= esc($pastura->getId()) ?>"><?= esc($pastura->getNombre()) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-pasturaId" class="error-message">Seleccioná la pastura</div>
      </div>

      <div class="form-group">
        <label for="categoriaId">Categoría</label>
        <select id="categoriaId" name="categoriaId" class="campo-input">
          <option value="">-- Seleccioná una categoría --</option>
          <?php if (is_array($categorias)): ?>
            <?php foreach ($categorias as $categoria): ?>
              <option value="<?= esc($categoria->getId()) ?>"><?= esc($categoria->getNombre()) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div id="error-categoriaId" class="error-message">Seleccioná la categoría</div>
      </div>

      <div class="form-group">
        <label for="cantidadCategoria">Cantidad de Categoría</label>
        <input type="number" id="cantidadCategoria" name="cantidadCategoria" />
        <div id="error-cantidadCategoria" class="error-message">Ingresá la cantidad.</div>
      </div>

      <!-- Botonera + Filtros -->
      <div class="form-group" style="display:flex; gap:10px; align-items:center;">
        <button type="submit" id="submitBtn" class="btn-usuario">Registrar</button>

        <!-- Nuevo: Botón Filtrar (abre modal) -->
        <button type="button" id="abrirFiltros" class="btn-usuario" style="background:#2d6ca2;">
          Filtrar
        </button>

        <!-- Resumen de filtros (se completa por JS) -->
        <div id="resumenFiltros" style="margin-left:auto; font-size:.9rem; color:#084a83;"></div>

        <button type="button" id="cancelarEdicion" class="btn-usuario" style="display:none; background:#888;">
          Cancelar edición
        </button>
      </div>
    </form>
  </div>

  <!-- ===== Tabla ===== -->
  <div class="form-container table">
    <h2>Potreros Registrados</h2>
    <div class="table-wrapper">
      <table class="table-modern" aria-label="Listado de Potreros">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Pastura</th>
            <th>Categoría</th>
            <th>Cant. Cat.</th>
            <th>Campo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ===== Modal de Filtros ===== -->
  <div id="filtroModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:520px; text-align:left;">
      <h3>Filtrar potreros</h3>

      <div class="form-group">
        <label for="filtroCampo">Campo</label>
        <!-- Se completa por JS clonando opciones de #campoId -->
        <select id="filtroCampo" class="campo-input"></select>
      </div>

      <div class="form-group">
        <label for="filtroPastura">Pastura</label>
        <!-- Se completa por JS clonando opciones de #pasturaId -->
        <select id="filtroPastura" class="campo-input"></select>
      </div>

      <div class="form-group">
        <label for="filtroCategoria">Categoría</label>
        <!-- Se completa por JS clonando opciones de #categoriaId y agregando la opción especial -->
        <select id="filtroCategoria" class="campo-input"></select>
        <small style="display:block; margin-top:6px; color:#555;">
          Opción especial: <em>Todas las categorías (sólo los que tienen)</em>.
        </small>
      </div>

      <div class="modal-actions" style="justify-content:flex-end;">
        <button type="button" id="limpiarFiltros" class="btn-usuario" style="background:#888;">Limpiar filtros</button>
        <button type="button" id="aplicarFiltros" class="btn-usuario">Aplicar</button>
        <button type="button" id="cerrarFiltros" class="btn-usuario" style="background:#c0392b;">Cerrar</button>
      </div>
    </div>
  </div>

  <!-- ===== Modal de confirmación ===== -->
  <div id="confirmModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
      <h3>Confirmar eliminación</h3>
      <p id="confirmText">¿Seguro que deseas eliminar este potrero?</p>
      <div class="modal-actions">
        <button id="confirmYes" class="btn-usuario" style="width:auto;">Sí, eliminar</button>
        <button id="confirmNo" class="btn-usuario" style="width:auto; background:#888;">Cancelar</button>
      </div>
    </div>
  </div>

  <!-- ===== Modal mover categoría ===== -->
  <div id="moverModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
      <h3>Mover categoría</h3>
      <p>Seleccioná a qué potrero querés mover la categoría:</p>
      <select id="potreroDestino" class="campo-input">
        <option value="">-- Seleccioná un potrero destino --</option>
      </select>
      <div class="modal-actions" style="margin-top:15px;">
        <button id="confirmMover" type="button" class="btn-usuario" style="width:auto;">Mover</button>
        <button id="cancelarMover" type="button" class="btn-usuario"
          style="width:auto; background:#aaa;">Cancelar</button>
      </div>
    </div>
  </div>

  <script src="../../javascript/potrero.js"></script>
</body>

</html>