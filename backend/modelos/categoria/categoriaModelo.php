<?php

// Clase de modelo para la entidad Categoria
class Categoria
{
  // Propiedades que corresponden a las columnas de la tabla Categorias
  private $id;
  private $nombre;
  private $cantidad;

  public function __construct($id = null, $nombre = null, $cantidad = null)
  {
    $this->id = $id;
    $this->nombre = $nombre;
    $this->cantidad = $cantidad;
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

    public function getCantidad()
  {
    return $this->cantidad;
  }

  // Método "setter" para modificar las propiedades.

  public function setNombre($nombre)
  {
    $this->nombre = $nombre;
  }

    public function setCantdidad($cantidad)
  {
    $this->cantidad = $cantidad;
  }
}
