<?php
// Incluye las interfaces y clases necesarias.
require_once __DIR__ . '../../databaseConnectionInterface.php';
require_once __DIR__ . '../../env.php';

// Clase que implementa la interfaz `DatabaseConnectionInterface` para MySQL.
// Se encarga de la conexión, consulta y cierre de la base de datos MySQL.
class MySQLDatabaseConnection implements DatabaseConnectionInterface
{
  // Propiedades privadas para almacenar los datos de conexión.
  private $host;
  private $dbname;
  private $username;
  private $password;

  /**
   * Constructor de la clase.
   * Carga las variables de entorno (host, nombre de la base de datos, usuario y contraseña)
   * utilizando la clase `Env`.
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
   * Establece una conexión con la base de datos MySQL.
   *
   * @return mysqli La conexión a la base de datos si es exitosa.
   * @throws Throwable En caso de un error de conexión.
   */
  public function connect()
  {
    try {
      // Crea una nueva conexión usando la extensión mysqli.
      $conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);
      // Verifica si hay un error de conexión.
      if ($conn->connect_error) {
        die("Error en la conexion: " . $conn->connect_error);
      } else {
        return $conn;
      }
    } catch (\Throwable $th) {
      // Captura cualquier excepción y muestra un mensaje de error.
      echo "Error en la conexion" . $th->getMessage();
      exit;
    }
  }

  /**
   * Ejecuta una consulta SQL en la base de datos.
   * Se conecta, ejecuta la consulta, cierra la conexión y devuelve el resultado.
   *
   * @param string $query La consulta SQL a ejecutar.
   * @return mixed El resultado de la consulta.
   */
  public function query($query)
  {
    // Establece la conexión.
    $conn = $this->connect();
    // Ejecuta la consulta.
    $result = $conn->query($query);
    // Cierra la conexión.
    $conn->close();

    return $result;
  }

  /**
   * Método para cerrar la conexión.
   * Por el momento, no contiene ninguna lógica ya que la conexión se cierra
   * después de cada consulta en el método `query()`.
   */
  public function close()
  {
    // No es necesario por el momento.
  }
}
