<?php
// Clase para cargar las variables de entorno desde un archivo .env.
class Env
{
  // Almacena las variables de entorno.
  private $env;

  /**
   * Constructor de la clase.
   * Carga el archivo .env y lo analiza para llenar el array $env.
   */
  public function __construct()
  {
    $this->env = parse_ini_file(__DIR__ . '../../../.env');
  }

  /**
   * Obtiene el valor de una variable de entorno por su clave.
   *
   * @param string $key La clave de la variable de entorno.
   * @return string El valor de la variable de entorno.
   */
  public function get($key)
  {
    return $this->env[$key];
  }
}
