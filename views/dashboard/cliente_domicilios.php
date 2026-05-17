<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [3,'3','cliente'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}
$usuario = $_SESSION['usuario'];
$titulo  = "DOMICILIOS";

require_once __DIR__ . '/../../config/database.php';
$db = (new database())->conectar();

// Obtener id_cliente del usuario en sesión
$stmtCli = $db->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = :id LIMIT 1");
$stmtCli->execute([':id' => $usuario['id_usuario']]);
$clienteRow = $stmtCli->fetch(PDO::FETCH_ASSOC);
$id_cliente = $clienteRow['id_cliente'] ?? null;

// Filtro de estado
$filtroEstado = $_GET['estado'] ?? 'todos';

// Obtener pedidos a domicilio del cliente
$pedidos = [];
if ($id_cliente) {
    $where = "p.id_cliente = :id_cliente AND (tp.nombre_tipo LIKE '%domicilio%' OR tp.nombre_tipo LIKE '%delivery%')";
    $params = [':id_cliente' => $id_cliente];

    if ($filtroEstado !== 'todos') {
        $where .= " AND ep.nombre_estado = :estado";
        $params[':estado'] = $filtroEstado;
    }

    $stmtP = $db->prepare("
        SELECT p.id_pedido, p.fecha_pedido,
               ep.nombre_estado AS estado,
               IFNULL(f.total_factura, 0) AS total,
               COUNT(dp.id_detalle) AS num_productos
        FROM pedido p
        JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
        JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
        LEFT JOIN factura  f  ON f.id_pedido        = p.id_pedido
        LEFT JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
        WHERE {$where}
        GROUP BY p.id_pedido
        ORDER BY p.id_pedido DESC
    ");
    $stmtP->execute($params);
    $pedidos = $stmtP->fetchAll(PDO::FETCH_ASSOC);
}

// Colores por estado
function badgeDomicilio($estado) {
    $map = [
        'pendiente'      => ['bg'=>'#FEF3C7','color'=>'#D97706','icon'=>'fa-hourglass-half','label'=>'Pendiente',    'desc'=>'Esperando confirmación'],
        'en_preparacion' => ['bg'=>'#DBEAFE','color'=>'#2563EB','icon'=>'fa-fire-burner',   'label'=>'Preparando',   'desc'=>'Estamos preparando tu pedido'],
        'listo'          => ['bg'=>'#D1FAE5','color'=>'#059669','icon'=>'fa-motorcycle',    'label'=>'En camino',    'desc'=>'Tu pedido va en camino'],
        'entregado'      => ['bg'=>'#EDE9FE','color'=>'#7C3AED','icon'=>'fa-check-circle',  'label'=>'Entregado',    'desc'=>'Pedido entregado'],
        'completado'     => ['bg'=>'#EDE9FE','color'=>'#7C3AED','icon'=>'fa-check-circle',  'label'=>'Completado',   'desc'=>'Pedido completado'],
        'cancelado'      => ['bg'=>'#FEE2E2','color'=>'#DC2626','icon'=>'fa-ban',           'label'=>'Cancelado',    'desc'=>'Pedido cancelado'],
    ];
    $key = strtolower(str_replace(' ','_',$estado));
    return $map[$key] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','icon'=>'fa-circle','label'=>ucfirst($estado),'desc'=>''];
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
                <i class="fas fa-motorcycle"></i> Mis pedidos a domicilio
            </h1>
            <p class="text-gray-500 font-body text-sm mt-1">Aquí aparecen tus pedidos a domicilio realizados.</p>
        </div>
    </div>

    <!-- Tabs de estado -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center gap-1 px-6 pt-5 border-b border-gray-100 overflow-x-auto">
            <?php
            $tabs = [
                'todos'          => 'Todos',
                'listo'          => 'En camino',
                'entregado'      => 'Entregado',
                'cancelado'      => 'Cancelado',
            ];
            foreach ($tabs as $val => $lbl):
                $activo = ($filtroEstado === $val);
            ?>
            <a href="?estado=<?= $val ?>"
               class="px-4 py-3 text-sm font-body whitespace-nowrap border-b-2 transition
                      <?= $activo ? 'border-retro-dark text-retro-dark font-bold' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
                <?= $lbl ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Lista de pedidos -->
        <div class="divide-y divide-gray-50">
            <?php if (empty($pedidos)): ?>
            <div class="py-20 text-center text-gray-400">
                <i class="fas fa-motorcycle text-5xl mb-4"></i>
                <p class="font-heading text-lg">Sin pedidos a domicilio</p>
                <p class="text-sm mt-1">Cuando realices un pedido a domicilio aparecerá aquí.</p>
                <a href="cliente_catalogo.php"
                   class="inline-block mt-4 px-6 py-2 bg-retro-dark text-white rounded-xl font-heading text-sm transition hover:bg-gray-800">
                    Ver catálogo
                </a>
            </div>
            <?php else: ?>
            <?php foreach ($pedidos as $p):
                $b   = badgeDomicilio($p['estado']);
                $num = str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT);
                $fecha = date('d M, Y · g:i A', strtotime($p['fecha_pedido']));
                $nProd = (int)$p['num_productos'];
            ?>
            <div class="flex flex-col md:flex-row items-start md:items-center gap-4 px-6 py-5 hover:bg-gray-50 transition">

                <!-- Ícono estado -->
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"
                     style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
                    <i class="fas <?= $b['icon'] ?>"></i>
                </div>

                <!-- Info pedido -->
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-800">Pedido #<?= $num ?></p>
                    <p class="text-xs text-gray-500 mt-0.5"><?= $fecha ?></p>
                    <p class="text-xs text-gray-400 mt-0.5"><?= $nProd ?> <?= $nProd === 1 ? 'producto' : 'productos' ?></p>
                </div>

                <!-- Estado -->
                <div class="flex-1 min-w-0">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                          style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
                        <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $b['color'] ?>;"></span>
                        <?= $b['label'] ?>
                    </span>
                    <p class="text-xs text-gray-400 mt-1"><?= $b['desc'] ?></p>
                </div>

                <!-- Total -->
                <div class="text-right flex-shrink-0">
                    <p class="text-xs text-gray-400">Total</p>
                    <p class="font-bold text-lg text-gray-800">$<?= number_format($p['total'], 0, ',', '.') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
