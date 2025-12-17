<?php
date_default_timezone_set('America/Argentina/Cordoba');
// =============================================================
// REPORTE PDF – ÓRDENES REGISTRADAS
// =============================================================

// 1. DEPENDENCIAS
require_once '../servicios/Dompdf/dompdf/autoload.inc.php';
require_once '../controladores/ordenController.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 2. DATOS
$controllerOrden = new OrdenController();
$filtros = $_GET ?? [];
$ordenes = $controllerOrden->obtenerOrdenesDetalladas($filtros);
$fechaReporte = date("d/m/Y H:i:s");

// 3. LOGO BASE64 (DOMPDF SAFE)
$ruta_logo = __DIR__ . '/../../frontend/img/logo2.png';
$logo_base64 = '';

if (file_exists($ruta_logo)) {
  $tipo = pathinfo($ruta_logo, PATHINFO_EXTENSION);
  $data = file_get_contents($ruta_logo);
  $logo_base64 = 'data:image/' . $tipo . ';base64,' . base64_encode($data);
}

// =============================================================
// 4. HTML DEL PDF
// =============================================================
$html = '
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Reporte de Órdenes</title>

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

        /* ================= ESTADOS ================= */
        .estado-1 { color: #8a6d3b; }
        .estado-2 { color: #31708f; }
        .estado-3 { color: #007bff; }
        .estado-4 { color: #3c763d; }
        .estado-5 { color: #a94442; }

        /* ================= PIE DE PÁGINA (FOOTER) ================= */
        @page {
                margin-bottom: 60pt; /* Deja espacio para el footer fijo */
        }
    
    footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 40px; /* Altura del footer */
        border-top: 1px solid #ccc;
        font-size: 8pt;
        color: #666;
    }
    
    .footer-content {
        width: 100%;
        height: 20px;
        padding: 5px 40px 0 40px; /* Margen horizontal para contenido */
        box-sizing: border-box;
    }
    
    .footer-content .left {
        float: center;
        margin-left: 25%;
    }
    
    .footer-content .right {
        float: right;
        /* Dompdf sustituye {PAGE_NUM} y {PAGE_COUNT} si el elemento es fijo */
        content: "Página {PAGE_NUM} / {PAGE_COUNT}"; 
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
                                Listado de Órdenes
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
                        <th>#</th>
                        <th>Campo Origen</th>
                        <th>Categoría (Potrero)</th>
                        <th>Alimento</th>
                        <th>Cant.</th>
                        <th>Tractorista</th>
                        <th>Estado</th>
                        <th>Fecha / Hora</th>
                </tr>
        </thead>
        <tbody>
';

// 5. CUERPO DE TABLA
if (empty($ordenes)) {
  $html .= '
                <tr>
                        <td colspan="8" style="text-align:center;">
                                No se encontraron órdenes con los filtros aplicados.
                        </td>
                </tr>';
} else {
  foreach ($ordenes as $orden) {
    $estadoId = $orden['estadoId'] ?? 1;

    $html .= '
                <tr>
                        <td>' . htmlspecialchars($orden['id'] ?? '') . '</td>
                        <td>' . htmlspecialchars($orden['almacenNombre'] ?? '') . '</td>
                        <td>' . htmlspecialchars($orden['categoriaNombre'] ?? '') . ' (' . htmlspecialchars($orden['potreroNombre'] ?? '') . ')</td>
                        <td>' . htmlspecialchars($orden['tipoAlimentoNombre'] ?? '') . ' ' . htmlspecialchars($orden['alimentoNombre'] ?? '') . '</td>
                        <td>' . htmlspecialchars($orden['cantidad'] ?? 0) . '</td>
                        <td>' . htmlspecialchars($orden['usuarioNombre'] ?? '') . '</td>
                        <td class="estado-' . $estadoId . '">' . htmlspecialchars($orden['estadoDescripcion'] ?? '') . '</td>
                        <td>' . htmlspecialchars($orden['fechaActualizacion'] ?? '') . ' - ' . htmlspecialchars($orden['horaActualizacion'] ?? '') . '</td>
                </tr>';
  }
}

$html .= '
        </tbody>
</table>

<footer>
    <div class="footer-content">
        <span class="left">Generado por Tambosoft - Gestión de Alimentos</span>
       
</footer>

</body>
</html>';

// =============================================================
// 6. DOMPDF
// =============================================================
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 7. STREAM
$dompdf->stream(
  "Listado_Ordenes_" . date("Ymd_His") . ".pdf",
  ["Attachment" => false]
);

exit;