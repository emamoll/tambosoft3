<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla del almacén
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/almacen/almacenTabla.php';
require_once __DIR__ . '../../modelos/almacen/almacenModelo.php';

// Clase para el acceso a datos (DAO) de la tabla Almacenes

class AlmacenDAO
{
  // Propiedades para la conexión y la creación de la tabla.
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new AlmacenCrearTabla($this->db);
    $this->crearTabla->crearTablaAlmacen();
    $this->conn = $this->db->connect();
  }

// Obtiene todos los almacenes de la base de datos.

  public function getAllAlmacenes()
  {
    $sql = "SELECT * FROM almacenes";
    $result = $this->conn->query($sql);

    // Si la consulta falla, detiene la ejecución y muestra el error.
    if (!$result) {
      die("Error en la consulta: " . $this->conn->error);
    }

    $almacenes = [];

    // Recorre los resultados y crea un objeto Almacen por cada fila.
    while ($row = $result->fetch_assoc()) {
      $almacenes[] = new Almacen($row['id'], $row['nombre'], $row['campoId']);
    }

    return $almacenes;
  }

// Obtiene un almacén por su ID.
  public function getAlmacenById($id)
  {
    $sql = "SELECT * FROM almacenes WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();

    // Si no se encuentra ninguna fila, retorna null.
    if ($stmt->num_rows() === 0) {
      return null;
    }

    // Vincula las variables a las columnas del resultado y obtiene la fila.
    $stmt->bind_result($id, $nombre, $campoId);
    $stmt->fetch();

    return new Almacen($id, $nombre, $campoId);
  }

// Obtiene un almacén por su nombre.
  public function getAlmacenByNombre($nombre)
  {
    $sql = "SELECT * FROM almacenes WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $stmt->store_result();

    // Si no se encuentra ninguna fila, retorna null.
    if ($stmt->num_rows() === 0) {
      return null;
    }

    // Vincula las variables y obtiene la fila.
    $stmt->bind_result($id, $nombre, $campoId);
    $stmt->fetch();

    return new Almacen($id, $nombre, $campoId);
  }

// Obtiene todos los almacenes asociados a un ID de campo específico.
  public function getAlmacenByCampoId($campoId)
  {
    $sql = "SELECT * FROM almacenes WHERE campo$campoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $campoId);
    $stmt->execute();
    $result = $stmt->get_result();

    $almacenes = [];
    // Recorre los resultados y crea objetos Almacen.
    while ($row = $result->fetch_assoc()) {
      $almacenes[] = new Almacen($row['id'], $row['campo$campoId']);
    }

    return $almacenes;
  }

// Registra un nuevo almacén en la base de datos.
  public function registrarAlmacen(Almacen $almacen)
  {
    $nombre = $almacen->getNombre();
    $campoId = $almacen->getCampoId();

    // Primero, verifica si ya existe un almacén con el mismo nombre.
    $sqlVer = "SELECT id FROM almacenes WHERE nombre = ?";
    $stmtVer = $this->conn->prepare($sqlVer);
    $stmtVer->bind_param("s", $nombre);
    $stmtVer->execute();
    $stmtVer->store_result();

    if ($stmtVer->num_rows > 0) {
      $stmtVer->close();
      return false;
    }
    $stmtVer->close();

    // Verifica si el campoId existe en la tabla Campos
    $checkCampoSql = "SELECT COUNT(*) FROM campos WHERE id = ?";
    $checkCampoStmt = $this->conn->prepare($checkCampoSql);
    $checkCampoStmt->bind_param("i", $campoId);
    $checkCampoStmt->execute();
    $checkCampoResult = $checkCampoStmt->get_result();
    $row = $checkCampoResult->fetch_row();
    $campoExists = ($row[0] > 0);
    $checkCampoStmt->close();
    
    $sql = "INSERT INTO almacenes (nombre, campoId) VALUES (?, ?)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("si", $nombre, $campoId); 
    $stmt->close();
    return true;
  }

// Modifica un almacén existente.
  public function modificarAlmacen(Almacen $almacen)
  {
    $sql = "UPDATE almacenes SET campoId = ? WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $nombre = $almacen->getNombre();
    $campoId = $almacen->getCampoId();
    $stmt->bind_param('is', $campoId, $nombre);

    return $stmt->execute();
  }

//  Elimina un almacén por su nombre.

  public function eliminarAlmacen($nombre)
  {
    $sql = "DELETE FROM almacenes WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);

    return $stmt->execute();
  }
}