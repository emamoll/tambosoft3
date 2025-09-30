<?php
// Incluye la clase de conexión a la base de datos MySQL.
require_once __DIR__ . '../../servicios/databases/mysqlDatabaseConnection.php';
// require_once __DIR__ . '../../servicios/databases/postgresDatabaseConnection.php';

// Clase que funciona como una fábrica para crear objetos de conexión a la base de datos.
// Este patrón de diseño permite crear diferentes tipos de objetos
// (por ejemplo, para MySQL o PostgreSQL) sin tener que instanciarlos directamente.
class DatabaseFactory
{
  /**
   * Crea y devuelve una instancia de una conexión a la base de datos
   * según el tipo especificado.
   *
   * @param string $type El tipo de base de datos ('mysql', 'postgres', etc.).
   * @return DatabaseConnectionInterface Una instancia de la conexión a la base de datos.
   * @throws Exception Si el tipo de base de datos no es soportado.
   */
  public static function createDatabaseConnection($type)
  {
    switch ($type) {
      case 'mysql':
        return new MySQLDatabaseConnection();
      // case 'postgres':
      // return new PostgresDatabaseConnection();
      default:
        // Lanza una excepción si el tipo de base de datos no es reconocido.
        throw new Exception('Tipo de base de datos no soportado');
    }
  }
}
