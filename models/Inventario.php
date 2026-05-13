<?php
class Inventario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT p.id_producto, p.nombre, p.precio, p.unidad, p.imagen, 
                       c.nombre_categoria as categoria,
                       i.cantidad_actual as stock, i.cantidad_minima as minimo,
                       i.id_inventario
                FROM producto p
                LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                LEFT JOIN inventario i ON p.id_producto = i.id_producto
                WHERE p.es_menu = 0
                ORDER BY p.id_producto DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCategorias() {
        $sql = "SELECT * FROM categoria_producto ORDER BY nombre_categoria ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrar($datos) {
        try {
            $this->conn->beginTransaction();

            $sqlProducto = "INSERT INTO producto (id_categoria, nombre, precio, unidad, imagen) 
                            VALUES (:id_categoria, :nombre, :precio, :unidad, :imagen)";
            $stmtProducto = $this->conn->prepare($sqlProducto);
            $stmtProducto->bindParam(":id_categoria", $datos['id_categoria']);
            $stmtProducto->bindParam(":nombre", $datos['nombre']);
            $stmtProducto->bindParam(":precio", $datos['precio']);
            $stmtProducto->bindParam(":unidad", $datos['unidad']);
            $stmtProducto->bindParam(":imagen", $datos['imagen']);
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
            $this->conn->beginTransaction();

            $sqlProducto = "UPDATE producto 
                            SET id_categoria = :id_categoria, nombre = :nombre, precio = :precio, 
                                unidad = :unidad, imagen = :imagen 
                            WHERE id_producto = :id_producto";
            $stmtProducto = $this->conn->prepare($sqlProducto);
            $stmtProducto->bindParam(":id_categoria", $datos['id_categoria']);
            $stmtProducto->bindParam(":nombre", $datos['nombre']);
            $stmtProducto->bindParam(":precio", $datos['precio']);
            $stmtProducto->bindParam(":unidad", $datos['unidad']);
            $stmtProducto->bindParam(":imagen", $datos['imagen']);
            $stmtProducto->bindParam(":id_producto", $id_producto);
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
