<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [3,'3','cliente'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $base     = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$protocol}://{$host}{$base}/views/usuarios/login.php");
    exit;
}
$usuario = $_SESSION['usuario'];
$titulo  = "DASHBOARD";

require_once __DIR__ . '/../../config/database.php';

// Conexión a BD con manejo de error
$db = null;
$dbOk = false;
try {
    $db   = (new Database())->conectar();
    $dbOk = true;
} catch (Exception $e) {
    $dbOk = false;
}

// Cliente
$id_cliente = null;
if ($dbOk) {
    try {
        $stmtC = $db->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = :id LIMIT 1");
        $stmtC->execute([':id' => $usuario['id_usuario']]);
        $rowC = $stmtC->fetch(PDO::FETCH_ASSOC);
        if ($rowC) $id_cliente = $rowC['id_cliente'];
    } catch (Exception $e) { $id_cliente = null; }
}

// Conteos — protegidos con try/catch
$totalReservas   = 0;
$totalPedidos    = 0;
$totalDomicilios = 0;
$totalMenu       = 0;
if ($dbOk) {
    try {
        if ($id_cliente) {
            $totalReservas   = (int)$db->query("SELECT COUNT(*) FROM reserva WHERE id_cliente=$id_cliente AND fecha_reserva >= CURDATE()")->fetchColumn();
            $totalPedidos    = (int)$db->query("SELECT COUNT(*) FROM pedido WHERE id_cliente=$id_cliente")->fetchColumn();
            $totalDomicilios = (int)$db->query("SELECT COUNT(*) FROM pedido p JOIN tipo_pedido tp ON p.id_tipo_pedido=tp.id_tipo_pedido WHERE p.id_cliente=$id_cliente AND tp.nombre_tipo LIKE '%domicilio%'")->fetchColumn();
        }
        $totalMenu = (int)$db->query("SELECT COUNT(*) FROM producto")->fetchColumn();
    } catch (Exception $e) { /* silencioso */ }
}

