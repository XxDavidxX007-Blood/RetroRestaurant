<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador', 2, '2', 'empleado'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$titulo  = "PEDIDOS";

require_once __DIR__ . '/../../Controllers/PedidoController.php';

$controller = new PedidoController();
$controller->manejarPeticion();
$datos = $controller->obtenerDatosVista();
extract($datos);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Paleta de colores para KPI cards
$kpiPaleta = [
    ['bg' => '#EFF6FF', 'icon_bg' => '#DBEAFE', 'icon_color' => '#2563EB', 'icon' => 'fa-clipboard-list'],
    ['bg' => '#FFFBEB', 'icon_bg' => '#FEF3C7', 'icon_color' => '#D97706', 'icon' => 'fa-hourglass-half'],
    ['bg' => '#EFF6FF', 'icon_bg' => '#DBEAFE', 'icon_color' => '#2563EB', 'icon' => 'fa-fire-burner'],
    ['bg' => '#F0FDF4', 'icon_bg' => '#D1FAE5', 'icon_color' => '#059669', 'icon' => 'fa-check-circle'],
    ['bg' => '#FEF2F2', 'icon_bg' => '#FEE2E2', 'icon_color' => '#DC2626', 'icon' => 'fa-ban'],
];
$kpiIdx = 0;
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
                <i class="fas fa-receipt text-retro-red"></i> Pedidos
            </h1>
            <p class="text-gray-500 font-body text-sm mt-1">Gestiona y supervisa todos los pedidos del restaurante</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2">
        <i class="fas fa-check-circle"></i>
        <span class="font-body text-sm">Estado del pedido actualizado correctamente.</span>
    </div>
    <?php endif; ?>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">

        <!-- Pedidos hoy -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                 style="background:#DBEAFE; color:#2563EB;">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-body">Pedidos hoy</p>
                <p class="text-2xl font-heading font-bold text-gray-800"><?= $pedidosHoy ?></p>
                <p class="text-xs <?= $varPedidos >= 0 ? 'text-green-600' : 'text-red-500' ?> font-body">
                    <?= $varPedidos >= 0 ? '+' : '' ?><?= $varPedidos ?>% que ayer
                </p>
            </div>
        </div>

        <?php
        $kpiIconos = [
            'fa-hourglass-half', 'fa-fire-burner', 'fa-check-circle', 'fa-ban'
        ];
        $kpiBgs = [
            ['bg' => '#FEF3C7', 'color' => '#D97706'],
            ['bg' => '#DBEAFE', 'color' => '#2563EB'],
            ['bg' => '#D1FAE5', 'color' => '#059669'],
            ['bg' => '#FEE2E2', 'color' => '#DC2626'],
        ];
        $kpiLabels = [
            'En preparación', 'Listos para entregar', 'Completados hoy', 'Cancelados hoy'
        ];
        $kpiSubtitles = [
            'Pedidos activos', 'Listos para servir', 'Pedidos entregados', 'Pedidos cancelados'
        ];
        $i = 0;
        foreach ($kpiEstados as $nombre => $conteo):
            if ($i >= 4) break;
            $bg    = $kpiBgs[$i]['bg'];
            $color = $kpiBgs[$i]['color'];
            $icon  = $kpiIconos[$i];
            $label = $kpiLabels[$i];
            $sub   = $kpiSubtitles[$i];
            $i++;
        ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                 style="background:<?= $bg ?>; color:<?= $color ?>;">
                <i class="fas <?= $icon ?>"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-body"><?= htmlspecialchars($label) ?></p>
                <p class="text-2xl font-heading font-bold text-gray-800"><?= $conteo ?></p>
                <p class="text-xs text-gray-400 font-body"><?= $sub ?></p>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- FILTROS + TABLA -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        <!-- Tabs de estado + filtros de fecha -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 pt-5 pb-0 border-b border-gray-100">

            <!-- Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto pb-0">
                <?php
                $tabEstados = array_merge([['nombre_estado' => 'todos']], $estados);
                foreach ($tabEstados as $tab):
                    $nombre = $tab['nombre_estado'];
                    $label  = $nombre === 'todos' ? 'Todos' : ucfirst(str_replace('_', ' ', $nombre));
                    $active = ($filtro_estado === $nombre);
                    $b      = PedidoController::badgeEstado($nombre);
                    $activeStyle = $active
                        ? "border-b-2 font-bold text-retro-red border-retro-red"
                        : "text-gray-500 hover:text-gray-700 border-b-2 border-transparent";
                ?>
                <a href="?estado=<?= urlencode($nombre) ?><?= $filtro_fecha ? '&fecha='.urlencode($filtro_fecha) : '' ?>"
                   class="px-4 py-3 text-sm font-body whitespace-nowrap transition <?= $activeStyle ?>">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Fecha + filtro -->
            <div class="flex items-center gap-2 pb-3">
                <div class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-600 bg-gray-50">
                    <i class="fas fa-calendar text-gray-400"></i>
                    <input type="date" id="filtroFecha" value="<?= htmlspecialchars($filtro_fecha) ?>"
                           class="bg-transparent outline-none text-sm font-body"
                           onchange="aplicarFecha(this.value)">
                </div>
                <?php if ($filtro_fecha): ?>
                <a href="?estado=<?= urlencode($filtro_estado) ?>"
                   class="text-xs text-gray-400 hover:text-red-500 transition px-2 py-2 rounded-lg hover:bg-red-50"
                   title="Limpiar fecha">
                    <i class="fas fa-times"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Pedido</th>
                        <th class="px-6 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Mesa / Delivery</th>
                        <th class="px-6 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 font-body">

                    <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <i class="fas fa-receipt text-5xl"></i>
                                <p class="text-lg font-heading">No hay pedidos</p>
                                <p class="text-sm">No se encontraron pedidos con los filtros seleccionados.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($pedidos as $p):
                        $b = PedidoController::badgeEstado($p['estado']);
                        $num = str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT);
                        $esDelivery = stripos($p['tipo'], 'domicilio') !== false
                                   || stripos($p['tipo'], 'delivery')  !== false;
                    ?>
                    <tr class="hover:bg-gray-50 transition">

                        <!-- Pedido -->
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-800 text-sm">#ORD-<?= $num ?></p>
                            <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($p['fecha_pedido'])) ?></p>
                        </td>

                        <!-- Cliente -->
                        <td class="px-6 py-4">
                            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($p['nombre_cliente']) ?></p>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($p['telefono_cliente']) ?></p>
                        </td>

                        <!-- Mesa / Delivery -->
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-700"><?= htmlspecialchars($p['tipo']) ?></p>
                            <?php if ($esDelivery): ?>
                            <p class="text-xs font-semibold text-retro-red">Domicilio</p>
                            <?php else: ?>
                            <p class="text-xs text-gray-400">Salón</p>
                            <?php endif; ?>
                        </td>

                        <!-- Estado -->
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                                  style="background:<?= $b['bg'] ?>; color:<?= $b['color'] ?>;">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background:<?= $b['dot'] ?>;"></span>
                                <?= $b['label'] ?>
                            </span>
                        </td>

                        <!-- Total -->
                        <td class="px-6 py-4">
                            <span class="font-bold text-green-600 text-sm">
                                $<?= number_format($p['total'], 0, ',', '.') ?>
                            </span>
                        </td>

                        <!-- Fecha -->
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600"><?= date('d/m/Y', strtotime($p['fecha_pedido'])) ?></p>
                        </td>

                        <!-- Acciones -->
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="abrirDetalle(<?= $p['id_pedido'] ?>)"
                                        class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-blue-100 text-gray-500 hover:text-blue-600 transition flex items-center justify-center"
                                        title="Ver detalle">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                                <button onclick="abrirCambiarEstado(<?= $p['id_pedido'] ?>, '<?= htmlspecialchars($p['estado']) ?>')"
                                        class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-orange-100 text-gray-500 hover:text-orange-600 transition flex items-center justify-center"
                                        title="Cambiar estado">
                                    <i class="fas fa-ellipsis-vertical text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-3 border-t border-gray-100 bg-gray-50">
            <p class="text-sm text-gray-500 font-body">
                Mostrando <?= min(($pagina - 1) * $por_pagina + 1, $total) ?>
                a <?= min($pagina * $por_pagina, $total) ?>
                de <?= $total ?> pedidos
            </p>
            <div class="flex items-center gap-1">
                <?php if ($pagina > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>"
                   class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition font-body">
                    Anterior
                </a>
                <?php endif; ?>

                <?php for ($p2 = max(1, $pagina - 2); $p2 <= min($totalPaginas, $pagina + 2); $p2++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $p2])) ?>"
                   class="w-9 h-9 flex items-center justify-center text-sm rounded-lg border transition font-body
                          <?= $p2 === $pagina
                              ? 'bg-retro-red text-white border-retro-red font-bold'
                              : 'border-gray-200 hover:bg-white text-gray-600' ?>">
                    <?= $p2 ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagina < $totalPaginas): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>"
                   class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition font-body">
                    Siguiente
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- ── MODAL DETALLE PEDIDO ─────────────────────────────────────── -->
<div id="modalDetalle" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-xl font-heading font-bold text-gray-800" id="detalleTitulo">Detalle del Pedido</h3>
            <button onclick="cerrarModal('modalDetalle')" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6" id="detalleContenido">
            <div class="flex items-center justify-center py-12 text-gray-400">
                <i class="fas fa-spinner fa-spin text-3xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL CAMBIAR ESTADO ────────────────────────────────────── -->
