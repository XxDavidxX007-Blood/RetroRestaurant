<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador', 2, '2', 'empleado'])) {
    http_response_code(403);
    echo '<p class="text-red-500 text-center py-4">Acceso denegado.</p>';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo '<p class="text-red-500 text-center py-4">ID inválido.</p>';
    exit;
}

require_once __DIR__ . '/../../Controllers/PedidoController.php';

$controller = new PedidoController();
$data       = $controller->obtenerDetallePedido($id);
$pedido     = $data['pedido'];
$detalle    = $data['detalle'];

if (!$pedido) {
    echo '<p class="text-gray-500 text-center py-8">Pedido no encontrado.</p>';
    exit;
}

$b   = PedidoController::badgeEstado($pedido['estado']);
$num = str_pad($pedido['id_pedido'], 5, '0', STR_PAD_LEFT);
?>

<div class="space-y-5 font-body text-sm">

    <!-- Info general -->
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs text-gray-400 mb-1">Cliente</p>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($pedido['nombre_cliente']) ?></p>
            <p class="text-xs text-gray-500"><?= htmlspecialchars($pedido['telefono_cliente']) ?></p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs text-gray-400 mb-1">Tipo de pedido</p>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($pedido['tipo']) ?></p>
            <p class="text-xs text-gray-500">Mesero: <?= htmlspecialchars($pedido['mesero'] ?? '—') ?></p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs text-gray-400 mb-1">Fecha</p>
            <p class="font-semibold text-gray-800"><?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?></p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs text-gray-400 mb-1">Estado</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                  style="background:<?= $b['bg'] ?>; color:<?= $b['color'] ?>;">
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $b['dot'] ?>;"></span>
                <?= $b['label'] ?>
            </span>
        </div>
        <?php if (!empty($pedido['direccion_entrega'])): ?>
        <div class="bg-gray-50 rounded-xl p-4 col-span-2">
            <p class="text-xs text-gray-400 mb-1">Dirección de entrega</p>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($pedido['direccion_entrega']) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Productos -->
    <div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Productos del pedido</p>
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
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($item['producto']) ?></td>
                        <td class="px-4 py-3 text-center text-gray-600"><?= $item['cantidad'] ?></td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">
                            $<?= number_format($item['subtotal'], 0, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Total -->
    <div class="flex justify-between items-center bg-gray-800 text-white rounded-xl px-5 py-4">
        <span class="font-heading font-bold text-base">TOTAL</span>
        <span class="font-heading font-bold text-xl text-green-400">
            $<?= number_format($pedido['total'], 0, ',', '.') ?>
        </span>
    </div>

</div>
