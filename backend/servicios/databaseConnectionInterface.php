<?php
// Define una interfaz para la conexión a la base de datos.
// Esto garantiza que cualquier clase que implemente esta interfaz tenga
// los métodos 'connect', 'query' y 'close'.
interface DatabaseConnectionInterface
{
  /**
   * Establece una conexión con la base de datos.
   * @return object La conexión establecida.
   */
  public function connect();

  /**
   * Ejecuta una consulta SQL.
   * @param string $query La consulta SQL a ejecutar.
   * @return mixed El resultado de la consulta.
   */
  public function query($query);

  /**
   * Cierra la conexión a la base de datos.
   */
  public function close();
}
