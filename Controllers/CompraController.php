<?php
require_once __DIR__ . '/../config/database.php';

class CompraController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->conectar();
    }

    // ── CREAR PEDIDO DESDE CARRITO ────────────────────────────────
    public function procesarCompra($id_cliente, $carrito, $tipo = 'mesa', $id_mesa = null, $direccion = null) {
        try {
            $this->db->beginTransaction();

            // Obtener id_tipo_pedido
            $stmt = $this->db->prepare("SELECT id_tipo_pedido FROM tipo_pedido WHERE nombre_tipo = :t LIMIT 1");
            $stmt->execute([':t' => $tipo]);
            $id_tipo = $stmt->fetchColumn();
            if (!$id_tipo) $id_tipo = 1;

            // Estado pendiente
            $id_estado = $this->db->query("SELECT id_estado_pedido FROM estado_pedido WHERE nombre_estado='pendiente' LIMIT 1")->fetchColumn();
            if (!$id_estado) $id_estado = 1;

            // Primer mesero disponible
            $id_mesero = $this->db->query("SELECT id_mesero FROM mesero LIMIT 1")->fetchColumn();
            if (!$id_mesero) throw new Exception("No hay empleados registrados para asignar el pedido.");

            // Validar mesa si aplica
            $id_mesa_val = null;
            if ($tipo === 'mesa' && $id_mesa) {
                $chk = $this->db->prepare("SELECT id_mesa FROM mesa WHERE id_mesa = :id LIMIT 1");
                $chk->execute([':id' => $id_mesa]);
                if ($chk->fetchColumn()) $id_mesa_val = (int)$id_mesa;
            }

            // Dirección solo para domicilio
            $dir_val = ($tipo === 'domicilio' && !empty($direccion)) ? trim($direccion) : null;

            // Insertar pedido
            $stmt = $this->db->prepare("
                INSERT INTO pedido (id_cliente, id_mesa, direccion_entrega, id_mesero, id_tipo_pedido, fecha_pedido, id_estado_pedido)
                VALUES (:ic, :im, :dir, :imes, :it, CURDATE(), :ie)
            ");
            $stmt->execute([
                ':ic'   => $id_cliente,
                ':im'   => $id_mesa_val,
                ':dir'  => $dir_val,
                ':imes' => $id_mesero,
                ':it'   => $id_tipo,
                ':ie'   => $id_estado,
            ]);
            $id_pedido = $this->db->lastInsertId();

            // Insertar detalles y calcular total
            $total = 0;
            $stmtD = $this->db->prepare("
                INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal, observacion)
                VALUES (:ip, :iprod, :cant, :pu, :sub, :obs)
            ");
            foreach ($carrito as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $total   += $subtotal;
                $stmtD->execute([
                    ':ip'   => $id_pedido,
                    ':iprod'=> $item['id_producto'],
                    ':cant' => $item['cantidad'],
                    ':pu'   => $item['precio'],
                    ':sub'  => $subtotal,
                    ':obs'  => !empty($item['observacion']) ? trim($item['observacion']) : null,
                ]);
            }

            // Crear factura
            $stmtF = $this->db->prepare("
                INSERT INTO factura (id_pedido, id_cliente, fecha, metodo_pago, total_factura)
                VALUES (:ip, :ic, CURDATE(), 'efectivo', :total)
            ");
            $stmtF->execute([':ip'=>$id_pedido,':ic'=>$id_cliente,':total'=>$total]);

            // ── Notificar a todos los administradores ──
            $admins = $this->db->query("SELECT id_usuario FROM usuario WHERE id_rol = 1")->fetchAll(\PDO::FETCH_COLUMN);
            $clienteRow = $this->db->query("
                SELECT u.nombre, u.apellidos FROM cliente c JOIN usuario u ON c.id_usuario=u.id_usuario
                WHERE c.id_cliente=$id_cliente LIMIT 1
            ")->fetch(\PDO::FETCH_ASSOC);
            $nombreCliente = $clienteRow ? $clienteRow['nombre'].' '.$clienteRow['apellidos'] : 'Cliente';

            $stmtN = $this->db->prepare("
                INSERT INTO notificacion (id_usuario_destino, tipo, titulo, mensaje, id_referencia)
                VALUES (:uid, 'pedido', :titulo, :msg, :ref)
            ");
            foreach ($admins as $uid) {
                $stmtN->execute([
                    ':uid'   => $uid,
                    ':titulo'=> 'Nuevo pedido #'.str_pad($id_pedido,5,'0',STR_PAD_LEFT),
                    ':msg'   => $nombreCliente.' realizó un pedido por $'.number_format($total,0,',','.').' ('.count($carrito).' productos)',
                    ':ref'   => $id_pedido,
                ]);
            }

            // ── Notificar al cliente que su pago fue exitoso ──
            $id_usuario_cliente = $this->db->query("
                SELECT id_usuario FROM cliente WHERE id_cliente = $id_cliente LIMIT 1
            ")->fetchColumn();

            if ($id_usuario_cliente) {
                $productos = count($carrito);
                $this->db->prepare("
                    INSERT INTO notificacion (id_usuario_destino, tipo, titulo, mensaje, id_referencia)
                    VALUES (:uid, 'pago', :titulo, :msg, :ref)
                ")->execute([
                    ':uid'   => $id_usuario_cliente,
                    ':titulo'=> '¡Pago confirmado! 🎉',
                    ':msg'   => 'Tu pedido #'.str_pad($id_pedido,5,'0',STR_PAD_LEFT).' por $'.number_format($total,0,',','.').' ('.$productos.' '.($productos===1?'producto':'productos').') está siendo preparado.',
                    ':ref'   => $id_pedido,
                ]);
            }

            $this->db->commit();
            return ['ok'=>true, 'id_pedido'=>$id_pedido, 'total'=>$total];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['ok'=>false, 'error'=>$e->getMessage()];
        }
    }

    // ── NOTIFICACIONES NO LEÍDAS ──────────────────────────────────
    public static function getNoLeidas($id_usuario) {
        try {
            $db = (new Database())->conectar();
            return (int)$db->query("
                SELECT COUNT(*) FROM notificacion
                WHERE id_usuario_destino = $id_usuario
                  AND leida = 0
                  AND created_at >= NOW() - INTERVAL 3 DAY
            ")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public static function getNotificaciones($id_usuario, $limite = 8) {
        try {
            $db = (new Database())->conectar();
            // Limpiar notificaciones antiguas (> 3 días) antes de consultar
            $db->prepare("
                DELETE FROM notificacion
                WHERE id_usuario_destino = :id
                  AND created_at < NOW() - INTERVAL 3 DAY
            ")->execute([':id' => $id_usuario]);

            $stmt = $db->prepare("
                SELECT * FROM notificacion
                WHERE id_usuario_destino = :id
                ORDER BY created_at DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':id',  $id_usuario, \PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limite,     \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public static function marcarLeidas($id_usuario) {
        try {
            $db = (new Database())->conectar();
            $db->prepare("UPDATE notificacion SET leida=1 WHERE id_usuario_destino=:id")->execute([':id'=>$id_usuario]);
        } catch (Exception $e) {
            // silencioso
        }
    }

    /**
     * Elimina notificaciones con más de 3 días de antigüedad para el usuario dado.
     * Se ejecuta automáticamente al cargar notificaciones.
     */
    public static function limpiarAntiguas($id_usuario) {
        try {
            $db = (new Database())->conectar();
            $db->prepare("
                DELETE FROM notificacion
                WHERE id_usuario_destino = :id
                  AND created_at < NOW() - INTERVAL 3 DAY
            ")->execute([':id' => $id_usuario]);
        } catch (Exception $e) {
            // silencioso
        }
    }

    // ── NOTIFICACIONES DE PROMOCIONES PRÓXIMAS A VENCER ──────────
    // Llama esto al cargar el dashboard del cliente para generar
    // notificaciones de promociones que vencen en los próximos 3 días
    public static function notificarPromocionesCliente($id_usuario) {
        try {
            $db = (new database())->conectar();

            // Verificar que la tabla promocion exista
            $check = $db->query("SHOW TABLES LIKE 'promocion'")->fetchColumn();
            if (!$check) return;

            // Promociones que vencen en los próximos 3 días y aún no fueron notificadas a este usuario
            $stmt = $db->prepare("
                SELECT p.* FROM promocion p
                WHERE p.activa = 1
                  AND p.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                  AND p.id_promocion NOT IN (
                      SELECT id_referencia FROM notificacion
                      WHERE id_usuario_destino = :uid
                        AND tipo = 'promocion'
                        AND id_referencia IS NOT NULL
                  )
            ");
            $stmt->execute([':uid' => $id_usuario]);
            $promociones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmtN = $db->prepare("
                INSERT INTO notificacion (id_usuario_destino, tipo, titulo, mensaje, id_referencia)
                VALUES (:uid, 'promocion', :titulo, :msg, :ref)
            ");

            foreach ($promociones as $promo) {
                $diasRestantes = (int)((strtotime($promo['fecha_fin']) - strtotime(date('Y-m-d'))) / 86400);
                $textoTiempo   = $diasRestantes === 0 ? '¡Vence hoy!' : "Vence en $diasRestantes día".($diasRestantes===1?'':'s');

                $stmtN->execute([
                    ':uid'   => $id_usuario,
                    ':titulo'=> '🔥 '.$promo['titulo'].' — '.$textoTiempo,
                    ':msg'   => $promo['descripcion'].($promo['descuento'] > 0 ? ' ('.$promo['descuento'].'% de descuento)' : ''),
                    ':ref'   => $promo['id_promocion'],
                ]);
            }
        } catch (Exception $e) {
            // Silencioso — no romper el dashboard si falla
        }
    }
}
