<?php

require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/stock/stockModelo.php';
require_once __DIR__ . '../../modelos/stock/stockTabla.php';
require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../DAOS/proveedorDAO.php';
require_once __DIR__ . '../../DAOS/almacenDAO.php';

// Clase para el acceso a datos (DAO) de la tabla stocks
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

  // Obtiene la suma total de stock disponible para un alimento
  public function getStockDisponibleByAlimento(int $alimentoId): int
  {
    $sql = "SELECT SUM(cantidad) AS total_stock FROM stocks WHERE alimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $alimentoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int) ($row['total_stock'] ?? 0);
  }

  // Registra un nuevo stock
  public function registrarStock(Stock $stock): bool
  {
    $almacenId = $stock->getAlmacenId();
    $alimentoId = $stock->getAlimentoId();
    $cantidad = $stock->getCantidad();
    $produccionInterna = $stock->getProduccionInterna() ? 1 : 0;
    $proveedorId = $stock->getProveedorId();
    $fechaIngreso = $stock->getFechaIngreso();

    $sql = "INSERT INTO stocks (almacenId, alimentoId, cantidad, produccionInterna, proveedorId, fechaIngreso)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $this->conn->prepare($sql);

    $proveedorIdSafe = $proveedorId ?? NULL;
    $almacenIdSafe = $almacenId ?? NULL;

    $stmt->bind_param("iiiiis", $almacenIdSafe, $alimentoId, $cantidad, $produccionInterna, $proveedorIdSafe, $fechaIngreso);

    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
  }

  /** Modifica un stock existente */
  public function modificarStock(Stock $stock): bool
  {
    $id = $stock->getId();
    $alimentoId = $stock->getAlimentoId();
    $cantidad = $stock->getCantidad();
    $produccionInterna = $stock->getProduccionInterna() ? 1 : 0;
    $proveedorId = $stock->getProveedorId();
    $almacenId = $stock->getAlmacenId();
    $fechaIngreso = $stock->getFechaIngreso();

    $sql = "UPDATE stocks
            SET almacenId = ?, alimentoId = ?, cantidad = ?, produccionInterna = ?, proveedorId = ?, fechaIngreso = ?
            WHERE id = ?";

    $stmt = $this->conn->prepare($sql);

    $proveedorIdSafe = $proveedorId ?? NULL;
    $almacenIdSafe = $almacenId ?? NULL;

    // Tipos: i (almacenId), i (alimentoId), i (cantidad), i (produccionInterna), i (proveedorId), s (fechaIngreso), i (id)
    $stmt->bind_param("iiiiisi", $almacenIdSafe, $alimentoId, $cantidad, $produccionInterna, $proveedorIdSafe, $fechaIngreso, $id);

    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
  }

  // Obtiene todos los stock 
  public function getAllStocksDetalle(?int $alimentoId = null, ?int $produccionInterna = -1, ?int $almacenId = null): array
  {
    $sql = "SELECT s.id, s.almacenId, s.alimentoId, s.cantidad, s.produccionInterna, s.proveedorId, s.fechaIngreso, 
                   a.nombre AS alimentoNombre,
                   p.denominacion AS proveedorNombre,
                   alm.nombre AS almacenNombre
            FROM stocks s
            INNER JOIN alimentos a ON s.alimentoId = a.id
            LEFT JOIN proveedores p ON s.proveedorId = p.id
            LEFT JOIN almacenes alm ON s.almacenId = alm.id";

    $conditions = [];
    $types = '';
    $params = [];

    if ($alimentoId > 0) {
      $conditions[] = "s.alimentoId = ?";
      $types .= 'i';
      $params[] = $alimentoId;
    }

    if ($produccionInterna !== -1 && $produccionInterna !== null) {
      $conditions[] = "s.produccionInterna = ?";
      $types .= 'i';
      $params[] = $produccionInterna;
    }

    if ($almacenId > 0) {
      $conditions[] = "s.almacenId = ?";
      $types .= 'i';
      $params[] = $almacenId;
    }

    if (!empty($conditions)) {
      $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY s.fechaIngreso DESC, s.id DESC";

    $stmt = $this->conn->prepare($sql);

    if (!empty($params)) {
      $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
      error_log("Error en la consulta de stock con filtros: " . $this->conn->error);
      return [];
    }

    $stocks = [];
    while ($row = $result->fetch_assoc()) {
      // CORRECCIÓN CLAVE: Asegurar que el orden de los parámetros coincida con el constructor del modelo (id, alimentoId, cantidad, produccionInterna, proveedorId, almacenId, fechaIngreso)
      $stockObj = new Stock(
        $row['id'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['produccionInterna'],
        $row['proveedorId'],
        $row['almacenId'], // AlmacenId es el 6to parámetro en el constructor del modelo
        $row['fechaIngreso']
      );

      $stockData = (array) $stockObj;
      $stockData['alimentoNombre'] = $row['alimentoNombre'];
      $stockData['proveedorNombre'] = $row['proveedorNombre'];
      $stockData['almacenNombre'] = $row['almacenNombre'];

      $stocks[] = (object) $stockData;
    }
    $stmt->close();
    return $stocks;
  }

  // Obtiene un stock por ID 
  public function getStockById($id): ?Stock
  {
    $sql = "SELECT * FROM stocks WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    // CORRECCIÓN CLAVE: Orden de los parámetros
    return $row ? new Stock(
      $row['id'],
      $row['alimentoId'],
      $row['cantidad'],
      $row['produccionInterna'],
      $row['proveedorId'],
      $row['almacenId'], // AlmacenId es el 6to parámetro
      $row['fechaIngreso']
    ) : null;
  }

  // Eliminar un stock
  public function eliminarStock($id): bool
  {
    $sql = "DELETE FROM stocks WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }
}