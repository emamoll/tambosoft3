<?php
date_default_timezone_set('America/Argentina/Cordoba');

require_once __DIR__ . '../../DAOS/stockDAO.php';
require_once __DIR__ . '../../modelos/stock/stockModelo.php';
require_once __DIR__ . '../../modelos/ingreso/ingresoModelo.php';
require_once __DIR__ . '../../DAOS/ingresoDAO.php';

class StockController
{
  private $stockDAO;
  private $ingresoDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->stockDAO = new StockDAO();
      $this->ingresoDAO = new IngresoDAO();

    } catch (Exception $e) {
      $this->stockDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  public function procesarFormularios()
  {
    // Manejo de error de conexión
    if ($this->connError !== null) {
      if (ob_get_level()) {
        ob_clean();
      }
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'tipo' => 'error',
        'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError
      ]);
      exit;
    }

    $accion = $_GET['action'] ?? null;

    // ===============================
    // LISTAR STOCKS (GET, con filtros)
    // ===============================
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'list') {

      // Función auxiliar: convierte parámetros GET en arrays de enteros
      $getArrayOfInts = function ($key) {
        if (!isset($_GET[$key])) {
          return null;
        }
        $val = $_GET[$key];
        if (!is_array($val)) {
          $val = [$val];
        }
        $ints = array_map('intval', array_filter($val, fn($v) => $v !== ''));
        return count($ints) > 0 ? $ints : null;
      };

      // Filtros admitidos (arrays de IDs, fechas o null)
      $filtros = [
        'almacenId' => $getArrayOfInts('almacenId'),
        'tipoAlimentoId' => $getArrayOfInts('tipoAlimentoId'),
        'alimentoId' => $getArrayOfInts('alimentoId'),
        'proveedorId' => $getArrayOfInts('proveedorId'),
        'produccionInterna' => $getArrayOfInts('produccionInterna'),
        'fechaMin' => $_GET['fechaMin'] ?? null,
        'fechaMax' => $_GET['fechaMax'] ?? null
      ];

      try {
        $stocks = $this->stockDAO->listar($filtros);
        $out = [];

        foreach ($stocks as $stock) {
          $out[] = [
            'id' => (int) $stock['id'],
            'almacenId' => (int) $stock['almacenId'],
            'tipoAlimentoId' => (int) $stock['tipoAlimentoId'],
            'alimentoId' => (int) $stock['alimentoId'],
            'cantidad' => (int) $stock['cantidad'],
            'produccionInterna' => (int) $stock['produccionInterna'],
            'proveedorId' => $stock['proveedorId'] !== null ? (int) $stock['proveedorId'] : null,
            'precio' => $stock['precio'] !== null ? (float) $stock['precio'] : null,
            'fechaIngreso' => $stock['fechaIngreso'] ?? null,
            'almacenNombre' => $stock['almacenNombre'] ?? '',
            'tipoAlimentoNombre' => $stock['tipoAlimentoNombre'] ?? '',
            'alimentoNombre' => $stock['alimentoNombre'] ?? '',
            'proveedorNombre' => $stock['proveedorNombre'] ?? '',
          ];
        }

        if (ob_get_level()) {
          ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($out);
        exit;

      } catch (Throwable $e) {
        if (ob_get_level()) {
          ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
          'tipo' => 'error',
          'mensaje' => 'Error interno del servidor al listar stock: ' . $e->getMessage(),
          'file' => $e->getFile(),
          'line' => $e->getLine()
        ]);
        exit;
      }
    }

    // ===============================
    // OBTENER UNA FILA POR ID (GET)
    // ===============================
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'get') {

      $id = intval($_GET['id'] ?? 0);

      if (!$id) {
        if (ob_get_level())
          ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
          'tipo' => 'error',
          'mensaje' => 'ID inválido.'
        ]);
        exit;
      }

      try {
        $stock = $this->stockDAO->getStockById($id);

        if (!$stock) {
          if (ob_get_level())
            ob_clean();
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode([
            'tipo' => 'error',
            'mensaje' => 'No se encontró el registro.'
          ]);
          exit;
        }

        $out = [
          'id' => (int) $stock->getId(),
          'almacenId' => (int) $stock->getAlmacenId(),
          'tipoAlimentoId' => (int) $stock->getTipoAlimentoId(),
          'alimentoId' => (int) $stock->getAlimentoId(),
          'cantidad' => (int) $stock->getCantidad(),
          'produccionInterna' => (int) $stock->getProduccionInterna(),
          'proveedorId' => $stock->getProveedorId() !== null ? (int) $stock->getProveedorId() : null,
          'precio' => $stock->getPrecio() !== null ? (float) $stock->getPrecio() : null,
          'fechaIngreso' => $stock->getFechaIngreso()
        ];

        if (ob_get_level())
          ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($out);
        exit;

      } catch (Throwable $e) {
        if (ob_get_level())
          ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
          'tipo' => 'error',
          'mensaje' => 'Error al obtener el registro: ' . $e->getMessage()
        ]);
        exit;
      }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'detalleGrupo') {
      $almacenId = intval($_GET['almacenId'] ?? 0);
      $tipoAlimentoId = intval($_GET['tipoAlimentoId'] ?? 0);
      $alimentoId = intval($_GET['alimentoId'] ?? 0);
      $produccionInterna = intval($_GET['produccionInterna'] ?? 0);
      $proveedorId = $_GET['proveedorId'] ?? null;
      if ($proveedorId !== null && $proveedorId !== '') {
        $proveedorId = intval($proveedorId);
      } else {
        $proveedorId = null;
      }

      $fechaMin = $_GET['fechaMin'] ?? null;
      $fechaMax = $_GET['fechaMax'] ?? null;
      $detalles = $this->stockDAO->listarDetalleGrupo(
        $almacenId,
        $tipoAlimentoId,
        $alimentoId,
        $produccionInterna,
        $proveedorId,
        $fechaMin,
        $fechaMax
      );

      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($detalles);
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'get') {
      $id = intval($_GET['id'] ?? 0);
      $stock = $this->stockDAO->getStockById($id);
      if (!$stock) {
        if (ob_get_level())
          ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'No existe el stock solicitado']);
        exit;
      }

      $out = [
        'id' => $stock->getId(),
        'almacenId' => $stock->getAlmacenId(),
        'tipoAlimentoId' => $stock->getTipoAlimentoId(),
        'alimentoId' => $stock->getAlimentoId(),
        'cantidad' => $stock->getCantidad(),
        'produccionInterna' => $stock->getProduccionInterna(),
        $stock->getProveedorId(),
        'precio' => $stock->getPrecio(),
        'fechaIngreso' => $stock->getFechaIngreso(),
      ];

      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($out);
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'detalle') {

      $almacenId = intval($_GET['almacenId']);
      $tipoAlimentoId = intval($_GET['tipoAlimentoId']);
      $alimentoId = intval($_GET['alimentoId']);
      $produccionInterna = intval($_GET['produccionInterna']);
      $proveedorId = $_GET['proveedorId'];
      if ($proveedorId === "" || $proveedorId === "0") {
        $proveedorId = null;
      }

      $detalle = $this->stockDAO->getDetalleGrupo(
        $almacenId,
        $tipoAlimentoId,
        $alimentoId,
        $produccionInterna,
        $proveedorId
      );

      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json');
      echo json_encode($detalle);
      exit;
    }

    // ===============================
    // ABM STOCK (POST)
    // ===============================
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
      $tipoAlimentoId = trim($data['tipoAlimentoId'] ?? '');
      $cantidad = intval($data['cantidad'] ?? 0);
      $produccionInterna = trim($data['produccionInterna'] ?? '');
      $proveedorId = trim($data['proveedorId'] ?? '');
      $precio = trim($data['precio'] ?? '');
      $fechaIngreso = trim($data['fechaIngreso'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {

        case 'registrar':
          $produccionInternaInt = intval($produccionInterna);

          if (
            empty($almacenId) ||
            empty($tipoAlimentoId) ||
            empty($alimentoId) ||
            empty($cantidad) ||
            empty($fechaIngreso) ||
            empty($precio) // El precio ahora se valida siempre aquí
          ) {
            $res = [
              'tipo' => 'error',
              'mensaje' => 'Debés completar Campo, Tipo Alimento, Alimento, Cantidad, Precio y Fecha de Ingreso.'
            ];
            break;
          }

          $almacenId = intval($almacenId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);
          $cantidad = intval($cantidad);
          $precio = floatval($precio);

          if ($produccionInternaInt === 1) {
            // Producción interna: proveedorId debe ser NULL
            $proveedorId = null;
          } else {
            // Producción comprada: proveedorId es obligatorio
            if (empty($proveedorId)) {
              $res = [
                'tipo' => 'error',
                'mensaje' => 'Para producción comprada, Proveedor es obligatorio.'
              ];
              break;
            }
            // El precio ya está validado arriba, solo casteamos el proveedor.
            $proveedorId = intval($proveedorId);
          }

          $this->ingresoDAO->registrarIngreso(new Ingreso(
            null,
            $almacenId,
            $tipoAlimentoId,
            $alimentoId,
            $cantidad,
            $produccionInternaInt,
            $proveedorId,
            $precio,
            $fechaIngreso
          ));

          $ok = $this->stockDAO->registrarStock(
            new Stock(
              null,
              $almacenId,
              $tipoAlimentoId,
              $alimentoId,
              $cantidad,
              $produccionInternaInt,
              $proveedorId,
              $precio,
              $fechaIngreso
            )
          );

          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Stock registrado correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrar el stock.'];

          break;

        case 'modificar':
          $produccionInternaInt = intval($produccionInterna);

          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido.'];
            break;
          }

          // Se valida que todos los campos obligatorios, incluyendo precio, estén llenos.
          // NOTA: Se ha corregido la sintaxis de empty() en la condición:
          if (empty($almacenId) || empty($tipoAlimentoId) || empty($alimentoId) || empty($cantidad) || empty($fechaIngreso) || empty($precio)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error: Debés completar Campo, Tipo Alimento, Alimento, Cantidad, Precio y Fecha de Ingreso.'];
            break;
          }

          $almacenId = intval($almacenId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);
          $cantidad = intval($cantidad);
          $precio = floatval($precio);

          if ($produccionInternaInt === 1) {
            $proveedorId = null;
          } else {
            // Producción comprada: proveedorId es obligatorio
            if (empty($proveedorId)) {
              $res = ['tipo' => 'error', 'mensaje' => 'Error: Para producción comprada, Proveedor es obligatorio.'];
              break;
            }
            $proveedorId = intval($proveedorId);
          }

          $this->ingresoDAO->modificarIngreso(
            new Ingreso(
              $id,
              $almacenId,
              $tipoAlimentoId,
              $alimentoId,
              $cantidad,
              $produccionInternaInt,
              $proveedorId,
              $precio,
              $fechaIngreso
            )
          );

          $ok = $this->stockDAO->modificarStock(
            new Stock(
              $id,
              $almacenId,
              $tipoAlimentoId,
              $alimentoId,
              $cantidad,
              $produccionInternaInt,
              $proveedorId,
              $precio,
              $fechaIngreso
            )
          );

          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Stock modificado correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar el stock.'];

          break;

        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar.'];
          } else {
            try {
              $this->ingresoDAO->eliminarIngreso($id);
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

      if (ob_get_level()) {
        ob_clean();
      }
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($res);
      exit;
    }
  }

  public function listarStock(array $filtros = []): array
  {
    if ($this->connError !== null) {
      return [];
    }

    return $this->stockDAO->listar($filtros);
  }

  // ================
  // MÉTODOS DE APOYO
  // ================
  public function obtenerStock()
  {
    if ($this->connError !== null) {
      return [];
    }
    return $this->stockDAO->getAllStocks();
  }

  public function getStockById($id)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->stockDAO->getStockById($id);
  }

  public function getStockByAlmacenId($almacenId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->stockDAO->getStockByAlmacenId($almacenId);
  }

  public function getStockByTipoAlimentoId($tipoAlimentoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->stockDAO->getStockByTipoAlimentoId($tipoAlimentoId);
  }

  public function getStockByAlimentoId($alimentoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->stockDAO->getStockByAlimentoId($alimentoId);
  }

  public function getStockByProduccion($produccionInterna)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->stockDAO->getStockByProduccion($produccionInterna);
  }
}

// PUNTO DE ENTRADA PRINCIPAL
if (php_sapi_name() !== 'cli') {
  $isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    ||
    ($_SERVER['REQUEST_METHOD'] === 'POST')
  );

  $ctrl = new StockController();

  if ($isAjax) {
    ob_start();
    $ctrl->procesarFormularios();
    exit;
  }

  if (isset($_POST['accion'])) {
    $ctrl->procesarFormularios();
    exit;
  }
}