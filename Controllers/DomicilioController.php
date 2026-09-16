<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Domicilio.php';

class DomicilioController {
    private $model;
    private $dbError = null;

    public function __construct() {
        try {
            $db = new Database();
            $this->model = new Domicilio($db->conectar());
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
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear') {
            $datos = [
                'id_cliente'        => (int)($_POST['id_cliente'] ?? 0) ?: null,
                'id_mesero'         => (int)($_POST['id_mesero']  ?? 0),
                'fecha_pedido'      => $_POST['fecha_pedido']     ?? date('Y-m-d'),
                'id_estado_pedido'  => (int)($_POST['id_estado_pedido'] ?? 1),
                'direccion_entrega' => isset($_POST['direccion_entrega']) ? trim($_POST['direccion_entrega']) : null,
            ];
            $r = $this->model->crear($datos);
            if ($r === true) header("Location: " . $this->baseUrl() . "/admin_domicilios.php?success=creado");
            else             header("Location: " . $this->baseUrl() . "/admin_domicilios.php?error=" . urlencode($r));
            exit;
        }

        if ($accion === 'cambiar_estado') {
            $id  = (int)($_POST['id_pedido'] ?? 0);
            $est = (int)($_POST['id_estado_pedido'] ?? 0);
            if ($id && $est) $this->model->cambiarEstado($id, $est);
            header("Location: " . $this->baseUrl() . "/admin_domicilios.php?success=estado"); exit;
        }

        if ($accion === 'eliminar') {
            $id = (int)($_POST['id_pedido'] ?? 0);
            if ($id) {
                $resultado = $this->model->eliminar($id);
                if ($resultado === true) {
                    header("Location: " . $this->baseUrl() . "/admin_domicilios.php?success=eliminado");
                } else {
                    header("Location: " . $this->baseUrl() . "/admin_domicilios.php?error=" . urlencode($resultado));
                }
            } else {
                header("Location: " . $this->baseUrl() . "/admin_domicilios.php?error=ID+requerido");
            }
            exit;
        }
    }

    public function obtenerDatosVista() {
        if ($this->dbError) {
            return ['domicilios'=>[],'estados'=>[],'clientes'=>[],'meseros'=>[],'kpiEstados'=>[],'hoy'=>0,'var'=>0,'filtro_estado'=>'todos','filtro_fecha'=>'','pagina'=>1,'totalPaginas'=>1,'total'=>0,'por_pagina'=>10];
        }
        $filtro_estado = $_GET['estado'] ?? 'todos';
        $filtro_fecha  = $_GET['fecha']  ?? '';
        $pagina        = max(1, (int)($_GET['pagina'] ?? 1));
        $por_pagina    = 10;

        $hoy  = $this->model->getDomiciliosHoy();
        $ayer = $this->model->getDomiciliosAyer();
        $var  = $ayer > 0 ? round((($hoy - $ayer) / $ayer) * 100, 1) : ($hoy > 0 ? 100 : 0);

        $estados    = $this->model->getEstados();
        $clientes   = $this->model->getClientes();
        $meseros    = $this->model->getMeseros();
        $kpiEstados = [];
        foreach ($estados as $e) $kpiEstados[$e['nombre_estado']] = $this->model->getConteoByEstado($e['nombre_estado']);

        $total        = $this->model->getTotalCount($filtro_estado, $filtro_fecha);
        $domicilios   = $this->model->getAll($filtro_estado, $filtro_fecha, $pagina, $por_pagina);
        $totalPaginas = max(1, ceil($total / $por_pagina));

        return compact('domicilios','estados','clientes','meseros','kpiEstados','hoy','var','filtro_estado','filtro_fecha','pagina','totalPaginas','total','por_pagina');
    }

    public static function badgeEstado($estado) {
        $key = strtolower(str_replace(' ', '_', $estado));
        $map = [
            'pendiente'      => ['bg'=>'#FEF3C7','color'=>'#D97706','dot'=>'#F59E0B'],
            'en_preparacion' => ['bg'=>'#DBEAFE','color'=>'#2563EB','dot'=>'#3B82F6'],
            'listo'          => ['bg'=>'#D1FAE5','color'=>'#059669','dot'=>'#10B981'],
            'entregado'      => ['bg'=>'#EDE9FE','color'=>'#7C3AED','dot'=>'#8B5CF6'],
            'completado'     => ['bg'=>'#EDE9FE','color'=>'#7C3AED','dot'=>'#8B5CF6'],
            'cancelado'      => ['bg'=>'#FEE2E2','color'=>'#DC2626','dot'=>'#EF4444'],
        ];
        $b = $map[$key] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','dot'=>'#9CA3AF'];
        $b['label'] = ucfirst(str_replace('_', ' ', $estado));
        return $b;
    }
}
