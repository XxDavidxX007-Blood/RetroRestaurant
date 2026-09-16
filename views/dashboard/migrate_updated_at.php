<?php
/**
 * migrate_updated_at.php — Agrega columna updated_at a la tabla pedido.
 * Ejecutar una sola vez: http://localhost/RetroRestaurant/views/dashboard/migrate_updated_at.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1,'1'])) {
    die('Acceso denegado. Solo administradores.');
}
require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->conectar();

$pasos = [];

// 1. Agregar updated_at a pedido si no existe
try {
    $db->exec("ALTER TABLE pedido ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    $pasos[] = ['ok', 'Columna updated_at agregada a tabla pedido'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $pasos[] = ['skip', 'updated_at ya existe en pedido — OK'];
    } else {
        $pasos[] = ['err', 'Error: ' . $e->getMessage()];
    }
}

// 2. Agregar observacion a detalle_pedido si no existe (usada en PedidoDetalleCliente)
try {
    $db->exec("ALTER TABLE detalle_pedido ADD COLUMN observacion VARCHAR(255) DEFAULT NULL");
    $pasos[] = ['ok', 'Columna observacion agregada a detalle_pedido'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $pasos[] = ['skip', 'observacion ya existe en detalle_pedido — OK'];
    } else {
        $pasos[] = ['err', 'Error: ' . $e->getMessage()];
    }
}

// 3. Agregar direccion_entrega a pedido si no existe
try {
    $db->exec("ALTER TABLE pedido ADD COLUMN direccion_entrega VARCHAR(255) DEFAULT NULL");
    $pasos[] = ['ok', 'Columna direccion_entrega agregada a pedido'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $pasos[] = ['skip', 'direccion_entrega ya existe — OK'];
    } else {
        $pasos[] = ['err', 'Error: ' . $e->getMessage()];
    }
}

// 4. Índice para optimizar queries SSE
try {
    $db->exec("CREATE INDEX idx_pedido_updated ON pedido(updated_at)");
    $pasos[] = ['ok', 'Índice idx_pedido_updated creado'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $pasos[] = ['skip', 'Índice ya existe — OK'];
    } else {
        $pasos[] = ['err', 'Error creando índice: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Migración — updated_at</title>
<style>
  body { font-family: monospace; background: #111; color: #eee; padding: 2rem; }
  .ok   { color: #4ade80; } .skip { color: #facc15; } .err { color: #f87171; }
  h2 { color: #c5a059; }
</style>
</head>
<body>
<h2>🔧 Migración — Sincronización en Tiempo Real</h2>
<?php foreach ($pasos as [$tipo, $msg]): ?>
<p class="<?= $tipo ?>">
  <?= $tipo === 'ok' ? '✅' : ($tipo === 'skip' ? '⏭️' : '❌') ?> <?= htmlspecialchars($msg) ?>
</p>
<?php endforeach; ?>
<p style="margin-top:2rem;color:#94a3b8;">
  Migración completada. Puedes cerrar esta página.
  <a href="../dashboard/admin_pedidos.php" style="color:#c5a059;">← Volver a Pedidos</a>
</p>
</body>
</html>
