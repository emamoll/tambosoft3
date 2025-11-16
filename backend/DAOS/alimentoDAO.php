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
    $sql = "SELECT id
            FROM alimentos
           WHERE LOWER(TRIM(nombre)) = LOWER(?)
             AND tipoAlimentoId = ?";

    if ($id !== null) {
      $sql .= " AND id <> ?";
    }

    $stmt = $this->conn->prepare($sql);
    if ($id !== null) {
      $stmt->bind_param("sii", $nombre, $tipoAlimentoId, $id);
    } else {
      $stmt->bind_param("si", $nombre, $tipoAlimentoId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();
    return $existe;
  }

  // Listar alimentos con filtros dinámicos
  public function listar(array $filtros = []): array
  {
    // Consulta base con JOIN al tipo de alimento
    $sql = "SELECT 
          a.id,
          a.nombre,
          a.tipoAlimentoId,
          ta.tipoAlimento AS tipoAlimentoNombre
        FROM alimentos a
        LEFT JOIN tiposAlimentos ta ON a.tipoAlimentoId = ta.id
        WHERE 1=1";

    $params = [];
    $types = "";

    // Helper para filtros dinámicos (IN)
    $addInClause = function (&$sql, &$params, &$types, $key, $column, $typeChar) use ($filtros) {
      if (!empty($filtros[$key]) && is_array($filtros[$key])) {
        $placeholders = implode(',', array_fill(0, count($filtros[$key]), '?'));
        $sql .= " AND {$column} IN ({$placeholders})";
        foreach ($filtros[$key] as $valor) {
          $params[] = $valor;
          $types .= $typeChar;
        }
        return true;
      }
      return false;
    };

    // === Filtros dinámicos ===
    $addInClause($sql, $params, $types, 'tipoAlimentoId', 'a.tipoAlimentoId', 'i');
    $addInClause($sql, $params, $types, 'nombre', 'a.nombre', 's');

    $sql .= " ORDER BY a.nombre ASC";

    // Preparar y ejecutar la consulta
    $stmt = $this->conn->prepare($sql);
    if ($stmt === false) {
      throw new Exception("Error al preparar SQL: " . $this->conn->error . " | SQL: " . $sql);
    }

    if (!empty($params)) {
      $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $alimentos = [];
    while ($r = $result->fetch_assoc()) {
      $alimentos[] = $r;
    }
    $stmt->close();

    // === Opciones para filtros (tipos + nombres) ===
    $tiposDisponibles = [];
    $rsTipos = $this->conn->query("SELECT id, tipoAlimento FROM tiposAlimentos ORDER BY tipoAlimento");
    while ($row = $rsTipos->fetch_assoc()) {
      $tiposDisponibles[] = $row;
    }

    $nombresDisponibles = [];
    $rsNombres = $this->conn->query("SELECT DISTINCT nombre FROM alimentos WHERE nombre IS NOT NULL AND nombre <> '' ORDER BY nombre");
    while ($row = $rsNombres->fetch_row()) {
      $nombresDisponibles[] = $row[0];
    }

    // === Retornar todo en una sola estructura ===
    return [
      'alimentos' => $alimentos,
      'opciones' => [
        'tipos' => $tiposDisponibles,
        'nombres' => $nombresDisponibles
      ]
    ];
  }



  // Registrar un nuev alimento
  public function registrarAlimento(Alimento $alimento): bool
  {
    $tipoAlimentoId = trim($alimento->getTipoAlimentoId());
    $nombre = trim($alimento->getNombre());

    if ($this->existeNombreYTipo($tipoAlimentoId, $nombre)) {
      return false;
    }

    $sql = "INSERT INTO alimentos (tipoAlimentoId, nombre) VALUES (?, ?)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("is", $tipoAlimentoId, $nombre);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
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
      $alimentos[] = new Alimento($row['id'], $row['tipoAlimentoId'], $row['nombre']);
    }
    return $alimentos;
  }

  // Obtener un alimento por ID
  public function getAlimentoById($id): ?Alimento
  {
    $sql = "SELECT * FROM alimentos WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Alimento($row['id'], $row['tipoAlimentoId'], $row['nombre']) : null;
  }

  // Obtener una alimento por tipo
  public function getAlimentoByTipoAlimentoId($tipoAlimentoId): ?Alimento
  {
    $sql = "SELECT * FROM alimentos WHERE tipoAlimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $tipoAlimentoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Alimento($row['id'], $row['tipoAlimentoId'], $row['nombre']) : null;
  }


  // Obtener una alimento por nombre
  public function getAlimentoByNombre($nombre): ?Alimento
  {
    $sql = "SELECT * FROM alimentos WHERE nombre = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? new Alimento($row['id'], $row['tipoAlimentoId'], $row['nombre']) : null;
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