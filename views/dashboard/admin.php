<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/usuario.php';

$database = new Database();
$db = $database->conectar();
$usuarioModel = new Usuario($db);
$usuarios = $usuarioModel->obtenerTodos();

$titulo = "GESTION DE USUARIOS";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>

<div class="space-y-8">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl shadow-lg p-6 border-4 border-retro-dark flex items-center justify-between">
            <div>
                <p class="text-gray-500 font-body font-medium mb-1">Total Usuarios</p>
                <h3 class="text-4xl font-heading font-bold text-retro-dark"><?= count($usuarios) ?></h3>
            </div>
            <div class="w-16 h-16 rounded-2xl bg-retro-red/10 flex items-center justify-center text-retro-red text-3xl">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="bg-white rounded-3xl shadow-lg p-6 border-4 border-retro-dark flex items-center justify-between">
            <div>
                <p class="text-gray-500 font-body font-medium mb-1">Ventas Hoy</p>
                <h3 class="text-4xl font-heading font-bold text-retro-dark">$1,250</h3>
            </div>
            <div class="w-16 h-16 rounded-2xl bg-retro-yellow/20 flex items-center justify-center text-retro-yellow text-3xl">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="bg-white rounded-3xl shadow-lg p-6 border-4 border-retro-dark flex items-center justify-between">
            <div>
                <p class="text-gray-500 font-body font-medium mb-1">Órdenes Pendientes</p>
                <h3 class="text-4xl font-heading font-bold text-retro-dark">12</h3>
            </div>
            <div class="w-16 h-16 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-500 text-3xl">
                <i class="fas fa-concierge-bell"></i>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl shadow-lg p-8 border-4 border-retro-dark relative z-20">
        <div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-user-shield text-retro-red text-3xl"></i>
                <h2 class="text-3xl font-heading font-bold text-retro-dark">GESTIÓN DE USUARIOS</h2>
            </div>
            <button onclick="openModal('modalCrear')" class="bg-retro-red hover:bg-red-700 text-white font-heading px-6 py-3 rounded-full shadow-lg hover:shadow-red-500/50 transition-all transform hover:-translate-y-1 flex items-center gap-2">
                <i class="fas fa-plus"></i> NUEVO USUARIO
            </button>
        </div>

        <?php if (isset($_SESSION['alert'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: '<?= htmlspecialchars($_SESSION['alert']['icon']) ?>',
                        title: '<?= htmlspecialchars($_SESSION['alert']['title']) ?>',
                        text: '<?= htmlspecialchars($_SESSION['alert']['text']) ?>',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#E53E3E',
                        background: '#F7FAFC',
                        customClass: {
                            title: 'font-heading font-bold text-gray-800',
                            htmlContainer: 'font-body text-gray-600'
                        }
                    });
                });
            </script>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <div class="overflow-x-auto rounded-xl border-2 border-gray-100">
            <table class="w-full text-left">
                <thead class="bg-retro-dark text-retro-yellow font-heading tracking-wide text-lg">
                    <tr>
                        <th class="p-4 rounded-tl-lg">Usuario</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Teléfono</th>
                        <th class="p-4">Rol</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-center rounded-tr-lg">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-body text-gray-600">
                    <?php foreach ($usuarios as $u): ?>
                    <?php $activo = !isset($u['activo']) || $u['activo'] == 1; ?>
                    <tr class="hover:bg-gray-50 transition <?= !$activo ? 'opacity-60' : '' ?>">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 border border-gray-300">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?></span>
                            </div>
                        </td>
                        <td class="p-4"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="p-4"><?= htmlspecialchars($u['telefono']) ?></td>
                        <td class="p-4">
                            <?php 
                            if (in_array($u['id_rol'], ['1', 1, 'administrador'])) {
                                echo '<span class="bg-red-100 text-red-800 text-xs font-bold px-3 py-1 rounded-full border border-red-200">Admin</span>';
                            } elseif (in_array($u['id_rol'], ['2', 2, 'empleado'])) {
                                echo '<span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full border border-blue-200">Empleado</span>';
                            } else {
                                echo '<span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full border border-green-200">Cliente</span>';
                            }
                            ?>
                        </td>
                        <td class="p-4">
                            <?php if ($activo): ?>
                                <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full border border-green-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activo
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-500 text-xs font-bold px-3 py-1 rounded-full border border-gray-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactivo
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Editar -->
                                <button type="button"
                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                    class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition shadow-sm border border-blue-200"
                                    title="Editar">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>

                                <?php if ($activo): ?>
                                <!-- Deshabilitar -->
                                <button type="button"
                                    onclick="confirmarAccion('deshabilitar', <?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'].' '.$u['apellidos'])) ?>')"
                                    class="w-9 h-9 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-500 hover:text-white transition shadow-sm border border-yellow-200"
                                    title="Deshabilitar">
                                    <i class="fas fa-ban text-xs"></i>
                                </button>
                                <?php else: ?>
                                <!-- Activar -->
                                <button type="button"
                                    onclick="confirmarAccion('activar', <?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'].' '.$u['apellidos'])) ?>')"
                                    class="w-9 h-9 rounded-full bg-green-50 text-green-600 hover:bg-green-500 hover:text-white transition shadow-sm border border-green-200"
                                    title="Activar">
                                    <i class="fas fa-check text-xs"></i>
                                </button>
                                <?php endif; ?>

                                <!-- Eliminar -->
                                <button type="button"
                                    onclick="confirmarAccion('eliminar', <?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'].' '.$u['apellidos'])) ?>')"
                                    class="w-9 h-9 rounded-full bg-red-50 text-red-500 hover:bg-red-600 hover:text-white transition shadow-sm border border-red-200"
                                    title="Eliminar">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-500 font-body">No hay usuarios registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div id="modalCrear" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border-4 border-retro-dark relative z-50">
        <div class="bg-retro-dark p-6 flex justify-between items-center border-b-4 border-retro-yellow">
            <h3 class="text-2xl font-heading font-bold text-retro-yellow flex items-center gap-2">
                <i class="fas fa-user-plus"></i> AGREGAR USUARIO
            </h3>
            <button onclick="closeModal('modalCrear')" class="text-white hover:text-retro-yellow transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form action="../../Controllers/AdminUsuarioController.php?accion=crear" method="POST" class="p-8 space-y-5 font-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Nombres *</label>
                    <input type="text" name="nombre" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Apellidos *</label>
                    <input type="text" name="apellidos" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Correo Electrónico *</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Teléfono</label>
                    <input type="text" name="telefono" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Contraseña *</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Rol *</label>
                    <div class="relative">
                        <select name="id_rol" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50 appearance-none">
                            <option value="">Seleccione un rol...</option>
                            <option value="1">Administrador</option>
                            <option value="2">Empleado</option>
                            <option value="3">Cliente</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-6 mt-6">
                <button type="button" onclick="closeModal('modalCrear')" class="px-6 py-3 font-heading font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors">CANCELAR</button>
                <button type="submit" class="px-6 py-3 font-heading font-bold text-white bg-retro-red hover:bg-red-700 rounded-full shadow-lg hover:shadow-red-500/50 transition-all transform hover:-translate-y-1">GUARDAR</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div id="modalEditar" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border-4 border-retro-dark relative z-50">
        <div class="bg-retro-dark p-6 flex justify-between items-center border-b-4 border-retro-yellow">
            <h3 class="text-2xl font-heading font-bold text-retro-yellow flex items-center gap-2">
                <i class="fas fa-user-edit"></i> EDITAR USUARIO
            </h3>
            <button onclick="closeModal('modalEditar')" class="text-white hover:text-retro-yellow transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form action="../../Controllers/AdminUsuarioController.php?accion=editar" method="POST" class="p-8 space-y-5 font-body">
            <input type="hidden" name="id_usuario" id="edit_id_usuario">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Nombres *</label>
                    <input type="text" name="nombre" id="edit_nombre" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Apellidos *</label>
                    <input type="text" name="apellidos" id="edit_apellidos" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-retro-dark mb-1">Correo Electrónico</label>
                <input type="email" id="edit_email" readonly class="w-full px-4 py-3 border-2 border-gray-100 bg-gray-100 text-gray-500 rounded-xl outline-none cursor-not-allowed">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Nueva Contraseña <span class="font-normal text-gray-400 text-xs">(opcional)</span></label>
                    <input type="password" name="password" placeholder="••••••••" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-bold text-retro-dark mb-1">Rol *</label>
                    <div class="relative">
                        <select name="id_rol" id="edit_id_rol" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none transition bg-gray-50 appearance-none">
                            <option value="1">Administrador</option>
                            <option value="2">Empleado</option>
                            <option value="3">Cliente</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-6 mt-6">
                <button type="button" onclick="closeModal('modalEditar')" class="px-6 py-3 font-heading font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors">CANCELAR</button>
                <button type="submit" class="px-6 py-3 font-heading font-bold text-white bg-retro-red hover:bg-red-700 rounded-full shadow-lg hover:shadow-red-500/50 transition-all transform hover:-translate-y-1">ACTUALIZAR</button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario oculto para acciones de estado/eliminar -->
