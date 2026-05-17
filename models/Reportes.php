<?php
class Reportes {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerKPIsGenerales() {
        $kpis = [
            'total_productos' => 0,
            'valor_total' => 0,
            'total_usuarios' => 0
        ];

        // Total productos y valor
        $sqlInventario = "SELECT COUNT(p.id_producto) as total, 
                                 SUM(p.precio * i.cantidad_actual) as valor 
                          FROM producto p 
                          JOIN inventario i ON p.id_producto = i.id_producto";
        $stmt = $this->conn->prepare($sqlInventario);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $kpis['total_productos'] = $row['total'] ?? 0;
        $kpis['valor_total'] = $row['valor'] ?? 0;

        // Total usuarios
        $sqlUsuarios = "SELECT COUNT(id_usuario) as total FROM usuario";
        $stmt = $this->conn->prepare($sqlUsuarios);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $kpis['total_usuarios'] = $row['total'] ?? 0;

        return $kpis;
    }

    public function obtenerValorPorCategoria() {
        $sql = "SELECT c.nombre_categoria, SUM(p.precio * i.cantidad_actual) as valor_total 
                FROM producto p 
                JOIN inventario i ON p.id_producto = i.id_producto 
                JOIN categoria_producto c ON p.id_categoria = c.id_categoria 
                GROUP BY c.id_categoria 
                ORDER BY valor_total DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstadoStock() {
        $sql = "SELECT i.cantidad_actual, i.cantidad_minima FROM inventario i";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $estados = [
            'En Stock' => 0,
            'Stock Bajo' => 0,
            'Sin Stock' => 0
        ];

        foreach ($items as $item) {
            if ($item['cantidad_actual'] == 0) {
                $estados['Sin Stock']++;
            } elseif ($item['cantidad_actual'] <= $item['cantidad_minima']) {
                $estados['Stock Bajo']++;
            } else {
                $estados['En Stock']++;
            }
        }

        return $estados;
    }
    public function obtenerInventarioFiltrado($tipo, $valor) {
        $sql = "SELECT p.id_producto, p.nombre, p.precio, p.unidad, 
                       c.nombre_categoria as categoria,
                       i.cantidad_actual as stock, i.cantidad_minima as minimo
                FROM producto p
                LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                LEFT JOIN inventario i ON p.id_producto = i.id_producto
                WHERE 1=1 ";
                
        $params = [];

        if ($tipo === 'categoria' && !empty($valor) && $valor !== 'todas') {
            $sql .= " AND p.id_categoria = :id_categoria ";
            $params[':id_categoria'] = $valor;
        } elseif ($tipo === 'estado') {
            if ($valor === 'sin') {
                $sql .= " AND i.cantidad_actual = 0 ";
            } elseif ($valor === 'bajo') {
                $sql .= " AND i.cantidad_actual > 0 AND i.cantidad_actual <= i.cantidad_minima ";
            } elseif ($valor === 'stock') {
                $sql .= " AND i.cantidad_actual > i.cantidad_minima ";
            }
        }

        $sql .= " ORDER BY p.nombre ASC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function guardarHistorial($datos) {
        try {
            // Crear tabla si no existe
            $this->conn->exec("CREATE TABLE IF NOT EXISTS reportes_guardados (
                id_reporte         INT PRIMARY KEY AUTO_INCREMENT,
                fecha_generacion   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                generado_por_nombre VARCHAR(150) NOT NULL,
                tipo_filtro        VARCHAR(50)  NOT NULL,
                valor_filtro       VARCHAR(100) NOT NULL,
                total_productos    INT          NOT NULL DEFAULT 0,
                valor_total        DECIMAL(12,2) NOT NULL DEFAULT 0,
                datos_json         LONGTEXT
            )");
            $sql = "INSERT INTO reportes_guardados (generado_por_nombre, tipo_filtro, valor_filtro, total_productos, valor_total, datos_json) 
                    VALUES (:nombre, :tipo, :valor, :total_prod, :valor_total, :json)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre',      $datos['nombre_usuario']);
            $stmt->bindParam(':tipo',        $datos['tipo_filtro']);
            $stmt->bindParam(':valor',       $datos['valor_filtro']);
            $stmt->bindParam(':total_prod',  $datos['total_productos']);
            $stmt->bindParam(':valor_total', $datos['valor_total']);
            $stmt->bindParam(':json',        $datos['datos_json']);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    public function obtenerHistorial() {
        try {
            // Crear tabla si no existe
            $this->conn->exec("CREATE TABLE IF NOT EXISTS reportes_guardados (
                id_reporte         INT PRIMARY KEY AUTO_INCREMENT,
                fecha_generacion   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                generado_por_nombre VARCHAR(150) NOT NULL,
                tipo_filtro        VARCHAR(50)  NOT NULL,
                valor_filtro       VARCHAR(100) NOT NULL,
                total_productos    INT          NOT NULL DEFAULT 0,
                valor_total        DECIMAL(12,2) NOT NULL DEFAULT 0,
                datos_json         LONGTEXT
            )");
            $sql = "SELECT id_reporte, fecha_generacion, generado_por_nombre, tipo_filtro, valor_filtro, total_productos, valor_total 
                    FROM reportes_guardados 
                    ORDER BY fecha_generacion DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function obtenerReporteGuardado($id_reporte) {
        try {
            $sql = "SELECT * FROM reportes_guardados WHERE id_reporte = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id_reporte);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) { return null; }
    }

    public function eliminarHistorial($id_reporte) {
        try {
            $sql = "DELETE FROM reportes_guardados WHERE id_reporte = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id_reporte);
            return $stmt->execute();
        } catch (Exception $e) { return false; }
    }
}
?>
