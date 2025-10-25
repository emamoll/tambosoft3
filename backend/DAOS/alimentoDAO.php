<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla de la alimento.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/alimento/alimentoModelo.php';
require_once __DIR__ . '../../modelos/alimento/alimentoTabla.php';

// Clase para el acceso a datos (DAO) de la tabla alimentos
class AlimentoDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new AlimentoCrearTabla($this->db);
    $this->crearTabla->crearTablaTiposAlimentosId();
    $this->crearTabla->insertarTiposAlimentosPredeterminados();
    $this->crearTabla->crearTablaAlimento();
    $this->conn = $this->db->connect();
  }

  /**
   * Verifica si existe un Alimento con el mismo nombre.
   * Si se pasa un $id, lo excluye de la validación (para modificaciones).
   */
  public function existeNombreYTipo($tipoAlimentoId, $nombre, $id = null): bool
  {
    $sql = "SELECT id FROM alimentos WHERE LOWER(TRIM(nombre)) = LOWER(?)";
    if ($id !== null) {
      $sql .= " AND id <> ?";
    }

    $stmt = $this->conn->prepare($sql);
    if ($id !== null) {
      $stmt->bind_param("isi", $tipoAlimentoId, $nombre, $id);
    } else {
      $stmt->bind_param("is", $tipoAlimentoId, $nombre);
    }

    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
  }

  // Listar alimentos con filtros dinámicos
  public function listar(array $filtros = []): array
  {
    // Unimos los nombres de las tablas de referencia para poder devolver los nombres
    $sql = "SELECT a.*, 
                   ta.nombre AS tipoAlimentoNombre
            FROM alimentos a
            LEFT JOIN tiposAlimentos ta ON  a.tipoAlimentoId = ta.id
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

    //  CORRECCIÓN CRÍTICA: Usamos cláusula IN para múltiples IDs
    $addInClause($sql, $params, $types, 'tipoAlimentoId', 'a.tipoAlimentoId');

    $sql .= " ORDER BY a.nombre ASC";

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

  // Registrar un nuev alimento
  public function registrarAlimento(Alimento $alimento): bool
  {
    $tipoAlimentoId = trim($alimento->getTipoAlimentoId());
    $nombre = trim($alimento->getNombre());

    // Verificación de duplicado usando existeNombreYTipo
    if ($this->existeNombreYTipo($tipoAlimentoId,$nombre)) {
      return false;
    }

    $sql = "INSERT INTO alimentos (tipoAlimentoId, nombre) VALUES (?, ?)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("is", $tipoAlimentoId, $nombre);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  // Modificar un alimento existente
  public function modificarAlimento(Alimento $alimento): bool
  {
    $id = $alimento->getId();
    $tipoAlimentoId = trim($alimento->getTipoAlimentoId());
    $nombre = trim($alimento->getNombre());

    // Verificación de duplicado excluyendo el propio ID
    if ($this->existeNombreYTipo($tipoAlimentoId, $nombre, $id)) {
      return false;
    }

    $sql = "UPDATE alimentos SET tipoAlimentoId = ?, nombre = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("isi", $tipoAlimentoId, $nombre, $id);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  // Obtener todos los alimentos
  public function getAllAlimentos(): array
  {
    $sql = "SELECT * FROM alimentos";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $alimentos = [];
    while ($row = $result->fetch_assoc()) {
      $alimentos[] = new Alimento($row['id'], $row['tipoAlimentoId'],$row['nombre']);
    }
    return $alimentos;
  }

  // Obtener un alimento por ID
  public function getAlimentoById($id): ? Alimento
  {
    $sql = "SELECT * FROM alimentos WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Alimento($row['id'], $row['tipoAlimentoId'],$row['nombre']) : null;
  }

    // Obtener una alimento por tipo
  public function getAlimentoByTipoAlimentoId($tipoAlimentoId): ? Alimento
  {
    $sql = "SELECT * FROM alimentos WHERE tipo$tipoAlimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $tipoAlimentoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Alimento($row['id'], $row['tipoAlimentoId'], $row['nombre']) : null;
  }

  // Obtener una alimento por nombre
  public function getAlimentoByNombre($nombre): ? Alimento
  {
    $sql = "SELECT * FROM alimentos WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Alimento($row['id'], $row['tipoAlimentoId'],$row['nombre']) : null;
  }

  // Eliminar una alimento
  public function eliminarAlimento($id): bool
  {
    $sql = "DELETE FROM alimentos WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
  }
}