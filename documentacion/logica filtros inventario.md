# Filtros del Inventario — Retro Restaurant

## ¿Qué hacen?

Permiten buscar y filtrar los productos de la tabla de inventario en tiempo real, sin recargar la página. Son 4 controles que trabajan juntos: buscador, categoría, estado y limpiar.

---

## Los archivos que participan

```
views/dashboard/admin_gestion_de_inventario.php  →  HTML de los filtros + función JS filtrarTabla()
```

Solo vive en la vista. No hay controlador ni BD involucrados — todo ocurre en el navegador.

---

## Los 4 controles

| Control | Tipo | Filtra por |
|---|---|---|
| Buscar ingrediente o producto... | `input text` | Nombre del producto |
| Todas las categorías | `select` | Categoría (Lácteos, Carnes, etc.) |
| Todos los estados | `select` | En stock / Stock bajo / Sin stock |
| Limpiar Filtros | `button` | Resetea los 3 anteriores |

---

## ¿Cómo funciona el filtrado?

Cada control llama a `filtrarTabla()` al cambiar (`onkeyup` / `onchange`). La función lee los tres valores y muestra u oculta filas de la tabla:

```js
function filtrarTabla() {
    const busqueda  = document.getElementById('buscarInventario').value.toLowerCase();
    const categoria = document.getElementById('filtroCategoria').value.toLowerCase();
    const estado    = document.getElementById('filtroEstado').value.toLowerCase();

    document.querySelectorAll('.inventario-item').forEach(fila => {
        const matchNombre    = fila.dataset.nombre.includes(busqueda);
        const matchCategoria = categoria === 'todos' || fila.dataset.categoria === categoria;
        const matchEstado    = estado    === 'todos' || fila.dataset.estado    === estado;

        fila.style.display = (matchNombre && matchCategoria && matchEstado) ? '' : 'none';
    });
}
```

Las tres condiciones deben cumplirse al mismo tiempo (`&&`). Si cualquiera falla, la fila se oculta.

---

## ¿Cómo sabe la función qué datos tiene cada fila?

Los datos se guardan como atributos `data-*` en cada `<tr>` cuando PHP genera la tabla:

```php
<tr class="inventario-item"
    data-nombre="<?= strtolower(htmlspecialchars($item['nombre'])) ?>"
    data-categoria="<?= strtolower(htmlspecialchars($item['categoria'])) ?>"
    data-estado="<?= $estado ?>">   <!-- 'stock', 'bajo' o 'sin' -->
```

Todo en minúsculas para que la comparación no falle por mayúsculas.

---

## Las opciones de estado

El select de estado tiene valores cortos que coinciden exactamente con el `data-estado` de cada fila:

```html
<option value="todos">Todos los estados</option>
<option value="stock">En stock</option>   <!-- data-estado="stock" -->
<option value="bajo">Stock bajo</option>  <!-- data-estado="bajo"  -->
<option value="sin">Sin stock</option>    <!-- data-estado="sin"   -->
```

Y en PHP, al generar cada fila:
```php
if ($item['stock'] == 0)                    $estado = 'sin';
elseif ($item['stock'] <= $item['minimo'])  $estado = 'bajo';
else                                         $estado = 'stock';
```

---

## ¿Cómo funciona "Limpiar Filtros"?

Resetea los tres controles a su valor por defecto y vuelve a llamar a `filtrarTabla()` para mostrar todo:

```js
function limpiarFiltros() {
    document.getElementById('buscarInventario').value  = '';
    document.getElementById('filtroCategoria').value   = 'todos';
    document.getElementById('filtroEstado').value      = 'todos';
    filtrarTabla();
}
```

---

## ¿Cómo se cargan las categorías del select?

Las opciones del select de categorías vienen de la BD, no están escritas a mano:

```php
// El controlador las trae:
$categorias = $datosVista['categorias'];

// La vista las pinta:
<?php foreach($categorias as $cat): ?>
    <option value="<?= strtolower($cat['nombre_categoria']) ?>">
        <?= htmlspecialchars($cat['nombre_categoria']) ?>
    </option>
<?php endforeach; ?>
```

El `value` va en minúsculas para que coincida con el `data-categoria` de las filas.

---

## Cosas a tener en cuenta

- **Todo ocurre en el navegador** — no hay petición al servidor al filtrar. Es instantáneo porque los datos ya están en el HTML.
- **El contador se actualiza** — al filtrar, el texto "Mostrando X productos" se actualiza con el número de filas visibles.
- **Los filtros se combinan** — puedes buscar "leche" + categoría "Lácteos" + estado "Sin stock" al mismo tiempo.
- **Si agregas una categoría nueva en la BD**, aparece automáticamente en el select sin tocar el código.
