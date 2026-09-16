<?php
/**
 * Endpoint AJAX — Disponibilidad de mesas por fecha y hora.
 *
 * GET params:
 *   fecha   : Y-m-d
 *   hora    : H:i
 *   excluir : id_reserva a ignorar (para ediciones, opcional)
 *
 * Responde con JSON:  { id_mesa: { ocupada: 0|1 }, ... }
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Solo usuarios autenticados
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$fecha   = $_GET['fecha']   ?? '';
$hora    = $_GET['hora']    ?? '';
$excluir = (int)($_GET['excluir'] ?? 0);

// Validación básica
if (!$fecha || !$hora || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

// Normalizar hora a H:i:s para comparar con el campo TIME de MySQL
if (strlen($hora) === 5) $hora .= ':00';

try {
    $db = (new database())->conectar();

    // Obtener todas las mesas
    $mesas = $db->query("SELECT id_mesa FROM mesa")->fetchAll(PDO::FETCH_COLUMN);

    // Obtener mesas ocupadas en esa fecha y hora
    $sql = "
        SELECT r.id_mesa
        FROM reserva r
        JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
        WHERE r.fecha_reserva = :fecha
          AND r.hora_reserva  = :hora
          AND er.nombre_estado NOT IN ('cancelada', 'completada', 'no asistio')
    ";
    $params = [':fecha' => $fecha, ':hora' => $hora];

    if ($excluir > 0) {
        $sql .= " AND r.id_reserva != :excluir";
        $params[':excluir'] = $excluir;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $ocupadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $ocupadasSet = array_flip($ocupadas);

    // Construir respuesta
    $result = [];
    foreach ($mesas as $id_mesa) {
        $result[$id_mesa] = ['ocupada' => isset($ocupadasSet[$id_mesa]) ? 1 : 0];
    }

    header('Content-Type: application/json');
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}
