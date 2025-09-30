<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

// Clase encargada de crear la tabla Campos en la base de datos
class CampoCrearTabla
{
  // Propiedad para la instancia de conexión a la base de datos
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

// Crea la tabla Campos si no existe
  public function crearTablaCampos()
  {
    // Crea una nueva conexión a la base de datos
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();

    // Sentencia SQL para la creación de la tabla.
    $sql = "CREATE TABLE IF NOT EXISTS campos (
              id INT PRIMARY KEY AUTO_INCREMENT,
              nombre VARCHAR(255) NOT NULL UNIQUE,
              ubicacion VARCHAR(255) NOT NULL,
              superficie INT NOT NULL)";

    // Ejecuta la consulta y cierra la conexión
    $conn->query($sql);
    $conn->close();
  }
}