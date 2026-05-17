<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$titulo = "GESTIÓN DE MENÚ";

require_once __DIR__ . '/../../Controllers/MenuController.php';

$menuController = new MenuController();
$menuController->manejarPeticion();
$datosVista = $menuController->obtenerDatosVista();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$menu = $datosVista['menu'];
$categorias = $datosVista['categorias'];

$totalPlatos = count($menu);
$platosOcultos = 0;
$valorPromedio = 0;
$sumaPrecios = 0;

foreach ($menu as $item) {
    if ($item['disponible'] == 0) {
        $platosOcultos++;
    }
    $sumaPrecios += $item['precio'];
}
if ($totalPlatos > 0) {
    $valorPromedio = $sumaPrecios / $totalPlatos;
}
?>

<div class="space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-4xl font-heading font-bold text-retro-dark">
                Gestión de Menú
            </h1>
            <p class="font-body text-gray-500">
                Administra los platos, bebidas y combos que se muestran en el catálogo del cliente
            </p>
        </div>

        <div class="flex gap-3">
            <button onclick="openModal('modalNuevoProducto')" class="bg-blue-600 hover:bg-blue-700 text-white font-heading px-6 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Agregar Plato al Menú
            </button>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
        <p class="font-bold">Éxito</p>
        <p>Operación realizada correctamente.</p>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
        <p class="font-bold">Error</p>
        <p>Hubo un problema al procesar la solicitud.</p>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl">
                <i class="fas fa-utensils"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Total Platos</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark"><?= $totalPlatos ?></h3>
                <p class="text-xs text-gray-400">En el catálogo</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-3xl">
                <i class="fas fa-eye-slash"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Platos Ocultos</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark"><?= $platosOcultos ?></h3>
                <p class="text-xs text-gray-400">No visibles para el cliente</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-3xl">
                <i class="fas fa-tag"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Precio Promedio</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark">
                    $<?= number_format($valorPromedio, 0, ',', '.') ?>
                </h3>
                <p class="text-xs text-gray-400">Por plato</p>
            </div>
        </div>

    </div>

    <!-- Filtros -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="md:col-span-2 relative">
            <input type="text" id="buscarMenu" placeholder="Buscar plato o bebida..."
                   class="w-full px-5 py-4 pl-12 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-blue-500 outline-none bg-white" onkeyup="filtrarTabla()">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>

        <select id="filtroCategoria" class="px-5 py-4 border-2 border-gray-200 rounded-xl bg-white outline-none" onchange="filtrarTabla()">
            <option value="todos">Todas las categorías</option>
            <?php foreach($categorias as $cat): ?>
                <option value="<?= htmlspecialchars(strtolower($cat['nombre_categoria'])) ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filtroDisponibilidad" class="px-5 py-4 border-2 border-gray-200 rounded-xl bg-white outline-none" onchange="filtrarTabla()">
            <option value="todos">Todos (Visibles/Ocultos)</option>
            <option value="1">Visibles (Catálogo)</option>
            <option value="0">Ocultos (Agotados)</option>
        </select>

        <button onclick="limpiarFiltros()" class="px-5 py-4 border-2 border-gray-200 rounded-xl bg-white hover:border-retro-dark transition font-heading text-gray-600 hover:text-retro-dark">
            <i class="fas fa-filter mr-2"></i>
            Limpiar
        </button>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="tablaMenu">
                <thead class="bg-gray-50 text-gray-600 font-heading border-b-2 border-gray-200">
                    <tr>
                        <th class="p-5">Plato / Bebida</th>
                        <th class="p-5">Categoría</th>
                        <th class="p-5">Descripción</th>
                        <th class="p-5">Precio</th>
                        <th class="p-5">Disponibilidad</th>
                        <th class="p-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 font-body">
                    <?php if (empty($menu)): ?>
                        <tr>
                            <td colspan="6" class="p-5 text-center text-gray-500">No hay platos registrados en el menú.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($menu as $item): ?>
                        <tr class="menu-item hover:bg-gray-50 transition"
                            data-nombre="<?= strtolower(htmlspecialchars($item['nombre'])) ?>"
                            data-categoria="<?= strtolower(htmlspecialchars($item['categoria'])) ?>"
                            data-disponible="<?= $item['disponible'] ?>">

                            <td class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden shadow-inner border border-gray-200 flex-shrink-0">
                                        <?php
                                        $imgMenu = $item['imagen'] ?? '';
                                        // Si es una ruta de archivo (contiene /)
                                        if (!empty($imgMenu) && (str_contains($imgMenu, '/') || str_contains($imgMenu, '.'))) {
                                            echo '<img src="../../img/menu/' . htmlspecialchars($imgMenu) . '" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML=\'🍽️\'">';
                                        } else {
                                            echo '<span class="text-3xl">' . (empty($imgMenu) ? '🍽️' : htmlspecialchars($imgMenu)) . '</span>';
                                        }
                                        ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-retro-dark text-lg"><?= htmlspecialchars($item['nombre']) ?></p>
                                    </div>
                                </div>
                            </td>

                            <td class="p-5">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-bold">
                                    <?= htmlspecialchars($item['categoria']) ?>
                                </span>
                            </td>

                            <td class="p-5 text-gray-500 max-w-xs truncate" title="<?= htmlspecialchars($item['descripcion']) ?>">
                                <?= htmlspecialchars($item['descripcion']) ?: '<span class="italic text-gray-400">Sin descripción</span>' ?>
                            </td>

                            <td class="p-5 font-bold text-green-600 text-lg">
                                $<?= number_format($item['precio'], 0, ',', '.') ?>
                            </td>

                            <td class="p-5">
                                <?php if ($item['disponible']): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-bold text-sm flex items-center w-max gap-2">
                                        <i class="fas fa-check-circle"></i> Visible
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full font-bold text-sm flex items-center w-max gap-2">
                                        <i class="fas fa-eye-slash"></i> Oculto
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-5 text-center relative dropdown-container">
                                <button onclick="toggleDropdown(<?= $item['id_producto'] ?>)" class="w-10 h-10 rounded-full hover:bg-gray-200 text-gray-600 focus:outline-none transition">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div id="dropdown-<?= $item['id_producto'] ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-100 overflow-hidden">
                                    <div class="py-1">
                                        <a href="#" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($item)) ?>); return false;" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-50">
                                            <i class="fas fa-edit text-blue-500 mr-2"></i> Editar Plato
                                        </a>
                                        <a href="#" onclick="abrirModalEliminar(<?= $item['id_producto'] ?>); return false;" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50">
                                            <i class="fas fa-trash text-red-500 mr-2"></i> Eliminar del Menú
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-5 flex justify-between items-center border-t border-gray-100 bg-gray-50">
            <p class="font-body text-sm text-gray-600" id="infoResultados">
                Mostrando <?= count($menu) ?> platos
            </p>
        </div>
    </div>
