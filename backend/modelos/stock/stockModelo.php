<?php

// Clase de modelo para la entidad Proveedores
class Proveedor
{
  // Propiedades que corresponden a las columnas de la tabla Proveedores
  private $id;
  private $alimentoId;
  private $cantidad;
  private $produccionInterna;

  private $proveedorId;

  private $numeroLote;

  private $fechaIngreso;

  public function __construct($id = null, $alimentoId = null, $cantidad = null, $produccionInterna = null, $proveedorId = null, $numeroLote = null, $fechaIngreso = null)
  {
    $this->id = $id;
    $this->alimentoId = $alimentoId;
    $this->cantidad = $cantidad;
    $this->produccionInterna = $produccionInterna;
    $this->proveedorId = $proveedorId;
    $this->numeroLote = $numeroLote;
    $this->fechaIngreso = $fechaIngreso;
  }
  // Métodos "getter" para acceder a las propiedades.

  public function getId()
  {
    return $this->id;
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

  public function getNumeroLote()
  {
    return $this->numeroLote;
  }

  public function getFechaIngreso()
  {
    return $this->fechaIngreso;
  }

  // Métodos "setter" para modificar las propiedades.

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

  public function setnumeroLote($numeroLote)
  {
    $this->numeroLote = $numeroLote;
  }

  public function setFechaIngreso($fechaIngreso)
  {
    $this->fechaIngreso = $fechaIngreso;
  }
}
