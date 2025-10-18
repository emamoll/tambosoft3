<?php

// Clase de modelo para la entidad Proveedores
class Proveedor
{
  // Propiedades que corresponden a las columnas de la tabla Proveedores
  private $id;
  private $denominacion;
  private $emailP;
  private $telefono;

  public function __construct($id = null, $denominacion = null, $emailP = null, $telefono = null)
  {
    $this->id = $id;
    $this->denominacion = $denominacion;
    $this->emailP = $emailP;
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

  public function getEmailP()
  {
    return $this->emailP;
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

  public function setEmailP($emailP)
  {
    $this->emailP = $emailP;
  }

  public function setTelefono($telefono)
  {
    $this->telefono = $telefono;
  }
}
