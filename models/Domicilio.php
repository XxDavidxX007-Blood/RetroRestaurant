<?php
class Domicilio {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // ── KPIs ──────────────────────────────────────────────────────

    public function getDomiciliosHoy() {
        return $this->db->query("
            SELECT COUNT(*) FROM pedido p
            JOIN tipo_pedido tp ON p.id_tipo_pedido = tp.id_tipo_pedido
            WHERE DATE(p.fecha_pedido) = CURDATE()
              AND (tp.nombre_tipo LIKE '%domicilio%' OR tp.nombre_tipo LIKE '%delivery%')
        ")->fetchColumn();
    }

    public function getDomiciliosAyer() {
        return $this->db->query("
            SELECT COUNT(*) FROM pedido p
            JOIN tipo_pedido tp ON p.id_tipo_pedido = tp.id_tipo_pedido
            WHERE DATE(p.fecha_pedido) = CURDATE() - INTERVAL 1 DAY
              AND (tp.nombre_tipo LIKE '%domicilio%' OR tp.nombre_tipo LIKE '%delivery%')
        ")->fetchColumn();
    }

    public function getConteoByEstado($nombre_estado) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM pedido p
            JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            WHERE (tp.nombre_tipo LIKE '%domicilio%' OR tp.nombre_tipo LIKE '%delivery%')
              AND ep.nombre_estado = :estado
        ");
        $stmt->execute([':estado' => $nombre_estado]);
        return $stmt->fetchColumn();
    }

    // ── LISTADO ───────────────────────────────────────────────────

    public function getAll($filtro_estado = null, $filtro_fecha = null, $pagina = 1, $por_pagina = 10) {
        $where  = ["(tp.nombre_tipo LIKE '%domicilio%' OR tp.nombre_tipo LIKE '%delivery%')"];
        $params = [];

        if ($filtro_estado && $filtro_estado !== 'todos') {
            $where[] = "ep.nombre_estado = :estado";
            $params[':estado'] = $filtro_estado;
        }
        if ($filtro_fecha) {
            $where[] = "DATE(p.fecha_pedido) = :fecha";
            $params[':fecha'] = $filtro_fecha;
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);
        $offset   = ($pagina - 1) * $por_pagina;

        $stmt = $this->db->prepare("
            SELECT
                p.id_pedido,
                p.fecha_pedido,
                ep.nombre_estado                        AS estado,
                ep.id_estado_pedido,
                COALESCE(u.nombre, 'Sin cliente')       AS nombre_cliente,
                COALESCE(u.apellidos, '')               AS apellidos_cliente,
                COALESCE(u.telefono, '—')               AS telefono_cliente,
                IFNULL(f.total_factura, 0)              AS total
            FROM pedido p
            JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            LEFT JOIN cliente  c  ON p.id_cliente       = c.id_cliente
            LEFT JOIN usuario  u  ON c.id_usuario       = u.id_usuario
            LEFT JOIN factura  f  ON f.id_pedido        = p.id_pedido
            $whereSQL
            ORDER BY p.id_pedido DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount($filtro_estado = null, $filtro_fecha = null) {
        $where  = ["(tp.nombre_tipo LIKE '%domicilio%' OR tp.nombre_tipo LIKE '%delivery%')"];
        $params = [];
        if ($filtro_estado && $filtro_estado !== 'todos') {
            $where[] = "ep.nombre_estado = :estado";
            $params[':estado'] = $filtro_estado;
        }
        if ($filtro_fecha) {
            $where[] = "DATE(p.fecha_pedido) = :fecha";
            $params[':fecha'] = $filtro_fecha;
        }
        $whereSQL = 'WHERE ' . implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM pedido p
            JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            $whereSQL
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ── CREAR ─────────────────────────────────────────────────────

    public function crear($datos) {
        try {
            // Obtener id_tipo_pedido de domicilio
            $tp = $this->db->query("SELECT id_tipo_pedido FROM tipo_pedido WHERE nombre_tipo LIKE '%domicilio%' LIMIT 1")->fetchColumn();
            if (!$tp) throw new Exception("No existe tipo de pedido 'domicilio'");

            $stmt = $this->db->prepare("
                INSERT INTO pedido (id_cliente, id_mesero, id_tipo_pedido, fecha_pedido, id_estado_pedido)
                VALUES (:id_cliente, :id_mesero, :id_tipo_pedido, :fecha_pedido, :id_estado_pedido)
            ");
            $stmt->execute([
                ':id_cliente'        => $datos['id_cliente'] ?: null,
                ':id_mesero'         => $datos['id_mesero'],
                ':id_tipo_pedido'    => $tp,
                ':fecha_pedido'      => $datos['fecha_pedido'],
                ':id_estado_pedido'  => $datos['id_estado_pedido'],
            ]);
            return true;
        } catch (Exception $e) { return $e->getMessage(); }
    }

    public function cambiarEstado($id_pedido, $id_estado_pedido) {
        $stmt = $this->db->prepare("UPDATE pedido SET id_estado_pedido=:e WHERE id_pedido=:id");
        return $stmt->execute([':e' => $id_estado_pedido, ':id' => $id_pedido]);
    }

    public function eliminar($id_pedido) {
        try {
            $this->db->beginTransaction();
            // Eliminar detalle del pedido si existe
            $this->db->prepare("DELETE FROM detalle_pedido WHERE id_pedido = :id")->execute([':id' => $id_pedido]);
            // Eliminar factura si existe
            $this->db->prepare("DELETE FROM factura WHERE id_pedido = :id")->execute([':id' => $id_pedido]);
            // Eliminar el pedido
            $this->db->prepare("DELETE FROM pedido WHERE id_pedido = :id")->execute([':id' => $id_pedido]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return $e->getMessage();
        }
    }

    // ── CATÁLOGOS ─────────────────────────────────────────────────

    public function getEstados() {
        return $this->db->query("SELECT id_estado_pedido, nombre_estado FROM estado_pedido ORDER BY id_estado_pedido")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClientes() {
        return $this->db->query("
            SELECT c.id_cliente, u.nombre, u.apellidos, u.telefono
            FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario
            ORDER BY u.nombre
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMeseros() {
        return $this->db->query("
            SELECT m.id_mesero, u.nombre, u.apellidos
            FROM mesero m JOIN usuario u ON m.id_usuario = u.id_usuario
            ORDER BY u.nombre
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
