  <?php

class OrdenAuditoria
{
  private $id;
  private $ordenId;
  private $usuarioId;
  private $accion;
  private $motivo;
  private $cantidadAnterior;
  private $cantidadNueva;
  private $fecha;

  public function __construct(
    $id,
    $ordenId,
    $usuarioId,
    $accion,
    $motivo,
    $cantidadAnterior,
    $cantidadNueva,
    $fecha
  ) {
    $this->id = $id;
    $this->ordenId = $ordenId;
    $this->usuarioId = $usuarioId;
    $this->accion = $accion;
    $this->motivo = $motivo;
    $this->cantidadAnterior = $cantidadAnterior;
    $this->cantidadNueva = $cantidadNueva;
    $this->fecha = $fecha;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setId($id): void
  {
    $this->id = $id;
  }

  public function getOrdenId()
  {
    return $this->ordenId;
  }

  public function setOrdenId($ordenId): void
  {
    $this->ordenId = $ordenId;
  }

  public function getUsuarioId()
  {
    return $this->usuarioId;
  }

  public function setUsuarioId($usuarioId): void
  {
    $this->usuarioId = $usuarioId;
  }

  public function getAccion()
  {
    return $this->accion;
  }

  public function setAccion($accion): void
  {
    $this->accion = $accion;
  }

  public function getMotivo()
  {
    return $this->motivo;
  }

  public function setMotivo($motivo): void
  {
    $this->motivo = $motivo;
  }

  public function getCantidadAnterior()
  {
    return $this->cantidadAnterior;
  }

  public function setcantidadAnterior($cantidadAnterior): void
  {
    $this->cantidadAnterior = $cantidadAnterior;
  }
  
  public function getCantidadNueva()
  {
    return $this->cantidadNueva;
  }

  public function setCantidadNueva($cantidadNueva): void
  {
    $this->cantidadNueva = $cantidadNueva;
  }

  public function getFecha()
  {
    return $this->fecha;
  }

  public function setFecha($fecha): void
  {
    $this->fecha = $fecha;
  }

  
}