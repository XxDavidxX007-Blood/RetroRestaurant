# Links "Ver todos" y "Ver reporte" — Dashboard Admin

## ¿Qué son?

Son enlaces de acceso rápido que aparecen en la esquina superior derecha de cada tarjeta del dashboard. Te llevan directamente al módulo completo de esa sección sin pasar por el sidebar.

---

## ¿Dónde están en el código?

Viven dentro del `card-header` de cada tarjeta en `admin_dashboard.php`:

```php
<div class="card-header">
    <span class="card-title">Pedidos recientes</span>
    <a href="admin_pedidos.php" class="card-link">Ver todos</a>
</div>
```

La clase `card-link` define su estilo: texto pequeño en mayúsculas con una línea debajo que cambia a dorado al hacer hover.

```css
.card-link {
    font-size: 11px;
    color: var(--text);          /* negro por defecto */
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 1px solid var(--text);
}
.card-link:hover {
    color: var(--gold);          /* dorado al pasar el cursor */
    border-color: var(--gold);
}
```

---

## ¿A dónde lleva cada uno?

| Tarjeta | Texto del link | Destino |
|---|---|---|
| Pedidos recientes | Ver todos | `admin_pedidos.php` |
| Platos más vendidos | Ver reporte | `admin_reportes.php` |
| Domicilios recientes | Ver todos | `admin_domicilios.php` |
| Reservas de hoy | Ver todas | `admin_reservas.php` |

---

## ¿Qué diferencia hay entre "Ver todos" y "Ver reporte"?

- **Ver todos** — lleva a la gestión completa del módulo donde puedes ver, filtrar y administrar todos los registros (pedidos, domicilios, reservas).
- **Ver reporte** — lleva a `admin_reportes.php`, que es una vista de solo lectura con estadísticas, gráficos y análisis. No se puede editar nada desde ahí.

---

## ¿Cómo agregar uno nuevo?

Si agregas una tarjeta nueva al dashboard y quieres que tenga su propio link, solo necesitas esto:

```php
<div class="card-header">
    <span class="card-title">Mi nueva tarjeta</span>
    <a href="mi_modulo.php" class="card-link">Ver todos</a>
</div>
```

No hay lógica PHP detrás — son enlaces HTML simples. La clase `card-link` ya tiene el estilo listo.
