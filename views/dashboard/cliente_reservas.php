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
$titulo  = "MIS RESERVAS";

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../Controllers/ReservaController.php';

$db = (new database())->conectar();

// Obtener id_cliente
$stmtC = $db->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = :id LIMIT 1");
$stmtC->execute([':id' => $usuario['id_usuario']]);
$rowC = $stmtC->fetch(PDO::FETCH_ASSOC);
$id_cliente = $rowC ? $rowC['id_cliente'] : null;

// Manejar POST (crear reserva)
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'crear' && $id_cliente) {
        $datos = [
            'id_cliente'        => $id_cliente,
            'id_mesa'           => (int)($_POST['id_mesa'] ?? 0),
            'fecha_reserva'     => $_POST['fecha_reserva'] ?? '',
            'hora_reserva'      => $_POST['hora_reserva']  ?? '',
            'numero_personas'   => (int)($_POST['numero_personas'] ?? 1),
            'id_estado_reserva' => 1, // pendiente por defecto
        ];
        $ctrl = new ReservaController();
        // Usar el modelo directamente
        require_once __DIR__ . '/../../models/Reserva.php';
        $model = new Reserva($db);
        $r = $model->crear($datos);
        if ($r === true) $success = 'Reserva creada correctamente.';
        else             $error   = 'Error al crear la reserva: ' . $r;
    }
    if ($_POST['accion'] === 'editar' && $id_cliente) {
        $id_reserva = (int)($_POST['id_reserva'] ?? 0);
        // Verificar que la reserva pertenece al cliente y no está cancelada/completada
        $check = $db->prepare("
            SELECT r.id_reserva FROM reserva r
            JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
            WHERE r.id_reserva = :id AND r.id_cliente = :c
              AND er.nombre_estado NOT IN ('cancelada','completada')
        ");
        $check->execute([':id'=>$id_reserva,':c'=>$id_cliente]);
        if ($check->fetch()) {
            // Obtener el estado actual para no modificarlo
            $estadoActual = $db->prepare("SELECT id_estado_reserva FROM reserva WHERE id_reserva=:id");
            $estadoActual->execute([':id'=>$id_reserva]);
            $id_estado_actual = $estadoActual->fetchColumn();

            $datos = [
                'id_mesa'           => (int)($_POST['id_mesa'] ?? 0),
                'fecha_reserva'     => $_POST['fecha_reserva'] ?? '',
                'hora_reserva'      => $_POST['hora_reserva']  ?? '',
                'numero_personas'   => (int)($_POST['numero_personas'] ?? 1),
                'id_estado_reserva' => $id_estado_actual, // el admin maneja el estado
            ];
            require_once __DIR__ . '/../../models/Reserva.php';
            $model = new Reserva($db);
            $r = $model->actualizar($id_reserva, $datos);
            if ($r === true) $success = 'Reserva actualizada correctamente.';
            else             $error   = 'Error al actualizar: ' . $error;
        } else {
            $error = 'No puedes editar esta reserva.';
        }
    }
    if ($_POST['accion'] === 'eliminar' && $id_cliente) {
        $id_reserva = (int)($_POST['id_reserva'] ?? 0);
        // Solo puede eliminar sus propias reservas canceladas o completadas
        $check = $db->prepare("
            SELECT r.id_reserva FROM reserva r
            JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
            WHERE r.id_reserva = :id AND r.id_cliente = :c
              AND er.nombre_estado IN ('cancelada','completada','no asistio')
        ");
        $check->execute([':id'=>$id_reserva,':c'=>$id_cliente]);
        if ($check->fetch()) {
            $db->prepare("DELETE FROM reserva WHERE id_reserva = :id")->execute([':id'=>$id_reserva]);
            $success = 'Reserva eliminada correctamente.';
        } else {
            $error = 'Solo puedes eliminar reservas canceladas o completadas.';
        }
    }
    if ($_POST['accion'] === 'cancelar' && $id_cliente) {
        $id_reserva  = (int)($_POST['id_reserva'] ?? 0);
        $idCancelada = $db->query("SELECT id_estado_reserva FROM estado_reserva WHERE nombre_estado='cancelada' LIMIT 1")->fetchColumn();
        if ($id_reserva && $idCancelada) {
            $db->prepare("UPDATE reserva SET id_estado_reserva=:e WHERE id_reserva=:id AND id_cliente=:c")
               ->execute([':e'=>$idCancelada,':id'=>$id_reserva,':c'=>$id_cliente]);
            $success = 'Reserva cancelada.';
        }
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todas';
$filtro_fecha  = $_GET['fecha']  ?? '';
$pagina        = $_POST['pagina'] ?? 1;
$por_pagina    = 8;

// Construir query
$where  = $id_cliente ? ["r.id_cliente = $id_cliente"] : ["1=0"];
$params = [];
if ($filtro_estado !== 'todas') {
    $where[] = "er.nombre_estado = :estado";
    $params[':estado'] = $filtro_estado;
}
if ($filtro_fecha) {
    $where[] = "DATE(r.fecha_reserva) = :fecha";
    $params[':fecha'] = $filtro_fecha;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);
$offset   = ($pagina - 1) * $por_pagina;

$stmtTotal = $db->prepare("SELECT COUNT(*) FROM reserva r JOIN estado_reserva er ON r.id_estado_reserva=er.id_estado_reserva $whereSQL");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, ceil($total / $por_pagina));

$stmtR = $db->prepare("
    SELECT r.id_reserva, r.fecha_reserva, r.hora_reserva, r.numero_personas,
           er.nombre_estado AS estado, er.id_estado_reserva,
           m.numero_mesa, m.capacidad
    FROM reserva r
    JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
    LEFT JOIN mesa m ON r.id_mesa = m.id_mesa
    $whereSQL
    ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC
    LIMIT :lim OFFSET :off
");
foreach ($params as $k => $ReservaController) $stmtR->bindValue($k, $ReservaController);
$stmtR->bindValue(':lim', $por_pagina, PDO::PARAM_INT);
$stmtR->bindValue(':off', $offset,     PDO::PARAM_INT);
$stmtR->execute();
$reservas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$totalReservas    = $id_cliente ? (int)$db->query("SELECT COUNT(*) FROM reserva WHERE id_cliente=$id_cliente")->fetchColumn() : 0;
$proximasReservas = $id_cliente ? (int)$db->query("SELECT COUNT(*) FROM reserva r JOIN estado_reserva er ON r.id_estado_reserva=er.id_estado_reserva WHERE r.id_cliente=$id_cliente AND r.fecha_reserva >= CURDATE() AND er.nombre_estado NOT IN ('cancelada','completada')")->fetchColumn() : 0;
$confirmadas      = $id_cliente ? (int)$db->query("SELECT COUNT(*) FROM reserva r JOIN estado_reserva er ON r.id_estado_reserva=er.id_estado_reserva WHERE r.id_cliente=$id_cliente AND er.nombre_estado='confirmada'")->fetchColumn() : 0;
$canceladas       = $id_cliente ? (int)$db->query("SELECT COUNT(*) FROM reserva r JOIN estado_reserva er ON r.id_estado_reserva=er.id_estado_reserva WHERE r.id_cliente=$id_cliente AND er.nombre_estado='cancelada'")->fetchColumn() : 0;

// Catálogos para el modal
$mesas    = $db->query("SELECT id_mesa, numero_mesa, capacidad FROM mesa ORDER BY numero_mesa")->fetchAll(PDO::FETCH_ASSOC);
$estados  = $db->query("SELECT id_estado_reserva, nombre_estado FROM estado_reserva ORDER BY id_estado_reserva")->fetchAll(PDO::FETCH_ASSOC);

// Para el modal de edición, se necesita la reserva activa de la misma mesa si existiese.
// La disponibilidad dinámica se calcula vía AJAX al cambiar fecha/hora en el formulario.

$dias = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles',
         'Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

function badgeReserva($estado) {
    $map = [
        'confirmada' => ['bg'=>'#D1FAE5','color'=>'#059669','dot'=>'#10B981'],
        'pendiente'  => ['bg'=>'#FEF3C7','color'=>'#D97706','dot'=>'#F59E0B'],
        'cancelada'  => ['bg'=>'#FEE2E2','color'=>'#DC2626','dot'=>'#EF4444'],
        'completada' => ['bg'=>'#EDE9FE','color'=>'#7C3AED','dot'=>'#8B5CF6'],
        'no asistio' => ['bg'=>'#F3F4F6','color'=>'#6B7280','dot'=>'#9CA3AF'],
    ];
    $b = $map[strtolower($estado)] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','dot'=>'#9CA3AF'];
    $b['label'] = ucfirst($estado);
    return $b;
}
?>

<style>
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes zoomIn { from{opacity:0;transform:scale(.9)} to{opacity:1;transform:scale(1)} }
.modal-bg  { display:none;position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.5);align-items:center;justify-content:center; }
.modal-bg.show { display:flex; }
.modal-box { background:#fff;border-radius:20px;width:90%;max-width:460px;overflow:hidden;animation:zoomIn .3s cubic-bezier(.34,1.56,.64,1); }
.res-card  { background:#fff;border-radius:16px;border:1px solid #f0f0f0;padding:20px;
             transition:transform .2s,box-shadow .2s;animation:fadeUp .4s ease both; }
.res-card:hover { transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.08); }
</style>

<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
        <i class="fas fa-calendar-check text-retro-red"></i> Mis Reservas
      </h1>
      <p class="text-gray-500 font-body text-sm mt-1">Gestiona todas tus reservas en el restaurante.</p>
    </div>
    <button onclick="document.getElementById('modalCrear').classList.add('show')"
      class="bg-retro-red hover:bg-red-700 text-white font-heading px-5 py-3 rounded-xl shadow flex items-center gap-2 transition">
      <i class="fas fa-plus"></i> Nueva Reserva
    </button>
  </div>

  <!-- Alertas -->
  <?php if ($success): ?>
  <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2 font-body text-sm">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
  </div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-2 font-body text-sm">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- KPIs -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach ([
      ['📅','Total reservas',   $totalReservas,    '#EDE9FE','#7C3AED'],
      ['⏰','Próximas',         $proximasReservas, '#DBEAFE','#2563EB'],
      ['✅','Confirmadas',      $confirmadas,      '#D1FAE5','#059669'],
      ['❌','Canceladas',       $canceladas,       '#FEE2E2','#DC2626'],
    ] as [$ico,$lbl,$val,$bg,$col]): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
           style="background:<?= $bg ?>;"><?= $ico ?></div>
      <div>
        <p class="text-xs text-gray-500 font-body"><?= $lbl ?></p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $val ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Filtros -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 pt-5 pb-0 border-b border-gray-100">
      <!-- Tabs -->
      <div class="flex items-center gap-1 overflow-x-auto">
        <?php foreach (array_merge([['nombre_estado'=>'todas']], $estados) as $tab):
          $val = $tab['nombre_estado'];
          $lbl = $val === 'todas' ? 'Todas' : ucfirst($val);
          $act = ($filtro_estado === $val);
        ?>
        <a href="?estado=<?= urlencode($val) ?><?= $filtro_fecha ? '&fecha='.urlencode($filtro_fecha) : '' ?>"
           class="px-4 py-3 text-sm font-body whitespace-nowrap transition border-b-2
                  <?= $act ? 'border-retro-red text-retro-red font-bold' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
          <?= $lbl ?>
        </a>
        <?php endforeach; ?>
      </div>
      <!-- Filtro fecha -->
      <div class="flex items-center gap-2 pb-3">
        <div class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50">
          <i class="fas fa-calendar text-gray-400"></i>
          <input type="date" value="<?= htmlspecialchars($filtro_fecha) ?>"
                 class="bg-transparent outline-none text-sm font-body"
                 onchange="aplicarFecha(this.value)">
        </div>
        <?php if ($filtro_fecha): ?>
        <a href="?estado=<?= urlencode($filtro_estado) ?>"
           class="text-xs text-gray-400 hover:text-red-500 px-2 py-2 rounded-lg hover:bg-red-50 transition">
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
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Fecha</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Hora</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Mesa</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Personas</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Estado</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider text-center">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 font-body">
          <?php if (empty($reservas)): ?>
          <tr>
            <td colspan="6" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center gap-3 text-gray-400">
                <i class="fas fa-calendar-xmark text-5xl"></i>
                <p class="text-lg font-heading">Sin reservas</p>
                <p class="text-sm">No tienes reservas con los filtros seleccionados.</p>
                <button onclick="document.getElementById('modalCrear').classList.add('show')"
                  class="mt-2 bg-retro-red hover:bg-red-700 text-white font-heading px-5 py-2 rounded-xl text-sm transition">
                  <i class="fas fa-plus mr-1"></i> Crear reserva
                </button>
              </div>
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($reservas as $r):
            $b    = badgeReserva($r['estado']);
            $hora = date('g:i A', strtotime($r['hora_reserva']));
            $fecha = date('d/m/Y', strtotime($r['fecha_reserva']));
            $diaKey = date('l', strtotime($r['fecha_reserva']));
            $esHoy  = (date('Y-m-d') === $r['fecha_reserva']);
            $puedeCancel = !in_array(strtolower($r['estado']), ['cancelada','completada']);
          ?>
          <tr class="hover:bg-gray-50 transition">
            <td class="px-5 py-4">
              <p class="font-semibold text-gray-800 text-sm"><?= $fecha ?></p>
              <p class="text-xs text-gray-400"><?= ($dias[$diaKey] ?? $diaKey) . ($esHoy ? ' · Hoy' : '') ?></p>
            </td>
            <td class="px-5 py-4 text-sm text-gray-700 font-semibold"><?= $hora ?></td>
            <td class="px-5 py-4">
              <p class="font-semibold text-gray-800 text-sm">Mesa <?= $r['numero_mesa'] ?? '—' ?></p>
              <p class="text-xs text-gray-400">Cap. <?= $r['capacidad'] ?? '—' ?></p>
            </td>
            <td class="px-5 py-4 text-sm text-gray-700">
              <i class="fas fa-users text-gray-400 text-xs mr-1"></i><?= $r['numero_personas'] ?> personas
            </td>
            <td class="px-5 py-4">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                    style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $b['dot'] ?>;"></span>
                <?= $b['label'] ?>
              </span>
            </td>
            <td class="px-5 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
              <?php
              $estado_lower = strtolower($r['estado']);
              $esActiva     = !in_array($estado_lower, ['cancelada','completada','no asistio']);
              $esEliminable = in_array($estado_lower, ['cancelada','completada','no asistio']);
              ?>
              <?php if ($esActiva): ?>
                <!-- Solo editar en reservas activas -->
                <button onclick="abrirEditar(<?= htmlspecialchars(json_encode($r)) ?>)"
                  class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-blue-100 text-gray-500 hover:text-blue-600 transition flex items-center justify-center"
                  title="Editar reserva">
                  <i class="fas fa-pen text-xs"></i>
                </button>
              <?php elseif ($esEliminable): ?>
                <!-- Solo eliminar en canceladas/completadas -->
                <form method="POST" onsubmit="return confirm('¿Eliminar esta reserva? No se puede deshacer.');" style="display:inline;">
                  <input type="hidden" name="accion"     value="eliminar">
                  <input type="hidden" name="id_reserva" value="<?= $r['id_reserva'] ?>">
                  <button type="submit"
                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-600 transition flex items-center justify-center"
                    title="Eliminar reserva">
                    <i class="fas fa-trash text-xs"></i>
                  </button>
                </form>
              <?php else: ?>
                <span class="text-xs text-gray-300">—</span>
              <?php endif; ?>
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
        Mostrando <?= $total > 0 ? min(($pagina-1)*$por_pagina+1, $total) : 0 ?>
        a <?= min($pagina*$por_pagina, $total) ?> de <?= $total ?> reservas
      </p>
      <div class="flex items-center gap-1">
        <?php if ($pagina > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$pagina-1])) ?>"
           class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition">Anterior</a>
        <?php endif; ?>
        <?php for ($p2 = max(1,$pagina-2); $p2 <= min($totalPaginas,$pagina+2); $p2++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$p2])) ?>"
           class="w-9 h-9 flex items-center justify-center text-sm rounded-lg border transition
                  <?= $p2===$pagina ? 'bg-retro-red text-white border-retro-red font-bold' : 'border-gray-200 hover:bg-white text-gray-600' ?>">
          <?= $p2 ?>
        </a>
        <?php endfor; ?>
        <?php if ($pagina < $totalPaginas): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$pagina+1])) ?>"
           class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition">Siguiente</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CREAR RESERVA -->
