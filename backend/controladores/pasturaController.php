<?php

// Incluye los archivos necesarios para las operaciones con la base de datos y los modelos.
require_once __DIR__ . '../../DAOS/pasturaDAO.php';
require_once __DIR__ . '../../modelos/pastura/pasturaModelo.php';

// Clase controladora para gestionar las operaciones relacionadas con las pasturas
class PasturaController
{
  // Propiedad para la instancia de PasturaDAO.
  private $pasturaDAO;

  public function __construct()
  {
    $this->pasturaDAO = new PasturaDAO();
  }

  // Procesa los formularios de registro, modificación y eliminación de pasturas.
  public function procesarFormularios()
  {
    // Verifica si la petición es de tipo POST.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Obtiene la acción y el nombre de la pastura.
      $accion = $_POST['accion'] ?? '';
      $nombre = trim($_POST['nombre'] ?? '');
      $fechaSiembra = trim($_POST['fechaSiembra'] ?? '');

      // Evalúa la acción.
      switch ($accion) {
        case 'registrar':
          if (empty($nombre) || empty($fechaSiembra)) {
            return ['tipo' => 'error', 'mensaje' => 'Por favor, completá todos los campos para registrar'];
          }
          $res = $this->pasturaDAO->registrarPastura(new Pastura(null, $nombre, $fechaSiembra));
          if ($res['ok']) {
            return ['tipo' => 'success', 'mensaje' => 'Pastura registrada correctamente'];
          }
          if (!empty($res['dup'])) {
            return ['tipo' => 'error', 'mensaje' => 'Error: ya existe una pastura con ese nombre'];
          }
          return ['tipo' => 'error', 'mensaje' => 'No se pudo registrar la pastura'];
        case 'modificar':
          $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          }

          // Validar campos
          if (empty($nombre) || empty($fechaSiembra)) {
            return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para modificar'];
          }

          $pasturaModificada = new Pastura($id, $nombre, $fechaSiembra);
          $res = $this->pasturaDAO->modificarPastura($pasturaModificada);
          if ($res['ok']) {
            return ['tipo' => 'success', 'mensaje' => 'Pastura modificada correctamente'];
          }
          if (!empty($res['dup'])) {
            return ['tipo' => 'error', 'mensaje' => 'Error: ya existe una pastura con ese nombre'];
          }
          return ['tipo' => 'error', 'mensaje' => 'No se pudo modificar la pastura'];
        case 'eliminar':
          $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
          }

          try {
            if ($this->pasturaDAO->eliminarPastura($id)) {
              return ['tipo' => 'success', 'mensaje' => 'Pastura eliminada correctamente'];
            } else {
              return ['tipo' => 'error', 'mensaje' => 'No se encontró la pastura o no se pudo eliminar'];
            }
          } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1451) { // violación de clave foránea
              return ['tipo' => 'error', 'mensaje' => 'No se puede eliminar la pastura'];
            }
            return ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
          }
      }
    }
    return null;
  }

  // Obtiene todas las pasturas de la base de datos.
  public function obtenerPasturas()
  {
    return $this->pasturaDAO->getAllPasturas();
  }

  // Obtiene una pastura por su ID.
  public function getPasturaById($id)
  {
    return $this->pasturaDAO->getPasturaById($id);
  }
}

if (php_sapi_name() !== 'cli') {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    // Devolver lista de pasturas
    $ctrl = new PasturaController();
    $pasturas = $ctrl->obtenerPasturas();
    $out = [];
    foreach ($pasturas as $pastura) {
      $out[] = [
        'id' => $pastura->getId(),
        'nombre' => $pastura->getNombre(),
        'fechaSiembra' => $pastura->getFechaSiembra(),
      ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    exit;
  }

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar registrar / modificar / eliminar
    $ctrl = new PasturaController();
    $res = $ctrl->procesarFormularios();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res ?? ['tipo' => 'error', 'mensaje' => 'Sin resultado']);
    exit;
  }
}