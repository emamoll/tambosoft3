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

    // GENERACIÓN DE TOKEN
    $tokenRegistro = bin2hex(random_bytes(32));

    // 4. Si no existen, procede con el registro.
    $this->usuarioDAO->verificarRoles();
    // Se usa $imagen (el nuevo archivo o 'user.png') y el token generado.
    $usuario = new Usuario(null, $username, $email, $hash, $rolId, $imagen, $tokenRegistro);
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
        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['rolId']) || empty($_POST['password2'])) {
          $res = ['tipo' => 'error', 'mensaje' => 'Todos los campos marcados con * son obligatorios.'];
        } else {
          $username = trim($_POST['username']);
          $email = trim($_POST['email']);
          $password = trim($_POST['password']);
          $password2 = trim($_POST['password2']);
          $rolId = intval($_POST['rolId']);
          $token = bin2hex(random_bytes(32));

          // VALIDACIÓN DE SERVIDOR: Comprobar que las contraseñas coincidan
          if ($password !== $password2) {
            $res = ['tipo' => 'error', 'mensaje' => 'Las contraseñas no coinciden.'];
          } else {

            // --- INICIO LÓGICA DE SUBIDA DE IMAGEN ---
            $imagenNombre = 'user.png'; // Valor por defecto

            // RUTA CORREGIDA: Apuntando directamente a 'frontend/img/'
            $uploadFileDir = '../../../frontend/img/';

            $expectedPath = 'frontend/img/';

            // 1. Verificar si el directorio existe y crearlo si es necesario
            if (!is_dir($uploadFileDir)) {
              // Intenta crear el directorio con permisos 0777 (permisivo)
              if (!mkdir($uploadFileDir, 0777, true)) {
                $res = ['tipo' => 'error', 'mensaje' => "Error interno: No se pudo crear el directorio de subida de imágenes. Ruta esperada: {$expectedPath}"];
                goto finish_ajax;
              }
            }

            // 2. Verificar si el directorio es escribible
            if (!is_writable($uploadFileDir)) {
              $res = ['tipo' => 'error', 'mensaje' => "Error de permiso: El servidor no puede escribir en el directorio. Verifique permisos (chmod 775/777) en la carpeta {$expectedPath}."];
              goto finish_ajax;
            }

            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
              $fileTmpPath = $_FILES['imagen']['tmp_name'];
              $fileName = $_FILES['imagen']['name'];
              $fileNameCmps = explode(".", $fileName);
              $fileExtension = strtolower(end($fileNameCmps));

              // Generar un nombre único para evitar colisiones (hash + timestamp)
              $newFileName = hash('sha256', uniqid(mt_rand(), true)) . '.' . $fileExtension;

              $destPath = $uploadFileDir . $newFileName;

              // Validar la extensión (solo imágenes)
              $allowedfileExtensions = array('jpg', 'jpeg', 'gif', 'png', 'webp');
              if (in_array($fileExtension, $allowedfileExtensions)) {
                // Mover el archivo subido
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                  $imagenNombre = $newFileName;
                } else {
                  // Error al mover el archivo (generalmente por permisos o ruta).
                  $res = ['tipo' => 'error', 'mensaje' => "Error al mover la imagen. Verifique los permisos o si la ruta '{$expectedPath}' es correcta."];
                  goto finish_ajax;
                }
              } else {
                // Extensión no permitida.
                $res = ['tipo' => 'error', 'mensaje' => 'Formato de imagen no permitido. Solo se permiten JPG, JPEG, GIF, PNG, WEBP.'];
                goto finish_ajax;
              }
            }
            // --- FIN LÓGICA DE SUBIDA DE IMAGEN ---


            // Se pasa el $imagenNombre (el nuevo archivo o 'user.png')
            $resultadoRegistro = $this->registrarUsuario($username, $email, $password, $rolId, $imagenNombre, $token);

            $res = $resultadoRegistro; // Ya tiene el formato ['tipo' => '...', 'mensaje' => '...']
          }
        }

        finish_ajax:
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