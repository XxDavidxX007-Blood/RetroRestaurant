<?php
class Inventario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        // Detectar columnas que pueden no existir en producción
        $cols = $this->conn->query("SHOW COLUMNS FROM producto")->fetchAll(PDO::FETCH_COLUMN);
        $tieneUnidad = in_array('unidad', $cols);
        $tieneEsMenu = in_array('es_menu', $cols);

        $unidadExpr  = $tieneUnidad ? "COALESCE(p.unidad, 'unid')" : "'unid'";
        // Solo traer productos que NO son del menú
        $whereEsMenu = $tieneEsMenu ? "AND (p.es_menu = 0 OR p.es_menu IS NULL)" : "";

        $sql = "SELECT p.id_producto, p.nombre, p.precio,
                       {$unidadExpr} AS unidad,
                       p.imagen,
                       c.nombre_categoria AS categoria,
                       COALESCE(i.cantidad_actual, 0) AS stock,
                       COALESCE(i.cantidad_minima, 0) AS minimo,
                       i.id_inventario
                FROM producto p
                LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                LEFT JOIN inventario i ON p.id_producto = i.id_producto
                WHERE i.id_inventario IS NOT NULL
                {$whereEsMenu}
                ORDER BY p.id_producto DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Imagen por defecto si está vacía (en PHP para evitar problemas de collation)
        foreach ($rows as &$row) {
            if (empty($row['imagen'])) {
                $row['imagen'] = null; // null = mostrar ícono por defecto en la vista
            }
        }
        return $rows;
    }

    public function obtenerCategorias() {
        $sql = "SELECT * FROM categoria_producto ORDER BY nombre_categoria ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrar($datos) {
        try {
            $cols = $this->conn->query("SHOW COLUMNS FROM producto")->fetchAll(PDO::FETCH_COLUMN);
            $tieneUnidad = in_array('unidad', $cols);

            $this->conn->beginTransaction();

            if ($tieneUnidad) {
                $sqlProducto = "INSERT INTO producto (id_categoria, nombre, precio, unidad, imagen) 
                                VALUES (:id_categoria, :nombre, :precio, :unidad, :imagen)";
            } else {
                $sqlProducto = "INSERT INTO producto (id_categoria, nombre, precio, imagen) 
                                VALUES (:id_categoria, :nombre, :precio, :imagen)";
            }
            $stmtProducto = $this->conn->prepare($sqlProducto);
            $stmtProducto->bindParam(":id_categoria", $datos['id_categoria']);
            $stmtProducto->bindParam(":nombre", $datos['nombre']);
            $stmtProducto->bindParam(":precio", $datos['precio']);
            $stmtProducto->bindParam(":imagen", $datos['imagen']);
            if ($tieneUnidad) {
                $stmtProducto->bindParam(":unidad", $datos['unidad']);
            }
            $stmtProducto->execute();

            $id_producto = $this->conn->lastInsertId();

            $sqlInventario = "INSERT INTO inventario (id_producto, cantidad_actual, cantidad_minima, fecha_actualizacion) 
                              VALUES (:id_producto, :stock, :minimo, CURDATE())";
            $stmtInventario = $this->conn->prepare($sqlInventario);
            $stmtInventario->bindParam(":id_producto", $id_producto);
            $stmtInventario->bindParam(":stock", $datos['stock']);
            $stmtInventario->bindParam(":minimo", $datos['minimo']);
            $stmtInventario->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return "Error: " . $e->getMessage();
        }
    }

    public function actualizar($id_producto, $datos) {
        try {
            $cols = $this->conn->query("SHOW COLUMNS FROM producto")->fetchAll(PDO::FETCH_COLUMN);
            $tieneUnidad = in_array('unidad', $cols);

            $this->conn->beginTransaction();

            if ($tieneUnidad) {
                $sqlProducto = "UPDATE producto 
                                SET id_categoria = :id_categoria, nombre = :nombre, precio = :precio, 
                                    unidad = :unidad, imagen = :imagen 
                                WHERE id_producto = :id_producto";
            } else {
                $sqlProducto = "UPDATE producto 
                                SET id_categoria = :id_categoria, nombre = :nombre, precio = :precio, 
                                    imagen = :imagen 
                                WHERE id_producto = :id_producto";
            }
            $stmtProducto = $this->conn->prepare($sqlProducto);
            $stmtProducto->bindParam(":id_categoria", $datos['id_categoria']);
            $stmtProducto->bindParam(":nombre", $datos['nombre']);
            $stmtProducto->bindParam(":precio", $datos['precio']);
            $stmtProducto->bindParam(":imagen", $datos['imagen']);
            $stmtProducto->bindParam(":id_producto", $id_producto);
            if ($tieneUnidad) {
                $stmtProducto->bindParam(":unidad", $datos['unidad']);
            }
            $stmtProducto->execute();

            $sqlInventario = "UPDATE inventario 
                              SET cantidad_actual = :stock, cantidad_minima = :minimo, fecha_actualizacion = CURDATE()
                              WHERE id_producto = :id_producto";
            $stmtInventario = $this->conn->prepare($sqlInventario);
            $stmtInventario->bindParam(":stock", $datos['stock']);
            $stmtInventario->bindParam(":minimo", $datos['minimo']);
            $stmtInventario->bindParam(":id_producto", $id_producto);
            $stmtInventario->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return "Error: " . $e->getMessage();
        }
    }

    public function eliminar($id_producto) {
        try {
            $this->conn->beginTransaction();
            
            // Delete from inventario first due to foreign key
            $sqlInventario = "DELETE FROM inventario WHERE id_producto = :id_producto";
            $stmtInventario = $this->conn->prepare($sqlInventario);
            $stmtInventario->bindParam(":id_producto", $id_producto);
            $stmtInventario->execute();

            $sqlProducto = "DELETE FROM producto WHERE id_producto = :id_producto";
            $stmtProducto = $this->conn->prepare($sqlProducto);
            $stmtProducto->bindParam(":id_producto", $id_producto);
            $stmtProducto->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return "Error: " . $e->getMessage();
        }
    }
}
?>
