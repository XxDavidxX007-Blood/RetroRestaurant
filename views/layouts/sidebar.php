<?php
$rol_id         = $usuario['id_rol'];
$rol_nombre     = in_array($rol_id, ['1',1,'administrador']) ? 'Administrador' : (in_array($rol_id, ['2',2,'empleado']) ? 'Empleado' : 'Cliente');
$nombreCompleto = $usuario['nombre'] . ' ' . $usuario['apellidos'];

// ── Notificaciones ──────────────────────────────────────────
$notifCount = 0;
$notifItems = [];
if (!class_exists('CompraController')) {
    @require_once __DIR__ . '/../../Controllers/CompraController.php';
}
if (class_exists('CompraController')) {
    if (in_array($rol_id, ['3',3,'cliente'])) {
        CompraController::notificarPromocionesCliente($usuario['id_usuario']);
    }
    // Marcar como leídas automáticamente si el usuario está en la página de notificaciones
    $paginaActualSidebar = basename($_SERVER['PHP_SELF']);
    $paginasNotif = ['admin_notificaciones.php', 'cliente_notificaciones.php'];
    if (in_array($paginaActualSidebar, $paginasNotif)) {
        CompraController::marcarLeidas($usuario['id_usuario']);
    }
    $notifCount = CompraController::getNoLeidas($usuario['id_usuario']);
    $notifItems = CompraController::getNotificaciones($usuario['id_usuario'], 8);
}

$notifIconos = [
    'pago'      => ['i' => '🎉', 'bg' => '#D1FAE5'],
    'pedido'    => ['i' => '🛍️', 'bg' => '#DBEAFE'],
    'promocion' => ['i' => '🔥', 'bg' => '#FEF3C7'],
    'combo'     => ['i' => '🍱', 'bg' => '#EDE9FE'],
];

if (!function_exists('notifTiempoRelativo')) {
    function notifTiempoRelativo($fecha) {
        $diff = time() - strtotime($fecha);
        if ($diff < 60)    return 'Hace '.$diff.'s';
        if ($diff < 3600)  return 'Hace '.floor($diff/60).'min';
        if ($diff < 86400) return 'Hace '.floor($diff/3600).'h';
        return 'Hace '.floor($diff/86400).'d';
    }
}

$verTodasUrl = in_array($rol_id, ['3',3,'cliente']) ? 'cliente_notificaciones.php' : 'admin_notificaciones.php';

function notifUrl($tipo, $rol_id) {
    $esCliente = in_array($rol_id, ['3',3,'cliente']);
    $map = [
        'pago'      => $esCliente ? 'cliente_pedidos.php'    : 'admin_pedidos.php',
        'pedido'    => $esCliente ? 'cliente_pedidos.php'    : 'admin_pedidos.php',
        'promocion' => $esCliente ? 'cliente_catalogo.php'   : 'admin_gestion_de_menu.php',
        'combo'     => $esCliente ? 'cliente_catalogo.php'   : 'admin_gestion_de_menu.php',
        'reserva'   => $esCliente ? 'cliente_reservas.php'   : 'admin_reservas.php',
        'domicilio' => $esCliente ? 'cliente_domicilios.php' : 'admin_domicilios.php',
    ];
    return $map[$tipo] ?? ($esCliente ? 'cliente_notificaciones.php' : 'admin_notificaciones.php');
}
?>

<!-- ═══════════════════════════════════════════════════════════
     PANEL DE NOTIFICACIONES — adjunto al body para evitar
     que overflow:hidden del layout lo recorte
════════════════════════════════════════════════════════════ -->
<div id="notif-panel" style="
    display:none;
    position:fixed;
    z-index:2147483647;
    width:400px;
    background:#ffffff;
    border-radius:20px;
    box-shadow:0 32px 80px rgba(0,0,0,0.22), 0 8px 24px rgba(0,0,0,0.10);
    border:1px solid #efefef;
    overflow:hidden;
    font-family:'Montserrat',sans-serif;
