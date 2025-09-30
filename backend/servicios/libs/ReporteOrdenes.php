<?php

ob_start(); // Iniciar el buffer de salida al principio del script
require_once __DIR__ . "/fpdf186/fpdf.php";
require_once __DIR__ . "/../../../backend/controladores/ordenController.php";
// No necesitamos alimentoController aquí directamente si ordenController ya adjunta el precio
// require_once __DIR__ . "/../../../backend/controladores/alimentoController.php";

$controllerOrden = new OrdenController();
// $controllerAlimento = new AlimentoController(); // No se necesita si el precio ya viene adjunto

// Procesar filtros: procesarFiltro() en OrdenController ahora ya espera los filtros en $_GET.
// Esto obtendrá las órdenes filtradas por el modal y enriquecidas con nombres y precios.
$ordenes = $controllerOrden->procesarFiltro();

// Gerencia solo debe ver órdenes ENTREGADAS (estado 5)
// Filtrar las órdenes después de que procesarFiltro las traiga y enriquezca.
$ordenesGerenciaParaPDF = [];
foreach ($ordenes as $orden) {
    if ($orden->getEstado_id() == 5) { // Suponiendo que 5 es el ID de estado "Entregada"
        $ordenesGerenciaParaPDF[] = $orden;
    }
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('Reporte de Órdenes Entregadas'), 0, 1, 'C');
$pdf->Ln(5);

// Encabezado del PDF (Debe coincidir con la tabla de Gerencia)
$pdf->SetFont('Arial', 'B', 9); // Fuente más pequeña para que quepan todos los encabezados
$pdf->Cell(10, 10, 'N', 1);
$pdf->Cell(25, 10, utf8_decode('Campo'), 1); // Ancho ajustado
$pdf->Cell(30, 10, 'Alimento', 1); // Ancho ajustado
$pdf->Cell(20, 10, 'Cantidad', 1);
$pdf->Cell(25, 10, 'P. Unitario', 1); // Ajustado a "P. Unitario"
$pdf->Cell(25, 10, 'Total', 1);
$pdf->Cell(30, 10, 'Fecha Entrega', 1);
$pdf->Cell(25, 10, 'Hora Entrega', 1);
$pdf->Ln();

// Datos
$pdf->SetFont('Arial', '', 9); // Fuente más pequeña para los datos
$sumaTotalGeneral = 0;

if (!empty($ordenesGerenciaParaPDF)) {
    foreach ($ordenesGerenciaParaPDF as $o) {
        // El precio unitario ya viene adjunto en $o->alimento_precio desde el controlador.
        $precioUnitario = $o->alimento_precio ?? 0;

        $subtotal = $precioUnitario * $o->getCantidad();
        $sumaTotalGeneral += $subtotal;

        $pdf->Cell(10, 10, $o->getId(), 1);
        $pdf->Cell(25, 10, utf8_decode($o->almacen_nombre ?? 'N/A'), 1);
        $pdf->Cell(30, 10, utf8_decode($o->alimento_nombre ?? 'N/A'), 1);
        $pdf->Cell(20, 10, $o->getCantidad(), 1);
        $pdf->Cell(25, 10, '$' . number_format($precioUnitario, 2, ',', '.'), 1, 0, 'R'); // Alinear a la derecha
        $pdf->Cell(25, 10, '$' . number_format($subtotal, 2, ',', '.'), 1, 0, 'R'); // Alinear a la derecha

        // Fecha y Hora de Entrega (se obtiene de fecha_actualizacion/hora_actualizacion)
        $fechaEntrega = date('d-m-Y', strtotime($o->getFecha_actualizacion()));
        $horaEntrega = $o->getHora_actualizacion();

        $pdf->Cell(30, 10, $fechaEntrega, 1);
        $pdf->Cell(25, 10, $horaEntrega, 1);
        $pdf->Ln();
    }
} else {
    // Si no hay órdenes filtradas para mostrar
    $pdf->Cell(0, 10, utf8_decode('No hay órdenes entregadas que coincidan con los filtros.'), 1, 1, 'C');
}


// Fila del total general al final de la tabla del PDF
$pdf->SetFont('Arial', 'B', 10);
// Suma de anchos de las primeras 5 columnas (15+25+30+20+25 = 115)
$pdf->Cell(110, 10, 'Total General:', 1, 0, 'R');
$pdf->Cell(25, 10, '$' . number_format($sumaTotalGeneral, 2, ',', '.'), 1, 0, 'R'); // Columna del total
$pdf->Cell(35 + 20, 10, '', 1); // Celda vacía que cubre las columnas de Fecha y Hora
$pdf->Ln();

ob_end_clean(); // Limpiar el buffer de salida
$pdf->Output('I', 'Reporte de Ordenes Entregadas'); // Salida del PDF
exit;
