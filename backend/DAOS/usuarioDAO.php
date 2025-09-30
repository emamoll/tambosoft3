<?php

// Incluye los archivos necesarios para la conexión a la base de datos, el modelo y la tabla del usuario.
require_once __DIR__ . '../../servicios/databaseFactory.php';
require_once __DIR__ . '../../modelos/usuario/usuarioTabla.php';
require_once __DIR__ . '../../modelos/usuario/usuarioModelo.php';

// Clase para el acceso a datos (DAO) de la tabla Usuarios
class UsuarioDAO
{
  // Propiedades para la conexión y la creación de tablas
  private $db;
  private $conn;
  private $crearTabla;

  public function __construct()
  {
    $this->db = DatabaseFactory::createDatabaseConnection('mysql');
    $this->crearTabla = new UsuarioCrearTabla($this->db);
    $this->crearTabla->crearTablaRoles();
    $this->crearTabla->crearTablaUsuarios();
    $this->crearTabla->insertarRolesPredeterminados();
    $this->crearTabla->insertarUsuarioAdministrador();
    $this->conn = $this->db->connect();
  }

  //Obtiene todos los usuarios de la base de datos.
  public function getAllUsuarios()
  {
    $sql = "SELECT * FROM usuarios";
    $result = $this->conn->query($sql);

    // Si la consulta falla, detiene la ejecución y muestra el error.
    if (!$result) {
      die("Error en la consulta: " . $this->conn->error);
    }

    $usuarios = [];

    // Recorre los resultados y crea un objeto Usuario por cada fila.
    while ($row = $result->fetch_assoc()) {
      $usuarios[] = new Usuario($row['id'], $row['username'], $row['email'], $row['password'], $row['rolId'], $row['imagen'], $row['token']);
    }

    return $usuarios;
  }

  // Obtiene un usuario por su ID.
  public function getUsuarioById($id)
  {
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();

    // Si no se encuentra ninguna fila, retorna null.
    if ($stmt->num_rows() === 0) {
      return null;
    }

    // Vincula las variables a las columnas del resultado y obtiene la fila.
    $stmt->bind_result($id, $username, $email, $password, $rolId, $imagen, $token);
    $stmt->fetch();

    return new Usuario($id, $username, $email, $password, $rolId, $imagen, $token);
  }

  // Obtiene un usuario por su nombre de usuario.
  public function getUsuarioByUsername($username)
  {
    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Si no se encuentra ninguna fila, retorna null.
    if ($stmt->num_rows() === 0) {
      return null;
    }

    // Vincula las variables y obtiene la fila.
    $stmt->bind_result($id, $username, $email, $password, $rolId, $imagen, $token);
    $stmt->fetch();

    return new Usuario($id, $username, $email, $password, $rolId, $imagen, $token);
  }

  // Obtiene un usuario por su dirección de correo electrónico.
  public function getUsuarioByEmail($email)
  {
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Si no se encuentra ninguna fila, retorna null.
    if ($stmt->num_rows() === 0) {
      return null;
    }

    // Vincula las variables y obtiene la fila.
    $stmt->bind_result($id, $username, $email, $password, $rolId, $imagen, $token);
    $stmt->fetch();

    return new Usuario($id, $username, $email, $password, $rolId, $imagen, $token);
  }

  // Obtiene un usuario por su token de sesión
  public function getUsuarioByToken($token)
  {
    $sql = "SELECT id, username, email, password, rolId, token FROM usuarios WHERE token = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    // Si no se encuentra ninguna fila, retorna null
    if ($stmt->num_rows === 0)
      return null;

    // Vincula las variables y obtiene la fila.
    $stmt->bind_result($id, $username, $email, $password, $rolId, $imagen, $token);
    $stmt->fetch();

    return new Usuario($id, $username, $email, $password, $rolId, $imagen, $token);
  }

  // Inserta los roles predeterminados si no existen.
  public function insertarRoles()
  {
    $roles = ['Administrador', 'Gerente', 'Tractorista', 'Administrador de Campos', 'Administrador de Usuarios', 'Administrador de Alimentos'];

    foreach ($roles as $rol) {
      // 1. Verificamos si el rol ya existe.
      $sqlVerificar = "SELECT id FROM roles WHERE nombre = ?";
      $stmtVerificar = $this->conn->prepare($sqlVerificar);
      $stmtVerificar->bind_param("s", $rol);
      $stmtVerificar->execute();
      $stmtVerificar->store_result();

      if ($stmtVerificar->num_rows === 0) {
        // 2. Si no existe, lo insertamos.
        $stmtVerificar->close();

        $sqlInsertar = "INSERT INTO roles (nombre) VALUES (?)";
        $stmtInsertar = $this->conn->prepare($sqlInsertar);
        $stmtInsertar->bind_param("s", $rol);
        $stmtInsertar->execute();
        $stmtInsertar->close();
      } else {
        $stmtVerificar->close();
      }
    }
  }

  // Verifica si los roles predeterminados están presentes
  public function verificarRoles()
  {
    $sql = "SELECT COUNT(*) as count FROM roles WHERE nombre IN ('Administrador', 'Gerente', 'Tractorista', 'Administrador de Campos', 'Administrador de Usuarios', 'Administrador de Alimentos')";
    $result = $this->conn->query($sql);
    $row = $result->fetch_assoc();
    if ($row['count'] < 6) {
      $this->insertarRoles();
    }
  }

  // Registra un nuevo usuario en la base de datos
  public function registrarUsuario(Usuario $usuario)
  {
    $sql = "INSERT INTO usuarios (username, email, password, rolId, imagen, token) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $this->conn->prepare($sql);

    if (!$stmt) {
      echo ("Error en prepare: " . $this->conn->error);
      return false;
    }

    $username = $usuario->getUsername();
    $email = $usuario->getEmail();
    $password = $usuario->getPassword();
    $rolId = $usuario->getRolId();
    $imagen = $usuario->getImagen();
    $token = $usuario->getToken();

    $stmt->bind_param("sssiss", $username, $email, $password, $rolId, $imagen, $token);

    $resultado = $stmt->execute();
    if (!$resultado) {
      echo ("Error en execute: " . $stmt->error);
    }

    return $resultado;
  }

  // Intenta iniciar sesión con un usuario y contraseña
  public function loginUsuario($username, $password)
  {
    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    // Verifica si el usuario existe y si la contraseña es correcta
    if ($usuario && password_verify($password, $usuario['password'])) {
      // Genera y actualiza un nuevo token.
      $token = bin2hex(random_bytes(32));
      $this->actualizarToken($usuario['id'], $token);
      return ['usuario' => $usuario, 'token' => $token];
    }

    return null;
  }

  // Actualiza el token de sesión de un usuario por su ID
  public function actualizarToken($id, $token)
  {
    $sql = "UPDATE usuarios SET token = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("si", $token, $id);
    $stmt->execute();
  }
}