">
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f3f4f6;background:#fafafa;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:34px;height:34px;background:#0a0a0a;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-bell" style="color:#c5a059;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-family:'Playfair Display',serif;font-weight:700;font-size:15px;color:#0a0a0a;line-height:1;">Notificaciones</div>
                <?php if ($notifCount > 0): ?>
                <div style="font-size:11px;color:#9ca3af;margin-top:2px;"><?= $notifCount ?> sin leer</div>
                <?php else: ?>
                <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Todo al día</div>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($notifCount > 0): ?>
            <button onclick="marcarTodasLeidas()" style="font-size:11px;color:#9ca3af;background:none;border:none;cursor:pointer;font-family:'Montserrat',sans-serif;padding:0;transition:color .2s;" onmouseover="this.style.color='#c5a059'" onmouseout="this.style.color='#9ca3af'">
                Marcar leídas
            </button>
            <?php endif; ?>
            <button onclick="cerrarNotif()" style="width:28px;height:28px;border-radius:8px;background:#f3f4f6;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6b7280;transition:background .2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                <i class="fas fa-times" style="font-size:11px;"></i>
            </button>
        </div>
    </div>

    <!-- Lista -->
    <div style="max-height:420px;overflow-y:auto;">
        <?php if (empty($notifItems)): ?>
        <div style="padding:56px 20px;text-align:center;">
            <div style="width:64px;height:64px;background:#f9fafb;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-bell-slash" style="font-size:24px;color:#d1d5db;"></i>
            </div>
            <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 6px;">Sin notificaciones</p>
            <p style="font-size:12px;color:#9ca3af;margin:0;">Aquí aparecerán tus alertas y novedades</p>
        </div>
        <?php else: ?>
        <?php foreach ($notifItems as $n):
            $estilo   = $notifIconos[$n['tipo']] ?? ['i'=>'🔔','bg'=>'#F3F4F6'];
            $opacidad = $n['leida'] ? '0.45' : '1';
            $destino  = notifUrl($n['tipo'], $rol_id);
        ?>
        <a href="<?= $destino ?>" class="notif-item" style="display:flex;align-items:flex-start;gap:14px;padding:16px 22px;border-bottom:1px solid #f9fafb;cursor:pointer;transition:background .15s;opacity:<?= $opacidad ?>;text-decoration:none;" onmouseover="this.style.background='#fafafa';this.style.opacity='1'" onmouseout="this.style.background='transparent';this.style.opacity='<?= $opacidad ?>'">
            <div style="width:44px;height:44px;border-radius:14px;background:<?= $estilo['bg'] ?>;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                <?= $estilo['i'] ?>
            </div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 4px;line-height:1.3;"><?= htmlspecialchars($n['titulo']) ?></p>
                <p style="font-size:12px;color:#6b7280;margin:0 0 6px;line-height:1.5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($n['mensaje']) ?></p>
                <span style="font-size:11px;color:#c5a059;font-weight:600;"><?= notifTiempoRelativo($n['created_at']) ?></span>
            </div>
            <?php if (!$n['leida']): ?>
            <div style="width:9px;height:9px;border-radius:50%;background:#c5a059;flex-shrink:0;margin-top:5px;box-shadow:0 0 0 3px rgba(197,160,89,.2);"></div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div style="padding:14px 22px;border-top:1px solid #f3f4f6;background:#fafafa;text-align:center;">
        <a href="<?= $verTodasUrl ?>" style="font-size:12px;font-weight:700;color:#0a0a0a;text-decoration:none;letter-spacing:.06em;text-transform:uppercase;transition:color .2s;" onmouseover="this.style.color='#c5a059'" onmouseout="this.style.color='#0a0a0a'">
            Ver todas las notificaciones &rarr;
        </a>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════════════════════ -->
