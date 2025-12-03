<?php

require_once __DIR__ . '../../DAOS/ordenDAO.php';
require_once __DIR__ . '../../modelos/orden/ordenModelo.php';

class OrdenController
{
  private $ordenDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->ordenDAO = new OrdenkDAO();

    } catch (Exception $e) {
      $this->ordenDAO = null;
      $this->connError = $e->getMessage();
    }
  }

  public function procesarFormularios()
  {
    // Manejo de error de conexión
    if ($this->connError !== null) {
      if (ob_get_level()) {
        ob_clean();
      }
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'tipo' => 'error',
        'mensaje' => 'Error de conexión a la base de datos: ' . $this->connError
      ]);
      exit;
    }

    // ===============================
    // ABM STOCK (POST)
    // ===============================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $data = $_POST;
      if (empty($data)) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?? [];
      }

      $accion = $data['accion'] ?? null;
      $id = intval($data['id'] ?? 0);
      $potreroId = trim($data['potreroId'] ?? '');
      $tipoAlimentoId = trim($data['tipoAlimentoId'] ?? '');
      $alimentoId = trim($data['alimentoId'] ?? '');
      $cantidad = intval($data['cantidad'] ?? 0);
      $usuarioId = trim($data['usuarioId'] ?? '');
      $estadoId = trim($data['estadoId'] ?? '');
      $fechaCreacion = trim($data['fechaCreacion'] ?? '');
      $fechaActualizacion = trim($data['fechaActualizacion'] ?? '');
      $horaCreacion = trim($data['horaCreacion'] ?? '');
      $horaActualizacion = trim($data['horaActualizacion'] ?? '');

      $res = ['tipo' => 'error', 'mensaje' => 'Acción no válida'];

      switch ($accion) {

        case 'registrar':
          if (
            empty($potreroId) ||
            empty($tipoAlimentoId) ||
            empty($alimentoId) ||
            empty($cantidad)
          ) {
            $res = [
              'tipo' => 'error',
              'mensaje' => 'Debés completar Potrero, Tipo Alimento, Alimento y Cantidad.'
            ];
            break;
          }

          $potreroId = intval($potreroId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);
          $cantidad = intval($cantidad);

          $ok = $this->ordenDAO->registrarOrden(
            new Orden(
              null,
              $potreroId,
              $tipoAlimentoId,
              $alimentoId,
              $cantidad,
              $usuarioId,
              $estadoId,
              $fechaCreacion,
              $fechaActualizacion,
              $horaCreacion,
              $horaActualizacion
            )
          );

          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Orden registrada correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrarla orden.'];

          break;

        case 'modificar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido.'];
            break;
          }

          if (empty($potreroId) || empty($tipoAlimentoId) || empty($alimentoId) || empty($cantidad)) {
            $res = ['tipo' => 'error', 'mensaje' => 'Error: Debés completar Campo, Tipo Alimento, Alimento y Cantidad.'];
            break;
          }

          $potreroId = intval($potreroId);
          $tipoAlimentoId = intval($tipoAlimentoId);
          $alimentoId = intval($alimentoId);
          $cantidad = intval($cantidad);

          $ok = $this->ordenDAO->modificarOrden(
            new Orden(
              $id,
              $potreroId,
              $tipoAlimentoId,
              $alimentoId,
              $cantidad,
              $usuarioId,
              $estadoId,
              $fechaCreacion,
              $fechaActualizacion,
              $horaCreacion,
              $horaActualizacion
            )
          );

          $res = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Orden modificada correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar la orden.'];

          break;

        case 'eliminar':
          if (!$id) {
            $res = ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar.'];
          } else {
            try {
              $ok = $this->ordenDAO->eliminarOrden($id);
              $res = $ok
                ? ['tipo' => 'success', 'mensaje' => 'Orden eliminada correctamente.']
                : ['tipo' => 'error', 'mensaje' => 'No se encontró la orden o no se pudo eliminar.'];
            } catch (mysqli_sql_exception $e) {
              if ((int) $e->getCode() === 1451) {
                $res = ['tipo' => 'error', 'mensaje' => 'No se puede eliminar porque está en uso.'];
              } else {
                $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
              }
            }
          }
          exit;
      }

      if (ob_get_level()) {
        ob_clean();
      }
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($res);
      exit;
    }
  }

  // ================
  // MÉTODOS DE APOYO
  // ================
  public function obtenerOrden()
  {
    if ($this->connError !== null) {
      return [];
    }
    return $this->ordenDAO->getAllOrdenes();
  }

  public function getOrdenById($id)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenById($id);
  }

  public function getOrdenByPotreroId($potreroId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByPotreroId($potreroId);
  }

  public function getOrdenByTipoAlimentoId($tipoAlimentoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByTipoAlimentoId($tipoAlimentoId);
  }

  public function getOrdenByAlimentoId($alimentoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByAlimentoId($alimentoId);
  }

  public function getOrdenByCantidad($cantidad)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByCantidad($cantidad);
  }

    public function getOrdenByUsuarioId($usuarioId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByUsuarioId($usuarioId);
  }

    public function getOrdenByEstadoId($estadoId)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->ordenDAO->getOrdenByEstadoId($estadoId);
  }
}

// PUNTO DE ENTRADA PRINCIPAL
if (php_sapi_name() !== 'cli') {
  $isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    ||
    ($_SERVER['REQUEST_METHOD'] === 'POST')
  );

  $ctrl = new OrdenController();

  if ($isAjax) {
    ob_start();
    $ctrl->procesarFormularios();
    exit;
  }

  if (isset($_POST['accion'])) {
    $ctrl->procesarFormularios();
    exit;
  }
}