<?php
session_start();

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$titulo = "GESTIÓN DE INVENTARIOS";

require_once __DIR__ . '/../../Controllers/InventarioController.php';

$inventarioController = new InventarioController();
$inventarioController->manejarPeticion();
$datosVista = $inventarioController->obtenerDatosVista();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$inventario = $datosVista['inventario'];
$categorias = $datosVista['categorias'];

$totalProductos = count($inventario);
$valorInventario = 0;
$stockBajo = 0;
$sinStock = 0;

foreach ($inventario as $item) {
    $valorInventario += $item['stock'] * $item['precio'];

    if ($item['stock'] == 0) {
        $sinStock++;
    } elseif ($item['stock'] <= $item['minimo']) {
        $stockBajo++;
    }
}
?>

<div class="space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-4xl font-heading font-bold text-retro-dark">
                Gestión de Inventarios
            </h1>
            <p class="font-body text-gray-500">
                Administra y controla todos los insumos de tu restaurante
            </p>
        </div>

        <div class="flex gap-3">
            <button onclick="window.location.href='?exportar=csv'" class="bg-white border-2 border-gray-200 hover:border-retro-dark text-retro-dark font-heading px-6 py-3 rounded-xl shadow-sm transition flex items-center gap-2">
                <i class="fas fa-download"></i>
                Exportar
            </button>

            <button onclick="openModal('modalNuevoProducto')" class="bg-green-600 hover:bg-green-700 text-white font-heading px-6 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Nuevo Producto
            </button>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
        <p class="font-bold">Éxito</p>
        <p>Operación realizada correctamente (<?= htmlspecialchars($_GET['success']) ?>).</p>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
        <p class="font-bold">Error</p>
        <p><?= htmlspecialchars($_GET['error']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 gap-6" style="grid-template-columns: 1fr 1.4fr 1fr 1fr 1fr;">

        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-3xl">
                <i class="fas fa-box-open"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Total de Productos</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark"><?= $totalProductos ?></h3>
                <p class="text-xs text-gray-400">Insumos registrados</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl flex-shrink-0">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="min-w-0">
                <p class="text-gray-500 font-body text-sm">Valor Inventario</p>
                <?php
                    $valorFormateado = '$' . number_format($valorInventario, 0, ',', '.');
                    $len = strlen(str_replace(['.','$'], '', $valorFormateado));
                    $tamano = $len > 9 ? 'text-base' : ($len > 6 ? 'text-xl' : 'text-3xl');
                ?>
                <h3 class="<?= $tamano ?> font-heading font-bold text-retro-dark leading-tight whitespace-nowrap">
                    <?= $valorFormateado ?>
                </h3>
                <p class="text-xs text-gray-400">Valor total</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 text-3xl">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Stock Bajo</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark"><?= $stockBajo ?></h3>
                <p class="text-xs text-gray-400">Productos bajos</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center text-red-600 text-3xl">
                <i class="fas fa-times-circle"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Sin Stock</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark"><?= $sinStock ?></h3>
                <p class="text-xs text-gray-400">Agotados</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-3xl">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Movimientos</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark">156</h3>
                <p class="text-xs text-gray-400">Este mes</p>
            </div>
        </div>

    </div>

    <!-- Filtros -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="md:col-span-2 relative">
            <input type="text" id="buscarInventario" placeholder="Buscar ingrediente o producto..."
                   class="w-full px-5 py-4 pl-12 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-retro-red outline-none bg-white" onkeyup="filtrarTabla()">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>

        <select id="filtroCategoria" class="px-5 py-4 border-2 border-gray-200 rounded-xl bg-white outline-none" onchange="filtrarTabla()">
            <option value="todos">Todas las categorías</option>
            <?php foreach($categorias as $cat): ?>
                <option value="<?= htmlspecialchars(strtolower($cat['nombre_categoria'])) ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filtroEstado" class="px-5 py-4 border-2 border-gray-200 rounded-xl bg-white outline-none" onchange="filtrarTabla()">
            <option value="todos">Todos los estados</option>
            <option value="stock">En stock</option>
            <option value="bajo">Stock bajo</option>
            <option value="sin">Sin stock</option>
        </select>

        <button onclick="limpiarFiltros()" class="px-5 py-4 border-2 border-gray-200 rounded-xl bg-white hover:border-retro-dark transition font-heading">
            <i class="fas fa-filter mr-2"></i>
            Limpiar Filtros
        </button>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="tablaInventario">
                <thead class="bg-gray-50 text-gray-600 font-heading">
                    <tr>
                        <th class="p-5">Producto</th>
                        <th class="p-5">Categoría</th>
                        <th class="p-5">Unidad</th>
                        <th class="p-5">Stock Actual</th>
                        <th class="p-5">Stock Mínimo</th>
                        <th class="p-5">Estado</th>
                        <th class="p-5">Valor Unitario</th>
                        <th class="p-5">Valor Total</th>
                        <th class="p-5 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 font-body">
                    <?php if (empty($inventario)): ?>
                        <tr>
                            <td colspan="9" class="p-5 text-center text-gray-500">No hay productos registrados en el inventario.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($inventario as $item): ?>
                        <?php
                            if ($item['stock'] == 0) {
                                $estado = 'sin';
                                $estadoTexto = 'Sin stock';
                                $badge = 'bg-red-100 text-red-700';
                                $stockColor = 'text-red-600';
                            } elseif ($item['stock'] <= $item['minimo']) {
                                $estado = 'bajo';
                                $estadoTexto = 'Stock bajo';
                                $badge = 'bg-yellow-100 text-yellow-700';
                                $stockColor = 'text-yellow-600';
                            } else {
                                $estado = 'stock';
                                $estadoTexto = 'En stock';
                                $badge = 'bg-green-100 text-green-700';
                                $stockColor = 'text-green-600';
                            }

                            $valorTotal = $item['stock'] * $item['precio'];
                            $codigo = 'ING-' . str_pad($item['id_producto'], 3, '0', STR_PAD_LEFT);
                        ?>

                        <tr class="inventario-item hover:bg-gray-50 transition"
                            data-nombre="<?= strtolower(htmlspecialchars($item['nombre'])) ?>"
                            data-categoria="<?= strtolower(htmlspecialchars($item['categoria'])) ?>"
                            data-estado="<?= $estado ?>">

                            <td class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-2xl">
                                        <?= htmlspecialchars($item['imagen']) ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-retro-dark"><?= htmlspecialchars($item['nombre']) ?></p>
                                        <p class="text-sm text-gray-500">ID: <?= $codigo ?></p>
                                    </div>
                                </div>
                            </td>

                            <td class="p-5"><?= htmlspecialchars($item['categoria']) ?></td>
                            <td class="p-5"><?= htmlspecialchars($item['unidad']) ?></td>

                            <td class="p-5 font-bold <?= $stockColor ?>">
                                <?= htmlspecialchars($item['stock']) ?>
                            </td>

                            <td class="p-5"><?= htmlspecialchars($item['minimo']) ?></td>

                            <td class="p-5">
                                <span class="px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap <?= $badge ?>">
                                    <?= $estadoTexto ?>
                                </span>
                            </td>

                            <td class="p-5 whitespace-nowrap">$<?= number_format($item['precio'], 0, ',', '.') ?></td>
                            <td class="p-5 whitespace-nowrap">$<?= number_format($valorTotal, 0, ',', '.') ?></td>

                            <td class="p-5 text-center relative dropdown-container">
                                <button onclick="toggleDropdown(<?= $item['id_producto'] ?>)" class="w-10 h-10 rounded-full hover:bg-gray-100 text-gray-600 focus:outline-none">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div id="dropdown-<?= $item['id_producto'] ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-100">
                                    <div class="py-1">
                                        <a href="#" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($item)) ?>); return false;" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-edit text-blue-500 mr-2"></i> Editar
                                        </a>
                                        <a href="#" onclick="abrirModalEliminar(<?= $item['id_producto'] ?>); return false;" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                            <i class="fas fa-trash text-red-500 mr-2"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-5 flex justify-between items-center border-t border-gray-100">
            <p class="font-body text-sm text-gray-600" id="infoResultados">
                Mostrando <?= count($inventario) ?> productos
            </p>
        </div>
    </div>

    <!-- Consejo -->
    <div class="bg-green-50 border-2 border-green-100 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="text-green-600 text-3xl">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-retro-dark">Consejo</h3>
                <p class="font-body text-gray-600">
                    Mantén tu inventario actualizado para evitar faltantes y optimizar costos.
                </p>
            </div>
        </div>

        <a href="admin_reportes.php" class="bg-white border-2 border-gray-200 hover:border-green-600 px-6 py-3 rounded-xl font-heading transition">
            <i class="fas fa-chart-simple mr-2"></i>
            Ver Reportes de Inventario
        </a>
    </div>

