<?php
/**
 * Script de migración: renombra la columna numero_usuarios → numero_personas
 * en la tabla reserva, si aún no se ha hecho.
 *
 * Acceder UNA sola vez desde el navegador:
 *   https://retrorestaurant.byethost24.com/views/dashboard/db_fix_columna_reserva.php
 *
 * Eliminar este archivo después de ejecutarlo.
 */

// Protección básica: solo ejecutar si se pasa el parámetro correcto
if (!isset($_GET['run']) || $_GET['run'] !== 'fix2025') {
    die('<b>Acceso denegado.</b> Agrega ?run=fix2025 a la URL para ejecutar.');
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = (new Database())->conectar();

    // Verificar si la columna numero_usuarios existe
    $stmt = $db->query("SHOW COLUMNS FROM reserva LIKE 'numero_usuarios'");
    $existe = $stmt->fetch();

    if ($existe) {
        // Renombrar la columna
        $db->exec("ALTER TABLE reserva CHANGE numero_usuarios numero_personas INT NOT NULL");
        echo '<p style="color:green;font-weight:bold;">✅ Columna renombrada: numero_usuarios → numero_personas</p>';
    } else {
        // Verificar si ya existe numero_personas
        $stmt2 = $db->query("SHOW COLUMNS FROM reserva LIKE 'numero_personas'");
        if ($stmt2->fetch()) {
            echo '<p style="color:blue;">ℹ️ La columna ya se llama <b>numero_personas</b>. No se requiere migración.</p>';
        } else {
            // No existe ninguna de las dos — agregar la columna
            $db->exec("ALTER TABLE reserva ADD COLUMN numero_personas INT NOT NULL DEFAULT 1");
            echo '<p style="color:orange;">⚠️ Columna <b>numero_personas</b> no existía. Se creó con valor por defecto 1.</p>';
        }
    }

    // Mostrar estructura actual de la tabla
    echo '<h3>Estructura actual de la tabla reserva:</h3><pre>';
    $cols = $db->query("SHOW COLUMNS FROM reserva")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo $col['Field'] . ' — ' . $col['Type'] . "\n";
    }
    echo '</pre>';
    echo '<p style="color:red;"><b>IMPORTANTE:</b> Elimina este archivo del servidor después de ejecutarlo.</p>';

} catch (Exception $e) {
    echo '<p style="color:red;">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