<div id="modalCrear" class="modal-bg" onclick="cerrarSiAfuera(event,'modalCrear')">
  <div class="modal-box" style="max-width:540px;">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
      <h3 class="text-xl font-heading font-bold text-gray-800">
        <i class="fas fa-calendar-plus text-retro-red mr-2"></i>Nueva Reserva
      </h3>
      <button onclick="document.getElementById('modalCrear').classList.remove('show')"
        class="text-gray-400 hover:text-red-500 transition text-lg">✕</button>
    </div>
    <form method="POST" id="formCrear" class="p-6 space-y-4">
      <input type="hidden" name="accion" value="crear">
      <input type="hidden" name="id_mesa" id="crear_id_mesa" required>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
          <input type="date" name="fecha_reserva" id="crear_fecha" required value="<?= date('Y-m-d') ?>"
                 min="<?= date('Y-m-d') ?>"
                 onchange="actualizarDisponibilidad('crear')"
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Hora <span class="text-red-500">*</span></label>
          <input type="time" name="hora_reserva" id="crear_hora" required value="12:00"
                 onchange="actualizarDisponibilidad('crear')"
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Personas <span class="text-red-500">*</span></label>
          <input type="number" name="numero_personas" min="1" max="20" value="2" required
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
      </div>

      <!-- Selector visual de mesas -->
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">
          Mesa <span class="text-red-500">*</span>
          <span class="text-xs font-normal text-gray-400 ml-1">— selecciona una mesa disponible</span>
        </label>
        <div class="flex items-center gap-4 mb-3 text-xs font-body text-gray-500">
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span> Disponible</span>
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span> Ocupada</span>
        </div>
        <div id="mesas_crear" class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto pr-1">
          <?php foreach ($mesas as $m): ?>
          <button type="button"
            data-id="<?= $m['id_mesa'] ?>"
            data-cap="<?= $m['capacidad'] ?>"
            data-num="<?= $m['numero_mesa'] ?>"
            onclick="seleccionarMesa(this,'crear')"
            class="mesa-btn relative flex flex-col items-center justify-center gap-1 p-3 rounded-xl border-2 border-gray-200 bg-white hover:border-retro-red transition text-sm font-body cursor-pointer text-center">
            <i class="fas fa-chair text-gray-400 text-lg"></i>
            <span class="font-bold text-gray-700">Mesa <?= $m['numero_mesa'] ?></span>
            <span class="text-xs text-gray-400">Cap. <?= $m['capacidad'] ?></span>
          </button>
          <?php endforeach; ?>
        </div>
        <div id="crear_mesa_error" class="text-red-500 text-xs mt-1 hidden">Por favor selecciona una mesa disponible.</div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalCrear').classList.remove('show')"
          class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">Cancelar</button>
        <button type="submit" id="btnCrearReserva"
          class="px-5 py-2 bg-retro-red hover:bg-red-700 text-white rounded-xl font-heading text-sm shadow transition">
          Reservar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function aplicarFecha(val) {
  const url = new URL(window.location.href);
  val ? url.searchParams.set('fecha', val) : url.searchParams.delete('fecha');
  url.searchParams.set('pagina', 1);
  window.location.href = url.toString();
}
function cerrarSiAfuera(e, id) {
  if (e.target === document.getElementById(id))
    document.getElementById(id).classList.remove('show');
}

