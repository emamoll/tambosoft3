<?php
// DAO de Campos con lógica consistente con PasturaDAO

require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/campo/campoModelo.php';
require_once __DIR__ . '../../modelos/campo/campoTabla.php';
require_once __DIR__ . '../../modelos/almacen/almacenTabla.php';

class CampoDAO
{
  private $db;
  private $conn;
  private $crearTablaCampo;
  private $crearTablaAlmacen;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTablaCampo = new CampoCrearTabla($this->db);
    $this->crearTablaCampo->crearTablaCampos();

    // Por relación FK/uso en cascada
    $this->crearTablaAlmacen = new AlmacenCrearTabla($this->db);
    $this->crearTablaAlmacen->crearTablaAlmacen();

    $this->conn = $this->db->connect();
  }

  /** Verifica duplicados por nombre. Si se pasa $id, lo excluye (para modificaciones). */
  public function existeNombre(string $nombre, ?int $id = null): bool
  {
    $sql = "SELECT id FROM campos WHERE LOWER(TRIM(nombre)) = LOWER(?)";
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

  /** Crear nuevo Campo. Además, crea (opcional) un Almacén por defecto con el mismo nombre. */
  public function registrarCampo(Campo $campo, bool $crearAlmacenPorDefecto = true): string
  {
    $nombre = trim($campo->getNombre());
    $ubicacion = trim($campo->getUbicacion());
    $superficie = trim($campo->getSuperficie());

    if ($this->existeNombre($nombre)) {
      return "duplicado";
    }

    try {
      $this->conn->begin_transaction();

      $sql = "INSERT INTO campos (nombre, ubicacion, superficie) VALUES (?, ?, ?)";
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("sss", $nombre, $ubicacion, $superficie);
      if (!$stmt->execute()) {
        $this->conn->rollback();
        $stmt->close();
        return "error_insert_campo";
      }
      $stmt->close();

      if ($crearAlmacenPorDefecto) {
        $campoId = $this->conn->insert_id;
        $stmtA = $this->conn->prepare("INSERT INTO almacenes (nombre, campoId) VALUES (?, ?)");
        $stmtA->bind_param("si", $nombre, $campoId);
        if (!$stmtA->execute()) {
          $this->conn->rollback();
          $stmtA->close();
          return "error_insert_almacen";
        }
        $stmtA->close();
      }

      $this->conn->commit();
      return "ok";
    } catch (Throwable $e) {
      $this->conn->rollback();
      return "error_excepcion";
    }
  }

  /** Modificar Campo. Valida duplicado por nombre ignorando su propio ID. */
  public function modificarCampo(Campo $campo): bool
  {
    $id = (int) $campo->getId();
    $nombre = trim($campo->getNombre());
    $ubicacion = trim($campo->getUbicacion());
    $superficie = trim($campo->getSuperficie());

    if ($id <= 0)
      return false;
    if ($this->existeNombre($nombre, $id))
      return false;

    $sql = "UPDATE campos SET nombre = ?, ubicacion = ?, superficie = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $ubicacion, $superficie, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  /** Listado completo */
  public function getAllCampos(): array
  {
    $result = $this->conn->query("SELECT id, nombre, ubicacion, superficie FROM campos ORDER BY id DESC");
    if (!$result) {
      return [];
    }

    $out = [];
    while ($row = $result->fetch_assoc()) {
      $out[] = new Campo($row['id'], $row['nombre'], $row['ubicacion'], $row['superficie']);
    }
    return $out;
  }

  /** Buscar por ID */
  public function getCampoById(int $id): ?Campo
  {
    $stmt = $this->conn->prepare("SELECT id, nombre, ubicacion, superficie FROM campos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ? new Campo($row['id'], $row['nombre'], $row['ubicacion'], $row['superficie']) : null;
  }

  /** Buscar por nombre */
  public function getCampoByNombre(string $nombre): ?Campo
  {
    $stmt = $this->conn->prepare("SELECT id, nombre, ubicacion, superficie FROM campos WHERE nombre = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ? new Campo($row['id'], $row['nombre'], $row['ubicacion'], $row['superficie']) : null;
  }

  /** Eliminar en cascada: borra almacenes del campo y luego el campo */
  public function eliminarCampoYCascada(int $campoId): bool
  {
    try {
      $this->conn->begin_transaction();

      $stmt = $this->conn->prepare("DELETE FROM almacenes WHERE campoId = ?");
      $stmt->bind_param("i", $campoId);
      $stmt->execute();
      $stmt->close();

      $stmt2 = $this->conn->prepare("DELETE FROM campos WHERE id = ?");
      $stmt2->bind_param("i", $campoId);
      $ok = $stmt2->execute();
      $stmt2->close();

      if (!$ok) {
        $this->conn->rollback();
        return false;
      }

      $this->conn->commit();
      return true;
    } catch (Throwable $e) {
      $this->conn->rollback();
      return false;
    }
  }
}
