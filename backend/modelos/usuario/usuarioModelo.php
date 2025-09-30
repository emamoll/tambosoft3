<?php

// Clase de modelo para la entidad Usuario
class Usuario
{
  // Propiedades que corresponden a las columnas de la tabla Usuarios
  private $id;
  private $username;
  private $email;
  private $password;
  private $rolId;
  private $imagen;
  private $token;

  public function __construct($id = null, $username = null, $email = null, $password = null, $rolId = null, $imagen = null, $token = null)
  {
    $this->id = $id;
    $this->username = $username;
    $this->email = $email;
    $this->password = $password;
    $this->rolId = $rolId;
    $this->imagen = $imagen;
    $this->token = $token;
  }

  // Métodos "getter" para acceder a las propiedades.

  public function getId()
  {
    return $this->id;
  }

  public function getUsername()
  {
    return $this->username;
  }

  public function getEmail()
  {
    return $this->email;
  }

  public function getPassword()
  {
    return $this->password;
  }

  public function getRolId()
  {
    return $this->rolId;
  }

  public function getImagen()
  {
    return $this->imagen;
  }

  public function getToken()
  {
    return $this->token;
  }

  // Métodos "setter" para modificar las propiedades.

  public function setUsername($username)
  {
    $this->username = $username;
  }

  public function setEmail($email)
  {
    $this->email = $email;
  }

  public function setPassword($password)
  {
    $this->password = $password;
  }

  public function setRolId($rolId)
  {
    $this->rolId = $rolId; 
  }

  public function setImagen($imagen)
  {
    $this->imagen = $imagen;
  }

  public function setToken($token)
  {
    $this->token = $token;
  }
}
