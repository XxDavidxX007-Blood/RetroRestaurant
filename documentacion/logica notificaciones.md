# Sistema de Notificaciones — Retro Restaurant

## ¿Qué hace?

Avisa a cada usuario sobre cosas que le importan. Aparece como una campana 🔔 en el header de cualquier panel (admin, empleado, cliente). Al hacer clic se abre un panel flotante con las últimas 8 notificaciones.

---

## Los archivos que participan

```
Controllers/CompraController.php     →  crea y lee notificaciones
Controllers/NotifController.php      →  marca notificaciones como leídas (vía fetch)
views/layouts/sidebar.php            →  campana + panel flotante (aplica a todos los roles)
views/dashboard/admin_notificaciones.php   →  página completa para admin/empleado
views/dashboard/cliente_notificaciones.php →  página completa para cliente
sql/notificaciones_cliente.sql       →  tabla `promocion` (ejecutar una vez)
```

---

## La tabla en base de datos

Todas las notificaciones van a la misma tabla `notificacion`, sin importar el rol:

```sql
notificacion
├── id_notificacion       INT PK
├── id_usuario_destino    INT  → a quién va dirigida
├── tipo                  VARCHAR  → 'pago' | 'pedido' | 'promocion' | 'combo' | 'reserva' | 'domicilio'
├── titulo                VARCHAR
├── mensaje               VARCHAR
├── id_referencia         INT  → id del pedido, promoción, etc. (opcional)
├── leida                 TINYINT  → 0 = no leída, 1 = leída
└── created_at            TIMESTAMP
```

---

## ¿Cuándo se genera cada tipo?

### 🎉 `pago` — cuando el cliente confirma un pedido
Se crea en `CompraController::procesarCompra()`, justo después de insertar la factura.
```
Cliente paga → INSERT pedido + factura → notificación al cliente con número y total
```

### 🛍️ `pedido` — cuando llega un pedido nuevo (para admins)
Se crea en el mismo `procesarCompra()`, pero dirigida a todos los usuarios con `id_rol = 1`.
```
Cliente paga → notificación a TODOS los administradores
```

### 🔥 `promocion` — cuando una promo está por vencer
Se genera en `CompraController::notificarPromocionesCliente()`. Se llama automáticamente cada vez que el cliente carga cualquier página del dashboard.
```
Cliente carga página → busca promos que vencen en ≤ 3 días → si no fue notificado antes → INSERT notificación
```
No se repite: usa `id_referencia` para verificar que esa promo ya no fue notificada al mismo usuario.

---

## El flujo del panel flotante

```
1. sidebar.php carga → PHP consulta notificaciones del usuario actual
2. El panel HTML se genera con los datos y se mueve al <body> con JS
   (esto es clave: evita que overflow:hidden del layout lo tape)
3. Usuario hace clic en la campana → toggleNotif() calcula posición y lo muestra
4. Usuario hace clic en una notificación → navega a la página correspondiente
5. Usuario hace clic en "Marcar leídas" → fetch a NotifController.php → UPDATE leida=1
6. Clic fuera del panel → cerrarNotif() con animación fade
```

---

## ¿A dónde lleva cada notificación?

| Tipo | Cliente | Admin / Empleado |
|---|---|---|
| `pago` / `pedido` | Mis Pedidos | Pedidos |
| `promocion` / `combo` | Catálogo | Gestión de Menú |
| `reserva` | Mis Reservas | Reservas |
| `domicilio` | Domicilios | Domicilios |

---

## ¿Cómo funciona "Marcar leídas"?

Sin recargar la página. El botón llama a `marcarTodasLeidas()` en JS:

```js
fetch('../../Controllers/NotifController.php?accion=marcar', { method: 'POST' })
  .then(() => {
      // Quita el badge rojo del contador
      // Baja la opacidad de todos los items
      // Elimina los puntos dorados de "no leído"
  })
```

El servidor hace simplemente:
```sql
UPDATE notificacion SET leida = 1 WHERE id_usuario_destino = :id
```

---

## El truco del z-index

El panel flotante se renderiza dentro del `<main>`, que tiene `overflow:hidden`. Eso hacía que el panel quedara tapado por el contenido del dashboard.

**Solución:** moverlo al `<body>` con JavaScript al cargar la página:
```js
document.body.appendChild(notifPanel);
```

Desde el `<body>`, con `position:fixed` y `z-index: 2147483647` (el máximo posible), nada lo puede tapar.

---

## Cosas a tener en cuenta

- **Las promos necesitan la tabla `promocion`** — hay que ejecutar `sql/notificaciones_cliente.sql` una vez. Si la tabla no existe, el sistema falla silenciosamente (no rompe el dashboard).
- **Los admins no tienen notificaciones de pago** — solo reciben `pedido` cuando un cliente compra. Si se quiere notificarles de otras cosas, hay que agregar más `INSERT` en los controladores correspondientes.
- **El panel muestra máximo 8 notificaciones** — la página completa (`admin_notificaciones.php` / `cliente_notificaciones.php`) muestra las últimas 50.
- **Al abrir la página completa se marcan todas como leídas** — `CompraController::marcarLeidas()` se llama al inicio de ambas vistas.
