<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla de la pastura.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/pastura/pasturaModelo.php';
require_once __DIR__ . '../../modelos/pastura/pasturaTabla.php';

// Clase para el acceso a datos (DAO) de la tabla Pasturas
class PasturaDAO
{
  // Propiedades privadas para la conexión y la creación de la tabla.
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new PasturaCrearTabla($this->db);
    $this->crearTabla->crearTablaPastura();
    $this->conn = $this->db->connect();
  }

  public function registrarPastura(Pastura $pastura)
  {
    $nombre = trim($pastura->getNombre());
    $fechaSiembra = $pastura->getFechaSiembra();

    // 1. Verificar si la pastura ya existe (la forma correcta y explícita).
    $sqlVer = "SELECT id FROM pasturas WHERE LOWER(TRIM(nombre)) = LOWER(?)";
    $stmtVer = $this->conn->prepare($sqlVer);
    $stmtVer->bind_param("s", $nombre);

    if (!$stmtVer->execute()) {
      error_log("Error en SELECT duplicado: " . $stmtVer->error);
      $stmtVer->close();
      return ['ok' => false, 'dup' => false];
    }

    $stmtVer->store_result();
    $existe = $stmtVer->num_rows > 0;
    $stmtVer->close();

    // Si ya existe, devolvemos un error de duplicado inmediatamente.
    if ($existe) {
      return ['ok' => false, 'dup' => true];
    }

    // 2. Si no existe, procedemos con la inserción.
    $sql = "INSERT INTO pasturas (nombre, fechaSiembra) VALUES (?, ?)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ss", $nombre, $fechaSiembra);

    if (!$stmt->execute()) {
      error_log("Error al insertar pastura: " . $stmt->error);
      $stmt->close();
      return ['ok' => false, 'dup' => false];
    }

    $stmt->close();
    return ['ok' => true];
  }

  // Obtiene todas las pasturas de la base de datos
  public function getAllPasturas()
  {
    $sql = "SELECT * FROM pasturas";
    $result = $this->conn->query($sql);

    if (!$result) {
      die("Error en la consulta: " . $this->conn->error);
    }

    $pasturas = [];
    while ($row = $result->fetch_assoc()) {
      $pasturas[] = new Pastura($row['id'], $row['nombre'], $row['fechaSiembra']);
    }
    return $pasturas;
  }

  // Obtiene una pastura por su ID.
  public function getPasturaById($id)
  {
    $sql = "SELECT * FROM pasturas WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
      return new Pastura($row['id'], $row['nombre'], $row['fechaSiembra']);
    }
    return null;
  }

  // Obtiene una pastura por su nombre.
  public function getPasturaByNombre($nombre)
  {
    $sql = "SELECT * FROM pasturas WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
      return new Pastura($row['id'], $row['nombre'], $row['fechaSiembra']);
    }
    return null;
  }

  // Obtiene una pastura por su fecha de siembra.
  public function getPasturaByFechaSiembra($fechaSiembra)
  {
    $sql = "SELECT * FROM pasturas WHERE fechaSiembra = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $fechaSiembra);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
      return new Pastura($row['id'], $row['nombre'], $row['fechaSiembra']);
    }
    return null;
  }

  // Modifica una pastura existente
  public function modificarPastura(Pastura $pastura)
  {
    $sqlVer = "SELECT id FROM pasturas WHERE nombre = ? AND id <> ?";
    $stmtVer = $this->conn->prepare($sqlVer);
    $id = $pastura->getId();
    $nombre = $pastura->getNombre();
    $fechaSiembra = $pastura->getFechaSiembra();
    $stmtVer->bind_param("si", $nombre, $id);
    $stmtVer->execute();
    $stmtVer->store_result();
    if ($stmtVer->num_rows > 0) {
      $stmtVer->close();
      return ['ok' => false, 'dup' => true];
    }
    $stmtVer->close();

    $sql = "UPDATE pasturas SET nombre = ?, fechaSiembra = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ssi", $nombre, $fechaSiembra, $id);
    if (!$stmt->execute()) {
      if ($stmt->errno === 1062) {
        $stmt->close();
        return ['ok' => false, 'dup' => true];
      }
      $stmt->close();
      return ['ok' => false, 'dup' => false];
    }
    $stmt->close();
    return ['ok' => true];
  }

  // Elimina una pastura por su id.
  public function eliminarPastura($id)
  {
    $sql = "DELETE FROM pasturas WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }
}