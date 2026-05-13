<?php

class Dashboard {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── KPIs DEL DÍA ──────────────────────────────────────────────

    public function getVentasHoy() {
        return $this->db->query("
            SELECT IFNULL(SUM(f.total_factura), 0)
            FROM factura f
            WHERE DATE(f.fecha) = CURDATE()
        ")->fetchColumn();
    }

    public function getVentasAyer() {
        return $this->db->query("
            SELECT IFNULL(SUM(f.total_factura), 0)
            FROM factura f
            WHERE DATE(f.fecha) = CURDATE() - INTERVAL 1 DAY
        ")->fetchColumn();
    }

    public function getPedidosHoy() {
        return $this->db->query("
            SELECT COUNT(*)
            FROM pedido
            WHERE DATE(fecha_pedido) = CURDATE()
        ")->fetchColumn();
    }

    public function getPedidosAyer() {
        return $this->db->query("
            SELECT COUNT(*)
            FROM pedido
            WHERE DATE(fecha_pedido) = CURDATE() - INTERVAL 1 DAY
        ")->fetchColumn();
    }

    public function getClientesHoy() {
        return $this->db->query("
            SELECT COUNT(DISTINCT id_cliente)
            FROM pedido
            WHERE DATE(fecha_pedido) = CURDATE()
              AND id_cliente IS NOT NULL
        ")->fetchColumn();
    }

    public function getClientesAyer() {
        return $this->db->query("
            SELECT COUNT(DISTINCT id_cliente)
            FROM pedido
            WHERE DATE(fecha_pedido) = CURDATE() - INTERVAL 1 DAY
              AND id_cliente IS NOT NULL
        ")->fetchColumn();
    }

    public function getPlatosMasVendidosCount() {
        return $this->db->query("
            SELECT COUNT(DISTINCT id_producto) FROM detalle_pedido
        ")->fetchColumn();
    }

    public function getTotalProductos() {
        return $this->db->query("
            SELECT COUNT(*) FROM producto
        ")->fetchColumn();
    }

    // ── GRÁFICO DE VENTAS (últimos 7 días) ────────────────────────

    public function getVentasSemana() {
        return $this->db->query("
            SELECT DATE(f.fecha) AS dia, IFNULL(SUM(f.total_factura), 0) AS total
            FROM factura f
            WHERE f.fecha >= CURDATE() - INTERVAL 6 DAY
            GROUP BY DATE(f.fecha)
            ORDER BY dia ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── PEDIDOS POR ESTADO (donut) ────────────────────────────────

    public function getPedidosPorEstado() {
        return $this->db->query("
            SELECT ep.nombre_estado AS estado, COUNT(p.id_pedido) AS total
            FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            GROUP BY ep.id_estado_pedido, ep.nombre_estado
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── PEDIDOS RECIENTES ─────────────────────────────────────────

    public function getPedidosRecientes($limite = 5) {
        $stmt = $this->db->prepare("
            SELECT
                p.id_pedido,
                COALESCE(u.nombre, 'Sin cliente') AS nombre,
                tp.nombre_tipo AS lugar,
                IFNULL(f.total_factura, 0) AS total,
                ep.nombre_estado AS estado
            FROM pedido p
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            JOIN tipo_pedido tp   ON p.id_tipo_pedido   = tp.id_tipo_pedido
            LEFT JOIN cliente c   ON p.id_cliente = c.id_cliente
            LEFT JOIN usuario u   ON c.id_usuario = u.id_usuario
            LEFT JOIN factura f   ON f.id_pedido  = p.id_pedido
            ORDER BY p.id_pedido DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── PLATOS MÁS VENDIDOS ───────────────────────────────────────

    public function getPlatosMasVendidos($limite = 5) {
        $stmt = $this->db->prepare("
            SELECT
                pr.nombre,
                cp.nombre_categoria AS categoria,
                SUM(dp.cantidad) AS vendidos,
                SUM(dp.subtotal) AS ingresos,
                '' AS imagen
            FROM detalle_pedido dp
            JOIN producto pr          ON dp.id_producto  = pr.id_producto
            JOIN categoria_producto cp ON pr.id_categoria = cp.id_categoria
            GROUP BY dp.id_producto, pr.nombre, cp.nombre_categoria
            ORDER BY vendidos DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── DOMICILIOS RECIENTES ──────────────────────────────────────

    public function getDomiciliosRecientes($limite = 5) {
        $stmt = $this->db->prepare("
            SELECT
                p.id_pedido,
                COALESCE(u.nombre, 'Sin cliente')  AS nombre,
                COALESCE(u.telefono, '—')           AS telefono,
                IFNULL(f.total_factura, 0)          AS total,
                ep.nombre_estado                    AS estado,
                p.fecha_pedido
            FROM pedido p
            JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
            JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
            LEFT JOIN cliente  c  ON p.id_cliente       = c.id_cliente
            LEFT JOIN usuario  u  ON c.id_usuario       = u.id_usuario
            LEFT JOIN factura  f  ON f.id_pedido        = p.id_pedido
            WHERE tp.nombre_tipo LIKE '%domicilio%'
               OR tp.nombre_tipo LIKE '%delivery%'
            ORDER BY p.id_pedido DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDomiciliosHoy() {
        return $this->db->query("
            SELECT COUNT(*) FROM pedido p
            JOIN tipo_pedido tp ON p.id_tipo_pedido = tp.id_tipo_pedido
            WHERE DATE(p.fecha_pedido) = CURDATE()
              AND (tp.nombre_tipo LIKE '%domicilio%' OR tp.nombre_tipo LIKE '%delivery%')
        ")->fetchColumn();
    }

    public function getActividadReciente($limite = 4) {
        $stmt = $this->db->prepare("
            SELECT
                'pedido' AS tipo,
                CONCAT('Nuevo pedido #', p.id_pedido) AS titulo,
                COALESCE(u.nombre, 'Cliente sin registrar') AS subtitulo,
                p.fecha_pedido AS momento
            FROM pedido p
            LEFT JOIN cliente c ON p.id_cliente = c.id_cliente
            LEFT JOIN usuario u ON c.id_usuario = u.id_usuario
            ORDER BY p.id_pedido DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
