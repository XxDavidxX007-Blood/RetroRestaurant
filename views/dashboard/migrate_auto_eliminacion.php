<?php
/**
 * migrate_auto_eliminacion.php
 * Agrega la columna fecha_auto_eliminacion a la tabla pedido.
 * Ejecutar una sola vez: http://localhost/RetroRestaurant/views/dashboard/migrate_auto_eliminacion.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1,'1'])) {
    die('Acceso denegado. Solo administradores.');
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';

$db    = (new Database())->conectar();
$pasos = [];

// 1. Agregar columna fecha_auto_eliminacion
try {
    $db->exec("ALTER TABLE pedido ADD COLUMN fecha_auto_eliminacion DATE DEFAULT NULL");
    $pasos[] = ['ok', 'Columna fecha_auto_eliminacion agregada a tabla pedido'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $pasos[] = ['skip', 'fecha_auto_eliminacion ya existe — OK'];
    } else {
        $pasos[] = ['err', 'Error: ' . $e->getMessage()];
    }
}

// 2. Índice para optimizar la consulta de limpieza
try {
    $db->exec("CREATE INDEX idx_pedido_autoelim ON pedido(fecha_auto_eliminacion)");
    $pasos[] = ['ok', 'Índice idx_pedido_autoelim creado'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $pasos[] = ['skip', 'Índice ya existe — OK'];
    } else {
        $pasos[] = ['err', 'Error creando índice: ' . $e->getMessage()];
    }
}

// 3. Poblar fechas para pedidos existentes entregados/cancelados
try {
    $model = new Pedido($db);
    $model->sincronizarFechasEliminacion();
    $pasos[] = ['ok', 'Fechas de eliminación calculadas para pedidos existentes'];
} catch (Exception $e) {
    $pasos[] = ['err', 'Error calculando fechas: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Migración — Auto-eliminación de Pedidos</title>
<style>
  body { font-family: monospace; background: #111; color: #eee; padding: 2rem; }
  .ok   { color: #4ade80; } .skip { color: #facc15; } .err { color: #f87171; }
  h2 { color: #c5a059; }
</style>
</head>
<body>
<h2>🗑️ Migración — Eliminación automática de pedidos (7 días hábiles)</h2>
<?php foreach ($pasos as [$tipo, $msg]): ?>
<p class="<?= $tipo ?>">
  <?= $tipo === 'ok' ? '✅' : ($tipo === 'skip' ? '⏭️' : '❌') ?> <?= htmlspecialchars($msg) ?>
</p>
<?php endforeach; ?>
<p style="margin-top:2rem;color:#94a3b8;">
  Migración completada. Los pedidos entregados/cancelados se eliminarán automáticamente
  7 días hábiles después de su fecha de pedido.
  <a href="admin_pedidos.php" style="color:#c5a059;">← Volver a Pedidos</a>
</p>
</body>
</html>
