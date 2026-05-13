<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Pedido.php';

class PedidoController {
    private $model;

    public function __construct() {
        $db = new Database();
        $this->model = new Pedido($db->conectar());
    }

    public function manejarPeticion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $accion = $_POST['accion'] ?? '';

        if ($accion === 'cambiar_estado') {
            $id_pedido        = (int)($_POST['id_pedido'] ?? 0);
            $id_estado_pedido = (int)($_POST['id_estado_pedido'] ?? 0);

            if ($id_pedido && $id_estado_pedido) {
                $this->model->cambiarEstado($id_pedido, $id_estado_pedido);
            }

            header("Location: admin_pedidos.php?success=estado_actualizado");
            exit;
        }
    }

    public function obtenerDatosVista() {
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
