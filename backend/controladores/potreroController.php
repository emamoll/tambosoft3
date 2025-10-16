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
    header('Content-Type: application/json; charset=utf-8');

    if ($this->connError !== null) {
      echo json_encode(['tipo' => 'error', 'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError]);
      exit;
    }

    $accion = $_GET['action'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'list') {
      $potreros = $this->potreroDAO->getAllPotreros();
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
      $nombre = trim($data['nombre'] ?? '');
      $pasturaId = intval($data['pasturaId'] ?? '');
      $categoriaId = isset($data['categoriaId']) && $data['categoriaId'] !== '' ? intval($data['categoriaId']) : null;
      $cantidadCategoria = isset($data['cantidadCategoria']) && $data['cantidadCategoria'] !== '' ? intval($data['cantidadCategoria']) : null;
      $campoId = intval($data['campoId'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {
        case 'registrar':
          if (empty($nombre) || empty($pasturaId) || empty($campoId)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Por favor, completá los campos obligatorios para registrar'];
          } elseif ($this->potreroDAO->existeNombre($nombre)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Ya existe un potrero con ese nombre'];
          } elseif ($categoriaId !== null && ($cantidadCategoria === null || !is_numeric($cantidadCategoria))) {
            $res = ['tipo' => 'error', 'mensaje' => 'Si ingresa una categoría, debe ingresar la cantidad.'];
          } elseif ($cantidadCategoria !== null && ($categoriaId === null || !is_numeric($categoriaId))) {
            $res = ['tipo' => 'error', 'mensaje' => 'Si ingresa una cantidad, debe seleccionar una categoría.'];
          } elseif ($cantidadCategoria !== null && $cantidadCategoria <= 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'La cantidad debe ser mayor a 0.'];
          } else {
            $ok = $this->potreroDAO->registrarPotrero(new Potrero(null, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId));
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Potrero registrado correctamente']
              : ['tipo' => 'error', 'mensaje' => 'Error al registrar el potrero'];
          }
          break;

        case 'modificar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          } elseif (empty($nombre) || empty($pasturaId) || empty($campoId)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios para modificar'];
          } elseif ($this->potreroDAO->existeNombre($nombre, $id)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Ya existe un potrero con ese nombre'];
          } elseif ($categoriaId !== null && ($cantidadCategoria === null || !is_numeric($cantidadCategoria))) {
            $res = ['tipo' => 'error', 'mensaje' => 'Si ingresa una categoría, debe ingresar la cantidad.'];
          } elseif ($cantidadCategoria !== null && ($categoriaId === null || !is_numeric($categoriaId))) {
            $res = ['tipo' => 'error', 'mensaje' => 'Si ingresa una cantidad, debe seleccionar una categoría.'];
          } elseif ($cantidadCategoria !== null && $cantidadCategoria <= 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'La cantidad debe ser mayor a 0.'];
          } else {
            $ok = $this->potreroDAO->modificarPotrero(new Potrero($id, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId));
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Potrero modificado correctamente']
              : ['tipo' => 'error', 'mensaje' => 'Error al modificar el potrero'];
          }
          break;

        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
          } else {
            try {
              $ok = $this->potreroDAO->eliminarPotrero($id);
              $res = $ok
                ? ['tipo' => 'success', 'mensaje' => 'Potrero eliminado correctamente']
                : ['tipo' => 'error', 'mensaje' => 'No se encontró el potrero o no se pudo eliminar'];
            } catch (mysqli_sql_exception $e) {
              if ((int) $e->getCode() === 1451) {
                $res = ['tipo' => 'error', 'mensaje' => 'No se puede eliminar el potrero porque está en uso'];
              } else {
                $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
              }
            }
          }
          break;

        case 'moverCategoria':
          if (!isset($data['idOrigen']) || !isset($data['idDestino'])) {
            $res = ['tipo' => 'error', 'mensaje' => 'Datos inválidos para mover la categoría'];
          } else {
            $idOrigen = intval($data['idOrigen']);
            $idDestino = intval($data['idDestino']);
            $res = $this->potreroDAO->moverCategoria($idOrigen, $idDestino);
          }
          break;
      }
      echo json_encode($res);
      exit;
    }
  }

  // Funciones de consulta completas
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

// 🔹 Lógica principal para procesar las peticiones AJAX
if (php_sapi_name() !== 'cli') {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  if ($isAjax) {
    $ctrl = new PotreroController();
    $ctrl->procesarFormularios();
  }
}

if (isset($_POST['accion'])) {
  $ctrl = new PotreroController();
  $mensaje = $ctrl->procesarFormularios();
}