</div>

<!-- Modal Nuevo Producto -->
<div id="modalNuevoProducto" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-2xl font-heading font-bold text-retro-dark">Nuevo Producto</h3>
            <button onclick="closeModal('modalNuevoProducto')" class="text-gray-400 hover:text-red-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form action="admin_gestion_de_inventario.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Nombre del Producto</label>
                        <input type="text" name="nombre" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Categoría</label>
                        <select name="id_categoria" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Seleccione...</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Precio Unitario ($)</label>
                        <input type="number" name="precio" min="0" step="0.01" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Unidad (ej. kg, L, und)</label>
                        <input type="text" name="unidad" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Stock Inicial</label>
                        <input type="number" name="stock" min="0" step="0.01" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Stock Mínimo</label>
                        <input type="number" name="minimo" min="0" step="0.01" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('modalNuevoProducto')" class="px-5 py-2 text-gray-500 hover:text-gray-700 font-bold border rounded-lg">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md transition">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Producto -->
<div id="modalEditarProducto" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-2xl font-heading font-bold text-retro-dark">Editar Producto</h3>
            <button onclick="closeModal('modalEditarProducto')" class="text-gray-400 hover:text-red-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form action="admin_gestion_de_inventario.php" method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_producto" id="edit_id_producto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Nombre del Producto</label>
                        <input type="text" name="nombre" id="edit_nombre" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Categoría</label>
                        <select name="id_categoria" id="edit_categoria" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccione...</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Precio Unitario ($)</label>
                        <input type="number" name="precio" id="edit_precio" min="0" step="0.01" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Unidad (ej. kg, L, und)</label>
                        <input type="text" name="unidad" id="edit_unidad" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Stock Actual</label>
                        <input type="number" name="stock" id="edit_stock" min="0" step="0.01" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Stock Mínimo</label>
                        <input type="number" name="minimo" id="edit_minimo" min="0" step="0.01" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('modalEditarProducto')" class="px-5 py-2 text-gray-500 hover:text-gray-700 font-bold border rounded-lg">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition">Actualizar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar Producto -->
