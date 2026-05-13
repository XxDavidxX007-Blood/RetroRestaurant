<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [3,'3','cliente'])) {
    echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CompraController.php';

$data = json_decode(file_get_contents('php://input'), true);
$carrito = $data['carrito'] ?? [];
$tipo    = $data['tipo']    ?? 'mesa';

if (empty($carrito)) {
    echo json_encode(['ok'=>false,'error'=>'El carrito está vacío']); exit;
}

// Obtener id_cliente
$db   = (new database())->conectar();
$stmt = $db->prepare("SELECT id_cliente FROM cliente WHERE id_usuario=:id LIMIT 1");
$stmt->execute([':id' => $_SESSION['usuario']['id_usuario']]);
$row  = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['ok'=>false,'error'=>'No se encontró el cliente']); exit;
}

$ctrl = new CompraController();
$result = $ctrl->procesarCompra($row['id_cliente'], $carrito, $tipo);
echo json_encode($result);
