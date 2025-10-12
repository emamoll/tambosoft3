<?php

// Clase de modelo para la entidad Categoria
class Categoria
{
  // Propiedades que corresponden a las columnas de la tabla Categorias
  private $id;
  private $nombre;

  public function __construct($id = null, $nombre = null)
  {
    $this->id = $id;
    $this->nombre = $nombre;
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

  // Método "setter" para modificar la propiedad `nombre`.

  public function setNombre($nombre)
  {
    $this->nombre = $nombre;
  }
}
