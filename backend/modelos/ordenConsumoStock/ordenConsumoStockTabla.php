<?php

class OrdenConsumoStockCrearTabla
{
  private $db;
  private $tableName = 'orden_stock_consumo';

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function crearTabla()
  {
    $conn = $this->db->connect();

    $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ordenId INT(11) NOT NULL,
            stockId INT(11) NOT NULL,
            cantidadConsumida INT(11) NOT NULL,
            FOREIGN KEY (ordenId) REFERENCES ordenes(id) ON DELETE CASCADE,
            FOREIGN KEY (stockId) REFERENCES stocks(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) === TRUE) {
      // echo "Tabla {$this->tableName} creada exitosamente\n";
    } else {
      error_log("Error creando la tabla {$this->tableName}: " . $conn->error);
    }
  }
}