// Reservas próximas
$misReservas = [];
if ($dbOk && $id_cliente) {
    try {
        // Detectar nombre real de la columna (numero_personas o numero_usuarios)
        $colPersonas = 'numero_personas';
        try {
            $testCol = $db->query("SHOW COLUMNS FROM reserva LIKE 'numero_personas'")->fetch();
            if (!$testCol) $colPersonas = 'numero_usuarios';
        } catch (Exception $e) { $colPersonas = 'numero_personas'; }

        $stmtR = $db->prepare("
            SELECT r.fecha_reserva, r.hora_reserva, r.{$colPersonas} AS numero_personas,
                   er.nombre_estado AS estado, m.numero_mesa
            FROM reserva r
            JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
            LEFT JOIN mesa m ON r.id_mesa = m.id_mesa
            WHERE r.id_cliente = :id
            ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC
            LIMIT 5
        ");
        $stmtR->execute([':id' => $id_cliente]);
        $misReservas = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $misReservas = [];
    }
}

// Puntos
$puntos   = 1250;
$nivel    = $puntos >= 1000 ? 'Oro' : ($puntos >= 500 ? 'Plata' : 'Bronce');
$meta     = 2000;
$progreso = min(100, round($puntos / $meta * 100));

$dias  = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles',
          'Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
$meses = ['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril',
          'May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto',
          'September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

function badgeR($estado) {
    $map = [
        'confirmada'=>['bg'=>'#D1FAE5','color'=>'#059669'],
        'pendiente' =>['bg'=>'#FEF3C7','color'=>'#D97706'],
        'cancelada' =>['bg'=>'#FEE2E2','color'=>'#DC2626'],
        'completada'=>['bg'=>'#EDE9FE','color'=>'#7C3AED'],
    ];
    $b = $map[strtolower($estado)] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280'];
    $b['label'] = ucfirst($estado);
    return $b;
}
?>

<style>
@keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes wave   { 0%,100%{transform:rotate(0)} 25%{transform:rotate(-15deg)} 75%{transform:rotate(15deg)} }
@keyframes countUp{ from{opacity:0;transform:scale(.6)} to{opacity:1;transform:scale(1)} }

.card-dark {
  background:#1a1f2e; color:#fff; border-radius:16px; padding:20px;
  text-decoration:none; display:block; position:relative; overflow:hidden;
  border:1px solid transparent;
  transition:transform .3s cubic-bezier(.34,1.56,.64,1), border-color .3s, box-shadow .3s;
  animation:fadeUp .5s ease both;
}
.card-dark:hover {
  transform:translateY(-6px) scale(1.02);
  border-color:rgba(236,201,75,.4);
  box-shadow:0 12px 32px rgba(0,0,0,.3), 0 0 0 1px rgba(236,201,75,.15);
}
.card-dark:nth-child(1){animation-delay:.05s}
.card-dark:nth-child(2){animation-delay:.15s}
.card-dark:nth-child(3){animation-delay:.25s}
.card-dark:nth-child(4){animation-delay:.35s}
.card-dark h3  { font-size:13px; color:#a0aec0; margin-bottom:4px; }
.card-big      { font-size:28px; font-weight:700; display:inline-block; }
.card-dark .sub{ font-size:12px; color:#a0aec0; margin-top:2px; }
.card-dark .lnk{ color:#ECC94B; font-size:12px; font-weight:600; text-decoration:none;
                 display:inline-flex; align-items:center; gap:4px; margin-top:12px; transition:gap .2s; }
.card-dark:hover .lnk { gap:8px; }
.card-dark .icon-wrap { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;
  justify-content:center;font-size:20px;margin-bottom:12px;
  transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.card-dark:hover .icon-wrap { transform:scale(1.2) rotate(-5deg); }
.wave-emoji { display:inline-block; animation:wave 1.5s ease-in-out 1s 3; }

.puntos-bar  { height:6px;background:#374151;border-radius:3px;margin:10px 0; }
.puntos-fill { height:6px;background:linear-gradient(90deg,#ECC94B,#f6ad55);border-radius:3px;
               width:0;transition:width 1.4s cubic-bezier(.4,0,.2,1); }
</style>

<div class="space-y-6">

  <!-- Bienvenida -->
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-heading font-bold text-retro-dark" style="font-size:24px;">
        ¡Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?>! <span class="wave-emoji">👋</span>
      </h1>
      <p class="text-gray-500 font-body text-sm">¿Qué quieres disfrutar hoy?</p>
    </div>
    <div style="position:relative;">
      <input type="text" placeholder="Buscar platos, bebidas..."
        style="padding:10px 16px 10px 40px;border:1px solid #e2e8f0;border-radius:12px;font-size:13px;width:260px;outline:none;background:#f8fafc;">
      <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#a0aec0;font-size:13px;"></i>
    </div>
  </div>

  <!-- 4 tarjetas -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
    <a href="cliente_reservas.php" class="card-dark">
      <div class="icon-wrap" style="background:#2d3748;">📅</div>
      <h3>Mis Reservas</h3>
      <div class="card-big"><?= $totalReservas ?></div>
      <div class="sub">próximas</div>
      <span class="lnk">Ver todas →</span>
    </a>
    <a href="cliente_pedidos.php" class="card-dark">
      <div class="icon-wrap" style="background:#2d3748;">🧾</div>
      <h3>Mis Pedidos</h3>
      <div class="card-big"><?= $totalPedidos ?></div>
      <div class="sub">pedidos</div>
      <span class="lnk">Ver historial →</span>
    </a>
    <a href="cliente_domicilios.php" class="card-dark">
      <div class="icon-wrap" style="background:#2d3748;">🛵</div>
      <h3>Domicilios</h3>
      <div class="card-big"><?= $totalDomicilios ?></div>
      <div class="sub">Pide a domicilio</div>
      <span class="lnk">Ordenar ahora →</span>
    </a>
    <a href="cliente_catalogo.php" class="card-dark">
      <div class="icon-wrap" style="background:#2d3748;">🍽️</div>
      <h3>Menú</h3>
      <div class="card-big"><?= $totalMenu ?>+</div>
      <div class="sub">Explorar carta</div>
      <span class="lnk">Ver menú →</span>
    </a>
  </div>

  <!-- Reservas próximas -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <h3 class="font-heading font-bold text-retro-dark flex items-center gap-2">
        📅 Mis reservas próximas
      </h3>
      <a href="cliente_reservas.php" class="text-xs font-bold" style="color:#E53E3E;">Ver todas</a>
    </div>
    <?php if (empty($misReservas)): ?>
    <div class="py-10 text-center text-gray-400">
      <p class="text-sm">No tienes reservas próximas.</p>
      <a href="admin_reservas.php" class="text-xs font-bold mt-2 inline-block" style="color:#ECC94B;">+ Nueva reserva</a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-left font-body text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-5 py-3 text-xs text-gray-500 font-heading uppercase">Fecha</th>
            <th class="px-5 py-3 text-xs text-gray-500 font-heading uppercase">Hora</th>
            <th class="px-5 py-3 text-xs text-gray-500 font-heading uppercase">Mesa</th>
            <th class="px-5 py-3 text-xs text-gray-500 font-heading uppercase">Personas</th>
            <th class="px-5 py-3 text-xs text-gray-500 font-heading uppercase">Estado</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($misReservas as $r):
            $b = badgeR($r['estado']);
            $diaKey = date('l', strtotime($r['fecha_reserva']));
          ?>
          <tr class="hover:bg-gray-50 transition">
            <td class="px-5 py-3">
              <div class="font-semibold text-gray-800"><?= date('d/m/Y', strtotime($r['fecha_reserva'])) ?></div>
              <div class="text-xs text-gray-400"><?= $dias[$diaKey] ?? $diaKey ?></div>
            </td>
            <td class="px-5 py-3 text-gray-600"><?= date('g:i A', strtotime($r['hora_reserva'])) ?></td>
            <td class="px-5 py-3 font-semibold text-gray-800">Mesa <?= $r['numero_mesa'] ?? '—' ?></td>
            <td class="px-5 py-3 text-gray-600"><?= $r['numero_personas'] ?> personas</td>
            <td class="px-5 py-3">
              <span class="px-3 py-1 rounded-full text-xs font-bold"
                    style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
                <?= $b['label'] ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Retro Puntos -->
  <div style="background:linear-gradient(135deg,#1a1f2e,#2d3748);border-radius:16px;padding:20px;color:#fff;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
      <span style="font-size:12px;font-weight:700;color:#ECC94B;letter-spacing:.5px;">⭐ RETRO PUNTOS</span>
      <span style="font-size:22px;">👑</span>
    </div>
    <div style="font-size:32px;font-weight:700;"><?= number_format($puntos) ?> <span style="font-size:14px;color:#a0aec0;">pts</span></div>
    <div style="font-size:13px;color:#ECC94B;font-weight:600;margin-top:2px;">Cliente <?= $nivel ?></div>
    <div class="puntos-bar"><div class="puntos-fill" data-target="<?= $progreso ?>"></div></div>
    <div style="font-size:12px;color:#a0aec0;">Faltan <?= number_format($meta - $puntos) ?> pts para tu próximo descuento</div>
    <button style="margin-top:12px;width:100%;background:transparent;border:1px solid #ECC94B;color:#ECC94B;
                   padding:9px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;
                   transition:background .2s,color .2s;"
            onmouseover="this.style.background='#ECC94B';this.style.color='#1a202c'"
            onmouseout="this.style.background='transparent';this.style.color='#ECC94B'">
      Ver beneficios
    </button>
  </div>

  <!-- Barra de características -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach ([
      ['fas fa-shield-alt','Pago 100% seguro','Tus datos están protegidos'],
      ['fas fa-headset','Atención 24/7','Siempre para ayudarte'],
      ['fas fa-shipping-fast','Envíos rápidos','Directo a tu puerta'],
      ['fas fa-star','Calidad garantizada','Ingredientes seleccionados'],
    ] as $f): ?>
    <div class="flex items-center gap-3">
      <i class="<?= $f[0] ?> text-2xl" style="color:#ECC94B;"></i>
      <div>
        <div class="font-heading font-bold text-retro-dark text-xs"><?= $f[1] ?></div>
        <div class="text-xs text-gray-500"><?= $f[2] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<script>
// Contador animado
document.querySelectorAll('.card-big').forEach(el => {
  const raw = el.textContent.trim();
  const num = parseInt(raw.replace(/\D/g,'')) || 0;
  const suf = raw.replace(/[0-9]/g,'');
  el.textContent = '0' + suf;
  const obs = new IntersectionObserver(entries => {
    if (!entries[0].isIntersecting) return;
    let s = 0, step = Math.ceil(num/50);
    const t = setInterval(() => {
      s += step;
      if (s >= num) { el.textContent = num + suf; clearInterval(t); }
      else el.textContent = s + suf;
    }, 16);
    obs.disconnect();
  }, {threshold:.3});
  obs.observe(el);
});

// Barra de puntos
const fill = document.querySelector('.puntos-fill');
if (fill) {
  const obs = new IntersectionObserver(entries => {
    if (!entries[0].isIntersecting) return;
    setTimeout(() => { fill.style.width = fill.dataset.target + '%'; }, 300);
    obs.disconnect();
  }, {threshold:.5});
  obs.observe(fill);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
