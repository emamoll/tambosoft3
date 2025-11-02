<?php

require_once __DIR__ . '../../DAOS/pasturaDAO.php';
require_once __DIR__ . '../../modelos/pastura/pasturaModelo.php';

class PasturaController
{
  private $pasturaDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->pasturaDAO = new PasturaDAO();
    } catch (Exception $e) {
      $this->pasturaDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  public function procesarFormularios()
  {
    if ($this->connError !== null) {
      return ['tipo' => 'error', 'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $accion = $_POST['accion'] ?? '';
      $id = isset($_POST['id']) ? intval($_POST['id']) : null;
      $nombre = trim($_POST['nombre'] ?? '');

      switch ($accion) {
        case 'registrar':
          if (empty($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Por favor, completá todos los campos para registrar'];
          }
          if ($this->pasturaDAO->existeNombre($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe una pastura con ese nombre'];
          }
          $ok = $this->pasturaDAO->registrarPastura(new Pastura(null, $nombre));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Pastura registrada correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrar la pastura'];

        case 'modificar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          }
          if (empty($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para modificar'];
          }
          if ($this->pasturaDAO->existeNombre($nombre, $id)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe una pastura con ese nombre'];
          }
          $ok = $this->pasturaDAO->modificarPastura(new Pastura($id, $nombre));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Pastura modificada correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar la pastura'];

        case 'eliminar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
          }
          try {
            $ok = $this->pasturaDAO->eliminarPastura($id);
            return $ok
              ? ['tipo' => 'success', 'mensaje' => 'Pastura eliminada correctamente']
              : ['tipo' => 'error', 'mensaje' => 'No se encontró la pastura o no se pudo eliminar'];
          } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1451) {
              return ['tipo' => 'error', 'mensaje' => 'No se puede eliminar la pastura porque está en uso'];
            }
            return ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
          }
      }
    }
    return null;
  }

  public function obtenerPasturas()
  {
    if ($this->connError !== null) {
      return [];
    }
    return $this->pasturaDAO->getAllPasturas();
  }

  public function getPasturaById($id)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->pasturaDAO->getPasturaById($id);
  }
}

$isAjax = (
  !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (isset($_GET['ajax']) && $_GET['ajax'] === '1');

if (php_sapi_name() !== 'cli') {
  $ctrl = new PasturaController();

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'list')) {
    $pasturas = $ctrl->obtenerPasturas();
    $out = [];
    foreach ($pasturas as $pastura) {
      $out[] = [
        'id' => $pastura->getId(),
        'nombre' => $pastura->getNombre(),
      ];
    }
    // Limpiar cualquier salida previa (espacios/BOM/notices)
    while (ob_get_level()) {
      ob_end_clean();
    }
    if (!headers_sent()) {
      header('Content-Type: application/json; charset=utf-8');
      header('Cache-Control: no-store');
    }
    echo json_encode($out);
    exit;
  }

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar cualquier salida previa (espacios/BOM/notices)
    while (ob_get_level()) {
      ob_end_clean();
    }
    if (!headers_sent()) {
      header('Content-Type: application/json; charset=utf-8');
      header('Cache-Control: no-store');
    }
    try {
      $res = $ctrl->procesarFormularios();
      echo json_encode($res ?? ['tipo' => 'error', 'mensaje' => 'Sin resultado']);
    } catch (Throwable $e) {
      // Nunca devolvemos HTML
      echo json_encode([
        'tipo' => 'error',
        'mensaje' => 'Excepción en servidor',
        'detalle' => $e->getMessage(),
      ]);
    }
    exit;
  }
}