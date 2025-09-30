<?php

// Clase de modelo para la entidad Almacen
class Almacen
{
  // Propiedades que corresponden a las columnas de la tabla Almacenes
  private $id;
  private $nombre;
  private $campoId;

  public function __construct($id = null, $nombre = null, $campoId = null)
  {
    $this->id = $id;
    $this->nombre = $nombre;
    $this->campoId = $campoId;
  }

// Obtiene el ID del almacén.
  public function getId()
  {
    return $this->id;
  }

// Obtiene el nombre del almacén.
  public function getNombre()
  {
    return $this->nombre;
  }

  public function getCampoId()
  {
    return $this->campoId;
  }

// Establece el nombre del almacén.
  public function setNombre($nombre)
  {
    $this->nombre = $nombre;
  }

// Establece el ID del campo.
  public function setCampoId($campoId)
  {
    $this->campoId = $campoId;
  }
}