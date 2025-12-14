<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

class OrdenAuditoriaCrearTabla
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function crearTablaOrdenAuditoria()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "CREATE TABLE IF NOT EXISTS ordenAuditoria (
              id INT PRIMARY KEY AUTO_INCREMENT,
              ordenId INT NOT NULL,
              usuarioId INT NOT NULL,
              accion VARCHAR(20) NOT NULL,
              motivo VARCHAR(255) NOT NULL,
              cantidadAnterior INT DEFAULT NULL,
              cantidadNueva INT DEFAULT NULL,
              fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (ordenId) REFERENCES ordenes(id),
              FOREIGN KEY (usuarioId) REFERENCES usuarios(id)
            )";

    $conn->query($sql);
    $conn->close();
  }
}