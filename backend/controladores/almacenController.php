<?php

// Incluye los archivos necesarios para las operaciones con la base de datos y los modelos.
require_once __DIR__ . '../../DAOS/almacenDAO.php';
require_once __DIR__ . '../../DAOS/campoDAO.php';
require_once __DIR__ . '../../modelos/almacen/almacenModelo.php';

// Clase controladora para gestionar las operaciones relacionadas con los almacenes.
class AlmacenController
{
  // Propiedad para la instancia de AlmacenDAO.
  private $almacenDAO;

  public function __construct()
  {
    $this->almacenDAO = new AlmacenDAO();
  }

// Procesa los formularios de registro, modificación y eliminación de almacenes.
  public function procesarFormularios()
  {
    $mensaje = '';

    // Verifica si la petición es de tipo POST.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Obtiene la acción solicitada y los datos del formulario.
      $accion = $_POST['accion'] ?? '';
      $nombre = trim($_POST['nombre'] ?? '');
      $campoNombre = trim($_POST['campoNombre'] ?? '');

      // Evalúa la acción para realizar la operación correspondiente.
      switch ($accion) {
        case 'registrar':
          // Valida que los campos no estén vacíos.
          if (empty($nombre) || empty($campoNombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Por favor, completá todos los campos para registrar'];
          }

          // Busca el campo por su nombre para obtener su ID.
          $campoDAO = new CampoDAO();
          $campo = $campoDAO->getCampoByNombre($campoNombre);

          // Si el campo no existe, devuelve un error.
          if (!$campo) {
            return ['tipo' => 'error', 'mensaje' => 'El campo seleccionado no existe'];
          }

          $campoId = $campo->getId();
          // Verifica si ya existe un almacén asociado a ese campo.
          $almacenExistente = $this->almacenDAO->getAlmacenByCampoId($campo->getId());

          // Crea una nueva instancia de Almacen.
          $almacen = new Almacen(null, $nombre, $campoId);

          // Intenta registrar el almacén.
          if ($this->almacenDAO->registrarAlmacen($almacen)) {
            return ['tipo' => 'success', 'mensaje' => 'Almacen registrado correctamente'];
          } else {
            return ['tipo' => 'error', 'mensaje' => 'Error: ya existe un almacen con ese nombre'];
          }
        case 'modificar':
          // Valida que el nombre no esté vacío.
          if (empty($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Por favor, ingresá el nombre del almacen que querés modificar'];
          }

          // Obtiene el almacén actual por su nombre.
          $almacenActual = $this->almacenDAO->getAlmacenByNombre($nombre);

          // Si el almacén no existe, devuelve un error.
          if (!$almacenActual) {
            return ['tipo' => 'error', 'mensaje' => 'El almacen no existe para modificar'];
          }

          // Si el nombre del campo fue modificado, valida que exista.
          if (!empty($campoNombre)) {
            $campoDAO = new CampoDAO();
            $campo = $campoDAO->getCampoByNombre($campoNombre);
            if (!$campo) {
              return ['tipo' => 'error', 'mensaje' => 'El almacen no existe'];
            }
            $campoIdNuevo = $campo->getId();
          } else {
            // Si no se cambió, mantiene el ID del campo actual.
            $campoIdNuevo = $almacenActual->getCampoId();
          }

          // Crea una instancia de Almacen con los datos actualizados.
          $almacenModificado = new Almacen(null, $nombre, $campoIdNuevo);

          // Intenta modificar el almacén.
          if ($this->almacenDAO->modificarAlmacen($almacenModificado)) {
            return ['tipo' => 'success', 'mensaje' => 'Almacen modificado correctamente'];
          } else {
            return ['tipo' => 'error', 'mensaje' => 'Error al modificar el almacen'];
          }
        case 'eliminar':
          // Valida que el nombre no esté vacío.
          if (empty($nombre)) {
            return ['tipo' => 'error', 'mensaje' => 'Por favor, ingresá el nombre del almacen que querés eliminar'];
          }

          // Verifica que el almacén exista antes de intentar eliminarlo.
          $almacenActual = $this->almacenDAO->getAlmacenByNombre($nombre);

          if (!$almacenActual) {
            return ['tipo' => 'error', 'mensaje' => 'Almacen no existe para modificar'];
          }

          // Intenta eliminar el almacén.
          if ($this->almacenDAO->eliminarAlmacen($nombre)) {
            return ['tipo' => 'success', 'mensaje' => 'Almacen eliminado correctamente'];
          } else {
            return ['tipo' => 'error', 'mensaje' => 'Error al eliminar el almacen'];
          }
        default:
          return ['tipo' => 'error', 'mensaje' => 'Acción no válida'];
      }
    }
    return null;
  }

// Obtiene todos los almacenes de la base de datos.
  public function obtenerAlmacenes()
  {
    return $this->almacenDAO->getAllAlmacenes();
  }

// Obtiene un almacén por su nombre.
  public function getAlmacenByNombre($almacen_nombre)
  {
    return $this->almacenDAO->getAlmacenByNombre($almacen_nombre);
  }
}