<?php

class Pedido {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── KPIs ──────────────────────────────────────────────────────

    public function getPedidosHoy() {
        return $this->db->query("
            SELECT COUNT(*) FROM pedido WHERE DATE(fecha_pedido) = CURDATE()
        ")->fetchColumn();
    }

    public function getPedidosAyer() {
        return $this->db->query("
            SELECT COUNT(*) FROM pedido WHERE DATE(fecha_pedido) = CURDATE() - INTERVAL 1 DAY
        ")->fetchColumn();
    }

    public function getConteoByEstado($nombre_estado) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            WHERE ep.nombre_estado = :estado
        ");
        $stmt->execute([':estado' => $nombre_estado]);
        return $stmt->fetchColumn();
    }

    // ── LISTADO CON FILTROS ───────────────────────────────────────

    public function getAll($filtro_estado = null, $filtro_fecha = null, $pagina = 1, $por_pagina = 10) {
        $where = [];
        $params = [];

        if ($filtro_estado && $filtro_estado !== 'todos') {
            $where[] = "ep.nombre_estado = :estado";
            $params[':estado'] = $filtro_estado;
        }

        if ($filtro_fecha) {
            $where[] = "DATE(p.fecha_pedido) = :fecha";
            $params[':fecha'] = $filtro_fecha;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset   = ($pagina - 1) * $por_pagina;

        $stmt = $this->db->prepare("
            SELECT
                p.id_pedido,
                COALESCE(u.nombre, 'Sin cliente')                   AS nombre_cliente,
                COALESCE(u.telefono, '—')                           AS telefono_cliente,
                tp.nombre_tipo                                       AS tipo,
                ep.nombre_estado                                     AS estado,
                IFNULL(f.total_factura, 0)                          AS total,
                p.fecha_pedido,
                m.nombre_mesero                                      AS mesero
            FROM pedido p
            JOIN estado_pedido ep  ON p.id_estado_pedido = ep.id_estado_pedido
            JOIN tipo_pedido   tp  ON p.id_tipo_pedido   = tp.id_tipo_pedido
            LEFT JOIN cliente  c   ON p.id_cliente       = c.id_cliente
            LEFT JOIN usuario  u   ON c.id_usuario       = u.id_usuario
            LEFT JOIN factura  f   ON f.id_pedido        = p.id_pedido
            LEFT JOIN (
                SELECT m2.id_mesero, u2.nombre AS nombre_mesero
                FROM mesero m2 JOIN usuario u2 ON m2.id_usuario = u2.id_usuario
            ) m ON p.id_mesero = m.id_mesero
            $whereSQL
            ORDER BY p.id_pedido DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount($filtro_estado = null, $filtro_fecha = null) {
        $where  = [];
        $params = [];

        if ($filtro_estado && $filtro_estado !== 'todos') {
            $where[] = "ep.nombre_estado = :estado";
            $params[':estado'] = $filtro_estado;
        }

        if ($filtro_fecha) {
            $where[] = "DATE(p.fecha_pedido) = :fecha";
            $params[':fecha'] = $filtro_fecha;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            $whereSQL
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ── DETALLE DE UN PEDIDO ──────────────────────────────────────

    public function getById($id_pedido) {
        $stmt = $this->db->prepare("
            SELECT
                p.id_pedido,
                COALESCE(u.nombre, 'Sin cliente')  AS nombre_cliente,
                COALESCE(u.telefono, '—')           AS telefono_cliente,
                tp.nombre_tipo                      AS tipo,
                ep.nombre_estado                    AS estado,
                ep.id_estado_pedido,
                IFNULL(f.total_factura, 0)          AS total,
                p.fecha_pedido,
                m.nombre_mesero                     AS mesero
            FROM pedido p
            JOIN estado_pedido ep  ON p.id_estado_pedido = ep.id_estado_pedido
            JOIN tipo_pedido   tp  ON p.id_tipo_pedido   = tp.id_tipo_pedido
            LEFT JOIN cliente  c   ON p.id_cliente       = c.id_cliente
            LEFT JOIN usuario  u   ON c.id_usuario       = u.id_usuario
            LEFT JOIN factura  f   ON f.id_pedido        = p.id_pedido
            LEFT JOIN (
                SELECT m2.id_mesero, u2.nombre AS nombre_mesero
                FROM mesero m2 JOIN usuario u2 ON m2.id_usuario = u2.id_usuario
            ) m ON p.id_mesero = m.id_mesero
            WHERE p.id_pedido = :id
        ");
        $stmt->execute([':id' => $id_pedido]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDetalle($id_pedido) {
        $stmt = $this->db->prepare("
            SELECT
                dp.cantidad,
                dp.precio_unitario,
                dp.subtotal,
                pr.nombre AS producto
            FROM detalle_pedido dp
            JOIN producto pr ON dp.id_producto = pr.id_producto
            WHERE dp.id_pedido = :id
        ");
        $stmt->execute([':id' => $id_pedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── ESTADOS DISPONIBLES ───────────────────────────────────────

    public function getEstados() {
        return $this->db->query("
            SELECT id_estado_pedido, nombre_estado FROM estado_pedido ORDER BY id_estado_pedido
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── CAMBIAR ESTADO ────────────────────────────────────────────

    public function cambiarEstado($id_pedido, $id_estado_pedido) {
        $stmt = $this->db->prepare("
            UPDATE pedido SET id_estado_pedido = :estado WHERE id_pedido = :id
        ");
        return $stmt->execute([':estado' => $id_estado_pedido, ':id' => $id_pedido]);
    }
}
