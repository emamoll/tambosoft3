<?php
// Incluye las interfaces y clases necesarias.
require_once __DIR__ . '../../databaseConnectionInterface.php';
require_once __DIR__ . '../../env.php';

// Clase que implementa la interfaz `DatabaseConnectionInterface` para PostgreSQL.
class PostgresDatabaseConnection implements DatabaseConnectionInterface
{
  // Propiedades privadas para almacenar los datos de conexión.
  private $host;
  private $dbname;
  private $username;
  private $password;

  /**
   * Constructor de la clase.
   * Carga las variables de entorno para la conexión.
   */
  public function __construct()
  {
    $env = new Env();
    $this->host = $env->get('DB_HOST');
    $this->username = $env->get('DB_USERNAME');
    $this->password = $env->get('DB_PASSWORD');
    $this->dbname = $env->get('DB_NAME');
  }

  /**
   * Establece una conexión con la base de datos PostgreSQL.
   *
   * @return resource La conexión a la base de datos si es exitosa.
   * @throws Throwable En caso de un error de conexión.
   */
  public function connect()
  {
    try {
      // Conecta a la base de datos usando la función pg_connect().
      $conn = pg_connect("host=$this->host dbname=$this->dbname user=$this->username password=$this->password");
      if (!$conn) {
        // Muestra un error si la conexión falla.
        die("Conexión fallida: " . pg_last_error());
      }
      return $conn;
    } catch (\Throwable $th) {
      // Captura y maneja cualquier excepción.
      die("Conexión fallida: " . pg_last_error());
    }
  }

  /**
   * Ejecuta una consulta SQL en la base de datos.
   * Se conecta, ejecuta la consulta, cierra la conexión y devuelve el resultado.
   *
   * @param string $query La consulta SQL a ejecutar.
   * @return resource El resultado de la consulta.
   */
  public function query($query)
  {
    // Establece la conexión.
    $conn = $this->connect();
    // Ejecuta la consulta.
    $result = pg_query($conn, $query);
    // Cierra la conexión.
    pg_close($conn);

    return $result;
  }

  /**
   * Método para cerrar la conexión.
   * No se implementa ninguna lógica, ya que la conexión se cierra
   * después de cada consulta en el método `query()`.
   */
  public function close()
  {
    // No es necesario por el momento.
  }
}
