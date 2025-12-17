<?php
session_start();

// 1. Verificación de Seguridad
if (!isset($_SESSION['username']) || !isset($_SESSION['rolId']) || $_SESSION['rolId'] != 1) {
  header('Location: ../usuario/login.php');
  exit;
}

// 2. Carga de dependencias y obtención de datos
require_once __DIR__ . '/../../../backend/controladores/stockController.php';

$controller = new StockController();
// Se utiliza el método que calcula cantidad * precio agrupado
$datos = $controller->listarStockValorizado();

// Inicialización de variables para cálculos y gráficos
$totalGeneral = 0;
$labels = [];
$valores = [];

// 3. Procesamiento de datos
if (is_array($datos) && !empty($datos)) {
  foreach ($datos as $s) {
    $subtotal = (float) ($s['subtotalValor'] ?? 0);
    $totalGeneral += $subtotal;

    // Formato unificado: Tipo Alimento Alimento (Origen/Proveedor)
    $origenTexto = ($s['produccionInterna'] == 1) ? 'Producción Propia' : ($s['proveedorNombre'] ?? 'Proveedor');
    $textoIdentificador = ($s['tipoAlimentoNombre'] ?? '') . " " . ($s['alimentoNombre'] ?? '') . " (" . $origenTexto . ")";

    $labels[] = $textoIdentificador;
    $valores[] = $subtotal;
  }
} else {
  $datos = [];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambosoft: Reportes de Valorización</title>
  <link rel="icon" href="../../../../img/logo2.png" type="image/png">

  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../javascript/header.js"></script>
</head>

<body class="bodyHome">
  <?php require_once __DIR__ . '../../secciones/header.php'; ?>
  <?php require_once __DIR__ . '../../secciones/navbar.php'; ?>

  <div class="container mt-4">
    <div class="row mb-4 align-items-center">
      <div class="col-md-8">
        <h1 class="text-white"><i class="fas fa-chart-bar"></i> Valorización de Inventario</h1>
      </div>
    </div>

    <div class="row mb-4 justify-content-end">
      <div class="col-md-4">
        <div class="card bg-success text-white shadow border-0">
          <div class="card-body text-end">
            <h5 class="fw-light">Inversión Total en Stock</h5>
            <h2 class="display-6 fw-bold">$<?php echo number_format($totalGeneral, 2, ',', '.'); ?></h2>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-7 mb-4">
        <div class="card shadow h-100 border-0">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-primary fw-bold">Distribución de Valor por Alimento</h5>
          </div>
          <div class="card-body">
            <?php if (empty($valores)): ?>
              <p class="text-center text-muted">No hay datos suficientes para generar el gráfico.</p>
            <?php else: ?>
              <div style="position: relative; height:400px;">
                <canvas id="chartValorizacion"></canvas>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-5 mb-4">
        <div class="card shadow h-100 border-0">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-primary fw-bold">Detalle Económico</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-dark">
                  <tr>
                    <th>Alimento (Origen)</th>
                    <th class="text-end">Cant.</th>
                    <th class="text-end">P. Unit.</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($datos)): ?>
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted">No hay stock registrado.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($datos as $index => $s): ?>
                      <tr>
                        <td class="small">
                          <?php echo htmlspecialchars($labels[$index]); ?>
                        </td>
                        <td class="text-end"><?php echo $s['cantidadTotal'] ?? 0; ?></td>
                        <td class="text-end text-muted small">
                          $<?php echo number_format($s['precioUnitario'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold text-success">
                          $<?php echo number_format($s['subtotalValor'] ?? 0, 2, ',', '.'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <div class="row mb-4 justify-content-end">
    <div class="col-md-4">
      <div class="col-md-4 text-end">
        <a href="../../../backend/reportes/reporteEconomicoStocko.php" target="_blank" class="btn btn-danger shadow-sm"
          style="background-color:#084a83">
          <i class="fas fa-file-pdf"></i> Exportar PDF
        </a>
      </div>
    </div>
  </div>


  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const canvasElement = document.getElementById('chartValorizacion');
      if (canvasElement) {
        const ctx = canvasElement.getContext('2d');
        new Chart(ctx, {
          type: 'bar', // Gráfico de barras vertical (por defecto)
          data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
              label: 'Inversión ($)',
              data: <?php echo json_encode($valores); ?>,
              backgroundColor: 'rgba(25, 135, 84, 0.7)', // Color verde coincidente con la tarjeta
              borderColor: 'rgba(25, 135, 84, 1)',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function (value) { return '$' + value.toLocaleString('es-AR'); }
                }
              },
              x: {
                ticks: {
                  // Rotar etiquetas si son muy largas para evitar solapamiento
                  maxRotation: 45,
                  minRotation: 45
                }
              }
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    return 'Inversión: $' + context.parsed.y.toLocaleString('es-AR', { minimumFractionDigits: 2 });
                  }
                }
              }
            }
          }
        });
      }
    });
  </script>
</body>

</html>