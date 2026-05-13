<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false]); exit;
}

require_once __DIR__ . '/../config/database.php';

$accion     = $_GET['accion'] ?? '';
$id_usuario = $_SESSION['usuario']['id_usuario'];

if ($accion === 'marcar') {
    $db = (new database())->conectar();
    $db->prepare("UPDATE notificacion SET leida = 1 WHERE id_usuario_destino = :id")
       ->execute([':id' => $id_usuario]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no reconocida']);
