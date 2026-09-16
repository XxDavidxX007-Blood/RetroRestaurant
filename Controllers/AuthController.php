<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuario.php';

// Detectar la base de la URL para construir redirects absolutos
// Funciona tanto en local (laragon) como en producción (byethost24)
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $script   = $_SERVER['SCRIPT_NAME']; // /RetroRestaurant/Controllers/AuthController.php
    // Subir dos niveles: Controllers/ -> raíz del proyecto
    $base     = dirname(dirname($script));
    // Normalizar: si queda solo "/" no duplicar
    $base     = rtrim($base, '/');
    return $protocol . '://' . $host . $base;
}

class AuthController {

    public function login() {
        $base = getBaseUrl();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$base}/views/usuarios/login.php");
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Campos incompletos',
                'text'  => 'Debe ingresar correo y contraseña'
            ];
            header("Location: {$base}/views/usuarios/login.php");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Correo inválido',
                'text'  => 'Ingrese un correo electrónico válido'
            ];
            header("Location: {$base}/views/usuarios/login.php");
            exit;
        }

        try {
            $database     = new Database();
            $db           = $database->conectar();
            $usuarioModel = new Usuario($db);
            $usuario      = $usuarioModel->obtenerPorEmail($email);
        } catch (Exception $e) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Error de conexión',
                'text'  => 'No se pudo conectar a la base de datos. Intente más tarde.'
            ];
            header("Location: {$base}/views/usuarios/login.php");
            exit;
        }

        if (!$usuario) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Usuario no encontrado',
                'text'  => 'El correo no está registrado'
            ];
            header("Location: {$base}/views/usuarios/login.php");
            exit;
        }

        if (!password_verify($password, $usuario['password'])) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Contraseña incorrecta',
                'text'  => 'Verifique sus credenciales'
            ];
            header("Location: {$base}/views/usuarios/login.php");
            exit;
        }

        // Verificar si la cuenta está habilitada
        if (isset($usuario['activo']) && $usuario['activo'] == 0) {
            $_SESSION['alert'] = [
                'icon'  => 'error', 
                'title' => 'Cuenta deshabilitada',
                'text'  => 'Tu cuenta ha sido deshabilitada. Contacta al administrador.'
            ];
            header("Location: {$base}/views/usuarios/login.php");
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_usuario'],
            'nombre'     => $usuario['nombre'],
            'apellidos'  => $usuario['apellidos'],
            'email'      => $usuario['email'],
            'telefono'   => $usuario['telefono'],
            'id_rol'     => $usuario['id_rol'],
            'foto'       => $usuario['foto'] ?? null,
        ];

        $rol = (string) $usuario['id_rol'];
        switch ($rol) {
            case '1':
            case 'administrador':
                header("Location: {$base}/views/dashboard/admin_dashboard.php");
                exit;

            case '2':
            case 'empleado':
                header("Location: {$base}/views/dashboard/empleado.php");
                exit;

            case '3':
            case 'cliente':
                header("Location: {$base}/views/dashboard/cliente.php");
                exit;

            default:
                $_SESSION['alert'] = [
                    'icon'  => 'error',
                    'title' => 'Rol no válido',
                    'text'  => 'No se pudo determinar el acceso del usuario'
                ];
                header("Location: {$base}/views/usuarios/login.php");
                exit;
        }
    }

    public function logout() {
        $base = getBaseUrl();
        session_unset();
        session_destroy();
        header("Location: {$base}/views/usuarios/login.php");
        exit;
    }
}

$controller = new AuthController();
$accion     = $_GET['accion'] ?? 'login';

if ($accion === 'logout') {
    $controller->logout();
} else {
    $controller->login();
}
?>
