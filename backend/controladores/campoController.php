<?php

// Incluye los archivos necesarios para las operaciones con la base de datos y los modelos
require_once __DIR__ . '../../DAOS/campoDAO.php';
require_once __DIR__ . '../../modelos/campo/campoModelo.php';

// Clase controladora para gestionar las operaciones relacionadas con los campos.
class CampoController
{
  // Propiedad para la instancia de CampoDAO
  private $campoDAO;

  public function __construct()
  {
    $this->campoDAO = new CampoDAO();
  }

  // Procesa los formularios de registro, modificación y eliminación de campos.
  public function procesarFormularios()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $accion = $_POST['accion'] ?? '';
      $nombre = $_POST['nombre'] ?? '';
      $ubicacion = $_POST['ubicacion'] ?? '';
      $superficie = $_POST['superficie'] ?? '';

      switch ($accion) {
        case 'registrar':
          if (empty($nombre) || empty($ubicacion) || empty($superficie)) {
            return ['tipo' => 'error', 'mensaje' => 'Por favor, completá todos los campos para registrar'];
          }

          $campo = new Campo(null, $nombre, $ubicacion, $superficie);
          if ($this->campoDAO->registrarCampo($campo)) {
            return ['tipo' => 'success', 'mensaje' => 'Campo registrado correctamente'];
          } else {
            return ['tipo' => 'error', 'mensaje' => 'Error: ya existe un campo con ese nombre'];
          }

        case 'modificar':
          $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          }

          // Validar campos
          if (empty($nombre) || empty($ubicacion) || empty($superficie)) {
            return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para modificar'];
          }

          $campoModificado = new Campo($id, $nombre, $ubicacion, $superficie);
          if ($this->campoDAO->modificarCampo($campoModificado)) {
            return ['tipo' => 'success', 'mensaje' => 'Campo modificado correctamente'];
          } else {
            return ['tipo' => 'error', 'mensaje' => 'No se pudo modificar el campo'];
          }

        case 'eliminar':
          $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
          }

          try {
            if ($this->campoDAO->eliminarCampoYCascada($id)) {
              return ['tipo' => 'success', 'mensaje' => 'Campo eliminado correctamente'];
            } else {
              return ['tipo' => 'error', 'mensaje' => 'No se encontró el campo o no se pudo eliminar'];
            }
          } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1451) { // violación de clave foránea
              return ['tipo' => 'error', 'mensaje' => 'No se puede eliminar el campo'];
            }
            return ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
          }
      }
    }
    return null;
  }

  // Obtiene todos los campos de la base de datos.
  public function obtenerCampos()
  {
    return $this->campoDAO->getAllCampos();
  }

  // Obtiene un campo por su ID.
  public function getCampoById($id)
  {
    return $this->campoDAO->getCampoById($id);
  }
}

if (php_sapi_name() !== 'cli') {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    // Devolver lista de campos
    $ctrl = new CampoController();
    $campos = $ctrl->obtenerCampos();
    $out = [];
    foreach ($campos as $c) {
      $out[] = [
        'id' => $c->getId(),
        'nombre' => $c->getNombre(),
        'ubicacion' => $c->getUbicacion(),
        'superficie' => $c->getSuperficie(),
      ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    exit;
  }

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar registrar / modificar / eliminar
    $ctrl = new CampoController();
    $res = $ctrl->procesarFormularios(); 
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res ?? ['tipo' => 'error', 'mensaje' => 'Sin resultado']);
    exit;
  }
}