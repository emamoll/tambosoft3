<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla de la potreros.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/potrero/potreroModelo.php';
require_once __DIR__ . '../../modelos/potrero/potreroTabla.php';

// Clase para el acceso a datos (DAO) de la tabla Potreros
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

  // Verifica si existe un potrero con el mismo nombre
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

  // Registrar un nuevo potrero
  public function registrarPotrero(Potrero $potrero): bool
  {
    $nombre = trim($potrero->getNombre());
    $pasturaId = $potrero->getPasturaId();
    $categoriaId = $potrero->getCategoriaId() ?: null;
    $cantidadCategoria = $potrero->getCantidadCategoria() ?: null;
    $campoId = $potrero->getCampoId();

    if ($this->existeNombre($nombre)) {
      return false;
    }

    $sql = "INSERT INTO potreros (nombre, pasturaId, categoriaId, cantidadCategoria, campoId)
          VALUES (?, ?, ?, ?, ?)";

    $stmt = $this->conn->prepare($sql);

    $stmt->bind_param("siiii", $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId);

    // Ejecutar
    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
  }

  // Modificar un potrero existente
  public function modificarPotrero(Potrero $potrero): bool
  {
    $id = $potrero->getId();
    $nombre = trim($potrero->getNombre());
    $pasturaId = $potrero->getPasturaId();
    $categoriaId = $potrero->getCategoriaId() ?: null;
    $cantidadCategoria = $potrero->getCantidadCategoria() ?: null;
    $campoId = $potrero->getCampoId();

    if ($this->existeNombre($nombre, $id)) {
      return false;
    }

    $sql = "UPDATE potreros
          SET nombre = ?, pasturaId = ?, categoriaId = ?, cantidadCategoria = ?, campoId = ?
          WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("siiiii", $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId, $id);

    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
  }
  // Obtener todas los potreros
  public function getAllPotreros(): array
  {
    $sql = "SELECT * FROM potreros";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $potreros = [];
    while ($row = $result->fetch_assoc()) {
      $potreros[] = new Potrero($row['id'], $row['nombre'], $row['pasturaId'], $row['categoriaId'], $row['cantidadCategoria'], $row['campoId']);
    }
    return $potreros;
  }

  // Obtener un potrero por ID
  public function getPotreroById($id): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Potrero($row['id'], $row['nombre'], $row['pasturaId'], $row['categoriaId'], $row['cantidadCategoria'], $row['campoId']) : null;
  }

  // Obtener un potrero por nombre
  public function getPotreroByNombre($nombre): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Potrero($row['id'], $row['nombre'], $row['pasturaId'], $row['categoriaId'], $row['cantidadCategoria'], $row['campoId']) : null;
  }

  // Obtener un potrero por pastura
  public function getPotreroByPastura($pasturaId): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE pasturaId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $pasturaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Potrero($row['id'], $row['nombre'], $row['pasturaId'], $row['categoriaId'], $row['cantidadCategoria'], $row['campoId']) : null;
  }

  // Obtener un potrero por categoria
  public function getPotreroByCategoria($categoriaId): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE categoriaId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $categoriaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Potrero($row['id'], $row['nombre'], $row['pasturaId'], $row['categoriaId'], $row['cantidadCategoria'], $row['campoId']) : null;
  }

  // Obtener un potrero por cantidad de categoria
  public function getPotreroByCantidadCategoria($cantidadCategoria): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE cantidadCategoria = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $cantidadCategoria);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Potrero($row['id'], $row['nombre'], $row['pasturaId'], $row['categoriaId'], $row['cantidadCategoria'], $row['campoId']) : null;
  }

  // Obtener un potrero por campo
  public function getPotreroByCampo($campoId): ?Potrero
  {
    $sql = "SELECT * FROM potreros WHERE campoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $campoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Potrero($row['id'], $row['nombre'], $row['pasturaId'], $row['categoriaId'], $row['cantidadCategoria'], $row['campoId']) : null;
  }

  // Eliminar un potrero
  public function eliminarPotrero($id): bool
  {
    $sql = "DELETE FROM potreros WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }

  public function moverCategoria($idOrigen, $idDestino)
  {
    try {
      // 🔹 1. Obtener los datos de origen
      $sqlSelect = "SELECT categoriaId, cantidadCategoria FROM potreros WHERE id = ?";
      $stmt = $this->conn->prepare($sqlSelect);
      $stmt->bind_param("i", $idOrigen);
      $stmt->execute();
      $res = $stmt->get_result();

      if (!$res || $res->num_rows === 0) {
        return ['tipo' => 'error', 'mensaje' => 'Potrero origen no encontrado'];
      }

      $row = $res->fetch_assoc();
      $categoriaId = $row['categoriaId'];
      $cantidadCategoria = $row['cantidadCategoria'];
      $stmt->close();

      // 🔹 2. Validar que haya categoría para mover
      if ($categoriaId === null) {
        return ['tipo' => 'error', 'mensaje' => 'El potrero origen no tiene una categoría asignada'];
      }

      // 🔹 3. Actualizar destino
      $sqlUpdate = "UPDATE potreros SET categoriaId = ?, cantidadCategoria = ? WHERE id = ?";
      $stmt2 = $this->conn->prepare($sqlUpdate);
      $stmt2->bind_param("iii", $categoriaId, $cantidadCategoria, $idDestino);
      $stmt2->execute();
      $stmt2->close();

      // 🔹 4. Limpiar origen
      $sqlClear = "UPDATE potreros SET categoriaId = NULL, cantidadCategoria = NULL WHERE id = ?";
      $stmt3 = $this->conn->prepare($sqlClear);
      $stmt3->bind_param("i", $idOrigen);
      $stmt3->execute();
      $stmt3->close();

      return ['tipo' => 'success', 'mensaje' => 'Categoría movida correctamente'];
    } catch (Exception $e) {
      return ['tipo' => 'error', 'mensaje' => 'Error al mover la categoría: ' . $e->getMessage()];
    }
  }
}