# Sidebar (Barra de navegación) — Retro Restaurant

## ¿Qué es?

Es el menú lateral que aparece en todas las páginas del panel. Muestra opciones distintas según el rol del usuario y marca visualmente la página activa. Vive en un solo archivo que se incluye en todas las vistas.

---

## Archivo

```
views/layouts/sidebar.php  →  único archivo, se incluye en todas las vistas del panel
```

Cada vista lo llama así:
```php
require_once __DIR__ . '/../layouts/sidebar.php';
```

---

## ¿Cómo sabe qué opciones mostrar?

Lee el rol del usuario desde la sesión y con `in_array()` decide qué bloque de menú renderizar:

```php
$rol_id = $usuario['id_rol'];  // viene de $_SESSION['usuario']

if (in_array($rol_id, ['1', 1, 'administrador'])) → menú de admin
if (in_array($rol_id, ['2', 2, 'empleado']))      → menú de empleado
if (in_array($rol_id, ['3', 3, 'cliente']))        → menú de cliente
```

Acepta el rol como string, entero o nombre porque PDO puede devolver cualquiera de los tres.

---

## Opciones por rol

| Opción | Admin | Empleado | Cliente |
|---|:---:|:---:|:---:|
| Dashboard | ✓ | ✓ | ✓ |
| Gestión de usuarios | ✓ | — | — |
| Inventario | ✓ | — | — |
| Menú | ✓ | — | — |
| Pedidos | ✓ | ✓ | ✓ (mis pedidos) |
| Reportes | ✓ | — | — |
| Reservas | ✓ | ✓ | ✓ (mis reservas) |
| Domicilios | ✓ | ✓ | ✓ |
| Catálogo | — | ✓ | ✓ |
| Perfil | — | — | ✓ |

---

## ¿Cómo se marca la opción activa?

Compara el nombre del archivo actual con el `href` de cada link usando `basename()`:

```php
$currentPage = basename($_SERVER['PHP_SELF']);
// Si estás en admin_pedidos.php → $currentPage = 'admin_pedidos.php'
```

Cada link aplica clases distintas según si es la página actual o no:

```php
class="... <?= $currentPage === 'admin_pedidos.php'
    ? 'bg-retro-gold text-retro-dark'   // activo: fondo dorado
    : 'hover:text-retro-gold text-gray-300'  // inactivo: gris con hover dorado
?>"
```

---

## ¿Cómo funciona el Dashboard link?

El Dashboard es el único link dinámico — su destino cambia según el rol:

```php
if (in_array($rol_id, ['1',1,'administrador'])) $dashboardHref = 'admin_dashboard.php';
elseif (in_array($rol_id, ['2',2,'empleado']))  $dashboardHref = 'empleado.php';
else                                             $dashboardHref = 'cliente.php';
```

Y se marca como activo si estás en cualquiera de los tres:
```php
in_array($currentPage, ['admin_dashboard.php','empleado.php','cliente.php'])
```

---

## ¿Cómo funciona Cerrar Sesión?

El botón al fondo llama al `AuthController` con el parámetro `accion=logout`:

```php
<a href="../../Controllers/AuthController.php?accion=logout">
```

El controlador hace:
```php
session_unset();    // borra todas las variables
session_destroy();  // destruye la sesión
header("Location: ../views/usuarios/login.php");
```

---

## El header dentro del sidebar

El sidebar también construye el header superior (título + campana + usuario). Tiene acceso a `$titulo` que cada vista define antes de incluirlo:

```php
// En cada vista, antes del require:
$titulo = "PEDIDOS";

// El sidebar lo usa así:
<h1><?= htmlspecialchars($titulo) ?></h1>
```

Si una vista no define `$titulo`, el header usa `'Dashboard'` como valor por defecto (definido en `header.php`).

---

## Estructura visual resumida

```
<aside>  ← barra oscura izquierda (w-64, bg-retro-dark)
  Logo + nombre
  <nav>
    Link Dashboard        ← dinámico según rol
    Links según rol       ← bloques PHP condicionales
  </nav>
  Botón cerrar sesión
</aside>

<main>   ← contenido principal (flex-1)
  <header>
    Título de la página
    Campana de notificaciones
    Info del usuario + avatar
  </header>
  <div>  ← área de contenido scrolleable
    [aquí va el contenido de cada vista]
  </div>
</main>
```

---

## Cosas a tener en cuenta

- **El sidebar y el header son el mismo archivo.** `sidebar.php` abre el `<aside>` y el `<main>` pero no los cierra — eso lo hace `footer.php`.
- **`$usuario` debe existir antes de incluir el sidebar.** Viene de `$_SESSION['usuario']` y se asigna en `header.php`, que siempre se incluye primero.
- **Los íconos** son de Font Awesome 6 (`fas fa-*`). Cada link tiene `group-hover:scale-110` para que el ícono haga un pequeño zoom al pasar el cursor.
