<?php

/**
 * Clase de modelo para la entidad 'Potrero'.
 * Representa la estructura de los datos de un potrero.
 */
class Potrero
{
  // Propiedades privadas que corresponden a las columnas de la tabla `potreros`.
  private $id;
  private $nombre;
  private $superficie;
  private $pastura_id;
  private $categoria_id;
  private $campo_id;

  /**
   * Constructor de la clase.
   *
   * @param int|null $id El ID del potrero.
   * @param string|null $nombre El nombre del potrero.
   * @param string|null $superficie La superficie del potrero.
   * @param int|null $pastura_id El ID de la pastura asociada.
   * @param int|null $categoria_id El ID de la categoría asociada.
   * @param int|null $campo_id El ID del campo al que pertenece el potrero.
   */
  public function __construct($id = null, $nombre = null, $superficie = null, $pastura_id = null, $categoria_id = null, $campo_id = null)
  {
    $this->id = $id;
    $this->nombre = $nombre;
    $this->superficie = $superficie;
    $this->pastura_id = $pastura_id;
    $this->categoria_id = $categoria_id;
    $this->campo_id = $campo_id;
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

  public function getSuperficie()
  {
    return $this->superficie;
  }

  public function getPastura_id()
  {
    return $this->pastura_id;
  }

  public function getCategoria_id()
  {
    return $this->categoria_id;
  }

  public function getCampo_id()
  {
    return $this->campo_id;
  }

  // Métodos "setter" para modificar las propiedades.

  public function setNombre($nombre)
  {
    $this->nombre = $nombre;
  }

  public function setSuperficie($superficie)
  {
    $this->superficie = $superficie;
  }

  public function setPastura_id($pastura_id)
  {
    $this->pastura_id = $pastura_id;
  }

  public function setCategoria_id($categoria_id)
  {
    $this->categoria_id = $categoria_id;
  }

  public function setCampo_id($campo_id)
  {
    $this->campo_id = $campo_id;
  }
}