// ── Selección visual de mesa ──────────────────────────────────────────────────
function seleccionarMesa(btn, ctx) {
  if (btn.dataset.ocupada === '1') return; // no permitir ocupadas
  const container = document.getElementById('mesas_' + ctx);
  container.querySelectorAll('.mesa-btn').forEach(b => {
    b.classList.remove('border-retro-red', 'bg-red-50', 'mesa-seleccionada');
  });
  btn.classList.add('border-retro-red', 'bg-red-50', 'mesa-seleccionada');
  document.getElementById(ctx === 'crear' ? 'crear_id_mesa' : 'edit_id_mesa').value = btn.dataset.id;
  document.getElementById(ctx + '_mesa_error').classList.add('hidden');
}

// ── Consultar disponibilidad de mesas vía AJAX ────────────────────────────────
function actualizarDisponibilidad(ctx) {
  const fecha     = document.getElementById(ctx === 'crear' ? 'crear_fecha' : 'edit_fecha').value;
  const hora      = document.getElementById(ctx === 'crear' ? 'crear_hora'  : 'edit_hora').value;
  const mesaActual = ctx === 'editar' ? document.getElementById('edit_id_mesa').value : null;
  const reservaId  = ctx === 'editar' ? document.getElementById('edit_id').value : null;

  if (!fecha || !hora) return;

  const container = document.getElementById('mesas_' + ctx);

  fetch(`<?= rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') ?>/Controllers/ReservaDisponibilidadController.php?fecha=${encodeURIComponent(fecha)}&hora=${encodeURIComponent(hora)}&excluir=${reservaId || ''}`)
    .then(r => r.json())
    .then(data => {
      container.querySelectorAll('.mesa-btn').forEach(btn => {
        const id   = btn.dataset.id;
        const info = data[id];
        const ocupada = info && info.ocupada == '1';
        btn.dataset.ocupada = ocupada ? '1' : '0';

        if (ocupada) {
          btn.classList.remove('border-gray-200','border-retro-red','bg-white','bg-red-50','mesa-seleccionada','hover:border-retro-red','cursor-pointer');
          btn.classList.add('border-red-300','bg-red-50','cursor-not-allowed','opacity-75');
          btn.querySelector('i').className = 'fas fa-lock text-red-400 text-lg';
          const label = btn.querySelector('.mesa-ocupada-label');
          if (!label) {
            const span = document.createElement('span');
            span.className = 'mesa-ocupada-label text-xs font-bold text-red-500 mt-0.5';
            span.textContent = 'Ocupada';
            btn.appendChild(span);
          }
          // Si esta mesa estaba seleccionada, deseleccionar
          if (ctx === 'editar' && mesaActual === id) {
            document.getElementById('edit_id_mesa').value = '';
          }
          if (ctx === 'crear' && document.getElementById('crear_id_mesa').value === id) {
            document.getElementById('crear_id_mesa').value = '';
          }
        } else {
          btn.classList.remove('border-red-300','cursor-not-allowed','opacity-75');
          btn.classList.add('border-gray-200','hover:border-retro-red','cursor-pointer');
          btn.querySelector('i').className = 'fas fa-chair text-gray-400 text-lg';
          const label = btn.querySelector('.mesa-ocupada-label');
          if (label) label.remove();
          btn.dataset.ocupada = '0';
          // Restaurar selección si es la mesa actual en edición
          if (ctx === 'editar' && mesaActual === id) {
            seleccionarMesa(btn, ctx);
          }
        }
      });
    })
    .catch(() => {}); // falla silenciosa — el servidor validará igualmente
}

