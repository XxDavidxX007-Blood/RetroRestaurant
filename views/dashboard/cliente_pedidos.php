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
$titulo  = "MIS PEDIDOS";

require_once __DIR__ . '/../../config/database.php';
$db = (new database())->conectar();

// Obtener id_cliente
$stmtC = $db->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = :id LIMIT 1");
$stmtC->execute([':id' => $usuario['id_usuario']]);
$rowC = $stmtC->fetch(PDO::FETCH_ASSOC);
$id_cliente = $rowC ? $rowC['id_cliente'] : null;

// Manejar edición de pedido
$msg_ok  = '';
$msg_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'editar_pedido' && $id_cliente) {
        $id_pedido = (int)($_POST['id_pedido'] ?? 0);
        $id_tipo   = (int)($_POST['id_tipo_pedido'] ?? 0);
        $cantidades = $_POST['cantidad'] ?? [];   // array [id_detalle => cantidad]

        // Verificar que el pedido es del cliente y está pendiente
        $check = $db->prepare("
            SELECT p.id_pedido FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            WHERE p.id_pedido=:id AND p.id_cliente=:c AND ep.nombre_estado='pendiente'
        ");
        $check->execute([':id'=>$id_pedido,':c'=>$id_cliente]);

        if ($check->fetch()) {
            // Actualizar tipo
            if ($id_tipo) {
                $db->prepare("UPDATE pedido SET id_tipo_pedido=:t WHERE id_pedido=:id")
                   ->execute([':t'=>$id_tipo,':id'=>$id_pedido]);
            }
            // Actualizar cantidades
            foreach ($cantidades as $id_detalle => $cant) {
                $cant = max(1, (int)$cant);
                $stmtDet = $db->prepare("SELECT precio_unitario FROM detalle_pedido WHERE id_detalle=:d AND id_pedido=:p");
                $stmtDet->execute([':d'=>$id_detalle,':p'=>$id_pedido]);
                $row = $stmtDet->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $subtotal = $row['precio_unitario'] * $cant;
                    $db->prepare("UPDATE detalle_pedido SET cantidad=:c, subtotal=:s WHERE id_detalle=:d")
                       ->execute([':c'=>$cant,':s'=>$subtotal,':d'=>$id_detalle]);
                }
            }
            // Recalcular total en factura
            $nuevoTotal = $db->query("SELECT IFNULL(SUM(subtotal),0) FROM detalle_pedido WHERE id_pedido=$id_pedido")->fetchColumn();
            $db->prepare("UPDATE factura SET total_factura=:t WHERE id_pedido=:id")
               ->execute([':t'=>$nuevoTotal,':id'=>$id_pedido]);

            $msg_ok = 'Pedido actualizado correctamente.';
        } else {
            $msg_err = 'Solo puedes editar pedidos en estado pendiente.';
        }
    }

    if ($_POST['accion'] === 'eliminar_pedido' && $id_cliente) {
        $id_pedido = (int)($_POST['id_pedido'] ?? 0);
        // Solo puede eliminar pedidos cancelados o entregados
        $check = $db->prepare("
            SELECT p.id_pedido FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            WHERE p.id_pedido = :id AND p.id_cliente = :c
              AND ep.nombre_estado IN ('cancelado','entregado')
        ");
        $check->execute([':id'=>$id_pedido, ':c'=>$id_cliente]);
        if ($check->fetch()) {
            // Eliminar detalle, factura y pedido
            $db->prepare("DELETE FROM detalle_pedido WHERE id_pedido=:id")->execute([':id'=>$id_pedido]);
            $db->prepare("DELETE FROM factura WHERE id_pedido=:id")->execute([':id'=>$id_pedido]);
            $db->prepare("DELETE FROM pedido WHERE id_pedido=:id AND id_cliente=:c")->execute([':id'=>$id_pedido,':c'=>$id_cliente]);
            $msg_ok = 'Pedido eliminado correctamente.';
        } else {
            $msg_err = 'Solo puedes eliminar pedidos cancelados o entregados.';
        }
    }
    if ($_POST['accion'] === 'cancelar_pedido' && $id_cliente) {
        $id_pedido = (int)($_POST['id_pedido'] ?? 0);
        $check = $db->prepare("
            SELECT p.id_pedido FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido=ep.id_estado_pedido
            WHERE p.id_pedido=:id AND p.id_cliente=:c AND ep.nombre_estado='pendiente'
        ");
        $check->execute([':id'=>$id_pedido,':c'=>$id_cliente]);
        if ($check->fetch()) {
            $idCancelado = $db->query("SELECT id_estado_pedido FROM estado_pedido WHERE nombre_estado='cancelado' LIMIT 1")->fetchColumn();
            if ($idCancelado) {
                $db->prepare("UPDATE pedido SET id_estado_pedido=:e WHERE id_pedido=:id")
                   ->execute([':e'=>$idCancelado,':id'=>$id_pedido]);
                $msg_ok = 'Pedido cancelado.';
            }
        } else {
            $msg_err = 'Solo puedes cancelar pedidos pendientes.';
        }
    }

    if ($_POST['accion'] === 'eliminar_detalle' && $id_cliente) {
        $id_pedido  = (int)($_POST['id_pedido']  ?? 0);
        $id_detalle = (int)($_POST['id_detalle'] ?? 0);
        // Verificar que el pedido es del cliente y está pendiente
        $check = $db->prepare("
            SELECT p.id_pedido FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido=ep.id_estado_pedido
            WHERE p.id_pedido=:id AND p.id_cliente=:c AND ep.nombre_estado='pendiente'
        ");
        $check->execute([':id'=>$id_pedido,':c'=>$id_cliente]);
        if ($check->fetch()) {
            // No eliminar si es el único producto
            $count = $db->query("SELECT COUNT(*) FROM detalle_pedido WHERE id_pedido=$id_pedido")->fetchColumn();
            if ($count > 1) {
                $db->prepare("DELETE FROM detalle_pedido WHERE id_detalle=:d AND id_pedido=:p")
                   ->execute([':d'=>$id_detalle,':p'=>$id_pedido]);
                // Recalcular total
                $nuevoTotal = $db->query("SELECT IFNULL(SUM(subtotal),0) FROM detalle_pedido WHERE id_pedido=$id_pedido")->fetchColumn();
                $db->prepare("UPDATE factura SET total_factura=:t WHERE id_pedido=:id")
                   ->execute([':t'=>$nuevoTotal,':id'=>$id_pedido]);
                $msg_ok = 'Producto eliminado del pedido.';
            } else {
                $msg_err = 'No puedes eliminar el único producto. Cancela el pedido si ya no lo quieres.';
            }
        }
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_fecha  = $_GET['fecha']  ?? '';
$pagina        = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina    = 8;

// Query base
$where  = $id_cliente ? ["p.id_cliente = $id_cliente"] : ["1=0"];
$params = [];
if ($filtro_estado !== 'todos') {
    $where[] = "ep.nombre_estado = :estado";
    $params[':estado'] = $filtro_estado;
}
if ($filtro_fecha) {
    $where[] = "DATE(p.fecha_pedido) = :fecha";
    $params[':fecha'] = $filtro_fecha;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);
$offset   = ($pagina - 1) * $por_pagina;

// Total
$stmtT = $db->prepare("
    SELECT COUNT(*) FROM pedido p
    JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
    $whereSQL
");
$stmtT->execute($params);
$total        = (int)$stmtT->fetchColumn();
$totalPaginas = max(1, ceil($total / $por_pagina));

// Pedidos
$stmtP = $db->prepare("
    SELECT
        p.id_pedido,
        p.fecha_pedido,
        ep.nombre_estado        AS estado,
        ep.id_estado_pedido,
        tp.nombre_tipo          AS tipo,
        IFNULL(f.total_factura, 0) AS total,
        (SELECT IFNULL(SUM(dp.cantidad),0) FROM detalle_pedido dp WHERE dp.id_pedido = p.id_pedido) AS num_items
    FROM pedido p
    JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
    JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
    LEFT JOIN factura  f  ON f.id_pedido        = p.id_pedido
    $whereSQL
    ORDER BY p.id_pedido DESC
    LIMIT :lim OFFSET :off
");
foreach ($params as $k => $v) $stmtP->bindValue($k, $v);
$stmtP->bindValue(':lim', $por_pagina, PDO::PARAM_INT);
$stmtP->bindValue(':off', $offset,     PDO::PARAM_INT);
$stmtP->execute();
$pedidos = $stmtP->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$totalPedidos    = $id_cliente ? (int)$db->query("SELECT COUNT(*) FROM pedido WHERE id_cliente=$id_cliente")->fetchColumn() : 0;
$enProceso       = $id_cliente ? (int)$db->query("SELECT COUNT(*) FROM pedido p JOIN estado_pedido ep ON p.id_estado_pedido=ep.id_estado_pedido WHERE p.id_cliente=$id_cliente AND ep.nombre_estado IN ('pendiente','en_preparacion','listo')")->fetchColumn() : 0;
$entregados      = $id_cliente ? (int)$db->query("SELECT COUNT(*) FROM pedido p JOIN estado_pedido ep ON p.id_estado_pedido=ep.id_estado_pedido WHERE p.id_cliente=$id_cliente AND ep.nombre_estado='entregado'")->fetchColumn() : 0;
$totalGastado    = $id_cliente ? (float)$db->query("SELECT IFNULL(SUM(f.total_factura),0) FROM factura f JOIN pedido p ON f.id_pedido=p.id_pedido WHERE p.id_cliente=$id_cliente")->fetchColumn() : 0;

// Estados para tabs
$estados = $db->query("SELECT id_estado_pedido, nombre_estado FROM estado_pedido ORDER BY id_estado_pedido")->fetchAll(PDO::FETCH_ASSOC);

// Tipos de pedido para el modal de edición
$tipos = $db->query("SELECT id_tipo_pedido, nombre_tipo FROM tipo_pedido ORDER BY id_tipo_pedido")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

function badgePedido($estado) {
    $map = [
        'pendiente'      => ['bg'=>'#FEF3C7','color'=>'#D97706','dot'=>'#F59E0B'],
        'en_preparacion' => ['bg'=>'#DBEAFE','color'=>'#2563EB','dot'=>'#3B82F6'],
        'listo'          => ['bg'=>'#D1FAE5','color'=>'#059669','dot'=>'#10B981'],
        'entregado'      => ['bg'=>'#EDE9FE','color'=>'#7C3AED','dot'=>'#8B5CF6'],
        'cancelado'      => ['bg'=>'#FEE2E2','color'=>'#DC2626','dot'=>'#EF4444'],
    ];
    $key = strtolower(str_replace(' ','_',$estado));
    $b   = $map[$key] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','dot'=>'#9CA3AF'];
    $b['label'] = ucfirst(str_replace('_',' ',$estado));
    return $b;
}

function tipoIcon($tipo) {
    $t = strtolower($tipo);
    if (str_contains($t,'domicilio') || str_contains($t,'delivery')) return '🛵';
    if (str_contains($t,'llevar'))  return '🥡';
    return '🍽️';
}
?>

<style>
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes zoomIn { from{opacity:0;transform:scale(.9)} to{opacity:1;transform:scale(1)} }
.modal-bg  { display:none;position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.5);align-items:center;justify-content:center; }
.modal-bg.show { display:flex; }
.modal-box { background:#fff;border-radius:20px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;animation:zoomIn .3s cubic-bezier(.34,1.56,.64,1); }
</style>

<div class="space-y-6">

  <!-- Header -->
  <div>
    <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
      <i class="fas fa-receipt text-retro-red"></i> Mis Pedidos
    </h1>
    <p class="text-gray-500 font-body text-sm mt-1">Historial y seguimiento de todos tus pedidos.</p>
  </div>

  <?php if ($msg_ok): ?>
  <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2 font-body text-sm">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg_ok) ?>
  </div>
  <?php endif; ?>
  <?php if ($msg_err): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-2 font-body text-sm">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($msg_err) ?>
  </div>
  <?php endif; ?>

  <!-- KPIs -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach ([
      ['🧾','Total pedidos',  $totalPedidos, '#EDE9FE','#7C3AED'],
      ['⏳','En proceso',     $enProceso,    '#FEF3C7','#D97706'],
      ['✅','Entregados',     $entregados,   '#D1FAE5','#059669'],
      ['💰','Total gastado',  '$'.number_format($totalGastado,0,',','.'), '#DBEAFE','#2563EB'],
    ] as [$ico,$lbl,$val,$bg,$col]): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4"
         style="animation:fadeUp .4s ease both;">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
           style="background:<?= $bg ?>;"><?= $ico ?></div>
      <div>
        <p class="text-xs text-gray-500 font-body"><?= $lbl ?></p>
        <p class="text-xl font-heading font-bold text-gray-800"><?= $val ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabla -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Tabs + fecha -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 pt-5 pb-0 border-b border-gray-100">
      <div class="flex items-center gap-1 overflow-x-auto">
        <?php foreach (array_merge([['nombre_estado'=>'todos']], $estados) as $tab):
          $val = $tab['nombre_estado'];
          $lbl = $val === 'todos' ? 'Todos' : ucfirst(str_replace('_',' ',$val));
          $act = ($filtro_estado === $val);
        ?>
        <a href="?estado=<?= urlencode($val) ?><?= $filtro_fecha ? '&fecha='.urlencode($filtro_fecha) : '' ?>"
           class="px-4 py-3 text-sm font-body whitespace-nowrap transition border-b-2
                  <?= $act ? 'border-retro-red text-retro-red font-bold' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
          <?= $lbl ?>
        </a>
        <?php endforeach; ?>
      </div>
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
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Pedido</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Tipo</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Productos</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Fecha</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Total</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Estado</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider text-center">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 font-body">
          <?php if (empty($pedidos)): ?>
          <tr>
            <td colspan="7" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center gap-3 text-gray-400">
                <i class="fas fa-receipt text-5xl"></i>
                <p class="text-lg font-heading">Sin pedidos</p>
                <p class="text-sm">No tienes pedidos con los filtros seleccionados.</p>
                <a href="cliente_catalogo.php"
                   class="mt-2 bg-retro-red hover:bg-red-700 text-white font-heading px-5 py-2 rounded-xl text-sm transition">
                  <i class="fas fa-utensils mr-1"></i> Ver catálogo
                </a>
              </div>
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($pedidos as $p):
            $b   = badgePedido($p['estado']);
            $num = str_pad($p['id_pedido'],5,'0',STR_PAD_LEFT);
            $ico = tipoIcon($p['tipo']);
          ?>
          <tr class="hover:bg-gray-50 transition">
            <td class="px-5 py-4">
              <p class="font-bold text-gray-800 text-sm">#ORD-<?= $num ?></p>
              <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($p['fecha_pedido'])) ?></p>
            </td>
            <td class="px-5 py-4">
              <span class="text-lg"><?= $ico ?></span>
              <span class="text-xs text-gray-600 ml-1"><?= htmlspecialchars($p['tipo']) ?></span>
            </td>
            <td class="px-5 py-4">
              <span class="text-sm text-gray-700">
                <?= $p['num_items'] ?> <?= $p['num_items'] == 1 ? 'producto' : 'productos' ?>
              </span>
            </td>
            <td class="px-5 py-4 text-sm text-gray-600">
              <?= date('d/m/Y', strtotime($p['fecha_pedido'])) ?>
            </td>
            <td class="px-5 py-4">
              <span class="font-bold text-green-600 text-sm">
                $<?= number_format($p['total'],0,',','.') ?>
              </span>
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
                <button onclick="verDetalle(<?= $p['id_pedido'] ?>)"
                  class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-blue-100 text-gray-500 hover:text-blue-600 transition flex items-center justify-center"
                  title="Ver detalle">
                  <i class="fas fa-eye text-xs"></i>
                </button>
                <?php if (strtolower($p['estado']) === 'pendiente'): ?>
                <button onclick="abrirEditar(<?= $p['id_pedido'] ?>, '<?= addslashes($p['tipo']) ?>')"
                  class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-yellow-100 text-gray-500 hover:text-yellow-600 transition flex items-center justify-center"
                  title="Editar pedido">
                  <i class="fas fa-pen text-xs"></i>
                </button>
                <form method="POST" onsubmit="return confirm('¿Cancelar este pedido?')" style="display:inline;">
                  <input type="hidden" name="accion"    value="cancelar_pedido">
                  <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                  <button type="submit"
                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-600 transition flex items-center justify-center"
                    title="Cancelar pedido">
                    <i class="fas fa-times text-xs"></i>
                  </button>
                </form>
                <?php elseif (in_array(strtolower($p['estado']), ['cancelado','entregado'])): ?>
                <form method="POST" onsubmit="return confirm('¿Eliminar este pedido? No se puede deshacer.')" style="display:inline;">
                  <input type="hidden" name="accion"    value="eliminar_pedido">
                  <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                  <button type="submit"
                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-600 transition flex items-center justify-center"
                    title="Eliminar pedido">
                    <i class="fas fa-trash text-xs"></i>
                  </button>
                </form>
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
        Mostrando <?= $total > 0 ? min(($pagina-1)*$por_pagina+1,$total) : 0 ?>
        a <?= min($pagina*$por_pagina,$total) ?> de <?= $total ?> pedidos
      </p>
      <div class="flex items-center gap-1">
        <?php if ($pagina > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$pagina-1])) ?>"
           class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition">Anterior</a>
        <?php endif; ?>
        <?php for ($p2=max(1,$pagina-2);$p2<=min($totalPaginas,$pagina+2);$p2++): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$p2])) ?>"
           class="w-9 h-9 flex items-center justify-center text-sm rounded-lg border transition
                  <?= $p2===$pagina?'bg-retro-red text-white border-retro-red font-bold':'border-gray-200 hover:bg-white text-gray-600' ?>">
          <?= $p2 ?>
        </a>
        <?php endfor; ?>
        <?php if ($pagina < $totalPaginas): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$pagina+1])) ?>"
           class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition">Siguiente</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL EDITAR PEDIDO -->
