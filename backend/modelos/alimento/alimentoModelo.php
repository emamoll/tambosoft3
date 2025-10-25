<?php

// Clase de modelo para la entidad Alimento
class Alimento
{
  // Propiedades que corresponden a las columnas de la tabla `alimntos`.
  private $id;
  private $tipoAlimentoId;
  private $nombre;

  public function __construct($id = null, $tipoAlimentoId = null, $nombre = null)
  {
    $this->id = $id;
    $this->tipoAlimentoId = $tipoAlimentoId;
    $this->nombre = $nombre;
  }

  // Métodos "getter" para acceder a las propiedades.

  public function getId()
  {
    return $this->id;
  }

    public function getTipoAlimentoId()
  {
    return $this->tipoAlimentoId;
  }

  public function getNombre()
  {
    return $this->nombre;
  }

  // Método "setter" 

    public function setTipoAlimentoId($tipoAlimentoId)
  {
    $this->$tipoAlimentoId = $tipoAlimentoId;
  }

  public function setNombre($nombre)
  {
    $this->nombre = $nombre;
  }
}