// ── Validación al enviar el formulario ────────────────────────────────────────
document.getElementById('formCrear').addEventListener('submit', function(e) {
  const mesaId = document.getElementById('crear_id_mesa').value;
  if (!mesaId) {
    e.preventDefault();
    document.getElementById('crear_mesa_error').classList.remove('hidden');
  }
});
document.getElementById('formEditar').addEventListener('submit', function(e) {
  const mesaId = document.getElementById('edit_id_mesa').value;
  if (!mesaId) {
    e.preventDefault();
    document.getElementById('editar_mesa_error').classList.remove('hidden');
  }
});

// ── Abrir modal de edición ────────────────────────────────────────────────────
function abrirEditar(r) {
  document.getElementById('edit_id').value       = r.id_reserva;
  document.getElementById('edit_personas').value = r.numero_personas;
  document.getElementById('edit_fecha').value    = r.fecha_reserva;
  document.getElementById('edit_hora').value     = r.hora_reserva.substring(0,5);
  document.getElementById('edit_id_mesa').value  = r.id_mesa || '';

  document.getElementById('modalEditar').classList.add('show');

  // Cargar disponibilidad y luego marcar la mesa actual como seleccionada
  const fecha = r.fecha_reserva;
  const hora  = r.hora_reserva.substring(0,5);
  const reservaId = r.id_reserva;

  fetch(`<?= rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') ?>/Controllers/ReservaDisponibilidadController.php?fecha=${encodeURIComponent(fecha)}&hora=${encodeURIComponent(hora)}&excluir=${reservaId}`)
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById('mesas_editar');
      container.querySelectorAll('.mesa-btn').forEach(btn => {
        const id   = btn.dataset.id;
        const info = data[id];
        const ocupada = info && info.ocupada == '1';
        btn.dataset.ocupada = ocupada ? '1' : '0';

        if (ocupada) {
          btn.classList.remove('border-gray-200','border-retro-red','bg-white','bg-red-50','mesa-seleccionada','hover:border-retro-red','cursor-pointer');
          btn.classList.add('border-red-300','bg-red-50','cursor-not-allowed','opacity-75');
          btn.querySelector('i').className = 'fas fa-lock text-red-400 text-lg';
          const label = btn.querySelector('.mesa-ocupada-label');
          if (!label) {
            const span = document.createElement('span');
            span.className = 'mesa-ocupada-label text-xs font-bold text-red-500 mt-0.5';
            span.textContent = 'Ocupada';
            btn.appendChild(span);
          }
        } else {
          btn.classList.remove('border-red-300','cursor-not-allowed','opacity-75');
          btn.classList.add('border-gray-200','hover:border-retro-red','cursor-pointer');
          btn.querySelector('i').className = 'fas fa-chair text-gray-400 text-lg';
          const label = btn.querySelector('.mesa-ocupada-label');
          if (label) label.remove();
          // Marcar la mesa de la reserva como seleccionada
          if (id == r.id_mesa) {
            seleccionarMesa(btn, 'editar');
          }
        }
      });
    })
    .catch(() => {
      // Sin AJAX: marcar simplemente la mesa actual
      const container = document.getElementById('mesas_editar');
      container.querySelectorAll('.mesa-btn').forEach(btn => {
        if (btn.dataset.id == r.id_mesa) seleccionarMesa(btn, 'editar');
      });
    });
}

