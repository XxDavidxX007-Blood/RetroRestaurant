<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Menu.php';

class MenuController {
    private $menuModel;

    public function __construct() {
        $db = new Database();
        $this->menuModel = new Menu($db->conectar());
    }

    public function manejarPeticion() {
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
        return [
            'menu' => $this->menuModel->obtenerTodos(),
            'categorias' => $this->menuModel->obtenerCategorias()
        ];
    }

    private function crear() {
        $datos = [
            'id_categoria' => $_POST['id_categoria'],
            'nombre' => $_POST['nombre'],
            'precio' => $_POST['precio'],
            'descripcion' => $_POST['descripcion'] ?? '',
            'imagen' => $_POST['imagen'] ?? '🍔',
            'disponible' => isset($_POST['disponible']) ? 1 : 0
        ];

        $resultado = $this->menuModel->registrar($datos);
        
        if ($resultado === true) {
            header("Location: admin_gestion_de_menu.php?success=creado");
        } else {
            header("Location: admin_gestion_de_menu.php?error=crear");
        }
        exit;
    }

    private function editar() {
        $id_producto = $_POST['id_producto'];
        $datos = [
            'id_categoria' => $_POST['id_categoria'],
            'nombre' => $_POST['nombre'],
            'precio' => $_POST['precio'],
            'descripcion' => $_POST['descripcion'] ?? '',
            'imagen' => $_POST['imagen'] ?? '🍔',
            'disponible' => isset($_POST['disponible']) ? 1 : 0
        ];

        $resultado = $this->menuModel->actualizar($id_producto, $datos);
        
        if ($resultado === true) {
            header("Location: admin_gestion_de_menu.php?success=editado");
        } else {
            header("Location: admin_gestion_de_menu.php?error=editar");
        }
        exit;
    }

    private function eliminar() {
        $id_producto = $_POST['id_producto'];
        $resultado = $this->menuModel->eliminar($id_producto);
        
        if ($resultado === true) {
            header("Location: admin_gestion_de_menu.php?success=eliminado");
        } else {
            header("Location: admin_gestion_de_menu.php?error=eliminar");
        }
        exit;
    }
}
?>
