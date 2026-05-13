<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1,'1','administrador',2,'2','empleado'])) {
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
?>

<div class="space-y-5 max-w-2xl">

  <div>
    <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
      <i class="fas fa-bell text-retro-red"></i> Notificaciones
    </h1>
    <p class="text-gray-500 font-body text-sm mt-1">Pedidos y actividad reciente del restaurante.</p>
  </div>

  <?php if (empty($notifs)): ?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center text-gray-400">
    <i class="fas fa-bell-slash text-5xl mb-4"></i>
    <p class="font-heading text-lg">Sin notificaciones</p>
    <p class="text-sm mt-1">Aquí aparecerán los nuevos pedidos de los clientes.</p>
  </div>
  <?php else: ?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <?php foreach ($notifs as $n):
      $iconos = ['pedido'=>'🛍️','reserva'=>'📅','domicilio'=>'🛵'];
      $icono  = $iconos[$n['tipo']] ?? '🔔';
    ?>
    <div class="flex items-start gap-4 px-6 py-4 border-b border-gray-50 hover:bg-gray-50 transition <?= $n['leida'] ? 'opacity-60' : '' ?>">
      <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
           style="background:#FEF3C7;"><?= $icono ?></div>
      <div class="flex-1 min-w-0">
        <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($n['titulo']) ?></p>
        <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($n['mensaje']) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= tiempoRelativo($n['created_at']) ?></p>
      </div>
      <?php if ($n['id_referencia']): ?>
      <a href="admin_pedidos.php" class="text-xs font-bold flex-shrink-0 mt-1" style="color:#E53E3E;">Ver pedido</a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
