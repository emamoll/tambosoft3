<?php

require_once __DIR__ . '../../DAOS/ordenDAO.php';
require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../DAOS/stockDAO.php';
require_once __DIR__ . '../../DAOS/potreroDAO.php';
require_once __DIR__ . '../../DAOS/usuarioDAO.php';
require_once __DIR__ . '../../modelos/orden/ordenModelo.php';

class OrdenController
{
  private $ordenDAO;
  private $alimentoDAO;
  private $stockDAO;
  private $potreroDAO;
  private $usuarioDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->ordenDAO = new OrdenDAO();
      $this->alimentoDAO = new AlimentoDAO();
      $this->stockDAO = new StockDAO();
      $this->potreroDAO = new PotreroDAO();
      $this->usuarioDAO = new UsuarioDAO();

    } catch (Exception $e) {
      $this->ordenDAO = null;
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

    // =========================================================
    // Manejo de peticiones GET (para AJAX de datos)
    // =========================================================

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $accion = $_GET['action'] ?? null;

      // Obtener Stock para display
      if ($accion === 'getStock') {
        $alimentoId = intval($_GET['alimentoId'] ?? 0);
        $tipoAlimentoId = intval($_GET['tipoAlimentoId'] ?? 0);

        if ($alimentoId > 0 && $tipoAlimentoId > 0) {
          $totalStock = $this->stockDAO->getTotalStockByAlimentoIdAndTipo($alimentoId, $tipoAlimentoId);
          if (ob_get_level()) {
            ob_clean();
          }
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['stock' => $totalStock]);
          exit;
        }
      }

      // Obtener la lista completa de órdenes (para llenar la tabla con JS)
      if ($accion === 'obtenerOrden') {
        $ordenes = $this->obtenerOrden();
        if (ob_get_level()) {
          ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($ordenes);
        exit;
      }

      // Obtener una orden por ID (para editar)
      if ($accion === 'getOrdenById') {
        $id = intval($_GET['id'] ?? 0);
        $orden = $this->ordenDAO->getOrdenById($id);
        if (ob_get_level()) {
          ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        // Devolver el objeto Orden en formato JSON
        echo json_encode($orden ? $orden->toArray() : null);
        exit;
      }

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

      // Sanitización y obtención de datos
      $potreroId = trim($data['potreroId'] ?? '');
      $tipoAlimentoId = trim($data['tipoAlimentoId'] ?? '');
      $alimentoId = trim($data['alimentoId'] ?? '');
      $cantidad = intval($data['cantidad'] ?? 0);

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {

        case 'registrar':

          // 1. Obtener ID del usuario logueado (desde la sesión)
          $usuarioIdLogueado = $_SESSION['usuarioId'] ?? 0;
          if ($usuarioIdLogueado == 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'Sesión de usuario no válida.'];
            break;
          }

          // 2. Verificar Rol (Tractorista ID=3)
          $usuario = $this->usuarioDAO->getUsuarioById($usuarioIdLogueado);
          $rolId = $usuario ? $usuario->getRolId() : 0;

          // El usuario debe ser Tractorista (rolId = 3)
          if ($rolId !== 3) {
            $res = ['tipo' => 'error', 'mensaje' => 'Solo los Tractoristas (Rol ID 3) pueden registrar órdenes. Su rol es: ' . $rolId];
            break;
          }

          // 3. Validación de campos
          if (
            empty($potreroId) ||
            empty($tipoAlimentoId) ||
            empty($alimentoId) ||
            $cantidad <= 0
          ) {
            $res = [
              'tipo' => 'error',
              'mensaje' => 'Debés completar Potrero, Tipo Alimento, Alimento y Cantidad (debe ser mayor a 0).'
            ];
            break;
          }

          $potreroId = intval($potreroId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);

          // 4. Crear Orden Modelo
          $orden = new Orden(
            null,
            $potreroId,
            $tipoAlimentoId,
            $alimentoId,
            $cantidad,
            $usuarioIdLogueado, // ID del usuario logueado
            1, // Estado inicial 1: Pendiente.
            date('Y-m-d'), // Placeholder, el DAO lo maneja
            date('Y-m-d'), // Placeholder, el DAO lo maneja
            date('H:i:s'), // Placeholder, el DAO lo maneja
            date('H:i:s')  // Placeholder, el DAO lo maneja
          );

          // 5. El DAO se encarga de: a) Verificar stock, b) Reducir stock FIFO, c) Registrar orden.
          $ok = $this->ordenDAO->registrarOrden($orden);

          if ($ok === false) {
            // Si el DAO devuelve false, es por stock insuficiente o error de DB.
            $stockDisponible = $this->stockDAO->getTotalStockByAlimentoIdAndTipo($alimentoId, $tipoAlimentoId);
            if ($stockDisponible < $cantidad) {
              $res = [
                'tipo' => 'error',
                'mensaje' => "Stock insuficiente. Solo hay {$stockDisponible} unidades disponibles."
              ];
            } else {
              $res = ['tipo' => 'error', 'mensaje' => 'Error al registrar la orden y/o reducir el stock (error DB).'];
            }
          } else {
            $res = ['tipo' => 'success', 'mensaje' => 'Orden registrada correctamente y stock reducido.'];
          }

          break;

        case 'modificar':
          // ... (se mantiene la lógica de modificación del DAO) ...
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido.'];
            break;
          }

          if (empty($potreroId) || empty($tipoAlimentoId) || empty($alimentoId) || empty($cantidad)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error: Debés completar Potrero, Tipo Alimento, Alimento y Cantidad.'];
            break;
          }

          $potreroId = intval($potreroId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);
          $cantidad = intval($cantidad);

          $ordenActual = $this->ordenDAO->getOrdenById($id);
          if (!$ordenActual) {
            $res = ['tipo' => 'error', 'mensaje' => 'Orden no encontrada para modificar.'];
            break;
          }

          $ordenModificada = new Orden(
            $id,
            $potreroId,
            $tipoAlimentoId,
            $alimentoId,
            $cantidad,
            $ordenActual->getUsuarioId(),
            $ordenActual->getEstadoId(),
            $ordenActual->getFechaCreacion(),
            date('Y-m-d'),
            $ordenActual->getHoraCreacion(),
            date('H:i:s')
          );

          $ok = $this->ordenDAO->modificarOrden($ordenModificada);

          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Orden modificada correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar la orden.'];

          break;

        case 'eliminar':
          // ... (código existente de eliminación) ...
          // NOTA: La eliminación de una orden NO revierte el stock automáticamente.
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar.'];
          } else {
            try {
              $ok = $this->ordenDAO->eliminarOrden($id);
              $res = $ok
                ? ['tipo' => 'success', 'mensaje' => 'Orden eliminada correctamente.']
                : ['tipo' => 'error', 'mensaje' => 'No se encontró la orden o no se pudo eliminar.'];
            } catch (mysqli_sql_exception $e) {
              if ((int) $e->getCode() === 1451) {
                $res = ['tipo' => 'error', 'mensaje' => 'No se puede eliminar porque está en uso.'];
              } else {
                $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
              }
            }
          }
          break;

        default:
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

  // ================
  // MÉTODOS DE APOYO
  // ================
  public function obtenerOrden()
  {
    if ($this->connError !== null) {
      return [];
    }

    $conn = $this->ordenDAO->getConn();

    $sql = "SELECT 
                o.id, o.potreroId, o.tipoAlimentoId, o.alimentoId, o.cantidad, o.usuarioId, o.estadoId, 
                o.fechaCreacion, o.fechaActualizacion, o.horaCreacion, o.horaActualizacion,
                p.nombre AS potreroNombre,
                ta.tipoAlimento AS tipoAlimentoNombre,
                a.nombre AS alimentoNombre,
                u.username AS usuarioNombre,
                e.descripcion AS estadoDescripcion,
                e.colores AS estadoColor
            FROM ordenes o
            LEFT JOIN potreros p ON o.potreroId = p.id
            LEFT JOIN tiposAlimentos ta ON o.tipoAlimentoId = ta.id
            LEFT JOIN alimentos a ON o.alimentoId = a.id
            LEFT JOIN usuarios u ON o.usuarioId = u.id
            LEFT JOIN estados e ON o.estadoId = e.id
            ORDER BY o.fechaCreacion DESC, o.horaCreacion DESC";

    $result = $conn->query($sql);

    $ordenes = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $ordenes[] = $row;
      }
    }

    return $ordenes;
  }

  // Métodos auxiliares para llenar los SELECTs del formulario
  public function obtenerTodosLosPotreros()
  {
    if ($this->connError !== null) {
      return [];
    }
    $potreros = $this->potreroDAO->getAllPotreros();
    return array_map(fn($p) => ['id' => $p->getId(), 'nombre' => $p->getNombre()], $potreros);
  }

  public function obtenerTiposAlimentos()
  {
    if ($this->connError !== null) {
      return [];
    }
    // Usamos AlimentoDAO para obtener los tipos de alimentos, ya que tiene la lógica de creación de tabla.
    $sql = "SELECT id, tipoAlimento FROM tiposAlimentos ORDER BY id";
    $result = $this->alimentoDAO->getConn()->query($sql);
    $tipos = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $tipos[] = $row;
      }
    }
    return $tipos;
  }

  public function obtenerTodosLosAlimentos()
  {
    if ($this->connError !== null) {
      return [];
    }
    $alimentos = $this->alimentoDAO->getAllAlimentos();
    return array_map(fn($a) => ['id' => $a->getId(), 'nombre' => $a->getNombre(), 'tipoAlimentoId' => $a->getTipoAlimentoId()], $alimentos);
  }

  public function getOrdenById($id)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenById($id);
  }

  public function getOrdenByPotreroId($potreroId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByPotreroId($potreroId);
  }

  public function getOrdenByTipoAlimentoId($tipoAlimentoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByTipoAlimentoId($tipoAlimentoId);
  }

  public function getOrdenByAlimentoId($alimentoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByAlimentoId($alimentoId);
  }

  public function getOrdenByCantidad($cantidad)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByCantidad($cantidad);
  }

  public function getOrdenByUsuarioId($usuarioId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByUsuarioId($usuarioId);
  }

  public function getOrdenByEstadoId($estadoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByEstadoId($estadoId);
  }
}

// PUNTO DE ENTRADA PRINCIPAL
if (php_sapi_name() !== 'cli') {
  $isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    ||
    ($_SERVER['REQUEST_METHOD'] === 'POST')
    ||
    ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) // Permitir GET para AJAX
  );

  $ctrl = new OrdenController();

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