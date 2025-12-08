<?php
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/orden/ordenModelo.php';
require_once __DIR__ . '../../modelos/orden/ordenTabla.php';
require_once __DIR__ . '../stockDAO.php';
require_once __DIR__ . '../ordenConsumoStockDAO.php'; // NUEVO REQUIRE

class OrdenDAO
{
  private $db;
  private $conn;
  private $crearTabla;
  private $stockDAO;
  private $ordenConsumoStockDAO; // NUEVA PROPIEDAD

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new OrdenCrearTabla($this->db);
    $this->crearTabla->crearTablaEstados();
    $this->crearTabla->insertarValoresTablaEstados();
    $this->crearTabla->crearTablaOrden();
    $this->conn = $this->db->connect();
    $this->stockDAO = new StockDAO();
    $this->ordenConsumoStockDAO = new OrdenConsumoStockDAO(); // INICIALIZACIÓN
  }

  public function getConn()
  {
    return $this->conn;
  }

  // =============================================================
  // Registrar orden (Modificado: Pasa la conexión a StockDAO)
  // =============================================================
  public function registrarOrden(Orden $orden): bool|int
  {
    $potreroId = $orden->getPotreroId();
    $almacenId = $orden->getAlmacenId();
    $tipoAlimentoId = $orden->getTipoAlimentoId();
    $alimentoId = $orden->getAlimentoId();
    $cantidad = $orden->getCantidad();
    // CAMPOS FIJOS/AUTOMÁTICOS
    $usuarioId = $orden->getUsuarioId();
    $estadoId = 1; // Estado inicial: 'Pendiente'
    $fechaCreacion = date('Y-m-d');
    $fechaActualizacion = date('Y-m-d');
    $horaCreacion = date('H:i:s');
    $horaActualizacion = date('H:i:s');

    // 1. Calcular Reducción de Stock FIFO (obtiene los lotes, no ejecuta la DB)
    $lotesConsumidos = $this->stockDAO->calcularReduccionFIFO($alimentoId, $tipoAlimentoId, $cantidad, $almacenId);

    if (empty($lotesConsumidos)) {
      return false; // Retorna FALSE si el stock es insuficiente o el cálculo falló
    }

    $this->conn->begin_transaction(); // INICIO DE LA TRANSACCIÓN
    $lastId = 0;

    try {
      // A. Registro de la Orden
      $sql = "INSERT INTO ordenes 
                 (potreroId, almacenId, tipoAlimentoId, alimentoId, cantidad, usuarioId, estadoId, fechaCreacion, fechaActualizacion, horaCreacion, horaActualizacion)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param(
        "iiiiiiissss",
        $potreroId,
        $almacenId,
        $tipoAlimentoId,
        $alimentoId,
        $cantidad,
        $usuarioId,
        $estadoId,
        $fechaCreacion,
        $fechaActualizacion,
        $horaCreacion,
        $horaActualizacion
      );

      if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Error al registrar la orden.");
      }
      $lastId = $this->conn->insert_id;
      $stmt->close();

      // B. Ejecutar la Reducción de Stock y Registrar el Detalle de Consumo
      foreach ($lotesConsumidos as $lote) {
        // Ejecutar la Reducción en stocks (USANDO LA CONEXIÓN DE LA TRANSACCIÓN: $this->conn)
        if (!$this->stockDAO->ejecutarConsumo($this->conn, $lote['stockId'], $lote['cantidadConsumida'])) {
          throw new Exception("Error al ejecutar consumo de stock.");
        }
        // Registrar el Detalle de Consumo (USANDO LA CONEXIÓN DE LA TRANSACCIÓN: $this->conn)
        if (!$this->ordenConsumoStockDAO->registrarDetalle($this->conn, $lastId, $lote['stockId'], $lote['cantidadConsumida'])) {
          throw new Exception("Error al registrar detalle de consumo.");
        }
      }

      $this->conn->commit(); // CONFIRMACIÓN ATÓMICA
      return $lastId;

    } catch (Exception $e) {
      $this->conn->rollback(); // DESHACER SI FALLA
      error_log("Fallo la transacción de registro de orden: " . $e->getMessage());
      return false;
    }
  }

  // Modificar una orden (La lógica de Stock se maneja en el Controller)
  public function modificarOrden(Orden $orden): bool
  {
    $id = $orden->getId();
    $potreroId = $orden->getPotreroId();
    $almacenId = $orden->getAlmacenId();
    $tipoAlimentoId = $orden->getTipoAlimentoId();
    $alimentoId = $orden->getAlimentoId();
    $cantidad = $orden->getCantidad();
    $usuarioId = $orden->getUsuarioId();
    $estadoId = $orden->getEstadoId();

    // Asumimos que getFechaCreacion y getHoraCreacion devuelven formatos válidos
    $fechaCreacion = $orden->getFechaCreacion();
    $fechaActualizacion = date('Y-m-d');
    $horaCreacion = $orden->getHoraCreacion();
    $horaActualizacion = date('H:i:s');

    $sql = "UPDATE ordenes
             SET potreroId = ?, almacenId = ?, tipoAlimentoId = ?, alimentoId = ?, cantidad = ?, usuarioId = ?, estadoId = ?, fechaCreacion = ?, fechaActualizacion = ?, horaCreacion = ?, horaActualizacion = ?
             WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param(
      "iiiiiiissssi",
      $potreroId,
      $almacenId,
      $tipoAlimentoId,
      $alimentoId,
      $cantidad,
      $usuarioId,
      $estadoId,
      $fechaCreacion,
      $fechaActualizacion,
      $horaCreacion,
      $horaActualizacion,
      $id
    );

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // Mostrar todas las ordenes
  public function getAllOrdenes(): array
  {
    $sql = "SELECT * FROM ordenes ORDER BY fechaCreacion ASC";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $ordenes = [];
    while ($row = $result->fetch_assoc()) {
      $ordenes[] = new Orden(
        $row['id'],
        $row['potreroId'],
        $row['almacenId'],
        $row['tipoAlimentoId'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['usuarioId'],
        $row['estadoId'],
        $row['fechaCreacion'],
        $row['fechaActualizacion'],
        $row['horaCreacion'],
        $row['horaActualizacion']
      );
    }
    return $ordenes;
  }

  public function getOrdenById($id): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByPotreroId($potreroId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE potreroId = ?"; // FIX de typo
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $potreroId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByTipoAlimentoId($tipoAlimentoId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE tipoAlimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $tipoAlimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByAlimentoId($alimentoId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE alimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $alimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByCantidad($cantidad): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE cantidad = ?"; // FIX de typo
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $cantidad);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByUsuarioId($usuarioId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE usuarioId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByEstadoId($estadoId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE estadoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $estadoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  // =============================================================
  // Eliminar orden (Modificado: Pasa la conexión a StockDAO)
  // =============================================================
  public function eliminarOrden($id): bool
  {
    $this->conn->begin_transaction();
    $ok = false;

    try {
      // 1. Obtener los detalles de consumo de la orden (qué lotes sacó y cuánto)
      $consumos = $this->ordenConsumoStockDAO->getConsumoByOrdenId($id);

      if (empty($consumos)) {
        // Si no hay consumos registrados, simplemente eliminamos la orden.
        // Esto cubre órdenes antiguas o fallidas.
        $sql = "DELETE FROM ordenes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
          throw new Exception("Error al eliminar la orden sin detalles de consumo.");
        }
        $this->conn->commit();
        return true;
      }

      // 2. Devolver la cantidad a CADA LOTE consumido (Rollback Preciso)
      foreach ($consumos as $consumo) {
        // USANDO LA CONEXIÓN DE LA TRANSACCIÓN: $this->conn
        if (!$this->stockDAO->aumentarStockPorLote($this->conn, $consumo['stockId'], $consumo['cantidadConsumida'])) {
          throw new Exception("Error al devolver stock al lote ID: " . $consumo['stockId']);
        }
      }

      // 3. Eliminar la Orden. La FK ON DELETE CASCADE en orden_stock_consumo
      // se encargará de eliminar los registros de consumo.
      $sqlDeleteOrden = "DELETE FROM ordenes WHERE id = ?";
      $stmtDeleteOrden = $this->conn->prepare($sqlDeleteOrden);
      $stmtDeleteOrden->bind_param("i", $id);
      $ok = $stmtDeleteOrden->execute();
      $stmtDeleteOrden->close();

      if (!$ok) {
        throw new Exception("Error al eliminar la orden.");
      }

      $this->conn->commit();
      return true;

    } catch (Exception $e) {
      $this->conn->rollback();
      error_log("Fallo la transacción de eliminación de orden: " . $e->getMessage());
      return false;
    }
  }
}