</div>

<!-- Modal Nuevo Producto Menú -->
<div id="modalNuevoProducto" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-blue-50">
            <h3 class="text-2xl font-heading font-bold text-blue-900"><i class="fas fa-hamburger mr-2"></i> Nuevo Plato al Menú</h3>
            <button onclick="closeModal('modalNuevoProducto')" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form action="admin_gestion_de_menu.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="crear">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-bold mb-2">Nombre del Plato o Bebida</label>
                        <input type="text" name="nombre" placeholder="Ej. Hamburguesa Doble" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Categoría</label>
                        <select name="id_categoria" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition bg-white">
                            <option value="">Seleccione...</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Precio de Venta ($)</label>
                        <input type="number" name="precio" min="0" step="0.01" placeholder="0.00" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-bold mb-2">Descripción (Visible para el cliente)</label>
                        <textarea name="descripcion" rows="3" placeholder="Ingredientes o descripción atractiva..." class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition"></textarea>
                    </div>
                    <!-- Imagen -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-bold mb-2">Imagen del Plato</label>
                        <div class="flex items-center gap-4">
                            <div id="nuevo_preview_wrap" class="w-20 h-20 rounded-xl bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <i class="fas fa-image text-gray-400 text-2xl" id="nuevo_preview_icon"></i>
                                <img id="nuevo_preview_img" src="" class="hidden w-full h-full object-cover rounded-xl">
                            </div>
                            <div class="flex-1">
                                <label class="cursor-pointer flex items-center gap-2 px-4 py-3 border-2 border-dashed border-blue-300 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                                    <i class="fas fa-upload text-blue-500"></i>
                                    <span class="text-gray-600 text-sm font-medium" id="nuevo_file_label">Seleccionar imagen (JPG, PNG, WEBP — máx. 2MB)</span>
                                    <input type="file" name="imagen_file" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewImagen(this,'nuevo_preview_img','nuevo_preview_icon','nuevo_file_label')">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="disponible" checked class="form-checkbox h-6 w-6 text-blue-600 rounded">
                            <span class="ml-3 text-gray-700 font-bold">Mostrar en Catálogo (Disponible)</span>
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-4 border-t border-gray-100 pt-6">
                    <button type="button" onclick="closeModal('modalNuevoProducto')" class="px-6 py-3 text-gray-500 hover:text-gray-800 hover:bg-gray-100 font-bold rounded-xl transition">Cancelar</button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition flex items-center">
                        <i class="fas fa-save mr-2"></i> Guardar Plato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Producto Menú -->
