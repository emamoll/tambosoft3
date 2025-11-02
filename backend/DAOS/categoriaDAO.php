<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla de categorias.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/categoria/categoriaModelo.php';
require_once __DIR__ . '../../modelos/categoria/categoriaTabla.php';

// Clase para el acceso a datos (DAO) de la tabla Categorias
class CategoriaDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new CategoriaCrearTabla($this->db);
    $this->crearTabla->crearTablaCategoria();
    $this->conn = $this->db->connect();
  }

  // Verifica si existe una categoria con el mismo nombre

  public function existeNombre($nombre, $id = null): bool
  {
    $sql = "SELECT id FROM categorias WHERE LOWER(TRIM(nombre)) = LOWER(?)";
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

  // Registrar una nueva categoria
  public function registrarCategoria(Categoria $categoria): bool
  {
    $nombre = trim($categoria->getNombre());
    $cantidad = trim($categoria->getCantidad());

    // Verificación de duplicado usando existeNombre
    if ($this->existeNombre($nombre)) {
      return false;
    }

    $sql = "INSERT INTO categorias (nombre, cantidad) VALUES (?, ?)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("si", $nombre, $cantidad);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  // Modificar una categoria existente
  public function modificarCategoria(Categoria $categoria): bool
  {
    $id = $categoria->getId();
    $nombre = trim($categoria->getNombre());
    $cantidad = trim($categoria->getCantidad());

    // Verificación de duplicado excluyendo el propio ID
    if ($this->existeNombre($nombre, $id)) {
      return false;
    }

    $sql = "UPDATE categorias SET nombre = ?, cantidad = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("sii", $nombre, $cantidad, $id);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  // Obtener todas las categorias
  public function getAllCategorias(): array
  {
    $sql = "SELECT * FROM categorias";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $categorias = [];
    while ($row = $result->fetch_assoc()) {
      $categorias[] = new Categoria($row['id'], $row['nombre'], $row['cantidad']);
    }
    return $categorias;
  }

  // Obtener una categoria por ID
  public function getCategoriaById($id): ?Categoria
  {
    $sql = "SELECT * FROM categorias WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Categoria($row['id'], $row['nombre'], $row['cantidad']) : null;
  }

  // Obtener una categoria por nombre
  public function getCategoriaByNombre($nombre): ?Categoria
  {
    $sql = "SELECT * FROM categorias WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Categoria($row['id'], $row['nombre'], $row['cantidad']) : null;
  }

  // Eliminar una categoria
  public function eliminarCategoria($id): bool
  {
    $sql = "DELETE FROM categorias WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }
}