# Editar Usuario desde el Panel Admin — Retro Restaurant

## ¿Qué hace?

Permite al administrador modificar los datos de un usuario ya existente: nombre, apellidos, rol y opcionalmente la contraseña. El email no se puede cambiar.

---

## Los archivos que participan

```
views/dashboard/admin.php               →  tabla de usuarios + modal de edición
Controllers/AdminUsuarioController.php  →  recibe el formulario y llama al modelo
models/usuario.php                      →  ejecuta el UPDATE en la base de datos
```

---

## El flujo, de principio a fin

```
1. El admin ve la tabla de usuarios en el dashboard
2. Hace clic en el botón de editar (ícono de lápiz) de cualquier fila
3. Se abre el modal "EDITAR USUARIO" ya pre-llenado con los datos actuales
4. El admin modifica lo que necesita y hace clic en "ACTUALIZAR"
5. El formulario envía POST a AdminUsuarioController.php?accion=editar
6. El controlador verifica que la sesión sea de administrador
7. Verifica que venga un id_usuario válido
8. Actualiza los datos en BD
9. Redirige a admin.php con alerta de éxito o error
```

---

## ¿Cómo se pre-llena el modal?

Cuando el admin hace clic en editar, JavaScript toma los datos de esa fila y los mete directamente en los campos del modal. No hace ninguna petición al servidor.

```js
function openEditModal(usuario) {
    document.getElementById('edit_id_usuario').value = usuario.id_usuario;
    document.getElementById('edit_nombre').value     = usuario.nombre;
    document.getElementById('edit_apellidos').value  = usuario.apellidos;
    document.getElementById('edit_email').value      = usuario.email;
    document.getElementById('edit_id_rol').value     = usuario.id_rol;
    openModal('modalEditar');
}
```

Los datos del usuario vienen del PHP que generó la tabla, pasados como JSON al atributo `onclick` de cada botón:

```php
// En la vista, en cada fila de la tabla:
onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"
```

---

## Campos del formulario de edición

| Campo | Editable | Detalle |
|---|---|---|
| Nombres | Sí | — |
| Apellidos | Sí | — |
| Correo electrónico | **No** | Solo lectura, no se envía al servidor |
| Contraseña | Opcional | Si se deja vacío, la contraseña no cambia |
| Rol | Sí | Admin puede cambiar a cualquier rol |

---

## ¿Qué pasa con la contraseña?

Si el campo se deja vacío, el modelo simplemente no la toca. Si se escribe algo, se hashea y se actualiza.

```php
// En el controlador:
if (!empty($_POST['password'])) {
    $datos['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
}

// En el modelo, el UPDATE solo incluye password si viene en $datos:
if (!empty($datos['password'])) {
    $sql .= ", password = :password";
}
```

---

## ¿Qué pasa si se cambia el rol?

El modelo se encarga de crear el registro en la tabla correspondiente si todavía no existe. No borra el registro anterior del rol viejo.

```php
// Si el nuevo rol es cliente (3) y no tiene registro en tabla cliente → lo crea
if (in_array($datos['id_rol'], ['3', 3])) {
    $check = $this->conn->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = :id");
    $check->execute([':id' => $id_usuario]);
    if (!$check->fetch()) {
        $ins = $this->conn->prepare("INSERT INTO cliente (id_usuario) VALUES (:id)");
        $ins->execute([':id' => $id_usuario]);
    }
}

// Lo mismo para empleado (2) con la tabla mesero
```

---

## Validaciones

Son mínimas comparado con el registro:

| # | ¿Qué revisa? | Si falla dice... |
|---|---|---|
| 1 | Que venga un `id_usuario` en el POST | "ID de usuario no proporcionado" |
| 2 | Que nombre, apellidos y rol no estén vacíos | (validación HTML `required` en el formulario) |

No valida longitud de contraseña ni duplicados de email porque el email no se puede cambiar.

---

## Cosas a tener en cuenta

- **El email no se puede editar.** Está en el modal como campo de solo lectura para que el admin sepa a quién está editando, pero no se envía en el POST ni el modelo lo actualiza.
- **Cambiar el rol no elimina el rol anterior.** Si un cliente pasa a ser empleado, su registro en la tabla `cliente` sigue existiendo. Solo se agrega el nuevo en `mesero`.
- **Si hay un error, el modal se cierra.** Al redirigir a `admin.php`, el modal ya no está abierto. El admin tiene que buscar al usuario en la tabla y volver a hacer clic en editar.
- **No hay opción de eliminar usuarios** desde esta interfaz. Solo se puede crear y editar.
