<?php
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/ordenAuditoria/ordenAuditoriaModelo.php';
require_once __DIR__ . '../../modelos/ordenAuditoria/ordenAuditoriaTabla.php';

class OrdenAuditoriaDAO
{
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new OrdenAuditoriaCrearTabla($this->db);
    $this->crearTabla->crearTablaOrdenAuditoria();
    $this->conn = $this->db->connect();
  }

  public function getConn()
  {
    return $this->conn;
  }

  public function registrarAuditoria(OrdenAuditoria $auditoria): bool
  {
    $ordenId = (int) $auditoria->getOrdenId();
    $usuarioId = (int) $auditoria->getUsuarioId();
    $accion = trim($auditoria->getAccion());
    $motivo = trim($auditoria->getMotivo());
    $cantidadAnterior = $auditoria->getCantidadAnterior();
    $cantidadNueva = $auditoria->getCantidadNueva();

    $sql = "
    INSERT INTO ordenAuditoria
    (ordenId, usuarioId, accion, motivo, cantidadAnterior, cantidadNueva)
    VALUES (?, ?, ?, ?, ?, ?)
  ";

    $stmt = $this->conn->prepare($sql);

    $stmt->bind_param(
      "iissii",
      $ordenId,
      $usuarioId,
      $accion,
      $motivo,
      $cantidadAnterior,
      $cantidadNueva
    );

    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
  }

  public function listarAuditoriaPorOrden(int $ordenId): array
  {
    $sql = "
    SELECT 
      oa.id,
      oa.accion,
      oa.motivo,
      oa.cantidadAnterior,
      oa.cantidadNueva,
      oa.fecha,
      u.username AS usuarioNombre
    FROM ordenAuditoria oa
    INNER JOIN usuarios u ON u.id = oa.usuarioId
    WHERE oa.ordenId = ?
    ORDER BY oa.fecha DESC
  ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $ordenId);
    $stmt->execute();

    $result = $stmt->get_result();
    $auditorias = [];

    while ($row = $result->fetch_assoc()) {
      $auditorias[] = $row;
    }

    $stmt->close();

    return $auditorias;
  }

}