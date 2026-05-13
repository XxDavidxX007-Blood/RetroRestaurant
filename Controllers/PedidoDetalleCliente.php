<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [3,'3','cliente'])) {
    http_response_code(403); echo '<p class="text-red-500 text-center py-4">Acceso denegado.</p>'; exit;
}

$id   = (int)($_GET['id']   ?? 0);
$modo = $_GET['modo'] ?? 'ver'; // 'ver' o 'editar'
if (!$id) { echo '<p class="text-gray-500 text-center py-4">ID inválido.</p>'; exit; }

require_once __DIR__ . '/../config/database.php';
$db = (new database())->conectar();

$stmtC = $db->prepare("SELECT id_cliente FROM cliente WHERE id_usuario=:u LIMIT 1");
$stmtC->execute([':u' => $_SESSION['usuario']['id_usuario']]);
$rowC = $stmtC->fetch(PDO::FETCH_ASSOC);
$id_cliente = $rowC ? $rowC['id_cliente'] : null;

$stmtP = $db->prepare("
    SELECT p.id_pedido, p.fecha_pedido, ep.nombre_estado AS estado,
           tp.nombre_tipo AS tipo, IFNULL(f.total_factura,0) AS total
    FROM pedido p
    JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
    JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
    LEFT JOIN factura  f  ON f.id_pedido        = p.id_pedido
    WHERE p.id_pedido = :id AND p.id_cliente = :c
");
$stmtP->execute([':id'=>$id, ':c'=>$id_cliente]);
$pedido = $stmtP->fetch(PDO::FETCH_ASSOC);
if (!$pedido) { echo '<p class="text-gray-500 text-center py-8">Pedido no encontrado.</p>'; exit; }

$stmtD = $db->prepare("
    SELECT dp.id_detalle, dp.cantidad, dp.precio_unitario, dp.subtotal, pr.nombre
    FROM detalle_pedido dp
    JOIN producto pr ON dp.id_producto = pr.id_producto
    WHERE dp.id_pedido = :id
");
$stmtD->execute([':id' => $id]);
$detalle = $stmtD->fetchAll(PDO::FETCH_ASSOC);

// ── MODO EDITAR ───────────────────────────────────────────────
if ($modo === 'editar') {
    $esPendiente = strtolower($pedido['estado']) === 'pendiente';
    if (!$esPendiente) {
        echo '<p class="text-center text-gray-500 py-4 text-sm">Solo puedes editar pedidos pendientes.</p>';
        exit;
    }
    ?>
    <div class="space-y-3 font-body text-sm">
      <?php if (empty($detalle)): ?>
      <p class="text-gray-400 text-center py-4">Sin productos.</p>
      <?php else: ?>
      <?php foreach ($detalle as $item): ?>
      <div class="prod-edit-row flex items-center gap-3 p-3 bg-gray-50 rounded-xl"
           data-detalle="<?= $item['id_detalle'] ?>"
           data-pedido="<?= $id ?>">
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($item['nombre']) ?></p>
          <p class="text-xs text-gray-400">
            $<?= number_format($item['precio_unitario'],0,',','.') ?> c/u
            · Total: <span class="sub-display font-semibold text-gray-700">
              $<?= number_format($item['subtotal'],0,',','.') ?>
            </span>
          </p>
        </div>
        <!-- Cantidad -->
        <div class="flex items-center gap-2 flex-shrink-0">
          <button type="button" onclick="cambiarCantEdit(this,-1)"
            class="w-7 h-7 rounded-lg border border-gray-200 bg-white hover:bg-gray-100 flex items-center justify-center font-bold text-gray-600 transition">−</button>
          <input type="number" name="cantidad[<?= $item['id_detalle'] ?>]"
                 value="<?= $item['cantidad'] ?>" min="1"
                 data-precio="<?= $item['precio_unitario'] ?>"
                 class="w-12 text-center border border-gray-200 rounded-lg py-1 text-sm font-bold outline-none focus:border-yellow-400"
                 oninput="actualizarSub(this)">
          <button type="button" onclick="cambiarCantEdit(this,1)"
            class="w-7 h-7 rounded-lg border border-gray-200 bg-white hover:bg-gray-100 flex items-center justify-center font-bold text-gray-600 transition">+</button>
        </div>
        <!-- Eliminar producto — usa AJAX para no anidar forms -->
        <button type="button"
          onclick="eliminarDetalle(this)"
          class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-400 hover:text-red-600 flex items-center justify-center transition flex-shrink-0"
          title="Quitar producto">
          <i class="fas fa-trash text-xs"></i>
        </button>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php
    exit;
}

// ── MODO VER ──────────────────────────────────────────────────
$badges = [
    'pendiente'      => ['bg'=>'#FEF3C7','color'=>'#D97706'],
    'en_preparacion' => ['bg'=>'#DBEAFE','color'=>'#2563EB'],
    'listo'          => ['bg'=>'#D1FAE5','color'=>'#059669'],
    'entregado'      => ['bg'=>'#EDE9FE','color'=>'#7C3AED'],
    'cancelado'      => ['bg'=>'#FEE2E2','color'=>'#DC2626'],
];
$key = strtolower(str_replace(' ','_',$pedido['estado']));
$b   = $badges[$key] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280'];
?>
<div class="space-y-4 font-body text-sm">
  <div class="grid grid-cols-2 gap-3">
    <div class="bg-gray-50 rounded-xl p-4">
      <p class="text-xs text-gray-400 mb-1">Fecha</p>
      <p class="font-semibold text-gray-800"><?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?></p>
    </div>
    <div class="bg-gray-50 rounded-xl p-4">
      <p class="text-xs text-gray-400 mb-1">Tipo</p>
      <p class="font-semibold text-gray-800"><?= htmlspecialchars($pedido['tipo']) ?></p>
    </div>
    <div class="bg-gray-50 rounded-xl p-4 col-span-2">
      <p class="text-xs text-gray-400 mb-1">Estado</p>
      <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold"
            style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
        <?= ucfirst(str_replace('_',' ',$pedido['estado'])) ?>
      </span>
    </div>
  </div>
  <div>
    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Productos</p>
    <?php if (empty($detalle)): ?>
    <p class="text-gray-400 text-center py-4">Sin productos registrados.</p>
    <?php else: ?>
    <div class="border border-gray-100 rounded-xl overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-xs text-gray-500 font-heading uppercase">Producto</th>
            <th class="px-4 py-3 text-xs text-gray-500 font-heading uppercase text-center">Cant.</th>
            <th class="px-4 py-3 text-xs text-gray-500 font-heading uppercase text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($detalle as $item): ?>
          <tr>
            <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($item['nombre']) ?></td>
            <td class="px-4 py-3 text-center text-gray-600"><?= $item['cantidad'] ?></td>
            <td class="px-4 py-3 text-right font-semibold text-gray-800">
              $<?= number_format($item['subtotal'],0,',','.') ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <div class="flex justify-between items-center bg-gray-800 text-white rounded-xl px-5 py-4">
    <span class="font-heading font-bold">TOTAL</span>
    <span class="font-heading font-bold text-xl text-green-400">
      $<?= number_format($pedido['total'],0,',','.') ?>
    </span>
  </div>
</div>
