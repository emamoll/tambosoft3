<?php

require_once __DIR__ . '../../DAOS/alimentoDAO.php';
require_once __DIR__ . '../../modelos/alimento/alimentoModelo.php';

// Detectar AJAX una sola vez
$isAjax = (
  !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (isset($_GET['ajax']) && $_GET['ajax'] === '1');

// 🚨 CORRECCIÓN CRÍTICA: Limpiar el buffer y preparar encabezados para AJAX desde el inicio
if (php_sapi_name() !== 'cli' && $isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1. Limpiar cualquier salida previa (incluyendo BOM, espacios en blanco o notices de includes)
  while (ob_get_level()) {
    ob_end_clean();
  }
  // 2. Establecer el tipo de contenido antes de cualquier posible output
  if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
  }
}


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

    $accion = $_GET['action'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'listar') {
      $getArray = function ($key) {
        if (!isset($_GET[$key]))
          return null;
        $val = $_GET[$key];
        if (!is_array($val))
          $val = [$val];
        $vals = array_filter($val, fn($v) => trim($v) !== '');
        return count($vals) > 0 ? $vals : null;
      };

      $filtros = [
        'tipoAlimentoId' => $getArray('tipoAlimentoId'),
        'nombre' => $getArray('nombre'),
      ];

      $data = $this->alimentoDAO->listar($filtros);

      // Limpieza antes de enviar (Mantenemos esta limpieza para el GET 'listar' también)
      while (ob_get_level())
        ob_end_clean();
      header('Content-Type: application/json; charset=utf-8');
      header('Cache-Control: no-store');
      echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $accion = $_POST['accion'] ?? '';
      $id = isset($_POST['id']) ? intval($_POST['id']) : null;
      $tipoAlimentoId = trim($_POST['tipoAlimentoId'] ?? '');
      $nombre = trim($_POST['nombre'] ?? '');

      switch ($accion) {
        case 'registrar':
          if (empty($nombre) || empty($tipoAlimentoId)) {
            return ['tipo' => 'error', 'mensaje' => 'Por favor, completá todos los campos para registrar'];
          }
          if ($this->alimentoDAO->existeNombreYTipo($tipoAlimentoId, $nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe un alimento con ese nombre'];
          }
          $ok = $this->alimentoDAO->registrarAlimento(new Alimento(null, $tipoAlimentoId, $nombre));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Alimento registrado correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrar el alimento'];

        case 'modificar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          }
          if (empty($nombre) || empty($tipoAlimentoId)) {
            return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para modificar'];
          }
          if ($this->alimentoDAO->existeNombreYTipo($tipoAlimentoId, $nombre, $id)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe un alimento con ese nombre'];
          }
          $ok = $this->alimentoDAO->modificarAlimento(new Alimento($id, $tipoAlimentoId, $nombre));
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
              : ['tipo' => 'error', 'mensaje' => 'La eliminación falló: el alimento no existe o el ID es incorrecto.'];
          } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1451) {
              return ['tipo' => 'error', 'mensaje' => 'No se puede eliminar el alimento porque está en uso en otra tabla (Stock, Ordenes, Ingresos, etc.).'];
            }
            // Captura otros errores SQL de mysqli
            error_log("Error de SQL (mysqli) al intentar eliminar alimento ID {$id}: " . $e->getMessage());
            return ['tipo' => 'error', 'mensaje' => 'Error grave de DB: ' . $e->getMessage()];
          } catch (Exception $e) { // 🚨 Captura la excepción lanzada por el DAO si prepare/execute falla
            // Este mensaje devolverá el error detallado de ejecución/preparación SQL
            error_log("Excepción al eliminar alimento ID {$id}: " . $e->getMessage());
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

  public function getAlimentoByTipoAlimentoId($tipoAlimentoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->alimentoDAO->getAlimentoByTipoAlimentoId($tipoAlimentoId);
  }

  public function getAlimentoByNombre($nombre)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->alimentoDAO->getAlimentoByNombre($nombre);
  }

  public function procesarListar(array $filtros = []): array
  {
    if ($this->connError !== null) {
      return ['alimentos' => [], 'opciones' => ['tipos' => [], 'nombres' => []]];
    }

    return $this->alimentoDAO->listar($filtros);
  }

}


if (php_sapi_name() !== 'cli') {
  $ctrl = new AlimentoController();

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'listar' || ($_GET['action'] ?? '') === 'list')) {

    $getArray = function ($key) {
      if (!isset($_GET[$key]))
        return null;
      $val = $_GET[$key];
      if (!is_array($val))
        $val = [$val];
      $vals = array_filter($val, fn($v) => trim($v) !== '');
      return count($vals) > 0 ? $vals : null;
    };

    $filtros = [
      'tipoAlimentoId' => $getArray('tipoAlimentoId'),
      'nombre' => $getArray('nombre')
    ];

    $data = $ctrl->procesarListar($filtros);


    while (ob_get_level())
      ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }

  // 🚨 Usamos la variable $isAjax detectada al inicio
  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // La limpieza de buffer y encabezados ya se hizo al inicio del archivo (CORRECCIÓN CRÍTICA)

    try {
      $res = $ctrl->procesarFormularios();
      // Aseguramos que solo el resultado final se imprime como JSON
      echo json_encode($res ?? ['tipo' => 'error', 'mensaje' => 'Sin resultado']);
    } catch (Throwable $e) {
      // Capturamos cualquier error en la ejecución de la solicitud
      echo json_encode([
        'tipo' => 'error',
        'mensaje' => 'Excepción en servidor',
        'detalle' => $e->getMessage(),
      ]);
    }
    exit;
  }
}
