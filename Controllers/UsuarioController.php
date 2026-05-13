<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuario.php';

function getBaseUrlUsuario() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $base     = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $protocol . '://' . $host . $base;
}

class UsuarioController {

    public function registrar() {
        $base = getBaseUrlUsuario();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$base}/views/usuarios/registre.php");
            exit;
        }

        $nombre             = trim($_POST['nombre']             ?? '');
        $apellidos          = trim($_POST['apellidos']          ?? '');
        $email              = trim($_POST['email']              ?? '');
        $telefono           = trim($_POST['telefono']           ?? '');
        $password           = trim($_POST['password']           ?? '');
        $confirmar_password = trim($_POST['confirmar_password'] ?? '');
        $id_rol             = trim($_POST['id_rol']             ?? '3');

        if (empty($nombre) || empty($apellidos) || empty($email) || empty($telefono) || empty($password) || empty($confirmar_password) || empty($id_rol)) {
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Campos incompletos',
                'text'  => 'Debe completar todos los campos'
            ];
            header("Location: {$base}/views/usuarios/registre.php");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Email inválido',
                'text'  => 'Ingrese un email válido'
            ];
            header("Location: {$base}/views/usuarios/registre.php");
            exit;
        }

        if ($password !== $confirmar_password) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Error',
                'text'  => 'Las contraseñas no coinciden'
            ];
            header("Location: {$base}/views/usuarios/registre.php");
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Contraseña inválida',
                'text'  => 'Mínimo 6 caracteres'
            ];
            header("Location: {$base}/views/usuarios/registre.php");
            exit;
        }

        try {
            $database = new Database();
            $db       = $database->conectar();
        } catch (Exception $e) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Error de conexión',
                'text'  => 'No se pudo conectar a la base de datos. Intente más tarde.'
            ];
            header("Location: {$base}/views/usuarios/registre.php");
            exit;
        }

        $usuario = new Usuario($db);

        if ($usuario->existeemail($email)) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Email existente',
                'text'  => 'Este email ya está registrado'
            ];
            header("Location: {$base}/views/usuarios/registre.php");
            exit;
        }

        $password = password_hash($password, PASSWORD_DEFAULT);

        $datos = [
            'nombre'    => $nombre,
            'apellidos' => $apellidos,
            'email'     => $email,
            'telefono'  => $telefono,
            'password'  => $password,
            'id_rol'    => $id_rol,
        ];

        $resultado = $usuario->registrar($datos);

        if ($resultado === true) {
            $_SESSION['alert'] = [
                'icon'     => 'success',
                'title'    => 'Registro exitoso',
                'text'     => '¡Bienvenido! Tu cuenta ha sido creada correctamente.',
                'redirect' => 'login.php'
            ];
        } else {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Error al registrar',
                'text'  => $resultado
            ];
        }

        header("Location: {$base}/views/usuarios/registre.php");
        exit;
    }
}

$controller = new UsuarioController();
$controller->registrar();
?>
