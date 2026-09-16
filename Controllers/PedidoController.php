<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Pedido.php';

class PedidoController {
    private $model;
    private $dbError = null;

    public function __construct() {
        try {
            $db = new Database();
            $this->model = new Pedido($db->conectar());
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

        $accion  = $_POST['accion'] ?? '';
        $esAjax  = !empty($_POST['ajax']) || !empty($_GET['ajax']);

        if ($accion === 'cambiar_estado') {
            $id_pedido        = (int)($_POST['id_pedido'] ?? 0);
            $id_estado_pedido = (int)($_POST['id_estado_pedido'] ?? 0);

            if ($id_pedido && $id_estado_pedido) {
                $ok = $this->model->cambiarEstado($id_pedido, $id_estado_pedido);
                // Sincronizar fecha de eliminación si el nuevo estado es entregado/cancelado
                if ($ok) {
                    $this->model->sincronizarFechasEliminacion();
                }
            } else {
                $ok = false;
            }

            if ($esAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => (bool)$ok]);
                exit;
            }

            header("Location: " . $this->baseUrl() . "/admin_pedidos.php?success=estado_actualizado");
            exit;
        }
    }

    public function obtenerDatosVista() {
        if ($this->dbError) {
            return ['pedidos'=>[],'estados'=>[],'kpiEstados'=>[],'pedidosHoy'=>0,'varPedidos'=>0,'filtro_estado'=>'todos','filtro_fecha'=>'','pagina'=>1,'totalPaginas'=>1,'total'=>0,'por_pagina'=>10];
        }

        // Limpieza automática y sincronización de fechas al cargar la vista
        $this->model->sincronizarFechasEliminacion();
        $this->model->ejecutarLimpiezaAutomatica();

        $filtro_estado = $_GET['estado'] ?? 'todos';
        $filtro_fecha  = $_GET['fecha']  ?? '';
        $pagina        = max(1, (int)($_GET['pagina'] ?? 1));
        $por_pagina    = 10;

        $pedidosHoy  = $this->model->getPedidosHoy();
        $pedidosAyer = $this->model->getPedidosAyer();
        $varPedidos  = $pedidosAyer > 0
            ? round((($pedidosHoy - $pedidosAyer) / $pedidosAyer) * 100, 1)
            : ($pedidosHoy > 0 ? 100 : 0);

        $estados = $this->model->getEstados();

        // KPIs por estado (usa el nombre_estado de la BD)
        $kpiEstados = [];
        foreach ($estados as $e) {
            $kpiEstados[$e['nombre_estado']] = $this->model->getConteoByEstado($e['nombre_estado']);
        }

        $total   = $this->model->getTotalCount($filtro_estado, $filtro_fecha);
        $pedidos = $this->model->getAll($filtro_estado, $filtro_fecha, $pagina, $por_pagina);
        $totalPaginas = max(1, ceil($total / $por_pagina));

        return [
            'pedidos'       => $pedidos,
            'estados'       => $estados,
            'kpiEstados'    => $kpiEstados,
            'pedidosHoy'    => $pedidosHoy,
            'varPedidos'    => $varPedidos,
            'filtro_estado' => $filtro_estado,
            'filtro_fecha'  => $filtro_fecha,
            'pagina'        => $pagina,
            'totalPaginas'  => $totalPaginas,
            'total'         => $total,
            'por_pagina'    => $por_pagina,
        ];
    }

    public function obtenerDetallePedido($id_pedido) {
        if ($this->dbError) return ['pedido' => null, 'detalle' => [], 'estados' => []];
        return [
            'pedido'  => $this->model->getById($id_pedido),
            'detalle' => $this->model->getDetalle($id_pedido),
            'estados' => $this->model->getEstados(),
        ];
    }

    // ── HELPERS ESTÁTICOS ─────────────────────────────────────────

    public static function badgeEstado($estado) {
        $key = strtolower(str_replace(' ', '_', $estado));
        $map = [
            'pendiente'      => ['bg' => '#FEF3C7', 'color' => '#D97706', 'dot' => '#F59E0B'],
            'en_preparacion' => ['bg' => '#DBEAFE', 'color' => '#2563EB', 'dot' => '#3B82F6'],
            'listo'          => ['bg' => '#D1FAE5', 'color' => '#059669', 'dot' => '#10B981'],
            'entregado'      => ['bg' => '#EDE9FE', 'color' => '#7C3AED', 'dot' => '#8B5CF6'],
            'completado'     => ['bg' => '#EDE9FE', 'color' => '#7C3AED', 'dot' => '#8B5CF6'],
            'cancelado'      => ['bg' => '#FEE2E2', 'color' => '#DC2626', 'dot' => '#EF4444'],
        ];
        $b = $map[$key] ?? ['bg' => '#F3F4F6', 'color' => '#6B7280', 'dot' => '#9CA3AF'];
        $b['label'] = ucfirst(str_replace('_', ' ', $estado));
        return $b;
    }
}
