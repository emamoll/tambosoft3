<?php
require_once __DIR__ . '/../servicios/Dompdf/dompdf/autoload.inc.php';
require_once __DIR__ . '/../DAOS/stockDAO.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$dao = new StockDAO();
$datos = $dao->listarValorizado();
$fechaReporte = date("d/m/Y H:i");
$totalGeneral = 0;

// Configuración de Logo (similar a reporteStock.php)
$ruta_logo = __DIR__ . '/../../frontend/img/logo2.png';
$logo_base64 = '';
if (file_exists($ruta_logo)) {
  $data = file_get_contents($ruta_logo);
  $logo_base64 = 'data:image/' . pathinfo($ruta_logo, PATHINFO_EXTENSION) . ';base64,' . base64_encode($data);
}

$html = '
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 9pt; }
        .header { width: 100%; border-bottom: 2px solid #444; margin-bottom: 20px; }
        .titulo { font-size: 16pt; font-weight: bold; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 8px; text-align: left; }
        td { border: 1px solid #eee; padding: 6px; }
        .derecha { text-align: right; }
        .total-row { background-color: #eee; font-weight: bold; font-size: 11pt; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td border="0" width="20%"><img src="' . $logo_base64 . '" style="height:60px;"></td>
                <td border="0" class="titulo">Reporte de Valorización de Inventario</td>
                <td border="0" width="20%" class="derecha">' . $fechaReporte . '</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Alimento</th>
                <th>Origen</th>
                <th>Cant.</th>
                <th>P. Unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>';

foreach ($datos as $s) {
  $subtotal = (float) $s['subtotalValor'];
  $totalGeneral += $subtotal;
  $origen = $s['produccionInterna'] ? "Interna" : "Prov: " . ($s['proveedorNombre'] ?? '-');

  $html .= '
            <tr>
                <td>' . htmlspecialchars($s['almacenNombre']) . '</td>
                <td>' . htmlspecialchars($s['tipoAlimentoNombre']) . '</td>
                <td>' . htmlspecialchars($s['alimentoNombre']) . '</td>
                <td>' . $origen . '</td>
                <td class="derecha">' . $s['cantidadTotal'] . '</td>
                <td class="derecha">$' . number_format($s['precioUnitario'], 2) . '</td>
                <td class="derecha">$' . number_format($subtotal, 2) . '</td>
            </tr>';
}

$html .= '
            <tr class="total-row">
                <td colspan="6" class="derecha">VALOR TOTAL DEL STOCK:</td>
                <td class="derecha">$' . number_format($totalGeneral, 2) . '</td>
            </tr>
        </tbody>
    </table>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Valorizacion_Stock.pdf", ["Attachment" => false]);