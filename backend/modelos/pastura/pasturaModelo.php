<?php

// Clase de modelo para la entidad Pastura
class Pastura
{
  // Propiedades que corresponden a las columnas de la tabla `pasturas`.
  private $id;
  private $nombre;
  private $fechaSiembra;

  public function __construct($id = null, $nombre = null, $fechaSiembra = null)
  {
    $this->id = $id;
    $this->nombre = $nombre;
    $this->fechaSiembra = $fechaSiembra;
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

  public function getFechaSiembra()
  {
    return $this->fechaSiembra;
  }

  // Método "setter" para modificar la propiedad `nombre`.

  public function setNombre($nombre)
  {
    $this->nombre = $nombre;
  }

  public function setFechaSiembre($fechaSiembra)
  {
    $this->fechaSiembra = $fechaSiembra;
  }
}