<?php

// Clase de modelo para la entidad stock
class Stock
{
  // Propiedades que corresponden a las columnas de la tabla stocks
  private $id;
  private $almacenId;
  private $alimentoId;
  private $cantidad;
  private $produccionInterna;
  private $proveedorId;
  private $fechaIngreso;

  public function __construct($id = null, $almacenId = null, $alimentoId = null, $cantidad = null, $produccionInterna = null, $proveedorId = null, $fechaIngreso = null)
  {
    $this->id = $id;
    $this->almacenId = $almacenId;
    $this->alimentoId = $alimentoId;
    $this->cantidad = $cantidad;
    $this->produccionInterna = $produccionInterna;
    $this->proveedorId = $proveedorId;
    $this->fechaIngreso = $fechaIngreso;
  }
  // Métodos "getter" para acceder a las propiedades.

  public function getId()
  {
    return $this->id;
  }

  public function getAlmacenId()
  {
    return $this->almacenId;
  }

  public function getAlimentoId()
  {
    return $this->alimentoId;
  }

  public function getCantidad()
  {
    return $this->cantidad;
  }

  public function getProduccionInterna()
  {
    return $this->produccionInterna;
  }

  public function getProveedorId()
  {
    return $this->proveedorId;
  }

  public function getFechaIngreso()
  {
    return $this->fechaIngreso;
  }

  // Métodos "setter" para modificar las propiedades.

  public function setAlmacenId($almacenId)
  {
    $this->almacenId = $almacenId;
  }

  public function setAlimentoId($alimentoId)
  {
    $this->alimentoId = $alimentoId;
  }

  public function setCantidad($cantidad)
  {
    $this->cantidad = $cantidad;
  }

  public function setProduccionInterna($produccionInterna)
  {
    $this->produccionInterna = $produccionInterna;
  }

  public function setProveedorId($proveedorId)
  {
    $this->proveedorId = $proveedorId;
  }

  public function setFechaIngreso($fechaIngreso)
  {
    $this->fechaIngreso = $fechaIngreso;
  }
}
