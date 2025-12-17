<?php
date_default_timezone_set('America/Argentina/Cordoba');
// =============================================================
// REPORTE PDF – VALORIZACIÓN DE INVENTARIO (ESTILO reporteStock)
// =============================================================

// 1. DEPENDENCIAS
require_once __DIR__ . '/../servicios/Dompdf/dompdf/autoload.inc.php';
require_once __DIR__ . '/../DAOS/stockDAO.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// -------------------------------------------------
// 2. DATOS
// -------------------------------------------------
$dao = new StockDAO();
$datos = $dao->listarValorizado();

$fechaReporte = date("d/m/Y H:i");
$totalGeneral = 0;

// -------------------------------------------------
// 3. LOGO (igual a reporteStock.php)
// -------------------------------------------------
$ruta_logo = __DIR__ . '/../../frontend/img/logo2.png';
$logo_base64 = '';

if (file_exists($ruta_logo)) {
  $data = file_get_contents($ruta_logo);
  $logo_base64 = 'data:image/' . pathinfo($ruta_logo, PATHINFO_EXTENSION) . ';base64,' . base64_encode($data);
}

// -------------------------------------------------
// 4. HTML (misma plantilla visual que reporteStock)
// -------------------------------------------------
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Valorización de Inventario</title>

  <style>
        body {
                font-family: sans-serif;
                font-size: 10pt;
        }

        /* ================= HEADER ================= */
        .header {
                width: 100%;
                border-bottom: 1px solid #ccc;
                padding-bottom: 8px;
                margin-bottom: 15px;
        }

        .header-table {
                width: 100%;
                border-collapse: collapse;
        }

        .logo {
                height: 60px;
        }

        .titulo {
                text-align: center;
                font-size: 16pt;
                font-weight: bold;
        }

        .metadata {
                font-size: 8pt;
                color: #666;
                text-align: right;
                white-space: nowrap;
        }

        /* ================= TABLA ================= */
        table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
        }

        th, td {
                border: 1px solid #ddd;
                padding: 6px;
        }

        th {
                background-color: #f2f2f2;
                font-size: 9pt;
                text-align: left;
        }

        td {
                font-size: 9pt;
        }

        .derecha {
                text-align: right;
        }

        .total-row {
                background-color: #f2f2f2;
                font-weight: bold;
        }

        /* ================= PIE DE PÁGINA ================= */
        @page {
                margin-bottom: 60pt;
        }

        footer {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                height: 40pt;
                border-top: 1px solid #ccc;
                font-size: 8pt;
                color: #666;
        }

        .footer-content {
                width: 100%;
                height: 20px;
                padding: 5px 40px 0 40px;
                box-sizing: border-box;
        }

        .footer-content .left {
                float: center;
                margin-left: 25%;
        }
  </style>
</head>

<body>

  <div class="header">
    <table class="header-table">
      <tr>
        <td width="20%">
          <img src="' . $logo_base64 . '" class="logo">
        </td>

        <td width="55%" class="titulo">
          Reporte de Stock Total
        </td>

        <td width="25%" class="metadata">
          ' . $fechaReporte . '
        </td>
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
        <th class="derecha">Cant.</th>
        <th class="derecha">P. Unit.</th>
        <th class="derecha">Subtotal</th>
      </tr>
    </thead>
    <tbody>
';

// rows
foreach ($datos as $s) {
  $subtotal = (float) ($s['subtotalValor'] ?? 0);
  $totalGeneral += $subtotal;

  $origen = !empty($s['produccionInterna'])
    ? "Interna"
    : "Prov: " . ($s['proveedorNombre'] ?? "-");

  $html .= '
      <tr>
        <td>' . htmlspecialchars($s['almacenNombre'] ?? '-') . '</td>
        <td>' . htmlspecialchars($s['tipoAlimentoNombre'] ?? '-') . '</td>
        <td>' . htmlspecialchars($s['alimentoNombre'] ?? '-') . '</td>
        <td>' . htmlspecialchars($origen) . '</td>
        <td class="derecha">' . htmlspecialchars($s['cantidadTotal'] ?? '0') . '</td>
        <td class="derecha">$' . number_format((float) ($s['precioUnitario'] ?? 0), 2) . '</td>
        <td class="derecha">$' . number_format($subtotal, 2) . '</td>
      </tr>
  ';
}

// total row
$html .= '
      <tr class="total-row">
        <td colspan="6" class="derecha">VALOR TOTAL DEL STOCK:</td>
        <td class="derecha">$' . number_format($totalGeneral, 2) . '</td>
      </tr>
    </tbody>
  </table>

  <footer>
    <div class="footer-content">
      <span class="left">Generado por Tambosoft - Gestión de Alimentos</span>
    </div>
  </footer>

</body>
</html>
';

// -------------------------------------------------
// 5. DOMPDF (igual a reporteStock.php)
// -------------------------------------------------
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// -------------------------------------------------
// 6. STREAM
// -------------------------------------------------
$dompdf->stream(
  "Reporte_Stock_" . date("Ymd_His") . ".pdf",
  ["Attachment" => false]
);

exit;
