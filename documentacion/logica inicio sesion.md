# Flujo de Inicio de Sesión — Retro Restaurant

## ¿Qué hace el login?

Verifica que el usuario exista y que su contraseña sea correcta. Según el rol que tenga, lo manda a un dashboard diferente.

---

## Los archivos que participan

```
login.php          →  el formulario que ve el usuario
AuthController.php →  recibe el formulario, valida y redirige
usuario.php        →  busca al usuario en la base de datos
database.php       →  abre la conexión a MySQL
```

---

## El flujo, de principio a fin

```
1. Usuario escribe su email y contraseña y hace clic en "Ingresar"
2. El navegador envía los datos por POST a AuthController.php
3. El controlador valida que los campos no estén vacíos
4. Verifica que el email tenga formato válido
5. Busca al usuario en BD por email
6. Si no existe → alerta de error → vuelve al login
7. Si existe → compara la contraseña con el hash guardado
8. Si no coincide → alerta de error → vuelve al login
9. Si coincide → regenera el ID de sesión (seguridad)
10. Guarda los datos del usuario en $_SESSION
11. Redirige al dashboard según el rol
```

---

## Validaciones (en orden)

| # | ¿Qué revisa? | Si falla dice... |
|---|---|---|
| 1 | Que email y contraseña no estén vacíos | "Debe ingresar correo y contraseña" |
| 2 | Que el email tenga formato válido | "Ingrese un correo electrónico válido" |
| 3 | Que el email exista en BD y esté activo | "El correo no está registrado o está inactivo" |
| 4 | Que la contraseña coincida con el hash | "Verifique sus credenciales" |

---

## ¿Cómo se verifica la contraseña?

La contraseña nunca se guarda en texto plano, así que no se puede comparar directamente. PHP tiene una función para esto:

```php
// Busca el usuario por email y trae su hash de la BD
$usuario = $usuarioModel->obtenerPorEmail($email);

// Compara lo que escribió el usuario con el hash guardado
if (!password_verify($password, $usuario['password'])) {
    // contraseña incorrecta
}
```

---

## ¿Qué se guarda en la sesión?

Si el login es exitoso, se regenera el ID de sesión (evita ataques de session fixation) y se guardan estos datos:

```php
session_regenerate_id(true); // nueva ID de sesión por seguridad

$_SESSION['usuario'] = [
    'id_usuario' => $usuario['id_usuario'],
    'nombre'     => $usuario['nombre'],
    'apellidos'  => $usuario['apellidos'],
    'email'      => $usuario['email'],
    'telefono'   => $usuario['telefono'],
    'id_rol'     => $usuario['id_rol'],
    'foto'       => $usuario['foto'] ?? null,
];
```

Estos datos quedan disponibles en todas las páginas mientras dure la sesión.

---

## Redirección según rol

```php
switch ($usuario['id_rol']) {
    case '1':  →  admin_dashboard.php   (Administrador)
    case '2':  →  empleado.php          (Empleado / Mesero)
    case '3':  →  cliente.php           (Cliente)
    default:   →  vuelve al login con error "id_Rol no válido"
}
```

---

## ¿Cómo se cierra la sesión?

El controlador también maneja el logout. Se accede con `?accion=logout`:

```php
// URL para cerrar sesión:
AuthController.php?accion=logout

// Lo que hace:
session_unset();    // borra todas las variables de sesión
session_destroy();  // destruye la sesión completamente
// redirige a login.php
```

---

## ¿Cómo se muestran los errores?

Igual que en el registro: sesión + SweetAlert2. El controlador escribe la alerta, redirige, y la vista la muestra y la borra.

```php
// Controlador escribe:
$_SESSION['alert'] = [
    'icon'  => 'error',
    'title' => 'Contraseña incorrecta',
    'text'  => 'Verifique sus credenciales'
];
header("Location: ../views/usuarios/login.php");

// Vista lee y borra:
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
```

> A diferencia del registro, el login **no usa `redirect`** dentro de la alerta. Simplemente muestra el mensaje y el usuario sigue en el formulario.

---

## Cosas a tener en cuenta

- El checkbox "Recordar mis datos" está en el formulario pero **no tiene lógica implementada** en el backend. Es solo visual por ahora.
- El enlace "¿Olvidó su contraseña?" tampoco tiene funcionalidad. Apunta a `#`.
- El controlador acepta el rol tanto como string (`'1'`) como número (`1`) en el switch, pero los casos solo cubren strings. Si la BD devuelve un entero, caería en `default`. En la práctica PDO devuelve strings por defecto, así que funciona.
