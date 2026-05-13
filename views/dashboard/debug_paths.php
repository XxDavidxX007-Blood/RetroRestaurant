<?php
/**
 * Script de diagnóstico de rutas — ELIMINAR después de usar
 * Acceder: https://retrorestaurant.byethost24.com/views/dashboard/debug_paths.php
 */

// Protección básica
if (!isset($_GET['key']) || $_GET['key'] !== 'retro2025') {
    die('Acceso denegado. Agrega ?key=retro2025');
}

$script = $_SERVER['SCRIPT_NAME'];
echo "<h2>Diagnóstico de rutas</h2>";
echo "<pre>";
echo "SCRIPT_NAME: " . $script . "\n";
echo "dirname x1:  " . dirname($script) . "\n";
echo "dirname x2:  " . dirname(dirname($script)) . "\n";
echo "dirname x3:  " . dirname(dirname(dirname($script))) . "\n";
echo "\n";

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
echo "Protocol: $protocol\n";
echo "Host:     $host\n";
echo "\n";

// Cómo calcula AuthController la base (2 niveles desde Controllers/)
$baseAuth = rtrim(dirname(dirname($script)), '/');
echo "Base (AuthController, 2 niveles): $baseAuth\n";
echo "Login URL (AuthController): {$protocol}://{$host}{$baseAuth}/views/usuarios/login.php\n";
echo "\n";

// Cómo calcula header.php la base (3 niveles desde views/dashboard/)
$baseHeader = rtrim(dirname(dirname(dirname($script))), '/');
if ($baseHeader === '.') $baseHeader = '';
echo "Base (header.php, 3 niveles): $baseHeader\n";
echo "Login URL (header.php): {$protocol}://{$host}{$baseHeader}/views/usuarios/login.php\n";
echo "\n";

// Verificar conexión a BD
echo "=== Test BD ===\n";
require_once __DIR__ . '/../../config/database.php';
try {
    $db = (new Database())->conectar();
    echo "Conexión BD: OK\n";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas encontradas: " . implode(', ', $tables) . "\n";
    
    // Verificar columna reserva
    $cols = $db->query("SHOW COLUMNS FROM reserva")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nColumnas de reserva: " . implode(', ', $cols) . "\n";
    
    // Verificar si hay usuarios
    $count = $db->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
    echo "Usuarios en BD: $count\n";
    
} catch (Exception $e) {
    echo "Error BD: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p style='color:red'><b>IMPORTANTE: Elimina este archivo después de usarlo.</b></p>";
?>
