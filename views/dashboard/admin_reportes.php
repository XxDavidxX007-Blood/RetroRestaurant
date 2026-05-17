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
$titulo = "REPORTES EN TIEMPO REAL";

require_once __DIR__ . '/../../Controllers/ReportesController.php';
require_once __DIR__ . '/../../Controllers/InventarioController.php';

$reportesController = new ReportesController();

// Handle form submission for reports BEFORE any output is sent
$respuestaReporte = $reportesController->manejarPeticion();
$resultadosReporte = $respuestaReporte['datos'] ?? null;
$filtrosUsados = $respuestaReporte['filtros'] ?? null;

$datosReporte = $reportesController->obtenerDatosGraficos();
$kpis = $datosReporte['kpis'];

$invController = new InventarioController();
$categorias = $invController->obtenerDatosVista()['categorias'];

$historial = $reportesController->obtenerHistorial();

// Now require layouts
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>

<style>
    /* Estilos para impresión */
    @media print {
        body * { visibility: hidden; }
        #area-imprimible, #area-imprimible * { visibility: visible; }
        #area-imprimible { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 no-print">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-4xl font-heading font-bold text-retro-dark">
                Panel de Reportes
            </h1>
            <p class="font-body text-gray-500">
                Visualiza el rendimiento y las estadísticas generales del restaurante en tiempo real
            </p>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
        <p class="font-bold">Éxito</p>
        <p>
            <?php 
            if($_GET['success'] == 'guardado') echo 'Reporte guardado exitosamente en el historial.';
            if($_GET['success'] == 'eliminado') echo 'Reporte eliminado exitosamente del historial.';
            ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl">
                <i class="fas fa-box-open"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Total Productos</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark"><?= htmlspecialchars($kpis['total_productos']) ?></h3>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-3xl">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Valor Total Inventario</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark">$<?= number_format($kpis['valor_total'], 0, ',', '.') ?></h3>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow p-6 border-2 border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-3xl">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <p class="text-gray-500 font-body">Usuarios Registrados</p>
                <h3 class="text-3xl font-heading font-bold text-retro-dark"><?= htmlspecialchars($kpis['total_usuarios']) ?></h3>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 p-6">
            <h3 class="text-xl font-heading font-bold text-retro-dark mb-4 text-center">Valor de Inventario por Categoría</h3>
            <div class="relative h-80 w-full flex justify-center">
                <canvas id="chartCategorias"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 p-6">
            <h3 class="text-xl font-heading font-bold text-retro-dark mb-4 text-center">Estado General del Stock</h3>
            <div class="relative h-80 w-full flex justify-center">
                <canvas id="chartStock"></canvas>
            </div>
        </div>
    </div>

    <!-- Generador de Reportes -->
    <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 p-8 mt-8">
        <h2 class="text-2xl font-heading font-bold text-retro-dark mb-6 border-b pb-4">
            <i class="fas fa-file-export mr-2 text-retro-red"></i> Generador de Reportes Especiales
        </h2>
        
        <form action="admin_reportes.php" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
            <input type="hidden" name="accion" value="generar_reporte">
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Filtrar por:</label>
                <select name="tipo_filtro" id="tipo_filtro" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl outline-none focus:border-retro-red" onchange="toggleFiltros()">
                    <option value="todo">Todo el Inventario</option>
                    <option value="categoria">Por Categoría</option>
                    <option value="estado">Por Estado de Stock</option>
                </select>
            </div>

            <div id="div_categoria" class="hidden">
                <label class="block text-gray-700 font-bold mb-2">Seleccione Categoría:</label>
                <select name="filtro_categoria" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl outline-none focus:border-retro-red">
                    <option value="todas">Todas las categorías</option>
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="div_estado" class="hidden">
                <label class="block text-gray-700 font-bold mb-2">Seleccione Estado:</label>
                <select name="filtro_estado" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl outline-none focus:border-retro-red">
                    <option value="sin">Sin Stock (Agotado)</option>
                    <option value="bajo">Stock Bajo</option>
                    <option value="stock">En Stock Normal</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Formato:</label>
                <select name="formato" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl outline-none focus:border-retro-red">
                    <option value="pantalla">Ver en Pantalla (Imprimible)</option>
                    <option value="csv">Descargar CSV (Excel)</option>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full px-6 py-3 bg-retro-red hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition font-heading">
                    <i class="fas fa-play mr-2"></i> Generar Reporte
                </button>
            </div>
        </form>
    </div>

    <!-- Historial de Reportes -->
    <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-100 p-8 mt-8">
        <h2 class="text-2xl font-heading font-bold text-retro-dark mb-6 border-b pb-4">
            <i class="fas fa-history mr-2 text-retro-dark"></i> Historial de Reportes Guardados
        </h2>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100 text-retro-dark font-heading">
                    <tr>
                        <th class="p-4 border-b">ID</th>
                        <th class="p-4 border-b">Fecha y Hora</th>
                        <th class="p-4 border-b">Generado Por</th>
                        <th class="p-4 border-b">Filtro Usado</th>
                        <th class="p-4 border-b">Total Productos</th>
                        <th class="p-4 border-b">Valor ($)</th>
                        <th class="p-4 border-b text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($historial)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No hay reportes guardados en el historial.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($historial as $rep): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 text-gray-500">#<?= $rep['id_reporte'] ?></td>
                            <td class="p-4 font-bold"><?= date('d/m/Y h:i A', strtotime($rep['fecha_generacion'])) ?></td>
                            <td class="p-4"><?= htmlspecialchars($rep['generado_por_nombre']) ?></td>
                            <td class="p-4">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-bold">
                                    <?= htmlspecialchars($rep['tipo_filtro']) ?> (<?= htmlspecialchars($rep['valor_filtro']) ?>)
                                </span>
                            </td>
                            <td class="p-4 font-bold text-retro-dark"><?= $rep['total_productos'] ?></td>
                            <td class="p-4 text-green-600 font-bold">$<?= number_format($rep['valor_total'], 0, ',', '.') ?></td>
                            <td class="p-4 text-center">
                                <button onclick="eliminarReporteGuardado(<?= $rep['id_reporte'] ?>)" class="w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-200 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Área Imprimible del Reporte Generado -->
<?php if (isset($resultadosReporte) && is_array($resultadosReporte)): ?>
<div id="area-imprimible" class="mt-8 bg-white p-8 rounded-2xl shadow-lg border-2 border-gray-100">
    <div class="text-center mb-8 border-b-2 border-retro-dark pb-4">
        <h1 class="text-4xl font-heading font-bold text-retro-dark mb-2">REPORTE DE INVENTARIO</h1>
        <p class="text-gray-500 font-body">Filtro aplicado: <?= htmlspecialchars($filtrosUsados['tipo']) ?> - <?= htmlspecialchars($filtrosUsados['valor']) ?></p>
        <p class="text-gray-500 font-body">Fecha de generación: <?= date('d/m/Y H:i:s') ?></p>
        <p class="text-gray-500 font-body">Generado por: <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?></p>
    </div>

    <table class="w-full text-left border-collapse">
        <thead class="bg-gray-100 text-retro-dark font-heading border-b-2 border-gray-300">
            <tr>
                <th class="p-4 border">ID</th>
                <th class="p-4 border">Producto</th>
                <th class="p-4 border">Categoría</th>
                <th class="p-4 border">Stock</th>
                <th class="p-4 border">Precio Und.</th>
                <th class="p-4 border">Valor Total</th>
                <th class="p-4 border">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 font-body">
            <?php 
            $totalSuma = 0;
            $countProd = count($resultadosReporte);

            if (empty($resultadosReporte)): 
            ?>
                <tr>
                    <td colspan="7" class="p-4 text-center text-gray-500">No se encontraron resultados para los filtros seleccionados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($resultadosReporte as $item): 
                    $codigo = 'ING-' . str_pad($item['id_producto'], 3, '0', STR_PAD_LEFT);
                    $valorTotal = $item['stock'] * $item['precio'];
                    $totalSuma += $valorTotal;

                    $estadoTexto = 'En stock';
                    $estadoClase = 'text-green-600 font-bold';
                    if ($item['stock'] == 0) {
                        $estadoTexto = 'Sin stock';
                        $estadoClase = 'text-red-600 font-bold';
                    } elseif ($item['stock'] <= $item['minimo']) {
                        $estadoTexto = 'Stock bajo';
                        $estadoClase = 'text-yellow-600 font-bold';
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border"><?= $codigo ?></td>
                    <td class="p-3 border font-bold"><?= htmlspecialchars($item['nombre']) ?></td>
                    <td class="p-3 border"><?= htmlspecialchars($item['categoria']) ?></td>
                    <td class="p-3 border"><?= htmlspecialchars($item['stock'] . ' ' . $item['unidad']) ?></td>
                    <td class="p-3 border">$<?= number_format($item['precio'], 0, ',', '.') ?></td>
                    <td class="p-3 border">$<?= number_format($valorTotal, 0, ',', '.') ?></td>
                    <td class="p-3 border <?= $estadoClase ?>"><?= $estadoTexto ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="bg-gray-50 font-bold font-heading">
            <tr>
                <td colspan="5" class="p-4 text-right border">VALOR TOTAL DEL REPORTE:</td>
                <td colspan="2" class="p-4 text-left border text-retro-dark text-xl">$<?= number_format($totalSuma, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-8 flex flex-wrap justify-center gap-4 no-print">
        <button onclick="window.print()" class="px-8 py-3 bg-retro-dark text-white font-bold rounded-xl shadow-lg hover:bg-black transition text-lg flex items-center">
            <i class="fas fa-print mr-2"></i> Imprimir / Guardar como PDF
        </button>

        <form action="admin_reportes.php" method="POST">
            <input type="hidden" name="accion" value="guardar_reporte">
            <input type="hidden" name="nombre_usuario" value="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?>">
            <input type="hidden" name="tipo_filtro" value="<?= htmlspecialchars($filtrosUsados['tipo']) ?>">
            <input type="hidden" name="valor_filtro" value="<?= htmlspecialchars($filtrosUsados['valor']) ?>">
            <input type="hidden" name="total_productos" value="<?= $countProd ?>">
            <input type="hidden" name="valor_total" value="<?= $totalSuma ?>">
            <input type="hidden" name="datos_json" value="<?= htmlspecialchars(json_encode($resultadosReporte)) ?>">
            
            <button type="submit" class="px-8 py-3 bg-green-600 text-white font-bold rounded-xl shadow-lg hover:bg-green-700 transition text-lg flex items-center">
                <i class="fas fa-save mr-2"></i> Guardar en Historial
            </button>
        </form>

        <button onclick="document.getElementById('area-imprimible').style.display='none'" class="px-8 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl shadow-lg hover:bg-gray-300 transition text-lg flex items-center">
            <i class="fas fa-times mr-2"></i> Cerrar Visualización
        </button>
    </div>
</div>

<script>
    window.onload = function() {
        if(document.getElementById('area-imprimible')){
            document.getElementById('area-imprimible').scrollIntoView({ behavior: 'smooth' });
        }
    };
</script>
<?php endif; ?>

<!-- Formulario Oculto para Eliminar Historial -->
<form id="formEliminarHistorial" action="admin_reportes.php" method="POST" class="hidden">
    <input type="hidden" name="accion" value="eliminar_reporte">
    <input type="hidden" name="id_reporte" id="delete_id_reporte">
</form>

<script>
    function toggleFiltros() {
        const tipo = document.getElementById('tipo_filtro').value;
        const divCat = document.getElementById('div_categoria');
        const divEst = document.getElementById('div_estado');

        divCat.classList.add('hidden');
        divEst.classList.add('hidden');

        if (tipo === 'categoria') {
            divCat.classList.remove('hidden');
        } else if (tipo === 'estado') {
            divEst.classList.remove('hidden');
        }
    }

    function eliminarReporteGuardado(id) {
        if(confirm('¿Estás seguro de que deseas eliminar este reporte guardado? Esta acción no se puede deshacer.')){
            document.getElementById('delete_id_reporte').value = id;
            document.getElementById('formEliminarHistorial').submit();
        }
    }

    // Datos inyectados desde PHP
    const datosCategorias = <?= json_encode($datosReporte['grafico_categorias']) ?>;
    const datosStock = <?= json_encode($datosReporte['grafico_stock']) ?>;

    const coloresCategorias = ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1'];

    const ctxCategorias = document.getElementById('chartCategorias').getContext('2d');
    new Chart(ctxCategorias, {
        type: 'doughnut',
        data: {
            labels: datosCategorias.labels,
            datasets: [{
                label: 'Valor Total ($)',
                data: datosCategorias.data,
                backgroundColor: coloresCategorias,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } }
        }
    });

    const ctxStock = document.getElementById('chartStock').getContext('2d');
    new Chart(ctxStock, {
        type: 'pie',
        data: {
            labels: datosStock.labels,
            datasets: [{
                label: 'Cantidad de Productos',
                data: datosStock.data,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
