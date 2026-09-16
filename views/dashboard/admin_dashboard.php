<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $base     = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$protocol}://{$host}{$base}/views/usuarios/login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../Controllers/DashboardController.php';

$controller = new DashboardController();
$datos      = $controller->obtenerDatosVista();
extract($datos);

$titulo = "DASHBOARD";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    :root {
        --gold: #c5a059;
        --gold-dark: #b08d4b;
        --bg: transparent;
        --card: #ffffff;
        --text: #0a0a0a;
        --muted: #888888;
        --border: #f3f4f6;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--card);
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: 16px;
        border: 1px solid var(--border);
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .stat-label { font-size: 11.5px; color: var(--muted); font-weight: 500; margin-bottom: 2px; }
    .stat-value { font-size: 20px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .stat-delta { font-size: 11px; margin-top: 3px; }
    .delta-up   { color: #10B981; }
    .delta-down { color: #EF4444; }

    .mid-row {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 16px;
        margin-bottom: 24px;
    }

    .dash-card {
        background: var(--card);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--shadow);
        border: 2px solid var(--border);
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .card-title  { font-family: "Playfair Display", serif; font-size: 16px; font-weight: 700; color: var(--text); }
    .card-link   { font-size: 11px; color: var(--text); font-weight: 600; text-decoration: none; text-transform: uppercase; letter-spacing: 0.1em; border-bottom: 1px solid var(--text); padding-bottom: 2px; }
    .card-link:hover { color: var(--gold); border-color: var(--gold); }
    .chart-wrap  { position: relative; height: 190px; }

    .donut-legend { margin-top: 14px; display: flex; flex-direction: column; gap: 6px; }
    .legend-item  { display: flex; align-items: center; justify-content: space-between; font-size: 12px; }
    .legend-dot   { width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; flex-shrink: 0; }
    .legend-left  { display: flex; align-items: center; color: var(--muted); }
    .legend-right { font-weight: 600; font-size: 12px; }

    .activity-list { display: flex; flex-direction: column; gap: 12px; }
    .activity-item { display: flex; align-items: flex-start; gap: 10px; }
    .act-icon  { width: 32px; height: 32px; border-radius: 8px; background: #FEF3C7; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
    .act-body  { flex: 1; min-width: 0; }
    .act-title { font-size: 12.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .act-sub   { font-size: 11px; color: var(--muted); margin-top: 1px; }
    .act-time  { font-size: 11px; color: var(--muted); white-space: nowrap; }

    .bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .dash-table { width: 100%; border-collapse: collapse; }
    .dash-table th { font-size: 10px; color: var(--muted); font-weight: 600; text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--border); letter-spacing: 0.15em; text-transform: uppercase; }
    .dash-table td { padding: 12px 10px; font-size: 13px; border-bottom: 1px solid var(--border); vertical-align: middle; color: var(--text); }
    .dash-table tr:last-child td { border-bottom: none; }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .plato-row  { display: flex; align-items: center; gap: 8px; }
    .plato-emoji { font-size: 20px; }
    .plato-name  { font-size: 13px; font-weight: 600; }
    .action-btn  { background: none; border: none; cursor: pointer; font-size: 16px; color: var(--muted); padding: 2px 6px; border-radius: 4px; }
    .action-btn:hover { background: #F4F6FB; }

    .date-badge-dash {
        background: #F4F6FB;
        border: 1px solid var(--border);
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
        .mid-row    { grid-template-columns: 1fr; }
        .bottom-row { grid-template-columns: 1fr; }
    }
</style>

<!-- TOPBAR del dashboard -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
    <div>
        <p style="font-size:13px; color:var(--muted);">Resumen general de tu negocio</p>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        <div class="date-badge-dash">📅 <?php echo date('d \d\e F, Y'); ?></div>
    </div>
</div>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#0a0a0a; color:#c5a059;"><i class="fas fa-coins text-lg"></i></div>
        <div>
            <div class="stat-label">Ventas del día</div>
            <div class="stat-value">$<?php echo number_format($ventasHoy); ?></div>
            <div class="stat-delta <?php echo $varVentas >= 0 ? 'delta-up' : 'delta-down'; ?>">
                <?php echo $varVentas >= 0 ? '▲' : '▼'; ?> <?php echo abs($varVentas); ?>% vs ayer
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#0a0a0a; color:#c5a059;"><i class="fas fa-receipt text-lg"></i></div>
        <div>
            <div class="stat-label">Pedidos del día</div>
            <div class="stat-value"><?php echo $pedidosHoy; ?></div>
            <div class="stat-delta <?php echo $varPedidos >= 0 ? 'delta-up' : 'delta-down'; ?>">
                <?php echo $varPedidos >= 0 ? '▲' : '▼'; ?> <?php echo abs($varPedidos); ?>% vs ayer
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#0a0a0a; color:#c5a059;"><i class="fas fa-user-tie text-lg"></i></div>
        <div>
            <div class="stat-label">Clientes atendidos</div>
            <div class="stat-value"><?php echo $clientesHoy; ?></div>
            <div class="stat-delta <?php echo $varClientes >= 0 ? 'delta-up' : 'delta-down'; ?>">
                <?php echo $varClientes >= 0 ? '▲' : '▼'; ?> <?php echo abs($varClientes); ?>% vs ayer
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#0a0a0a; color:#c5a059;"><i class="fas fa-utensils text-lg"></i></div>
        <div>
            <div class="stat-label">Platos más vendidos</div>
            <div class="stat-value"><?php echo $platosMasVendidosCount; ?></div>
            <div class="stat-delta" style="color:var(--muted);">Hoy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#0a0a0a; color:#c5a059;"><i class="fas fa-boxes-stacked text-lg"></i></div>
        <div>
            <div class="stat-label">Productos en inventario</div>
            <div class="stat-value"><?php echo $totalProductos; ?></div>
            <div class="stat-delta" style="color:var(--muted);">En stock</div>
        </div>
    </div>
</div>

<!-- MID ROW -->
<div class="mid-row">

    <div class="dash-card">
        <div class="card-header">
            <span class="card-title">Ventas de los últimos 7 días</span>
            <span class="date-badge-dash" style="font-size:11px;">Últimos 7 días</span>
        </div>
        <div class="chart-wrap"><canvas id="ventasChart"></canvas></div>
    </div>

    <div class="dash-card">
        <div class="card-header"><span class="card-title">Pedidos por estado</span></div>
        <div class="chart-wrap" style="height:160px;"><canvas id="donutChart"></canvas></div>
        <div class="donut-legend">
            <?php
            $totalPed = array_sum(array_column($pedidosEstado, 'total'));
            foreach ($pedidosEstado as $e):
                $color = $coloresEstado[$e['estado']] ?? '#94A3B8';
                $label = ucfirst(str_replace('_', ' ', $e['estado']));
                $pct   = $totalPed > 0 ? round($e['total'] / $totalPed * 100, 1) : 0;
            ?>
            <div class="legend-item">
                <div class="legend-left">
                    <div class="legend-dot" style="background:<?php echo $color; ?>;"></div>
                    <?php echo htmlspecialchars($label); ?> (<?php echo $pct; ?>%)
                </div>
                <div class="legend-right"><?php echo $e['total']; ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($pedidosEstado)): ?>
                <p style="color:var(--muted);font-size:12px;text-align:center;">Sin datos de pedidos</p>
            <?php endif; ?>
        </div>

        <?php
        // Reservas de hoy — con manejo de error
        $reservasHoy = [];
        try {
            $db2 = (new Database())->conectar();
            $reservasHoy = $db2->query("
                SELECT r.hora_reserva, r.numero_personas,
                       u.nombre, u.apellidos,
                       er.nombre_estado AS estado,
                       m.numero_mesa
                FROM reserva r
                JOIN cliente c  ON r.id_cliente = c.id_cliente
                JOIN usuario u  ON c.id_usuario = u.id_usuario
                JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
                LEFT JOIN mesa m ON r.id_mesa = m.id_mesa
                WHERE r.fecha_reserva = CURDATE()
                ORDER BY r.hora_reserva ASC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $reservasHoy = [];
        }
        $badgesR = [
            'confirmada' => ['bg'=>'#D1FAE5','color'=>'#059669'],
            'pendiente'  => ['bg'=>'#FEF3C7','color'=>'#D97706'],
            'cancelada'  => ['bg'=>'#FEE2E2','color'=>'#DC2626'],
            'completada' => ['bg'=>'#EDE9FE','color'=>'#7C3AED'],
        ];
        ?>
        <div style="margin-top:20px;border-top:1px solid var(--border);padding-top:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-family:'Playfair Display',serif;font-size:14px;font-weight:700;color:var(--text);">Reservas de hoy</span>
                <a href="admin_reservas.php" style="font-size:11px;color:var(--text);font-weight:600;text-decoration:none;text-transform:uppercase;letter-spacing:.1em;border-bottom:1px solid var(--text);">Ver todas</a>
            </div>
            <?php if (empty($reservasHoy)): ?>
                <p style="color:var(--muted);font-size:12px;text-align:center;padding:12px 0;">Sin reservas para hoy</p>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($reservasHoy as $rv):
                    $br = $badgesR[strtolower($rv['estado'])] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280'];
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#fafafa;border-radius:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:16px;">📅</span>
                        <div>
                            <div style="font-size:12px;font-weight:600;color:var(--text);"><?= htmlspecialchars($rv['nombre'].' '.$rv['apellidos']) ?></div>
                            <div style="font-size:11px;color:var(--muted);"><?= date('g:i A', strtotime($rv['hora_reserva'])) ?> · Mesa <?= $rv['numero_mesa'] ?? '—' ?> · <?= $rv['numero_personas'] ?> pers.</div>
                        </div>
                    </div>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $br['bg'] ?>;color:<?= $br['color'] ?>;"><?= ucfirst($rv['estado']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- BOTTOM ROW -->
<div class="bottom-row">

    <div class="dash-card">
        <div class="card-header">
            <span class="card-title">Pedidos recientes</span>
            <a href="admin_pedidos.php" class="card-link">Ver todos</a>
        </div>
        <table class="dash-table">
            <thead>
                <tr><th>ID</th><th>Cliente</th><th>Mesa/Domicilio</th><th>Total</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($pedidosRecientes)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">Sin pedidos aún</td></tr>
                <?php endif; ?>
                <?php foreach ($pedidosRecientes as $p):
                    $b = DashboardController::badgeEstado($p['estado']);
                ?>
                <tr data-pedido-id="<?php echo $p['id_pedido']; ?>">
                    <td style="font-weight:600;">#<?php echo $p['id_pedido']; ?></td>
                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td style="color:var(--muted);"><?php echo htmlspecialchars($p['lugar']); ?></td>
                    <td style="font-weight:600;">$<?php echo number_format($p['total']); ?></td>
                    <td>
                        <span class="badge dash-badge-estado" style="color:<?php echo $b['color']; ?>;background:<?php echo $b['bg']; ?>;">
                            <?php echo $b['label']; ?>
                        </span>
                    </td>
                    <td><button class="action-btn">⋮</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="dash-card">
        <div class="card-header">
            <span class="card-title">Platos más vendidos</span>
            <a href="admin_reportes.php" class="card-link">Ver reporte</a>
        </div>
        <table class="dash-table">
            <thead>
                <tr><th>Plato</th><th>Categoría</th><th>Vendidos</th><th>Ingresos</th></tr>
            </thead>
            <tbody>
                <?php if (empty($platosMasVendidos)): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:24px;">Sin datos de ventas</td></tr>
                <?php endif; ?>
                <?php foreach ($platosMasVendidos as $pl): ?>
                <tr>
                    <td>
                        <div class="plato-row">
                            <span class="plato-emoji"><?php echo $pl['imagen'] ?: '🍽️'; ?></span>
                            <span class="plato-name"><?php echo htmlspecialchars($pl['nombre']); ?></span>
                        </div>
                    </td>
                    <td style="color:var(--muted);"><?php echo htmlspecialchars($pl['categoria']); ?></td>
                    <td style="font-weight:600;"><?php echo $pl['vendidos']; ?></td>
                    <td style="font-weight:600;">$<?php echo number_format($pl['ingresos']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- DOMICILIOS RECIENTES -->
<div class="dash-card" style="margin-top:16px;">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:10px;">
            <i class="fas fa-motorcycle text-retro-gold text-xl"></i>
            <span class="card-title">Domicilios recientes</span>
            <span style="background:#0a0a0a;color:#c5a059;font-size:10px;font-weight:700;padding:2px 10px;border-radius:20px;letter-spacing:0.1em;text-transform:uppercase;">
                <?php echo $domiciliosHoy; ?> hoy
            </span>
        </div>
        <a href="admin_domicilios.php" class="card-link">Ver todos</a>
    </div>
    <table class="dash-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($domiciliosRecientes)): ?>
            <tr>
                <td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">
                    Sin domicilios registrados —
                    <a href="admin_domicilios.php" style="color:#E53E3E;font-weight:600;">Registrar uno</a>
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($domiciliosRecientes as $d):
                $b   = DashboardController::badgeEstado($d['estado']);
                $num = str_pad($d['id_pedido'], 5, '0', STR_PAD_LEFT);
            ?>
            <tr>
                <td style="font-weight:600;">#ORD-<?php echo $num; ?></td>
                <td><?php echo htmlspecialchars($d['nombre']); ?></td>
                <td style="color:var(--muted);">
                    <span style="display:flex;align-items:center;gap:4px;">
                        📞 <?php echo htmlspecialchars($d['telefono']); ?>
                    </span>
                </td>
                <td style="color:var(--muted);"><?php echo date('d/m/Y', strtotime($d['fecha_pedido'])); ?></td>
                <td style="font-weight:600;color:#059669;">$<?php echo number_format($d['total']); ?></td>
                <td>
                    <span class="badge" style="color:<?php echo $b['color']; ?>;background:<?php echo $b['bg']; ?>;">
                        <?php echo $b['label']; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('ventasChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?php echo $chartLabels; ?>,
        datasets: [{
            label: 'Ventas ($)',
            data: <?php echo $chartData; ?>,
            borderColor: '#c5a059',
            backgroundColor: 'rgba(197, 160, 89, 0.1)',
            borderWidth: 2,
            pointBackgroundColor: '#c5a059',
            pointRadius: 3,
            pointHoverRadius: 5,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' $' + ctx.parsed.y.toLocaleString('es-CO')
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#718096' } },
            y: {
                grid: { color: '#F1F5F9' },
                ticks: {
                    font: { size: 11 }, color: '#718096',
                    callback: v => '$' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v)
                }
            }
        }
    }
});

new Chart(document.getElementById('donutChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?php echo $donutLabels; ?>,
        datasets: [{
            data: <?php echo $donutData; ?>,
            backgroundColor: <?php echo $donutColors; ?>,
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.label + ': ' + ctx.parsed
                }
            }
        }
    }
});
</script>

<div id="toastDash" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none;"></div>

<script>
// ── SSE Dashboard ───────────────────────────────────────────────────────────────
const BADGE_DASH = {
  pendiente:      { bg:'#FEF3C7', color:'#D97706', label:'Pendiente' },
  en_preparacion: { bg:'#DBEAFE', color:'#2563EB', label:'En preparación' },
  listo:          { bg:'#D1FAE5', color:'#059669', label:'Listo' },
  entregado:      { bg:'#EDE9FE', color:'#7C3AED', label:'Entregado' },
  completado:     { bg:'#EDE9FE', color:'#7C3AED', label:'Completado' },
  cancelado:      { bg:'#FEE2E2', color:'#DC2626', label:'Cancelado' },
};

function toastDash(msg) {
  const c = document.getElementById('toastDash');
  const t = document.createElement('div');
  t.style.cssText = 'pointer-events:auto;background:#0a0a0a;color:#c5a059;padding:10px 18px;border-radius:12px;font-size:12px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.3);opacity:0;transform:translateY(8px);transition:all .3s ease;';
  t.textContent = msg;
  c.appendChild(t);
  requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
  setTimeout(() => { t.style.opacity='0'; setTimeout(() => t.remove(), 350); }, 3000);
}

(function() {
  if (!window.EventSource) return;
  const url = `../../Controllers/PedidoSSE.php?modo=admin&desde=${Math.floor(Date.now()/1000)}`;
  let es = null;
  let reconectando = false;

  function conectar() {
    es = new EventSource(url);
    es.addEventListener('pedido_actualizado', function(e) {
      try {
        const data = JSON.parse(e.data);
        if (!data.cambios || !data.cambios.length) return;
        data.cambios.forEach(c => {
          const row = document.querySelector(`tr[data-pedido-id="${c.id_pedido}"]`);
          if (!row) return;
          const badge = row.querySelector('.dash-badge-estado');
          const b = BADGE_DASH[c.estado] || { bg:'#F3F4F6', color:'#6B7280', label:c.estado };
          if (badge) {
            badge.style.background = b.bg;
            badge.style.color      = b.color;
            badge.textContent      = b.label;
          }
          row.style.transition = 'background .5s ease';
          row.style.background = '#FFF8E7';
          setTimeout(() => { row.style.background = ''; }, 2000);
        });
        const ords = data.cambios.map(c => '#' + c.id_pedido).join(', ');
        toastDash('🔄 Pedido ' + ords + ' actualizado');
      } catch(err) {}
    });
    es.addEventListener('reconnect', () => { es.close(); setTimeout(conectar,1000); });
    es.onerror = () => {
      es.close();
      if (!reconectando) { reconectando = true; setTimeout(() => { reconectando=false; conectar(); }, 5000); }
    };
  }

  conectar();
  window.addEventListener('beforeunload', () => { if (es) es.close(); });
}());
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>