<?php
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/stock/stockModelo.php';
require_once __DIR__ . '../../modelos/stock/stockTabla.php';

class StockDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new StockCrearTabla($this->db);
    $this->crearTabla->crearTablaStock();
    $this->conn = $this->db->connect();
  }

  // =============================================================
  // REGISTRAR STOCK (SIEMPRE INSERTA UNA FILA NUEVA)
  // =============================================================
  // REGISTRAR STOCK (SIEMPRE INSERTA UNA FILA NUEVA)
  public function registrarStock(Stock $stock): bool
  {
    $almacenId = trim($stock->getAlmacenId());
    $tipoAlimentoId = $stock->getTipoAlimentoId();
    $alimentoId = $stock->getAlimentoId();
    $cantidad = (int) $stock->getCantidad();
    $produccionInterna = (int) $stock->getProduccionInterna();
    $proveedorId = $stock->getProveedorId() ?: null;
    $precio = $stock->getPrecio() ?: null;
    $fechaIngreso = $stock->getFechaIngreso();

    $sql = "INSERT INTO stocks 
             (almacenId, tipoAlimentoId, alimentoId, cantidad, produccionInterna, proveedorId, precio, fechaIngreso)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param(
      "iiiiisss",
      $almacenId,
      $tipoAlimentoId,
      $alimentoId,
      $cantidad,
      $produccionInterna,
      $proveedorId,
      $precio,
      $fechaIngreso
    );

    $ok = $stmt->execute();

    // Obtener el ID generado
    if ($ok) {
      $lastId = $this->conn->insert_id; // Obtener el último ID insertado
      $stmt->close();
      return $lastId;  // Retornamos el ID generado por la base de datos
    }

    $stmt->close();
    return false;
  }


  // =============================================================
  // MODIFICAR UNA FILA EXISTENTE (NO SUMA NI MEZCLA)
  // =============================================================
  public function modificarStock(Stock $stock): bool
  {
    $id = $stock->getId();
    $almacenId = trim($stock->getAlmacenId());
    $tipoAlimentoId = $stock->getTipoAlimentoId();
    $alimentoId = $stock->getAlimentoId();
    $cantidad = (int) $stock->getCantidad();
    $produccionInterna = (int) $stock->getProduccionInterna();
    $proveedorId = $stock->getProveedorId() ?: null;
    $precio = $stock->getPrecio() ?: null;
    $fechaIngreso = $stock->getFechaIngreso();

    $sql = "UPDATE stocks
             SET almacenId = ?, tipoAlimentoId = ?, alimentoId = ?, cantidad = ?, produccionInterna = ?, proveedorId = ?, precio = ?, fechaIngreso = ?
             WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    // CORRECCIÓN: La cadena correcta es "iiiiiidsi" para los 9 parámetros.
    $stmt->bind_param(
      "iiiiiidsi",
      $almacenId,
      $tipoAlimentoId,
      $alimentoId,
      $cantidad,
      $produccionInterna,
      $proveedorId,
      $precio,
      $fechaIngreso,
      $id
    );

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // =============================================================
  // LISTAR AGRUPADO (FIX DEFINITIVO DE ENLACE DE PARÁMETROS)
  // =============================================================
  public function listar(array $filtros = []): array
  {
    $sql = "
    SELECT 
      s.almacenId,
      alm.nombre AS almacenNombre,
      s.tipoAlimentoId,
      tip.tipoAlimento AS tipoAlimentoNombre,
      s.alimentoId,
      ali.nombre AS alimentoNombre,
      s.produccionInterna,
      s.proveedorId,
      po.denominacion AS proveedorNombre,
      SUM(s.cantidad) AS cantidadTotal,
      MAX(s.precio) AS precio,
      MAX(s.fechaIngreso) AS ultimaFecha
    FROM stocks s
    LEFT JOIN tiposAlimentos tip ON s.tipoAlimentoId = tip.id
    LEFT JOIN almacenes alm ON s.almacenId = alm.id
    LEFT JOIN alimentos ali ON s.alimentoId = ali.id
    LEFT JOIN proveedores po ON s.proveedorId = po.id
    WHERE 1=1
    ";

    $params = [];
    $types = "";

    // Lógica para añadir filtros IN (múltiples selecciones)
    $addInClause = function (&$sql, &$params, &$types, $key, $column) use ($filtros) {
      if (!empty($filtros[$key]) && is_array($filtros[$key])) {
        // Creamos los placeholders '?' para cada ID
        $placeholders = implode(',', array_fill(0, count($filtros[$key]), '?'));
        $sql .= " AND {$column} IN ({$placeholders})";

        // Agregamos cada ID a la lista de parámetros y su tipo 'i' (integer)
        foreach ($filtros[$key] as $id) {
          $params[] = $id;
          $types .= "i";
        }
      }
    };

    // Aplicar filtros IN
    $addInClause($sql, $params, $types, 'tipoAlimentoId', 's.tipoAlimentoId');
    $addInClause($sql, $params, $types, 'almacenId', 's.almacenId');
    $addInClause($sql, $params, $types, 'alimentoId', 's.alimentoId');
    $addInClause($sql, $params, $types, 'proveedorId', 's.proveedorId');
    $addInClause($sql, $params, $types, 'produccionInterna', 's.produccionInterna');

    // Añadir filtros de RANGO (Fechas)
    if (!empty($filtros['fechaMin'])) {
      $sql .= " AND s.fechaIngreso >= ?";
      $params[] = $filtros['fechaMin'];
      $types .= "s"; // s for string (date)
    }

    if (!empty($filtros['fechaMax'])) {
      $sql .= " AND s.fechaIngreso <= ?";
      $params[] = $filtros['fechaMax'];
      $types .= "s"; // s for string (date)
    }


    $sql .= "
    GROUP BY 
      s.almacenId, alm.nombre,
      s.tipoAlimentoId, tip.tipoAlimento,
      s.alimentoId, ali.nombre,
      s.produccionInterna,
      s.proveedorId, po.denominacion
    ORDER BY 
      s.almacenId, s.tipoAlimentoId, s.alimentoId, 
      s.produccionInterna, s.proveedorId
    ";

    // Preparar la consulta y devolver los resultados
    $stmt = $this->conn->prepare($sql);

    // FIX CRÍTICO: Enlace de parámetros dinámicos con referencias
    if (!empty($params)) {
      $bind_names = [$types];
      // Es CRUCIAL pasar los parámetros por referencia (&) para bind_param
      for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
      }
      // Esta llamada garantiza que todos los parámetros se enlacen correctamente.
      call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($r = $result->fetch_assoc()) {
      $rows[] = [
        'almacenId' => $r['almacenId'],
        'almacenNombre' => $r['almacenNombre'],
        'tipoAlimentoId' => $r['tipoAlimentoId'],
        'tipoAlimentoNombre' => $r['tipoAlimentoNombre'],
        'alimentoId' => $r['alimentoId'],
        'alimentoNombre' => $r['alimentoNombre'],
        'produccionInterna' => $r['produccionInterna'],
        'proveedorId' => $r['proveedorId'],
        'proveedorNombre' => $r['proveedorNombre'] ?? '-',
        'cantidad' => (int) $r['cantidadTotal'],
        'precio' => $r['precio'],
        'fechaIngreso' => $r['ultimaFecha'],
      ];
    }

    return $rows;
  }


  public function listarDetalleGrupo(
    $almacenId,
    $tipoAlimentoId,
    $alimentoId,
    $produccionInterna,
    $proveedorId = null,
    $fechaMin = null,
    $fechaMax = null
  ): array {

    $sql = "SELECT
        s.id,
        s.almacenId,
        alm.nombre AS almacenNombre,
        s.tipoAlimentoId,
        tip.tipoAlimento AS tipoAlimentoNombre,
        s.alimentoId,
        ali.nombre AS alimentoNombre,
        s.produccionInterna,
        s.proveedorId,
        po.denominacion AS proveedorNombre,
        s.cantidad,
        s.precio,
        s.fechaIngreso
        FROM stocks s
        LEFT JOIN tiposAlimentos tip ON s.tipoAlimentoId = tip.id
        LEFT JOIN almacenes alm ON s.almacenId = alm.id
        LEFT JOIN alimentos ali ON s.alimentoId = ali.id
        LEFT JOIN proveedores po ON s.proveedorId = po.id
        WHERE s.almacenId = ?
          AND s.tipoAlimentoId = ?
          AND s.alimentoId = ?
          AND s.produccionInterna = ?";

    $params = [$almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna];
    $types = "iiii";

    if ($proveedorId !== null && $proveedorId !== '' && $proveedorId !== 0) {
      $sql .= " AND s.proveedorId = ?";
      $params[] = $proveedorId;
      $types .= "i";
    } else {
      $sql .= " AND s.proveedorId IS NULL";
    }

    // ★★ NUEVO: FILTROS DE FECHA ★★
    if ($fechaMin) {
      $sql .= " AND s.fechaIngreso >= ?";
      $params[] = $fechaMin;
      $types .= "s"; // s for string (date)
    }

    if ($fechaMax) {
      $sql .= " AND s.fechaIngreso <= ?";
      $params[] = $fechaMax;
      $types .= "s"; // s for string (date)
    }

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
      $rows[] = $r;
    }

    return $rows;
  }



  public function getDetalleGrupo($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna, $proveedorId)
  {
    // Si el proveedor es NULL (producción interna)
    if ($proveedorId === null || $proveedorId === "" || $proveedorId === "0") {
      $sql = "SELECT s.*, 
                    alm.nombre AS almacenNombre,
                    tip.tipoAlimento AS tipoAlimentoNombre,
                    ali.nombre AS alimentoNombre
                FROM stocks s
                LEFT JOIN almacenes alm ON s.almacenId = alm.id
                LEFT JOIN tiposAlimentos tip ON s.tipoAlimentoId = tip.id
                LEFT JOIN alimentos ali ON s.alimentoId = ali.id
                WHERE s.almacenId = ?
                  AND s.tipoAlimentoId = ?
                  AND s.alimentoId = ?
                  AND s.produccionInterna = ?
                  AND s.proveedorId IS NULL";

      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("iiii", $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);

    } else {

      $sql = "SELECT s.*, 
                    alm.nombre AS almacenNombre,
                    tip.tipoAlimento AS tipoAlimentoNombre,
                    ali.nombre AS alimentoNombre,
                    p.denominacion AS proveedorNombre
                FROM stocks s
                LEFT JOIN almacenes alm ON s.almacenId = alm.id
                LEFT JOIN tiposAlimentos tip ON s.tipoAlimentoId = tip.id
                LEFT JOIN alimentos ali ON s.alimentoId = ali.id
                LEFT JOIN proveedores p ON s.proveedorId = p.id
                WHERE s.almacenId = ?
                  AND s.tipoAlimentoId = ?
                  AND s.alimentoId = ?
                  AND s.produccionInterna = ?
                  AND s.proveedorId = ?";

      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("iiiii", $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna, $proveedorId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($r = $result->fetch_assoc()) {
      $rows[] = $r;
    }

    return $rows;
  }

  // =============================================================
  // MÉTODOS TRANSACCIONALES (PARA USO DE ORDENDAO)
  // =============================================================

  // 1. CÁLCULO DE REDUCCIÓN FIFO (solo devuelve qué lotes consumir, NO ejecuta DB)
  // Retorna un array con [{stockId, cantidadConsumida}] o un array vacío si el stock es insuficiente.
  public function calcularReduccionFIFO($alimentoId, $tipoAlimentoId, $cantidadRequerida, $almacenId): array
  {
    // Aseguramos que la cantidad requerida sea un entero
    $cantidadPendiente = (int) $cantidadRequerida;
    $consumoPorLote = [];

    if ($cantidadPendiente <= 0) {
      return $consumoPorLote;
    }

    // 1. Obtener entradas de stock para el alimento, tipo, y ALMACÉN ordenadas por fecha de ingreso (FIFO)
    $sqlSelect = "SELECT id, cantidad FROM stocks 
                  WHERE alimentoId = ? AND tipoAlimentoId = ? AND almacenId = ? AND cantidad > 0
                  ORDER BY fechaIngreso ASC, id ASC"; // FIFO por fecha, luego por ID
    $stmtSelect = $this->conn->prepare($sqlSelect);
    $stmtSelect->bind_param("iii", $alimentoId, $tipoAlimentoId, $almacenId);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result();
    $stmtSelect->close();


    while ($row = $result->fetch_assoc()) {
      $stockId = (int) $row['id']; // Cast de seguridad
      $cantidadActual = (int) $row['cantidad']; // Cast de seguridad

      if ($cantidadPendiente <= 0) {
        break;
      }

      $cantidadConsumida = min($cantidadPendiente, $cantidadActual);
      $cantidadPendiente -= $cantidadConsumida;

      $consumoPorLote[] = [
        'stockId' => $stockId,
        'cantidadConsumida' => $cantidadConsumida
      ];
    }

    // 2. Comprobar si se pudo satisfacer toda la demanda
    if ($cantidadPendiente > 0) {
      return []; // Stock insuficiente, devuelve array vacío
    }

    return $consumoPorLote; // Devuelve los detalles de consumo
  }

  // 2. EJECUCIÓN DE CONSUMO (Método atómico para reducir stock por lote)
  // Acepta $transactionConn para la atomicidad
  public function ejecutarConsumo(object $transactionConn, int $stockId, int $cantidadConsumida): bool
  {
    // Usa la conexión de la transacción principal
    $sql = "UPDATE stocks SET cantidad = GREATEST(cantidad - ?, 0) WHERE id = ?";
    $stmt = $transactionConn->prepare($sql); // USANDO $transactionConn
    $stmt->bind_param("ii", $cantidadConsumida, $stockId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // 3. AUMENTO/ROLLBACK PRECISO (Método atómico para devolver stock al lote exacto)
  // Acepta $transactionConn para la atomicidad
  public function aumentarStockPorLote(object $transactionConn, int $stockId, int $cantidadDevuelta): bool
  {
    // Aumenta la cantidad al ID de lote del que salió.
    $sql = "UPDATE stocks SET cantidad = cantidad + ? WHERE id = ?";
    $stmt = $transactionConn->prepare($sql); // USANDO $transactionConn
    $stmt->bind_param("ii", $cantidadDevuelta, $stockId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // =============================================================
  // RESTO DE TUS MÉTODOS - SIN CAMBIOS ESTRUCTURALES
  // =============================================================

  public function getAllStocks(): array
  {
    $sql = "SELECT * FROM stocks ORDER BY produccionInterna ASC";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $stocks = [];
    while ($row = $result->fetch_assoc()) {
      $stocks[] = new Stock(
        $row['id'],
        $row['almacenId'],
        $row['tipoAlimentoId'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['produccionInterna'],
        $row['proveedorId'],
        $row['precio'],
        $row['fechaIngreso']
      );
    }
    return $stocks;
  }

  public function getStockById($id): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res
      ? new Stock(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function getStockByAlmacenId($almacenId): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE almacenId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $almacenId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Stock(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function getStockByTipoAlimentoId($tipoAlimentoId): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE tipoAlimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $tipoAlimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Stock(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function getStockByAlimentoId($alimentoId): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE alimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $alimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Stock(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function getStockByProduccion($produccionInterna): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE produccionInterna = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $produccionInterna);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Stock(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function eliminarStock($id): bool
  {
    $sql = "DELETE FROM stocks WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  public function eliminarGrupo($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna, $proveedorId)
  {
    // Caso especial: proveedorId puede ser NULL
    if ($proveedorId === null) {
      $sql = "DELETE FROM stocks 
                WHERE almacenId = ? 
                  AND tipoAlimentoId = ? 
                  AND alimentoId = ? 
                  AND produccionInterna = ? 
                  AND proveedorId IS NULL";

      $stmt = $this->conn->prepare($sql);
      if (!$stmt)
        return false;

      $stmt->bind_param("iiii", $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);

    } else {
      $sql = "DELETE FROM stocks 
                WHERE almacenId = ? 
                  AND tipoAlimentoId = ? 
                  AND alimentoId = ? 
                  AND produccionInterna = ? 
                  AND proveedorId = ?";

      $stmt = $this->conn->prepare($sql);
      if (!$stmt)
        return false;

      $stmt->bind_param("iiiii", $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna, $proveedorId);
    }

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  public function getStockByAlmacenIdAndTipoAlimentoAndAlimentoIdAndProduccionInterna($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE almacenId = ? AND tipoAlimentoId = ? AND alimentoId = ? AND produccionInterna = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iiii", $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);
    $stmt->execute();

    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return $row
      ? new Stock(
        $row['id'],
        $row['almacenId'],
        $row['tipoAlimentoId'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['produccionInterna'],
        $row['proveedorId'],
        $row['precio'],
        $row['fechaIngreso']
      )
      : null;
  }

  public function actualizarStock($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna, $cantidad): ?bool
  {
    $stockActual = $this->getStockByAlmacenIdAndTipoAlimentoAndAlimentoIdAndProduccionInterna(
      $almacenId,
      $tipoAlimentoId,
      $alimentoId,
      $produccionInterna
    );

    if ($stockActual) {
      $nuevoStock = $stockActual->getCantidad() + $cantidad;
      $sql = "UPDATE stocks SET cantidad = ? WHERE almacenId = ? AND tipoAlimentoId = ? AND alimentoId = ? AND produccionInterna = ?";
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("iiiii", $nuevoStock, $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);
      return $stmt->execute();
    } else {
      // Si no existe una fila de stock, se inserta una nueva entrada
      $sql = "INSERT INTO stocks(almacenId, tipoAlimentoId, alimentoId, cantidad, produccionInterna, fechaIngreso) VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $this->conn->prepare($sql);
      $fechaIngreso = date('Y-m-d');
      $stmt->bind_param("iiiiis", $almacenId, $tipoAlimentoId, $alimentoId, $cantidad, $produccionInterna, $fechaIngreso);
      return $stmt->execute();
    }
  }

  public function reducirStock($almacenId, $tipoAlimentoId, $alimentoId, $cantidad, $produccionInterna)
  {
    $stockActual = $this->getStockByAlmacenIdAndTipoAlimentoAndAlimentoIdAndProduccionInterna(
      $almacenId,
      $tipoAlimentoId,
      $alimentoId,
      $produccionInterna
    );

    if ($stockActual) {
      $nuevoStock = $stockActual->getCantidad() - $cantidad;
      if ($nuevoStock < 0) {
        return false;
      }
      $sql = "UPDATE stocks SET cantidad = ? WHERE almacenId = ? AND tipoAlimentoId = ? AND alimentoId = ? AND produccionInterna = ?";
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("iiiii", $nuevoStock, $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);
      return $stmt->execute();
    }
    return false;
  }

  public function getAlimentosConStockByAlmacenId($almacenId)
  {
    $sql = "SELECT s.tipoAlimentoId, t.tipo as tipoAlimento, s.alimentoId, a.nombre as alimentoNombre, s.cantidad 
             FROM stocks s 
             JOIN tiposalimentos t ON s.tipoAlimentoId = t.id
             JOIN alimentos a ON s.alimentoId = a.id 
             WHERE s.almacenId = ? AND s.cantidad > 0";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $almacenId);
    $stmt->execute();

    $result = $stmt->get_result();
    $alimentosConStock = [];

    while ($row = $result->fetch_assoc()) {
      $alimentosConStock[] = [
        'id' => $row['alimentoId'],
        'tipo' => $row['tipoAlimento'],
        'nombre' => $row['alimentoNombre'],
        'cantidad' => $row['cantidad']
      ];
    }

    return $alimentosConStock;
  }

  public function getStocksFiltradas(array $almacenId, array $tipoAlimentoId, array $alimentoId, array $produccionInterna)
  {
    $sql = "SELECT * FROM stocks WHERE 1=1";
    $params = [];
    $tipos = '';

    if (!empty($almacenId)) {
      $placeholders = implode(',', array_fill(0, count($almacenId), '?'));
      $sql .= " AND almacenId IN ($placeholders)";
      $params = array_merge($params, $almacenId);
      $tipos .= str_repeat('i', count($almacenId));
    }

    if (!empty($tipoAlimentoId)) {
      $placeholders = implode(',', array_fill(0, count($tipoAlimentoId), '?'));
      $sql .= " AND tipoAlimentoId IN ($placeholders)";
      $params = array_merge($params, $tipoAlimentoId);
      $tipos .= str_repeat('i', count($tipoAlimentoId));
    }

    if (!empty($alimentoId)) {
      $placeholders = implode(',', array_fill(0, count($alimentoId), '?'));
      $sql .= " AND alimentoId IN ($placeholders)";
      $params = array_merge($params, $alimentoId);
      $tipos .= str_repeat('i', count($alimentoId));
    }

    if (!empty($produccionInterna)) {
      $placeholders = implode(',', array_fill(0, count($produccionInterna), '?'));
      $sql .= " AND produccionInterna IN ($placeholders)";
      $params = array_merge($params, $produccionInterna);
      $tipos .= str_repeat('i', count($produccionInterna));
    }

    $sql .= " ORDER BY id";

    $stmt = $this->conn->prepare($sql);

    if (!empty($params)) {
      $bind_names = [];
      $bind_names[] = $tipos;

      foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
      }

      call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    $stocks = [];
    while ($row = $resultado->fetch_assoc()) {
      $stocks[] = new Stock(
        $row['id'],
        $row['almacenId'],
        $row['tipoAlimentoId'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['produccionInterna'],
        $row['proveedorId'],
        $row['precio'],
        $row['fechaIngreso']
      );
    }

    return $stocks;
  }

  public function getTotalStockByTipoAlimentoId($tipoAlimentoId)
  {
    $sql = "SELECT SUM(cantidad) AS totalStock FROM stocks WHERE tipoAlimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $tipoAlimentoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['totalStock'] ?? 0;
  }

  public function getTotalStockByAlimentoId($alimentoId)
  {
    $sql = "SELECT SUM(cantidad) AS totalStock FROM stocks WHERE alimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $alimentoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['totalStock'] ?? 0;
  }

  public function getTotalStockByAlmacenId($almacenId)
  {
    $sql = "SELECT SUM(cantidad) AS totalStock FROM stocks WHERE almacenId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $almacenId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['totalStock'] ?? 0;
  }

  public function getTotalStockByProduccionInterna($produccionInterna)
  {
    $sql = "SELECT SUM(cantidad) AS totalStock FROM stocks WHERE produccionInterna = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $produccionInterna);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['totalStock'] ?? 0;
  }

  public function getTotalEconomicValue()
  {
    $sql = "SELECT SUM(s.cantidad * s.precio) AS totalValor 
             FROM stocks s 
             JOIN alimentos a ON s.alimentoId = a.id";

    $result = $this->conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['totalValor'] ?? 0;
  }

  public function getStockComprado($almacenId, $tipoAlimentoId, $alimentoId, $proveedorId)
  {
    $sql = "SELECT * FROM stocks 
             WHERE almacenId = ?
               AND tipoAlimentoId = ?
               AND alimentoId = ?
               AND produccionInterna = 0
               AND proveedorId = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iiii", $almacenId, $tipoAlimentoId, $alimentoId, $proveedorId);
    $stmt->execute();

    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res
      ? new Stock(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  // Obtener la cantidad total de stock disponible para un alimento específico y un almacén.
  public function getTotalStockByAlimentoIdAndTipoAndAlmacen($alimentoId, $tipoAlimentoId, $almacenId): int
  {
    $sql = "SELECT COALESCE(SUM(cantidad), 0) AS totalStock 
            FROM stocks 
            WHERE alimentoId = ? AND tipoAlimentoId = ? AND almacenId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iii", $alimentoId, $tipoAlimentoId, $almacenId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int) $row['totalStock'];
  }

  // NUEVO: Obtener Alimentos con Stock por Almacén y Tipo (para el filtro en cascada del form)
  public function getAlimentosConStockByAlmacenIdAndTipoId($almacenId, $tipoAlimentoId): array
  {
    $sql = "SELECT 
            s.alimentoId, 
            a.nombre AS alimentoNombre, 
            COALESCE(SUM(s.cantidad), 0) AS totalStock
          FROM stocks s
          JOIN alimentos a ON s.alimentoId = a.id
          WHERE s.almacenId = ?
            AND s.tipoAlimentoId = ?
          GROUP BY s.alimentoId, a.nombre
          ORDER BY a.nombre ASC";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ii", $almacenId, $tipoAlimentoId);
    $stmt->execute();
    $result = $stmt->get_result();

    $alimentos = [];
    while ($row = $result->fetch_assoc()) {
      $alimentos[] = [
        'id' => $row['alimentoId'],
        'nombre' => $row['alimentoNombre'],
        'cantidad' => (int) $row['totalStock'],
      ];
    }
    $stmt->close();

    return $alimentos;
  }


}