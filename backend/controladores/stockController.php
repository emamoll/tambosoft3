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
    // --- Error de conexión ---
    if ($this->connError !== null) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['tipo' => 'error', 'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError]);
      exit;
    }

    $accion = $_REQUEST['action'] ?? $_REQUEST['accion'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($accion === 'list' || $accion === null)) {

      $alimentoId = isset($_GET['alimentoId']) ? intval($_GET['alimentoId']) : 0;
      $almacenId = isset($_GET['almacenId']) ? intval($_GET['almacenId']) : 0;
      $produccionInterna = isset($_GET['produccionInterna']) ? intval($_GET['produccionInterna']) : -1;

      try {
        $stocks = $this->stockDAO->getAllStocksDetalle($alimentoId, $produccionInterna, $almacenId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($stocks, JSON_UNESCAPED_UNICODE);
      } catch (Exception $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Error al obtener stocks: ' . $e->getMessage()]);
      }
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'getStockTotal') {
      $alimentoId = intval($_GET['alimentoId'] ?? 0);
      try {
        $total = $alimentoId > 0 ? $this->stockDAO->getStockDisponibleByAlimento($alimentoId) : 0;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['total' => $total]);
      } catch (Exception $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Error al obtener stock total: ' . $e->getMessage()]);
      }
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      $data = $_POST;
      $accion = $data['accion'] ?? null;

      $id = intval($data['id'] ?? 0);
      $almacenId = intval($data['almacenId'] ?? 0);
      $alimentoId = intval($data['alimentoId'] ?? 0);
      $cantidad = floatval($data['cantidad'] ?? 0);
      $produccionInterna = isset($data['produccionInterna']) && $data['produccionInterna'] === 'on';
      $proveedorId = isset($data['proveedorId']) && $data['proveedorId'] !== '' ? intval($data['proveedorId']) : null;
      $fechaIngreso = trim($data['fechaIngreso'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      if (in_array($accion, ['registrar', 'modificar'])) {
        if (empty($almacenId) || empty($alimentoId) || $cantidad <= 0 || empty($fechaIngreso)) {
          $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios: Alimento, Cantidad (> 0), Fecha y Almacén.'];
        } elseif (!$produccionInterna && empty($proveedorId)) {
          $res = ['tipo' => 'error', 'mensaje' => 'Si no es producción interna, el proveedor es obligatorio.'];
        } else {
          $res = ['tipo' => 'success', 'mensaje' => ''];
        }
      }

      switch ($accion) {
        // --- REGISTRAR ---
        case 'registrar':
          if ($res['tipo'] === 'error')
            break;

          $stock = new Stock(null, $almacenId, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $fechaIngreso);
          try {
            $ok = $this->stockDAO->registrarStock($stock);
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Lote de stock registrado correctamente.']
              : ['tipo' => 'error', 'mensaje' => 'Error al registrar el lote de stock.'];
          } catch (Exception $e) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error SQL al registrar: ' . $e->getMessage()];
          }
          break;

        // --- MODIFICAR ---
        case 'modificar':
          if ($res['tipo'] === 'error')
            break;
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID de lote inválido para modificar.'];
            break;
          }

          $stock = new Stock($id, $almacenId, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $fechaIngreso);
          try {
            $ok = $this->stockDAO->modificarStock($stock);
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Lote de stock modificado correctamente.']
              : ['tipo' => 'error', 'mensaje' => 'Error al modificar el lote de stock.'];
          } catch (Exception $e) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error SQL al modificar: ' . $e->getMessage()];
          }
          break;

        // --- ELIMINAR ---
        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID de lote inválido para eliminar.'];
            break;
          }
          try {
            $ok = $this->stockDAO->eliminarStock($id);
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Lote de stock eliminado correctamente.']
              : ['tipo' => 'error', 'mensaje' => 'No se encontró el lote o no se pudo eliminar.'];
          } catch (Exception $e) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error SQL al eliminar: ' . $e->getMessage()];
          }
          break;

        // --- DEFAULT ---
        default:
          $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida.'];
          break;
      }

      // --- Envío de respuesta JSON ---
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($res, JSON_UNESCAPED_UNICODE);
      exit;
    }

    // Si no hay coincidencia con GET o POST válidos
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['tipo' => 'error', 'mensaje' => 'Solicitud inválida.']);
    exit;
  }

  public function obtenerAlimentos()
  {
    return $this->alimentoDAO ? $this->alimentoDAO->getAllAlimentos() : [];
  }

  public function obtenerProveedores()
  {
    return $this->proveedorDAO ? $this->proveedorDAO->getAllProveedores() : [];
  }

  public function obtenerAlmacenes()
  {
    return $this->almacenDAO ? $this->almacenDAO->getAllAlmacenes() : [];
  }
}

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
$isFormSubmit = ($_SERVER['REQUEST_METHOD'] === 'POST');
$isGetList = ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_REQUEST['action'] ?? $_REQUEST['accion'] ?? null) !== null);

// La lógica principal del controlador SOLO debe ejecutarse si es una llamada de API o un POST.
if (php_sapi_name() !== 'cli' && ($isAjax || $isFormSubmit || $isGetList)) {
  $ctrl = new StockController();
  $ctrl->procesarFormularios();
}
?>