<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

// Clase encargada de crear la tabla Stock en la base de datos
class StockCrearTabla
{
  // Propiedad para la instancia de conexión a la base de datos
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  // Crea la tabla Stocks si no existe
  public function crearTablaStock()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "CREATE TABLE IF NOT EXISTS  stocks (
              id INT PRIMARY KEY AUTO_INCREMENT,  
              almacenId INT NOT NULL,
              tipoAlimentoId INT NOT NULL,
              alimentoId INT NOT NULL,
              cantidad INT NOT NULL,
              produccionInterna INT NOT NULL,
              proveedorId INT,
              precio INT,
              fechaIngreso DATE NOT NULL,
              FOREIGN KEY (almacenId) REFERENCES almacenes(id),
              FOREIGN KEY (tipoAlimentoId) REFERENCES tiposAlimentos(id),
              FOREIGN KEY (alimentoId) REFERENCES alimentos(id),
              FOREIGN KEY (proveedorId) REFERENCES proveedores(id))";

    $conn->query($sql);
    $conn->close();
  }
}