<?php

require_once __DIR__ . '../../DAOS/categoriaDAO.php';
require_once __DIR__ . '../../modelos/categoria/categoriaModelo.php';

class CategoriaController
{
  private $categoriaDAO;
  private $connError = null;

  public function __construct()
  {
    try {
      $this->categoriaDAO = new CategoriaDAO();
    } catch (Exception $e) {
      $this->categoriaDAO = null;
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
          if ($this->categoriaDAO->existeNombre($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe una categoría con ese nombre'];
          }
          $ok = $this->categoriaDAO->registrarCategoria(new Categoria(null, $nombre));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Categoría registrada correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al registrar la categoría'];

        case 'modificar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para modificar'];
          }
          if (empty($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Completá todos los campos para modificar'];
          }
          if ($this->categoriaDAO->existeNombre($nombre, $id)) {
            return ['tipo' => 'error', 'mensaje' => 'Ya existe una categoría con ese nombre'];
          }
          $ok = $this->categoriaDAO->modificarCategoria(new Categoria($id, $nombre));
          return $ok
            ? ['tipo' => 'success', 'mensaje' => 'Categoría modificada correctamente']
            : ['tipo' => 'error', 'mensaje' => 'Error al modificar la categoría'];

        case 'eliminar':
          if (!$id) {
            return ['tipo' => 'error', 'mensaje' => 'ID inválido para eliminar'];
          }
          try {
            $ok = $this->categoriaDAO->eliminarCategoria($id);
            return $ok
              ? ['tipo' => 'success', 'mensaje' => 'Categoría eliminada correctamente']
              : ['tipo' => 'error', 'mensaje' => 'No se encontró la categoría o no se pudo eliminar'];
          } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1451) {
              return ['tipo' => 'error', 'mensaje' => 'No se puede eliminar la categoría porque está en uso'];
            }
            return ['tipo' => 'error', 'mensaje' => 'Error al eliminar: ' . $e->getMessage()];
          }
      }
    }
    return null;
  }

  public function obtenerCategorias()
  {
    if ($this->connError !== null) {
      return [];
    }
    return $this->categoriaDAO->getAllCategorias();
  }

  public function getCategoriaById($id)
  {
    if ($this->connError !== null) {
      return null;
    }
    return $this->categoriaDAO->getCategoriaById($id);
  }
}

if (php_sapi_name() !== 'cli') {
  $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  $ctrl = new CategoriaController();

  if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    $categorias = $ctrl->obtenerCategorias();
    $out = [];
    foreach ($categorias as $categoria) {
      $out[] = [
        'id' => $categoria->getId(),
        'nombre' => $categoria->getNombre(),
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