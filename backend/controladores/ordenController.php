<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

date_default_timezone_set('America/Argentina/Buenos_Aires'); // FIJADO ZONA HORARIA A ARGENTINA

require_once __DIR__ . '../../DAOS/ordenDAO.php';
require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../DAOS/stockDAO.php';
require_once __DIR__ . '../../DAOS/potreroDAO.php';
require_once __DIR__ . '../../DAOS/usuarioDAO.php';
require_once __DIR__ . '../../DAOS/almacenDAO.php';
require_once __DIR__ . '../../DAOS/ordenAuditoriaDAO.php';
require_once __DIR__ . '../../DAOS/ordenConsumoStockDAO.php'; // NUEVO REQUIRE
require_once __DIR__ . '../../modelos/orden/ordenModelo.php';
require_once __DIR__ . '../../modelos/ordenConsumoStock/ordenConsumoStockModelo.php'; // NUEVO REQUIRE

// Detectar AJAX una sola vez
$isAjax = (
  !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (isset($_GET['ajax']) && $_GET['ajax'] === '1')
  || ($_SERVER['REQUEST_METHOD'] === 'POST'); // Los POST son tratados como AJAX para la respuesta JSON

// 🚨 CORRECCIÓN CRÍTICA: Limpiar el buffer y preparar encabezados para AJAX desde el inicio
if (php_sapi_name() !== 'cli' && $isAjax) {
  // 1. Limpiar cualquier salida previa (BOM, espacios en blanco o notices de includes)
  while (ob_get_level()) {
    ob_end_clean();
  }
  // 2. Establecer el tipo de contenido antes de cualquier posible output
  if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
  }
}

