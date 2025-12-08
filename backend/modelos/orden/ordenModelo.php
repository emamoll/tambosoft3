<?php

class Orden
{
  private $id;
  private $potreroId;
  private $almacenId;
  private $tipoAlimentoId;
  private $alimentoId;
  private $cantidad;
  private $usuarioId;
  private $estadoId;
  private $categoriaId; // NUEVO: Campo para estadísticas
  private $fechaCreacion;
  private $fechaActualizacion;
  private $horaCreacion;
  private $horaActualizacion;

  public function __construct(
    $id,
    $potreroId,
    $almacenId,
    $tipoAlimentoId,
    $alimentoId,
    $cantidad,
    $usuarioId,
    $estadoId,
    $categoriaId, // NUEVO
    $fechaCreacion,
    $fechaActualizacion,
    $horaCreacion,
    $horaActualizacion
  ) {
    $this->id = $id;
    $this->potreroId = $potreroId;
    $this->almacenId = $almacenId;
    $this->tipoAlimentoId = $tipoAlimentoId;
    $this->alimentoId = $alimentoId;
    $this->cantidad = $cantidad;
    $this->usuarioId = $usuarioId;
    $this->estadoId = $estadoId;
    $this->categoriaId = $categoriaId; // NUEVO
    $this->fechaCreacion = $fechaCreacion;
    $this->fechaActualizacion = $fechaActualizacion;
    $this->horaCreacion = $horaCreacion;
    $this->horaActualizacion = $horaActualizacion;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setId($id): void
  {
    $this->id = $id;
  }

  public function getPotreroId()
  {
    return $this->potreroId;
  }

  public function setPotreroId($potreroId): void
  {
    $this->potreroId = $potreroId;
  }

  public function getAlmacenId()
  {
    return $this->almacenId;
  }

  public function setAlmacenId($almacenId): void
  {
    $this->almacenId = $almacenId;
  }

  public function getTipoAlimentoId()
  {
    return $this->tipoAlimentoId;
  }

  public function setTipoAlimentoId($tipoAlimentoId): void
  {
    $this->tipoAlimentoId = $tipoAlimentoId;
  }

  public function getAlimentoId()
  {
    return $this->alimentoId;
  }

  public function setAlimentoId($alimentoId): void
  {
    $this->alimentoId = $alimentoId;
  }

  public function getCantidad()
  {
    return $this->cantidad;
  }

  public function setCantidad($cantidad): void
  {
    $this->cantidad = $cantidad;
  }

  public function getUsuarioId()
  {
    return $this->usuarioId;
  }

  public function setUsuarioId($usuarioId): void
  {
    $this->usuarioId = $usuarioId;
  }

  public function getEstadoId()
  {
    return $this->estadoId;
  }

  public function setEstadoId($estadoId): void
  {
    $this->estadoId = $estadoId;
  }

  public function getCategoriaId() // NUEVO GETTER
  {
    return $this->categoriaId;
  }

  public function setCategoriaId($categoriaId): void // NUEVO SETTER
  {
    $this->categoriaId = $categoriaId;
  }

  public function getFechaCreacion()
  {
    return $this->fechaCreacion;
  }

  public function setFechaCreacion($fechaCreacion): void
  {
    $this->fechaCreacion = $fechaCreacion;
  }

  public function getFechaActualizacion()
  {
    return $this->fechaActualizacion;
  }

  public function setFechaActualizacion($fechaActualizacion): void
  {
    $this->fechaActualizacion = $fechaActualizacion;
  }

  public function getHoraCreacion()
  {
    return $this->horaCreacion;
  }

  public function setHoraCreacion($horaCreacion): void
  {
    $this->horaCreacion = $horaCreacion;
  }

  public function getHoraActualizacion()
  {
    return $this->horaActualizacion;
  }

  public function setHoraActualizacion($horaActualizacion): void
  {
    $this->horaActualizacion = $horaActualizacion;
  }
}