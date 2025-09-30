<?php
require_once __DIR__ . "/fpdf186/fpdf.php";
require_once __DIR__ . "/../../../backend/controladores/ordenController.php"; // Asegúrate de que la ruta sea correcta desde ordenes.php
require_once __DIR__ . "/../../../backend/controladores/almacenController.php"; // Necesario para obtener el nombre del almacén si no está enriquecido

// Aquí, en lugar de obtener solo todas las órdenes, llamaremos a procesarFiltro()
// que se encargará de obtener los datos filtrados (si vienen por GET) o todos los datos.
$controllerOrden = new OrdenController();

// Como los filtros para el PDF vienen por GET, necesitamos simular el $_GET
// que procesarFiltro() espera. Esto se hace copiando $_GET a $_POST temporalmente
// para la llamada a procesarFiltro, o reestructurando procesarFiltro para leer GET directamente.
// Ya lo habíamos reestructurado para leer GET directamente, así que es más simple.

// procesarFiltro() en OrdenController ahora ya espera los filtros en $_GET.
$ordenes = $controllerOrden->procesarFiltro();


$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('Listado de Órdenes de Alimentos'), 0, 1, 'C');
$pdf->Ln(5);

// Encabezado
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(15, 10, 'N', 1);
$pdf->Cell(30, 10, utf8_decode('Campo'), 1); 
$pdf->Cell(30, 10, 'Alimento', 1);
$pdf->Cell(20, 10, 'Cantidad', 1);
$pdf->Cell(30, 10, 'Fecha', 1);
$pdf->Cell(20, 10, 'Hora', 1);
$pdf->Cell(30, 10, 'Estado', 1);
$pdf->Ln();

// Datos
$pdf->SetFont('Arial', '', 10);
foreach ($ordenes as $o) {
  $pdf->Cell(15, 10, $o->getId(), 1);
  $pdf->Cell(30, 10, utf8_decode($o->almacen_nombre ?? 'N/A'), 1); 
  $pdf->Cell(30, 10, utf8_decode($o->alimento_nombre ?? 'N/A'), 1); 
  $pdf->Cell(20, 10, $o->getCantidad(), 1);
  $fecha = date('d-m-Y', strtotime($o->getFecha_actualizacion()));
  $pdf->Cell(30, 10, $fecha, 1);
  $pdf->Cell(20, 10, $o->getHora_actualizacion(), 1); 
  $pdf->Cell(30, 10, utf8_decode($o->estado_nombre ?? 'N/A'), 1); 
  $pdf->Ln();
}

$pdf->Output('I', 'ordenes.pdf');