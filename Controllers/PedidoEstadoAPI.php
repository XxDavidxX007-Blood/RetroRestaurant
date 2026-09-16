<?php
/**
 * PedidoEstadoAPI.php — API JSON para consultar estados y KPIs de pedidos.
 *
 * GET ?kpis=1              → KPIs generales (admin)
 * GET ?id=X                → estado de un pedido específico
 * GET ?ids=1,2,3           → estados de varios pedidos
 */

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache');

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->conectar();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error']);
    exit;
}

$usuario = $_SESSION['usuario'];
$id_rol  = $usuario['id_rol'];

// ── KPIs para admin ───────────────────────────────────────────────────────────
if (isset($_GET['kpis'])) {
    if (!in_array($id_rol, [1,'1',2,'2'])) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }

    $pedidosHoy = (int)$db->query("
        SELECT COUNT(*) FROM pedido WHERE DATE(fecha_pedido) = CURDATE()
    ")->fetchColumn();

    $pedidosAyer = (int)$db->query("
        SELECT COUNT(*) FROM pedido WHERE DATE(fecha_pedido) = CURDATE() - INTERVAL 1 DAY
    ")->fetchColumn();

    $varPedidos = $pedidosAyer > 0
        ? round((($pedidosHoy - $pedidosAyer) / $pedidosAyer) * 100, 1)
        : ($pedidosHoy > 0 ? 100 : 0);

    $rows = $db->query("
        SELECT ep.nombre_estado, COUNT(p.id_pedido) AS total
        FROM estado_pedido ep
        LEFT JOIN pedido p ON p.id_estado_pedido = ep.id_estado_pedido
        GROUP BY ep.id_estado_pedido, ep.nombre_estado
        ORDER BY ep.id_estado_pedido
    ")->fetchAll(PDO::FETCH_ASSOC);

    $estados = [];
    foreach ($rows as $r) {
        $estados[$r['nombre_estado']] = (int)$r['total'];
    }

    echo json_encode([
        'pedidosHoy' => $pedidosHoy,
        'varPedidos' => $varPedidos,
        'estados'    => $estados,
        'ts'         => time(),
    ]);
    exit;
}

// ── Estado de un pedido específico ────────────────────────────────────────────
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("
        SELECT p.id_pedido, ep.nombre_estado AS estado, ep.id_estado_pedido,
               UNIX_TIMESTAMP(p.updated_at) AS ts
        FROM pedido p
        JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
        WHERE p.id_pedido = :id
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($row ?: ['error' => 'not_found']);
    exit;
}

// ── Estados de múltiples pedidos ──────────────────────────────────────────────
if (isset($_GET['ids'])) {
    $rawIds = explode(',', $_GET['ids']);
    $ids    = array_map('intval', array_filter($rawIds, 'is_numeric'));
    if (empty($ids)) {
        echo json_encode([]);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("
        SELECT p.id_pedido, ep.nombre_estado AS estado, ep.id_estado_pedido,
               UNIX_TIMESTAMP(p.updated_at) AS ts
        FROM pedido p
        JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
        WHERE p.id_pedido IN ($placeholders)
    ");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($rows as $r) {
        $result[$r['id_pedido']] = $r;
    }
    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'bad_request']);
