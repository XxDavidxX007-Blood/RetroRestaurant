<?php

class Reserva {
    private $db;

    public function __construct($db) { $this->db = $db; }

    // ── KPIs ──────────────────────────────────────────────────────

    public function getReservasHoy() {
        return $this->db->query("SELECT COUNT(*) FROM reserva WHERE DATE(fecha_reserva) = CURDATE()")->fetchColumn();
    }

    public function getReservasAyer() {
        return $this->db->query("SELECT COUNT(*) FROM reserva WHERE DATE(fecha_reserva) = CURDATE() - INTERVAL 1 DAY")->fetchColumn();
    }

    public function getReservasManana() {
        return $this->db->query("SELECT COUNT(*) FROM reserva WHERE DATE(fecha_reserva) = CURDATE() + INTERVAL 1 DAY")->fetchColumn();
    }

    public function getConteoByEstado($nombre_estado) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reserva r
            JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
            WHERE er.nombre_estado = :estado AND DATE(r.fecha_reserva) = CURDATE()
        ");
        $stmt->execute([':estado' => $nombre_estado]);
        return $stmt->fetchColumn();
    }

    // ── LISTADO ───────────────────────────────────────────────────

    public function getAll($filtro_estado = null, $filtro_fecha = null, $pagina = 1, $por_pagina = 10) {
        $where  = [];
        $params = [];

        if ($filtro_estado && $filtro_estado !== 'todos') {
            $where[] = "er.nombre_estado = :estado";
            $params[':estado'] = $filtro_estado;
        }
        if ($filtro_fecha) {
            $where[] = "DATE(r.fecha_reserva) = :fecha";
            $params[':fecha'] = $filtro_fecha;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset   = ($pagina - 1) * $por_pagina;

        $stmt = $this->db->prepare("
            SELECT
                r.id_reserva,
                r.hora_reserva,
                r.fecha_reserva,
                r.numero_personas,
                er.nombre_estado        AS estado,
                er.id_estado_reserva,
                COALESCE(u.nombre, 'Sin cliente')  AS nombre_cliente,
                COALESCE(u.apellidos, '')           AS apellidos_cliente,
                COALESCE(u.telefono, '—')           AS telefono_cliente,
                m.numero_mesa,
                m.capacidad             AS capacidad_mesa,
                m.estado                AS estado_mesa
            FROM reserva r
            JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
            LEFT JOIN mesa m       ON r.id_mesa           = m.id_mesa
            LEFT JOIN cliente c    ON r.id_cliente        = c.id_cliente
            LEFT JOIN usuario u    ON c.id_usuario        = u.id_usuario
            $whereSQL
            ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount($filtro_estado = null, $filtro_fecha = null) {
        $where  = [];
        $params = [];
        if ($filtro_estado && $filtro_estado !== 'todos') {
            $where[] = "er.nombre_estado = :estado";
            $params[':estado'] = $filtro_estado;
        }
        if ($filtro_fecha) {
            $where[] = "DATE(r.fecha_reserva) = :fecha";
            $params[':fecha'] = $filtro_fecha;
        }
        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reserva r
            JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
            $whereSQL
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ── CRUD ──────────────────────────────────────────────────────

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT r.*, er.nombre_estado AS estado,
                COALESCE(u.nombre,'Sin cliente') AS nombre_cliente,
                COALESCE(u.apellidos,'')         AS apellidos_cliente,
                COALESCE(u.telefono,'—')         AS telefono_cliente,
                m.numero_mesa, m.capacidad AS capacidad_mesa
            FROM reserva r
            JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
            LEFT JOIN mesa m       ON r.id_mesa = m.id_mesa
            LEFT JOIN cliente c    ON r.id_cliente = c.id_cliente
            LEFT JOIN usuario u    ON c.id_usuario = u.id_usuario
            WHERE r.id_reserva = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO reserva (id_cliente, id_mesa, fecha_reserva, hora_reserva, numero_personas, id_estado_reserva)
                VALUES (:id_cliente, :id_mesa, :fecha_reserva, :hora_reserva, :numero_personas, :id_estado_reserva)
            ");
            $stmt->execute([
                ':id_cliente'        => $datos['id_cliente'] ?: null,
                ':id_mesa'           => $datos['id_mesa'],
                ':fecha_reserva'     => $datos['fecha_reserva'],
                ':hora_reserva'      => $datos['hora_reserva'],
                ':numero_personas'   => $datos['numero_personas'],
                ':id_estado_reserva' => $datos['id_estado_reserva'],
            ]);
            return true;
        } catch (Exception $e) { return $e->getMessage(); }
    }

    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE reserva SET
                    id_mesa           = :id_mesa,
                    fecha_reserva     = :fecha_reserva,
                    hora_reserva      = :hora_reserva,
                    numero_personas   = :numero_personas,
                    id_estado_reserva = :id_estado_reserva
                WHERE id_reserva = :id
            ");
            $stmt->execute([
                ':id_mesa'           => $datos['id_mesa'],
                ':fecha_reserva'     => $datos['fecha_reserva'],
                ':hora_reserva'      => $datos['hora_reserva'],
                ':numero_personas'   => $datos['numero_personas'],
                ':id_estado_reserva' => $datos['id_estado_reserva'],
                ':id'                => $id,
            ]);
            return true;
        } catch (Exception $e) { return $e->getMessage(); }
    }

    public function eliminar($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM reserva WHERE id_reserva = :id");
            $stmt->execute([':id' => $id]);
            return true;
        } catch (Exception $e) { return $e->getMessage(); }
    }

    // ── CATÁLOGOS ─────────────────────────────────────────────────

    public function getEstados() {
        return $this->db->query("SELECT id_estado_reserva, nombre_estado FROM estado_reserva ORDER BY id_estado_reserva")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMesas() {
        return $this->db->query("SELECT id_mesa, numero_mesa, capacidad, estado FROM mesa ORDER BY numero_mesa")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClientes() {
        return $this->db->query("
            SELECT c.id_cliente, u.nombre, u.apellidos, u.telefono
            FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario
            ORDER BY u.nombre
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
