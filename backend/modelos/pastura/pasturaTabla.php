<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

// Clase encargada de crear la tabla Pasturas en la base de datos.
class PasturaCrearTabla
{
  // Propiedad para la instancia de conexión a la base de datos.
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  // Crea la tabla Pasturas si no existe.

  public function crearTablaPastura()
  {
    // Crea una nueva conexión a la base de datos.
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();

    // Sentencia SQL para la creación de la tabla.
    $sql = "CREATE TABLE IF NOT EXISTS pasturas (
              id INT PRIMARY KEY AUTO_INCREMENT,
              nombre VARCHAR(255) NOT NULL UNIQUE,
              fechaSiembra DATE NOT NULL)";

    // Ejecuta la consulta y cierra la conexión.
    $conn->query($sql);
    $conn->close();
  }
}
