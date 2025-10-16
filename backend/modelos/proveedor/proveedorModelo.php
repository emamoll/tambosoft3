<?php

// Clase de modelo para la entidad Proveedores
class Proveedor
{
  // Propiedades que corresponden a las columnas de la tabla Proveedores
  private $id;
  private $denominacion;
  private $email;
  private $telefono;

  public function __construct($id = null, $denominacion = null, $email = null, $telefono = null)
  {
    $this->id = $id;
    $this->denominacion = $denominacion;
    $this->email = $email;
    $this->telefono = $telefono;
  }
  // Métodos "getter" para acceder a las propiedades.

  public function getId()
  {
    return $this->id;
  }

  public function getDenominacion()
  {
    return $this->denominacion;
  }

  public function getEmail()
  {
    return $this->email;
  }

  public function getTelefono()
  {
    return $this->telefono;
  }

  // Métodos "setter" para modificar las propiedades.

  public function setDenominacion($denominacion)
  {
    $this->denominacion = $denominacion;
  }

  public function setEmail($email)
  {
    $this->email = $email;
  }

  public function setTelefono($telefono)
  {
    $this->telefono = $telefono;
  }
}