<div id="modalEstado" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-heading font-bold text-gray-800">Cambiar Estado</h3>
            <button onclick="cerrarModal('modalEstado')" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="admin_pedidos.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="accion" value="cambiar_estado">
            <input type="hidden" name="id_pedido" id="estado_id_pedido">

            <p class="text-sm text-gray-500 font-body">Selecciona el nuevo estado para el pedido <strong id="estado_num_pedido"></strong>.</p>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nuevo estado</label>
                <select name="id_estado_pedido" id="estado_select"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red bg-white font-body text-sm">
                    <?php foreach ($estados as $e): ?>
                    <option value="<?= $e['id_estado_pedido'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $e['nombre_estado'])) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="cerrarModal('modalEstado')"
                        class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-retro-red hover:bg-red-700 text-white rounded-xl font-heading text-sm shadow transition">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Helpers ──────────────────────────────────────────────────────
function cerrarModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function aplicarFecha(val) {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set('fecha', val);
    } else {
        url.searchParams.delete('fecha');
    }
    url.searchParams.set('pagina', 1);
    window.location.href = url.toString();
}

// ── Cambiar estado ────────────────────────────────────────────────
function abrirCambiarEstado(id, estadoActual) {
    document.getElementById('estado_id_pedido').value = id;
    document.getElementById('estado_num_pedido').textContent = '#ORD-' + String(id).padStart(5, '0');

    // Seleccionar el estado actual en el select
    const sel = document.getElementById('estado_select');
    for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].text.toLowerCase().replace(/ /g,'_') === estadoActual.toLowerCase()) {
            sel.selectedIndex = i;
            break;
        }
    }

    document.getElementById('modalEstado').classList.remove('hidden');
}

// ── Ver detalle ───────────────────────────────────────────────────
function abrirDetalle(id) {
    const modal    = document.getElementById('modalDetalle');
    const titulo   = document.getElementById('detalleTitulo');
    const contenido = document.getElementById('detalleContenido');

    titulo.textContent = '#ORD-' + String(id).padStart(5, '0');
    contenido.innerHTML = '<div class="flex items-center justify-center py-12 text-gray-400"><i class="fas fa-spinner fa-spin text-3xl"></i></div>';
    modal.classList.remove('hidden');

    fetch('admin_pedidos_detalle.php?id=' + id)
        .then(r => r.text())
        .then(html => { contenido.innerHTML = html; })
        .catch(() => {
            contenido.innerHTML = '<p class="text-center text-red-500 py-8">Error al cargar el detalle.</p>';
        });
}

// Cerrar modales al hacer click fuera
document.addEventListener('click', function(e) {
    ['modalDetalle', 'modalEstado'].forEach(id => {
        const modal = document.getElementById(id);
        if (e.target === modal) modal.classList.add('hidden');
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
