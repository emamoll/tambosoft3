<?php

date_default_timezone_set('America/Argentina/Buenos_Aires'); // FIJADO ZONA HORARIA A ARGENTINA

require_once __DIR__ . '../../DAOS/ordenDAO.php';
require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../DAOS/stockDAO.php';
require_once __DIR__ . '../../DAOS/potreroDAO.php';
require_once __DIR__ . '../../DAOS/usuarioDAO.php';
require_once __DIR__ . '../../DAOS/almacenDAO.php';
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
      $this->ordenConsumoStockDAO = new OrdenConsumoStockDAO(); // INICIALIZACIÓN

    } catch (Exception $e) {
      $this->ordenDAO = null;
      $this->connError = $e->getMessage();
    }
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

        $ordenes = $this->obtenerOrden($usuarioIdFiltro > 0 ? $usuarioIdFiltro : null);

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

          // Creamos el array manualmente usando los getters del modelo Orden.
          $data = [
            'id' => $orden->getId(),
            'potreroId' => $orden->getPotreroId(),
            'almacenId' => $orden->getAlmacenId(),
            'tipoAlimentoId' => $orden->getTipoAlimentoId(),
            'alimentoId' => $orden->getAlimentoId(),
            'cantidad' => $orden->getCantidad(),
            'usuarioId' => $orden->getUsuarioId(),
            'estadoId' => $orden->getEstadoId(),
            'fechaCreacion' => $orden->getFechaCreacion(),
            'fechaActualizacion' => $orden->getFechaActualizacion(),
            'horaCreacion' => $orden->getHoraCreacion(),
            'horaActualizacion' => $orden->getHoraActualizacion(),
            'categoriaId' => $categoriaId, // Ahora viene del modelo
          ];
        }

        echo json_encode($data);
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
          // LÓGICA REESCRITA PARA ROLLBACK/AVANCE PRECISO
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido.'];
            break;
          }

          // El tractorista es obligatorio en la modificación también
          if ($usuarioIdForm == 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'El campo Tractorista es obligatorio.'];
            break;
          }

          // 1. Obtener el PotreroId asociado a la Categoria seleccionada
          $potreroDetails = $this->potreroDAO->getPotreroDetailsByCategoriaId(intval($categoriaIdForm));
          $potreroIdNuevo = $potreroDetails ? $potreroDetails['potreroId'] : 0;
          $categoriaIdNueva = intval($categoriaIdForm);


          if (empty($categoriaIdForm) || $potreroIdNuevo == 0 || empty($almacenId) || empty($tipoAlimentoId) || empty($alimentoId) || $cantidad <= 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error: Debés completar Categoría (con potrero asignado), Almacén, Tipo Alimento, Alimento y Cantidad (debe ser mayor a 0).'];
            break;
          }

          $almacenId = intval($almacenId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);
          $cantidadNueva = intval($cantidad);
          $usuarioIdNuevo = intval($usuarioIdForm); // Aseguramos que sea int


          $ordenActual = $this->ordenDAO->getOrdenById($id);
          if (!$ordenActual) {
            $res = ['tipo' => 'error', 'mensaje' => 'Orden no encontrada para modificar.'];
            break;
          }

          $cantidadOriginal = $ordenActual->getCantidad();
          $almacenOriginal = $ordenActual->getAlmacenId();
          $alimentoOriginal = $ordenActual->getAlimentoId();
          $tipoAlimentoOriginal = $ordenActual->getTipoAlimentoId();
          $potreroOriginal = $ordenActual->getPotreroId();
          $categoriaOriginal = $ordenActual->getCategoriaId(); // OBTENEMOS EL ID DE LA CATEGORÍA ORIGINAL


          // 1. Verificar si el almacén, tipo o alimento han cambiado (no permitido)
          if ($almacenOriginal != $almacenId || $alimentoOriginal != $alimentoId || $tipoAlimentoOriginal != $tipoAlimentoId) {
            $res = ['tipo' => 'error', 'mensaje' => 'No se permite cambiar el almacén, tipo o alimento en la modificación de la orden para garantizar la integridad del stock. Por favor, elimine y registre una nueva orden.'];
            break;
          }

          // 2. Verificar si el potrero (a través de la categoría) ha cambiado (no permitido)
          // Se verifica tanto el potrero como la categoría.
          if ($potreroOriginal != $potreroIdNuevo || $categoriaOriginal != $categoriaIdNueva) {
            $res = ['tipo' => 'error', 'mensaje' => 'No se permite cambiar la Categoría/Potrero en la modificación de la orden. Por favor, elimine y registre una nueva orden.'];
            break;
          }

          // Iniciar Transacción y obtener la conexión para pasarla a los DAOs auxiliares
          $conn = $this->ordenDAO->getConn();
          $conn->begin_transaction();
          $stockAfectado = true;

          try {
            $diferenciaCantidad = $cantidadOriginal - $cantidadNueva;
            $nuevosConsumos = [];

            if ($diferenciaCantidad > 0) { // La cantidad se redujo (Rollback parcial)
              $cantidadDevuelta = $diferenciaCantidad;

              // A. Recuperar lotes consumidos por la orden
              $consumosOriginales = $this->ordenConsumoStockDAO->getConsumoByOrdenId($id);

              // B. Aplicar el Rollback LIFO (se revierte el FIFO)
              $cantidadPendienteDevolver = $cantidadDevuelta;

              // Se revierte el array para ir del lote más nuevo al más antiguo consumido
              $consumosInversos = array_reverse($consumosOriginales);

              $errorStock = false;

              foreach ($consumosInversos as $consumo) {
                if ($cantidadPendienteDevolver <= 0) {
                  break;
                }

                $cantidadDevolverEsteLote = min($cantidadPendienteDevolver, $consumo['cantidadConsumida']);

                // Rollback: Devolver el stock al lote exacto (PASANDO $conn)
                if (!$this->stockDAO->aumentarStockPorLote($conn, $consumo['stockId'], $cantidadDevolverEsteLote)) {
                  $errorStock = true;
                  break;
                }

                $cantidadConsumidaNueva = $consumo['cantidadConsumida'] - $cantidadDevolverEsteLote;
                $cantidadPendienteDevolver -= $cantidadDevolverEsteLote;

                // Guardar el nuevo consumo restante para el detalle
                if ($cantidadConsumidaNueva > 0) {
                  $nuevosConsumos[] = [
                    'stockId' => $consumo['stockId'],
                    'cantidadConsumida' => $cantidadConsumidaNueva
                  ];
                }
              }
              // Volver a ordenar para que queden como en la base de datos (FIFO)
              $nuevosConsumos = array_reverse($nuevosConsumos);

              if ($errorStock) {
                throw new Exception('Error de DB al devolver el stock.');
              }

            } elseif ($diferenciaCantidad < 0) { // La cantidad se aumentó (requiere más stock)
              $cantidadRequeridaAdicional = abs($diferenciaCantidad);

              // 2. Verificar y obtener lotes para el stock adicional (FIFO)
              $lotesAdicionales = $this->stockDAO->calcularReduccionFIFO($alimentoId, $tipoAlimentoId, $cantidadRequeridaAdicional, $almacenId);

              if (empty($lotesAdicionales)) {
                $stockDisponible = $this->stockDAO->getTotalStockByAlimentoIdAndTipoAndAlmacen($alimentoId, $tipoAlimentoId, $almacenId);
                throw new Exception("Stock insuficiente en el almacén para el aumento solicitado. Solo hay {$stockDisponible} unidades disponibles para retirar.");
              }

              // 3. Ejecutar la reducción y combinar los consumos
              $consumosOriginales = $this->ordenConsumoStockDAO->getConsumoByOrdenId($id);
              $nuevosConsumos = $consumosOriginales;

              foreach ($lotesAdicionales as $lote) {
                // Ejecutar la Reducción en stocks (PASANDO $conn)
                if (!$this->stockDAO->ejecutarConsumo($conn, $lote['stockId'], $lote['cantidadConsumida'])) {
                  throw new Exception("Error al ejecutar consumo de stock adicional.");
                }

                // Intentar fusionar el consumo si el stockId ya existe (puede pasar si un lote se consumió parcialmente)
                $found = false;
                foreach ($nuevosConsumos as $key => $nConsumo) {
                  if ($nConsumo['stockId'] == $lote['stockId']) {
                    $nuevosConsumos[$key]['cantidadConsumida'] += $lote['cantidadConsumida'];
                    $found = true;
                    break;
                  }
                }
                if (!$found) {
                  $nuevosConsumos[] = $lote;
                }
              }

            } else {
              // No hay cambio en la cantidad
              $nuevosConsumos = $this->ordenConsumoStockDAO->getConsumoByOrdenId($id);
            }

            // 4. Actualizar la tabla de OrdenConsumoStock (solo si hubo cambio en cantidad)
            if ($diferenciaCantidad != 0) {
              // A. Eliminar viejos registros de consumo (PASANDO $conn)
              if (!$this->ordenConsumoStockDAO->eliminarConsumoByOrdenId($conn, $id)) {
                throw new Exception('Error al limpiar registros de consumo antiguos.');
              }
              // B. Insertar nuevos registros de consumo (PASANDO $conn)
              foreach ($nuevosConsumos as $lote) {
                // Solo insertamos si la cantidad consumida es > 0
                if ($lote['cantidadConsumida'] > 0) {
                  if (!$this->ordenConsumoStockDAO->registrarDetalle($conn, $id, $lote['stockId'], $lote['cantidadConsumida'])) {
                    throw new Exception('Error al registrar nuevos detalles de consumo.');
                  }
                }
              }
            }

            // 5. Modificación de la Orden en la DB (metadata)
            $ordenModificada = new Orden(
              $id,
              $potreroIdNuevo, // Usamos el potreroId asociado a la categoría
              $almacenId,
              $tipoAlimentoId,
              $alimentoId,
              $cantidadNueva,
              $usuarioIdNuevo, // Se permite cambiar el tractorista
              $ordenActual->getEstadoId(),
              $categoriaIdNueva, // NUEVO CAMPO
              $ordenActual->getFechaCreacion(),
              date('Y-m-d'),
              $ordenActual->getHoraCreacion(),
              date('H:i:s')
            );

            // La modificación de la orden principal NO es transaccional, pero es la última operación.
            $ok = $this->ordenDAO->modificarOrden($ordenModificada);

            if (!$ok) {
              throw new Exception('Error al modificar la orden (DB error).');
            }

            $conn->commit();
            $res = ['tipo' => 'success', 'mensaje' => 'Orden modificada correctamente y stock ajustado.'];

          } catch (Exception $e) {
            $conn->rollback();
            // Se devuelve un mensaje de error detallado
            $res = ['tipo' => 'error', 'mensaje' => 'Error de base de datos al actualizar el stock. La orden no fue modificada. Detalle: ' . $e->getMessage()];
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
  public function obtenerOrden(?int $usuarioId = null) // MODIFICADO: Acepta ID de usuario opcional
  {
    if ($this->connError !== null) {
      return [];
    }

    $conn = $this->ordenDAO->getConn();

    $sql = "SELECT 
                o.id, o.potreroId, o.almacenId, o.tipoAlimentoId, o.alimentoId, o.cantidad, o.usuarioId, o.estadoId, o.categoriaId,
                DATE_FORMAT(o.fechaCreacion, '%d/%m/%y') AS fechaCreacion,
                TIME_FORMAT(o.horaCreacion, '%H:%i') AS horaCreacion, /* FORMATO DE HORA MODIFICADO */
                p.nombre AS potreroNombre,
                al.nombre AS almacenNombre,
                ta.tipoAlimento AS tipoAlimentoNombre,
                a.nombre AS alimentoNombre,
                u.username AS usuarioNombre,
                e.descripcion AS estadoDescripcion,
                e.colores AS estadoColor,
                c.nombre AS categoriaNombre
            FROM ordenes o
            LEFT JOIN potreros p ON o.potreroId = p.id
            LEFT JOIN almacenes al ON o.almacenId = al.id
            LEFT JOIN tiposAlimentos ta ON o.tipoAlimentoId = ta.id
            LEFT JOIN alimentos a ON o.alimentoId = a.id
            LEFT JOIN usuarios u ON o.usuarioId = u.id
            LEFT JOIN estados e ON o.estadoId = e.id
            LEFT JOIN categorias c ON o.categoriaId = c.id
            LEFT JOIN campos ca ON p.campoId = ca.id
            WHERE 1=1";

    $params = [];
    $types = '';

    // LÓGICA DE FILTRADO AÑADIDA
    if ($usuarioId !== null && $usuarioId > 0) {
      $sql .= " AND o.usuarioId = ?";
      $params[] = $usuarioId;
      $types .= 'i';
    }

    $sql .= " ORDER BY o.fechaCreacion DESC, o.horaCreacion DESC";

    // Preparar y ejecutar la consulta (necesario si hay parámetros)
    if (!empty($params)) {
      $stmt = $conn->prepare($sql);
      if ($stmt === false) {
        error_log("Error preparando consulta en obtenerOrden: " . $conn->error);
        return [];
      }
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      $stmt->close();
    } else {
      $result = $conn->query($sql);
    }

    $ordenes = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $ordenes[] = $row;
      }
    }

    return $ordenes;
  }

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