<?php
require_once 'config/database.php';
try {
    $db = new database();
    $conn = $db->conectar();

    // Check if columns exist before adding them
    $checkQuery = "SHOW COLUMNS FROM producto LIKE 'unidad'";
    $stmt = $conn->query($checkQuery);
    if($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE producto ADD COLUMN unidad VARCHAR(10) DEFAULT 'unid'");
    }

    $checkQuery2 = "SHOW COLUMNS FROM producto LIKE 'imagen'";
    $stmt2 = $conn->query($checkQuery2);
    if($stmt2->rowCount() == 0) {
        $conn->exec("ALTER TABLE producto ADD COLUMN imagen VARCHAR(255) DEFAULT '📦'");
    }
    
    // Add missing default categories if not exist
    $cats = ['Vegetales','Frutas', 'Carnes', 'Embutidos', 'Bebidas', 'Salsas', 'Lácteos','Harinas'];
    foreach($cats as $cat) {
        $stmt = $conn->prepare("SELECT id_categoria FROM categoria_producto WHERE nombre_categoria = ?");
        $stmt->execute([$cat]);
        if($stmt->rowCount() == 0) {
            $conn->prepare("INSERT INTO categoria_producto (nombre_categoria) VALUES (?)")->execute([$cat]);
        }
    }
    
    echo "SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
