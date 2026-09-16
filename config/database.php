<?php

class Database
{
    private $host = "127.0.0.1";
    private $db_name = "retrorestaurant";
    private $port = "3320";
    private $username = "root";
    private $contraseña = "";

    public $conn;

    public function conectar()
    {
        $this->conn = null;

        try {

            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->contraseña
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {

            throw new RuntimeException("Error de conexión: " . $e->getMessage());
        }

        return $this->conn;
    }
}

// Alias para compatibilidad: permite usar tanto "new Database()" como "new database()"
// PHP en Linux es case-sensitive para nombres de clase, esto resuelve el problema
if (!class_exists('database')) {
    class_alias('Database', 'database');
}
?>