<div id="modalEditarProducto" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-2xl font-heading font-bold text-retro-dark"><i class="fas fa-pen mr-2"></i> Editar Plato</h3>
            <button onclick="closeModal('modalEditarProducto')" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form action="admin_gestion_de_menu.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_producto" id="edit_id_producto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-bold mb-2">Nombre del Plato o Bebida</label>
                        <input type="text" name="nombre" id="edit_nombre" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Categoría</label>
                        <select name="id_categoria" id="edit_categoria" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition bg-white">
                            <option value="">Seleccione...</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Precio de Venta ($)</label>
                        <input type="number" name="precio" id="edit_precio" min="0" step="0.01" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-bold mb-2">Descripción (Visible para el cliente)</label>
                        <textarea name="descripcion" id="edit_descripcion" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition"></textarea>
                    </div>
                    <!-- Imagen -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-bold mb-2">Imagen del Plato</label>
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-xl bg-gray-100 border-2 border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <img id="edit_preview_img" src="" class="w-full h-full object-cover rounded-xl">
                            </div>
                            <div class="flex-1">
                                <label class="cursor-pointer flex items-center gap-2 px-4 py-3 border-2 border-dashed border-blue-300 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition">
                                    <i class="fas fa-upload text-blue-500"></i>
                                    <span class="text-gray-600 text-sm font-medium" id="edit_file_label">Cambiar imagen (dejar vacío para mantener la actual)</span>
                                    <input type="file" name="imagen_file" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewImagen(this,'edit_preview_img',null,'edit_file_label')">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center pt-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="disponible" id="edit_disponible" class="form-checkbox h-6 w-6 text-blue-600 rounded">
                            <span class="ml-3 text-gray-700 font-bold">Mostrar en Catálogo (Disponible)</span>
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-4 border-t border-gray-100 pt-6">
                    <button type="button" onclick="closeModal('modalEditarProducto')" class="px-6 py-3 text-gray-500 hover:text-gray-800 hover:bg-gray-100 font-bold rounded-xl transition">Cancelar</button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition flex items-center">
                        <i class="fas fa-save mr-2"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar Producto Menú -->