<aside id="sidebar" class="sidebar-panel w-64 bg-retro-dark text-white shadow-2xl flex flex-col justify-between border-r border-gray-800 relative z-20 transition-all duration-300 ease-in-out">

    <!-- Botón toggle — visible siempre en el borde derecho del sidebar -->
    <button id="sidebar-toggle"
            onclick="toggleSidebar()"
            title="Colapsar menú"
            class="absolute -right-3.5 top-8 z-30 w-7 h-7 rounded-full bg-retro-gold text-retro-dark flex items-center justify-center shadow-lg hover:scale-110 transition-transform duration-200 focus:outline-none">
        <i id="sidebar-toggle-icon" class="fas fa-chevron-left text-xs"></i>
    </button>

    <div>
        <!-- Logo -->
        <div class="h-24 flex items-center justify-center border-b border-gray-800 px-4 overflow-hidden">
            <div class="flex items-center gap-3">
                <i class="fas fa-wine-glass text-retro-gold text-3xl flex-shrink-0"></i>
                <div id="sidebar-label" class="font-heading text-lg tracking-[0.2em] uppercase text-retro-gold mt-1 whitespace-nowrap transition-all duration-300">Retro Menú</div>
            </div>
        </div>

        <nav class="mt-6 px-3 space-y-2">
            <?php
                if (in_array($rol_id, ['1',1,'administrador'])) {
                    $dashboardHref = 'admin_dashboard.php';
                } elseif (in_array($rol_id, ['2',2,'empleado'])) {
                    $dashboardHref = 'empleado.php';
                } else {
                    $dashboardHref = 'cliente.php';
                }
                $currentPage = basename($_SERVER['PHP_SELF']);
            ?>
            <a href="<?= $dashboardHref ?>" title="Dashboard" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= in_array($currentPage, ['admin_dashboard.php','empleado.php','cliente.php']) ? 'bg-retro-gold text-retro-dark' : 'hover:text-retro-gold text-gray-300' ?> transition shadow-sm">
                <i class="fas fa-gauge-high flex-shrink-0"></i>
                <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">DASHBOARD</span>
            </a>

            <?php if (in_array($rol_id, ['1',1,'administrador'])): ?>
                <a href="admin.php" title="Gestión de Usuarios" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-users flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">GESTIÓN DE USUARIOS</span>
                </a>
                <a href="admin_gestion_de_inventario.php" title="Inventario" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_gestion_de_inventario.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-boxes-stacked flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">INVENTARIO</span>
                </a>
                <a href="admin_gestion_de_menu.php" title="Menú" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_gestion_de_menu.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-utensils flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">MENÚ</span>
                </a>
                <a href="admin_pedidos.php" title="Pedidos" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_pedidos.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-receipt flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">PEDIDOS</span>
                </a>
                <a href="admin_reportes.php" title="Reportes" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_reportes.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-chart-pie flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">REPORTES</span>
                </a>
                <a href="admin_reservas.php" title="Reservas" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_reservas.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-calendar-check flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">RESERVAS</span>
                </a>
                <a href="admin_domicilios.php" title="Domicilios" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_domicilios.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-motorcycle flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">DOMICILIOS</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($rol_id, ['2',2,'empleado'])): ?>
                <a href="cliente_catalogo.php" title="Catálogo" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='cliente_catalogo.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-store flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">CATÁLOGO</span>
                </a>
                <a href="admin_reservas.php" title="Reservas" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_reservas.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-calendar-check flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">RESERVAS</span>
                </a>
                <a href="admin_pedidos.php" title="Pedidos" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_pedidos.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-receipt flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">PEDIDOS</span>
                </a>
                <a href="admin_domicilios.php" title="Domicilios" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='admin_domicilios.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-motorcycle flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">DOMICILIOS</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($rol_id, ['3',3,'cliente'])): ?>
                <a href="cliente_catalogo.php" title="Catálogo" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='cliente_catalogo.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-store flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">CATÁLOGO</span>
                </a>
                <a href="cliente_reservas.php" title="Mis Reservas" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='cliente_reservas.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-calendar-check flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">MIS RESERVAS</span>
                </a>
                <a href="cliente_pedidos.php" title="Mis Pedidos" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='cliente_pedidos.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-receipt flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">MIS PEDIDOS</span>
                </a>
                <a href="cliente_domicilios.php" title="Domicilios" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='cliente_domicilios.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-motorcycle flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">DOMICILIOS</span>
                </a>
                <a href="perfil.php" title="Perfil" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg <?= $currentPage==='perfil.php'?'bg-retro-gold text-retro-dark':'hover:text-retro-gold text-gray-300' ?> transition group">
                    <i class="fas fa-user flex-shrink-0 group-hover:scale-110 transition"></i>
                    <span class="sidebar-text font-heading tracking-widest text-xs uppercase whitespace-nowrap overflow-hidden transition-all duration-300">PERFIL</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="p-4 border-t border-gray-800">
        <a href="../../Controllers/AuthController.php?accion=logout" title="Cerrar sesión"
           class="sidebar-link flex items-center justify-center gap-3 px-4 py-3 rounded-lg hover:text-retro-gold text-gray-500 transition text-xs uppercase tracking-[0.2em]">
            <i class="fas fa-right-from-bracket flex-shrink-0"></i>
            <span class="sidebar-text whitespace-nowrap overflow-hidden transition-all duration-300">CERRAR SESIÓN</span>
        </a>
    </div>
