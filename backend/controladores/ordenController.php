<?php

require_once __DIR__ . '../../DAOS/ordenDAO.php';
require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../DAOS/stockDAO.php';
require_once __DIR__ . '../../DAOS/potreroDAO.php';
require_once __DIR__ . '../../DAOS/usuarioDAO.php';
require_once __DIR__ . '../../DAOS/almacenDAO.php';
require_once __DIR__ . '../../modelos/orden/ordenModelo.php';

class OrdenController
{
  private $ordenDAO;
  private $alimentoDAO;
  private $stockDAO;
  private $potreroDAO;
  private $usuarioDAO;
  private $almacenDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->ordenDAO = new OrdenDAO();
      $this->alimentoDAO = new AlimentoDAO();
      $this->stockDAO = new StockDAO();
      $this->potreroDAO = new PotreroDAO();
      $this->usuarioDAO = new UsuarioDAO();
      $this->almacenDAO = new AlmacenDAO();

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

      // Obtener Stock para display (MODIFICADO para incluir almacenId)
      if ($accion === 'getStock') {
        $almacenId = intval($_GET['almacenId'] ?? 0);
        $alimentoId = intval($_GET['alimentoId'] ?? 0);
        $tipoAlimentoId = intval($_GET['tipoAlimentoId'] ?? 0);

        if ($almacenId > 0 && $alimentoId > 0 && $tipoAlimentoId > 0) {
          $totalStock = $this->stockDAO->getTotalStockByAlimentoIdAndTipoAndAlmacen($alimentoId, $tipoAlimentoId, $almacenId);
          if (ob_get_level()) {
            ob_clean();
          }
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['stock' => $totalStock]);
          exit;
        } else {
          if (ob_get_level()) {
            ob_clean();
          }
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['stock' => 0]);
          exit;
        }
      }

      // Obtener Alimentos con Stock por Almacén y Tipo (NUEVO para el filtro en cascada del front)
      if ($accion === 'getAlimentosConStock') {
        $almacenId = intval($_GET['almacenId'] ?? 0);
        $tipoAlimentoId = intval($_GET['tipoAlimentoId'] ?? 0);

        if ($almacenId > 0 && $tipoAlimentoId > 0) {
          $alimentos = $this->stockDAO->getAlimentosConStockByAlmacenIdAndTipoId($almacenId, $tipoAlimentoId);
          if (ob_get_level()) {
            ob_clean();
          }
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode($alimentos);
          exit;
        } else {
          if (ob_get_level()) {
            ob_clean();
          }
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode([]);
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
      $almacenId = trim($data['almacenId'] ?? '');
      $tipoAlimentoId = trim($data['tipoAlimentoId'] ?? '');
      $alimentoId = trim($data['alimentoId'] ?? '');
      $cantidad = intval($data['cantidad'] ?? 0);
      $usuarioIdForm = intval($data['usuarioId'] ?? 0); // Para el nuevo select de tractorista

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {

        case 'registrar':

          // 1. Obtener ID del usuario a usar (formulario o sesión)
          $usuarioIdLogueado = $_SESSION['usuarioId'] ?? 0;
          $usuarioIdFinal = $usuarioIdForm > 0 ? $usuarioIdForm : $usuarioIdLogueado;

          if ($usuarioIdFinal == 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'Sesión de usuario no válida o usuario no seleccionado.'];
            break;
          }

          // 2. Verificar Rol (Tractorista ID=3) del usuario FINAL
          $usuario = $this->usuarioDAO->getUsuarioById($usuarioIdFinal);
          $rolId = $usuario ? $usuario->getRolId() : 0;

          // El usuario debe ser Tractorista (rolId = 3)
          if ($rolId !== 3) {
            $res = ['tipo' => 'error', 'mensaje' => 'Solo los Tractoristas (Rol ID 3) pueden registrar órdenes. Su rol es: ' . $rolId];
            break;
          }

          // 3. Validación de campos
          if (
            empty($potreroId) ||
            empty($almacenId) ||
            empty($tipoAlimentoId) ||
            empty($alimentoId) ||
            $cantidad <= 0
          ) {
            $res = [
              'tipo' => 'error',
              'mensaje' => 'Debés completar Potrero, Almacén, Tipo Alimento, Alimento y Cantidad (debe ser mayor a 0).'
            ];
            break;
          }

          $potreroId = intval($potreroId);
          $almacenId = intval($almacenId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);

          // 4. Crear Orden Modelo
          $orden = new Orden(
            null,
            $potreroId,
            $almacenId, // Almacén ID
            $tipoAlimentoId,
            $alimentoId,
            $cantidad,
            $usuarioIdFinal, // ID del usuario final
            1, // Estado inicial 1: Pendiente.
            date('Y-m-d'), // Placeholder, el DAO lo maneja
            date('Y-m-d'), // Placeholder, el DAO lo maneja
            date('H:i:s'), // Placeholder, el DAO lo maneja
            date('H:i:s')  // Placeholder, el DAO lo maneja
          );

          // 5. El DAO se encarga de: a) Verificar stock (por almacén), b) Reducir stock FIFO (por almacén), c) Registrar orden.
          $ok = $this->ordenDAO->registrarOrden($orden);

          if ($ok === false) {
            // Si el DAO devuelve false, es por stock insuficiente o error de DB.
            $stockDisponible = $this->stockDAO->getTotalStockByAlimentoIdAndTipoAndAlmacen($alimentoId, $tipoAlimentoId, $almacenId);
            if ($stockDisponible < $cantidad) {
              $res = [
                'tipo' => 'error',
                'mensaje' => "Stock insuficiente en el almacén. Solo hay {$stockDisponible} unidades disponibles."
              ];
            } else {
              $res = ['tipo' => 'error', 'mensaje' => 'Error al registrar la orden y/o reducir el stock (error DB).'];
            }
          } else {
            $res = ['tipo' => 'success', 'mensaje' => 'Orden registrada correctamente y stock reducido.'];
          }

          break;

        case 'modificar':

          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido.'];
            break;
          }

          if (empty($potreroId) || empty($almacenId) || empty($tipoAlimentoId) || empty($alimentoId) || empty($cantidad)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error: Debés completar Potrero, Almacén, Tipo Alimento, Alimento y Cantidad.'];
            break;
          }

          $potreroId = intval($potreroId);
          $almacenId = intval($almacenId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);
          $cantidadNueva = intval($cantidad);

          $ordenActual = $this->ordenDAO->getOrdenById($id);
          if (!$ordenActual) {
            $res = ['tipo' => 'error', 'mensaje' => 'Orden no encontrada para modificar.'];
            break;
          }

          $cantidadOriginal = $ordenActual->getCantidad();
          $almacenOriginal = $ordenActual->getAlmacenId();
          $alimentoOriginal = $ordenActual->getAlimentoId();
          $tipoAlimentoOriginal = $ordenActual->getTipoAlimentoId();

          $stockAfectado = true;

          // 1. Verificar si el almacén, tipo o alimento han cambiado (no permitido por ahora)
          if ($almacenOriginal != $almacenId || $alimentoOriginal != $alimentoId || $tipoAlimentoOriginal != $tipoAlimentoId) {
            $res = ['tipo' => 'error', 'mensaje' => 'No se permite cambiar el almacén, tipo o alimento en la modificación de la orden para garantizar la integridad del stock. Por favor, elimine y registre una nueva orden.'];
            break;
          }

          // 2. Manejo del Stock: Comparar cantidad nueva con la original
          $diferenciaCantidad = $cantidadOriginal - $cantidadNueva;

          if ($diferenciaCantidad > 0) { // La cantidad se redujo (roll back parcial)
            $cantidadDevuelta = $diferenciaCantidad;
            // Devolver la cantidad al stock del almacén original (usando actualizarStock)
            $stockAfectado = $this->stockDAO->aumentarStockParaRollback($almacenOriginal, $tipoAlimentoOriginal, $alimentoOriginal, $cantidadDevuelta);

          } elseif ($diferenciaCantidad < 0) { // La cantidad se aumentó (requiere más stock)
            $cantidadRequeridaAdicional = abs($diferenciaCantidad);

            // Verificar stock disponible para la cantidad adicional
            $stockDisponible = $this->stockDAO->getTotalStockByAlimentoIdAndTipoAndAlmacen($alimentoId, $tipoAlimentoId, $almacenId);

            if ($stockDisponible < $cantidadRequeridaAdicional) {
              $res = [
                'tipo' => 'error',
                'mensaje' => "Stock insuficiente en el almacén para el aumento solicitado. Solo hay {$stockDisponible} unidades disponibles para retirar."
              ];
              break;
            }

            // Retirar el stock adicional usando FIFO
            $stockAfectado = $this->stockDAO->reducirStockFIFO($alimentoId, $tipoAlimentoId, $cantidadRequeridaAdicional, $almacenId);

          }

          if (!$stockAfectado) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error de base de datos al actualizar el stock. La orden no fue modificada.'];
            break;
          }

          // 3. Modificación de la Orden en la DB
          $ordenModificada = new Orden(
            $id,
            $potreroId,
            $almacenId,
            $tipoAlimentoId,
            $alimentoId,
            $cantidadNueva,
            $ordenActual->getUsuarioId(),
            $ordenActual->getEstadoId(),
            $ordenActual->getFechaCreacion(),
            date('Y-m-d'),
            $ordenActual->getHoraCreacion(),
            date('H:i:s')
          );

          $ok = $this->ordenDAO->modificarOrden($ordenModificada);

          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Orden modificada correctamente y stock ajustado.']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar la orden (DB error).'];

          break;

        case 'eliminar':
          // 1. Obtener la orden para obtener los detalles de stock
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar.'];
            break;
          }

          $ordenActual = $this->ordenDAO->getOrdenById($id);
          if (!$ordenActual) {
            $res = ['tipo' => 'error', 'mensaje' => 'Orden no encontrada para eliminar.'];
            break;
          }

          $almacenId = $ordenActual->getAlmacenId();
          $tipoAlimentoId = $ordenActual->getTipoAlimentoId();
          $alimentoId = $ordenActual->getAlimentoId();
          $cantidad = $ordenActual->getCantidad();

          $stockAfectado = true;

          // 2. Devolver la cantidad al stock (Rollback completo, usando actualizarStock)
          $stockAfectado = $this->stockDAO->aumentarStockParaRollback($almacenId, $tipoAlimentoId, $alimentoId, $cantidad);

          if (!$stockAfectado) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error de base de datos al devolver el stock. La orden NO fue eliminada.'];
            break;
          }

          // 3. Eliminar la orden
          try {
            $ok = $this->ordenDAO->eliminarOrden($id);
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Orden eliminada correctamente y stock devuelto.']
              : ['tipo' => 'error', 'mensaje' => 'No se encontró la orden o no se pudo eliminar.'];
          } catch (mysqli_sql_exception $e) {
            // Manejo de error de DB (FK constraint)
            if ((int) $e->getCode() === 1451) {
              $res = ['tipo' => 'error', 'mensaje' => 'No se puede eliminar porque está en uso.'];
            } else {
              $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
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
                o.id, o.potreroId, o.almacenId, o.tipoAlimentoId, o.alimentoId, o.cantidad, o.usuarioId, o.estadoId, 
                o.fechaCreacion, o.fechaActualizacion, o.horaCreacion, o.horaActualizacion,
                p.nombre AS potreroNombre,
                al.nombre AS almacenNombre,
                ta.tipoAlimento AS tipoAlimentoNombre,
                a.nombre AS alimentoNombre,
                u.username AS usuarioNombre,
                e.descripcion AS estadoDescripcion,
                e.colores AS estadoColor
            FROM ordenes o
            LEFT JOIN potreros p ON o.potreroId = p.id
            LEFT JOIN almacenes al ON o.almacenId = al.id
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

  public function obtenerTodosLosAlmacenes()
  {
    if ($this->connError !== null) {
      return [];
    }
    $almacenes = $this->almacenDAO->getAllAlmacenes();
    return array_map(fn($a) => ['id' => $a->getId(), 'nombre' => $a->getNombre()], $almacenes);
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

  public function obtenerTractoristas()
  {
    if ($this->connError !== null) {
      return [];
    }
    // Rol ID 3 es Tractorista
    $tractoristas = $this->usuarioDAO->getUsuariosByRolId(3);
    return $tractoristas;
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