<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Dashboard.php';

class DashboardController {
    private $dashboardModel;
    private $dbError = null;

    public function __construct() {
        try {
            $db = new Database();
            $this->dashboardModel = new Dashboard($db->conectar());
        } catch (Exception $e) {
            $this->dbError = $e->getMessage();
        }
    }

    public function obtenerDatosVista() {
        // Valores por defecto seguros si la BD falla
        $defaults = [
            'ventasHoy'              => 0,
            'ventasAyer'             => 0,
            'pedidosHoy'             => 0,
            'pedidosAyer'            => 0,
            'clientesHoy'            => 0,
            'clientesAyer'           => 0,
            'varVentas'              => 0,
            'varPedidos'             => 0,
            'varClientes'            => 0,
            'platosMasVendidosCount' => 0,
            'totalProductos'         => 0,
            'chartLabels'            => json_encode([]),
            'chartData'              => json_encode([]),
            'pedidosEstado'          => [],
            'coloresEstado'          => [],
            'donutLabels'            => json_encode([]),
            'donutData'              => json_encode([]),
            'donutColors'            => json_encode([]),
            'pedidosRecientes'       => [],
            'platosMasVendidos'      => [],
            'actividadReciente'      => [],
            'domiciliosRecientes'    => [],
            'domiciliosHoy'          => 0,
        ];

        if ($this->dbError || !$this->dashboardModel) {
            return $defaults;
        }

        try {
            $ventasHoy    = $this->safe(fn() => $this->dashboardModel->getVentasHoy(),    0);
            $ventasAyer   = $this->safe(fn() => $this->dashboardModel->getVentasAyer(),   0);
            $pedidosHoy   = $this->safe(fn() => $this->dashboardModel->getPedidosHoy(),   0);
            $pedidosAyer  = $this->safe(fn() => $this->dashboardModel->getPedidosAyer(),  0);
            $clientesHoy  = $this->safe(fn() => $this->dashboardModel->getClientesHoy(),  0);
            $clientesAyer = $this->safe(fn() => $this->dashboardModel->getClientesAyer(), 0);

            // Gráfico de línea
            $chartLabels  = [];
            $chartValues  = [];
            $ventasSemana = $this->safe(fn() => $this->dashboardModel->getVentasSemana(), []);
            $mapaVentas   = array_column($ventasSemana, 'total', 'dia');
            for ($i = 6; $i >= 0; $i--) {
                $fecha = date('Y-m-d', strtotime("-$i days"));
                $chartLabels[] = date('d M', strtotime($fecha));
                $chartValues[] = isset($mapaVentas[$fecha]) ? (float)$mapaVentas[$fecha] : 0;
            }

            // Donut
            $pedidosEstado = $this->safe(fn() => $this->dashboardModel->getPedidosPorEstado(), []);
            $donutLabels   = [];
            $donutData     = [];
            $donutColors   = [];
            $coloresEstado = [];
            $paleta = ['#F59E0B','#3B82F6','#10B981','#8B5CF6','#EF4444','#06B6D4','#F97316'];
            foreach ($pedidosEstado as $i => $e) {
                $color = $paleta[$i] ?? '#94A3B8';
                $coloresEstado[$e['estado']] = $color;
                $donutLabels[] = ucfirst(str_replace('_', ' ', $e['estado']));
                $donutData[]   = (int)$e['total'];
                $donutColors[] = $color;
            }

            return [
                'ventasHoy'              => $ventasHoy,
                'ventasAyer'             => $ventasAyer,
                'pedidosHoy'             => $pedidosHoy,
                'pedidosAyer'            => $pedidosAyer,
                'clientesHoy'            => $clientesHoy,
                'clientesAyer'           => $clientesAyer,
                'varVentas'              => $this->variacion($ventasHoy, $ventasAyer),
                'varPedidos'             => $this->variacion($pedidosHoy, $pedidosAyer),
                'varClientes'            => $this->variacion($clientesHoy, $clientesAyer),
                'platosMasVendidosCount' => $this->safe(fn() => $this->dashboardModel->getPlatosMasVendidosCount(), 0),
                'totalProductos'         => $this->safe(fn() => $this->dashboardModel->getTotalProductos(), 0),
                'chartLabels'            => json_encode($chartLabels),
                'chartData'              => json_encode($chartValues),
                'pedidosEstado'          => $pedidosEstado,
                'coloresEstado'          => $coloresEstado,
                'donutLabels'            => json_encode($donutLabels),
                'donutData'              => json_encode($donutData),
                'donutColors'            => json_encode($donutColors),
                'pedidosRecientes'       => $this->safe(fn() => $this->dashboardModel->getPedidosRecientes(), []),
                'platosMasVendidos'      => $this->safe(fn() => $this->dashboardModel->getPlatosMasVendidos(), []),
                'actividadReciente'      => $this->safe(fn() => $this->dashboardModel->getActividadReciente(), []),
                'domiciliosRecientes'    => $this->safe(fn() => $this->dashboardModel->getDomiciliosRecientes(), []),
                'domiciliosHoy'          => $this->safe(fn() => $this->dashboardModel->getDomiciliosHoy(), 0),
            ];
        } catch (Exception $e) {
            return $defaults;
        }
    }

    // Ejecuta un callable y devuelve $default si lanza excepción
    private function safe(callable $fn, $default) {
        try { return $fn(); } catch (Exception $e) { return $default; }
    }

    private function variacion($hoy, $ayer) {
        if ($ayer == 0) return $hoy > 0 ? 100 : 0;
        return round((($hoy - $ayer) / $ayer) * 100, 1);
    }

    public static function tiempoRelativo($fecha) {
        $diff = time() - strtotime($fecha);
        if ($diff < 60)    return 'Hace ' . $diff . ' seg';
        if ($diff < 3600)  return 'Hace ' . floor($diff / 60) . ' min';
        if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' h';
        return 'Hace ' . floor($diff / 86400) . ' días';
    }

    public static function badgeEstado($estado) {
        $key = strtolower(str_replace(' ', '_', $estado));
        $badges = [
            'pendiente'      => ['color' => '#D97706', 'bg' => '#FEF3C7', 'dot' => '#D97706'],
            'en_preparacion' => ['color' => '#2563EB', 'bg' => '#DBEAFE', 'dot' => '#2563EB'],
            'listo'          => ['color' => '#059669', 'bg' => '#D1FAE5', 'dot' => '#059669'],
            'entregado'      => ['color' => '#7C3AED', 'bg' => '#EDE9FE', 'dot' => '#7C3AED'],
            'cancelado'      => ['color' => '#DC2626', 'bg' => '#FEE2E2', 'dot' => '#DC2626'],
        ];
        $badge = $badges[$key] ?? ['color' => '#6B7280', 'bg' => '#F3F4F6', 'dot' => '#6B7280'];
        $badge['label'] = ucfirst(str_replace('_', ' ', $estado));
        return $badge;
    }
}
?>