<div id="modalEditar" class="modal-bg" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="modal-box" style="max-width:480px;">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50 sticky top-0">
      <h3 id="edit-titulo" class="text-xl font-heading font-bold text-gray-800">
        <i class="fas fa-pen text-yellow-500 mr-2"></i>Editar Pedido
      </h3>
      <button onclick="document.getElementById('modalEditar').classList.remove('show')"
        class="text-gray-400 hover:text-red-500 transition text-lg">✕</button>
    </div>
    <form method="POST" id="formEditar" class="p-6 space-y-5">
      <input type="hidden" name="accion"    value="editar_pedido">
      <input type="hidden" name="id_pedido" id="edit_id_pedido">

      <!-- Tipo de pedido -->
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de pedido</label>
        <div class="grid grid-cols-3 gap-2">
          <?php foreach ($tipos as $t):
            $iconos = ['mesa'=>'🍽️','domicilio'=>'🛵','para llevar'=>'🥡'];
            $ico = $iconos[strtolower($t['nombre_tipo'])] ?? '📦';
          ?>
          <label class="cursor-pointer">
            <input type="radio" name="id_tipo_pedido" value="<?= $t['id_tipo_pedido'] ?>"
                   class="hidden tipo-radio" data-nombre="<?= strtolower($t['nombre_tipo']) ?>">
            <div class="tipo-lbl text-center p-3 rounded-xl border-2 border-gray-200 bg-white transition hover:border-yellow-400"
                 onclick="selTipoEdit(this)">
              <div class="text-2xl mb-1"><?= $ico ?></div>
              <div class="text-xs font-bold text-gray-600"><?= ucfirst($t['nombre_tipo']) ?></div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Productos con cantidades -->
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Productos del pedido</label>
        <div id="edit-productos" class="space-y-2">
          <p class="text-gray-400 text-sm text-center py-4">
            <i class="fas fa-spinner fa-spin"></i> Cargando...
          </p>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
        <button type="button" onclick="document.getElementById('modalEditar').classList.remove('show')"
          class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">Cancelar</button>
        <button type="submit"
          class="px-5 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl font-heading text-sm shadow transition">
          <i class="fas fa-save mr-1"></i> Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL DETALLE PEDIDO -->
