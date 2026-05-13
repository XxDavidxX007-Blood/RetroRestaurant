<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reportes.php';

class ReportesController {
    private $reportesModel;

    public function __construct() {
        $db = new Database();
        $this->reportesModel = new Reportes($db->conectar());
    }

    public function obtenerDatosGraficos() {
        $kpis = $this->reportesModel->obtenerKPIsGenerales();
        $categoriasData = $this->reportesModel->obtenerValorPorCategoria();
        $stockData = $this->reportesModel->obtenerEstadoStock();

        // Preparar para Chart.js - Categorías
        $nombresCategorias = [];
        $valoresCategorias = [];
        foreach ($categoriasData as $cat) {
            $nombresCategorias[] = $cat['nombre_categoria'];
            $valoresCategorias[] = (float)$cat['valor_total'];
        }

        // Preparar para Chart.js - Estado Stock
        $nombresStock = array_keys($stockData);
        $valoresStock = array_values($stockData);

        return [
            'kpis' => $kpis,
            'grafico_categorias' => [
                'labels' => $nombresCategorias,
                'data' => $valoresCategorias
            ],
            'grafico_stock' => [
                'labels' => $nombresStock,
                'data' => $valoresStock
            ]
        ];
    }
    public function manejarPeticion() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
            $accion = $_POST['accion'];

            if ($accion === 'generar_reporte') {
                $tipo = $_POST['tipo_filtro'] ?? 'todo';
                $valor_categoria = $_POST['filtro_categoria'] ?? '';
                $valor_estado = $_POST['filtro_estado'] ?? '';
                $formato = $_POST['formato'] ?? 'pantalla';
                
                $valor = '';
                if ($tipo === 'categoria') $valor = $valor_categoria;
                if ($tipo === 'estado') $valor = $valor_estado;

                $resultados = $this->reportesModel->obtenerInventarioFiltrado($tipo, $valor);

                if ($formato === 'csv') {
                    $this->exportarCSV($resultados);
                } else {
                    return [
                        'datos' => $resultados,
                        'filtros' => [
                            'tipo' => $tipo,
                            'valor' => $valor
                        ]
                    ];
                }
            } elseif ($accion === 'guardar_reporte') {
                $datosGuardar = [
                    'nombre_usuario' => $_POST['nombre_usuario'] ?? 'Desconocido',
                    'tipo_filtro' => $_POST['tipo_filtro'] ?? '',
                    'valor_filtro' => $_POST['valor_filtro'] ?? '',
                    'total_productos' => $_POST['total_productos'] ?? 0,
                    'valor_total' => $_POST['valor_total'] ?? 0,
                    'datos_json' => $_POST['datos_json'] ?? '[]'
                ];
                $this->reportesModel->guardarHistorial($datosGuardar);
                header("Location: admin_reportes.php?success=guardado");
                exit;
            } elseif ($accion === 'eliminar_reporte') {
                $id = $_POST['id_reporte'] ?? null;
                if ($id) {
                    $this->reportesModel->eliminarHistorial($id);
                    header("Location: admin_reportes.php?success=eliminado");
                    exit;
                }
            }
        }
        return null;
    }

    public function obtenerHistorial() {
        return $this->reportesModel->obtenerHistorial();
    }
    
    public function obtenerReporteEspecifico($id) {
        return $this->reportesModel->obtenerReporteGuardado($id);
    }

    private function exportarCSV($datos) {
        $filename = "reporte_personalizado_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Producto', 'Categoria', 'Unidad', 'Stock Actual', 'Stock Minimo', 'Precio Unitario', 'Valor Total', 'Estado'));
        
        foreach ($datos as $item) {
            $codigo = 'ING-' . str_pad($item['id_producto'], 3, '0', STR_PAD_LEFT);
            $valorTotal = $item['stock'] * $item['precio'];
            
            $estado = 'En stock';
            if ($item['stock'] == 0) $estado = 'Sin stock';
            elseif ($item['stock'] <= $item['minimo']) $estado = 'Stock bajo';

            fputcsv($output, array(
                $codigo,
                $item['nombre'],
                $item['categoria'],
                $item['unidad'],
                $item['stock'],
                $item['minimo'],
                $item['precio'],
                $valorTotal,
                $estado
            ));
        }
        fclose($output);
        exit;
    }
}
?>
