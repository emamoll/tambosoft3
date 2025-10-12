<?php
// DAO de Almacenes con lógica consistente con PasturaDAO

require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/almacen/almacenModelo.php';
require_once __DIR__ . '../../modelos/almacen/almacenTabla.php';

class AlmacenDAO
{
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

  /** Verifica duplicados por nombre. Si se pasa $id, lo excluye (para modificaciones). */
  public function existeNombre(string $nombre, ?int $id = null): bool
  {
    $sql = "SELECT id FROM almacenes WHERE LOWER(TRIM(nombre)) = LOWER(?)";
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

  /** Lista completa */
  public function getAllAlmacenes(): array
  {
    $result = $this->conn->query("SELECT id, nombre, campoId FROM almacenes ORDER BY id DESC");
    if (!$result) {
      return [];
    }
    $out = [];
    while ($row = $result->fetch_assoc()) {
      $out[] = new Almacen($row['id'], $row['nombre'], $row['campoId']);
    }
    return $out;
  }

  /** Buscar por ID */
  public function getAlmacenById(int $id): ?Almacen
  {
    $stmt = $this->conn->prepare("SELECT id, nombre, campoId FROM almacenes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ? new Almacen($row['id'], $row['nombre'], $row['campoId']) : null;
  }

  /** Buscar por Nombre */
  public function getAlmacenByNombre(string $nombre): ?Almacen
  {
    $stmt = $this->conn->prepare("SELECT id, nombre, campoId FROM almacenes WHERE nombre = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ? new Almacen($row['id'], $row['nombre'], $row['campoId']) : null;
  }

  /** Listar por Campo */
  public function getAlmacenesByCampoId(int $campoId): array
  {
    $stmt = $this->conn->prepare("SELECT id, nombre, campoId FROM almacenes WHERE campoId = ?");
    $stmt->bind_param("i", $campoId);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while ($row = $res->fetch_assoc()) {
      $out[] = new Almacen($row['id'], $row['nombre'], $row['campoId']);
    }
    $stmt->close();
    return $out;
  }

  /** Registrar (valida existencia de Campo y duplicado de nombre) */
  public function registrarAlmacen(Almacen $almacen): bool
  {
    $nombre = trim($almacen->getNombre());
    $campoId = (int) $almacen->getCampoId();

    // Verifica que el campo exista
    $stmtCheck = $this->conn->prepare("SELECT COUNT(1) AS total FROM campos WHERE id = ?");
    $stmtCheck->bind_param("i", $campoId);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $row = $res->fetch_assoc();
    $stmtCheck->close();

    $countCampo = $row ? (int) $row['total'] : 0;
    if ($countCampo <= 0) {
      return false;
    }

    // Duplicado por nombre
    if ($this->existeNombre($nombre)) {
      return false;
    }

    $stmt = $this->conn->prepare("INSERT INTO almacenes (nombre, campoId) VALUES (?, ?)");
    $stmt->bind_param("si", $nombre, $campoId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  /** Modificar por ID (valida duplicado de nombre excluyéndose) */
  public function modificarAlmacen(Almacen $almacen): bool
  {
    $id = (int) $almacen->getId();
    $nombre = trim($almacen->getNombre());
    $campoId = (int) $almacen->getCampoId();

    if ($id <= 0) {
      return false;
    }

    // Verifica FK campo
    $stmtCheck = $this->conn->prepare("SELECT COUNT(1) AS total FROM campos WHERE id = ?");
    $stmtCheck->bind_param("i", $campoId);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $row = $res->fetch_assoc();
    $stmtCheck->close();

    $countCampo = $row ? (int) $row['total'] : 0;
    if ($countCampo <= 0) {
      return false;
    }

    if ($this->existeNombre($nombre, $id)) {
      return false;
    }

    $stmt = $this->conn->prepare("UPDATE almacenes SET nombre = ?, campoId = ? WHERE id = ?");
    $stmt->bind_param("sii", $nombre, $campoId, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  /** Eliminar por ID */
  public function eliminarAlmacen(int $id): bool
  {
    $stmt = $this->conn->prepare("DELETE FROM almacenes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }
}
