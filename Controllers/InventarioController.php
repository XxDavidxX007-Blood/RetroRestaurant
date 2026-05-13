<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Inventario.php';

class InventarioController {
    private $inventarioModel;

    public function __construct() {
        $db = new Database();
        $this->inventarioModel = new Inventario($db->conectar());
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
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
                $this->exportar();
            }
        }
    }

    private function exportar() {
        $inventario = $this->inventarioModel->obtenerTodos();
        $filename   = "inventario_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // BOM para que Excel abra con tildes correctas
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Separador punto y coma → Excel en español lo reconoce como columnas
        fputcsv($output, ['ID', 'Producto', 'Categoría', 'Unidad', 'Stock Actual', 'Stock Mínimo', 'Precio Unitario', 'Valor Total'], ';');

        foreach ($inventario as $item) {
            $codigo     = 'ING-' . str_pad($item['id_producto'], 3, '0', STR_PAD_LEFT);
            $valorTotal = $item['stock'] * $item['precio'];

            fputcsv($output, [
                $codigo,
                $item['nombre'],
                $item['categoria'],
                $item['unidad'],
                (int)$item['stock'],
                (int)$item['minimo'],
                number_format((float)$item['precio'], 2, '.', ''),   // sin separador de miles
                number_format($valorTotal, 2, '.', ''),
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function obtenerDatosVista() {
        return [
            'inventario' => $this->inventarioModel->obtenerTodos(),
            'categorias' => $this->inventarioModel->obtenerCategorias()
        ];
    }

    private function crear() {
        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'id_categoria' => $_POST['id_categoria'] ?? '',
            'unidad' => $_POST['unidad'] ?? 'unid',
            'stock' => $_POST['stock'] ?? 0,
            'minimo' => $_POST['minimo'] ?? 0,
            'precio' => $_POST['precio'] ?? 0,
            'imagen' => '📦'
        ];

        $resultado = $this->inventarioModel->registrar($datos);
        
        if ($resultado === true) {
            header("Location: admin_gestion_de_inventario.php?success=creado");
            exit;
        } else {
            header("Location: admin_gestion_de_inventario.php?error=" . urlencode($resultado));
            exit;
        }
    }

    private function editar() {
        $id_producto = $_POST['id_producto'] ?? null;
        if (!$id_producto) {
            header("Location: admin_gestion_de_inventario.php?error=ID requerido");
            exit;
        }

        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'id_categoria' => $_POST['id_categoria'] ?? '',
            'unidad' => $_POST['unidad'] ?? 'unid',
            'stock' => $_POST['stock'] ?? 0,
            'minimo' => $_POST['minimo'] ?? 0,
            'precio' => $_POST['precio'] ?? 0,
            'imagen' => '📦'
        ];

        $resultado = $this->inventarioModel->actualizar($id_producto, $datos);
        
        if ($resultado === true) {
            header("Location: admin_gestion_de_inventario.php?success=editado");
            exit;
        } else {
            header("Location: admin_gestion_de_inventario.php?error=" . urlencode($resultado));
            exit;
        }
    }

    private function eliminar() {
        $id_producto = $_POST['id_producto'] ?? null;
        if (!$id_producto) {
            header("Location: admin_gestion_de_inventario.php?error=ID requerido");
            exit;
        }

        $resultado = $this->inventarioModel->eliminar($id_producto);
        
        if ($resultado === true) {
            header("Location: admin_gestion_de_inventario.php?success=eliminado");
            exit;
        } else {
            header("Location: admin_gestion_de_inventario.php?error=" . urlencode($resultado));
            exit;
        }
    }
}
?>
