# Métodos HTTP — Retro Restaurant

## ¿Qué es un método HTTP?

Cuando el navegador le habla al servidor, le dice **qué quiere hacer** con una URL. Eso es el método HTTP. Es como el verbo de la oración: no es lo mismo "dame esto" que "guarda esto".

---

## Los métodos que usa este proyecto

### GET — pedir información

El más común. Se usa para **cargar páginas y consultar datos**. Los parámetros van visibles en la URL.

```
GET admin_gestion_de_inventario.php          → carga la página normal
GET admin_gestion_de_inventario.php?exportar=csv  → descarga el CSV
GET AuthController.php?accion=logout         → cierra la sesión
```

**Características:**
- Los datos van en la URL (`?clave=valor`)
- Se puede guardar en favoritos o compartir el link
- No modifica nada en el servidor (solo lee)
- El navegador lo cachea (guarda temporalmente en su memoria interna "el caché")

**En PHP se lee así:**
```php
$accion   = $_GET['accion']   ?? '';
$exportar = $_GET['exportar'] ?? '';
```

---

### POST — enviar datos para guardar o procesar

Se usa cuando **el usuario envía un formulario** para crear, editar, eliminar o iniciar sesión. Los datos van en el cuerpo de la petición, no en la URL.

```
POST UsuarioController.php      → registrar usuario
POST AuthController.php         → iniciar sesión
POST AdminUsuarioController.php?accion=crear  → crear usuario desde panel
POST admin_gestion_de_inventario.php          → crear/editar/eliminar producto
```

**Características:**
- Los datos NO van en la URL (no se ven en la barra del navegador)
- No se puede guardar en favoritos
- Modifica datos en el servidor
- El navegador pregunta si quieres reenviar al refrescar la página

**En PHP se lee así:**
```php
$nombre   = trim($_POST['nombre']   ?? '');
$password = trim($_POST['password'] ?? '');
$accion   = $_POST['accion']        ?? '';

trim sirve para eliminar los espacios en blanco (y algunos caracteres especiales) al inicio y al final de una cadena de texto.
```

**Cómo se detecta en el controlador:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // procesar formulario
} else {
    // mostrar la página
}
```

---

### ¿Cómo se especifica el método en un formulario?

```html
<!-- POST: datos van en el cuerpo -->
<form action="Controllers/AuthController.php" method="POST">

<!-- GET: datos van en la URL (por defecto si no se especifica) -->
<form action="buscar.php" method="GET">
```

---

## El campo oculto `accion` — el truco del proyecto

Como un solo controlador maneja varias acciones (crear, editar, eliminar), el formulario le dice cuál ejecutar con un campo oculto:

```html
<input type="hidden" name="accion" value="crear">
<input type="hidden" name="accion" value="editar">
<input type="hidden" name="accion" value="eliminar">

hidden es un tipo de input invisible para el usuario, pero que envía datos al servidor cuando se envía el formulario.
```

El controlador lo lee y decide:
```php
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'crear':   $this->crear();   break;
    case 'editar':  $this->editar();  break;
    case 'eliminar':$this->eliminar();break;
}

switch es una estructura de control que sirve para evaluar una variable contra múltiples posibles valores y ejecutar el bloque de código que corresponda.
```

---

## Parámetros en la URL con GET — `?accion=logout`

Algunos controladores también usan GET para acciones simples que no envían datos:

```php
// URL: AuthController.php?accion=logout
$accion = $_GET['accion'] ?? 'login';

if ($accion === 'logout') {
    $controller->logout();
} else {
    $controller->login();
}
```

---

## Redirecciones después de POST

Después de procesar un POST, el proyecto siempre redirige con `header()`. Esto evita que al refrescar la página se reenvíe el formulario:

```php
// Patrón POST → Redirect → GET (PRG)
$resultado = $usuario->registrar($datos);

if ($resultado === true) {
    $_SESSION['alert'] = ['icon' => 'success', ...];
    header("Location: ../views/usuarios/registre.php");
    exit;  // ← siempre después de header()
}
```

Sin el `exit`, PHP seguiría ejecutando código después del redirect.

---

## fetch() — POST sin recargar la página

Para acciones pequeñas como marcar notificaciones como leídas, el proyecto usa `fetch` en JavaScript para hacer un POST en segundo plano:

```js
fetch('../../Controllers/NotifController.php?accion=marcar', {
    method: 'POST'
})
.then(() => {
    // actualizar la UI sin recargar
});
```

La diferencia con un formulario normal es que **la página no se recarga** — el servidor procesa la petición y responde, pero el usuario no lo nota.

---

## Resumen rápido

| Método | ¿Para qué? | ¿Datos en URL? | ¿Modifica BD? |
|---     |-         --|---             |--            -|
| GET| Cargar páginas, consultar, exportar | Sí | No  |
| POST | Formularios, crear, editar, eliminar, login | No | Sí |
| fetch POST | Acciones en segundo plano (notificaciones) | No | Sí |

---

## Cosas a tener en cuenta

- **Nunca uses GET para borrar datos** — si alguien guarda la URL en favoritos o un bot la visita, borrará cosas sin querer.
- **Siempre pon `exit` después de `header()`** — sin él, PHP sigue ejecutando el código aunque ya haya redirigido.
- **`$_POST` y `$_GET` nunca son seguros por sí solos** — siempre usa `trim()`, `htmlspecialchars()` o sentencias preparadas antes de usar esos valores.
