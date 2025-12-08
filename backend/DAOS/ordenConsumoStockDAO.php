<?php
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/ordenConsumoStock/ordenConsumoStockModelo.php';
require_once __DIR__ . '../../modelos/ordenConsumoStock/ordenConsumoStockTabla.php';

class OrdenConsumoStockDAO
{
  private $db;
  private $conn;
  private $crearTabla;
  private $tableName = 'orden_stock_consumo';

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new OrdenConsumoStockCrearTabla($this->db);
    $this->crearTabla->crearTabla();
    $this->conn = $this->db->connect();
  }

  // Registra un detalle de consumo (debe usar la conexión de la transacción)
  public function registrarDetalle(object $transactionConn, int $ordenId, int $stockId, int $cantidadConsumida): bool
  {
    $sql = "INSERT INTO {$this->tableName} (ordenId, stockId, cantidadConsumida) VALUES (?, ?, ?)";
    $stmt = $transactionConn->prepare($sql); // USANDO $transactionConn
    $stmt->bind_param("iii", $ordenId, $stockId, $cantidadConsumida);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // Obtiene todos los registros de consumo para una orden específica
  public function getConsumoByOrdenId(int $ordenId): array
  {
    $sql = "SELECT stockId, cantidadConsumida FROM {$this->tableName} WHERE ordenId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $ordenId);
    $stmt->execute();
    $result = $stmt->get_result();
    $consumos = [];
    while ($row = $result->fetch_assoc()) {
      $consumos[] = $row;
    }
    $stmt->close();
    return $consumos;
  }

  // Elimina todos los registros de consumo para una orden específica (debe usar la conexión de la transacción)
  public function eliminarConsumoByOrdenId(object $transactionConn, int $ordenId): bool
  {
    $sql = "DELETE FROM {$this->tableName} WHERE ordenId = ?";
    $stmt = $transactionConn->prepare($sql); // USANDO $transactionConn
    $stmt->bind_param("i", $ordenId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }
}