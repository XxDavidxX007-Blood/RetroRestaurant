# Exportar Inventario (CSV) — Retro Restaurant

## ¿Qué hace?

Descarga un archivo `.csv` con todos los productos del inventario. Se abre directamente en Excel con columnas bien separadas, tildes correctas y números sin deformarse.

---

## Los archivos que participan

```
views/dashboard/admin_gestion_de_inventario.php  →  botón "Exportar"
Controllers/InventarioController.php             →  genera y envía el archivo
models/Inventario.php                            →  consulta los datos de la BD
```

---

## ¿Cómo se activa?

El botón en la vista simplemente redirige a la misma página con un parámetro GET:

```php
// Botón en la vista:
<button onclick="window.location.href='?exportar=csv'">
    Exportar
</button>

// El controlador detecta ese parámetro:
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    $this->exportar();
}
```

No hay formulario ni POST — es una simple URL con `?exportar=csv`.

---

## El flujo completo

```
1. Admin hace clic en "Exportar"
2. Navegador hace GET a admin_gestion_de_inventario.php?exportar=csv
3. El controlador detecta el parámetro y llama a exportar()
4. Se consultan todos los productos del inventario desde la BD
5. Se envían headers HTTP para forzar la descarga
6. Se escribe el BOM UTF-8 (para que Excel muestre tildes)
7. Se escribe la fila de encabezados
8. Se escribe una fila por cada producto
9. El navegador descarga el archivo automáticamente
```

---

## Los headers HTTP — por qué son importantes

```php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventario_20260512_143022.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
```

- `Content-Type: text/csv` → le dice al navegador que es un archivo CSV
- `Content-Disposition: attachment` → fuerza la descarga en lugar de mostrar el contenido en pantalla
- `filename` → nombre del archivo con fecha y hora para que no se sobreescriba
- `Cache-Control` → evita que el navegador sirva una versión cacheada antigua

---

## El BOM UTF-8 — por qué existe

```php
echo "\xEF\xBB\xBF";
```

Son 3 bytes invisibles al inicio del archivo. Sin ellos, Excel abre el CSV con codificación incorrecta y las tildes y ñ aparecen como caracteres raros (`Ã©` en lugar de `é`). Con el BOM, Excel detecta automáticamente que es UTF-8.

---

## ¿Por qué punto y coma y no coma?

```php
fputcsv($output, [...], ';');  // tercer parámetro = separador
```

Excel en español usa `;` como separador de columnas en CSV. Si se usa `,` (el default de `fputcsv`), Excel interpreta todo como una sola columna y el archivo se ve como texto plano sin separar.

---

## Cómo se construye cada fila

```php
foreach ($inventario as $item) {
    $codigo     = 'ING-' . str_pad($item['id_producto'], 3, '0', STR_PAD_LEFT);
    // ING-001, ING-002, ING-003...

    $valorTotal = $item['stock'] * $item['precio'];

    fputcsv($output, [
        $codigo,
        $item['nombre'],
        $item['categoria'],
        $item['unidad'],
        (int)$item['stock'],                              // sin decimales
        (int)$item['minimo'],                             // sin decimales
        number_format((float)$item['precio'], 2, '.', ''), // 9000.00
        number_format($valorTotal, 2, '.', ''),            // 180000.00
    ], ';');
}
```

- `str_pad` → rellena el ID con ceros a la izquierda para que quede `ING-001` y no `ING-1`
- `(int)` en stock y mínimo → evita que salgan como `20.00` en lugar de `20`
- `number_format` sin separador de miles → evita que Excel parta `1.500.000` en varias celdas

---

## Resultado en Excel

| ID | Producto | Categoría | Unidad | Stock Actual | Stock Mínimo | Precio Unitario | Valor Total |
|---|---|---|---|---|---|---|---|
| ING-001 | leche | Lácteos | 15L | 0 | 20 | 2500.00 | 0.00 |
| ING-002 | fresas | Frutas | 10L | 20 | 10 | 1500000.00 | 30000000.00 |

---

## Cosas a tener en cuenta

- **No hay límite de filas** — exporta todo el inventario sin paginación.
- **El archivo se genera en tiempo real** — no se guarda en el servidor, va directo al navegador con `php://output`.
- **Solo admins pueden acceder** — la vista tiene verificación de sesión al inicio, así que si alguien intenta acceder a `?exportar=csv` sin sesión de admin, lo manda al login antes de llegar al controlador.
- **El nombre del archivo incluye fecha y hora** — `inventario_20260512_143022.csv` — para que cada exportación quede identificada y no se sobreescriba la anterior.