<div id="modalEliminarProducto" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="p-8 text-center">
            <div class="w-24 h-24 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-5xl mx-auto mb-6">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3 class="text-3xl font-heading font-bold text-retro-dark mb-4">¿Eliminar del Menú?</h3>
            <p class="text-gray-500 mb-8 text-lg">Esta acción removerá el plato permanentemente del catálogo y no podrá ser ordenado por los clientes.</p>
            
            <form action="admin_gestion_de_menu.php" method="POST" class="flex justify-center gap-4">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_producto" id="delete_id_producto">
                <button type="button" onclick="closeModal('modalEliminarProducto')" class="px-6 py-3 text-gray-600 hover:bg-gray-100 font-bold rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> Sí, eliminar plato
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function previewImagen(input, imgId, iconId, labelId) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) {
                alert('La imagen no puede superar 2MB');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(imgId);
                img.src = e.target.result;
                img.classList.remove('hidden');
                if (iconId) document.getElementById(iconId).classList.add('hidden');
                if (labelId) document.getElementById(labelId).textContent = file.name;
            };
            reader.readAsDataURL(file);
        }
    }

    function abrirModalEditar(item) {
        document.getElementById('edit_id_producto').value = item.id_producto;
        document.getElementById('edit_nombre').value = item.nombre;
        
        let catSelect = document.getElementById('edit_categoria');
        for (let i=0; i<catSelect.options.length; i++) {
            if (catSelect.options[i].value == item.id_categoria || catSelect.options[i].text === item.categoria) {
                catSelect.selectedIndex = i;
                break;
            }
        }

        document.getElementById('edit_precio').value = item.precio;
        document.getElementById('edit_descripcion').value = item.descripcion || '';
        document.getElementById('edit_disponible').checked = item.disponible == 1;

        // Mostrar imagen actual
        const previewImg = document.getElementById('edit_preview_img');
        const img = item.imagen || '';
        if (img && (img.includes('/') || img.includes('.'))) {
            previewImg.src = '../../img/menu/' + img;
            previewImg.onerror = function() { this.src = ''; this.parentElement.innerHTML = '<span class="text-3xl">🍽️</span>'; };
        } else {
            previewImg.src = '';
            previewImg.parentElement.innerHTML = '<span class="text-3xl">' + (img || '🍽️') + '</span>';
        }
        
        openModal('modalEditarProducto');
    }

    function abrirModalEliminar(id) {
        document.getElementById('delete_id_producto').value = id;
        openModal('modalEliminarProducto');
    }

    function toggleDropdown(id) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
            if(el.id !== 'dropdown-' + id) el.classList.add('hidden');
        });
        document.getElementById('dropdown-' + id).classList.toggle('hidden');
    }

    window.onclick = function(event) {
        if (!event.target.closest('.dropdown-container')) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
        }
    }

    function limpiarFiltros() {
        document.getElementById('buscarMenu').value = '';
        document.getElementById('filtroCategoria').value = 'todos';
        document.getElementById('filtroDisponibilidad').value = 'todos';
        filtrarTabla();
    }

    function filtrarTabla() {
        const busqueda = document.getElementById('buscarMenu').value.toLowerCase();
        const categoria = document.getElementById('filtroCategoria').value.toLowerCase();
        const disponibilidad = document.getElementById('filtroDisponibilidad').value;

        const filas = document.querySelectorAll('.menu-item');
        let visibles = 0;

        filas.forEach(fila => {
            const fNombre = fila.dataset.nombre;
            const fCategoria = fila.dataset.categoria;
            const fDisponible = fila.dataset.disponible;

            const matchBusqueda = fNombre.includes(busqueda);
            const matchCategoria = categoria === 'todos' || fCategoria === categoria;
            const matchDisponible = disponibilidad === 'todos' || fDisponible === disponibilidad;

            if (matchBusqueda && matchCategoria && matchDisponible) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        document.getElementById('infoResultados').innerText = 'Mostrando ' + visibles + ' platos filtrados';
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
