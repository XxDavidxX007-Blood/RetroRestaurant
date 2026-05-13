# Flujo de Registro — Retro Restaurant

## ¿Qué hace el registro?

Crea una cuenta nueva. Siempre es de tipo **cliente** (rol 3). Al terminar, guarda datos en dos tablas: `usuario` y `cliente`.

---

## Los 4 archivos que participan

```
registre.php          →  el formulario que ve el usuario
UsuarioController.php →  recibe el formulario y valida
usuario.php           →  habla con la base de datos
database.php          →  abre la conexión a MySQL
```

---

## El flujo de principio a fin

```
1. Usuario llena el formulario y hace clic en "Crear Cuenta"
2. El navegador envía los datos por POST a UsuarioController.php
3. El controlador valida los datos (ver tabla abajo)
4. Si algo falla → muestra alerta y vuelve al formulario
5. Si todo está bien → hashea la contraseña
6. Llama al modelo para guardar en BD (dentro de una transacción)
7. Guarda en tabla `usuario`, luego en tabla `cliente`
8. Si todo se guardó → redirige al login con mensaje de éxito
```

---

## Validaciones (en orden)

| # | ¿Qué revisa? | Si falla dice... |
|---|---           |---               |
| 1 | Que ningún campo esté vacío | "Debe completar todos los campos" |
| 2 | Que el email tenga formato válido | "Ingrese un email válido" |
| 3 | Que las dos contraseñas sean iguales | "Las contraseñas no coinciden" |
| 4 | Que la contraseña tenga al menos 6 caracteres | "Mínimo 6 caracteres" |
| 5 | Que el email no esté ya registrado en BD | "Este email ya está registrado" |

---

## ¿Cómo se guarda en la base de datos?

El modelo usa una **transacción**: si cualquier paso falla, deshace todo. No quedan datos a medias.

```php
BEGIN TRANSACTION
  INSERT INTO usuario (nombre, apellidos, email, telefono, password, id_rol)
  → obtiene el id_usuario recién creado

  si id_rol == 3  →  INSERT INTO cliente (id_usuario)
  si id_rol == 2  →  INSERT INTO mesero  (id_usuario)
COMMIT confirma la transaccion esto Significa que todos los cambios realizados (INSERT, UPDATE, DELETE) se guardan de forma permanente en la base de datos. /  ROLLBACK revierte la transaccion si algo “falla”o da error,condición no cumplida, se deshacen todos los cambios hechos desde el último BEGIN o SAVEPOINT.
```

---

## La contraseña

Nunca se guarda en texto plano. Se convierte con bcrypt antes de guardar:

```php
// En el controlador, antes de llamar al modelo:
$password = password_hash($password, PASSWORD_DEFAULT);

// En el login, para verificarla:
password_verify($passwordIngresada, $hashGuardado);
```

---

## ¿Cómo se muestran los errores?

Con un sistema de sesión + SweetAlert2. El controlador escribe la alerta, redirige, y la vista la muestra y la borra.

```php
// Controlador escribe:
$_SESSION['alert'] = [
    'icon'  => 'error',
    'title' => 'Email existente',
    'text'  => 'Este email ya está registrado'
];
header("Location: ../views/usuarios/registre.php");

// Vista lee y borra:
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
```

En caso de éxito, la alerta incluye `'redirect' => 'login.php'` para llevar al usuario al login al cerrar el modal.

---

## Cosas a tener en cuenta

- El rol siempre es `3` (cliente). Viene en un `<input type="hidden">` en el formulario. Los admins y empleados se crean desde el panel.
- Hay un input oculto duplicado con `name="nombre"`. No rompe nada porque el navegador toma el último valor, pero es un residuo de desarrollo.
- El mensaje de éxito dice "Administrador creado correctamente" aunque siempre se crea un cliente. Es un bug menor de texto.
