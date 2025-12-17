<?php
date_default_timezone_set('America/Argentina/Cordoba');
// =============================================================
// REPORTE PDF – STOCK REGISTRADO
// =============================================================

// 1. DEPENDENCIAS
require_once __DIR__ . '/../servicios/Dompdf/dompdf/autoload.inc.php';
require_once __DIR__ . '/../controladores/stockController.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// -------------------------------------------------
// 2. FILTROS (DESDE GET)
// -------------------------------------------------
$filtros = $_GET ?? [];

// Normalizar fechas (JS → DAO)
if (isset($filtros['fechaMin'])) {
        $filtros['filtroFechaMin'] = $filtros['fechaMin'];
}
if (isset($filtros['fechaMax'])) {
        $filtros['filtroFechaMax'] = $filtros['fechaMax'];
}

// -------------------------------------------------
// 3. DATOS
// -------------------------------------------------
$controller = new StockController();
$stock = $controller->listarStock($filtros);

$fechaReporte = date("d/m/Y H:i:s");

// -------------------------------------------------
// 4. LOGO BASE64
// -------------------------------------------------
$ruta_logo = __DIR__ . '/../../frontend/img/logo2.png';
$logo_base64 = '';

if (file_exists($ruta_logo)) {
        $tipo = pathinfo($ruta_logo, PATHINFO_EXTENSION);
        $data = file_get_contents($ruta_logo);
        $logo_base64 = 'data:image/' . $tipo . ';base64,' . base64_encode($data);
}

// -------------------------------------------------
// 5. HTML
// -------------------------------------------------
$html = '
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Reporte de Stock</title>

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

        .header-table td {
                border: none;
                vertical-align: middle;
        }

        .logo {
                height: 70px;
                width: auto;
                max-width: 100%;
        }

        .titulo {
                font-size: 15pt;
                font-weight: bold;
                text-align: center;
                color: #333;
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
        }

        /* ================= PIE DE PÁGINA ================= */
        @page {
                margin-bottom: 60pt;
        }

        footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 40px;
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
                                Listado de Stock
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
                        <th>Tipo de Alimento</th>
                        <th>Alimento</th>
                        <th>Origen</th>
                        <th>Proveedor</th>
                        <th>Cantidad</th>
                        <th>Fecha</th>
                </tr>
        </thead>
        <tbody>
';

// -------------------------------------------------
// 6. CUERPO
// -------------------------------------------------
function formatearFechaCorta($fecha)
{
        if (!$fecha)
                return '';
        try {
                return (new DateTime($fecha))->format('d/m/y');
        } catch (Exception $e) {
                return $fecha;
        }
}

if (empty($stock)) {
        $html .= '
        <tr>
                <td colspan="7" style="text-align:center;">
                        No se encontraron registros de stock.
                </td>
        </tr>';
} else {
        foreach ($stock as $s) {
                $html .= '
        <tr>
                <td>' . htmlspecialchars($s["almacenNombre"]) . '</td>
                <td>' . htmlspecialchars($s["tipoAlimentoNombre"]) . '</td>
                <td>' . htmlspecialchars($s["alimentoNombre"]) . '</td>
                <td>' . ($s["produccionInterna"] ? "Producción Interna" : "Proveedor") . '</td>
                <td>' . ($s["proveedorNombre"] ?? "-") . '</td>
                <td>' . htmlspecialchars($s["cantidad"]) . '</td>
                <td>' . formatearFechaCorta($s["fechaIngreso"]) . '</td>
        </tr>';
        }
}

$html .= '
        </tbody>
</table>

<footer>
    <div class="footer-content">
        <span class="left">Generado por Tambosoft - Gestión de Alimentos</span>
    </div>
</footer>

</body>
</html>';

// -------------------------------------------------
// 7. DOMPDF
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
// 8. STREAM
// -------------------------------------------------
$dompdf->stream(
        "Listado_Stock_" . date("Ymd_His") . ".pdf",
        ["Attachment" => false]
);

exit;
