<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit;
}

$rol_id = $_SESSION['usuario']['id_rol'];
// Solo clientes y empleados pueden hacer pedidos
if (!in_array($rol_id, [2,'2','empleado', 3,'3','cliente'])) {
    echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CompraController.php';

$data    = json_decode(file_get_contents('php://input'), true);
$carrito   = $data['carrito']   ?? [];
$tipo      = $data['tipo']      ?? 'mesa';
$id_mesa   = isset($data['id_mesa'])  ? (int)$data['id_mesa'] : null;
$direccion = isset($data['direccion']) ? trim($data['direccion']) : null;

if (empty($carrito)) {
    echo json_encode(['ok'=>false,'error'=>'El carrito está vacío']); exit;
}

$db = (new database())->conectar();

// Obtener o crear el registro en tabla cliente para este usuario
$stmt = $db->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = :id LIMIT 1");
$stmt->execute([':id' => $_SESSION['usuario']['id_usuario']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    // Crear el registro cliente automáticamente si no existe
    try {
        $db->prepare("INSERT INTO cliente (id_usuario) VALUES (:id)")
           ->execute([':id' => $_SESSION['usuario']['id_usuario']]);
        $id_cliente = (int)$db->lastInsertId();
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'error'=>'No se pudo registrar el cliente: '.$e->getMessage()]); exit;
    }
} else {
    $id_cliente = (int)$row['id_cliente'];
}

$ctrl   = new CompraController();
$result = $ctrl->procesarCompra($id_cliente, $carrito, $tipo, $id_mesa, $direccion);
echo json_encode($result);
