# Editar y Eliminar Producto — Gestión de Inventario

## ¿Qué hacen?

- **Editar** — modifica los datos de un producto ya existente en el inventario
- **Eliminar** — borra el producto del inventario permanentemente

Ambas opciones aparecen en un pequeño dropdown al hacer clic en los tres puntos `⋮` de cada fila.

---

## Los archivos que participan

```
views/dashboard/admin_gestion_de_inventario.php  →  dropdown + modales + JS
Controllers/InventarioController.php             →  procesa el POST de editar/eliminar
models/Inventario.php                            →  ejecuta UPDATE y DELETE en la BD
```

---

## El dropdown de acciones

El botón `⋮` de cada fila abre un pequeño menú flotante con las dos opciones:

```php
// Botón que abre el dropdown (uno por fila):
<button onclick="toggleDropdown(<?= $item['id_producto'] ?>)">
    <i class="fas fa-ellipsis-v"></i>
</button>

// El dropdown (oculto por defecto):
<div id="dropdown-<?= $item['id_producto'] ?>" class="hidden ...">
    <a onclick="abrirModalEditar(<?= json_encode($item) ?>)">Editar</a>
    <a onclick="abrirModalEliminar(<?= $item['id_producto'] ?>)">Eliminar</a>
</div>
```

```js
function toggleDropdown(id) {
    // Cierra todos los otros dropdowns abiertos
    document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
        if (el.id !== 'dropdown-' + id) el.classList.add('hidden');
    });
    // Abre o cierra el seleccionado
    document.getElementById('dropdown-' + id).classList.toggle('hidden');
}

// Clic fuera → cierra todos
window.onclick = function(e) {
    if (!e.target.closest('.dropdown-container')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
    }
}
```

---

## EDITAR

### ¿Cómo se pre-llena el modal?

Al hacer clic en "Editar", PHP ya pasó todos los datos del producto como JSON al atributo `onclick`. JavaScript los toma y llena los campos del modal sin hacer ninguna petición al servidor:

```js
function abrirModalEditar(item) {
    document.getElementById('edit_id_producto').value = item.id_producto;
    document.getElementById('edit_nombre').value      = item.nombre;
    document.getElementById('edit_precio').value      = item.precio;
    document.getElementById('edit_unidad').value      = item.unidad;
    document.getElementById('edit_stock').value       = item.stock;
    document.getElementById('edit_minimo').value      = item.minimo;
    document.getElementById('edit_imagen').value      = item.imagen;

    // La categoría se busca por nombre en el select
    let catSelect = document.getElementById('edit_categoria');
    for (let i = 0; i < catSelect.options.length; i++) {
        if (catSelect.options[i].text === item.categoria) {
            catSelect.selectedIndex = i; break;
        }
    }
    openModal('modalEditarProducto');
}
```

### Campos editables

| Campo | Editable |
|---|---|
| Nombre | Sí |
| Categoría | Sí |
| Precio unitario | Sí |
| Unidad | Sí |
| Stock actual | Sí |
| Stock mínimo | Sí |
| Emoji / Icono | Sí |

### ¿Qué pasa en la BD?

```php
// Controlador arma los datos y llama al modelo:
$datos = [
    'nombre'       => $_POST['nombre'],
    'id_categoria' => $_POST['id_categoria'],
    'unidad'       => $_POST['unidad'],
    'stock'        => $_POST['stock'],
    'minimo'       => $_POST['minimo'],
    'precio'       => $_POST['precio'],
    'imagen'       => $_POST['imagen'],
];
$this->inventarioModel->actualizar($id_producto, $datos);
```

```sql
-- El modelo ejecuta dos UPDATE:
UPDATE producto SET nombre=:nombre, precio=:precio, ... WHERE id_producto=:id
UPDATE inventario SET cantidad_actual=:stock, cantidad_minima=:minimo WHERE id_producto=:id
```

---

## ELIMINAR

### ¿Cómo funciona el modal de confirmación?

Al hacer clic en "Eliminar", se abre un modal de advertencia que pide confirmación antes de borrar. Solo guarda el ID del producto en un campo oculto:

```js
function abrirModalEliminar(id) {
    document.getElementById('delete_id_producto').value = id;
    openModal('modalEliminarProducto');
}
```

El modal muestra un ícono de advertencia y dos botones: **Cancelar** (cierra el modal) y **Sí, eliminar** (envía el formulario).

### ¿Qué pasa en la BD?

```php
// El modelo borra en orden por las foreign keys:
BEGIN TRANSACTION
  DELETE FROM inventario WHERE id_producto = :id   // primero el inventario
  DELETE FROM producto   WHERE id_producto = :id   // luego el producto
COMMIT
```

El orden importa — si se borrara `producto` primero, la FK de `inventario` lanzaría un error.

---

## Flujo completo comparado

```
EDITAR:
clic ⋮ → dropdown → clic Editar → modal pre-llenado con datos
→ admin modifica → POST accion=editar → UPDATE producto + UPDATE inventario
→ redirect ?success=editado

ELIMINAR:
clic ⋮ → dropdown → clic Eliminar → modal de confirmación
→ admin confirma → POST accion=eliminar → DELETE inventario + DELETE producto
→ redirect ?success=eliminado
```

---

## Cosas a tener en cuenta

- **La eliminación es permanente** — no hay papelera ni forma de recuperar el producto. El modal de confirmación es la única protección.
- **La categoría se busca por nombre** en el select de edición, no por ID. Si dos categorías tienen el mismo nombre, podría seleccionar la incorrecta.
- **Los datos del producto viajan en el HTML** como JSON en el `onclick`. Cualquiera que inspeccione el código fuente puede verlos — no incluyas datos sensibles en los atributos `data-*` o `onclick`.
- **No hay validación server-side** en el controlador — solo la validación HTML del formulario (`required`, `min`).
