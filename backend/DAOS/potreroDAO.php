<?php
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/potrero/potreroModelo.php';
require_once __DIR__ . '../../modelos/potrero/potreroTabla.php';

class PotreroDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new PotreroCrearTabla($this->db);
    $this->crearTabla->crearTablaPotrero();
    $this->conn = $this->db->connect();
  }

  // 🔹 Verifica si existe un potrero con el mismo nombre
  public function existeNombre($nombre, $id = null): bool
  {
    $sql = "SELECT id FROM potreros WHERE LOWER(TRIM(nombre)) = LOWER(?)";
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

  // 🔹 Registrar potrero
  public function registrarPotrero(Potrero $potrero): bool
  {
    $nombre = trim($potrero->getNombre());
    $pasturaId = $potrero->getPasturaId();
    $categoriaId = $potrero->getCategoriaId() ?: null;
    $cantidadCategoria = $potrero->getCantidadCategoria() ?: null;
    $campoId = $potrero->getCampoId();

    if ($this->existeNombre($nombre))
      return false;

    $sql = "INSERT INTO potreros (nombre, pasturaId, categoriaId, cantidadCategoria, campoId)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("siiii", $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId);

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // 🔹 Modificar potrero
  public function modificarPotrero(Potrero $potrero): bool
  {
    $id = $potrero->getId();
    $nombre = trim($potrero->getNombre());
    $pasturaId = $potrero->getPasturaId();
    $categoriaId = $potrero->getCategoriaId() ?: null;
    $cantidadCategoria = $potrero->getCantidadCategoria() ?: null;
    $campoId = $potrero->getCampoId();

    if ($this->existeNombre($nombre, $id))
      return false;

    $sql = "UPDATE potreros
            SET nombre = ?, pasturaId = ?, categoriaId = ?, cantidadCategoria = ?, campoId = ?
            WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("siiiii", $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId, $id);

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // 🔹 Listar potreros con filtros dinámicos (Clave para que los filtros funcionen)
  public function listar(array $filtros = []): array
  {
    $sql = "SELECT * FROM potreros WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($filtros['campoId'])) {
      $sql .= " AND campoId = ?";
      $params[] = $filtros['campoId'];
      $types .= "i";
    }

    if (!empty($filtros['pasturaId'])) {
      $sql .= " AND pasturaId = ?";
      $params[] = $filtros['pasturaId'];
      $types .= "i";
    }

    if (!empty($filtros['categoriaId'])) {
      $sql .= " AND categoriaId = ?";
      $params[] = $filtros['categoriaId'];
      $types .= "i";
    }

    // Filtro especial: sólo los que tienen categoría asignada
    if (!empty($filtros['conCategoria'])) {
      $sql .= " AND categoriaId IS NOT NULL";
    }

    $sql .= " ORDER BY nombre ASC";

    $stmt = $this->conn->prepare($sql);

    // CORRECCIÓN: Usar bind_param solo si hay parámetros
    if (!empty($params)) {
      $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($r = $result->fetch_assoc()) {
      $rows[] = $r;
    }

    $stmt->close();
    return $rows;
  }

  // 🔹 Obtener todos
  public function getAllPotreros(): array
  {
    $sql = "SELECT * FROM potreros ORDER BY nombre ASC";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $potreros = [];
    while ($row = $result->fetch_assoc()) {
      $potreros[] = new Potrero(
        $row['id'],
        $row['nombre'],
        $row['pasturaId'],
        $row['categoriaId'],
        $row['cantidadCategoria'],
        $row['campoId']
      );
    }
    return $potreros;
  }

  // 🔹 Obtener por ID
  public function getPotreroById($id): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res
      ? new Potrero($res['id'], $res['nombre'], $res['pasturaId'], $res['categoriaId'], $res['cantidadCategoria'], $res['campoId'])
      : null;
  }

  // 🔹 Obtener por campo (RESTAURADO)
  public function getPotreroByCampo($campoId): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE campoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $campoId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r
      ? new Potrero($r['id'], $r['nombre'], $r['pasturaId'], $r['categoriaId'], $r['cantidadCategoria'], $r['campoId'])
      : null;
  }

  public function getPotreroByPastura($pasturaId): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE pasturaId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $pasturaId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r
      ? new Potrero($r['id'], $r['nombre'], $r['pasturaId'], $r['categoriaId'], $r['cantidadCategoria'], $r['campoId'])
      : null;
  }

  public function getPotreroByCategoria($categoriaId): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE categoriaId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $categoriaId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r
      ? new Potrero($r['id'], $r['nombre'], $r['pasturaId'], $r['categoriaId'], $r['cantidadCategoria'], $r['campoId'])
      : null;
  }

  // 🔹 Eliminar potrero
  public function eliminarPotrero($id): bool
  {
    $sql = "DELETE FROM potreros WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // 🔹 Mover categoría entre potreros (MOVER TOTAL)
  public function moverCategoria($idOrigen, $idDestino)
  {
    $this->conn->begin_transaction();

    try {
      // 1️⃣ Obtener datos de origen (cantidad y categoría)
      $sqlSelect = "SELECT categoriaId, cantidadCategoria FROM potreros WHERE id = ?";
      $stmt = $this->conn->prepare($sqlSelect);
      $stmt->bind_param("i", $idOrigen);
      $stmt->execute();
      $res = $stmt->get_result();

      if (!$res || $res->num_rows === 0) {
        $this->conn->rollback();
        return ['tipo' => 'error', 'mensaje' => 'Potrero origen no encontrado'];
      }

      $row = $res->fetch_assoc();
      $categoriaId = $row['categoriaId'];
      $cantidadTotalMover = $row['cantidadCategoria'];
      $stmt->close();

      // 2️⃣ Validar que haya animales para mover
      if ($categoriaId === null || $cantidadTotalMover === null || $cantidadTotalMover <= 0) {
        $this->conn->rollback();
        return ['tipo' => 'error', 'mensaje' => 'El potrero origen no tiene animales asignados.'];
      }

      // 3️⃣ Verificar que destino esté vacío 
      $sqlSelectDestino = "SELECT categoriaId FROM potreros WHERE id = ?";
      $stmtDestino = $this->conn->prepare($sqlSelectDestino);
      $stmtDestino->bind_param("i", $idDestino);
      $stmtDestino->execute();
      $rowDestino = $stmtDestino->get_result()->fetch_assoc();
      $stmtDestino->close();

      if ($rowDestino === null) {
        $this->conn->rollback();
        return ['tipo' => 'error', 'mensaje' => 'Potrero destino no encontrado'];
      }

      if ($rowDestino['categoriaId'] !== null) {
        $this->conn->rollback();
        return ['tipo' => 'error', 'mensaje' => 'El potrero destino no está vacío. Solo se permite mover a potreros libres.'];
      }

      // 4️⃣ Actualizar destino (Mover la cantidad TOTAL)
      $sqlUpdateDestino = "UPDATE potreros SET categoriaId = ?, cantidadCategoria = ? WHERE id = ?";
      $stmt2 = $this->conn->prepare($sqlUpdateDestino);
      $stmt2->bind_param("iii", $categoriaId, $cantidadTotalMover, $idDestino);
      $stmt2->execute();
      $stmt2->close();

      // 5️⃣ Actualizar origen (Limpiar categoría y cantidad)
      $sqlUpdateOrigen = "UPDATE potreros SET categoriaId = NULL, cantidadCategoria = NULL WHERE id = ?";
      $stmt3 = $this->conn->prepare($sqlUpdateOrigen);
      $stmt3->bind_param("i", $idOrigen);
      $stmt3->execute();
      $stmt3->close();

      $this->conn->commit();
      return ['tipo' => 'success', 'mensaje' => 'Movimiento de la categoría completado correctamente.'];

    } catch (Exception $e) {
      $this->conn->rollback();
      error_log("Error en moverCategoria: " . $e->getMessage());
      return ['tipo' => 'error', 'mensaje' => 'Error en el proceso de movimiento.'];
    }
  }
}