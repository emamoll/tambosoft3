<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla de la alimento.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/alimento/alimentoModelo.php';
require_once __DIR__ . '../../modelos/alimento/alimentoTabla.php';

// Clase para el acceso a datos (DAO) de la tabla alimentos
class AlimentoDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new AlimentoCrearTabla($this->db);
    $this->crearTabla->crearTablaAlimento();
    $this->conn = $this->db->connect();
  }

  /**
   * Verifica si existe un Alimento con el mismo nombre.
   * Si se pasa un $id, lo excluye de la validación (para modificaciones).
   */
  public function existeNombre($nombre, $id = null): bool
  {
    $sql = "SELECT id FROM alimentos WHERE LOWER(TRIM(nombre)) = LOWER(?)";
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

  // Registrar un nuev alimento
  public function registrarAlimento(Alimento $alimento): bool
  {
    $nombre = trim($alimento->getNombre());

    // Verificación de duplicado usando existeNombre
    if ($this->existeNombre($nombre)) {
      return false;
    }

    $sql = "INSERT INTO alimentos (nombre) VALUES (?)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  // Modificar un alimento existente
  public function modificarAlimento(Alimento $alimento): bool
  {
    $id = $alimento->getId();
    $nombre = trim($alimento->getNombre());

    // Verificación de duplicado excluyendo el propio ID
    if ($this->existeNombre($nombre, $id)) {
      return false;
    }

    $sql = "UPDATE alimentos SET nombre = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("si", $nombre, $id);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  // Obtener todos los alimentos
  public function getAllAlimentos(): array
  {
    $sql = "SELECT * FROM alimentos";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $alimentos = [];
    while ($row = $result->fetch_assoc()) {
      $alimentos[] = new Alimento($row['id'], $row['nombre']);
    }
    return $alimentos;
  }

  // Obtener un alimento por ID
  public function getAlimentoById($id): ? Alimento
  {
    $sql = "SELECT * FROM alimentos WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Alimento($row['id'], $row['nombre']) : null;
  }

  // Obtener una alimento por nombre
  public function getAlimentoByNombre($nombre): ? Alimento
  {
    $sql = "SELECT * FROM alimentos WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Alimento($row['id'], $row['nombre']) : null;
  }

  // Eliminar una alimento
  public function eliminarAlimento($id): bool
  {
    $sql = "DELETE FROM alimentos WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }
}