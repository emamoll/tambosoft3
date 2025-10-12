<?php

// Clase de modelo para la entidad Potrero
class Potrero
{
  // Propiedades privadas que corresponden a las columnas de la tabla Potreros
  private $id;
  private $nombre;
  private $pasturaId;
  private $categoriaId;
  private $cantidadCategoria;
  private $campoId;

  public function __construct($id = null, $nombre = null, $pasturaId = null, $categoriaId = null, $cantidadCategoria = null, $campoId = null)
  {
    $this->id = $id;
    $this->nombre = $nombre;
    $this->pasturaId = $pasturaId;
    $this->categoriaId = $categoriaId;
    $this->cantidadCategoria = $cantidadCategoria;
    $this->campoId = $campoId;
  }

  // Métodos "getter" para acceder a las propiedades.

  public function getId()
  {
    return $this->id;
  }

  public function getNombre()
  {
    return $this->nombre;
  }

  public function getPasturaId()
  {
    return $this->pasturaId;
  }

  public function getCategoriaId()
  {
    return $this->categoriaId;
  }

  public function getCantidadCategoria()
  {
    return $this->cantidadCategoria;
  }

  public function getCampoId()
  {
    return $this->campoId;
  }

  // Métodos "setter" para modificar las propiedades.

  public function setNombre($nombre)
  {
    $this->nombre = $nombre;
  }


  public function setPasturaId($pasturaId)
  {
    $this->pasturaId = $pasturaId;
  }

  public function setCategoriaId($categoriaId)
  {
    $this->categoriaId = $categoriaId;
  }

  public function setCantidadCategoria($cantidadCategoria)
  {
    $this->cantidadCategoria = $cantidadCategoria;
  }

  public function setCampoId($campoId)
  {
    $this->campoId = $campoId;
  }
}