<div id="modalDetalle" class="modal-bg" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="modal-box">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50 sticky top-0">
      <h3 id="detalle-titulo" class="text-xl font-heading font-bold text-gray-800">
        <i class="fas fa-receipt text-retro-red mr-2"></i>Detalle del pedido
      </h3>
      <button onclick="document.getElementById('modalDetalle').classList.remove('show')"
        class="text-gray-400 hover:text-red-500 transition text-lg">✕</button>
    </div>
    <div id="detalle-body" class="p-6">
      <div class="flex items-center justify-center py-10 text-gray-400">
        <i class="fas fa-spinner fa-spin text-3xl"></i>
      </div>
    </div>
  </div>
</div>

<script>
function aplicarFecha(val) {
  const url = new URL(window.location.href);
  val ? url.searchParams.set('fecha', val) : url.searchParams.delete('fecha');
  url.searchParams.set('pagina', 1);
  window.location.href = url.toString();
}

// ── Editar pedido ─────────────────────────────────────────────
async function abrirEditar(id, tipoActual) {
  document.getElementById('edit_id_pedido').value = id;
  document.getElementById('edit-titulo').innerHTML =
    `<i class="fas fa-pen text-yellow-500 mr-2"></i>#ORD-${String(id).padStart(5,'0')}`;

  // Seleccionar tipo actual
  document.querySelectorAll('.tipo-lbl').forEach(el => {
    el.style.borderColor = '#e2e8f0'; el.style.background = '#fff';
  });
  document.querySelectorAll('.tipo-radio').forEach(r => {
    if (r.dataset.nombre === tipoActual.toLowerCase()) {
      r.checked = true;
      r.nextElementSibling.style.borderColor = '#EAB308';
      r.nextElementSibling.style.background  = '#FEFCE8';
    }
  });

  // Cargar productos vía AJAX
  const cont = document.getElementById('edit-productos');
  cont.innerHTML = '<p class="text-gray-400 text-sm text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>';
  document.getElementById('modalEditar').classList.add('show');

  try {
    const res  = await fetch('../../Controllers/PedidoDetalleCliente.php?id=' + id + '&modo=editar');
    const html = await res.text();
    cont.innerHTML = html;
  } catch(e) {
    cont.innerHTML = '<p class="text-red-500 text-sm text-center py-4">Error al cargar productos.</p>';
  }
}