<form id="formAccion" method="POST" style="display:none;">
    <input type="hidden" name="id_usuario" id="accion_id_usuario">
</form>

<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function openEditModal(usuario) {
        document.getElementById('edit_id_usuario').value = usuario.id_usuario;
        document.getElementById('edit_nombre').value = usuario.nombre;
        document.getElementById('edit_apellidos').value = usuario.apellidos;
        document.getElementById('edit_email').value = usuario.email;
        document.getElementById('edit_id_rol').value = usuario.id_rol;
        openModal('modalEditar');
    }

    function confirmarAccion(accion, id, nombre) {
        const cfg = {
            deshabilitar: {
                icon: 'warning',
                title: '¿Deshabilitar usuario?',
                text: `"${nombre}" no podrá iniciar sesión mientras esté inactivo.`,
                confirmText: 'Deshabilitar',
                confirmColor: '#D97706',
            },
            activar: {
                icon: 'question',
                title: '¿Activar usuario?',
                text: `"${nombre}" podrá volver a iniciar sesión.`,
                confirmText: 'Activar',
                confirmColor: '#059669',
            },
            eliminar: {
                icon: 'error',
                title: '¿Eliminar usuario?',
                text: `Esta acción es irreversible. Se eliminará a "${nombre}" permanentemente.`,
                confirmText: 'Eliminar',
                confirmColor: '#DC2626',
            },
        };

        const c = cfg[accion];
        Swal.fire({
            icon: c.icon,
            title: c.title,
            text: c.text,
            showCancelButton: true,
            confirmButtonText: c.confirmText,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: c.confirmColor,
            cancelButtonColor: '#6B7280',
            background: '#F7FAFC',
            customClass: {
                title: 'font-heading font-bold text-gray-800',
                htmlContainer: 'font-body text-gray-600'
            }
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.getElementById('formAccion');
                form.action = `../../Controllers/AdminUsuarioController.php?accion=${accion}`;
                document.getElementById('accion_id_usuario').value = id;
                form.submit();
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>