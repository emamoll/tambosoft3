<?php

require_once __DIR__ . '../../DAOS/stockDAO.php';
require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../DAOS/proveedorDAO.php';
require_once __DIR__ . '../../modelos/stock/stockModelo.php';

class StockController
{
  private $stockDAO;
  private $alimentoDAO;
  private $proveedorDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->stockDAO = new StockDAO();
      $this->alimentoDAO = new AlimentoDAO();
      $this->proveedorDAO = new ProveedorDAO();
    } catch (Exception $e) {
      $this->stockDAO = null;
      $this->alimentoDAO = null;
      $this->proveedorDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  public function procesarFormularios()
  {
    if ($this->connError !== null) {
      if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['tipo' => 'error', 'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError]);
        exit;
      }
      return ['tipo' => 'error', 'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError];
    }

    $accion = $_POST['accion'] ?? ($_GET['action'] ?? null);

    // Endpoint para LISTAR todos los lotes con filtros
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'list') {

      $alimentoId = intval($_GET['alimentoId'] ?? 0);
      $produccionInterna = $_GET['produccionInterna'] ?? '-1';

      if ($produccionInterna === '1') {
        $filtroPI = 1;
      } elseif ($produccionInterna === '0') {
        $filtroPI = 0;
      } else {
        $filtroPI = -1;
      }

      $alimentoIdFiltro = $alimentoId > 0 ? $alimentoId : null;

      $stocks = $this->stockDAO->getAllStocksDetalle($alimentoIdFiltro, $filtroPI);

      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($stocks);
      exit;
    }

    // Endpoint para obtener el STOCK TOTAL
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'getStockTotal') {
      $alimentoId = intval($_GET['alimentoId'] ?? 0);
      if ($alimentoId > 0) {
        $total = $this->stockDAO->getStockDisponibleByAlimento($alimentoId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['total' => $total]);
      } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['total' => 0]);
      }
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      $data = $_POST;

      $id = intval($data['id'] ?? 0);
      $alimentoId = intval($data['alimentoId'] ?? 0);
      $cantidad = intval($data['cantidad'] ?? 0);
      $produccionInterna = isset($data['produccionInterna']) ? 1 : 0;
      $proveedorId = isset($data['proveedorId']) && $data['proveedorId'] !== '' ? intval($data['proveedorId']) : null;
      $fechaIngreso = trim($data['fechaIngreso'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      // Validación para registrar/modificar (solo lotes de ingreso, cantidad > 0)
      if (in_array($accion, ['registrar', 'modificar'])) {
        if (empty($alimentoId) || $cantidad <= 0 || empty($fechaIngreso)) {
          $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios: Alimento, Cantidad (> 0) y Fecha.'];
        } elseif (!$produccionInterna && empty($proveedorId)) {
          $res = ['tipo' => 'error', 'mensaje' => 'Si no es producción interna, el proveedor es obligatorio.'];
        } else {
          $res = ['tipo' => 'success', 'mensaje' => ''];
        }
      }

      switch ($accion) {
        case 'registrar':
          if ($res['tipo'] === 'error')
            break;

          $stock = new Stock(null, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $fechaIngreso);
          $ok = $this->stockDAO->registrarStock($stock);
          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Lote de stock registrado correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrar el lote de stock.'];
          break;

        case 'modificar':
          if ($res['tipo'] === 'error')
            break;
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID de lote inválido para modificar.'];
            break;
          }

          $stock = new Stock($id, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $fechaIngreso);
          $ok = $this->stockDAO->modificarStock($stock);
          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Lote de stock modificado correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar el lote de stock.'];
          break;

        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID de lote inválido para eliminar.'];
            break;
          }
          $ok = $this->stockDAO->eliminarStock($id);
          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Lote de stock eliminado correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'No se encontró el lote o no se pudo eliminar.'];
          break;

        default:
          if ($res['tipo'] !== 'error') {
            $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida.'];
          }
          break;
      }

      if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res);
        exit;
      }
    }
  }

  // Métodos para obtener datos auxiliares para el frontend
  public function obtenerAlimentos()
  {
    if ($this->alimentoDAO === null)
      return [];
    return $this->alimentoDAO->getAllAlimentos();
  }

  public function obtenerProveedores()
  {
    if ($this->proveedorDAO === null)
      return [];
    return $this->proveedorDAO->getAllProveedores();
  }
}

// Punto de entrada principal para peticiones web
if (php_sapi_name() !== 'cli') {
  $ctrl = new StockController();
  $ctrl->procesarFormularios();
}