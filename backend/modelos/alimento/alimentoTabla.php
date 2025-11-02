<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

// Clase encargada de crear la tabla Alimentos en la base de datos.
class AlimentoCrearTabla
{
  // Propiedad para la instancia de conexión a la base de datos.
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  // Crea la tabla tiposAlimentos si no existe
  public function crearTablaTiposAlimentosId()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "CREATE TABLE IF NOT EXISTS tiposAlimentos (
              id INT PRIMARY KEY AUTO_INCREMENT, 
              tipoAlimento VARCHAR(255) NOT NULL UNIQUE)";

    $conn->query($sql);
    $conn->close();
  }

  // Inserta los tipos de alimentos predeterminados si no existen
  public function insertarTiposAlimentosPredeterminados()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();

    // Roles a insertar.
    $roles = ['Fardo', 'Silopack'];

    foreach ($roles as $rol) {
      $stmt = $conn->prepare("INSERT IGNORE INTO tiposAlimentos (tipoAlimento) VALUES (?)");
      $stmt->bind_param("s", $rol);
      $stmt->execute();
      $stmt->close();
    }

    $conn->close();
  }

  // Crea la tabla Alimento si no existe.

  public function crearTablaAlimento()
  {
    // Crea una nueva conexión a la base de datos.
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();

    // Sentencia SQL para la creación de la tabla.
    $sql = "CREATE TABLE IF NOT EXISTS alimentos (
              id INT PRIMARY KEY AUTO_INCREMENT,
              tipoAlimentoId INT NOT NULL,
              nombre VARCHAR(255) NOT NULL,
              FOREIGN KEY (tipoAlimentoId) REFERENCES tiposAlimentos(id))";

    // Ejecuta la consulta y cierra la conexión.
    $conn->query($sql);
    $conn->close();
  }
}
