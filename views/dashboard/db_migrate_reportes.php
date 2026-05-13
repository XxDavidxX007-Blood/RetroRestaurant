<?php
require_once 'config/database.php';
try {
    $db = new database();
    $conn = $db->conectar();

    $sql = "CREATE TABLE IF NOT EXISTS reportes_guardados (
        id_reporte INT PRIMARY KEY AUTO_INCREMENT,
        fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        generado_por_nombre VARCHAR(100),
        tipo_filtro VARCHAR(50),
        valor_filtro VARCHAR(50),
        total_productos INT,
        valor_total DECIMAL(12,2),
        datos_json LONGTEXT
    )";
    
    $conn->exec($sql);
    echo "SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
