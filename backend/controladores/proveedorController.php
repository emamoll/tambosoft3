<?php

require_once __DIR__ . '../../DAOS/proveedorDAO.php';
require_once __DIR__ . '../../modelos/proveedor/proveedorModelo.php';

class ProveedorController
{
  private $proveedorDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->proveedorDAO = new ProveedorDAO();
    } catch (Exception $e) {
      $this->proveedorDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  public function procesarFormularios()
  {
    header('Content-Type: application/json; charset=utf-8');

    if ($this->connError !== null) {
      echo json_encode(['tipo' => 'error', 'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError]);
      exit;
    }

    $accion = $_GET['action'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'list') {
      $proveedores = $this->proveedorDAO->getAllProveedores();
      $out = [];
      foreach ($proveedores as $proveedor) {
        $out[] = [
          'id' => $proveedor->getId(),
          'denominacion' => $proveedor->getDenominacion(),
          'emailP' => $proveedor->getEmailP(),
          'telefono' => $proveedor->getTelefono()
        ];
      }
      echo json_encode($out);
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Unificar la lectura de datos POST (JSON o formulario clásico)
      $data = $_POST;
      if (empty($data)) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?? [];
      }

      $accion = $data['accion'] ?? null;
      $id = intval($data['id'] ?? null);
      $denominacion = trim($data['denominacion'] ?? '');
      $emailP = trim($data['emailP'] ?? '');
      $telefono = intval($data['telefono'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {
        case 'registrar':
          if (empty($denominacion) || empty($emailP) || empty($telefono)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Por favor, completá los campos obligatorios para registrar'];
          } elseif ($this->proveedorDAO->existeDenominacion($denominacion)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Ya existe un proveedor con esa denominación'];
          } else {
            $ok = $this->proveedorDAO->registrarProveedor(proveedor: new Proveedor(null, $denominacion, $emailP, $telefono));
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Proveedor registrado correctamente']
              : ['tipo' => 'error', 'mensaje' => 'Error al registrar el proveedor'];
          }
          break;

        case 'modificar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          } elseif (empty($denominacion) || empty($emailP) || empty($telefono)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios para modificar'];
          } elseif ($this->proveedorDAO->existeDenominacion($denominacion, $id)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Ya existe un proveedor con ese nombre'];
          } else {
            $ok = $this->proveedorDAO->modificarProveedor(new Proveedor($id, $denominacion, $emailP, $telefono));
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Proveedor modificado correctamente']
              : ['tipo' => 'error', 'mensaje' => 'Error al modificar el proveedor'];
          }
          break;

        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
          } else {
            try {
              $ok = $this->proveedorDAO->eliminarProveedor($id);
              $res = $ok
                ? ['tipo' => 'success', 'mensaje' => 'Proveedor eliminado correctamente']
                : ['tipo' => 'error', 'mensaje' => 'No se encontró el proveedor o no se pudo eliminar'];
            } catch (mysqli_sql_exception $e) {
              if ((int) $e->getCode() === 1451) {
                $res = ['tipo' => 'error', 'mensaje' => 'No se puede eliminar el proveedor porque está en uso'];
              } else {
                $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
              }
            }
          }
          break;
      }
      echo json_encode($res);
      exit;
    }
  }

  // Funciones de consulta completas
  public function obtenerProveedores()
  {
    if ($this->connError !== null) {
      return [];
    }
    return $this->proveedorDAO->getAllProveedores();
  }

  public function getProveedorById($id)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->proveedorDAO->getProveedorById($id);
  }
}

// 🔹 Lógica principal para procesar las peticiones AJAX
if (php_sapi_name() !== 'cli') {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  if ($isAjax) {
    $ctrl = new ProveedorController();
    $ctrl->procesarFormularios();
  }
}

if (isset($_POST['accion'])) {
  $ctrl = new ProveedorController();
  $mensaje = $ctrl->procesarFormularios();
}