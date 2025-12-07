<?php

// Incluye el archivo de la capa de acceso a datos para los usuarios.
require_once __DIR__ . '../../DAOS/usuarioDAO.php';
require_once __DIR__ . '../../modelos/usuario/usuarioModelo.php';

// Clase controladora para gestionar las operaciones relacionadas con los usuarios
class UsuarioController
{
  // Propiedad para la instancia de UsuarioDAO.
  private $usuarioDAO;

  public function __construct()
  {
    $this->usuarioDAO = new UsuarioDAO();
  }

  public function registrarUsuario($username, $email, $password, $rolId, $imagen, $token)
  {
    // Cifra la contraseña utilizando password_hash
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 1. Verifica si el nombre de usuario o el email ya existen.
    $existeUsername = $this->usuarioDAO->getUsuarioByUsername($username);
    $existeEmail = $this->usuarioDAO->getUsuarioByEmail($email);

    // 2. Si el usuario ya existe, devuelve un mensaje de error.
    if ($existeUsername) {
      return ['tipo' => 'error', 'mensaje' => 'El nombre de usuario ya está en uso.'];
    }

    // 3. Si el email ya existe, devuelve un mensaje de error.
    if ($existeEmail) {
      return ['tipo' => 'error', 'mensaje' => 'El email ya está registrado.'];
    }

    // 4. Si no existen, procede con el registro.
    $this->usuarioDAO->verificarRoles();
    // NOTA: La imagen y el token son placeholders, generalmente 'user.png' y null o vacío en el registro.
    // Usamos el rolId del formulario.
    $usuario = new Usuario(null, $username, $email, $hash, $rolId, 'user.png', '');
    $resultado = $this->usuarioDAO->registrarUsuario($usuario);

    // 5. Devuelve el resultado del registro.
    if ($resultado === true) {
      return ['tipo' => 'success', 'mensaje' => 'Usuario registrado correctamente.'];
    } else {
      // Si el DAO devuelve un string, asumimos que es un error de DB
      $error = is_string($resultado) ? 'Error de DB: ' . $resultado : 'Error desconocido al registrar el usuario.';
      return ['tipo' => 'error', 'mensaje' => $error];
    }
  }

  // Obtiene todos los roles (MÉTODO AGREGADO para solucionar el error)
  public function obtenerRoles()
  {
    return $this->usuarioDAO->getAllRoles();
  }


  // Inicia sesión de un usuario.
  public function loginUsuario($username, $password)
  {
    // Obtiene el usuario por nombre.
    $usuario = $this->usuarioDAO->getUsuarioByUsername($username);

    // Verifica si el usuario existe y si la contraseña es correcta.
    if ($usuario && password_verify($password, $usuario->getPassword())) {
      // Genera un token de sesión.
      $token = bin2hex(random_bytes(32));
      $usuario->setToken($token);

      // Guarda el token en la base de datos y en la sesión.
      $this->usuarioDAO->actualizarToken($usuario->getId(), $token);
      $_SESSION['token'] = $token;

      return $usuario;
    }
    return null;
  }

  // Obtener todos los usuarios

  public function obtenerUsuarios()
  {
    return $this->usuarioDAO->getAllUsuarios();
  }

  // Obtiene un usuario por su nombre de usuario.
  public function getUsuarioByUsername($username)
  {
    return $this->usuarioDAO->getUsuarioByUsername($username);
  }

  // Obtiene un usuario por su dirección de correo electrónico.
  public function getUsuarioByEmail($email)
  {
    return $this->usuarioDAO->getUsuarioByEmail($email);
  }

  // Obtiene un usuario por su token de sesión.
  public function getUsuarioByToken($token)
  {
    return $this->usuarioDAO->getUsuarioByToken($token);
  }

  // Manejador de peticiones AJAX (Registro)
  public function procesarFormularios()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Asumimos que los datos vienen de FormData (POST)
      $accion = $_POST['accion'] ?? null;

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida.'];

      if ($accion === 'registrar') {
        // Validación básica (debería ser robusta en JS también)
        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['rolId'])) {
          $res = ['tipo' => 'error', 'mensaje' => 'Todos los campos marcados con * son obligatorios.'];
        } else {
          $username = trim($_POST['username']);
          $email = trim($_POST['email']);
          $password = trim($_POST['password']);
          $rolId = intval($_POST['rolId']);

          // Por defecto la imagen es 'user.png' y el token vacío.
          $resultadoRegistro = $this->registrarUsuario($username, $email, $password, $rolId, 'user.png', '');

          $res = $resultadoRegistro; // Ya tiene el formato ['tipo' => '...', 'mensaje' => '...']
        }
      }

      // Respuesta JSON para AJAX
      if (ob_get_level()) {
        ob_clean();
      }
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($res);
      exit;
    }
  }
}

// Punto de entrada para peticiones AJAX de registro
if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verificamos si es una petición AJAX
  $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

  if ($isAjax) {
    // Ob_start solo si es una petición que se procesará en el controlador
    ob_start();
    $ctrl = new UsuarioController();
    $ctrl->procesarFormularios();
    exit;
  }
}