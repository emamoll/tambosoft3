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
  // NUEVO: Actualizar solo el estado de la orden
  // =============================================================
  public function actualizarEstadoOrden($ordenId, $nuevoEstadoId): bool
  {
    $fechaActualizacion = date('Y-m-d');
    $horaActualizacion = date('H:i:s');

    $sql = "UPDATE ordenes
             SET estadoId = ?, fechaActualizacion = ?, horaActualizacion = ?
             WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("issi", $nuevoEstadoId, $fechaActualizacion, $horaActualizacion, $ordenId);

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // =============================================================
  // Registrar orden (MODIFICADA: Agrega categoriaId)
  // =============================================================
  public function registrarOrden(Orden $orden): bool|int
  {
    $potreroId = $orden->getPotreroId();
    $almacenId = $orden->getAlmacenId();
    $tipoAlimentoId = $orden->getTipoAlimentoId();
    $alimentoId = $orden->getAlimentoId();
    $cantidad = $orden->getCantidad();
    $categoriaId = $orden->getCategoriaId();
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
                 (potreroId, almacenId, tipoAlimentoId, alimentoId, cantidad, usuarioId, estadoId, categoriaId, fechaCreacion, fechaActualizacion, horaCreacion, horaActualizacion)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $this->conn->prepare($sql);
      // Tipos: 8x(i) para IDs/Cantidades, 4x(s) para fechas/horas
      $stmt->bind_param(
        "iiiiiiiissss",
        $potreroId,
        $almacenId,
        $tipoAlimentoId,
        $alimentoId,
        $cantidad,
        $usuarioId,
        $estadoId,
        $categoriaId,
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

  // Modificar una orden (MODIFICADA: Agrega categoriaId)
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
    $categoriaId = $orden->getCategoriaId(); // NUEVO CAMPO

    // Asumimos que getFechaCreacion y getHoraCreacion devuelven formatos válidos
    $fechaCreacion = $orden->getFechaCreacion();
    $fechaActualizacion = date('Y-m-d');
    $horaCreacion = $orden->getHoraCreacion();
    $horaActualizacion = date('H:i:s');

    $sql = "UPDATE ordenes
             SET potreroId = ?, almacenId = ?, tipoAlimentoId = ?, alimentoId = ?, cantidad = ?, usuarioId = ?, estadoId = ?, categoriaId = ?, fechaCreacion = ?, fechaActualizacion = ?, horaCreacion = ?, horaActualizacion = ?
             WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    // Tipos: 8x(i) para IDs/Cantidades, 4x(s) para fechas/horas, 1x(i) para id
    $stmt->bind_param(
      "iiiiiiiissssi",
      $potreroId,
      $almacenId,
      $tipoAlimentoId,
      $alimentoId,
      $cantidad,
      $usuarioId,
      $estadoId,
      $categoriaId, // NUEVO BIND
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

  // Mostrar todas las ordenes (MODIFICADA: Agrega categoriaId al constructor)
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
        $row['categoriaId'], // NUEVO CAMPO
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
        $res['categoriaId'], // NUEVO CAMPO
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByPotreroId($potreroId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE potreroId = ?";
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
        $res['categoriaId'], // NUEVO CAMPO
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
        $res['categoriaId'], // NUEVO CAMPO
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
        $res['categoriaId'], // NUEVO CAMPO
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
        $res['categoriaId'], // NUEVO CAMPO
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
        $res['categoriaId'], // NUEVO CAMPO
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
        $res['categoriaId'], // NUEVO CAMPO
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  // =============================================================
  // Eliminar orden (Sin cambios sustanciales, solo necesita el constructor actualizado)
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

  public function getTiposAlimentoPorAlmacen($almacenId)
  {
    $sql = "
            SELECT DISTINCT
                ta.id,
                ta.tipoAlimento
            FROM stocks s
            INNER JOIN alimentos a ON a.id = s.alimentoId
            INNER JOIN tiposAlimentos ta ON ta.id = a.tipoAlimentoId
            WHERE s.almacenId = ?
            ORDER BY ta.tipoAlimento
        ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $almacenId);
    $stmt->execute();

    $result = $stmt->get_result();

    $tipos = [];
    while ($row = $result->fetch_assoc()) {
      $tipos[] = $row;
    }

    return $tipos;
  }

  public function listarOrdenes(?int $usuarioId = null): array
  {
    $sql = "SELECT 
          o.id,
          o.potreroId,
          o.almacenId,
          o.tipoAlimentoId,
          o.alimentoId,
          o.cantidad,
          o.usuarioId,
          o.estadoId,
          o.categoriaId,

            DATE_FORMAT(o.fechaActualizacion, '%d/%m/%y') AS fechaActualizacion,
            DATE_FORMAT(o.fechaCreacion, '%d/%m/%y') AS fechaCreacion,
            TIME_FORMAT(o.horaActualizacion, '%H:%i') AS horaActualizacion,
            TIME_FORMAT(o.horaCreacion, '%H:%i') AS horaCreacion,

          p.nombre AS potreroNombre,
          al.nombre AS almacenNombre,
          ta.tipoAlimento AS tipoAlimentoNombre,
          a.nombre AS alimentoNombre,
          u.username AS usuarioNombre,
          e.descripcion AS estadoDescripcion,
          e.colores AS estadoColor,
          c.nombre AS categoriaNombre,

          EXISTS (
            SELECT 1
            FROM ordenAuditoria oa
            WHERE oa.ordenId = o.id
          ) AS tieneAuditoria

        FROM ordenes o
        LEFT JOIN potreros p ON o.potreroId = p.id
        LEFT JOIN almacenes al ON o.almacenId = al.id
        LEFT JOIN tiposAlimentos ta ON o.tipoAlimentoId = ta.id
        LEFT JOIN alimentos a ON o.alimentoId = a.id
        LEFT JOIN usuarios u ON o.usuarioId = u.id
        LEFT JOIN estados e ON o.estadoId = e.id
        LEFT JOIN categorias c ON o.categoriaId = c.id
        WHERE 1=1";

    $params = [];
    $types = '';

    if ($usuarioId !== null && $usuarioId > 0) {
      $sql .= " AND o.usuarioId = ?";
      $params[] = $usuarioId;
      $types .= 'i';
    }

    $sql .= " ORDER BY o.fechaCreacion DESC, o.horaCreacion DESC";

    if (!empty($params)) {
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      $stmt->close();
    } else {
      $result = $this->conn->query($sql);
    }

    $ordenes = [];
    while ($row = $result->fetch_assoc()) {
      $ordenes[] = $row;
    }

    return $ordenes;
  }

  public function listarOrdenesFiltradas(array $filtros): array
  {
    $sql = "SELECT 
            o.id,
            o.potreroId,
            o.almacenId,
            o.tipoAlimentoId,
            o.alimentoId,
            o.cantidad,
            o.usuarioId,
            o.estadoId,
            o.categoriaId,

            DATE_FORMAT(o.fechaActualizacion, '%d/%m/%y') AS fechaActualizacion,
            DATE_FORMAT(o.fechaCreacion, '%d/%m/%y') AS fechaCreacion,
            TIME_FORMAT(o.horaActualizacion, '%H:%i') AS horaActualizacion,
            TIME_FORMAT(o.horaCreacion, '%H:%i') AS horaCreacion,

            p.nombre AS potreroNombre,
            al.nombre AS almacenNombre,
            ta.tipoAlimento AS tipoAlimentoNombre,
            a.nombre AS alimentoNombre,
            u.username AS usuarioNombre,
            e.descripcion AS estadoDescripcion,
            e.colores AS estadoColor,
            c.nombre AS categoriaNombre,

            EXISTS (
                SELECT 1
                FROM ordenAuditoria oa
                WHERE oa.ordenId = o.id
            ) AS tieneAuditoria

            FROM ordenes o
            LEFT JOIN potreros p ON o.potreroId = p.id
            LEFT JOIN almacenes al ON o.almacenId = al.id
            LEFT JOIN tiposAlimentos ta ON o.tipoAlimentoId = ta.id
            LEFT JOIN alimentos a ON o.alimentoId = a.id
            LEFT JOIN usuarios u ON o.usuarioId = u.id
            LEFT JOIN estados e ON o.estadoId = e.id
            LEFT JOIN categorias c ON o.categoriaId = c.id
            WHERE 1=1";

    $params = [];
    $types = '';

    // Mapeo de filtros de array a columnas de la BD
    $arrayFiltros = [
      'almacenId' => 'o.almacenId',
      'categoriaId' => 'o.categoriaId',
      'tipoAlimentoId' => 'o.tipoAlimentoId',
      'alimentoId' => 'o.alimentoId',
      'usuarioId' => 'o.usuarioId',
      'estadoId' => 'o.estadoId', // Filtrar por ID de estado (ya mapeado en Controller)
    ];

    foreach ($arrayFiltros as $key => $column) {
      if (isset($filtros[$key]) && is_array($filtros[$key]) && !empty($filtros[$key])) {
        $placeholders = implode(',', array_fill(0, count($filtros[$key]), '?'));
        $sql .= " AND {$column} IN ({$placeholders})";

        foreach ($filtros[$key] as $value) {
          $params[] = $value;
          $types .= 'i';
        }
      }
    }

    // Filtros de Fecha (o.fechaCreacion)
    $fechaMin = $filtros['fechaMin'] ?? null;
    $fechaMax = $filtros['fechaMax'] ?? null;

    // Se usa 'fechaCreacion' del registro de la orden
    if (!empty($fechaMin) && !empty($fechaMax)) {
      $sql .= " AND o.fechaCreacion BETWEEN ? AND ?";
      $params[] = $fechaMin;
      $params[] = $fechaMax;
      $types .= 'ss';
    } elseif (!empty($fechaMin)) {
      $sql .= " AND o.fechaCreacion >= ?";
      $params[] = $fechaMin;
      $types .= 's';
    } elseif (!empty($fechaMax)) {
      $sql .= " AND o.fechaCreacion <= ?";
      $params[] = $fechaMax;
      $types .= 's';
    }

    $sql .= " ORDER BY o.fechaCreacion DESC, o.horaCreacion DESC";

    if (!empty($params)) {
      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
        error_log("Error de preparación SQL: " . $this->conn->error . " | SQL: " . $sql);
        return [];
      }
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      $stmt->close();
    } else {
      $result = $this->conn->query($sql);
    }

    $ordenes = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $ordenes[] = $row;
      }
    }
    return $ordenes;
  }

  public function obtenerConsumoPorCategoria(): array
  {
    $sql = "SELECT 
                cat.nombre AS categoria, -- Traemos el nombre de la Categoría Animal
                SUM(ocs.cantidadConsumida) AS cantidad 
            FROM ordenConsumoStock ocs
            INNER JOIN ordenes o ON ocs.ordenId = o.id
            INNER JOIN categorias cat ON o.categoriaId = cat.id -- Relación con Animales
            WHERE o.estadoId = 4
            GROUP BY cat.nombre";
    $result = $this->conn->query($sql);
    return ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
  }


  public function listarConsumoValorizado(): array
  {
    $sql = "SELECT 
                cat.nombre AS categoriaAnimal, 
                ta.tipoAlimento AS tipoAlimentoNombre, 
                ali.nombre AS alimentoNombre, 
                s.produccionInterna, -- CAMPO RECUPERADO
                prov.denominacion AS proveedorNombre, -- CAMPO RECUPERADO
                SUM(ocs.cantidadConsumida) AS cantidadTotal,
                AVG(s.precio) AS precioUnitario, -- CAMPO PARA PRECIO UNITARIO
                SUM(ocs.cantidadConsumida * s.precio) AS subtotalConsumo
            FROM ordenConsumoStock ocs
            INNER JOIN ordenes o ON ocs.ordenId = o.id
            INNER JOIN stocks s ON ocs.stockId = s.id
            INNER JOIN categorias cat ON o.categoriaId = cat.id
            LEFT JOIN tiposAlimentos ta ON o.tipoAlimentoId = ta.id
            LEFT JOIN alimentos ali ON o.alimentoId = ali.id
            LEFT JOIN proveedores prov ON s.proveedorId = prov.id -- JOIN PARA PROVEEDOR
            WHERE o.estadoId = 4
            GROUP BY cat.id, ta.id, ali.id, s.produccionInterna, s.proveedorId
            ORDER BY cat.nombre ASC";

    $result = $this->conn->query($sql);
    return ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
  }

  public function obtenerEstadoIdPorDescripcion(string $descripcion): ?int
  {
    $sql = "SELECT id FROM estados WHERE descripcion = ? LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $descripcion);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res ? (int) $res['id'] : null;
  }

  public function cancelarOrden(int $ordenId, int $estadoCanceladaId): bool
  {
    $conn = $this->conn;
    $conn->begin_transaction();

    try {
      // 1️⃣ Obtener consumos de stock de la orden
      $sql = "
      SELECT stockId, cantidadConsumida
      FROM ordenConsumoStock
      WHERE ordenId = ?
      ORDER BY id DESC
    ";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $ordenId);
      $stmt->execute();
      $result = $stmt->get_result();

      // 2️⃣ Devolver stock lote por lote (rollback real)
      while ($row = $result->fetch_assoc()) {
        $ok = $this->stockDAO->aumentarStockPorLote(
          $conn,
          (int) $row['stockId'],
          (int) $row['cantidadConsumida']
        );

        if (!$ok) {
          throw new Exception("Error devolviendo stock del lote {$row['stockId']}");
        }
      }
      $stmt->close();

      // 3️⃣ Eliminar detalle de consumos
      $sql = "DELETE FROM ordenConsumoStock WHERE ordenId = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $ordenId);
      if (!$stmt->execute()) {
        throw new Exception("Error limpiando consumos de la orden");
      }
      $stmt->close();

      // 4️⃣ Cambiar estado a Cancelada (SIN borrar orden)
      $sql = "UPDATE ordenes SET estadoId = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ii", $estadoCanceladaId, $ordenId);

      if (!$stmt->execute()) {
        throw new Exception("Error actualizando estado de la orden");
      }
      $stmt->close();

      // 5️⃣ Commit
      $conn->commit();
      return true;

    } catch (Exception $e) {
      $conn->rollback();
      error_log("CancelarOrden error: " . $e->getMessage());
      return false;
    }
  }


}