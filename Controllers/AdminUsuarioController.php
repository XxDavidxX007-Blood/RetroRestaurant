<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuario.php';

function getBaseUrlAdmin() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $base     = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $protocol . '://' . $host . $base;
}

$base = getBaseUrlAdmin();

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador'])) {
    header("Location: {$base}/views/usuarios/login.php");
    exit;
}

class AdminUsuarioController {
    private $usuarioModel;
    private $base;

    public function __construct($base) {
        $this->base = $base;
        $database = new Database();
        $db = $database->conectar();
        $this->usuarioModel = new Usuario($db);
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->base}/views/dashboard/admin.php");
            exit;
        }

        $datos = [
            'nombre'    => trim($_POST['nombre']    ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'email'     => trim($_POST['email']     ?? ''),
            'telefono'  => trim($_POST['telefono']  ?? ''),
            'password'  => password_hash(trim($_POST['password'] ?? ''), PASSWORD_DEFAULT),
            'id_rol'    => trim($_POST['id_rol']    ?? '')
        ];

        if (empty($datos['nombre']) || empty($datos['apellidos']) || empty($datos['email']) || empty($_POST['password']) || empty($datos['id_rol'])) {
            $this->setAlert('warning', 'Campos incompletos', 'Debe completar todos los campos obligatorios');
            header("Location: {$this->base}/views/dashboard/admin.php");
            exit;
        }

        if ($this->usuarioModel->existeemail($datos['email'])) {
            $this->setAlert('error', 'Correo existente', 'Este correo ya está registrado');
            header("Location: {$this->base}/views/dashboard/admin.php");
            exit;
        }

        $resultado = $this->usuarioModel->registrar($datos);

        if ($resultado === true) {
            $this->setAlert('success', 'Éxito', 'Usuario creado correctamente');
        } else {
            $this->setAlert('error', 'Error', is_string($resultado) ? $resultado : 'Error al crear usuario');
        }

        header("Location: {$this->base}/views/dashboard/admin.php");
        exit;
    }

    public function editar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->base}/views/dashboard/admin.php");
            exit;
        }

        $id_usuario = $_POST['id_usuario'] ?? null;

        if (!$id_usuario) {
            $this->setAlert('error', 'Error', 'ID de usuario no proporcionado');
            header("Location: {$this->base}/views/dashboard/admin.php");
            exit;
        }

        $datos = [
            'nombre'    => trim($_POST['nombre']    ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'id_rol'    => trim($_POST['id_rol']    ?? '')
        ];

        if (!empty($_POST['password'])) {
            $datos['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $resultado = $this->usuarioModel->actualizar($id_usuario, $datos);

        if ($resultado === true) {
            $this->setAlert('success', 'Éxito', 'Usuario actualizado correctamente');
        } else {
            $this->setAlert('error', 'Error', is_string($resultado) ? $resultado : 'Error al actualizar usuario');
        }

        header("Location: {$this->base}/views/dashboard/admin.php");
        exit;
    }

    public function deshabilitar() {
        $id = (int)($_POST['id_usuario'] ?? 0);
        if (!$id) {
            $this->setAlert('error', 'Error', 'ID de usuario no proporcionado');
            header("Location: {$this->base}/views/dashboard/admin.php"); exit;
        }
        // Proteger al propio admin
        if ($id === (int)$_SESSION['usuario']['id_usuario']) {
            $this->setAlert('warning', 'Acción no permitida', 'No puedes deshabilitar tu propia cuenta');
            header("Location: {$this->base}/views/dashboard/admin.php"); exit;
        }
        $r = $this->usuarioModel->deshabilitar($id);
        if ($r === true) $this->setAlert('success', 'Listo', 'Usuario deshabilitado correctamente');
        else             $this->setAlert('error', 'Error', is_string($r) ? $r : 'Error al deshabilitar');
        header("Location: {$this->base}/views/dashboard/admin.php"); exit;
    }

    public function activar() {
        $id = (int)($_POST['id_usuario'] ?? 0);
        if (!$id) {
            $this->setAlert('error', 'Error', 'ID de usuario no proporcionado');
            header("Location: {$this->base}/views/dashboard/admin.php"); exit;
        }
        $r = $this->usuarioModel->activar($id);
        if ($r === true) $this->setAlert('success', 'Listo', 'Usuario activado correctamente');
        else             $this->setAlert('error', 'Error', is_string($r) ? $r : 'Error al activar');
        header("Location: {$this->base}/views/dashboard/admin.php"); exit;
    }

    public function eliminar() {
        $id = (int)($_POST['id_usuario'] ?? 0);
        if (!$id) {
            $this->setAlert('error', 'Error', 'ID de usuario no proporcionado');
            header("Location: {$this->base}/views/dashboard/admin.php"); exit;
        }
        // Proteger al propio admin
        if ($id === (int)$_SESSION['usuario']['id_usuario']) {
            $this->setAlert('warning', 'Acción no permitida', 'No puedes eliminar tu propia cuenta');
            header("Location: {$this->base}/views/dashboard/admin.php"); exit;
        }
        $r = $this->usuarioModel->eliminar($id);
        if ($r === true) $this->setAlert('success', 'Eliminado', 'Usuario eliminado correctamente');
        else             $this->setAlert('error', 'Error', is_string($r) ? $r : 'Error al eliminar');
        header("Location: {$this->base}/views/dashboard/admin.php"); exit;
    }

    private function setAlert($icon, $title, $text) {
        $_SESSION['alert'] = [
            'icon'  => $icon,
            'title' => $title,
            'text'  => $text
        ];
    }
}

$controller = new AdminUsuarioController($base);
$accion     = $_GET['accion'] ?? '';

switch ($accion) {
    case 'crear':
        $controller->crear();
        break;

    case 'editar':
        $controller->editar();
        break;

    case 'deshabilitar':
        $controller->deshabilitar();
        break;

    case 'activar':
        $controller->activar();
        break;

    case 'eliminar':
        $controller->eliminar();
        break;

    default:
        header("Location: {$base}/views/dashboard/admin.php");
        exit;
}
?>
