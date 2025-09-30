<?php

// Clase de modelo para la entidad Campo
class Campo
{
  // Propiedades que corresponden a las columnas de la tabla campos
  private $id;
  private $nombre;
  private $ubicacion;
  private $superficie;

// Constructor de la clase
  public function __construct($id = null, $nombre = null, $ubicacion = null, $superficie = null)
  {
    $this->id = $id;
    $this->nombre = $nombre;
    $this->ubicacion = $ubicacion;
    $this->superficie = $superficie;
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

  public function getUbicacion()
  {
    return $this->ubicacion;
  }

    public function getSuperficie()
  {
    return $this->superficie;
  }

  // Métodos "setter" para modificar las propiedades.

  public function setNombre($nombre)
  {
    $this->nombre = $nombre;
  }

  public function setUbicacion($ubicacion)
  {
    $this->ubicacion = $ubicacion;
  }

    public function setSeperficie($superficie)
  {
    $this->$superficie = $superficie;
  }
}