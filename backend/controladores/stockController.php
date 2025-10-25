<?php
require_once __DIR__ . '../../DAOS/stockDAO.php';
require_once __DIR__ . '../../modelos/stock/stockModelo.php';

class StockController
{
  private $stockDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->stockDAO = new StockDAO();
    } catch (Exception $e) {
      $this->stockDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  public function procesarFormularios()
  {
    if ($this->connError !== null) {
      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'tipo' => 'error',
        'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError
      ]);
      exit;
    }

    $accion = $_GET['action'] ?? null;

    //  LISTAR STOCKS (con filtros)

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'list') {

      // Función auxiliar: convierte parámetros GET en arrays de enteros
      $getArrayOfInts = function ($key) {
        if (!isset($_GET[$key]))
          return null;
        $val = $_GET[$key];
        // Asegura que siempre sea un array para procesar los [] de JS
        if (!is_array($val))
          $val = [$val];
        // Convierte a int y elimina valores vacíos (como el string vacío de un checkbox no marcado)
        $ints = array_map('intval', array_filter($val, fn($v) => $v !== ''));
        return count($ints) > 0 ? $ints : null;
      };

      // Filtros admitidos (ahora esperan arrays de IDs o null)
      $filtros = [
        'almacenId' => $getArrayOfInts('almacenId'),
        'alimentoId' => $getArrayOfInts('alimentoId'),
        'produccionInterna' => $getArrayOfInts('produccionInterna')
      ];

      $stocks = $this->stockDAO->listar($filtros);
      $out = [];

      foreach ($stocks as $stock) {
        $out[] = [
          'id' => $stock['id'],
          'almacenId' => $stock['almacenId'],
          'alimentoId' => $stock['alimentoId'],
          'cantidad' => $stock['cantidad'],
          'produccionInterna' => $stock['produccionInterna'],
          'proveedorId' => $stock['proveedorId'],
          'precio' => $stock['precio'],
          'fechaIngreso' => $stock['fechaIngreso'],
          // Es útil devolver estos nombres para la tabla sin tener que hacer otra consulta o mapeo en JS
          'almacenNombre' => $stock['almacenNombre'] ?? '',
          'alimentoNombre' => $stock['alimentoNombre'] ?? '',
          'proveedorNombre' => $stock['campoNombre'] ?? '',
        ];
      }

      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($out);
      exit;
    }

    // ABM STOCK

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $data = $_POST;
      if (empty($data)) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?? [];
      }

      $accion = $data['accion'] ?? null;
      $id = intval($data['id'] ?? 0);
      $almacenId = trim($data['almacenId'] ?? '');
      $alimentoId = trim($data['alimentoId'] ?? '');
      $cantidad = intval($data['cantidad'] ?? 0);
      $produccionInterna = trim($data['produccionInterna'] ?? '');
      $proveedorId = trim($data['proveedorId'] ?? '');
      $precio = trim($data['precio'] ?? '');
      $fechaIngreso = trim($data['fechaIngreso'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {

        // REGISTRAR STOCK
        case 'registrar':
          if (empty($almacenId) || empty($alimentoId) || empty($cantidad) || empty($produccionInterna)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios.'];
          } else {
            $ok = $this->stockDAO->registrarStock(
              new Stock(id: null, $almacenId, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $precio, $fechaIngreso)
            );
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Stock registrado correctamente.']
              : ['tipo' => 'error', 'mensaje' => 'Error al registrar el stock.'];
          }
          break;

        // MODIFICAR STOCK
        case 'modificar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido.'];
          } elseif (empty($almacenId) || empty($alimentoId) || empty($cantidad) || empty($produccionInterna)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios.'];
          } else {
            $ok = $this->stockDAO->modificarStock(
              new Stock($id, $almacenId, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $precio, $fechaIngreso)
            );
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'stock modificado correctamente.']
              : ['tipo' => 'error', 'mensaje' => 'Error al modificar el stock.'];
          }
          break;

        // ELIMINAR STOCK
        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar.'];
          } else {
            try {
              $ok = $this->stockDAO->eliminarStock($id);
              $res = $ok
                ? ['tipo' => 'success', 'mensaje' => 'Stock eliminado correctamente.']
                : ['tipo' => 'error', 'mensaje' => 'No se encontró el stock o no se pudo eliminar.'];
            } catch (mysqli_sql_exception $e) {
              if ((int) $e->getCode() === 1451) {
                $res = ['tipo' => 'error', 'mensaje' => 'No se puede eliminar porque está en uso.'];
              } else {
                $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
              }
            }
          }
          break;
      }

      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($res);
      exit;
    }
  }

  // MÉTODOS DE APOYO
  public function obtenerStock()
  {
    if ($this->connError !== null)
      return [];
    return $this->stockDAO->getAllStocks();
  }

  public function getStockById($id)
  {
    if ($this->connError !== null)
      return null;
    return $this->stockDAO->getStockById($id);
  }

  public function getStockByAlmacenId($almacenId)
  {
    if ($this->connError !== null)
      return null;
    return $this->stockDAO->getStockByAlmacenId($almacenId);
  }

  public function getStockByAlimentoId($alimentoId)
  {
    if ($this->connError !== null)
      return null;
    return $this->stockDAO->getStockByAlimentoId($alimentoId);
  }

  public function getStockByProduccion($produccionInterna)
  {
    if ($this->connError !== null)
      return null;
    return $this->stockDAO->getStockByProduccion($produccionInterna);
  }
}

// PUNTO DE ENTRADA PRINCIPAL
if (php_sapi_name() !== 'cli') {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

  if ($isAjax) {
    ob_start();
    $ctrl = new StockController();
    $ctrl->procesarFormularios();
  }

  if (isset($_POST['accion'])) {
    $ctrl = new StockController();
    $ctrl->procesarFormularios();
  }
}