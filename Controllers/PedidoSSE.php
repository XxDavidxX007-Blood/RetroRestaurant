<?php
/**
 * PedidoSSE.php — Server-Sent Events endpoint para sincronización en tiempo real de pedidos.
 *
 * Parámetros GET:
 *   ?modo=admin                  → envía eventos de TODOS los pedidos (admin)
 *   ?modo=cliente&id_cliente=X   → envía solo eventos del cliente X
 *   ?desde=TIMESTAMP             → solo envía cambios ocurridos después de este timestamp Unix
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo "data: {\"error\":\"unauthenticated\"}\n\n";
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Cabeceras SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Desactiva buffering en Nginx
header('Connection: keep-alive');

// Desactivar compresión y buffers de salida
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
ini_set('zlib.output_compression', '0');
ini_set('output_buffering', '0');

// Limpiar cualquier buffer existente
while (ob_get_level()) {
    ob_end_flush();
}

$modo       = $_GET['modo']       ?? 'admin';
$id_cliente = (int)($_GET['id_cliente'] ?? 0);
$desde      = (int)($_GET['desde']      ?? time() - 5); // Por defecto, últimos 5 segundos

$usuario   = $_SESSION['usuario'];
$id_rol    = $usuario['id_rol'];

// Validación de permisos
if ($modo === 'admin' && !in_array($id_rol, [1, '1', 2, '2'])) {
    echo "data: {\"error\":\"forbidden\"}\n\n";
    flush();
    exit;
}
if ($modo === 'cliente' && !in_array($id_rol, [3, '3', 2, '2'])) {
    echo "data: {\"error\":\"forbidden\"}\n\n";
    flush();
    exit;
}

// Conectar a BD
try {
    $db = (new Database())->conectar();
} catch (Exception $e) {
    echo "data: {\"error\":\"db\"}\n\n";
    flush();
    exit;
}

// Enviar heartbeat inicial para confirmar conexión
echo "event: connected\n";
echo "data: {\"status\":\"ok\",\"modo\":\"" . $modo . "\",\"ts\":" . time() . "}\n\n";
flush();

$intervalo  = 3;  // segundos entre polls
$maxTiempo  = 50; // segundos máximo antes de cerrar (el cliente reconecta automáticamente)
$inicio     = time();
$ultimoCheck = $desde;

// ── Funciones de consulta ─────────────────────────────────────────────────────

function getPedidosCambiadosAdmin(PDO $db, int $desde): array {
    $stmt = $db->prepare("
        SELECT
            p.id_pedido,
            ep.nombre_estado   AS estado,
            ep.id_estado_pedido,
            UNIX_TIMESTAMP(p.updated_at) AS ts_cambio
        FROM pedido p
        JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
        WHERE UNIX_TIMESTAMP(p.updated_at) > :desde
        ORDER BY p.updated_at DESC
        LIMIT 20
    ");
    $stmt->execute([':desde' => $desde]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPedidosCambiadosCliente(PDO $db, int $id_cliente, int $desde): array {
    $stmt = $db->prepare("
        SELECT
            p.id_pedido,
            ep.nombre_estado   AS estado,
            ep.id_estado_pedido,
            UNIX_TIMESTAMP(p.updated_at) AS ts_cambio
        FROM pedido p
        JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
        WHERE p.id_cliente = :c
          AND UNIX_TIMESTAMP(p.updated_at) > :desde
        ORDER BY p.updated_at DESC
        LIMIT 10
    ");
    $stmt->execute([':c' => $id_cliente, ':desde' => $desde]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getKPIsAdmin(PDO $db): array {
    // KPIs: pedidos hoy y conteo por estado
    $pedidosHoy = (int)$db->query("
        SELECT COUNT(*) FROM pedido WHERE DATE(fecha_pedido) = CURDATE()
    ")->fetchColumn();

    $stmtEst = $db->query("
        SELECT ep.nombre_estado, COUNT(p.id_pedido) AS total
        FROM estado_pedido ep
        LEFT JOIN pedido p ON p.id_estado_pedido = ep.id_estado_pedido
        GROUP BY ep.id_estado_pedido, ep.nombre_estado
        ORDER BY ep.id_estado_pedido
    ");
    $kpis = [];
    foreach ($stmtEst->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $kpis[$row['nombre_estado']] = (int)$row['total'];
    }

    return ['pedidosHoy' => $pedidosHoy, 'estados' => $kpis];
}

// ── Loop SSE ──────────────────────────────────────────────────────────────────

while (true) {
    // Verificar si el cliente sigue conectado
    if (connection_aborted()) break;

    // Tiempo máximo de vida de la conexión
    if ((time() - $inicio) >= $maxTiempo) {
        // Decirle al cliente que reconecte
        echo "event: reconnect\n";
        echo "data: {\"ts\":" . time() . "}\n\n";
        flush();
        break;
    }

    try {
        if ($modo === 'admin') {
            $cambios = getPedidosCambiadosAdmin($db, $ultimoCheck);
            if (!empty($cambios)) {
                $kpis = getKPIsAdmin($db);
                echo "event: pedido_actualizado\n";
                echo "data: " . json_encode([
                    'cambios' => $cambios,
                    'kpis'    => $kpis,
                    'ts'      => time(),
                ]) . "\n\n";
                flush();
                // Actualizar timestamp al más reciente
                $ultimoCheck = max(array_column($cambios, 'ts_cambio'));
            }
        } else {
            // modo cliente
            if ($id_cliente > 0) {
                $cambios = getPedidosCambiadosCliente($db, $id_cliente, $ultimoCheck);
                if (!empty($cambios)) {
                    echo "event: pedido_actualizado\n";
                    echo "data: " . json_encode([
                        'cambios' => $cambios,
                        'ts'      => time(),
                    ]) . "\n\n";
                    flush();
                    $ultimoCheck = max(array_column($cambios, 'ts_cambio'));
                }
            }
        }
    } catch (Exception $e) {
        // Si la BD falla, enviar heartbeat y continuar
        echo ": db-error\n\n";
        flush();
    }

    // Heartbeat silencioso cada iteración para mantener la conexión viva
    echo ": heartbeat " . time() . "\n\n";
    flush();

    sleep($intervalo);
}
