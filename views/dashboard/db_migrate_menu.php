<?php
require_once 'config/database.php';
try {
    $db = new database();
    $conn = $db->conectar();

    // Check if columns exist before adding them
    $checkQuery = "SHOW COLUMNS FROM producto LIKE 'es_menu'";
    $stmt = $conn->query($checkQuery);
    if($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE producto ADD COLUMN es_menu TINYINT(1) DEFAULT 0");
    }

    $checkQuery2 = "SHOW COLUMNS FROM producto LIKE 'disponible'";
    $stmt2 = $conn->query($checkQuery2);
    if($stmt2->rowCount() == 0) {
        $conn->exec("ALTER TABLE producto ADD COLUMN disponible TINYINT(1) DEFAULT 1");
    }
    
    // Add menu categories
    $cats = ['Platos Fuertes', 'Hamburguesas', 'Perros Calientes', 'Bebidas Frías', 'Bebidas Calientes', 'Postres', 'Acompañamientos', 'Combos'];
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
