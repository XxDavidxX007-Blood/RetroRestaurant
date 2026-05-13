<?php
require_once 'config/database.php';
$db = new database();
$conn = $db->conectar();
$stmt = $conn->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n" . implode(", ", $tables) . "\n\n";

$stmt = $conn->query("SELECT * FROM producto LIMIT 5");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Productos:\n";
print_r($productos);
