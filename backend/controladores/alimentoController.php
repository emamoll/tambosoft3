<?php

require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../modelos/alimento/alimentoModelo.php';

class AlimentoController
{
  private $alimentoDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->alimentoDAO = new AlimentoDAO();
    } catch (Exception $e) {
      $this->alimentoDAO = null;
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
          if ($this->alimentoDAO->existeNombre($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe un alimento con ese nombre'];
          }
          $ok = $this->alimentoDAO->registrarAlimento(new Alimento(null, $nombre));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Alimento registrad correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrar el alimento'];

        case 'modificar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          }
          if (empty($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para modificar'];
          }
          if ($this->alimentoDAO->existeNombre($nombre, $id)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe un alimento con ese nombre'];
          }
          $ok = $this->alimentoDAO->modificarAlimento(new Alimento($id, $nombre));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Alimento modificado correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar el alimento'];

        case 'eliminar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
          }
          try {
            $ok = $this->alimentoDAO->eliminarAlimento($id);
            return $ok
              ? ['tipo' => 'success', 'mensaje' => 'Alimento eliminado correctamente']
              : ['tipo' => 'error', 'mensaje' => 'No se encontró el alimento o no se pudo eliminar'];
          } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1451) {
              return ['tipo' => 'error', 'mensaje' => 'No se puede eliminar el alimento porque está en uso'];
            }
            return ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
          }
      }
    }
    return null;
  }

  public function obtenerAlimentos()
  {
    if ($this->connError !== null) {
      return [];
    }
    return $this->alimentoDAO->getAllAlimentos();
  }

  public function getAlimentoById($id)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->alimentoDAO->getAlimentoById($id);
  }
}

$isAjax = (
  !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (isset($_GET['ajax']) && $_GET['ajax'] === '1');

if (php_sapi_name() !== 'cli') {
  $ctrl = new AlimentoController();

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'list')) {
    $alimentos = $ctrl->obtenerAlimentos();
    $out = [];
    foreach ($alimentos as $alimento) {
      $out[] = [
        'id' => $alimento->getId(),
        'nombre' => $alimento->getNombre(),
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