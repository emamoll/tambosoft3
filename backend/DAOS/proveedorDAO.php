<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla de la proveedores.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/proveedor/proveedorModelo.php';
require_once __DIR__ . '../../modelos/proveedor/proveedorTabla.php';

// Clase para el acceso a datos (DAO) de la tabla Proveedores
class ProveedorDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new ProveedorCrearTabla($this->db);
    $this->crearTabla->crearTablaProveedor();
    $this->conn = $this->db->connect();
  }

  // Verifica si existe un proveedor con la misma denominacion
  public function existeDenominacion($denominacion, $id = null): bool
  {
    $sql = "SELECT id FROM proveedores WHERE LOWER(TRIM(denominacion)) = LOWER(?)";
    if ($id !== null) {
      $sql .= " AND id <> ?";
    }

    $stmt = $this->conn->prepare($sql);
    if ($id !== null) {
      $stmt->bind_param("si", $denominacion, $id);
    } else {
      $stmt->bind_param("s", $denominacion);
    }

    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
  }

  // Registrar un nuevo proveedor
  public function registrarProveedor(Proveedor $proveedor): bool
  {
    $denominacion = trim($proveedor->getDenominacion());
    $emailP = $proveedor->getEmailP();
    $telefono = $proveedor->getTelefono() ?: null;

    if ($this->existeDenominacion($denominacion)) {
      return false;
    }

    $sql = "INSERT INTO proveedores (denominacion, emailP, telefono)
          VALUES (?, ?, ?)";

    $stmt = $this->conn->prepare($sql);

    $stmt->bind_param("ssi", $denominacion, $emailP, $telefono);

    // Ejecutar
    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
  }

  // Modificar un proveedor existente
  public function modificarProveedor(Proveedor $proveedor): bool
  {
    $id = $proveedor->getId();
    $denominacion = trim($proveedor->getDenominacion());
    $emailP = $proveedor->getEmailP();
    $telefono = $proveedor->getTelefono() ?: null;

    if ($this->existeDenominacion($denominacion, $id)) {
      return false;
    }

    $sql = "UPDATE proveedores
          SET denominacion = ?, emailP = ?, telefono = ?
          WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ssii", $denominacion, $emailP, $telefono, $id);

    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
  }
  // Obtener todas los proveedores
  public function getAllProveedores(): array
  {
    $sql = "SELECT * FROM proveedores";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $proveedores = [];
    while ($row = $result->fetch_assoc()) {
      $proveedores[] = new Proveedor($row['id'], $row['denominacion'], $row['emailP'], $row['telefono']);
    }
    return $proveedores;
  }

  // Obtener un proveedor por ID
  public function getProveedorById($id): ?Proveedor
  {
    $sql = "SELECT * FROM proveedores WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Proveedor($row['id'], $row['denominacion'], $row['emailP'], $row['telefono']) : null;
  }

  // Obtener un proveedor por denominacion
  public function getProveedorByDenominacion($denominacion): ?Proveedor
  {
    $sql = "SELECT * FROM proveedores WHERE denomi$denominacion = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $denominacion);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Proveedor($row['id'], $row['denominacion'], $row['emailP'], $row['telefono']) : null;
  }

  // Eliminar un proveedor
  public function eliminarProveedor($id): bool
  {
    $sql = "DELETE FROM proveedores WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }
}