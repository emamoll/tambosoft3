<?php
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/ingreso/ingresoModelo.php';
require_once __DIR__ . '../../modelos/ingreso/ingresoTabla.php';

class IngresoDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new IngresoCrearTabla($this->db);
    $this->crearTabla->crearTablaIngreso();
    $this->conn = $this->db->connect();
  }

  // REGISTRAR INGRESO
  public function registrarIngreso(Ingreso $ingreso): bool
  {
    $almacenId = trim($ingreso->getAlmacenId());
    $tipoAlimentoId = $ingreso->getTipoAlimentoId();
    $alimentoId = $ingreso->getAlimentoId();
    $cantidad = (int) $ingreso->getCantidad();
    $produccionInterna = (int) $ingreso->getProduccionInterna();
    $proveedorId = $ingreso->getProveedorId() ?: null;
    $precio = $ingreso->getPrecio() ?: null;
    $fechaIngreso = $ingreso->getFechaIngreso();

    $sql = "INSERT INTO ingresos 
             (almacenId, tipoAlimentoId, alimentoId, cantidad, produccionInterna, proveedorId, precio, fechaIngreso)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param(
      "iiiiisss",
      $almacenId,
      $tipoAlimentoId,
      $alimentoId,
      $cantidad,
      $produccionInterna,
      $proveedorId,
      $precio,
      $fechaIngreso
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


  // MODIFICAR UNA FILA EXISTENTE
  public function modificarIngreso(Ingreso $ingreso): bool
  {
    $id = $ingreso->getId();
    $almacenId = trim($ingreso->getAlmacenId());
    $tipoAlimentoId = $ingreso->getTipoAlimentoId();
    $alimentoId = $ingreso->getAlimentoId();
    $cantidad = (int) $ingreso->getCantidad();
    $produccionInterna = (int) $ingreso->getProduccionInterna();
    $proveedorId = $ingreso->getProveedorId() ?: null;
    $precio = $ingreso->getPrecio() ?: null;
    $fechaIngreso = $ingreso->getFechaIngreso();

    $sql = "UPDATE ingreso
             SET almacenId = ?, tipoAlimentoId = ?, alimentoId = ?, cantidad = ?, produccionInterna = ?, proveedorId = ?, precio = ?, fechaIngreso = ?
             WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param(
      "iiiiisssi",
      $almacenId,
      $tipoAlimentoId,
      $alimentoId,
      $cantidad,
      $produccionInterna,
      $proveedorId,
      $precio,
      $fechaIngreso,
      $id
    );

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  public function getAllIngresos(): array
  {
    $sql = "SELECT * FROM ingresos ORDER BY produccionInterna ASC";
    $result = $this->conn->query($sql);

    if (!$result) {
      error_log("Error en la consulta: " . $this->conn->error);
      return [];
    }

    $ingresos = [];
    while ($row = $result->fetch_assoc()) {
      $ingresos[] = new Ingreso(
        $row['id'],
        $row['almacenId'],
        $row['tipoAlimentoId'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['produccionInterna'],
        $row['proveedorId'],
        $row['precio'],
        $row['fechaIngreso']
      );
    }
    return $ingresos;
  }

  public function getIngresoById($id): ?Ingreso
  {
    $sql = "SELECT * FROM ingresos WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res
      ? new Ingreso(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function getIngresoByAlmacenId($almacenId): ?Ingreso
  {
    $sql = "SELECT * FROM ingresos WHERE almacenId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $almacenId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Ingreso(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function getIngresoByTipoAlimentoId($tipoAlimentoId): ?Ingreso
  {
    $sql = "SELECT * FROM ingresos WHERE tipoAlimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $tipoAlimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Ingreso(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function getIngresoByAlimentoId($alimentoId): ?Ingreso
  {
    $sql = "SELECT * FROM ingresos WHERE alimentoId = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $alimentoId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Ingreso(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function getIngresoByProduccion($produccionInterna): ?Ingreso
  {
    $sql = "SELECT * FROM ingresos WHERE produccionInterna = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $produccionInterna);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res
      ? new Ingreso(
        $res['id'],
        $res['almacenId'],
        $res['tipoAlimentoId'],
        $res['alimentoId'],
        $res['cantidad'],
        $res['produccionInterna'],
        $res['proveedorId'],
        $res['precio'],
        $res['fechaIngreso']
      )
      : null;
  }

  public function eliminarIngreso($id): bool
  {
    $sql = "DELETE FROM ingresos WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  public function getIngresoByAlmacenIdAndTipoAlimentoAndAlimentoIdAndProduccionInterna($almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna): ?Ingreso
  {
    $sql = "SELECT * FROM ingresos WHERE almacenId = ? AND tipoAlimentoId = ? AND alimentoId = ? AND produccionInterna = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iiii", $almacenId, $tipoAlimentoId, $alimentoId, $produccionInterna);
    $stmt->execute();

    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return $row
      ? new Ingreso(
        $row['id'],
        $row['almacenId'],
        $row['tipoAlimentoId'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['produccionInterna'],
        $row['proveedorId'],
        $row['precio'],
        $row['fechaIngreso']
      )
      : null;
  }


  public function getIngresosFiltradas(array $almacenId, array $tipoAlimentoId, array $alimentoId, array $produccionInterna)
  {
    $sql = "SELECT * FROM ingresos WHERE 1=1";
    $params = [];
    $tipos = '';

    if (!empty($almacenId)) {
      $placeholders = implode(',', array_fill(0, count($almacenId), '?'));
      $sql .= " AND almacenId IN ($placeholders)";
      $params = array_merge($params, $almacenId);
      $tipos .= str_repeat('i', count($almacenId));
    }

    if (!empty($tipoAlimentoId)) {
      $placeholders = implode(',', array_fill(0, count($tipoAlimentoId), '?'));
      $sql .= " AND tipoAlimentoId IN ($placeholders)";
      $params = array_merge($params, $tipoAlimentoId);
      $tipos .= str_repeat('i', count($tipoAlimentoId));
    }

    if (!empty($alimentoId)) {
      $placeholders = implode(',', array_fill(0, count($alimentoId), '?'));
      $sql .= " AND alimentoId IN ($placeholders)";
      $params = array_merge($params, $alimentoId);
      $tipos .= str_repeat('i', count($alimentoId));
    }

    if (!empty($produccionInterna)) {
      $placeholders = implode(',', array_fill(0, count($produccionInterna), '?'));
      $sql .= " AND produccionInterna IN ($placeholders)";
      $params = array_merge($params, $produccionInterna);
      $tipos .= str_repeat('i', count($produccionInterna));
    }

    $sql .= " ORDER BY id";

    $stmt = $this->conn->prepare($sql);

    if (!empty($params)) {
      $bind_names = [];
      $bind_names[] = $tipos;

      foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
      }

      call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    $ingresos = [];
    while ($row = $resultado->fetch_assoc()) {
      $ingresos[] = new Ingreso(
        $row['id'],
        $row['almacenId'],
        $row['tipoAlimentoId'],
        $row['alimentoId'],
        $row['cantidad'],
        $row['produccionInterna'],
        $row['proveedorId'],
        $row['precio'],
        $row['fechaIngreso']
      );
    }

    return $ingresos;
  }
}