<?php
session_start();
if (!isset($_SESSION['username'])) {
  header('Location: login.php');
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

// --- 2. DATOS CONSUMO ANIMALES ---
$datosConsumo = $ordenCtrl->obtenerConsumoValorizado();
$consumoPorCatAnimal = $ordenCtrl->obtenerConsumoPorCategoria();
$totalConsumo = 0;
if (is_array($datosConsumo)) {
  foreach ($datosConsumo as $c) {
    $totalConsumo += (float) ($c['subtotalConsumo'] ?? 0);
  }
}
$labelsAnimales = array_column($consumoPorCatAnimal, 'categoria');
$cantidadesAnimales = array_column($consumoPorCatAnimal, 'cantidad');

// --- 3. DATOS ESTADOS ---
require_once __DIR__ . '/../../../backend/DAOS/ordenDAO.php';
$ordenDAO = new OrdenDAO();
$todasLasOrdenes = $ordenDAO->listarOrdenes();

$conteoEstados = [];
$coloresEstados = [];

if (is_array($todasLasOrdenes)) {
  foreach ($todasLasOrdenes as $o) {
    $estado = $o['estadoDescripcion'] ?? 'Sin Estado';
    $color = $o['estadoColor'] ?? '#6c757d';

    if (!isset($conteoEstados[$estado])) {
      $conteoEstados[$estado] = 0;
      $coloresEstados[$estado] = $color;
    }
    $conteoEstados[$estado]++;
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambosoft: Home</title>
  <link rel="icon" href="../../../../img/logo2.png" type="image/png">
  <link rel="stylesheet" href="../../css/estilos.css" />
  <link rel="stylesheet" href="../../css/usuario.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../../javascript/header.js"></script>
  <style>
    /* Estilo para que las secciones parezcan botones clicables */
    .card-clickable {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      cursor: pointer;
    }

    .card-clickable:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }

    .dashboard-link {
      text-decoration: none;
      color: inherit;
      display: block;
    }
  </style>
</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <main class="container mt-3">
    <h2 class="text-white mb-3" style="font-size: 1.5rem;">Panel de Control</h2>

    <div class="row mb-3">
      <div class="col-md-6 mb-2">
        <a href="../reportes/reporte.php" class="dashboard-link">
          <div class="card shadow-sm border-0 bg-white p-3 rounded card-clickable" style="height: 220px;">
            <h6 class="text-center text-dark fw-bold mb-1">Distribución de Órdenes</h6>
            <div style="position: relative; height: 160px; width: 100%;">
              <canvas id="chartPieEstados"></canvas>
            </div>
          </div>
        </a>
      </div>

      <div class="col-md-6 mb-2">
        <a href="../reportes/reporte.php" class="dashboard-link">
          <div class="card shadow-sm border-0 bg-white p-3 rounded card-clickable" style="height: 220px;">
            <h6 class="text-center text-dark fw-bold mb-4">Resumen Económico</h6>
            <div class="row g-2 align-items-center h-75 pb-2">
              <div class="col-6">
                <div class="card bg-success text-white border-0 shadow-sm p-3 text-center">
                  <small style="font-size: 0.65rem;">INVERSIÓN STOCK</small>
                  <h4 class="mb-0" style="font-size: 1.2rem;">$<?= number_format($totalStock, 0, ',', '.') ?></h4>
                </div>
              </div>
              <div class="col-6">
                <div class="card bg-warning text-dark border-0 shadow-sm p-3 text-center">
                  <small style="font-size: 0.65rem;">CONSUMO TOTAL</small>
                  <h4 class="mb-0" style="font-size: 1.2rem;">$<?= number_format($totalConsumo, 0, ',', '.') ?></h4>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <a href="../reportes/reporte.php" class="dashboard-link">
          <div class="card shadow-sm border-0 bg-white p-3 rounded card-clickable" style="height: 300px;">
            <h6 class="text-center text-dark fw-bold mb-2">Valorización de Stock</h6>
            <canvas id="chartStockIndex"></canvas>
          </div>
        </a>
      </div>

      <div class="col-md-6 mb-3">
        <a href="../reportes/reporte.php" class="dashboard-link">
          <div class="card shadow-sm border-0 bg-white p-3 rounded card-clickable" style="height: 300px;">
            <h6 class="text-center text-dark fw-bold mb-2">Consumo por Animal</h6>
            <canvas id="chartAnimalesIndex"></canvas>
          </div>
        </a>
      </div>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const commonOptions = { responsive: true, maintainAspectRatio: false };

      // 1. Torta Estados
      new Chart(document.getElementById('chartPieEstados'), {
        type: 'pie',
        data: {
          labels: <?= json_encode(array_keys($conteoEstados)) ?>,
          datasets: [{
            data: <?= json_encode(array_values($conteoEstados)) ?>,
            backgroundColor: <?= json_encode(array_values($coloresEstados)) ?>,
            borderWidth: 2
          }]
        },
        options: {
          ...commonOptions,
          plugins: {
            legend: {
              position: 'bottom',
              labels: { boxWidth: 12, font: { size: 10 } }
            }
          }
        }
      });

      // 2. Stock
      new Chart(document.getElementById('chartStockIndex'), {
        type: 'bar',
        data: {
          labels: <?= json_encode($labelsStock) ?>,
          datasets: [{
            data: <?= json_encode($valoresStock) ?>,
            backgroundColor: 'rgba(25, 135, 84, 0.7)'
          }]
        },
        options: { ...commonOptions, plugins: { legend: { display: false } } }
      });

      // 3. Animales
      new Chart(document.getElementById('chartAnimalesIndex'), {
        type: 'bar',
        data: {
          labels: <?= json_encode($labelsAnimales) ?>,
          datasets: [{
            data: <?= json_encode($cantidadesAnimales) ?>,
            backgroundColor: 'rgba(255, 159, 64, 0.7)'
          }]
        },
        options: { ...commonOptions, plugins: { legend: { display: false } } }
      });
    });
  </script>
</body>

</html>