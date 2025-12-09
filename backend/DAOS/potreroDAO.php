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

  // 🔹 NUEVA FUNCIÓN: Verificar si una categoría ya está en uso por otro potrero
  public function isCategoriaUsed($categoriaId, $potreroId = null): bool
  {
    // Si no se proporciona categoría, se asume que no hay restricción.
    if ($categoriaId === null) {
      return false;
    }

    $sql = "SELECT id FROM potreros WHERE categoriaId = ?";
    $types = "i";
    $params = [$categoriaId];

    if ($potreroId !== null) {
      $sql .= " AND id <> ?";
      $types .= "i";
      $params[] = $potreroId;
    }

    $stmt = $this->conn->prepare($sql);

    // Uso de `...$params` para bind_param dinámico
    if (!empty($params)) {
      $stmt->bind_param($types, ...$params);
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
    $nombre = $potrero->getNombre();
    $pasturaId = $potrero->getPasturaId();
    $categoriaId = $potrero->getCategoriaId();
    $cantidadCategoria = $potrero->getCantidadCategoria();
    $campoId = $potrero->getCampoId();

    if ($this->existeNombre($nombre, $id))
      return false;

    $sql = "UPDATE potreros
            SET nombre = ?, pasturaId = ?, categoriaId = ?, cantidadCategoria = ?, campoId = ?
            WHERE id = ?";
    $stmt = $this->conn->prepare($sql);

    // Tipos: s (nombre), i (pasturaId), s (categoriaId), s (cantidadCategoria), i (campoId), i (id)
    $tipos = "sissii";

    $stmt->bind_param($tipos, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId, $id);

    $ok = $stmt->execute();

    // 📝 Agregamos registro de error para futura depuración
    if (!$ok) {
      error_log("Error al modificar potrero ID: {$id}. SQL Error: " . $this->conn->error);
    }

    $stmt->close();
    return $ok;
  }

  // Listar potreros con filtros dinámicos (Clave para que los filtros funcionen)
  public function listar(array $filtros = []): array
  {
    // Unimos los nombres de las tablas de referencia para poder devolver los nombres
    // y para usar los IDs de pastura/campo/categoría si la tabla original no los tiene.
    $sql = "SELECT p.*, 
                   pa.nombre AS pasturaNombre, 
                   c.nombre AS categoriaNombre,
                   c.cantidad AS categoriaCantidad, 
                   ca.nombre AS campoNombre
            FROM potreros p
            LEFT JOIN pasturas pa ON p.pasturaId = pa.id
            LEFT JOIN categorias c ON p.categoriaId = c.id
            LEFT JOIN campos ca ON p.campoId = ca.id
            WHERE 1=1";

    $params = [];
    $types = "";

    // -- Helper para crear la cláusula IN --
    $addInClause = function (&$sql, &$params, &$types, $key, $column) use ($filtros) {
      if (!empty($filtros[$key]) && is_array($filtros[$key])) {
        $placeholders = implode(',', array_fill(0, count($filtros[$key]), '?'));
        $sql .= " AND {$column} IN ({$placeholders})";

        foreach ($filtros[$key] as $id) {
          $params[] = $id;
          $types .= "i";
        }
        return true;
      }
      return false;
    };
    // ------------------------------------

    // CORRECCIÓN CRÍTICA: Usamos cláusula IN para múltiples IDs
    $addInClause($sql, $params, $types, 'campoId', 'p.campoId');
    $addInClause($sql, $params, $types, 'pasturaId', 'p.pasturaId');
    $addInClause($sql, $params, $types, 'categoriaId', 'p.categoriaId');


    // 🛑 MODIFICACIÓN AQUÍ: Se eliminó la condición "AND p.cantidadCategoria > 0"
    // para que el filtro traiga todos los potreros con categoría asignada.
    if (!empty($filtros['conCategoria'])) {
      $sql .= " AND p.categoriaId IS NOT NULL";
    }

    $sql .= " ORDER BY p.nombre ASC";

    $stmt = $this->conn->prepare($sql);

    // CORRECCIÓN: Usar bind_param solo si hay parámetros, y desempacando el array
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

  //  Obtener todos
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
      ? new Potrero(
        $res['id'],
        $res['nombre'],
        $res['pasturaId'],
        $res['categoriaId'],
        $res['cantidadCategoria'],
        $res['campoId']
      )
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

  // =============================================================
  // NUEVOS MÉTODOS PARA ORDEN CONTROLLER
  // =============================================================

  // 🔹 Obtener los detalles del potrero y campo por categoriaId
  public function getPotreroDetailsByCategoriaId($categoriaId): ?array
  {
    // Buscamos el potrero que tiene esta categoria asignada
    $sql = "SELECT 
                  p.id AS potreroId, 
                  p.nombre AS potreroNombre,
                  c.id AS categoriaId,
                  c.nombre AS categoriaNombre,
                  ca.nombre AS campoNombre
              FROM potreros p
              JOIN categorias c ON p.categoriaId = c.id
              LEFT JOIN campos ca ON p.campoId = ca.id
              WHERE p.categoriaId = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $categoriaId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res;
  }

  // 🔹 Obtener todas las categorías que están asignadas a un potrero (para el SELECT del form)
  public function getAllCategoriasConPotrero(): array
  {
    // MODIFICADO: Añadido ca.nombre AS campoNombre y LEFT JOIN campos
    $sql = "SELECT 
                  DISTINCT c.id, 
                  c.nombre, 
                  p.nombre AS potreroNombre,
                  ca.nombre AS campoNombre
              FROM categorias c
              JOIN potreros p ON c.id = p.categoriaId
              LEFT JOIN campos ca ON p.campoId = ca.id
              ORDER BY c.nombre ASC";

    $result = $this->conn->query($sql);

    $categorias = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
      }
    }
    return $categorias;
  }

  // 🔹 Mover categoría entre potreros (MOVER TOTAL)
  public function moverCategoria($idOrigen, $idDestino)
  {
    $this->conn->begin_transaction();

    try {
      // 1️⃣ Obtener datos de origen (categoría y cantidad desde la tabla categorias)
      $sqlSelect = "
            SELECT 
                p.categoriaId,
                COALESCE(p.cantidadCategoria, c.cantidad) AS cantidadTotal
            FROM potreros p
            LEFT JOIN categorias c ON c.id = p.categoriaId
            WHERE p.id = ?
        ";
      $stmt = $this->conn->prepare($sqlSelect);
      $stmt->bind_param("i", $idOrigen);
      $stmt->execute();
      $res = $stmt->get_result();

      if (!$res || $res->num_rows === 0) {
        $this->conn->rollback();
        return ['tipo' => 'error', 'mensaje' => 'Potrero origen no encontrado.'];
      }

      $row = $res->fetch_assoc();
      $stmt->close();

      $categoriaId = $row['categoriaId'];
      $cantidadTotalMover = (int) $row['cantidadTotal'];

      // 2️⃣ Validar que haya animales/cantidad
      if ($categoriaId === null || $cantidadTotalMover <= 0) {
        $this->conn->rollback();
        return ['tipo' => 'error', 'mensaje' => 'El potrero origen no tiene una categoría válida o su cantidad es 0.'];
      }

      // 3️⃣ Verificar que el potrero destino exista y esté vacío
      $sqlSelectDestino = "SELECT categoriaId FROM potreros WHERE id = ?";
      $stmtDestino = $this->conn->prepare($sqlSelectDestino);
      $stmtDestino->bind_param("i", $idDestino);
      $stmtDestino->execute();
      $rowDestino = $stmtDestino->get_result()->fetch_assoc();
      $stmtDestino->close();

      if (!$rowDestino) {
        $this->conn->rollback();
        return ['tipo' => 'error', 'mensaje' => 'Potrero destino no encontrado.'];
      }

      if ($rowDestino['categoriaId'] !== null) {
        $this->conn->rollback();
        return ['tipo' => 'error', 'mensaje' => 'El potrero destino no está vacío. Solo se puede mover a potreros libres.'];
      }

      // 4️⃣ Actualizar destino con la categoría y cantidad total
      $sqlUpdateDestino = "
            UPDATE potreros
            SET categoriaId = ?, cantidadCategoria = ?
            WHERE id = ?
        ";
      $stmt2 = $this->conn->prepare($sqlUpdateDestino);
      $stmt2->bind_param("iii", $categoriaId, $cantidadTotalMover, $idDestino);
      $stmt2->execute();
      $stmt2->close();

      // 5️⃣ Limpiar origen (quitar categoría y cantidad)
      $sqlUpdateOrigen = "
            UPDATE potreros
            SET categoriaId = NULL, cantidadCategoria = NULL
            WHERE id = ?
        ";
      $stmt3 = $this->conn->prepare($sqlUpdateOrigen);
      $stmt3->bind_param("i", $idOrigen);
      $stmt3->execute();
      $stmt3->close();

      // 6️⃣ Confirmar la transacción
      $this->conn->commit();

      return ['tipo' => 'success', 'mensaje' => 'Movimiento de la categoría completado correctamente.'];
    } catch (Exception $e) {
      $this->conn->rollback();
      error_log("Error en moverCategoria: " . $e->getMessage());
      return ['tipo' => 'error', 'mensaje' => 'Error en el proceso de movimiento.'];
    }
  }

}
?>