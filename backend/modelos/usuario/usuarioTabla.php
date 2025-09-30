<?php

// Incluye los archivos necesarios para la fábrica de bases de datos y la interfaz de conexión.
require_once __DIR__ . '../../../servicios/databaseFactory.php';
require_once __DIR__ . '../../../servicios/databaseConnectionInterface.php';

// Clase encargada de crear las tablas Roles y Usuarios en la base de datos y de insertar el usuario Administrador
class UsuarioCrearTabla
{
  // Propiedad para la instancia de conexión a la base de datos
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

// Crea la tabla Roles si no existe
  public function crearTablaRoles()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "CREATE TABLE IF NOT EXISTS roles (
              id INT PRIMARY KEY AUTO_INCREMENT, 
              nombre VARCHAR(255) NOT NULL UNIQUE)";

    $conn->query($sql);
    $conn->close();
  }

// Inserta los roles predeterminados si no existen
  public function insertarRolesPredeterminados()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();

    // Roles a insertar.
    $roles = ['Administrador', 'Gerente', 'Tractorista', 'Administrador de Campos', 'Administrador de Usuarios', 'Administrador de Alimentos'];

    foreach ($roles as $rol) {
      $stmt = $conn->prepare("INSERT IGNORE INTO roles (nombre) VALUES (?)");
      $stmt->bind_param("s", $rol);
      $stmt->execute();
      $stmt->close();
    }

    $conn->close();
  }

// Inserta un usuario administrador si no existe
  public function insertarUsuarioAdministrador()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();

    $adminUsername = "walter";
    $verificar = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
    $verificar->bind_param("s", $adminUsername);
    $verificar->execute();
    $verificar->store_result();

    if ($verificar->num_rows === 0) {
      $email = "walter@gmail.com";
      $password = password_hash("Plm_2429", PASSWORD_DEFAULT);
      $rolId = 1; // Rol 'Administrador'
      $token = bin2hex(random_bytes(32));
      $imagen = "logo_admin.png";

      $sql = "INSERT INTO usuarios (username, email, password, rolId, imagen, token)
                VALUES (?, ?, ?, ?, ?, ?)";
      $insertar = $conn->prepare($sql);
      $insertar->bind_param("sssiss", $adminUsername, $email, $password, $rolId, $imagen, $token);
      $insertar->execute();
      $insertar->close();
    }

    $verificar->close();
    $conn->close();
  }

// Crea la tabla Usuarios si no existe
  public function crearTablaUsuarios()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $conn = $this->db->connect();
    $sql = "CREATE TABLE IF NOT EXISTS  usuarios (
              id INT PRIMARY KEY AUTO_INCREMENT, 
              username VARCHAR(255) NOT NULL UNIQUE, 
              email VARCHAR(255) NOT NULL UNIQUE, 
              password VARCHAR(255) NOT NULL,
              rolId INT NOT NULL,
              imagen VARCHAR(255) NULL,
              token VARCHAR(64),
              FOREIGN KEY (rolId) REFERENCES roles(id))";

    $conn->query($sql);
    $conn->close();
  }
}