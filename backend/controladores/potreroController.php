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
      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'tipo' => 'error',
        'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError
      ]);
      exit;
    }

    $accion = $_GET['action'] ?? null;

    // LISTAR POTREROS (con filtros)

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'list') {

      // Función auxiliar: convierte parámetros GET en arrays de enteros
      $getArrayOfInts = function ($key) {
        if (!isset($_GET[$key]))
          return null;
        $val = $_GET[$key];
        // Asegura que siempre sea un array para procesar los [] de JS
        if (!is_array($val))
          $val = [$val];
        // Convierte a int y elimina valores vacíos (como el string vacío de un checkbox no marcado)
        $ints = array_map('intval', array_filter($val, fn($v) => $v !== ''));
        return count($ints) > 0 ? $ints : null;
      };

      // Filtros admitidos (ahora esperan arrays de IDs o null)
      $filtros = [
        'campoId' => $getArrayOfInts('campoId'),
        'pasturaId' => $getArrayOfInts('pasturaId'),
        // 'categoriaId' se envía solo si el filtro 'soloConCategoria' NO está activo
        'categoriaId' => $getArrayOfInts('categoriaId'),
        // El valor 1 lo envía el JS si se selecciona "Sólo con categoría"
        'conCategoria' => isset($_GET['conCategoria']) && $_GET['conCategoria'] === '1'
      ];

      $potreros = $this->potreroDAO->listar($filtros);
      $out = [];

      foreach ($potreros as $p) {
        $out[] = [
          'id' => $p['id'],
          'nombre' => $p['nombre'],
          'pasturaId' => $p['pasturaId'],
          'categoriaId' => $p['categoriaId'],
          'categoriaCantidad' => $p['categoriaCantidad'],
          'campoId' => $p['campoId'],
          // Es útil devolver estos nombres para la tabla sin tener que hacer otra consulta o mapeo en JS
          'pasturaNombre' => $p['pasturaNombre'] ?? '',
          'categoriaNombre' => $p['categoriaNombre'] ?? '',
          'campoNombre' => $p['campoNombre'] ?? '',
        ];
      }

      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($out);
      exit;
    }

    // ABM POTRERO Y MOVER CATEGORÍA

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        // ------------------------------
        // REGISTRAR POTRERO
        // ------------------------------
        case 'registrar':
          if (empty($nombre) || empty($pasturaId) || empty($campoId)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios.'];
          } elseif ($this->potreroDAO->existeNombre($nombre)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Ya existe un potrero con ese nombre.'];
          } else {
            $ok = $this->potreroDAO->registrarPotrero(
              new Potrero(null, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId)
            );
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Potrero registrado correctamente.']
              : ['tipo' => 'error', 'mensaje' => 'Error al registrar el potrero.'];
          }
          break;

        // ------------------------------
        // MODIFICAR POTRERO
        // ------------------------------
        case 'modificar':

          $id = intval($data['id'] ?? 0);
          $nombre = trim($data['nombre'] ?? '');
          $pasturaId = intval($data['pasturaId'] ?? 0);
          $campoId = intval($data['campoId'] ?? 0);

          // ✅ Asegurar que si no hay categoría seleccionada, sea NULL
          $categoriaId = isset($data['categoriaId']) && $data['categoriaId'] !== ''
            ? intval($data['categoriaId'])
            : null;

          // ✅ La cantidad de la categoría debe ser NULL, ya que no se edita aquí
          $cantidadCategoria = null;
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido.'];
          } elseif (empty($nombre) || empty($pasturaId) || empty($campoId)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Completá los campos obligatorios.'];
          } elseif ($this->potreroDAO->existeNombre($nombre, $id)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Ya existe un potrero con ese nombre.'];
          } else {
            $ok = $this->potreroDAO->modificarPotrero(
              new Potrero($id, $nombre, $pasturaId, $categoriaId, $cantidadCategoria, $campoId)
            );
            $res = $ok
              ? ['tipo' => 'success', 'mensaje' => 'Potrero modificado correctamente.']
              : ['tipo' => 'error', 'mensaje' => 'Error al modificar el potrero.'];
          }
          break;

        // ------------------------------
        // ELIMINAR POTRERO
        // ------------------------------
        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar.'];
          } else {
            try {
              $ok = $this->potreroDAO->eliminarPotrero($id);
              $res = $ok
                ? ['tipo' => 'success', 'mensaje' => 'Potrero eliminado correctamente.']
                : ['tipo' => 'error', 'mensaje' => 'No se encontró el potrero o no se pudo eliminar.'];
            } catch (mysqli_sql_exception $e) {
              if ((int) $e->getCode() === 1451) {
                $res = ['tipo' => 'error', 'mensaje' => 'No se puede eliminar porque está en uso.'];
              } else {
                $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
              }
            }
          }
          break;

        // ------------------------------
        // MOVER CATEGORÍA ENTRE POTREROS
        // ------------------------------
        case 'moverCategoria':
          if (!isset($data['idOrigen']) || !isset($data['idDestino'])) {
            $res = ['tipo' => 'error', 'mensaje' => 'Datos inválidos para mover categoría.'];
          } else {
            $idOrigen = intval($data['idOrigen']);
            $idDestino = intval($data['idDestino']);
            $res = $this->potreroDAO->moverCategoria($idOrigen, $idDestino);
          }
          break;
      }

      if (ob_get_level())
        ob_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($res);
      exit;
    }
  }

  // ==========================================
  // MÉTODOS DE APOYO
  // ==========================================
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

// ==========================================
// PUNTO DE ENTRADA PRINCIPAL
// ==========================================
if (php_sapi_name() !== 'cli') {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

  if ($isAjax) {
    ob_start();
    $ctrl = new PotreroController();
    $ctrl->procesarFormularios();
  }

  if (isset($_POST['accion'])) {
    $ctrl = new PotreroController();
    $ctrl->procesarFormularios();
  }
}