<?php

// Clase de modelo para la entidad orden
class Orden
{
  // Propiedades que corresponden a las columnas de la tabla ordenes
  private $id;
  private $potreroId;
  private $tipoAlimentoId;
  private $alimentoId;
  private $cantidad;
  private $usuarioId;
  private $estadoId;
  private $fechaCreacion;
  private $fechaActualizacion;
  private $horaCreacion;
  private $horaActualizacion;


  public function __construct($id = null, $potreroId = null, $tipoAlimentoId = null, $alimentoId = null, $cantidad = null, $usuarioId = null, $estadoId = null, $fechaCreacion = null, $fechaActualizacion = null, $horaCreacion = null, $horaActualizacion = null)
  {
    $this->id = $id;
    $this->potreroId = $potreroId;
    $this->tipoAlimentoId = $tipoAlimentoId;
    $this->alimentoId = $alimentoId;
    $this->cantidad = $cantidad;
    $this->usuarioId = $usuarioId;
    $this->estadoId = $estadoId;
    $this->fechaCreacion = $fechaCreacion;
    $this->fechaActualizacion = $fechaActualizacion;
    $this->horaCreacion = $horaCreacion;
    $this->horaActualizacion = $horaActualizacion;
  }
  // Métodos "getter" para acceder a las propiedades.

  public function getId()
  {
    return $this->id;
  }

  public function getPotreroId()
  {
    return $this->potreroId;
  }

  public function getTipoAlimentoId()
  {
    return $this->tipoAlimentoId;
  }

  public function getAlimentoId()
  {
    return $this->alimentoId;
  }

  public function getCantidad()
  {
    return $this->cantidad;
  }

  public function getUsuarioId()
  {
    return $this->usuarioId;
  }

  public function getEstadoId()
  {
    return $this->estadoId;
  }

  public function getFechaCreacion()
  {
    return $this->fechaCreacion;
  }

  public function getFechaActualizacion()
  {
    return $this->fechaActualizacion;
  }

  public function getHoraCreacion()
  {
    return $this->horaCreacion;
  }

  public function getHoraActualizacion()
  {
    return $this->horaActualizacion;
  }

  // Métodos "setter" para modificar las propiedades.

  public function setPotreroId($potreroId)
  {
    $this->potreroId = $potreroId;
  }

  public function setTipoAlimentoId($tipoAlimentoId)
  {
    $this->tipoAlimentoId = $tipoAlimentoId;
  }

  public function setAlimentoId($alimentoId)
  {
    $this->alimentoId = $alimentoId;
  }

  public function setCantidad($cantidad)
  {
    $this->cantidad = $cantidad;
  }

  public function setUsuarioId($usuarioId)
  {
    $this->usuarioId = $usuarioId;
  }

  public function setEstadoId($estadoId)
  {
    $this->estadoId = $estadoId;
  }

  public function setFechaCreacion($fechaCreacion)
  {
    $this->fechaCreacion = $fechaCreacion;
  }

  public function setFechaActualizacion($fechaActualizacion)
  {
    $this->fechaActualizacion = $fechaActualizacion;
  }

  public function setHoraCreacion($horaActualizacion)
  {
    $this->horaActualizacion = $horaActualizacion;
  }

  public function setHoraActualizacion($horaActualizacion)
  {
    $this->horaActualizacion = $horaActualizacion;
  }
}
