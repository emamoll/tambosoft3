<?php

require_once __DIR__ . '../../DAOS/potreroDAO.php';
require_once __DIR__ . '../../modelos/potrero/potreroModelo.php';

class PotreroController
{
  private $potreroDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->potreroDAO = new PotreroDAO();
    } catch (Exception $e) {
      $this->potreroDAO = null;
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
      $pasturaId = trim($_POST['pasturaId'] ?? '');
      $categoriaId = trim($_POST['categoriaId'] ?? '');
      $cantidadCategoria = trim($_POST['cantidadCategoria'] ?? '');
      $campoId = trim($_POST['campoId'] ?? '');

      switch ($accion) {
        case 'registrar':
          if (empty($nombre) || empty($pasturaId) || empty($campoId)) {
            return ['tipo' => 'error', 'mensaje' => 'Por favor, completá todos los campos para registrar'];
          }
          if ($this->potreroDAO->existeNombre($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe un potrero con ese nombre'];
          }
          $ok = $this->potreroDAO->registrarPotrero(new Potrero(null, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Potrero registrado correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrar el potrero'];

        case 'modificar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          }
          if (empty($nombre) || empty($pasturaId) || empty($campoId)) {
            return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para modificar'];
          }
          if ($this->potreroDAO->existeNombre($nombre, $id)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe un potrero con ese nombre'];
          }
          $ok = $this->potreroDAO->modificarPotrero(new Potrero($id, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Potrero modificado correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar el potrero'];

        case 'eliminar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
          }
          try {
            $ok = $this->potreroDAO->eliminarPotrero($id);
            return $ok
              ? ['tipo' => 'success', 'mensaje' => 'Potrero eliminado correctamente']
              : ['tipo' => 'error', 'mensaje' => 'No se encontró el potrero o no se pudo eliminar'];
          } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1451) {
              return ['tipo' => 'error', 'mensaje' => 'No se puede eliminar el potrero porque está en uso'];
            }
            return ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
          }
      }
    }
    return null;
  }

  public function obtenerPotreros()
  {
    if ($this->connError !== null) {
      return [];
    }
    return $this->potreroDAO->getAllPotreros();
  }

  public function getPotreroById($id)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->potreroDAO->getPotreroById($id);
  }

  public function getPotreroByPastura($pasturaId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->potreroDAO->getPotreroByPastura($pasturaId);
  }

  public function getPotreroByCategoria($categoriaId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->potreroDAO->getPotreroByCategoria($categoriaId);
  }

  public function getPotreroByCantidadCategoria($cantidadCategoria)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->potreroDAO->getPotreroByCantidadCategoria($cantidadCategoria);
  }

  public function getPotreroByCampo($campoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->potreroDAO->getPotreroByCampo($campoId);
  }
}

if (php_sapi_name() !== 'cli') {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  $ctrl = new PotreroController();

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    $potreros = $ctrl->obtenerPotreros();
    $out = [];
    foreach ($potreros as $potrero) {
      $out[] = [
        'id' => $potrero->getId(),
        'nombre' => $potrero->getNombre(),
        'pasturaId' => $potrero->getPasturaId(),
        'categoriaId' => $potrero->getCategoriaId(),
        'cantidadCategoria' => $potrero->getCantidadCategoria(),
        'campoId' => $potrero->getCampoId(),

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