<?php

class Pedido {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        // Auto-migración: garantiza que la columna exista sin necesitar script manual
        $this->asegurarColumnaAutoEliminacion();
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
                m.nombre_mesero                                      AS mesero,
                p.direccion_entrega,
                p.fecha_auto_eliminacion
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
                m.nombre_mesero                     AS mesero,
                p.direccion_entrega,
                p.fecha_auto_eliminacion
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
        // updated_at se actualiza automáticamente (ON UPDATE CURRENT_TIMESTAMP)
        // forzamos el toque con NOW() para compatibilidad si la columna no tiene ON UPDATE
        $stmt = $this->db->prepare("
            UPDATE pedido
            SET id_estado_pedido = :estado,
                updated_at = NOW()
            WHERE id_pedido = :id
        ");
        return $stmt->execute([':estado' => $id_estado_pedido, ':id' => $id_pedido]);
    }

    // ── ELIMINACIÓN AUTOMÁTICA POR 7 DÍAS HÁBILES ─────────────────

    /**
     * Calcula la fecha de eliminación automática sumando 7 días hábiles
     * (lunes a viernes) a partir de una fecha dada.
     */
    public static function calcularFechaEliminacion(string $fechaBase): string {
        $fecha    = new DateTime($fechaBase);
        $dias     = 0;
        while ($dias < 7) {
            $fecha->modify('+1 day');
            $dow = (int)$fecha->format('N'); // 1=lun … 7=dom
            if ($dow <= 5) { // lunes a viernes
                $dias++;
            }
        }
        return $fecha->format('Y-m-d');
    }

    /**
     * Verifica si la columna fecha_auto_eliminacion existe en la tabla pedido.
     * Si no existe, la crea automáticamente (auto-migración).
     */
    private function asegurarColumnaAutoEliminacion(): bool {
        try {
            // Verificar con INFORMATION_SCHEMA, sin tocar la tabla directamente
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'pedido'
                  AND COLUMN_NAME  = 'fecha_auto_eliminacion'
            ");
            $stmt->execute();
            $existe = (int)$stmt->fetchColumn();

            if ($existe === 0) {
                $this->db->exec("ALTER TABLE pedido ADD COLUMN fecha_auto_eliminacion DATE DEFAULT NULL");
                try {
                    $this->db->exec("CREATE INDEX idx_pedido_autoelim ON pedido(fecha_auto_eliminacion)");
                } catch (PDOException $ei) {
                    // El índice ya puede existir — no es error fatal
                }
            }
            return true;
        } catch (PDOException $e) {
            // No se pudo verificar ni crear — continuar sin auto-eliminación
            return false;
        }
    }

    /**
     * Elimina en cascada todos los pedidos cuya fecha_auto_eliminacion ya venció.
     * Solo borra pedidos con estado 'entregado' o 'cancelado'.
     * Retorna la cantidad de pedidos eliminados.
     */
    public function ejecutarLimpiezaAutomatica(): int {
        // Obtener ids a eliminar
        $stmt = $this->db->prepare("
            SELECT p.id_pedido
            FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            WHERE ep.nombre_estado IN ('entregado', 'cancelado')
              AND p.fecha_auto_eliminacion IS NOT NULL
              AND p.fecha_auto_eliminacion <= CURDATE()
        ");
        $stmt->execute();
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Eliminar detalles, facturas y pedidos en cascada
        $this->db->prepare("DELETE FROM detalle_pedido WHERE id_pedido IN ($placeholders)")
                 ->execute($ids);
        $this->db->prepare("DELETE FROM factura WHERE id_pedido IN ($placeholders)")
                 ->execute($ids);
        $this->db->prepare("DELETE FROM pedido WHERE id_pedido IN ($placeholders)")
                 ->execute($ids);

        return count($ids);
    }

    /**
     * Asigna fecha_auto_eliminacion a pedidos entregados/cancelados que aún no la tienen.
     * Se llama al cambiar estado o al cargar las vistas.
     */
    public function sincronizarFechasEliminacion(): void {
        $stmt = $this->db->prepare("
            SELECT p.id_pedido, p.fecha_pedido
            FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            WHERE ep.nombre_estado IN ('entregado', 'cancelado')
              AND p.fecha_auto_eliminacion IS NULL
        ");
        $stmt->execute();
        $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $update = $this->db->prepare("
            UPDATE pedido SET fecha_auto_eliminacion = :fecha WHERE id_pedido = :id
        ");
        foreach ($pendientes as $row) {
            $fechaElim = self::calcularFechaEliminacion($row['fecha_pedido']);
            $update->execute([':fecha' => $fechaElim, ':id' => $row['id_pedido']]);
        }
    }

    /**
     * Retorna la fecha de eliminación de un pedido específico.
     */
    public function getFechaEliminacion(int $id_pedido): ?string {
        $stmt = $this->db->prepare("
            SELECT fecha_auto_eliminacion FROM pedido WHERE id_pedido = :id
        ");
        $stmt->execute([':id' => $id_pedido]);
        $val = $stmt->fetchColumn();
        return $val ?: null;
    }
}