// Cargar disponibilidad inicial al abrir el modal crear
document.querySelector('[onclick*="modalCrear"]')?.addEventListener('click', function() {
  setTimeout(() => actualizarDisponibilidad('crear'), 100);
});
</script>

<!-- MODAL EDITAR RESERVA -->
<div id="modalEditar" class="modal-bg" onclick="cerrarSiAfuera(event,'modalEditar')">
  <div class="modal-box" style="max-width:540px;">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
      <h3 class="text-xl font-heading font-bold text-gray-800">
        <i class="fas fa-pen text-blue-500 mr-2"></i>Editar Reserva
      </h3>
      <button onclick="document.getElementById('modalEditar').classList.remove('show')"
        class="text-gray-400 hover:text-red-500 transition text-lg">✕</button>
    </div>
    <form method="POST" id="formEditar" class="p-6 space-y-4">
      <input type="hidden" name="accion"     value="editar">
      <input type="hidden" name="id_reserva" id="edit_id">
      <input type="hidden" name="id_mesa"    id="edit_id_mesa" required>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
          <input type="date" name="fecha_reserva" id="edit_fecha" required min="<?= date('Y-m-d') ?>"
                 onchange="actualizarDisponibilidad('editar')"
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Hora <span class="text-red-500">*</span></label>
          <input type="time" name="hora_reserva" id="edit_hora" required
                 onchange="actualizarDisponibilidad('editar')"
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Personas <span class="text-red-500">*</span></label>
          <input type="number" name="numero_personas" id="edit_personas" min="1" max="20" required
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
      </div>

      <!-- Selector visual de mesas (editar) -->
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">
          Mesa <span class="text-red-500">*</span>
          <span class="text-xs font-normal text-gray-400 ml-1">— mesas en rojo ya están reservadas</span>
        </label>
        <div class="flex items-center gap-4 mb-3 text-xs font-body text-gray-500">
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span> Disponible</span>
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span> Ocupada</span>
        </div>
        <div id="mesas_editar" class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto pr-1">
          <?php foreach ($mesas as $m): ?>
          <button type="button"
            data-id="<?= $m['id_mesa'] ?>"
            data-cap="<?= $m['capacidad'] ?>"
            data-num="<?= $m['numero_mesa'] ?>"
            onclick="seleccionarMesa(this,'editar')"
            class="mesa-btn relative flex flex-col items-center justify-center gap-1 p-3 rounded-xl border-2 border-gray-200 bg-white hover:border-retro-red transition text-sm font-body cursor-pointer text-center">
            <i class="fas fa-chair text-gray-400 text-lg"></i>
            <span class="font-bold text-gray-700">Mesa <?= $m['numero_mesa'] ?></span>
            <span class="text-xs text-gray-400">Cap. <?= $m['capacidad'] ?></span>
          </button>
          <?php endforeach; ?>
        </div>
        <div id="editar_mesa_error" class="text-red-500 text-xs mt-1 hidden">Por favor selecciona una mesa disponible.</div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalEditar').classList.remove('show')"
          class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">Cancelar</button>
        <button type="submit"
          class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-heading text-sm shadow transition">
          Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
