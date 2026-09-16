# 🛡️ GUÍA DE DEFENSA — Posibles Sabotajes del Instructor

> **Propósito:** Prepararte para identificar y corregir cualquier error que tu instructor introduzca en el código de RetroRestaurant. Cada sección indica **qué archivo revisar**, **qué buscar** y **cuál es la corrección exacta**.

---

## ÍNDICE

1. [Base de Datos — config/database.php](#1-base-de-datos)
2. [Autenticación — AuthController.php](#2-autenticación)
3. [Registro de Usuarios — UsuarioController.php + models/usuario.php](#3-registro-de-usuarios)
4. [Gestión de Usuarios Admin — AdminUsuarioController.php](#4-gestión-de-usuarios-admin)
5. [Inventario — InventarioController + models/Inventario.php](#5-inventario)
6. [Menú — MenuController + models/Menu.php](#6-menú)
7. [Pedidos — PedidoController + models/Pedido.php](#7-pedidos)
8. [Reservas — ReservaController + models/Reserva.php](#8-reservas)
9. [Domicilios — DomicilioController + models/Domicilio.php](#9-domicilios)
10. [Compras/Carrito — CompraController + ProcesarCompra.php](#10-comprascarrito)
11. [Reportes — ReportesController + models/Reportes.php](#11-reportes)
12. [Dashboard Admin — DashboardController + models/Dashboard.php](#12-dashboard-admin)
13. [Perfil — PerfilController](#13-perfil)
14. [Notificaciones — NotifController + sidebar.php](#14-notificaciones)
15. [Vistas y Layouts — header.php, sidebar.php](#15-vistas-y-layouts)
16. [Seguridad y Sesiones](#16-seguridad-y-sesiones)
17. [Checklist Rápido de Revisión](#17-checklist-rápido-de-revisión)

---

## 1. BASE DE DATOS

**Archivo:** `config/database.php`

### Sabotaje 1.1 — Credenciales incorrectas
**Qué hace:** Cambia el host, nombre de BD, usuario o contraseña.
```php
// ❌ Saboteado
private $host = "localhost";           // era: sql306.byethost24.com
private $db_name = "retrorestaurant"; // era: b24_41909782_retrorestaurant
private $username = "root";           // era: b24_41909782
private $contraseña = "wrongpass";    // era: 1597531208
```
**Síntoma:** Pantalla en blanco o error "Error de conexión" en todas las páginas.
**Solución:** Restaurar los valores exactos:
```php
private $host = "sql306.byethost24.com";
private $db_name = "b24_41909782_retrorestaurant";
private $username = "b24_41909782";
private $contraseña = "1597531208";
```

### Sabotaje 1.2 — Eliminar el alias de clase
**Qué hace:** Borra el bloque `class_alias` al final del archivo.
```php
// ❌ Eliminado
if (!class_exists('database')) {
    class_alias('Database', 'database');
}
```
**Síntoma:** Error `Class "database" not found` en vistas que usan `new database()` (header.php, cliente.php, empleado.php, ProcesarCompra.php).
**Solución:** Restaurar el bloque completo al final de `database.php`.

### Sabotaje 1.3 — Cambiar charset
**Qué hace:** Cambia `utf8mb4` por `utf8` o lo elimina.
**Síntoma:** Caracteres especiales (tildes, ñ) se muestran como `?` o `â€™`.
**Solución:** El DSN debe ser: `"mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4"`

### Sabotaje 1.4 — Quitar ERRMODE_EXCEPTION
**Qué hace:** Elimina `$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION)`.
**Síntoma:** Los errores SQL no se capturan, los catch no funcionan, datos no se guardan silenciosamente.
**Solución:** Esa línea es obligatoria después de crear el PDO.

---

## 2. AUTENTICACIÓN

**Archivo:** `Controllers/AuthController.php`

### Sabotaje 2.1 — Romper la verificación de contraseña
**Qué hace:** Cambia `password_verify` por comparación directa o invierte la condición.
```php
// ❌ Saboteado — nunca deja entrar
if (password_verify($password, $usuario['password'])) {
// ❌ Saboteado — siempre deja entrar sin verificar
if (!password_verify($password, $usuario['password'])) {
// ❌ Saboteado — comparación plana (no funciona con hash)
if ($password === $usuario['password']) {
```
**Síntoma:** Nadie puede iniciar sesión, o cualquiera entra con cualquier contraseña.
**Solución correcta:**
```php
if (!password_verify($password, $usuario['password'])) {
    // mostrar error y redirigir
}
// si pasa el if, el login es exitoso
```

### Sabotaje 2.2 — Redireccionamiento de roles incorrecto
**Qué hace:** Intercambia los destinos del switch de roles.
```php
// ❌ Saboteado — admin va a cliente, cliente va a admin
case '1': header("Location: {$base}/views/dashboard/cliente.php"); exit;
case '3': header("Location: {$base}/views/dashboard/admin_dashboard.php"); exit;
```
**Síntoma:** El admin ve el panel de cliente y viceversa.
**Solución correcta:**
```php
case '1': case 'administrador': header("Location: {$base}/views/dashboard/admin_dashboard.php"); exit;
case '2': case 'empleado':      header("Location: {$base}/views/dashboard/empleado.php"); exit;
case '3': case 'cliente':       header("Location: {$base}/views/dashboard/cliente.php"); exit;
```

### Sabotaje 2.3 — Romper getBaseUrl()
**Qué hace:** Cambia el número de niveles en `dirname()`.
```php
// ❌ Saboteado — sube 3 niveles en vez de 2
$base = dirname(dirname(dirname($script)));
// ❌ Saboteado — no sube ningún nivel
$base = $script;
```
**Síntoma:** Redirecciones van a URLs incorrectas (404 o loop infinito).
**Solución:** `dirname(dirname($script))` — exactamente 2 niveles desde `Controllers/AuthController.php`.

### Sabotaje 2.4 — Eliminar session_regenerate_id
**Qué hace:** Borra `session_regenerate_id(true)` tras el login exitoso.
**Síntoma:** Funciona pero es vulnerable a session fixation (el instructor puede señalarlo como falla de seguridad).
**Solución:** Esa línea debe estar justo antes de asignar `$_SESSION['usuario']`.

### Sabotaje 2.5 — Logout no destruye la sesión
**Qué hace:** Elimina `session_destroy()` del método logout.
```php
// ❌ Saboteado
public function logout() {
    session_unset();
    // falta session_destroy()
    header("Location: ...");
}
```
**Síntoma:** Al cerrar sesión, la sesión sigue activa en el servidor.
**Solución:** Ambas líneas son necesarias: `session_unset()` + `session_destroy()`.

---

## 3. REGISTRO DE USUARIOS

**Archivos:** `Controllers/UsuarioController.php` + `models/usuario.php`

### Sabotaje 3.1 — Quitar el hash de contraseña
**Qué hace:** Guarda la contraseña en texto plano.
```php
// ❌ Saboteado
$datos['password'] = $password; // sin hash
// o
$datos['password'] = md5($password); // hash débil
```
**Síntoma:** El registro funciona pero `password_verify()` en el login falla porque espera un hash bcrypt.
**Solución:** `$password = password_hash($password, PASSWORD_DEFAULT);` antes de armar `$datos`.

### Sabotaje 3.2 — Romper la validación de email duplicado
**Qué hace:** Invierte la condición de `existeemail`.
```php
// ❌ Saboteado — bloquea emails nuevos, permite duplicados
if (!$usuario->existeemail($email)) {
    // mostrar error de email existente
}
```
**Síntoma:** No se puede registrar ningún email nuevo, o se permiten emails duplicados.
**Solución correcta:** `if ($usuario->existeemail($email)) { /* mostrar error */ }`

### Sabotaje 3.3 — No insertar en tabla cliente
**Qué hace:** Elimina el bloque que inserta en `cliente` cuando `id_rol = 3`.
```php
// ❌ Saboteado — falta este bloque en models/usuario.php
if (in_array($datos['id_rol'], ['3', 3])) {
    $sqlcliente = "INSERT INTO cliente (id_usuario) VALUES (:id_usuario)";
    // ...
}
```
**Síntoma:** El usuario se registra pero no puede hacer pedidos ni reservas (no existe en tabla `cliente`). El dashboard cliente muestra 0 en todo.
**Solución:** El bloque debe estar dentro de la transacción en `registrar()`.

### Sabotaje 3.4 — Cambiar la validación de contraseñas
**Qué hace:** Modifica la longitud mínima o elimina la comparación.
```php
// ❌ Saboteado — mínimo 10 en vez de 6
if (strlen($password) < 10) { ... }
// ❌ Saboteado — no compara las contraseñas
// if ($password !== $confirmar_password) { ... }  ← comentado
```
**Síntoma:** Usuarios no pueden registrarse con contraseñas válidas, o se registran con contraseñas que no coinciden.
**Solución:** Mínimo 6 caracteres + comparación `$password !== $confirmar_password`.

### Sabotaje 3.5 — Rollback no funciona
**Qué hace:** Elimina el `rollBack()` del catch o el `beginTransaction()`.
```php
// ❌ Saboteado — sin transacción
// $this->conn->beginTransaction(); ← comentado
$stmtUsuario->execute();
$stmtcliente->execute();
// $this->conn->commit(); ← comentado
```
**Síntoma:** Si falla la inserción en `cliente`, el usuario queda en `usuario` sin su registro de cliente (datos inconsistentes).
**Solución:** La transacción debe envolver ambos INSERTs.

---

## 4. GESTIÓN DE USUARIOS ADMIN

**Archivo:** `Controllers/AdminUsuarioController.php`

### Sabotaje 4.1 — Quitar verificación de rol admin
**Qué hace:** Elimina o comenta la verificación de sesión al inicio del archivo.
```php
// ❌ Saboteado — cualquiera puede acceder
// if (!isset($_SESSION['usuario']) || !in_array(...)) { ... }
```
**Síntoma:** Cualquier usuario (o sin sesión) puede crear/editar usuarios.
**Solución:** La verificación debe estar antes de la clase:
```php
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador'])) {
    header("Location: {$base}/views/usuarios/login.php"); exit;
}
```

### Sabotaje 4.2 — No sincronizar tabla cliente/mesero al cambiar rol
**Qué hace:** Elimina los bloques que insertan en `cliente` o `mesero` al editar rol.
**Síntoma:** Un usuario cambiado a rol 3 no puede hacer pedidos; cambiado a rol 2 no aparece como mesero disponible.
**Solución:** En `models/usuario.php`, método `actualizar()`, deben existir ambos bloques de verificación e inserción.

### Sabotaje 4.3 — Cambiar la acción del formulario en la vista
**Qué hace:** Modifica la URL del form en `admin.php`.
```html
<!-- ❌ Saboteado -->
<form action="../../Controllers/UsuarioController.php" method="POST">
<!-- correcto -->
<form action="../../Controllers/AdminUsuarioController.php?accion=crear" method="POST">
```
**Síntoma:** El formulario de crear usuario va al controller equivocado.
**Solución:** Verificar que el `action` del form de crear sea `AdminUsuarioController.php?accion=crear` y el de editar `?accion=editar`.

---

## 5. INVENTARIO

**Archivos:** `Controllers/InventarioController.php` + `models/Inventario.php`

### Sabotaje 5.1 — Romper la transacción de crear producto
**Qué hace:** Elimina el INSERT en `inventario` dentro de `registrar()`.
```php
// ❌ Saboteado — solo inserta en producto, no en inventario
$stmtProducto->execute();
$id_producto = $this->conn->lastInsertId();
// falta el INSERT en inventario
$this->conn->commit();
```
**Síntoma:** El producto se crea pero no aparece en el inventario (la query de `obtenerTodos()` hace INNER JOIN con `inventario`).
**Solución:** Ambos INSERTs deben estar en la misma transacción.

### Sabotaje 5.2 — Cambiar el WHERE en obtenerTodos
**Qué hace:** Modifica la condición que filtra productos de inventario vs menú.
```php
// ❌ Saboteado — muestra productos del menú en inventario
$whereEsMenu = $tieneEsMenu ? "AND p.es_menu = 1" : ""; // era es_menu = 0
// ❌ Saboteado — cambia INNER JOIN por LEFT JOIN sin el WHERE
"WHERE i.id_inventario IS NOT NULL" // ← si lo elimina, muestra productos sin inventario
```
**Síntoma:** El inventario muestra platos del menú mezclados, o muestra productos sin stock.
**Solución:** La condición correcta es `es_menu = 0 OR es_menu IS NULL` y el `WHERE i.id_inventario IS NOT NULL`.

### Sabotaje 5.3 — Romper el orden de eliminación (FK)
**Qué hace:** Invierte el orden de DELETE en `eliminar()`.
```php
// ❌ Saboteado — intenta borrar producto antes que inventario
DELETE FROM producto WHERE id_producto = :id  // FK violation
DELETE FROM inventario WHERE id_producto = :id
```
**Síntoma:** Error de foreign key al eliminar un producto.
**Solución:** Siempre primero `DELETE FROM inventario`, luego `DELETE FROM producto`.

### Sabotaje 5.4 — Exportar CSV con separador incorrecto
**Qué hace:** Cambia el separador de `;` a `,` en `fputcsv`.
**Síntoma:** El CSV se descarga pero Excel en español no separa las columnas correctamente.
**Solución:** `fputcsv($output, [...], ';')` — el punto y coma es el separador correcto para Excel en español.

---

## 6. MENÚ

**Archivos:** `Controllers/MenuController.php` + `models/Menu.php`

### Sabotaje 6.1 — Cambiar el filtro de es_menu en obtenerTodos
**Qué hace:** Cambia `es_menu = 1` por `es_menu = 0` o elimina el WHERE.
```php
// ❌ Saboteado — muestra productos de inventario en el menú
$whereMenu = $tieneEsMenu ? "WHERE p.es_menu = 0" : "WHERE p.disponible = 1";
```
**Síntoma:** El panel de menú muestra ingredientes del inventario en vez de platos.
**Solución:** `WHERE p.es_menu = 1` para el menú.

### Sabotaje 6.2 — No insertar registro en inventario al crear plato
**Qué hace:** Elimina el INSERT dummy en `inventario` dentro de `registrar()` del modelo Menu.
**Síntoma:** Al crear un plato, falla con error de FK si otras tablas referencian `inventario`. O el plato aparece en el inventario con stock.
**Solución:** El INSERT en inventario con `cantidad_actual=0, cantidad_minima=0` es necesario para mantener la integridad referencial.

### Sabotaje 6.3 — Romper la condición AND es_menu=1 en actualizar/eliminar
**Qué hace:** Elimina `AND es_menu = 1` del WHERE en UPDATE o DELETE.
```php
// ❌ Saboteado — puede editar/eliminar cualquier producto
"UPDATE producto SET ... WHERE id_producto = :id_producto"
// correcto
"UPDATE producto SET ... WHERE id_producto = :id_producto AND es_menu = 1"
```
**Síntoma:** Se pueden modificar o eliminar productos del inventario desde el panel de menú.
**Solución:** Siempre incluir `AND es_menu = 1` en las operaciones de menú.

### Sabotaje 6.4 — Cambiar extensiones permitidas para imágenes
**Qué hace:** Elimina extensiones o agrega extensiones peligrosas.
```php
// ❌ Saboteado — permite PHP
$allowed = ['jpg','jpeg','png','webp','php'];
// ❌ Saboteado — no permite nada
$allowed = [];
```
**Síntoma:** No se pueden subir imágenes, o se pueden subir archivos PHP (vulnerabilidad).
**Solución:** `$allowed = ['jpg','jpeg','png','webp']` — exactamente estas 4.

---

## 7. PEDIDOS

**Archivos:** `Controllers/PedidoController.php` + `models/Pedido.php`

### Sabotaje 7.1 — Romper cambiar_estado
**Qué hace:** Invierte los parámetros en `cambiarEstado()`.
```php
// ❌ Saboteado — pasa id_estado donde va id_pedido
$stmt->execute([':estado' => $id_pedido, ':id' => $id_estado_pedido]);
```
**Síntoma:** Al cambiar estado de un pedido, actualiza el pedido equivocado o no actualiza nada.
**Solución:** `[':estado' => $id_estado_pedido, ':id' => $id_pedido]`

### Sabotaje 7.2 — Cambiar ORDER BY en getAll
**Qué hace:** Cambia `DESC` por `ASC` o elimina el ORDER BY.
```php
// ❌ Saboteado — muestra los más antiguos primero
ORDER BY p.id_pedido ASC
```
**Síntoma:** Los pedidos más recientes aparecen al final de la lista.
**Solución:** `ORDER BY p.id_pedido DESC`

### Sabotaje 7.3 — Romper la paginación
**Qué hace:** Cambia el cálculo del offset o el LIMIT.
```php
// ❌ Saboteado — offset incorrecto
$offset = $pagina * $por_pagina; // era ($pagina - 1) * $por_pagina
```
**Síntoma:** La primera página muestra los registros de la segunda, la paginación está desfasada.
**Solución:** `$offset = ($pagina - 1) * $por_pagina`

### Sabotaje 7.4 — Eliminar el JOIN con factura
**Qué hace:** Elimina el `LEFT JOIN factura f ON f.id_pedido = p.id_pedido`.
**Síntoma:** El total de todos los pedidos aparece como 0.
**Solución:** El JOIN con factura es necesario para obtener `total_factura`.

### Sabotaje 7.5 — Cambiar bindValue a bindParam en LIMIT/OFFSET
**Qué hace:** Usa `bindParam` en vez de `bindValue` para `:limit` y `:offset`.
```php
// ❌ Saboteado — bindParam pasa por referencia, puede fallar con PDO::PARAM_INT
$stmt->bindParam(':limit', $por_pagina, PDO::PARAM_INT);
```
**Síntoma:** Error PDO o resultados incorrectos en la paginación.
**Solución:** Usar `bindValue` para valores literales en LIMIT y OFFSET.

---

## 8. RESERVAS

**Archivos:** `Controllers/ReservaController.php` + `models/Reserva.php`

### Sabotaje 8.1 — Romper el cálculo de variación
**Qué hace:** Cambia la fórmula de variación porcentual.
```php
// ❌ Saboteado — fórmula invertida
$varHoy = $reservasHoy > 0
    ? round((($reservasAyer - $reservasHoy) / $reservasHoy) * 100, 1)
    : 0;
```
**Síntoma:** Los KPIs de variación muestran porcentajes incorrectos o negativos cuando deberían ser positivos.
**Solución correcta:** `(($reservasHoy - $reservasAyer) / $reservasAyer) * 100`

### Sabotaje 8.2 — Cambiar el filtro de tab "hoy"
**Qué hace:** Cambia `date('Y-m-d')` por una fecha hardcodeada o incorrecta.
```php
// ❌ Saboteado
if ($filtro_tab === 'hoy') $fecha_efectiva = '2020-01-01';
```
**Síntoma:** El tab "Hoy" no muestra las reservas del día actual.
**Solución:** `$fecha_efectiva = date('Y-m-d')` para hoy, `date('Y-m-d', strtotime('+1 day'))` para mañana.

### Sabotaje 8.3 — Eliminar el ORDER BY en getAll
**Qué hace:** Elimina `ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC`.
**Síntoma:** Las reservas aparecen en orden aleatorio, no cronológico.
**Solución:** El ORDER BY doble (fecha + hora) es necesario para mostrar reservas en orden temporal.

### Sabotaje 8.4 — Cambiar el JOIN de estado_reserva
**Qué hace:** Cambia `JOIN` por `LEFT JOIN` en `estado_reserva`.
```php
// ❌ Saboteado — muestra reservas sin estado
LEFT JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
```
**Síntoma:** Aparecen reservas con estado NULL o vacío.
**Solución:** Debe ser `JOIN` (INNER JOIN) para garantizar que toda reserva tenga estado.

---

## 9. DOMICILIOS

**Archivos:** `Controllers/DomicilioController.php` + `models/Domicilio.php`

### Sabotaje 9.1 — Cambiar el filtro de tipo_pedido
**Qué hace:** Cambia el LIKE de domicilio.
```php
// ❌ Saboteado — no encuentra ningún domicilio
"(tp.nombre_tipo LIKE '%mesa%')"
// ❌ Saboteado — falta el OR delivery
"(tp.nombre_tipo LIKE '%domicilio%')"
```
**Síntoma:** La sección de domicilios aparece vacía aunque haya pedidos a domicilio.
**Solución:** `(tp.nombre_tipo LIKE '%domicilio%' OR tp.nombre_tipo LIKE '%delivery%')`

### Sabotaje 9.2 — Romper el orden de eliminación en cascada
**Qué hace:** Elimina el DELETE de `detalle_pedido` o `factura` antes de borrar el pedido.
```php
// ❌ Saboteado — falta borrar detalle y factura primero
// DELETE FROM detalle_pedido WHERE id_pedido = :id  ← eliminado
// DELETE FROM factura WHERE id_pedido = :id          ← eliminado
DELETE FROM pedido WHERE id_pedido = :id
```
**Síntoma:** Error de foreign key al intentar eliminar un domicilio que tiene detalles o factura.
**Solución:** El orden correcto es: `detalle_pedido` → `factura` → `pedido`.

### Sabotaje 9.3 — No obtener el id_tipo_pedido correcto en crear
**Qué hace:** Hardcodea un id_tipo_pedido incorrecto.
```php
// ❌ Saboteado — usa tipo "mesa" para domicilios
$tp = 1; // hardcodeado, puede ser el tipo "mesa"
```
**Síntoma:** Los domicilios creados aparecen como pedidos de mesa, no en la sección de domicilios.
**Solución:** Debe hacer la query: `SELECT id_tipo_pedido FROM tipo_pedido WHERE nombre_tipo LIKE '%domicilio%' LIMIT 1`

---

## 10. COMPRAS/CARRITO

**Archivos:** `Controllers/CompraController.php` + `Controllers/ProcesarCompra.php`

### Sabotaje 10.1 — Romper el cálculo del total
**Qué hace:** Cambia la fórmula del subtotal o del total.
```php
// ❌ Saboteado — suma precio sin multiplicar por cantidad
$subtotal = $item['precio'];
$total += $subtotal;
// ❌ Saboteado — no acumula el total
$total = $subtotal; // sobreescribe en vez de sumar
```
**Síntoma:** La factura se crea con total incorrecto (solo el precio del último producto, o el precio unitario sin cantidad).
**Solución:** `$subtotal = $item['precio'] * $item['cantidad']; $total += $subtotal;`

### Sabotaje 10.2 — No crear la factura
**Qué hace:** Comenta o elimina el INSERT en `factura`.
**Síntoma:** El pedido se crea pero el total aparece como 0 en todas las vistas (usan `IFNULL(f.total_factura, 0)`).
**Solución:** El INSERT en `factura` es parte de la transacción y es obligatorio.

### Sabotaje 10.3 — Romper la verificación de rol en ProcesarCompra
**Qué hace:** Cambia el rol permitido.
```php
// ❌ Saboteado — solo admins pueden comprar
if (!in_array($_SESSION['usuario']['id_rol'], [1,'1','administrador'])) {
// correcto — solo clientes
if (!in_array($_SESSION['usuario']['id_rol'], [3,'3','cliente'])) {
```
**Síntoma:** Los clientes reciben "No autorizado" al intentar comprar.
**Solución:** El rol permitido para comprar es `[3,'3','cliente']`.

### Sabotaje 10.4 — No notificar al admin ni al cliente
**Qué hace:** Elimina los bloques de INSERT en `notificacion`.
**Síntoma:** Las compras se procesan pero no aparecen notificaciones en el panel del admin ni del cliente.
**Solución:** Deben existir dos bloques de notificación: uno para admins (tipo 'pedido') y uno para el cliente (tipo 'pago').

### Sabotaje 10.5 — Rollback no se ejecuta
**Qué hace:** Elimina el `$this->db->rollBack()` del catch.
```php
// ❌ Saboteado
} catch (Exception $e) {
    // falta rollBack()
    return ['ok'=>false, 'error'=>$e->getMessage()];
}
```
**Síntoma:** Si falla cualquier paso, quedan datos parciales (pedido sin detalle, o detalle sin factura).
**Solución:** El catch siempre debe llamar `$this->db->rollBack()`.

---

## 11. REPORTES

**Archivos:** `Controllers/ReportesController.php` + `models/Reportes.php`

### Sabotaje 11.1 — Cambiar el cálculo de valor_total en KPIs
**Qué hace:** Cambia la fórmula de valor total del inventario.
```php
// ❌ Saboteado — suma solo precios sin multiplicar por stock
SUM(p.precio) as valor
// correcto
SUM(p.precio * i.cantidad_actual) as valor
```
**Síntoma:** El KPI "Valor Total del Inventario" muestra un número incorrecto.
**Solución:** `SUM(p.precio * i.cantidad_actual)` — precio por cantidad actual.

### Sabotaje 11.2 — Romper la clasificación de estado de stock
**Qué hace:** Cambia las condiciones de En Stock / Stock Bajo / Sin Stock.
```php
// ❌ Saboteado — invierte las condiciones
if ($item['cantidad_actual'] == 0) {
    $estados['En Stock']++;  // era Sin Stock
} elseif ($item['cantidad_actual'] <= $item['cantidad_minima']) {
    $estados['Sin Stock']++; // era Stock Bajo
}
```
**Síntoma:** El gráfico de estado de stock muestra datos invertidos.
**Solución correcta:
- `cantidad_actual == 0` → Sin Stock
- `cantidad_actual <= cantidad_minima` → Stock Bajo
- resto → En Stock

### Sabotaje 11.3 — Cambiar el filtro de estado en obtenerInventarioFiltrado
**Qué hace:** Cambia los valores de comparación para los filtros.
```php
// ❌ Saboteado — 'bajo' filtra sin stock y viceversa
if ($valor === 'sin') {
    $sql .= " AND i.cantidad_actual > i.cantidad_minima "; // era = 0
} elseif ($valor === 'bajo') {
    $sql .= " AND i.cantidad_actual = 0 "; // era > 0 AND <= minima
}
```
**Síntoma:** El filtro "Stock Bajo" muestra productos sin stock y viceversa.
**Solución:** `'sin'` → `cantidad_actual = 0`, `'bajo'` → `cantidad_actual > 0 AND cantidad_actual <= cantidad_minima`, `'stock'` → `cantidad_actual > cantidad_minima`.

### Sabotaje 11.4 — Romper el BOM del CSV
**Qué hace:** Elimina el BOM UTF-8 del CSV exportado.
```php
// ❌ Saboteado — sin BOM
// echo "\xEF\xBB\xBF"; ← comentado
```
**Síntoma:** El CSV se descarga pero Excel muestra tildes y ñ como caracteres extraños.
**Solución:** `echo "\xEF\xBB\xBF";` debe estar justo después de los headers del CSV.

---

## 12. DASHBOARD ADMIN

**Archivos:** `Controllers/DashboardController.php` + `models/Dashboard.php`

### Sabotaje 12.1 — Romper el gráfico de ventas semanales
**Qué hace:** Cambia el rango de días o el campo de fecha.
```php
// ❌ Saboteado — últimos 30 días en vez de 7
WHERE f.fecha >= CURDATE() - INTERVAL 30 DAY
// ❌ Saboteado — usa fecha_pedido en vez de fecha de factura
WHERE DATE(p.fecha_pedido) >= CURDATE() - INTERVAL 6 DAY
```
**Síntoma:** El gráfico de línea muestra más o menos días de los esperados, o no coincide con las ventas reales.
**Solución:** `WHERE f.fecha >= CURDATE() - INTERVAL 6 DAY` (6 días atrás + hoy = 7 días).

### Sabotaje 12.2 — Cambiar la fórmula de variación
**Qué hace:** Divide por el valor de hoy en vez de ayer.
```php
// ❌ Saboteado
private function variacion($hoy, $ayer) {
    if ($hoy == 0) return 0;
    return round((($hoy - $ayer) / $hoy) * 100, 1); // dividía por $ayer
}
```
**Síntoma:** Los porcentajes de variación son incorrectos.
**Solución:** `(($hoy - $ayer) / $ayer) * 100` — siempre dividir por el valor de ayer (base de comparación).

### Sabotaje 12.3 — Eliminar el método safe()
**Qué hace:** Elimina el wrapper `safe()` que captura excepciones.
**Síntoma:** Si cualquier query del dashboard falla, toda la página da error 500 en vez de mostrar 0.
**Solución:** El método `safe(callable $fn, $default)` debe existir y usarse en todas las llamadas al modelo.

### Sabotaje 12.4 — Cambiar IFNULL por un campo que no existe
**Qué hace:** Cambia `IFNULL(f.total_factura, 0)` por un campo incorrecto.
```php
// ❌ Saboteado
IFNULL(f.total, 0) AS total  // el campo se llama total_factura
```
**Síntoma:** Error SQL o todos los totales aparecen como NULL/0.
**Solución:** El campo correcto es `f.total_factura`.

---

## 13. PERFIL

**Archivo:** `Controllers/PerfilController.php`

### Sabotaje 13.1 — No actualizar la sesión tras guardar
**Qué hace:** Elimina las líneas que actualizan `$_SESSION['usuario']` después de guardar.
```php
// ❌ Saboteado — falta actualizar sesión
$resultado = $this->model->actualizarPerfil($id, $datos);
// falta: $_SESSION['usuario']['nombre'] = $nombre; etc.
```
**Síntoma:** El perfil se guarda en BD pero el nombre/foto en el header no cambia hasta cerrar sesión.
**Solución:** Después de guardar exitosamente, actualizar `$_SESSION['usuario']['nombre']`, `apellidos`, `telefono` y `foto`.

### Sabotaje 13.2 — No borrar la foto anterior
**Qué hace:** Elimina el `@unlink()` de la foto anterior.
**Síntoma:** Las fotos antiguas se acumulan en `img/perfiles/` sin borrarse.
**Solución:** Antes de mover la nueva foto, obtener la actual con `obtenerPorId()` y borrarla con `@unlink()`.

### Sabotaje 13.3 — Cambiar el tamaño máximo de imagen
**Qué hace:** Cambia el límite de 2MB.
```php
// ❌ Saboteado — límite de 100KB, rechaza imágenes normales
if ($file['size'] > 100 * 1024) { ... }
```
**Síntoma:** No se pueden subir fotos de perfil normales.
**Solución:** `$file['size'] > 2 * 1024 * 1024` (2 MB).

---

## 14. NOTIFICACIONES

**Archivos:** `Controllers/NotifController.php` + `views/layouts/sidebar.php`

### Sabotaje 14.1 — Cambiar la acción de marcar leídas
**Qué hace:** Cambia `leida = 1` por `leida = 0` en el UPDATE.
```php
// ❌ Saboteado — marca como no leídas
$db->prepare("UPDATE notificacion SET leida = 0 WHERE id_usuario_destino = :id")
```
**Síntoma:** Al hacer clic en "Marcar leídas", el badge no desaparece y las notificaciones siguen marcadas como no leídas.
**Solución:** `SET leida = 1`

### Sabotaje 14.2 — Cambiar el campo de destino en la query
**Qué hace:** Cambia `id_usuario_destino` por `id_usuario`.
```php
// ❌ Saboteado — campo incorrecto
"UPDATE notificacion SET leida = 1 WHERE id_usuario = :id"
```
**Síntoma:** Error SQL (columna no existe) o no se marcan las notificaciones correctas.
**Solución:** El campo correcto es `id_usuario_destino`.

### Sabotaje 14.3 — Romper el conteo de no leídas
**Qué hace:** Elimina `AND leida=0` del COUNT.
```php
// ❌ Saboteado — cuenta todas, no solo las no leídas
"SELECT COUNT(*) FROM notificacion WHERE id_usuario_destino=$id_usuario"
```
**Síntoma:** El badge siempre muestra el total de notificaciones, nunca baja a 0.
**Solución:** `WHERE id_usuario_destino=$id_usuario AND leida=0`

### Sabotaje 14.4 — Cambiar el ORDER BY en getNotificaciones
**Qué hace:** Cambia `DESC` por `ASC`.
```php
// ❌ Saboteado — muestra las más antiguas primero
ORDER BY created_at ASC
```
**Síntoma:** Las notificaciones más recientes aparecen al final del panel.
**Solución:** `ORDER BY created_at DESC`

---

## 15. VISTAS Y LAYOUTS

**Archivos:** `views/layouts/header.php`, `views/layouts/sidebar.php`

### Sabotaje 15.1 — Romper la verificación de sesión en header.php
**Qué hace:** Cambia la condición de redirección.
```php
// ❌ Saboteado — redirige a usuarios CON sesión
if (isset($_SESSION['usuario'])) {
    header("Location: .../login.php"); exit;
}
// correcto — redirige a usuarios SIN sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: .../login.php"); exit;
}
```
**Síntoma:** Los usuarios autenticados son redirigidos al login; los no autenticados pueden ver el dashboard.

### Sabotaje 15.2 — Cambiar el href del logout en sidebar
**Qué hace:** Cambia la URL del enlace de cerrar sesión.
```html
<!-- ❌ Saboteado — URL incorrecta -->
<a href="../../Controllers/AuthController.php">Cerrar Sesión</a>
<!-- correcto — incluye ?accion=logout -->
<a href="../../Controllers/AuthController.php?accion=logout">Cerrar Sesión</a>
```
**Síntoma:** Al hacer clic en "Cerrar Sesión", va al login en vez de destruir la sesión (porque sin `?accion=logout` ejecuta el login).

### Sabotaje 15.3 — Romper la sincronización de foto en header
**Qué hace:** Elimina el bloque que sincroniza la foto desde BD.
**Síntoma:** La foto de perfil en el header no se actualiza al cambiarla desde el perfil (hasta que se cierra y abre sesión).
**Solución:** El bloque `SELECT foto FROM usuario WHERE id_usuario = :id` debe estar en header.php.

### Sabotaje 15.4 — Cambiar los roles en el sidebar
**Qué hace:** Cambia los valores de comparación de roles.
```php
// ❌ Saboteado — empleados ven menú de admin
if (in_array($rol_id, ['2',2,'empleado'])) {
    // muestra links de admin
}
```
**Síntoma:** Los empleados ven opciones de inventario/menú/reportes que no deberían ver.
**Solución:** Verificar que cada bloque `if` del sidebar use el rol correcto: `[1,1,'administrador']`, `[2,2,'empleado']`, `[3,3,'cliente']`.

### Sabotaje 15.5 — Cambiar el dashboard href según rol
**Qué hace:** Hardcodea un dashboard incorrecto para todos los roles.
```php
// ❌ Saboteado — todos van al dashboard de admin
$dashboardHref = 'admin_dashboard.php';
```
**Síntoma:** El link "Dashboard" en el sidebar lleva al panel equivocado según el rol.
**Solución:** La lógica correcta:
- rol 1 → `admin_dashboard.php`
- rol 2 → `empleado.php`
- rol 3 → `cliente.php`

---

## 16. SEGURIDAD Y SESIONES

### Sabotaje 16.1 — Quitar session_start() en vistas
**Qué hace:** Elimina `session_start()` al inicio de una vista o controller.
**Síntoma:** Error "Cannot modify header information" o `$_SESSION` vacío, redirige al login aunque el usuario esté autenticado.
**Dónde buscar:** Inicio de cada archivo PHP que use sesiones. Debe ser la primera instrucción (o con `if (session_status() === PHP_SESSION_NONE)`).

### Sabotaje 16.2 — Cambiar la verificación de rol en vistas protegidas
**Qué hace:** Cambia el array de roles permitidos.
```php
// ❌ Saboteado — solo rol 1 puede ver admin_dashboard, pero ahora acepta rol 3
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [3, '3'])) {
```
**Síntoma:** Clientes pueden acceder al dashboard de admin.
**Solución:** Cada vista debe verificar exactamente su rol:
- `admin_dashboard.php` → `[1, '1', 'administrador']`
- `empleado.php` → `[2, '2', 'empleado']`
- `cliente.php` → `[3, '3', 'cliente']`

### Sabotaje 16.3 — Eliminar htmlspecialchars en vistas
**Qué hace:** Elimina `htmlspecialchars()` en datos que se muestran en HTML.
```php
// ❌ Saboteado — XSS posible
echo $usuario['nombre'];
// correcto
echo htmlspecialchars($usuario['nombre']);
```
**Síntoma:** Funciona normalmente pero es vulnerable a XSS (el instructor puede señalarlo como falla de seguridad).
**Solución:** Todo dato de usuario que se muestre en HTML debe pasar por `htmlspecialchars()`.

### Sabotaje 16.4 — Cambiar el .htaccess para exponer config/
**Qué hace:** Elimina la regla que bloquea acceso a `config/`.
```apache
# ❌ Saboteado — eliminado
RewriteRule ^config/ - [F,L]
```
**Síntoma:** El archivo `config/database.php` es accesible desde el navegador (expone credenciales).
**Solución:** La regla `RewriteRule ^config/ - [F,L]` debe estar en `.htaccess`.

---

## 17. CHECKLIST RÁPIDO DE REVISIÓN

Cuando el instructor devuelva el código, revisa estos puntos en orden:

### 🔴 CRÍTICO — Revisar primero
- [ ] `config/database.php` — credenciales correctas y alias `database` presente
- [ ] `AuthController.php` — `password_verify()` con `!` (negación), roles correctos en switch
- [ ] `models/usuario.php` — `password_hash()` en registrar, INSERT en `cliente` para rol 3
- [ ] Verificaciones de sesión en todas las vistas protegidas

### 🟡 IMPORTANTE — Revisar segundo
- [ ] `models/Inventario.php` — transacciones completas, orden de DELETE (inventario antes que producto)
- [ ] `models/Menu.php` — `es_menu = 1` en WHERE, INSERT dummy en inventario al crear
- [ ] `CompraController.php` — cálculo de total, INSERT en factura, rollBack en catch
- [ ] `models/Pedido.php` — `ORDER BY DESC`, `bindValue` para LIMIT/OFFSET

### 🟢 FUNCIONAL — Revisar tercero
- [ ] `ReportesController.php` — BOM UTF-8 en CSV, separador `;`
- [ ] `models/Reportes.php` — fórmulas de KPIs y clasificación de stock
- [ ] `views/layouts/sidebar.php` — `?accion=logout` en el link, roles correctos por sección
- [ ] `views/layouts/header.php` — `!isset($_SESSION['usuario'])` con negación

### 🔵 DETALLES — Revisar al final
- [ ] `PerfilController.php` — actualización de `$_SESSION` tras guardar
- [ ] `NotifController.php` — `leida = 1` y campo `id_usuario_destino`
- [ ] `DashboardController.php` — método `safe()` presente, fórmula de variación correcta
- [ ] `.htaccess` — reglas de seguridad intactas

---

## CÓMO DETECTAR UN SABOTAJE RÁPIDAMENTE

### Por síntoma visual:
| Síntoma | Archivo sospechoso |
|---------|-------------------|
| Pantalla en blanco | `config/database.php` o `session_start()` faltante |
| Login no funciona | `AuthController.php` — `password_verify()` |
| Registro no funciona | `UsuarioController.php` — `password_hash()` o validaciones |
| Dashboard vacío (todo en 0) | `models/Dashboard.php` — queries o JOINs |
| Inventario vacío | `models/Inventario.php` — WHERE o JOIN con inventario |
| Menú muestra ingredientes | `models/Menu.php` — `es_menu = 1` vs `es_menu = 0` |
| Pedidos sin total | `CompraController.php` — INSERT en factura |
| CSV con caracteres raros | BOM UTF-8 faltante o separador incorrecto |
| Notificaciones no se marcan | `NotifController.php` — `leida = 1` |
| Logout no funciona | `sidebar.php` — `?accion=logout` faltante |
| Roles mezclados | `sidebar.php` o vistas — verificación de `id_rol` |

### Por error PHP:
| Error | Causa probable |
|-------|---------------|
| `Class "database" not found` | Alias eliminado en `database.php` |
| `Cannot modify header information` | `session_start()` faltante o output antes de headers |
| `SQLSTATE[23000]: Integrity constraint` | Orden de DELETE incorrecto (FK violation) |
| `SQLSTATE[42S22]: Column not found` | Nombre de campo cambiado en query SQL |
| `Undefined index` | Campo eliminado del array de datos |

---

*Documento generado automáticamente analizando el código fuente completo de RetroRestaurant.*
*Última actualización: Mayo 2026*
