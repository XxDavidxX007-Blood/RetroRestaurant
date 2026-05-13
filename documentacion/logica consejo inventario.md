# Barra de Consejo — Gestión de Inventario

## ¿Qué es?

Es una barra informativa al final de la página de inventario. Muestra un mensaje fijo de buenas prácticas y un botón que lleva directo a los reportes.

---

## ¿Dónde está en el código?

Al final de `admin_gestion_de_inventario.php`, después de la tabla:

```php
<div class="bg-green-50 border-2 border-green-100 rounded-2xl p-6 flex ...">
    <div class="flex items-center gap-4">
        <i class="fas fa-lightbulb text-green-600 text-3xl"></i>
        <div>
            <h3 class="font-heading font-bold text-retro-dark">Consejo</h3>
            <p class="font-body text-gray-600">
                Mantén tu inventario actualizado para evitar faltantes y optimizar costos.
            </p>
        </div>
    </div>

    <a href="admin_reportes.php">
        Ver Reportes de Inventario
    </a>
</div>
```

---

## ¿Qué hace el botón "Ver Reportes de Inventario"?

Es un enlace simple que navega a `admin_reportes.php`. No tiene lógica PHP detrás — solo redirige al módulo de reportes donde el admin puede ver estadísticas detalladas del inventario.

---

## Cosas a tener en cuenta

- **El mensaje es estático** — está escrito directamente en el HTML, no viene de la BD. Si quieres cambiarlo, editas el texto en la vista.
- **Solo es visual** — no tiene interacción más allá del botón de reportes. No valida nada ni ejecuta ninguna acción.
- **El color verde** (`bg-green-50`, `border-green-100`) es intencional para transmitir una sugerencia positiva, no una alerta de error.