<div id="modalEliminarProducto" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-6 text-center">
            <div class="w-20 h-20 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-4xl mx-auto mb-4">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-2xl font-heading font-bold text-retro-dark mb-2">¿Eliminar Producto?</h3>
            <p class="text-gray-500 mb-6">Esta acción no se puede deshacer. El producto será removido permanentemente del inventario.</p>
            
            <form action="admin_gestion_de_inventario.php" method="POST" class="flex justify-center gap-3">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_producto" id="delete_id_producto">
                <button type="button" onclick="closeModal('modalEliminarProducto')" class="px-5 py-2 text-gray-500 hover:text-gray-700 font-bold border rounded-lg">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg shadow-md transition">Sí, eliminar</button>
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

    function abrirModalEditar(item) {
        document.getElementById('edit_id_producto').value = item.id_producto;
        document.getElementById('edit_nombre').value      = item.nombre;
        
        let catSelect = document.getElementById('edit_categoria');
        for (let i=0; i<catSelect.options.length; i++) {
            if (catSelect.options[i].text === item.categoria) {
                catSelect.selectedIndex = i;
                break;
            }
        }

        document.getElementById('edit_precio').value  = item.precio;
        document.getElementById('edit_unidad').value  = item.unidad;
        document.getElementById('edit_stock').value   = item.stock;
        document.getElementById('edit_minimo').value  = item.minimo;
        
        openModal('modalEditarProducto');
    }

    function abrirModalEliminar(id) {
        document.getElementById('delete_id_producto').value = id;
        openModal('modalEliminarProducto');
    }

    // Toggle Dropdown
    function toggleDropdown(id) {
        // Cierra todos los otros
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
            if(el.id !== 'dropdown-' + id) el.classList.add('hidden');
        });
        // Toggle el seleccionado
        document.getElementById('dropdown-' + id).classList.toggle('hidden');
    }

    // Cerrar dropdown si se hace click fuera
    window.onclick = function(event) {
        if (!event.target.closest('.dropdown-container')) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
        }
    }

    function limpiarFiltros() {
        document.getElementById('buscarInventario').value = '';
        document.getElementById('filtroCategoria').value = 'todos';
        document.getElementById('filtroEstado').value = 'todos';
        filtrarTabla();
    }

    function filtrarTabla() {
        const busqueda = document.getElementById('buscarInventario').value.toLowerCase();
        const categoria = document.getElementById('filtroCategoria').value.toLowerCase();
        const estado = document.getElementById('filtroEstado').value.toLowerCase();

        const filas = document.querySelectorAll('.inventario-item');
        let visibles = 0;

        filas.forEach(fila => {
            const fNombre = fila.dataset.nombre;
            const fCategoria = fila.dataset.categoria;
            const fEstado = fila.dataset.estado;

            const matchBusqueda = fNombre.includes(busqueda);
            const matchCategoria = categoria === 'todos' || fCategoria === categoria;
            const matchEstado = estado === 'todos' || fEstado === estado;

            if (matchBusqueda && matchCategoria && matchEstado) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        document.getElementById('infoResultados').innerText = 'Mostrando ' + visibles + ' productos filtrados';
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>