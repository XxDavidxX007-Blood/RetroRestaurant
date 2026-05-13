<?php
// Test de diagnóstico — ELIMINAR después de usar
// URL: https://retrorestaurant.byethost24.com/views/dashboard/test500.php?key=retro2025

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['key']) || $_GET['key'] !== 'retro2025') {
    die('Acceso denegado.');
}

echo "<h2>Test de diagnóstico RetroRestaurant</h2><pre>";

// 1. Versión PHP
echo "PHP version: " . PHP_VERSION . "\n\n";

// 2. SCRIPT_NAME y cálculo de base
$script = $_SERVER['SCRIPT_NAME'];
echo "SCRIPT_NAME: $script\n";
$base3 = rtrim(dirname(dirname(dirname($script))), '/');
if ($base3 === '.') $base3 = '';
echo "Base (3 dirname): '$base3'\n";
$base2 = rtrim(dirname(dirname($script)), '/');
if ($base2 === '.') $base2 = '';
echo "Base (2 dirname): '$base2'\n\n";

// 3. Test de conexión a BD
echo "=== Conexión BD ===\n";
try {
    require_once __DIR__ . '/../../config/database.php';
    echo "database.php cargado OK\n";
    $db = (new Database())->conectar();
    echo "Conexión OK\n";
    
    // Tablas
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas: " . implode(', ', $tables) . "\n\n";
    
    // Columnas de reserva
    $cols = $db->query("SHOW COLUMNS FROM reserva")->fetchAll(PDO::FETCH_COLUMN);
    echo "Columnas reserva: " . implode(', ', $cols) . "\n\n";
    
    // Usuarios
    $users = $db->query("SELECT id_usuario, email, id_rol FROM usuario LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "Usuarios (primeros 5):\n";
    foreach ($users as $u) {
        echo "  id={$u['id_usuario']} email={$u['email']} rol={$u['id_rol']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR BD: " . $e->getMessage() . "\n";
}

// 4. Test de includes
echo "\n=== Test de includes ===\n";
$files = [
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../../Controllers/CompraController.php',
    __DIR__ . '/../layouts/header.php',
    __DIR__ . '/../layouts/sidebar.php',
    __DIR__ . '/../layouts/footer.php',
];
foreach ($files as $f) {
    echo (file_exists($f) ? "✅ EXISTS" : "❌ MISSING") . ": $f\n";
}

echo "\n=== FIN ===\n";
echo "</pre>";
echo "<p style='color:red'><b>ELIMINA este archivo del servidor después de usarlo.</b></p>";
?>
