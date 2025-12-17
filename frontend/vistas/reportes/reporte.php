<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId']) || $_SESSION['rolId'] != 1) {
  header('Location: ../usuario/login.php');
  exit;
}
require_once __DIR__ . '/../../../backend/controladores/stockController.php';
require_once __DIR__ . '/../../../backend/controladores/ordenController.php';

$stockCtrl = new StockController();
$ordenCtrl = new OrdenController();

// --- 1. DATOS STOCK ACTUAL ---
$datosStock = $stockCtrl->listarStockValorizado();
$totalStock = 0;
$labelsStock = [];
$valoresStock = [];
if (is_array($datosStock)) {
  foreach ($datosStock as $s) {
    $subtotal = (float) ($s['subtotalValor'] ?? 0);
    $totalStock += $subtotal;
    $labelsStock[] = ($s['tipoAlimentoNombre'] ?? '') . " - " . ($s['alimentoNombre'] ?? '');
    $valoresStock[] = $subtotal;
  }
}

// --- 2. DATOS CONSUMO (ANIMALES) ---
$datosConsumo = $ordenCtrl->obtenerConsumoValorizado();
$consumoPorCatAnimal = $ordenCtrl->obtenerConsumoPorCategoria();
$totalConsumo = 0;
$labelsAnimales = array_column($consumoPorCatAnimal, 'categoria');
$cantidadesAnimales = array_column($consumoPorCatAnimal, 'cantidad');
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambosoft: Reportes</title>
  <link rel="icon" href=".../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css" />
  <link rel="stylesheet" href="../../css/orden.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../../javascript/header.js"></script>
  <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> -->


<body class="bodyHome reporteria">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <div class="container mt-4">
    <h1 class="text-white mb-4">Reporte Económico</h1>

    <div class="section-container mb-5 p-4 border rounded shadow-sm bg-white">
      <div class="row mb-3 align-items-center">
        <div class="col-md-6">
          <h2 class="text-dark">Valorización de Stock Actual</h2>
        </div>
        <div class="col-md-6 text-end">
          <div class="card bg-success text-white d-inline-block p-2 px-4 border-0">
            <small>Inversión en Stock</small>
            <h3 class="mb-0">$<?= number_format($totalStock, 2, ',', '.') ?></h3>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-5"><canvas id="chartStock" height="300"></canvas></div>
        <div class="col-lg-7">
          <table class="table table-hover small">
            <thead class="table-dark">
              <tr>
                <th>Alimento (Producción)</th>
                <th class="text-end">P. Unit.</th>
                <th class="text-end">Cant.</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($datosStock as $s):
                $origen = ($s['produccionInterna'] == 1) ? 'Propia' : ($s['proveedorNombre'] ?? 'Prov'); ?>
                <tr>
                  <td><?= htmlspecialchars($s['tipoAlimentoNombre']) ?>
                    <?= htmlspecialchars($s['alimentoNombre']) ?> (<?= htmlspecialchars($origen) ?>)
                  </td>
                  <td class="text-end text-muted">$<?= number_format($s['precioUnitario'] ?? 0, 2, ',', '.') ?></td>
                  <td class="text-end"><?= $s['cantidadTotal'] ?></td>
                  <td class="text-end fw-bold text-success">$<?= number_format($s['subtotalValor'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="text-end mt-2">
          <a href="../../../backend/reportes/reporteEconomicoStock.php" target="_blank" class="btn btn-sm"
            style="background-color:#084a83; color:white">Exportar PDF Stock</a>
        </div>
      </div>
    </div>

    <hr class="text-white my-5">

    <div class="section-container mb-5 p-4 border rounded shadow-sm bg-white">
      <div class="row mb-3 align-items-center">
        <div class="col-md-6">
          <h2 class="text-dark">Valorización de Órdenes Entregadas</h2>
        </div>
        <div class="col-md-6 text-end">
          <?php if (is_array($datosConsumo))
            foreach ($datosConsumo as $c)
              $totalConsumo += (float) $c['subtotalConsumo']; ?>
          <div class="card bg-warning text-dark d-inline-block p-2 px-4 border-0">
            <small>Valor Total Consumido</small>
            <h3 class="mb-0">$<?= number_format($totalConsumo, 2, ',', '.') ?></h3>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-5"><canvas id="chartAnimales" height="300"></canvas></div>
        <div class="col-lg-7">
          <table class="table table-hover small">
            <thead class="table-dark">
              <tr>
                <th>Categoría</th>
                <th>Alimento (Producción)</th>
                <th class="text-end">P. Unit.</th>
                <th class="text-end">Cant.</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php if (is_array($datosConsumo)):
                foreach ($datosConsumo as $c):
                  $origenC = ($c['produccionInterna'] == 1) ? 'Propia' : ($c['proveedorNombre'] ?? 'Prov'); ?>
                  <tr>
                    <td><?= htmlspecialchars($c['categoriaAnimal'] ?? 'S/C') ?></td>
                    <td><?= htmlspecialchars($c['tipoAlimentoNombre'] ?? 'S/T') ?>
                      <?= htmlspecialchars($c['alimentoNombre']) ?> (<?= htmlspecialchars($origenC) ?>)
                    </td>
                    <td class="text-end text-muted">$<?= number_format($c['precioUnitario'] ?? 0, 2, ',', '.') ?></td>
                    <td class="text-end"><?= $c['cantidadTotal'] ?></td>
                    <td class="text-end fw-bold text-danger">$<?= number_format($c['subtotalConsumo'], 2, ',', '.') ?></td>
                  </tr>
                <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="text-end mt-2">
        <a href="../../../backend/reportes/reporteEconomicoOrden.php" target="_blank" class="btn btn-sm"
          style="background-color:#084a83; color:white">Exportar PDF Consumo</a>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      new Chart(document.getElementById('chartStock'), {
        type: 'bar',
        data: { labels: <?= json_encode($labelsStock) ?>, datasets: [{ label: 'Valor ($)', data: <?= json_encode($valoresStock) ?>, backgroundColor: 'rgba(25, 135, 84, 0.7)' }] },
        options: { responsive: true, maintainAspectRatio: false }
      });

      new Chart(document.getElementById('chartAnimales'), {
        type: 'bar',
        data: { labels: <?= json_encode($labelsAnimales) ?>, datasets: [{ label: 'Cantidad Consumida', data: <?= json_encode($cantidadesAnimales) ?>, backgroundColor: 'rgba(255, 159, 64, 0.7)' }] },
        options: {
          responsive: true, maintainAspectRatio: false,
          scales: { y: { beginAtZero: true }, x: { title: { display: true, text: 'Categorías de Animales' } } }
        }
      });
    });
  </script>
</body>

</html>