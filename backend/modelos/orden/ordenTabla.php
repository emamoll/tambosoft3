<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

// Clase encargada de crear la tabla Orden en la base de datos
class OrdenCrearTabla
{
  // Propiedad para la instancia de conexión a la base de datos
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }


  // Crear la tabla estados si no existe
  public function crearTablaEstados()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "CREATE TABLE IF NOT EXISTS estados (
              id INT PRIMARY KEY AUTO_INCREMENT, 
              descripcion VARCHAR(255) NOT NULL UNIQUE,
              colores VARCHAR(255) NOT NULL)";
    $conn->query($sql);
    $conn->close();
  }

  // Inserta los valores predeterminados en la tabla `estados` si no existen.
  public function insertarValoresTablaEstados()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "INSERT IGNORE INTO estados (descripcion, colores) 
            VALUES ('Pendiente', '#2773F5'), ('En preparación', '#DFEB1A'), ('Transportando', '#EB901A'), ('Entregada', '#1AEB40'), ('Cancelada', '#EB1A1A')";
    $conn->query($sql);
    $conn->close();
  }

  // Crear la tabla orden si no existe (MODIFICADA: Se agrega categoriaId)
  public function crearTablaOrden()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "CREATE TABLE IF NOT EXISTS ordenes (
              id INT PRIMARY KEY AUTO_INCREMENT,
              potreroId INT NOT NULL,
              almacenId INT NOT NULL,
              tipoAlimentoId INT NOT NULL,
              alimentoId INT NOT NULL,
              cantidad INT NOT NULL,
              usuarioId INT NOT NULL,
              estadoId INT NOT NULL,
              categoriaId INT NOT NULL, -- NUEVO CAMPO AGREGADO PARA ESTADÍSTICAS
              fechaCreacion DATE NOT NULL,
              fechaActualizacion DATE NOT NULL,
              horaCreacion TIME NOT NULL,
              horaActualizacion TIME NOT NULL,
              FOREIGN KEY (potreroId) REFERENCES potreros(id),
              FOREIGN KEY (almacenId) REFERENCES almacenes(id),
              FOREIGN KEY (tipoAlimentoId) REFERENCES tiposAlimentos(id),
              FOREIGN KEY (alimentoId) REFERENCES alimentos(id),
              FOREIGN KEY (usuarioId) REFERENCES usuarios(id),
              FOREIGN KEY (estadoId) REFERENCES estados(id),
              FOREIGN KEY (categoriaId) REFERENCES categorias(id) -- NUEVA FK
            )";

    $conn->query($sql);
    $conn->close();
  }
}