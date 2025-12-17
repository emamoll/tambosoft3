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
  <script src="../../javascript/usuario.js"></script>
</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <main class="container mt-3">
    <h2 class="text-white mb-3" style="font-size: 1.5rem;">Panel de Control</h2>

    <div class="row mb-3">
      <div class="col-md-6 mb-2">
        <div class="card shadow-sm border-0 bg-white p-3 rounded" style="height: 160px;">
          <h6 class="text-center text-dark fw-bold mb-2">Resumen Operativo</h6>
          <div class="d-flex justify-content-center align-items-center h-100">
            <p class="text-muted small italic">Próximo gráfico aquí...</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-2">
        <div class="card shadow-sm border-0 bg-white p-3 rounded" style="height: 160px;">
          <h6 class="text-center text-dark fw-bold mb-3">Resumen Económico</h6>
          <div class="row g-2 align-items-center">
            <div class="col-6">
              <div class="card bg-success text-white border-0 shadow-sm p-2 text-center">
                <small style="font-size: 0.65rem;">INVERSIÓN STOCK</small>
                <h4 class="mb-0" style="font-size: 1.2rem;">$<?= number_format($totalStock, 0, ',', '.') ?></h4>
              </div>
            </div>
            <div class="col-6">
              <div class="card bg-warning text-dark border-0 shadow-sm p-2 text-center">
                <small style="font-size: 0.65rem;">CONSUMO TOTAL</small>
                <h4 class="mb-0" style="font-size: 1.2rem;">$<?= number_format($totalConsumo, 0, ',', '.') ?></h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <div class="card shadow-sm border-0 bg-white p-3 rounded" style="height: 300px;">
          <h6 class="text-center text-dark fw-bold mb-2">Valorización de Stock</h6>
          <canvas id="chartStockIndex"></canvas>
        </div>
      </div>

      <div class="col-md-6 mb-3">
        <div class="card shadow-sm border-0 bg-white p-3 rounded" style="height: 300px;">
          <h6 class="text-center text-dark fw-bold mb-2">Consumo por Animal</h6>
          <canvas id="chartAnimalesIndex"></canvas>
        </div>
      </div>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
      };

      // Chart Stock
      new Chart(document.getElementById('chartStockIndex'), {
        type: 'bar',
        data: {
          labels: <?= json_encode($labelsStock) ?>,
          datasets: [{
            data: <?= json_encode($valoresStock) ?>,
            backgroundColor: 'rgba(25, 135, 84, 0.7)',
            borderColor: 'rgba(25, 135, 84, 1)',
            borderWidth: 1
          }]
        },
        options: commonOptions
      });

      // Chart Animales
      new Chart(document.getElementById('chartAnimalesIndex'), {
        type: 'bar',
        data: {
          labels: <?= json_encode($labelsAnimales) ?>,
          datasets: [{
            data: <?= json_encode($cantidadesAnimales) ?>,
            backgroundColor: 'rgba(255, 159, 64, 0.7)',
            borderColor: 'rgba(255, 159, 64, 1)',
            borderWidth: 1
          }]
        },
        options: {
          ...commonOptions,
          scales: { y: { beginAtZero: true } }
        }
      });
    });
  </script>
</body>

</html>