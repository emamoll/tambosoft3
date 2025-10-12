<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

// Clase encargada de crear la tabla Potreros en la base de datos
class PotreroCrearTabla
{
  // Propiedad para la instancia de conexión a la base de datos.
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  // Crea la tabla Potreros si no existe.
  public function crearTablaPotrero()
  {
    // Crea una nueva conexión a la base de datos.
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();

    // Sentencia SQL para la creación de la tabla.
    $sql = "CREATE TABLE IF NOT EXISTS potreros (
              id INT PRIMARY KEY AUTO_INCREMENT,
              nombre VARCHAR(255) NOT NULL UNIQUE,
              pasturaId INT NOT NULL,
              categoriaId INT,
              cantidadCategoria INT,
              campoId INT NOT NULL,
              FOREIGN KEY (pasturaId) REFERENCES pasturas(id),
              FOREIGN KEY (categoriaId) REFERENCES categorias(id),
              FOREIGN KEY (campoId) REFERENCES campos(id))";

    // Ejecuta la consulta y cierra la conexión.
    $conn->query($sql);
    $conn->close();
  }
}