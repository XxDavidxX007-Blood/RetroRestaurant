<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuario.php';

class PerfilController {
    private $model;
    private $dbError = null;

    public function __construct() {
        try {
            $db = new Database();
            $this->model = new Usuario($db->conectar());
        } catch (Exception $e) {
            $this->dbError = $e->getMessage();
        }
    }

    private function baseUrl() {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'];
        $base  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
        if ($base === '.') $base = '';
        return "{$proto}://{$host}{$base}/views/dashboard";
    }

    public function manejarPeticion() {
        if ($this->dbError) return;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
        if (!$id) return;

        $nombre = trim ($_POST ['nombre'] ?? '');
        $apellidos = trim ($_POST ['apellidos'] ?? '');
        $telefono = trim ($_POST ['telefono'] ?? '');
        $password = trim ($_POST ['password'] ?? '');
        $confirmar = trim ($_POST ['confirmar'] ?? '');

        if (empty($nombre) || empty($apellidos)) {
            $_SESSION['perfil_error'] = 'Nombre y apellidos son obligatorios.';
            header("Location: " . $this->baseUrl() . "/perfil.php"); exit;
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                $_SESSION['perfil_error'] = 'La contraseña debe tener al menos 6 caracteres.';
                header("Location: " . $this->baseUrl() . "/perfil.php"); exit;
            }
            if ($password !== $confirmar) {
                $_SESSION['perfil_error'] = 'Las contraseñas no coinciden.';
                header("Location: " . $this->baseUrl() . "/perfil.php"); exit;
            }
        }

        // ── Subida de foto ────────────────────────────────────────
        $fotoPath = null;
        if (!empty($_FILES['foto']['name'])) {
            $file     = $_FILES['foto'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp','gif'];

            if (!in_array($ext, $allowed)) {
                $_SESSION['perfil_error'] = 'Formato de imagen no permitido. Usa JPG, PNG o WEBP.';
                header("Location: " . $this->baseUrl() . "/perfil.php"); exit;
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                $_SESSION['perfil_error'] = 'La imagen no puede superar 2 MB.';
                header("Location: " . $this->baseUrl() . "/perfil.php"); exit;
            }

            $nombreArchivo = 'user_' . $id . '_' . time() . '.' . $ext;
            $destino       = __DIR__ . '/../img/perfiles/' . $nombreArchivo;

            if (!move_uploaded_file($file['tmp_name'], $destino)) {
                $_SESSION['perfil_error'] = 'Error al guardar la imagen.';
                header("Location: " . $this->baseUrl() . "/perfil.php"); exit;
            }

            // Borrar foto anterior si existe
            $actual = $this->model->obtenerPorId($id);
            if (!empty($actual['foto'])) {
                $anterior = __DIR__ . '/../img/perfiles/' . $actual['foto'];
                if (file_exists($anterior)) @unlink($anterior);
            }

            $fotoPath = $nombreArchivo;
        }

        $datos = [
            'nombre'    => $nombre,
            'apellidos' => $apellidos,
            'telefono'  => $telefono,
            'password'  => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '',
            'foto'      => $fotoPath,
        ];

        $resultado = $this->model->actualizarPerfil($id, $datos);

        if ($resultado === true) {
            $_SESSION['usuario']['nombre']    = $nombre;
            $_SESSION['usuario']['apellidos'] = $apellidos;
            $_SESSION['usuario']['telefono']  = $telefono;
            if ($fotoPath) $_SESSION['usuario']['foto'] = $fotoPath;
            $_SESSION['perfil_ok'] = 'Perfil actualizado correctamente.';
        } else {
            $_SESSION['perfil_error'] = $resultado;
        }

        header("Location: " . $this->baseUrl() . "/perfil.php"); exit;
    }

    public function obtenerUsuario() {
        if ($this->dbError) return null;
        $id = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
        return $this->model->obtenerPorId($id);
    }
}
