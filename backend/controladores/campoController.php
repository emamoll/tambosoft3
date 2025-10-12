<?php
require_once __DIR__ . '../../DAOS/campoDAO.php';
require_once __DIR__ . '../../modelos/campo/campoModelo.php';

class CampoController
{
  private ?CampoDAO $campoDAO = null;
  private ?string $connError = null;

  public function __construct()
  {
    try {
      $this->campoDAO = new CampoDAO();
    } catch (Exception $e) {
      $this->campoDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  /** POST registrar / modificar / eliminar */
  public function procesarFormularios(): array
  {
    if (!$this->campoDAO) {
      return ['tipo' => 'error', 'mensaje' => 'Error de conexión: ' . ($this->connError ?? 'desconocido')];
    }

    $accion = $_POST['accion'] ?? '';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $superficie = trim($_POST['superficie'] ?? '');

    switch ($accion) {
      case 'registrar':
        if ($nombre === '' || $ubicacion === '' || $superficie === '') {
          return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para registrar'];
        }

        $res = $this->campoDAO->registrarCampo(new Campo(null, $nombre, $ubicacion, $superficie), true);

        switch ($res) {
          case "ok":
            return ['tipo' => 'success', 'mensaje' => 'Campo registrado correctamente'];
          case "duplicado":
            return ['tipo' => 'error', 'mensaje' => 'Ya existe un campo con ese nombre'];
          case "error_insert_campo":
            return ['tipo' => 'error', 'mensaje' => 'Error al insertar el campo'];
          case "error_insert_almacen":
            return ['tipo' => 'error', 'mensaje' => 'El campo se creó pero falló el almacén por defecto'];
          case "error_excepcion":
          default:
            return ['tipo' => 'error', 'mensaje' => 'Error inesperado al registrar el campo'];
        }
      case 'modificar':
        if (!$id) {
          return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
        }
        if ($nombre === '' || $ubicacion === '' || $superficie === '') {
          return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para modificar'];
        }

        $ok = $this->campoDAO->modificarCampo(new Campo($id, $nombre, $ubicacion, $superficie));
        return $ok
          ? ['tipo' => 'success', 'mensaje' => 'Campo modificado correctamente']
          : ['tipo' => 'error', 'mensaje' => 'Ya existe un campo con ese nombre o error al modificar'];

      case 'eliminar':
        if (!$id) {
          return ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
        }
        $ok = $this->campoDAO->eliminarCampoYCascada($id);
        return $ok
          ? ['tipo' => 'success', 'mensaje' => 'Campo eliminado correctamente']
          : ['tipo' => 'error', 'mensaje' => 'No se encontró el campo o no se pudo eliminar'];

      default:
        return ['tipo' => 'error', 'mensaje' => 'Acción no válida'];
    }
  }

  /** GET listado */
  public function obtenerCampos(): array
  {
    if (!$this->campoDAO) {
      return [];
    }
    return $this->campoDAO->getAllCampos();
  }
}

// ====== Endpoints AJAX similares a PasturaController ======
$isAjax = (
  !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (isset($_GET['ajax']) && $_GET['ajax'] === '1');

if (php_sapi_name() !== 'cli') {
  $ctrl = new CampoController();

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'list')) {
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
    $res = $ctrl->procesarFormularios();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res ?? ['tipo' => 'error', 'mensaje' => 'Sin resultado']);
    exit;
  }
}
