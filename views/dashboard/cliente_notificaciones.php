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
$titulo  = "NOTIFICACIONES";

require_once __DIR__ . '/../../Controllers/CompraController.php';

// Marcar como leídas al abrir
CompraController::marcarLeidas($usuario['id_usuario']);

$notifs = CompraController::getNotificaciones($usuario['id_usuario'], 50);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

function tiempoRelativo($fecha) {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)    return 'Hace '.$diff.' seg';
    if ($diff < 3600)  return 'Hace '.floor($diff/60).' min';
    if ($diff < 86400) return 'Hace '.floor($diff/3600).' h';
    return 'Hace '.floor($diff/86400).' días';
}

// Iconos y colores por tipo
function notifEstilo($tipo) {
    $map = [
        'pago'      => ['icono' => '🎉', 'bg' => '#D1FAE5', 'label' => 'Pago confirmado'],
        'pedido'    => ['icono' => '🛍️', 'bg' => '#DBEAFE', 'label' => 'Pedido'],
        'promocion' => ['icono' => '🔥', 'bg' => '#FEF3C7', 'label' => 'Promoción'],
        'combo'     => ['icono' => '🍱', 'bg' => '#EDE9FE', 'label' => 'Combo'],
    ];
    return $map[$tipo] ?? ['icono' => '🔔', 'bg' => '#F3F4F6', 'label' => 'Notificación'];
}
?>

<div class="space-y-5 max-w-2xl">

  <div>
    <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
      <i class="fas fa-bell" style="color:#c5a059;"></i> Notificaciones
    </h1>
    <p class="text-gray-500 font-body text-sm mt-1">Tus pagos, pedidos y promociones activas.</p>
  </div>

  <?php if (empty($notifs)): ?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center text-gray-400">
    <i class="fas fa-bell-slash text-5xl mb-4" style="color:#e2e8f0;"></i>
    <p class="font-heading text-lg text-gray-600">Sin notificaciones</p>
    <p class="text-sm mt-1">Aquí verás confirmaciones de pago y promociones especiales.</p>
    <a href="cliente_catalogo.php"
       class="inline-block mt-6 px-6 py-3 text-xs font-bold uppercase tracking-widest text-white rounded-xl transition"
       style="background:#0a0a0a;">
      Ver menú
    </a>
  </div>

  <?php else: ?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <?php foreach ($notifs as $n):
      $estilo = notifEstilo($n['tipo']);
    ?>
    <div class="flex items-start gap-4 px-6 py-4 border-b border-gray-50 hover:bg-gray-50 transition <?= $n['leida'] ? 'opacity-60' : '' ?>">

      <!-- Ícono -->
      <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
           style="background:<?= $estilo['bg'] ?>;">
        <?= $estilo['icono'] ?>
      </div>

      <!-- Contenido -->
      <div class="flex-1 min-w-0">
        <?php if (!$n['leida']): ?>
        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded-full mb-1"
              style="background:<?= $estilo['bg'] ?>;color:#374151;">
          <?= $estilo['label'] ?>
        </span>
        <?php endif; ?>
        <p class="font-semibold text-gray-800 text-sm leading-snug"><?= htmlspecialchars($n['titulo']) ?></p>
        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed"><?= htmlspecialchars($n['mensaje']) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= tiempoRelativo($n['created_at']) ?></p>
      </div>

      <!-- Acción según tipo -->
      <?php if ($n['tipo'] === 'pago' && $n['id_referencia']): ?>
        <a href="cliente_pedidos.php" class="text-xs font-bold flex-shrink-0 mt-1 hover:underline" style="color:#c5a059;">
          Ver pedido
        </a>
      <?php elseif ($n['tipo'] === 'promocion'): ?>
        <a href="cliente_catalogo.php" class="text-xs font-bold flex-shrink-0 mt-1 hover:underline" style="color:#c5a059;">
          Ver menú
        </a>
      <?php endif; ?>

    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
