# Registro de Usuario desde el Panel Admin — Retro Restaurant

## ¿Qué hace?

Permite al administrador crear cualquier tipo de usuario (admin, empleado o cliente) desde dentro del dashboard. A diferencia del registro público, aquí el admin elige el rol manualmente.

---

## Los archivos que participan

```
views/dashboard/admin.php          →  la tabla de usuarios + el modal con el formulario
Controllers/AdminUsuarioController.php  →  recibe el formulario, valida y llama al modelo
models/usuario.php                 →  ejecuta el INSERT en la base de datos (mismo que registro público)
config/database.php                →  conexión a MySQL
```

---

## El flujo, de principio a fin

```
1. El admin hace clic en "NUEVO USUARIO" dentro del dashboard
2. Se abre un modal con el formulario (sin salir de la página)
3. El admin llena los datos y elige el rol (Admin / Empleado / Cliente)
4. Envía el formulario por POST a AdminUsuarioController.php?accion=crear
5. El controlador verifica que la sesión sea de un administrador
6. Si no es admin → redirige al login (acceso denegado)
7. Si es admin → valida los campos
8. Verifica que el email no esté ya registrado
9. Hashea la contraseña y llama al modelo
10. El modelo guarda en `usuario` y en la tabla del rol correspondiente
11. Redirige de vuelta a admin.php con alerta de éxito o error
```

---

## Lo primero que hace el controlador: verificar que sea admin

Antes de cualquier otra cosa, el controlador revisa la sesión. Si alguien intenta entrar directamente a la URL sin ser admin, lo manda al login.

```php
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador'])) {
    header("Location: ../views/usuarios/login.php");
    exit;
}
```

---

## Validaciones

| # | ¿Qué revisa? | Si falla dice... |
|---|---|---|
| 1 | Que nombre, apellidos, email, password y rol no estén vacíos | "Debe completar todos los campos obligatorios" |
| 2 | Que el email no esté ya registrado en BD | "Este correo ya está registrado" |

> A diferencia del registro público, aquí **no se valida** longitud mínima de contraseña ni se pide confirmarla. El admin es responsable de asignar una contraseña segura.

---

## Campos del formulario

| Campo | Obligatorio | Detalle |
|---|---|---|
| Nombres | Sí | — |
| Apellidos | Sí | — |
| Correo electrónico | Sí | — |
| Teléfono | No | Único campo opcional |
| Contraseña | Sí | No se pide confirmación |
| Rol | Sí | Admin elige: 1, 2 o 3 |

---

## ¿Cómo se guarda en la base de datos?

Usa exactamente el mismo método `Usuario::registrar()` que el registro público. La lógica de transacción es idéntica:

```
INSERT INTO usuario (nombre, apellidos, email, telefono, password, id_rol)

si id_rol == 3  →  INSERT INTO cliente (id_usuario)
si id_rol == 2  →  INSERT INTO mesero  (id_usuario)
si id_rol == 1  →  solo queda en tabla usuario (los admins no tienen tabla propia)
```

---

## El modal (interfaz)

El formulario no es una página aparte, es un modal que aparece encima del dashboard. Se abre y cierra con JavaScript puro:

```js
function openModal('modalCrear')   // muestra el modal
function closeModal('modalCrear')  // lo oculta
```

Esto significa que si hay un error, el admin vuelve a `admin.php` con la alerta, pero el modal ya está cerrado y tiene que abrirlo de nuevo para corregir. Los campos no conservan lo que escribió.

---

## ¿Cómo se muestran los errores?

Mismo sistema que en el resto de la app: `$_SESSION['alert']` + SweetAlert2. La diferencia es que aquí la alerta se lee y borra directamente en la vista, no al inicio del archivo:

```php
// En admin.php, dentro del HTML:
<?php if (isset($_SESSION['alert'])): ?>
    <script>
        Swal.fire({ ... });
    </script>
    <?php unset($_SESSION['alert']); ?>
<?php endif; ?>
```

---

## También existe: editar usuario

El mismo controlador maneja la edición con `?accion=editar`. Las diferencias respecto a crear:

- Se abre otro modal ("EDITAR USUARIO") que se pre-llena con los datos actuales del usuario
- El email aparece como campo de solo lectura (no se puede cambiar)
- La contraseña es opcional: si se deja vacía, no se modifica
- Puede cambiar el rol, y el modelo se encarga de crear el registro en `cliente` o `mesero` si hace falta

```js
// Al hacer clic en editar, JavaScript llena el modal con los datos del usuario:
function openEditModal(usuario) {
    document.getElementById('edit_id_usuario').value = usuario.id_usuario;
    document.getElementById('edit_nombre').value     = usuario.nombre;
    // ...
}
```

---

## Diferencias clave vs el registro público

| | Registro público | Registro desde admin |
|---|---|---|
| Acceso | Cualquier visitante | Solo administradores |
| Rol asignable | Siempre cliente (fijo) | Admin elige libremente |
| Confirmar contraseña | Sí | No |
| Mínimo 6 caracteres | Sí | No |
| Teléfono | Obligatorio | Opcional |
| Interfaz | Página completa | Modal dentro del dashboard |
| Puede editar usuarios | No | Sí |
