<?php
require_once __DIR__ . '/../servicios/Dompdf/dompdf/autoload.inc.php';
require_once __DIR__ . '/../DAOS/ordenDAO.php';

use Dompdf\Dompdf;

$dao = new OrdenDAO();
$datos = $dao->listarConsumoValorizado();
$total = 0;

$html = '
<style>
    body { font-family: Helvetica; font-size: 10pt; color: #333; }
    h2 { text-align: center; color: #084a83; border-bottom: 2px solid #084a83; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background-color: #343a40; color: white; padding: 10px; text-align: left; }
    td { padding: 8px; border-bottom: 1px solid #dee2e6; }
    .text-end { text-align: right; }
    .total-box { margin-top: 30px; text-align: right; font-size: 14pt; font-weight: bold; color: #d9534f; }
</style>
<h2>Reporte de Stock Consumido (Entregas Realizadas)</h2>
<table>
    <thead>
        <tr>
            <th>Alimento / Origen</th>
            <th class="text-end">Cant.</th>
            <th class="text-end">Subtotal</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datos as $d) {
  $origen = ($d['produccionInterna'] == 1) ? 'Propia' : ($d['proveedorNombre'] ?? 'Proveedor');
  $html .= '<tr>
        <td>' . $d['tipoAlimentoNombre'] . ' ' . $d['alimentoNombre'] . ' (' . $origen . ')</td>
        <td class="text-end">' . $d['cantidadTotal'] . '</td>
        <td class="text-end">$' . number_format($d['subtotalConsumo'], 2) . '</td>
    </tr>';
  $total += $d['subtotalConsumo'];
}

$html .= '</tbody>
</table>
<div class="total-box">COSTO TOTAL DEL CONSUMO: $' . number_format($total, 2, ',', '.') . '</div>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("ReporteConsumo_" . date('Ymd') . ".pdf", ["Attachment" => false]);