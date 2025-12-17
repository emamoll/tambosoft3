<?php
$rolId = $_SESSION['rolId'] ?? 0;
?>

<nav class="sidebar" id="sidebar">
  <ul>
    <?php if ($rolId == 3): ?>
      <li><a href="../orden/ordenTractorista.php"><i class="fas fa-tractor"></i><span>Ordenes</span></a></li>
    <?php elseif ($rolId == 2): ?>
      <li><a href="#"><i class="fas fa-chart-bar"></i><span>Reportes</span></a></li>
    <?php else: ?>
      <li><a href="../campo/campo.php"><i class="fas fa-map"></i><span>Campos</span></a></li>
      <li><a href="../categoria/categoria.php"><i class="fas fa-cow"></i><span>Categorias</span></a></li>
      <li><a href="../potrero/potrero.php"><i class="fas fa-map-marker-alt"></i><span>Potreros</span></a></li>
      <li><a href="../alimento/alimento.php"><i class="fas fa-leaf"></i><span>Alimentos</span></a></li>
      <li><a href="../proveedor/proveedor.php"><i class="fas fa-box"></i><span>Proveedores</span></a></li>
      <li><a href="../stock/stock.php"><i class="fas fa-warehouse"></i></i><span>Stocks</span></a></li>
      <li><a href="../orden/orden.php"><i class="fas fa-tractor"></i><span>Ordenes</span></a></li>
      <li><a href="../reportes/reporte.php"><i class="fas fa-chart-bar"></i><span>Reportes</span></a></li>
      <li><a href="../usuario/registrar.php"><i class="fas fa-user"></i><span>Usuarios</span></a></li>
    <?php endif; ?>
  </ul>
</nav>