class OrdenController
{
  private $ordenDAO;
  private $alimentoDAO;
  private $stockDAO;
  private $potreroDAO;
  private $usuarioDAO;
  private $almacenDAO;
  private $ordenAuditoriaDAO;
  private $ordenConsumoStockDAO; // NUEVA PROPIEDAD
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
      $this->ordenAuditoriaDAO = new OrdenAuditoriaDAO();
      $this->ordenConsumoStockDAO = new OrdenConsumoStockDAO(); // INICIALIZACIÓN

    } catch (Exception $e) {
      $this->ordenDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  // Auditorias
  private function registrarAuditoria(
    int $ordenId,
    int $usuarioId,
    string $accion,
    string $motivo = '',
    ?int $cantidadAnterior = null,
    ?int $cantidadNueva = null
  ): void {
    $auditoria = new OrdenAuditoria(
      null,
      $ordenId,
      $usuarioId,
      $accion,
      $motivo,
      $cantidadAnterior,
      $cantidadNueva,
      null // fecha la maneja la BD
    );

    $this->ordenAuditoriaDAO->registrarAuditoria($auditoria);
  }

  public function procesarFormularios()
  {
    // Manejo de error de conexión
    if ($this->connError !== null) {
      // Si hay error de conexión, se imprime JSON y se sale
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

        header('Content-Type: application/json; charset=utf-8');
        $almacenId = intval($_GET['almacenId'] ?? 0);
        $alimentoId = intval($_GET['alimentoId'] ?? 0);
        $tipoAlimentoId = intval($_GET['tipoAlimentoId'] ?? 0);

        if ($almacenId > 0 && $alimentoId > 0 && $tipoAlimentoId > 0) {
          $totalStock = $this->stockDAO->getTotalStockByAlimentoIdAndTipoAndAlmacen($alimentoId, $tipoAlimentoId, $almacenId);
          echo json_encode(['stock' => $totalStock]);
          exit;
        } else {
          echo json_encode(['stock' => 0]);
          exit;
        }
      }

      if ($accion === 'getTiposAlimentoPorAlmacen') {

        header('Content-Type: application/json; charset=utf-8');

        $almacenId = intval($_GET['almacenId'] ?? 0);

        if ($almacenId <= 0) {
          echo json_encode([]);
          exit;
        }

        $tipos = $this->ordenDAO->getTiposAlimentoPorAlmacen($almacenId);

        echo json_encode($tipos);
        exit;
      }

      // Obtener Alimentos con Stock por Almacén y Tipo (NUEVO para el filtro en cascada del front)
      if ($accion === 'getAlimentosConStock') {
        header('Content-Type: application/json; charset=utf-8');
        $almacenId = intval($_GET['almacenId'] ?? 0);
        $tipoAlimentoId = intval($_GET['tipoAlimentoId'] ?? 0);

        if ($almacenId > 0 && $tipoAlimentoId > 0) {
          // Si todo está bien, devuelve la lista de alimentos con stock > 0 para ese almacén y tipo.
          $alimentos = $this->stockDAO->getAlimentosConStockByAlmacenIdAndTipoId($almacenId, $tipoAlimentoId);
          echo json_encode($alimentos);
          exit;
        } else {
          echo json_encode([]);
          exit;
        }
      }

      // Obtener la lista completa de órdenes (para llenar la tabla con JS)
      if ($accion === 'obtenerOrden') {
        $usuarioIdFiltro = intval($_GET['usuarioId'] ?? 0); // Captura el ID del usuario si se envía

        $ordenes = $this->ordenDAO->listarOrdenes(
          $usuarioIdFiltro > 0 ? $usuarioIdFiltro : null
        );

        echo json_encode($ordenes);
        exit;
      }

      // Obtener una orden por ID (para editar)
      if ($accion === 'getOrdenById') {

        header('Content-Type: application/json; charset=utf-8');

        $id = intval($_GET['id'] ?? 0);
        $orden = $this->ordenDAO->getOrdenById($id);

        $data = null;
        if ($orden) {
          // Ahora el modelo Orden tiene categoriaId. Lo usamos directamente.
          $categoriaId = $orden->getCategoriaId();

          $stockDisponible = $this->stockDAO->getTotalStockByAlimentoIdAndTipoAndAlmacen(
            $orden->getAlimentoId(),
            $orden->getTipoAlimentoId(),
            $orden->getAlmacenId()
          );

          // clave: lo que ya consumió la orden también cuenta
          $maxCantidad = $stockDisponible + $orden->getCantidad();

          $data = [
            'id' => $orden->getId(),
            'potreroId' => $orden->getPotreroId(),
            'almacenId' => $orden->getAlmacenId(),
            'tipoAlimentoId' => $orden->getTipoAlimentoId(),
            'alimentoId' => $orden->getAlimentoId(),
            'cantidad' => $orden->getCantidad(),
            'usuarioId' => $orden->getUsuarioId(),
            'estadoId' => $orden->getEstadoId(),
            'categoriaId' => $categoriaId,
            'stockDisponible' => $stockDisponible,
            'maxCantidad' => $maxCantidad
          ];
        }

        echo json_encode($data);
        exit;
      }

      if ($accion === 'obtenerAuditoriaOrden') {
        header('Content-Type: application/json; charset=utf-8');

        $ordenId = intval($_GET['id'] ?? 0);
        if ($ordenId <= 0) {
          echo json_encode([]);
          exit;
        }

        $aud = $this->ordenAuditoriaDAO->listarAuditoriaPorOrden($ordenId);
        echo json_encode($aud);
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

      // NUEVO: Obtener el nuevo estado ID para la acción de cambiar estado
      $nuevoEstadoId = intval($data['nuevoEstadoId'] ?? 0);

      // Sanitización y obtención de datos
      $categoriaIdForm = trim($data['categoriaId'] ?? '');
      $almacenId = trim($data['almacenId'] ?? '');
      $tipoAlimentoId = trim($data['tipoAlimentoId'] ?? '');
      $alimentoId = trim($data['alimentoId'] ?? '');
      $cantidad = intval($data['cantidad'] ?? 0);
      $usuarioIdForm = intval($data['usuarioId'] ?? 0);
      $motivo = trim($data['motivo'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {

        case 'cambiarEstado':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID de orden inválido.'];
            break;
          }
          $ordenActual = $this->ordenDAO->getOrdenById($id);
          if (!$ordenActual) {
            $res = ['tipo' => 'error', 'mensaje' => 'Orden no encontrada.'];
            break;
          }

          $estadoActual = $ordenActual->getEstadoId();

          // Requisito: Solo de Pendiente (1) a En preparación (2)
          if ($estadoActual == 1 && $nuevoEstadoId == 2) {
            $ok = $this->ordenDAO->actualizarEstadoOrden($id, $nuevoEstadoId);

            if ($ok) {
              $this->registrarAuditoria(
                $id,
                $_SESSION['usuarioId'],
                'CAMBIO_ESTADO',
                'Cambio de estado a En preparación'
              );
            }

            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Estado actualizado a "En preparación" correctamente.']
              : ['tipo' => 'error', 'mensaje' => 'Error al actualizar el estado de la orden.'];
          } else {
            $res = ['tipo' => 'error', 'mensaje' => 'Transición de estado no permitida. Solo de Pendiente a En preparación.'];
          }
          break;

        case 'registrar':

          // 1. Validar que el tractorista es OBLIGATORIO
          $usuarioIdFinal = $usuarioIdForm;

          if ($usuarioIdFinal == 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'El campo Tractorista es obligatorio.'];
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

          // 3. Obtener el PotreroId asociado a la Categoria seleccionada
          $potreroDetails = $this->potreroDAO->getPotreroDetailsByCategoriaId(intval($categoriaIdForm));
          $potreroId = $potreroDetails ? $potreroDetails['potreroId'] : 0;


          // 4. Validación de campos
          if (
            empty($categoriaIdForm) ||
            $potreroId == 0 || // Aseguramos que se encontró el potrero asociado
            empty($almacenId) ||
            empty($tipoAlimentoId) ||
            empty($alimentoId) ||
            $cantidad <= 0
          ) {
            $res = [
              'tipo' => 'error',
              'mensaje' => 'Debés completar Almacén, Categoría (con potrero asignado), Tipo Alimento, Alimento y Cantidad (debe ser mayor a 0).'
            ];
            break;
          }

          $almacenId = intval($almacenId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);
          $categoriaId = intval($categoriaIdForm); // El ID de la categoría es el que se almacena

          // 5. Crear Orden Modelo
          $orden = new Orden(
            null,
            $potreroId, // Usamos el potreroId encontrado
            $almacenId, // Almacén ID
            $tipoAlimentoId,
            $alimentoId,
            $cantidad,
            $usuarioIdFinal, // ID del usuario final
            1, // Estado inicial 1: Pendiente.
            $categoriaId, // NUEVO CAMPO
            date('Y-m-d'), // Placeholder, el DAO lo maneja
            date('Y-m-d'), // Placeholder, el DAO lo maneja
            date('H:i:s'), // Placeholder, el DAO lo maneja
            date('H:i:s')  // Placeholder, el DAO lo maneja
          );

          // 6. El DAO se encarga de: a) Verificar stock (por almacén), b) Reducir stock FIFO (por almacén), c) Registrar orden.
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
              // El error de DB se maneja dentro del DAO
              $res = ['tipo' => 'error', 'mensaje' => 'Error al registrar la orden y/o reducir el stock (error de transacción DB).'];
            }
          } else {
            $res = ['tipo' => 'success', 'mensaje' => 'Orden registrada correctamente y stock reducido.'];
          }

          break;

        case 'modificar':

          // ===============================
          // 0. Validaciones básicas
          // ===============================
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido.'];
            break;
          }

          // Obtener orden actual (SIEMPRE)
          $ordenActual = $this->ordenDAO->getOrdenById($id);
          if (!$ordenActual) {
            $res = ['tipo' => 'error', 'mensaje' => 'Orden no encontrada para modificar.'];
            break;
          }

          // ===============================
          // 1. Resolver usuario según rol
          // ===============================
          if (isset($_SESSION['rolId']) && $_SESSION['rolId'] == 3) {
            // 🚜 Tractorista: se fuerza el usuario logueado
            $usuarioIdNuevo = (int) $_SESSION['usuarioId'];
          } else {
            // 👨‍💼 Admin
            $usuarioIdNuevo = (int) $usuarioIdForm;
          }

          if ($usuarioIdNuevo <= 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'El campo Tractorista es obligatorio.'];
            break;
          }

          // ===============================
          // 2. Resolver estructura (según rol)
          // ===============================
          if (isset($_SESSION['rolId']) && $_SESSION['rolId'] == 3) {
            // 🚜 Tractorista → estructura INMUTABLE
            $categoriaIdNueva = $ordenActual->getCategoriaId();
            $potreroIdNuevo = $ordenActual->getPotreroId();
            $almacenId = $ordenActual->getAlmacenId();
            $tipoAlimentoId = $ordenActual->getTipoAlimentoId();
            $alimentoId = $ordenActual->getAlimentoId();
          } else {
            // 👨‍💼 Admin
            $categoriaIdNueva = (int) $categoriaIdForm;
            $potreroDetails = $this->potreroDAO->getPotreroDetailsByCategoriaId($categoriaIdNueva);
            $potreroIdNuevo = $potreroDetails ? (int) $potreroDetails['potreroId'] : 0;

            $almacenId = (int) $almacenId;
            $tipoAlimentoId = (int) $tipoAlimentoId;
            $alimentoId = (int) $alimentoId;

            if ($categoriaIdNueva <= 0 || $potreroIdNuevo <= 0 || $almacenId <= 0 || $tipoAlimentoId <= 0 || $alimentoId <= 0) {
              $res = ['tipo' => 'error', 'mensaje' => 'Datos estructurales inválidos.'];
              break;
            }
          }

          // ===============================
          // 3. Validar cantidad
          // ===============================
          $cantidadNueva = (int) $cantidad;
          if ($cantidadNueva <= 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'La cantidad debe ser mayor a 0.'];
            break;
          }

          // ===============================
          // 4. Validar stock máximo permitido
          // ===============================
          $stockDisponible = $this->stockDAO->getTotalStockByAlimentoIdAndTipoAndAlmacen(
            $ordenActual->getAlimentoId(),
            $ordenActual->getTipoAlimentoId(),
            $ordenActual->getAlmacenId()
          );

          $maxPermitido = $stockDisponible + $ordenActual->getCantidad();

          if ($cantidadNueva > $maxPermitido) {
            $res = [
              'tipo' => 'error',
              'mensaje' => "Cantidad supera el stock disponible. Máximo permitido: {$maxPermitido}"
            ];
            break;
          }

          // ===============================
          // 5. Datos originales
          // ===============================
          $cantidadOriginal = $ordenActual->getCantidad();

          // ===============================
          // 6. Transacción de stock
          // ===============================
          $conn = $this->ordenDAO->getConn();
          $conn->begin_transaction();

          try {
            $diferenciaCantidad = $cantidadOriginal - $cantidadNueva;
            $nuevosConsumos = [];

            // -------- REDUCCIÓN (rollback parcial) --------
            if ($diferenciaCantidad > 0) {
              $cantidadPendiente = $diferenciaCantidad;
              $consumos = array_reverse($this->ordenConsumoStockDAO->getConsumoByOrdenId($id));

              foreach ($consumos as $consumo) {
                if ($cantidadPendiente <= 0)
                  break;

                $devuelve = min($cantidadPendiente, $consumo['cantidadConsumida']);

                if (!$this->stockDAO->aumentarStockPorLote($conn, $consumo['stockId'], $devuelve)) {
                  throw new Exception('Error devolviendo stock.');
                }

                $restante = $consumo['cantidadConsumida'] - $devuelve;
                $cantidadPendiente -= $devuelve;

                if ($restante > 0) {
                  $nuevosConsumos[] = [
                    'stockId' => $consumo['stockId'],
                    'cantidadConsumida' => $restante
                  ];
                }
              }

              $nuevosConsumos = array_reverse($nuevosConsumos);
            }

            // -------- AUMENTO (consumo FIFO) --------
            elseif ($diferenciaCantidad < 0) {
              $faltante = abs($diferenciaCantidad);
              $lotes = $this->stockDAO->calcularReduccionFIFO(
                $alimentoId,
                $tipoAlimentoId,
                $faltante,
                $almacenId
              );

              if (empty($lotes)) {
                throw new Exception('Stock insuficiente para el aumento solicitado.');
              }

              $nuevosConsumos = $this->ordenConsumoStockDAO->getConsumoByOrdenId($id);

              foreach ($lotes as $lote) {
                if (!$this->stockDAO->ejecutarConsumo($conn, $lote['stockId'], $lote['cantidadConsumida'])) {
                  throw new Exception('Error consumiendo stock adicional.');
                }

                $found = false;
                foreach ($nuevosConsumos as &$nc) {
                  if ($nc['stockId'] == $lote['stockId']) {
                    $nc['cantidadConsumida'] += $lote['cantidadConsumida'];
                    $found = true;
                    break;
                  }
                }
                if (!$found) {
                  $nuevosConsumos[] = $lote;
                }
              }
            }

            // -------- Persistir consumos --------
            if ($diferenciaCantidad != 0) {
              if (!$this->ordenConsumoStockDAO->eliminarConsumoByOrdenId($conn, $id)) {
                throw new Exception('Error limpiando consumos.');
              }

              foreach ($nuevosConsumos as $l) {
                if ($l['cantidadConsumida'] > 0) {
                  if (!$this->ordenConsumoStockDAO->registrarDetalle($conn, $id, $l['stockId'], $l['cantidadConsumida'])) {
                    throw new Exception('Error registrando detalle.');
                  }
                }
              }
            }

            // ===============================
            // 7. Actualizar orden
            // ===============================
            $ordenModificada = new Orden(
              $id,
              $potreroIdNuevo,
              $almacenId,
              $tipoAlimentoId,
              $alimentoId,
              $cantidadNueva,
              $usuarioIdNuevo,
              $ordenActual->getEstadoId(),
              $categoriaIdNueva,
              $ordenActual->getFechaCreacion(),
              date('Y-m-d'),
              $ordenActual->getHoraCreacion(),
              date('H:i:s')
            );

            if (!$this->ordenDAO->modificarOrden($ordenModificada)) {
              throw new Exception('Error al modificar la orden.');
            }

            $conn->commit();

            // Auditoría
            $this->registrarAuditoria(
              $id,
              $_SESSION['usuarioId'],
              'MODIFICACION',
              $motivo ?: 'Modificación de orden',
              $cantidadOriginal,
              $cantidadNueva
            );

            // Registrar auditoría adicional solo para el administrador si la orden fue modificada
            if (isset($_SESSION['rolId']) && $_SESSION['rolId'] != 3) {
              $res = [
                'tipo' => 'success',
                'mensaje' => 'Orden modificada correctamente y stock ajustado.',
                'auditoria' => [
                  'cantidadAnterior' => $cantidadOriginal,
                  'cantidadNueva' => $cantidadNueva,
                  'tractoristaModificador' => $_SESSION['usuarioId'], // ID del usuario que hizo la modificación
                  'motivo' => $motivo // El motivo de la modificación
                ]
              ];
            } else {
              $res = ['tipo' => 'success', 'mensaje' => 'Orden modificada correctamente y stock ajustado.'];
            }

          } catch (Exception $e) {
            $conn->rollback();
            $res = ['tipo' => 'error', 'mensaje' => $e->getMessage()];
          }

          break;



        case 'eliminar':
          // 1. Obtener la orden
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar.'];
            break;
          }

          $ordenActual = $this->ordenDAO->getOrdenById($id);
          if (!$ordenActual) {
            $res = ['tipo' => 'error', 'mensaje' => 'Orden no encontrada para eliminar.'];
            break;
          }

          // 2. El DAO ahora maneja la devolución precisa de stock y la eliminación de la orden en una sola transacción.
          try {
            $ok = $this->ordenDAO->eliminarOrden($id);
            if ($ok) {
              $this->registrarAuditoria(
                $id,
                $_SESSION['usuarioId'],
                'CANCELACION',
                'Orden cancelada por el usuario'
              );
            }
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Orden eliminada correctamente y stock devuelto.']
              : ['tipo' => 'error', 'mensaje' => 'Error de transacción al eliminar la orden.'];

          } catch (mysqli_sql_exception $e) {
            // Manejo de error de DB (FK constraint)
            if ((int) $e->getCode() === 1451) {
              $res = ['tipo' => 'error', 'mensaje' => 'No se puede eliminar porque está en uso.'];
            } else {
              $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
            }
          }
          break;

        // case 'obtenerAuditoriaOrden':
        //   $ordenId = intval($_GET['id'] ?? 0);
        //   echo json_encode(
        //     $this->ordenAuditoriaDAO->listarAuditoriaPorOrden($ordenId)
        //   );
        //   exit;

        default:
          break;
      }

      // El resultado de la operación POST
      echo json_encode($res);
      exit;
    }
  }

  // ================
  // MÉTODOS DE APOYO
  // ================

  // Nuevo método para obtener categorías con potrero asignado para el SELECT del form
  public function obtenerCategoriasConPotrero()
  {
    if ($this->connError !== null) {
      return [];
    }
    // Usamos el nuevo método del DAO
    return $this->potreroDAO->getAllCategoriasConPotrero();
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
  global $isAjax;
  $ctrl = new OrdenController();

  // El procesamiento se realiza dentro de procesarFormularios
  $ctrl->procesarFormularios();
}