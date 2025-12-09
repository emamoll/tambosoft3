<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

// Clase encargada de crear las tablas Proveedores en la base de datos
class ProveedorCrearTabla
{
  // Propiedad para la instancia de conexión a la base de datos
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  // Crea la tabla Proveedores si no existe
  public function crearTablaProveedor()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "CREATE TABLE IF NOT EXISTS  proveedores (
              id INT PRIMARY KEY AUTO_INCREMENT, 
              denominacion VARCHAR(255) NOT NULL UNIQUE, 
              emailP VARCHAR(255) NOT NULL UNIQUE,
              telefono VARCHAR(255) NOT NULL)";

    $conn->query($sql);
    $conn->close();
  }
}