function selTipoEdit(el) {
  document.querySelectorAll('.tipo-lbl').forEach(l => {
    l.style.borderColor = '#e2e8f0'; l.style.background = '#fff';
  });
  el.style.borderColor = '#EAB308';
  el.style.background  = '#FEFCE8';
  el.previousElementSibling.checked = true;
}

function cambiarCantEdit(input, delta) {
  let v = parseInt(input.value) + delta;
  if (v < 1) v = 1;
  input.value = v;
  // Actualizar subtotal mostrado
  const precio = parseFloat(input.dataset.precio);
  const sub    = input.closest('.prod-edit-row').querySelector('.sub-display');
  if (sub) sub.textContent = '$' + (precio * v).toLocaleString('es-CO');
}

// ── Funciones para editar cantidades (disponibles globalmente) ─
function cambiarCantEdit(btn, delta) {
  const row   = btn.closest('.prod-edit-row');
  const input = row.querySelector('input[type="number"]');
  let v = Math.max(1, (parseInt(input.value) || 1) + delta);
  input.value = v;
  actualizarSub(input);
}
function actualizarSub(input) {
  const v      = Math.max(1, parseInt(input.value) || 1);
  input.value  = v;
  const precio = parseFloat(input.dataset.precio);
  const sub    = input.closest('.prod-edit-row').querySelector('.sub-display');
  if (sub) sub.textContent = '$' + (precio * v).toLocaleString('es-CO');
}

