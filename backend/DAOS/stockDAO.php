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

  // Registrar stock
  public function registrarStock(Stock $stock): bool
  {
    $almacenId = trim($stock->getAlmacenId());
    $tipoAlimentoId = $stock->getTipoAlimentoId();
    $alimentoId = $stock->getAlimentoId();

    // ⛔ ANTES: $cantidad = $stock->getCantidad() ?: null;
    $cantidad = (int) $stock->getCantidad();  // ← CORREGIDO

    // ⛔ ANTES: $produccionInterna = $stock->getProduccionInterna() ?: null;
    $produccionInterna = (int) $stock->getProduccionInterna(); // ← CORREGIDO

    $proveedorId = $stock->getProveedorId() ?: null;
    $precio = $stock->getPrecio() ?: null;
    $fechaIngreso = $stock->getFechaIngreso();

    $sql = "INSERT INTO stocks 
            (almacenId, tipoAlimentoId, alimentoId, cantidad, produccionInterna, proveedorId, precio, fechaIngreso)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iiiiisss", $almacenId, $tipoAlimentoId, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $precio, $fechaIngreso);

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // Modificar stock
  public function modificarStock(Stock $stock): bool
  {
    $id = $stock->getId();
    $almacenId = trim($stock->getAlmacenId());
    $tipoAlimentoId = $stock->getTipoAlimentoId();
    $alimentoId = $stock->getAlimentoId();

    // ⛔ ANTES: $cantidad = $stock->getCantidad() ?: null;
    $cantidad = (int) $stock->getCantidad(); // ← CORREGIDO

    // ⛔ ANTES: $produccionInterna = $stock->getProduccionInterna() ?: null;
    $produccionInterna = (int) $stock->getProduccionInterna(); // ← CORREGIDO

    $proveedorId = $stock->getProveedorId() ?: null;
    $precio = $stock->getPrecio() ?: null;
    $fechaIngreso = $stock->getFechaIngreso();

    $sql = "UPDATE stocks
            SET almacenId = ?, tipoAlimentoId = ?, alimentoId = ?, cantidad = ?, produccionInterna = ?, proveedorId = ?, precio = ?, fechaIngreso = ?
            WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iiiiisssi", $almacenId, $tipoAlimentoId, $alimentoId, $cantidad, $produccionInterna, $proveedorId, $precio, $fechaIngreso, $id);

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // Listar stock con filtros dinámicos 
  public function listar(array $filtros = []): array
  {
    $sql = "SELECT s.*, 
                   alm.nombre AS almacenNombre, 
                   tip.nombre AS tipoAlimentoNombre,
                   ali.nombre AS alimentoNombre, 
                   po.denominacion AS proveedorNombre
            FROM stocks s
            LEFT JOIN tiposalimentos tip ON s.tipoAlimentoId = tip.id
            LEFT JOIN almacenes alm ON s.almacenId = alm.id
            LEFT JOIN alimentos ali ON s.alimentoId = ali.id
            LEFT JOIN proveedores po ON s.proveedorId = po.id
            WHERE 1=1";

    $params = [];
    $types = "";

    $addInClause = function (&$sql, &$params, &$types, $key, $column) use ($filtros) {
      if (!empty($filtros[$key]) && is_array($filtros[$key])) {
        $placeholders = implode(',', array_fill(0, count($filtros[$key]), '?'));
        $sql .= " AND {$column} IN ({$placeholders})";

        foreach ($filtros[$key] as $id) {
          $params[] = $id;
          $types .= "i";
        }
      }
    };

    $addInClause($sql, $params, $types, 'tipoAlimentoId', 's.tipoAlimentoId');
    $addInClause($sql, $params, $types, 'almacenId', 's.almacenId');
    $addInClause($sql, $params, $types, 'alimentoId', 's.alimentoId');
    $addInClause($sql, $params, $types, 'proveedorId', 's.proveedorId');
    $addInClause($sql, $params, $types, 'produccionInterna', 's.produccionInterna');

    $sql .= " ORDER BY s.produccionInterna ASC";

    $stmt = $this->conn->prepare($sql);
    if ($stmt === false) {
      error_log("Error en prepare (listar): " . $this->conn->error);
      return [];
    }

    if (!empty($params)) {
      $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($r = $result->fetch_assoc()) {
      $rows[] = $r;
    }

    $stmt->close();
    return $rows;
  }

  // Obtener todos
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

  // Obtener por ID
  public function getStockById($id): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res
      ? new Stock($res['id'], $res['almacenId'], $res['tipoAlimentoId'], $res['alimentoId'], $res['cantidad'], $res['produccionInterna'], $res['proveedorId'], $res['precio'], $res['fechaIngreso'])
      : null;
  }

  // Obtener por almacen
  public function getStockByAlmacenId($almacenId): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE almacenId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $almacenId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Stock($res['id'], $res['almacenId'], $res['tipoAlimentoId'], $res['alimentoId'], $res['cantidad'], $res['produccionInterna'], $res['proveedorId'], $res['precio'], $res['fechaIngreso'])
      : null;
  }

  // Obtener stock por tipo alimento
  public function getStockByTipoAlimentoId($tipoAlimentoId): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE tipoAlimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $tipoAlimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Stock($res['id'], $res['almacenId'], $res['tipoAlimentoId'], $res['alimentoId'], $res['cantidad'], $res['produccionInterna'], $res['proveedorId'], $res['precio'], $res['fechaIngreso'])
      : null;
  }

  // Obtener stock por alimento
  public function getStockByAlimentoId($alimentoId): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE alimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $alimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Stock($res['id'], $res['almacenId'], $res['tipoAlimentoId'], $res['alimentoId'], $res['cantidad'], $res['produccionInterna'], $res['proveedorId'], $res['precio'], $res['fechaIngreso'])
      : null;
  }

  // Obtener stock por produccion
  public function getStockByProduccion($produccionInterna): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE produccionInterna = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $produccionInterna);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Stock($res['id'], $res['almacenId'], $res['tipoAlimentoId'], $res['alimentoId'], $res['cantidad'], $res['produccionInterna'], $res['proveedorId'], $res['precio'], $res['fechaIngreso'])
      : null;
  }

  // Eliminar stock
  public function eliminarStock($id): bool
  {
    $sql = "DELETE FROM stocks WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // Obtener Stock por almacen, alimento y produccion interna 
  public function getStockByAlmacenIdAndTipoAlimentoAndAlimentoIdAndProduccionInterna($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE almacenId = ? AND tipoAlimentoId = ? AND alimentoId = ? AND produccionInterna = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iiii", $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);
    $stmt->execute();

    // ⛔ ANTES: cerrabas el stmt acá
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    $stmt->close();

    return $row
      ? new Stock($row['id'], $row['almacenId'], $row['tipoAlimentoId'], $row['alimentoId'], $row['cantidad'], $row['produccionInterna'], $row['proveedorId'], $row['precio'], $row['fechaIngreso'])
      : null;
  }

  // Actualiza el stock de un alimento en un almacén o lo registra si no existe.
  public function actualizarStock($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna, $cantidad): ?Stock
  {
    $stockActual = $this->getStockByAlmacenIdAndTipoAlimentoAndAlimentoIdAndProduccionInterna($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);

    if ($stockActual) {
      $nuevoStock = $stockActual->getCantidad() + $cantidad;
      $sql = "UPDATE stocks SET cantidad = ? WHERE almacenId = ? AND tipoAlimentoId = ? AND alimentoId = ? AND produccionInterna = ?";
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("iiiii", $nuevoStock, $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);
      return $stmt->execute();
    } else {
      $sql = "INSERT INTO stocks(almacenId, tipoAlimentoId, alimentoId, cantidad, produccionInterna) VALUES (?, ?, ?, ?, ?)";
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("iiiii", $almacenId, $tipoAlimentoId, $alimentoId, $cantidad, $produccionInterna);
      return $stmt->execute();
    }
  }

  // Reducir el stock
  public function reducirStock($almacenId, $tipoAlimentoId, $alimentoId, $cantidad, $produccionInterna)
  {
    $stockActual = $this->getStockByAlmacenIdAndTipoAlimentoAndAlimentoIdAndProduccionInterna($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);

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

  // Lista de alimentos con stock
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

  // Filtro de Stock
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

  // Totales
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
}
