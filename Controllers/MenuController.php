<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Menu.php';

class MenuController {
    private $menuModel;
    private $dbError = null;

    public function __construct() {
        try {
            $db = new Database();
            $this->menuModel = new Menu($db->conectar());
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            
            if ($accion === 'crear') {
                $this->crear();
            } elseif ($accion === 'editar') {
                $this->editar();
            } elseif ($accion === 'eliminar') {
                $this->eliminar();
            }
        }
    }

    public function obtenerDatosVista() {
        if ($this->dbError) {
            return ['menu' => [], 'categorias' => []];
        }
        return [
            'menu' => $this->menuModel->obtenerTodos(),
            'categorias' => $this->menuModel->obtenerCategorias()
        ];
    }

    private function subirImagen($campo = 'imagen_file') {
        if (empty($_FILES[$campo]['name'])) return null;

        $file    = $_FILES[$campo];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (!in_array($ext, $allowed)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null;

        $dir = __DIR__ . '/../img/menu/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $nombre  = 'menu_' . time() . '_' . mt_rand(100,999) . '.' . $ext;
        $destino = $dir . $nombre;

        if (move_uploaded_file($file['tmp_name'], $destino)) {
            return $nombre;
        }
        return null;
    }

    private function crear() {
        $imgNombre = $this->subirImagen();
        $datos = [
            'id_categoria' => $_POST['id_categoria'],
            'nombre'       => $_POST['nombre'],
            'precio'       => $_POST['precio'],
            'descripcion'  => $_POST['descripcion'] ?? '',
            'imagen'       => $imgNombre ?? '',
            'disponible'   => isset($_POST['disponible']) ? 1 : 0
        ];

        $resultado = $this->menuModel->registrar($datos);
        
        if ($resultado === true) {
            header("Location: " . $this->baseUrl() . "/admin_gestion_de_menu.php?success=creado");
        } else {
            header("Location: " . $this->baseUrl() . "/admin_gestion_de_menu.php?error=crear");
        }
        exit;
    }

    private function editar() {
        $id_producto = $_POST['id_producto'];
        $imgNombre   = $this->subirImagen();

        $datos = [
            'id_categoria' => $_POST['id_categoria'],
            'nombre'       => $_POST['nombre'],
            'precio'       => $_POST['precio'],
            'descripcion'  => $_POST['descripcion'] ?? '',
            'imagen'       => $imgNombre,   // null = no cambiar
            'disponible'   => isset($_POST['disponible']) ? 1 : 0
        ];

        $resultado = $this->menuModel->actualizar($id_producto, $datos);
        
        if ($resultado === true) {
            header("Location: " . $this->baseUrl() . "/admin_gestion_de_menu.php?success=editado");
        } else {
            header("Location: " . $this->baseUrl() . "/admin_gestion_de_menu.php?error=editar");
        }
        exit;
    }

    private function eliminar() {
        $id_producto = $_POST['id_producto'];
        $resultado = $this->menuModel->eliminar($id_producto);
        
        if ($resultado === true) {
            header("Location: " . $this->baseUrl() . "/admin_gestion_de_menu.php?success=eliminado");
        } else {
            header("Location: " . $this->baseUrl() . "/admin_gestion_de_menu.php?error=eliminar");
        }
        exit;
    }
}
?>
