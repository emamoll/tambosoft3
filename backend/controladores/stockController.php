<?php

require_once __DIR__ . '../../DAOS/stockDAO.php';
require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../DAOS/proveedorDAO.php';
require_once __DIR__ . '../../DAOS/almacenDAO.php'; 
require_once __DIR__ . '../../modelos/stock/stockModelo.php';

class StockController
{
  private $stockDAO;
  private $alimentoDAO;
  private $proveedorDAO;
  private $almacenDAO; 
  private $connError = null;

  public function __construct()
  {
    try {
      $this->stockDAO = new StockDAO();
      $this->alimentoDAO = new AlimentoDAO();
      $this->proveedorDAO = new ProveedorDAO();
      $this->almacenDAO = new AlmacenDAO(); 
    } catch (Exception $e) {
      $this->stockDAO = null;
      $this->alimentoDAO = null;
      $this->proveedorDAO = null;
      $this->almacenDAO = null;
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

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'list') {

      $alimentoId = intval($_GET['alimentoId'] ?? 0);
      $produccionInterna = $_GET['produccionInterna'] ?? '-1';
      $almacenId = intval($_GET['almacenId'] ?? 0); 

      if ($produccionInterna === '1') {
        $filtroPI = 1;
      } elseif ($produccionInterna === '0') {
        $filtroPI = 0;
      } else {
        $filtroPI = -1;
      }

      $alimentoIdFiltro = $alimentoId > 0 ? $alimentoId : null;
      $almacenIdFiltro = $almacenId > 0 ? $almacenId : null; // NUEVO

      $stocks = $this->stockDAO->getAllStocksDetalle($alimentoIdFiltro, $filtroPI, $almacenIdFiltro);

      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($stocks);
      exit;
    }

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
      $almacenId = isset($data['almacenId']) && $data['almacenId'] !== '' ? intval($data['almacenId']) : null; 
      $fechaIngreso = trim($data['fechaIngreso'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      // Validación para registrar/modificar (solo lotes de ingreso, cantidad > 0)
      if (in_array($accion, ['registrar', 'modificar'])) {
        if (empty($alimentoId) || $cantidad <= 0 || empty($fechaIngreso)) {
          $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios'];
        } elseif (!$produccionInterna && empty($proveedorId)) {
          $res = ['tipo' => 'error', 'mensaje' => 'Si no es producción interna, el proveedor es obligatorio.'];
        } elseif (empty($almacenId)) { 
          $res = ['tipo' => 'error', 'mensaje' => 'El Almacén es obligatorio.'];
        } else {
          $res = ['tipo' => 'success', 'mensaje' => ''];
        }
      }

      switch ($accion) {
        case 'registrar':
          if ($res['tipo'] === 'error')
            break;

          $stock = new Stock(null, $almacenId, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $fechaIngreso); // PASAR ALMACEN ID
          $ok = $this->stockDAO->registrarStock($stock);
          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Stock registrado correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrar el stock.'];
          break;

        case 'modificar':
          if ($res['tipo'] === 'error')
            break;
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID de stock inválido para modificar.'];
            break;
          }

          $stock = new Stock($id,  $almacenId, $alimentoId, $cantidad, $produccionInterna, $proveedorId,$fechaIngreso); // PASAR ALMACEN ID
          $ok = $this->stockDAO->modificarStock($stock);
          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Stock modificado correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar el stock.'];
          break;

        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID de stock inválido para eliminar.'];
            break;
          }
          $ok = $this->stockDAO->eliminarStock($id);
          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Stock eliminado correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'No se encontró el stock o no se pudo eliminar.'];
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

  public function obtenerAlmacenes() 
  {
    if ($this->almacenDAO === null)
      return [];
    return $this->almacenDAO->getAllAlmacenes();
  }
}

if (php_sapi_name() !== 'cli') {
  $ctrl = new StockController();
  $ctrl->procesarFormularios();
}