</aside>

<style>
/* ── Estado colapsado del sidebar ── */
#sidebar.sidebar-collapsed {
    width: 4.5rem; /* 72px — solo íconos */
}
#sidebar.sidebar-collapsed .sidebar-text,
#sidebar.sidebar-collapsed #sidebar-label {
    width: 0;
    opacity: 0;
    pointer-events: none;
    margin: 0;
}
#sidebar.sidebar-collapsed .sidebar-link {
    justify-content: center;
    padding-left: 0;
    padding-right: 0;
}
#sidebar.sidebar-collapsed .sidebar-link gap-3 {
    gap: 0;
}
/* Tooltip en modo colapsado */
#sidebar.sidebar-collapsed .sidebar-link {
    position: relative;
}
#sidebar.sidebar-collapsed .sidebar-link::after {
    content: attr(title);
    position: absolute;
    left: calc(100% + 12px);
    top: 50%;
    transform: translateY(-50%);
    background: #1a1a1a;
    color: #c5a059;
    font-family: 'Playfair Display', serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 6px 12px;
    border-radius: 8px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.18s ease;
    z-index: 9999;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
#sidebar.sidebar-collapsed .sidebar-link:hover::after {
    opacity: 1;
}
</style>

<!-- ═══════════════════════════════════════════════════════════
     MAIN + HEADER
════════════════════════════════════════════════════════════ -->
<main class="flex-1 flex flex-col h-screen overflow-hidden main-overlay relative z-10">
    <header class="h-24 bg-white/80 backdrop-blur-md px-8 flex items-center justify-between shadow-sm border-b border-gray-100 sticky top-0 z-10">
        <h1 class="text-2xl font-heading font-bold text-retro-dark flex items-center gap-3">
            <div class="w-1 h-6 bg-retro-gold"></div>
            <?= htmlspecialchars($titulo) ?>
        </h1>

        <div class="flex items-center gap-3">
            <!-- Botón campana -->
            <div class="relative">
                <button id="notif-btn"
                        onclick="toggleNotif(event)"
                        title="Notificaciones"
                        class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition text-gray-600">
                    <i class="fas fa-bell text-sm"></i>
                </button>
                <?php if ($notifCount > 0): ?>
                <span id="notif-badge" class="absolute -top-1 -right-1 w-5 h-5 bg-retro-dark text-retro-gold text-xs font-bold rounded-full flex items-center justify-center pointer-events-none">
                    <?= $notifCount > 9 ? '9+' : $notifCount ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Info usuario -->
            <div class="flex items-center gap-4 px-4 py-2 hover:bg-gray-50 rounded-xl transition cursor-pointer"
                 onclick="window.location.href='perfil.php'" title="Ver mi perfil">
                <div class="text-right hidden md:block">
                    <div class="font-heading font-bold text-retro-dark tracking-widest text-xs uppercase"><?= htmlspecialchars(strtoupper($rol_nombre)) ?></div>
                    <div class="text-xs text-gray-500 font-body"><?= htmlspecialchars($nombreCompleto) ?></div>
                </div>
                <?php if (!empty($usuario['foto'])): ?>
                <img src="../../img/perfiles/<?= htmlspecialchars($usuario['foto']) ?>"
                     class="w-10 h-10 rounded-full object-cover shadow-sm border border-gray-200">
                <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-retro-dark text-retro-gold flex items-center justify-center text-lg shadow-sm font-heading font-bold">
                    <?= strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-8 relative">
        <div class="relative z-10 max-w-7xl mx-auto">

