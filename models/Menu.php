<?php
class Menu {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT p.id_producto, p.nombre, p.precio, p.descripcion, p.imagen, p.disponible,
                       c.nombre_categoria as categoria
                FROM producto p
                LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                WHERE p.es_menu = 1
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

            $sqlProducto = "INSERT INTO producto (id_categoria, nombre, precio, descripcion, imagen, es_menu, disponible) 
                            VALUES (:id_categoria, :nombre, :precio, :descripcion, :imagen, 1, :disponible)";
            $stmtProducto = $this->conn->prepare($sqlProducto);
            $stmtProducto->bindParam(":id_categoria", $datos['id_categoria']);
            $stmtProducto->bindParam(":nombre", $datos['nombre']);
            $stmtProducto->bindParam(":precio", $datos['precio']);
            $stmtProducto->bindParam(":descripcion", $datos['descripcion']);
            $stmtProducto->bindParam(":imagen", $datos['imagen']);
            $stmtProducto->bindParam(":disponible", $datos['disponible']);
            $stmtProducto->execute();

            $id_producto = $this->conn->lastInsertId();

            // Insert a dummy inventory record to avoid breaking FOREIGN KEYs in other tables
            $sqlInventario = "INSERT INTO inventario (id_producto, cantidad_actual, cantidad_minima, fecha_actualizacion) 
                              VALUES (:id_producto, 0, 0, CURDATE())";
            $stmtInventario = $this->conn->prepare($sqlInventario);
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

    public function actualizar($id_producto, $datos) {
        try {
            $sqlProducto = "UPDATE producto 
                            SET id_categoria = :id_categoria, nombre = :nombre, precio = :precio, 
                                descripcion = :descripcion, imagen = :imagen, disponible = :disponible
                            WHERE id_producto = :id_producto AND es_menu = 1";
            $stmtProducto = $this->conn->prepare($sqlProducto);
            $stmtProducto->bindParam(":id_categoria", $datos['id_categoria']);
            $stmtProducto->bindParam(":nombre", $datos['nombre']);
            $stmtProducto->bindParam(":precio", $datos['precio']);
            $stmtProducto->bindParam(":descripcion", $datos['descripcion']);
            $stmtProducto->bindParam(":imagen", $datos['imagen']);
            $stmtProducto->bindParam(":disponible", $datos['disponible']);
            $stmtProducto->bindParam(":id_producto", $id_producto);
            $stmtProducto->execute();

            return true;
        } catch (Exception $e) {
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

            $sqlProducto = "DELETE FROM producto WHERE id_producto = :id_producto AND es_menu = 1";
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
