<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla de la pastura.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/pastura/pasturaModelo.php';
require_once __DIR__ . '../../modelos/pastura/pasturaTabla.php';

// Clase para el acceso a datos (DAO) de la tabla Pasturas
class PasturaDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new PasturaCrearTabla($this->db);
    $this->crearTabla->crearTablaPastura();
    $this->crearTabla->insertarPasturas();
    $this->conn = $this->db->connect();
  }

  /**
   * Verifica si existe una pastura con el mismo nombre.
   * Si se pasa un $id, lo excluye de la validación (para modificaciones).
   */
  public function existeNombre($nombre, $id = null): bool
  {
    $sql = "SELECT id FROM pasturas WHERE LOWER(TRIM(nombre)) = LOWER(?)";
    if ($id !== null) {
      $sql .= " AND id <> ?";
    }

    $stmt = $this->conn->prepare($sql);
    if ($id !== null) {
      $stmt->bind_param("si", $nombre, $id);
    } else {
      $stmt->bind_param("s", $nombre);
    }

    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
  }

  // Registrar una nueva pastura
  public function registrarPastura(Pastura $pastura): bool
  {
    $nombre = trim($pastura->getNombre());

    // Verificación de duplicado usando existeNombre
    if ($this->existeNombre($nombre)) {
      return false;
    }

    $sql = "INSERT INTO pasturas (nombre) VALUES (?)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre,);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  // Modificar una pastura existente
  public function modificarPastura(Pastura $pastura): bool
  {
    $id = $pastura->getId();
    $nombre = trim($pastura->getNombre());

    // Verificación de duplicado excluyendo el propio ID
    if ($this->existeNombre($nombre, $id)) {
      return false;
    }

    $sql = "UPDATE pasturas SET nombre = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("si", $nombre, $id);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  // Obtener todas las pasturas
  public function getAllPasturas(): array
  {
    $sql = "SELECT * FROM pasturas";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $pasturas = [];
    while ($row = $result->fetch_assoc()) {
      $pasturas[] = new Pastura($row['id'], $row['nombre']);
    }
    return $pasturas;
  }

  // Obtener una pastura por ID
  public function getPasturaById($id): ?Pastura
  {
    $sql = "SELECT * FROM pasturas WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Pastura($row['id'], $row['nombre']) : null;
  }

  // Obtener una pastura por nombre
  public function getPasturaByNombre($nombre): ?Pastura
  {
    $sql = "SELECT * FROM pasturas WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Pastura($row['id'], $row['nombre']) : null;
  }

  // Eliminar una pastura
  public function eliminarPastura($id): bool
  {
    $sql = "DELETE FROM pasturas WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }
}