<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [2, '2', 'empleado'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}
$usuario = $_SESSION['usuario'];
$titulo  = "PANEL EMPLEADO";

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Reserva.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Domicilio.php';

$db = (new database())->conectar();

$reservasHoy     = (new Reserva($db))->getReservasHoy();
$pedidosHoy      = (new Pedido($db))->getPedidosHoy();
$domiciliosHoy   = (new Domicilio($db))->getDomiciliosHoy();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

  <!-- Bienvenida -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-5">
    <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl flex-shrink-0" style="background:#DBEAFE;color:#2563EB;">
      <i class="fas fa-user-tie"></i>
    </div>
    <div>
      <h2 class="text-2xl font-heading font-bold text-retro-dark">
        Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?> 👋
      </h2>
      <p class="text-gray-500 font-body text-sm">Panel de empleado — <?= date('d \d\e F, Y') ?></p>
    </div>
  </div>

  <!-- KPIs -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0" style="background:#EDE9FE;color:#7C3AED;">
        <i class="fas fa-calendar-day"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500 font-body">Reservas hoy</p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $reservasHoy ?></p>
      </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0" style="background:#FEF3C7;color:#D97706;">
        <i class="fas fa-receipt"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500 font-body">Pedidos hoy</p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $pedidosHoy ?></p>
      </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0" style="background:#D1FAE5;color:#059669;">
        <i class="fas fa-motorcycle"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500 font-body">Domicilios hoy</p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $domiciliosHoy ?></p>
      </div>
    </div>
  </div>

  <!-- Accesos rápidos -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

    <a href="admin_reservas.php"
       class="bg-white rounded-2xl border-2 border-gray-100 shadow-sm p-6 hover:border-purple-300 hover:shadow-md transition-all group">
      <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition" style="background:#EDE9FE;color:#7C3AED;">
        <i class="fas fa-calendar-check"></i>
      </div>
      <h3 class="text-xl font-heading font-bold text-retro-dark">Reservas</h3>
      <p class="text-sm text-gray-500 font-body mt-1">Ver y gestionar reservas del día</p>
      <div class="mt-4 flex items-center gap-1 text-purple-600 text-sm font-bold">
        Ir a reservas <i class="fas fa-arrow-right text-xs ml-1"></i>
      </div>
    </a>

    <a href="admin_pedidos.php"
       class="bg-white rounded-2xl border-2 border-gray-100 shadow-sm p-6 hover:border-yellow-300 hover:shadow-md transition-all group">
      <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition" style="background:#FEF3C7;color:#D97706;">
        <i class="fas fa-receipt"></i>
      </div>
      <h3 class="text-xl font-heading font-bold text-retro-dark">Pedidos</h3>
      <p class="text-sm text-gray-500 font-body mt-1">Gestionar pedidos activos</p>
      <div class="mt-4 flex items-center gap-1 text-yellow-600 text-sm font-bold">
        Ir a pedidos <i class="fas fa-arrow-right text-xs ml-1"></i>
      </div>
    </a>

    <a href="admin_domicilios.php"
       class="bg-white rounded-2xl border-2 border-gray-100 shadow-sm p-6 hover:border-green-300 hover:shadow-md transition-all group">
      <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition" style="background:#D1FAE5;color:#059669;">
        <i class="fas fa-motorcycle"></i>
      </div>
      <h3 class="text-xl font-heading font-bold text-retro-dark">Domicilios</h3>
      <p class="text-sm text-gray-500 font-body mt-1">Gestionar pedidos a domicilio</p>
      <div class="mt-4 flex items-center gap-1 text-green-600 text-sm font-bold">
        Ir a domicilios <i class="fas fa-arrow-right text-xs ml-1"></i>
      </div>
    </a>

  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
