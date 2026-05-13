<?php
class Usuario {
    private $conn;
    private $tabla = "usuario";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function existeemail($email) {
        $sql = "SELECT id_usuario FROM " . $this->tabla . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function obtenerPorEmail($email) {
        $sql = "SELECT * FROM " . $this->tabla . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registrar($datos) {
        try {
            $this->conn->beginTransaction();

            $sqlUsuario = "INSERT INTO usuario
                (nombre, apellidos, email, telefono, password, id_rol )
                VALUES
                (:nombre, :apellidos, :email, :telefono, :password, :id_rol )";

            $stmtUsuario = $this->conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(":nombre", $datos['nombre']);
            $stmtUsuario->bindParam(":apellidos", $datos['apellidos']);
            $stmtUsuario->bindParam(":email", $datos['email']);
            $stmtUsuario->bindParam(":telefono", $datos["telefono"]);
            $stmtUsuario->bindParam(":password", $datos['password']);
            $stmtUsuario->bindParam(":id_rol", $datos['id_rol']);
            $stmtUsuario->execute();

            $id_usuario = $this->conn->lastInsertId();

            //if ($datos['id_rol'] === 'estudiante') {
                //$sqlEstudiante = "INSERT INTO estudiantes
                    //(id_usuario, codigo_estudiantil, fecha_nacimiento, grado_actual)
                    //VALUES
                    //(:id_usuario, :codigo_estudiantil, :fecha_nacimiento, :grado_actual)";

                //$stmtEstudiante = $this->conn->prepare($sqlEstudiante);
                //$stmtEstudiante->bindParam(":id_usuario", $id_usuario);
                //$stmtEstudiante->bindParam(":codigo_estudiantil", $datos['codigo_estudiantil']);
                //$stmtEstudiante->bindParam(":fecha_nacimiento", $datos['fecha_nacimiento']);
                //$stmtEstudiante->bindParam(":grado_actual", $datos['grado_actual']);
                //$stmtEstudiante->execute();
            //}

            if (in_array($datos['id_rol'], ['3', 3])) {
                $sqlcliente = "INSERT INTO cliente
                    (id_usuario)
                    VALUES
                    (:id_usuario)";
                $stmtcliente = $this->conn->prepare($sqlcliente);
                $stmtcliente->bindParam(":id_usuario", $id_usuario);
                $stmtcliente->execute();
            }

            if (in_array($datos['id_rol'], ['2', 2])) {
                $stmtMesero = $this->conn->prepare("INSERT IGNORE INTO mesero (id_usuario) VALUES (:id_usuario)");
                $stmtMesero->bindParam(":id_usuario", $id_usuario);
                $stmtMesero->execute();
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return "Error al registrar: " . $e->getMessage();
        }
    }

    public function obtenerTodos() {
        $sql = "SELECT id_usuario, nombre, apellidos, email, telefono, id_rol FROM " . $this->tabla . " ORDER BY id_usuario DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id_usuario) {
        $sql = "SELECT * FROM " . $this->tabla . " WHERE id_usuario = :id_usuario LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizar($id_usuario, $datos) {
        try {
            $sql = "UPDATE " . $this->tabla . " 
                    SET nombre = :nombre, apellidos = :apellidos, id_rol = :id_rol";
            
            if (!empty($datos['password'])) {
                $sql .= ", password = :password";
            }
            
            $sql .= " WHERE id_usuario = :id_usuario";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":nombre", $datos['nombre']);
            $stmt->bindParam(":apellidos", $datos['apellidos']);
            $stmt->bindParam(":id_rol", $datos['id_rol']);
            $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            
            if (!empty($datos['password'])) {
                $stmt->bindParam(":password", $datos['password']);
            }
            
            $stmt->execute();

            // Si el nuevo rol es cliente (3), asegurarse de que exista en tabla cliente
            if (in_array($datos['id_rol'], ['3', 3])) {
                $check = $this->conn->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = :id");
                $check->execute([':id' => $id_usuario]);
                if (!$check->fetch()) {
                    $ins = $this->conn->prepare("INSERT INTO cliente (id_usuario) VALUES (:id)");
                    $ins->execute([':id' => $id_usuario]);
                }
            }

            // Si el nuevo rol es empleado (2), asegurarse de que exista en tabla mesero
            if (in_array($datos['id_rol'], ['2', 2])) {
                $check = $this->conn->prepare("SELECT id_mesero FROM mesero WHERE id_usuario = :id");
                $check->execute([':id' => $id_usuario]);
                if (!$check->fetch()) {
                    $ins = $this->conn->prepare("INSERT IGNORE INTO mesero (id_usuario) VALUES (:id)");
                    $ins->execute([':id' => $id_usuario]);
                }
            }

            return true;
        } catch (Exception $e) {
            return "Error al actualizar: " . $e->getMessage();
        }
    }
    public function actualizarPerfil($id_usuario, $datos) {
        try {
            $sql = "UPDATE " . $this->tabla . "
                    SET nombre = :nombre, apellidos = :apellidos, telefono = :telefono";

            if (!empty($datos['password'])) {
                $sql .= ", password = :password";
            }
            if (array_key_exists('foto', $datos) && $datos['foto'] !== null) {
                $sql .= ", foto = :foto";
            }

            $sql .= " WHERE id_usuario = :id_usuario";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":nombre",     $datos['nombre']);
            $stmt->bindParam(":apellidos",  $datos['apellidos']);
            $stmt->bindParam(":telefono",   $datos['telefono']);
            $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);

            if (!empty($datos['password'])) {
                $stmt->bindParam(":password", $datos['password']);
            }
            if (array_key_exists('foto', $datos) && $datos['foto'] !== null) {
                $stmt->bindParam(":foto", $datos['foto']);
            }

            $stmt->execute();
            return true;
        } catch (Exception $e) {
            return "Error al actualizar perfil: " . $e->getMessage();
        }
    }
}