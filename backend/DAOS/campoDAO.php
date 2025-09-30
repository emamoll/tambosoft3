<?php

// Incluye los archivos necesarios para la conexión a la base de datos, los modelos y las tablas.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/campo/campoTabla.php';
require_once __DIR__ . '../../modelos/campo/campoModelo.php';
require_once __DIR__ . '../../modelos/almacen/almacenTabla.php';

// Clase para el acceso a datos (DAO) de la tabla Campos
class campoDAO
{
  // Propiedades privadas para la conexión y la creación de tablas.
  private $db;
  private $conn;
  private $crearTabla;
  private $crearTablaAlmacen;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new CampoCrearTabla($this->db);
    $this->crearTabla->crearTablaCampos();
    $this->crearTablaAlmacen = new AlmacenCrearTabla($this->db);
    $this->crearTablaAlmacen->crearTablaAlmacen();
    $this->conn = $this->db->connect();
  }

  // Obtiene todos los campos de la base de datos.
  public function getAllCampos()
  {
    $sql = "SELECT * FROM campos";
    $result = $this->conn->query($sql);

    // Si la consulta falla, detiene la ejecución.
    if (!$result) {
      die("Error en la consulta: " . $this->conn->error);
    }

    $campos = [];
    // Recorre los resultados y crea un objeto Campo por cada fila.
    while ($row = $result->fetch_assoc()) {
      $campos[] = new Campo($row['id'], $row['nombre'], $row['ubicacion'], $row['superficie']);
    }

    return $campos;
  }

  // Obtiene un campo por su ID.
  public function getCampoById($id)
  {
    $sql = "SELECT * FROM campos WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
      die("Error en la consulta: " . $this->conn->error);
    }

    // Si se encuentra una fila, crea y retorna un objeto Campo.
    if ($row = $result->fetch_assoc()) {
      return new Campo($row['id'], $row['nombre'], $row['ubicacion'], $row['superficie']);
    }
    return null;
  }

  // Obtiene un campo por su nombre.
  public function getCampoByNombre($nombre)
  {
    $sql = "SELECT * FROM campos WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
      return null;
    }

    $stmt->bind_result($id, $nombre, $ubicacion, $superficie);
    $stmt->fetch();

    return new Campo($id, $nombre, $ubicacion, $superficie);
  }

  // Registra un nuevo campo y un almacén asociado.
  public function registrarCampo(Campo $campo)
  {
    // 1. Verifica si ya existe un campo con ese nombre.
    $sqlVer = "SELECT id FROM campos WHERE nombre = ?";
    $stmtVer = $this->conn->prepare($sqlVer);
    $nombre = $campo->getNombre();
    $stmtVer->bind_param("s", $nombre);
    $stmtVer->execute();
    $stmtVer->store_result();

    if ($stmtVer->num_rows > 0) {
      $stmtVer->close();
      return false;
    }
    $stmtVer->close();

    // 2. Inserta el campo en la tabla Campos
    $sql = "INSERT INTO campos (nombre, ubicacion, superficie) VALUES (?, ?, ?)";
    $stmt = $this->conn->prepare($sql);
    $nombre = $campo->getNombre();
    $ubicacion = $campo->getUbicacion();
    $superficie = $campo->getSuperficie();
    $stmt->bind_param("ssi", $nombre, $ubicacion, $superficie);

    if (!$stmt->execute()) {
      $stmt->close();
      return false;
    }

    // 3. Obtiene el ID del campo recién insertado.
    $campoId = $stmt->insert_id;
    $stmt->close();

    // 4. Inserta un almacén con el mismo nombre y el ID del campo.
    $sqlAlm = "INSERT INTO almacenes (nombre, campoId) VALUES (?, ?)";
    $stmtAlm = $this->conn->prepare($sqlAlm);
    $stmtAlm->bind_param("si", $nombre, $campoId);

    $resultado = $stmtAlm->execute();
    $stmtAlm->close();

    return $resultado;
  }

  // Modifica un campo existente.
  public function modificarCampo(Campo $campo)
  {
    $sql = "UPDATE campos SET nombre = ?, ubicacion = ?, superficie = ? WHERE id = ? ";
    $stmt = $this->conn->prepare($sql);
    $id = $campo->getId();
    $nombre = $campo->getNombre();
    $ubicacion = $campo->getUbicacion();
    $superficie = $campo->getSuperficie();
    $stmt->bind_param("sssi", $nombre, $ubicacion, $superficie, $id);

    return $stmt->execute();
  }

  // Cuenta la cantidad de almacenes por campo 
  public function contarAlmacenesPorCampo(int $campoId): int
  {
    $sql = "SELECT COUNT(*) AS c FROM almacenes WHERE campoId = ?";
    $st = $this->conn->prepare($sql);
    $st->bind_param("i", $campoId);
    $st->execute();
    $res = $st->get_result()->fetch_assoc();
    return (int) $res['c'];
  }

  // Elimina un campo por id
  public function eliminarCampoYCascada(int $campoId): bool
  {
    $this->conn->begin_transaction();
    try {
      // 1) Borrar hijos de almacenes (ajustá nombres reales de columnas/tablas)
      $sql = "DELETE aa FROM almacenes aa
                JOIN almacenes a ON a.id = aa.campoId
               WHERE a.campoId = ?";
      $st = $this->conn->prepare($sql);
      $st->bind_param("i", $campoId);
      $st->execute();

      // 2) Borrar almacenes del campo
      $st = $this->conn->prepare("DELETE FROM almacenes WHERE campoId = ?");
      $st->bind_param("i", $campoId);
      $st->execute();

      // 3) Borrar el campo
      $st = $this->conn->prepare("DELETE FROM campos WHERE id = ?");
      $st->bind_param("i", $campoId);
      $st->execute();

      $this->conn->commit();
      return true;
    } catch (Throwable $e) {
      $this->conn->rollback();
      throw $e;
    }
  }
}