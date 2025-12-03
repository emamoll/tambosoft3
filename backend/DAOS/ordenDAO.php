<?php
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/orden/ordenModelo.php';
require_once __DIR__ . '../../modelos/orden/ordenTabla.php';

class OrdenkDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new OrdenCrearTabla($this->db);
    $this->crearTabla->crearTablaEstados();
    $this->crearTabla->insertarValoresTablaEstados();
    $this->crearTabla->crearTablaOrden();
    $this->conn = $this->db->connect();
  }

  // Registrar orden
  public function registrarOrden(Orden $orden): bool
  {
    $potreroId = $orden->getPotreroId();
    $tipoAlimentoId = $orden->getTipoAlimentoId();
    $alimentoId = $orden->getAlimentoId();
    $cantidad = $orden->getCantidad();
    $usuarioId = $orden->getUsuarioId();
    $estadoId = $orden->getEstadoId();
    $fechaCreacion = (int) $orden->getFechaCreacion();
    $fechaActualizacion = (int) $orden->getFechaActualizacion();
    $horaCreacion = $orden->getHoraCreacion();
    $horaActualizacion = $orden->getHoraActualizacion();

    $sql = "INSERT INTO ordenes 
             (potreroId, tipoAlimentoId, alimentoId, cantidad, usuarioId, estadoId, fechaCreacion, fechaActualizacion, horaCreacion, horaActualizacion)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param(
      "iiiiiissss",
      $potreroId,
      $tipoAlimentoId,
      $alimentoId,
      $cantidad,
      $usuarioId,
      $estadoId,
      $fechaCreacion,
      $fechaActualizacion,
      $horaCreacion,
      $horaActualizacion
    );

    $ok = $stmt->execute();

    // Obtener el ID generado
    if ($ok) {
      $lastId = $this->conn->insert_id; // Obtener el último ID insertado
      $stmt->close();
      return $lastId;  // Retornamos el ID generado por la base de datos
    }

    $stmt->close();
    return false;
  }

  // Modificar una orden
  public function modificarOrden(Orden $orden): bool
  {
    $id = $orden->getId();
    $potreroId = $orden->getPotreroId();
    $tipoAlimentoId = $orden->getTipoAlimentoId();
    $alimentoId = $orden->getAlimentoId();
    $cantidad = $orden->getCantidad();
    $usuarioId = $orden->getUsuarioId();
    $estadoId = $orden->getEstadoId();
    $fechaCreacion = (int) $orden->getFechaCreacion();
    $fechaActualizacion = (int) $orden->getFechaActualizacion();
    $horaCreacion = $orden->getHoraCreacion();
    $horaActualizacion = $orden->getHoraActualizacion();

    $sql = "UPDATE ordenes
             SET potreroId = ?, tipoAlimentoId = ?, alimentoId = ?, cantidad = ?, usuarioId = ?, estadoId = ?, fechaCreacion = ?, fechaActualizacion = ?, horaCreacion = ?, horaActualizacion = ?
             WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param(
      "iiiiiissssi",
      $potreroId,
      $tipoAlimentoId,
      $alimentoId,
      $cantidad,
      $usuarioId,
      $estadoId,
      $fechaCreacion,
      $fechaActualizacion,
      $horaCreacion,
      $horaActualizacion,
      $id
    );

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // Mostrar todas las ordenes
  public function getAllOrdenes(): array
  {
    $sql = "SELECT * FROM ordenes ORDER BY fechaCreacion ASC";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $ordenes = [];
    while ($row = $result->fetch_assoc()) {
      $ordenes[] = new Orden(
        $row['id'],
        $row['potreroId'],
        $row['tipoAlimentoId'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['usuarioId'],
        $row['estadoId'],
        $row['fechaCreacion'],
        $row['fechaActualizacion'],
        $row['horaCreacion'],
        $row['horaActualizacion']
      );
    }
    return $ordenes;
  }

  public function getOrdenById($id): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByPotreroId($potreroId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE potrer$potreroId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $potreroId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByTipoAlimentoId($tipoAlimentoId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE tipoAlimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $tipoAlimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByAlimentoId($alimentoId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE alimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $alimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByCantidad($cantidad): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE cant$cantidad = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $cantidad);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByUsuarioId($usuarioId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE usuarioId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function getOrdenByEstadoId($estadoId): ?Orden
  {
    $sql = "SELECT * FROM ordenes WHERE estadoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $estadoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Orden(
        $res['id'],
        $res['potreroId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['usuarioId'],
        $res['estadoId'],
        $res['fechaCreacion'],
        $res['fechaActualizacion'],
        $res['horaCreacion'],
        $res['horaActualizacion']
      )
      : null;
  }

  public function eliminarOrden($id): bool
  {
    $sql = "DELETE FROM ordenes WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }
}