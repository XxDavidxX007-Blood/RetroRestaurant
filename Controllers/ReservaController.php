<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reserva.php';

class ReservaController {
    private $model;

    public function __construct() {
        $db = new Database();
        $this->model = new Reserva($db->conectar());
    }

    public function manejarPeticion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $accion = $_POST['accion'] ?? '';

        switch ($accion) {
            case 'crear':
                $this->crear();
                break;
            case 'editar':
                $this->editar();
                break;
            case 'eliminar':
                $this->eliminar();
                break;
        }
    }

    private function crear() {
        $datos = [
            'id_cliente'        => (int)($_POST['id_cliente'] ?? 0) ?: null,
            'id_mesa'           => (int)($_POST['id_mesa'] ?? 0),
            'fecha_reserva'     => $_POST['fecha_reserva']  ?? '',
            'hora_reserva'      => $_POST['hora_reserva']   ?? '',
            'numero_personas'   => (int)($_POST['numero_personas'] ?? 1),
            'id_estado_reserva' => (int)($_POST['id_estado_reserva'] ?? 1),
        ];

        $resultado = $this->model->crear($datos);
        if ($resultado === true) {
            header("Location: admin_reservas.php?success=creada");
        } else {
            header("Location: admin_reservas.php?error=" . urlencode($resultado));
        }
        exit;
    }

    private function editar() {
        $id = (int)($_POST['id_reserva'] ?? 0);
        if (!$id) {
            header("Location: admin_reservas.php?error=ID+requerido");
            exit;
        }

        $datos = [
            'id_mesa'           => (int)($_POST['id_mesa'] ?? 0),
            'fecha_reserva'     => $_POST['fecha_reserva']  ?? '',
            'hora_reserva'      => $_POST['hora_reserva']   ?? '',
            'numero_personas'   => (int)($_POST['numero_personas'] ?? 1),
            'id_estado_reserva' => (int)($_POST['id_estado_reserva'] ?? 1),
        ];

        $resultado = $this->model->actualizar($id, $datos);
        if ($resultado === true) {
            header("Location: admin_reservas.php?success=editada");
        } else {
            header("Location: admin_reservas.php?error=" . urlencode($resultado));
        }
        exit;
    }

    private function eliminar() {
        $id = (int)($_POST['id_reserva'] ?? 0);
        if (!$id) {
            header("Location: admin_reservas.php?error=ID+requerido");
            exit;
        }

        $resultado = $this->model->eliminar($id);
        if ($resultado === true) {
            header("Location: admin_reservas.php?success=eliminada");
        } else {
            header("Location: admin_reservas.php?error=" . urlencode($resultado));
        }
        exit;
    }

    public function obtenerDatosVista() {
        $filtro_estado = $_GET['estado']  ?? 'todos';
        $filtro_fecha  = $_GET['fecha']   ?? '';
        $filtro_tab    = $_GET['tab']     ?? 'todos';
        $pagina        = max(1, (int)($_GET['pagina'] ?? 1));
        $por_pagina    = 10;

        // Aplicar tab rápido como filtro de fecha
        $fecha_efectiva = $filtro_fecha;
        if (!$fecha_efectiva) {
            if ($filtro_tab === 'hoy')    $fecha_efectiva = date('Y-m-d');
            if ($filtro_tab === 'manana') $fecha_efectiva = date('Y-m-d', strtotime('+1 day'));
            if ($filtro_tab === 'semana') {
                // Para "esta semana" no filtramos por fecha exacta, dejamos vacío
                // y mostramos desde hoy hasta fin de semana
                $fecha_efectiva = '';
            }
        }

        $reservasHoy    = $this->model->getReservasHoy();
        $reservasAyer   = $this->model->getReservasAyer();
        $reservasManana = $this->model->getReservasManana();

        $varHoy    = $reservasAyer > 0
            ? round((($reservasHoy - $reservasAyer) / $reservasAyer) * 100, 1)
            : ($reservasHoy > 0 ? 100 : 0);
        $varManana = $reservasHoy > 0
            ? round((($reservasManana - $reservasHoy) / $reservasHoy) * 100, 1)
            : ($reservasManana > 0 ? 100 : 0);

        $estados   = $this->model->getEstados();
        $mesas     = $this->model->getMesas();
        $clientes  = $this->model->getClientes();

        $kpiEstados = [];
        foreach ($estados as $e) {
            $kpiEstados[$e['nombre_estado']] = $this->model->getConteoByEstado($e['nombre_estado']);
        }

        $total        = $this->model->getTotalCount($filtro_estado, $fecha_efectiva);
        $reservas     = $this->model->getAll($filtro_estado, $fecha_efectiva, $pagina, $por_pagina);
        $totalPaginas = max(1, ceil($total / $por_pagina));

        return [
            'reservas'       => $reservas,
            'estados'        => $estados,
            'mesas'          => $mesas,
            'clientes'       => $clientes,
            'kpiEstados'     => $kpiEstados,
            'reservasHoy'    => $reservasHoy,
            'reservasManana' => $reservasManana,
            'varHoy'         => $varHoy,
            'varManana'      => $varManana,
            'filtro_estado'  => $filtro_estado,
            'filtro_fecha'   => $filtro_fecha,
            'filtro_tab'     => $filtro_tab,
            'fecha_efectiva' => $fecha_efectiva,
            'pagina'         => $pagina,
            'totalPaginas'   => $totalPaginas,
            'total'          => $total,
            'por_pagina'     => $por_pagina,
        ];
    }

    // ── HELPERS ESTÁTICOS ─────────────────────────────────────────

    public static function badgeEstado($estado) {
        $key = strtolower(str_replace([' ', '-'], '_', $estado));
        $map = [
            'confirmada'  => ['bg' => '#D1FAE5', 'color' => '#059669', 'dot' => '#10B981'],
            'confirmado'  => ['bg' => '#D1FAE5', 'color' => '#059669', 'dot' => '#10B981'],
            'pendiente'   => ['bg' => '#FEF3C7', 'color' => '#D97706', 'dot' => '#F59E0B'],
            'cancelada'   => ['bg' => '#FEE2E2', 'color' => '#DC2626', 'dot' => '#EF4444'],
            'cancelado'   => ['bg' => '#FEE2E2', 'color' => '#DC2626', 'dot' => '#EF4444'],
            'completada'  => ['bg' => '#EDE9FE', 'color' => '#7C3AED', 'dot' => '#8B5CF6'],
            'completado'  => ['bg' => '#EDE9FE', 'color' => '#7C3AED', 'dot' => '#8B5CF6'],
            'no_asistio'  => ['bg' => '#F3F4F6', 'color' => '#6B7280', 'dot' => '#9CA3AF'],
        ];
        $b = $map[$key] ?? ['bg' => '#F3F4F6', 'color' => '#6B7280', 'dot' => '#9CA3AF'];
        $b['label'] = ucfirst(str_replace('_', ' ', $estado));
        return $b;
    }
}
