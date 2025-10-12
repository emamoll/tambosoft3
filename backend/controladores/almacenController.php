<?php
require_once __DIR__ . '../../DAOS/almacenDAO.php';
require_once __DIR__ . '../../DAOS/campoDAO.php';
require_once __DIR__ . '../../modelos/almacen/almacenModelo.php';

class AlmacenController
{
  private ?AlmacenDAO $almacenDAO = null;
  private ?CampoDAO $campoDAO = null;
  private ?string $connError = null;

  public function __construct()
  {
    try {
      $this->almacenDAO = new AlmacenDAO();
      $this->campoDAO = new CampoDAO(); // para resolver nombres a IDs
    } catch (Exception $e) {
      $this->almacenDAO = null;
      $this->campoDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  public function procesarFormularios(): array
  {
    if (!$this->almacenDAO) {
      return ['tipo' => 'error', 'mensaje' => 'Error de conexión: ' . ($this->connError ?? 'desconocido')];
    }

    $accion = $_POST['accion'] ?? '';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    // Aceptamos campoId o campoNombre (backwards compatible)
    $campoId = isset($_POST['campoId']) ? (int) $_POST['campoId'] : null;
    $campoNombre = trim($_POST['campoNombre'] ?? '');

    // Resolver campoId si vino el nombre
    if (!$campoId && $campoNombre !== '' && $this->campoDAO) {
      $campo = $this->campoDAO->getCampoByNombre($campoNombre);
      $campoId = $campo ? (int) $campo->getId() : null;
    }

    switch ($accion) {
      case 'registrar':
        if ($nombre === '' || !$campoId) {
          return ['tipo' => 'error', 'mensaje' => 'Completá nombre y campo'];
        }
        if ($this->almacenDAO->existeNombre($nombre)) {
          return ['tipo' => 'error', 'mensaje' => 'Ya existe un almacén con ese nombre'];
        }
        $ok = $this->almacenDAO->registrarAlmacen(new Almacen(null, $nombre, $campoId));
        return $ok
          ? ['tipo' => 'success', 'mensaje' => 'Almacén registrado correctamente']
          : ['tipo' => 'error', 'mensaje' => 'Error al registrar el almacén'];

      case 'modificar':
        if (!$id && $nombre !== '') {
          // compat: buscar por nombre si no mandaron ID
          $exist = $this->almacenDAO->getAlmacenByNombre($nombre);
          $id = $exist ? (int) $exist->getId() : null;
        }
        if (!$id) {
          return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
        }
        if ($nombre === '' || !$campoId) {
          return ['tipo' => 'error', 'mensaje' => 'Completá nombre y campo'];
        }
        if ($this->almacenDAO->existeNombre($nombre, $id)) {
          return ['tipo' => 'error', 'mensaje' => 'Ya existe un almacén con ese nombre'];
        }
        $ok = $this->almacenDAO->modificarAlmacen(new Almacen($id, $nombre, $campoId));
        return $ok
          ? ['tipo' => 'success', 'mensaje' => 'Almacén modificado correctamente']
          : ['tipo' => 'error', 'mensaje' => 'Error al modificar el almacén'];

      case 'eliminar':
        if (!$id && $nombre !== '') {
          // compat: buscar por nombre si no mandaron ID
          $exist = $this->almacenDAO->getAlmacenByNombre($nombre);
          $id = $exist ? (int) $exist->getId() : null;
        }
        if (!$id) {
          return ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
        }
        $ok = $this->almacenDAO->eliminarAlmacen($id);
        return $ok
          ? ['tipo' => 'success', 'mensaje' => 'Almacén eliminado correctamente']
          : ['tipo' => 'error', 'mensaje' => 'No se encontró el almacén o no se pudo eliminar'];

      default:
        return ['tipo' => 'error', 'mensaje' => 'Acción no válida'];
    }
  }

  public function obtenerAlmacenes(): array
  {
    if (!$this->almacenDAO) {
      return [];
    }
    return $this->almacenDAO->getAllAlmacenes();
  }
}

// ====== Endpoints AJAX similares a PasturaController ======
$isAjax = (
  !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (isset($_GET['ajax']) && $_GET['ajax'] === '1');

if (php_sapi_name() !== 'cli') {
  $ctrl = new AlmacenController();

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'list')) {
    $almacenes = $ctrl->obtenerAlmacenes();
    $out = [];
    foreach ($almacenes as $a) {
      $out[] = [
        'id' => $a->getId(),
        'nombre' => $a->getNombre(),
        'campoId' => $a->getCampoId(),
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