async function eliminarDetalle(btn) {
  if (!confirm('¿Quitar este producto del pedido?')) return;
  const row       = btn.closest('.prod-edit-row');
  const idDetalle = row.dataset.detalle;
  const idPedido  = row.dataset.pedido;

  const fd = new FormData();
  fd.append('accion',     'eliminar_detalle');
  fd.append('id_pedido',  idPedido);
  fd.append('id_detalle', idDetalle);

  const res  = await fetch('cliente_pedidos.php', { method:'POST', body:fd });
  const text = await res.text();

  // Si hay error lo mostramos, si no recargamos los productos
  if (text.includes('Error') || text.includes('error')) {
    alert('No puedes eliminar el único producto. Cancela el pedido si ya no lo quieres.');
  } else {
    // Recargar la lista de productos en el modal
    const idPed = document.getElementById('edit_id_pedido').value;
    const cont  = document.getElementById('edit-productos');
    cont.innerHTML = '<p class="text-gray-400 text-sm text-center py-2"><i class="fas fa-spinner fa-spin"></i></p>';
    fetch('../../Controllers/PedidoDetalleCliente.php?id=' + idPed + '&modo=editar')
      .then(r => r.text())
      .then(html => { cont.innerHTML = html; });
  }
}

// ── Ver detalle ───────────────────────────────────────────────
function verDetalle(id) {
  const modal  = document.getElementById('modalDetalle');
  const body   = document.getElementById('detalle-body');
  const titulo = document.getElementById('detalle-titulo');
  titulo.innerHTML = `<i class="fas fa-receipt text-retro-red mr-2"></i>#ORD-${String(id).padStart(5,'0')}`;
  body.innerHTML   = '<div class="flex items-center justify-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-3xl"></i></div>';
  modal.classList.add('show');
  fetch('../../Controllers/PedidoDetalleCliente.php?id=' + id)
    .then(r => r.text())
    .then(html => { body.innerHTML = html; })
    .catch(() => { body.innerHTML = '<p class="text-center text-red-500 py-8">Error al cargar el detalle.</p>'; });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
