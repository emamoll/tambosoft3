<?php
require_once __DIR__ . '../servicios/PDF/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class ReporteStockPDF
{
  public static function generar($datos, $logoPath)
  {
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    // Cargar logo (opcional)
    $logoBase64 = '';
    if (file_exists($logoPath)) {
      $logoData = file_get_contents($logoPath);
      $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }

    // Construcción de filas
    $rowsHtml = "";
    foreach ($datos as $s) {
      $origen = $s['produccionInterna'] == 1 ? "Producción propia" : "Proveedor";
      $proveedor = $s['produccionInterna'] == 1 ? "-" : ($s['proveedorNombre'] ?? "-");

      $rowsHtml .= "
            <tr>
                <td>{$s['almacenNombre']}</td>
                <td>{$s['tipoAlimentoNombre']}</td>
                <td>{$s['alimentoNombre']}</td>
                <td style='text-align:right;'>{$s['cantidad']}</td>
                <td>{$origen}</td>
                <td>{$proveedor}</td>
            </tr>";
    }

    $fecha = date("d/m/Y H:i");

    // HTML del PDF
    $html = "
        <html>
        <head>
            <style>
                body { font-family: DejaVu Sans, Arial; font-size: 12px; }
                .header { display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 2px solid #007BFF; }
                .logo { height: 60px; }
                h1 { margin: 0; color: #003060; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th, td { border: 1px solid #ddd; padding: 6px; }
                th { background: #f0f4ff; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div>" . ($logoBase64 ? "<img class='logo' src='$logoBase64'>" : "") . "</div>
                <div style='text-align:right'>
                    <h1>Reporte de Stock</h1>
                    <small>Generado el $fecha</small>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Campo</th>
                        <th>Tipo</th>
                        <th>Alimento</th>
                        <th>Cantidad</th>
                        <th>Origen</th>
                        <th>Proveedor</th>
                    </tr>
                </thead>
                <tbody>
                    $rowsHtml
                </tbody>
            </table>
        </body>
        </html>
        ";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
  }
}