<script>
// ── Notificaciones dropdown ─────────────────────────────────
const notifPanel = document.getElementById('notif-panel');
const notifBtn   = document.getElementById('notif-btn');

// Mover el panel al body para que no quede atrapado en ningún contexto de apilamiento
document.body.appendChild(notifPanel);

function posicionarPanel() {
    const rect = notifBtn.getBoundingClientRect();
    notifPanel.style.top  = (rect.bottom + window.scrollY + 10) + 'px';
    notifPanel.style.left = Math.max(8, rect.right - 400) + 'px';
}

function toggleNotif(e) {
    e.stopPropagation();
    if (notifPanel.style.display === 'none' || notifPanel.style.display === '') {
        posicionarPanel();
        notifPanel.style.display = 'block';
        // Animación de entrada
        notifPanel.style.opacity = '0';
        notifPanel.style.transform = 'translateY(-8px)';
        notifPanel.style.transition = 'opacity .2s ease, transform .2s ease';
        requestAnimationFrame(() => {
            notifPanel.style.opacity = '1';
            notifPanel.style.transform = 'translateY(0)';
        });
        // Marcar como leídas al abrir el panel
        marcarTodasLeidas();
    } else {
        cerrarNotif();
    }
}

function cerrarNotif() {
    notifPanel.style.opacity = '0';
    notifPanel.style.transform = 'translateY(-8px)';
    setTimeout(() => { notifPanel.style.display = 'none'; }, 180);
}

document.addEventListener('click', function(e) {
    if (notifPanel.style.display !== 'none' &&
        !notifPanel.contains(e.target) &&
        e.target !== notifBtn &&
        !notifBtn.contains(e.target)) {
        cerrarNotif();
    }
});

window.addEventListener('resize', function() {
    if (notifPanel.style.display !== 'none') posicionarPanel();
});

function marcarTodasLeidas() {
    fetch('../../Controllers/NotifController.php?accion=marcar', { method: 'POST' })
        .then(() => {
            // Quitar badge del contador
            const badge = document.getElementById('notif-badge');
            if (badge) badge.remove();
            // Opacidad a todos los items
            document.querySelectorAll('.notif-item').forEach(el => {
                el.style.opacity = '0.45';
                el.setAttribute('onmouseout', `this.style.background='transparent';this.style.opacity='0.45'`);
            });
            // Quitar puntos dorados de "no leída"
            document.querySelectorAll('.notif-item > div:last-child[style*="background:#c5a059"]').forEach(d => d.remove());
            // Actualizar texto del header
            const subHeader = document.querySelector('#notif-panel div[style*="sin leer"]');
            if (subHeader) subHeader.textContent = 'Todo al día';
            // Quitar el botón "Marcar leídas"
            const btn = document.querySelector('[onclick="marcarTodasLeidas()"]');
            if (btn) btn.remove();
        });
}

// ── Sidebar colapsar / expandir ─────────────────────────────
(function initSidebar() {
    const sidebar     = document.getElementById('sidebar');
    const icon        = document.getElementById('sidebar-toggle-icon');
    const collapsed   = localStorage.getItem('sidebar_collapsed') === 'true';

    if (collapsed) {
        sidebar.classList.add('sidebar-collapsed');
        icon.classList.replace('fa-chevron-left', 'fa-chevron-right');
    }
})();

function toggleSidebar() {
    const sidebar   = document.getElementById('sidebar');
    const icon      = document.getElementById('sidebar-toggle-icon');
    const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');

    if (isCollapsed) {
        icon.classList.replace('fa-chevron-left', 'fa-chevron-right');
    } else {
        icon.classList.replace('fa-chevron-right', 'fa-chevron-left');
    }

    localStorage.setItem('sidebar_collapsed', isCollapsed);
}
</script>
