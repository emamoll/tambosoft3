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
      echo json_encode([
        'tipo' => 'error',
        'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError
      ]);
      exit;
    }

    $accion = $_GET['action'] ?? null;

    // ======== LISTAR (GET con filtros opcionales) ========
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'list') {
      $filtros = [
        'campoId' => isset($_GET['campoId']) && $_GET['campoId'] !== '' ? (int) $_GET['campoId'] : null,
        'pasturaId' => isset($_GET['pasturaId']) && $_GET['pasturaId'] !== '' ? (int) $_GET['pasturaId'] : null,
        'categoriaId' => isset($_GET['categoriaId']) && $_GET['categoriaId'] !== '' ? (int) $_GET['categoriaId'] : null,
        'conCategoria' => isset($_GET['conCategoria']) ? (bool) $_GET['conCategoria'] : false
      ];

      $potreros = $this->potreroDAO->listar($filtros);
      $out = [];
      // Se utiliza el array asociativo devuelto por DAO
      foreach ($potreros as $potrero) {
        $out[] = [
          'id' => $potrero['id'],
          'nombre' => $potrero['nombre'],
          'pasturaId' => $potrero['pasturaId'],
          'categoriaId' => $potrero['categoriaId'],
          'cantidadCategoria' => $potrero['cantidadCategoria'],
          'campoId' => $potrero['campoId'],
        ];
      }
      echo json_encode($out);
      exit;
    }

    // ======== ACCIONES POST ========
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Unificar lectura JSON o POST clásico
      $data = $_POST;
      if (empty($data)) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?? [];
      }

      $accion = $data['accion'] ?? null;
      $id = intval($data['id'] ?? 0);
      $nombre = trim($data['nombre'] ?? '');
      $pasturaId = intval($data['pasturaId'] ?? 0);
      $categoriaId = isset($data['categoriaId']) && $data['categoriaId'] !== '' ? intval($data['categoriaId']) : null;
      $cantidadCategoria = isset($data['cantidadCategoria']) && $data['cantidadCategoria'] !== '' ? intval($data['cantidadCategoria']) : null;
      $campoId = intval($data['campoId'] ?? 0);

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {
        // ======== REGISTRAR ========
        case 'registrar':
          if (empty($nombre) || empty($pasturaId) || empty($campoId)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Por favor, completá los campos obligatorios para registrar'];
          } elseif ($this->potreroDAO->existeNombre($nombre)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Ya existe un potrero con ese nombre'];
          } elseif ($categoriaId !== null && ($cantidadCategoria === null || !is_numeric($cantidadCategoria))) {
            $res = ['tipo' => 'error', 'mensaje' => 'Si ingresás una categoría, debés ingresar la cantidad.'];
          } elseif ($cantidadCategoria !== null && ($categoriaId === null || !is_numeric($categoriaId))) {
            $res = ['tipo' => 'error', 'mensaje' => 'Si ingresás una cantidad, debés seleccionar una categoría.'];
          } elseif ($cantidadCategoria !== null && $cantidadCategoria <= 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'La cantidad debe ser mayor a 0.'];
          } else {
            $ok = $this->potreroDAO->registrarPotrero(
              new Potrero(null, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId)
            );
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Potrero registrado correctamente']
              : ['tipo' => 'error', 'mensaje' => 'Error al registrar el potrero'];
          }
          break;

        // ======== MODIFICAR ========
        case 'modificar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          } elseif (empty($nombre) || empty($pasturaId) || empty($campoId)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios para modificar'];
          } elseif ($this->potreroDAO->existeNombre($nombre, $id)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Ya existe un potrero con ese nombre'];
          } elseif ($categoriaId !== null && ($cantidadCategoria === null || !is_numeric($cantidadCategoria))) {
            $res = ['tipo' => 'error', 'mensaje' => 'Si ingresás una categoría, debés ingresar la cantidad.'];
          } elseif ($cantidadCategoria !== null && ($categoriaId === null || !is_numeric($categoriaId))) {
            $res = ['tipo' => 'error', 'mensaje' => 'Si ingresás una cantidad, debés seleccionar una categoría.'];
          } elseif ($cantidadCategoria !== null && $cantidadCategoria <= 0) {
            $res = ['tipo' => 'error', 'mensaje' => 'La cantidad debe ser mayor a 0.'];
          } else {
            $ok = $this->potreroDAO->modificarPotrero(
              new Potrero($id, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId)
            );
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Potrero modificado correctamente']
              : ['tipo' => 'error', 'mensaje' => 'Error al modificar el potrero'];
          }
          break;

        // ======== ELIMINAR ========
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

        // ======== MOVER CATEGORÍA (TOTAL) ========
        case 'moverCategoria':
          // Se espera solo idOrigen y idDestino.
          if (!isset($data['idOrigen']) || !isset($data['idDestino'])) {
            $res = ['tipo' => 'error', 'mensaje' => 'Datos inválidos para mover la categoría'];
          } else {
            $idOrigen = intval($data['idOrigen']);
            $idDestino = intval($data['idDestino']);

            // Llama al DAO sin cantidad.
            $res = $this->potreroDAO->moverCategoria($idOrigen, $idDestino);
          }
          break;
      }

      echo json_encode($res);
      exit;
    }
  }

  // ===== Métodos de apoyo =====
  public function obtenerPotreros()
  {
    if ($this->connError !== null)
      return [];
    return $this->potreroDAO->getAllPotreros();
  }

  public function getPotreroById($id)
  {
    if ($this->connError !== null)
      return null;
    return $this->potreroDAO->getPotreroById($id);
  }

  public function getPotreroByPastura($pasturaId)
  {
    if ($this->connError !== null)
      return null;
    return $this->potreroDAO->getPotreroByPastura($pasturaId);
  }

  public function getPotreroByCategoria($categoriaId)
  {
    if ($this->connError !== null)
      return null;
    return $this->potreroDAO->getPotreroByCategoria($categoriaId);
  }

  public function getPotreroByCampo($campoId)
  {
    if ($this->connError !== null)
      return null;
    return $this->potreroDAO->getPotreroByCampo($campoId);
  }
}

// ===== Punto de entrada principal (AJAX o POST clásico) =====
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