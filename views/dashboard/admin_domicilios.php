<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1,'1','administrador', 2, '2', 'empleado'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}
$usuario = $_SESSION['usuario'];
$titulo  = "DOMICILIOS";
require_once __DIR__ . '/../../Controllers/DomicilioController.php';
$controller = new DomicilioController();
$controller->manejarPeticion();
$datos = $controller->obtenerDatosVista();
extract($datos);
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
        <i class="fas fa-motorcycle text-retro-red"></i> Domicilios
      </h1>
      <p class="text-gray-500 font-body text-sm mt-1">Gestiona y supervisa todos los pedidos a domicilio.</p>
    </div>
    <button onclick="document.getElementById('modalCrear').classList.remove('hidden')"
      class="bg-retro-red hover:bg-red-700 text-white font-heading px-5 py-3 rounded-xl shadow flex items-center gap-2 transition">
      <i class="fas fa-plus"></i> Nuevo Domicilio
    </button>
  </div>

  <?php if (isset($_GET['success'])): ?>
  <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2">
    <i class="fas fa-check-circle"></i>
    <span class="text-sm font-body">
      <?= $_GET['success']==='creado' ? 'Domicilio registrado correctamente.' : 'Estado actualizado correctamente.' ?>
    </span>
  </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-2">
    <i class="fas fa-exclamation-circle"></i>
    <span class="text-sm font-body"><?= htmlspecialchars($_GET['error']) ?></span>
  </div>
  <?php endif; ?>

  <!-- KPI CARDS -->
  <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0" style="background:#DBEAFE;color:#2563EB;">
        <i class="fas fa-motorcycle"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500 font-body">Domicilios hoy</p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $hoy ?></p>
        <p class="text-xs <?= $var>=0?'text-green-600':'text-red-500' ?> font-body"><?= $var>=0?'+':'' ?><?= $var ?>% vs ayer</p>
      </div>
    </div>
    <?php
    $kpiCfg = [
      ['bg'=>'#FEF3C7','color'=>'#D97706','icon'=>'fa-hourglass-half','label'=>'Pendientes'],
      ['bg'=>'#DBEAFE','color'=>'#2563EB','icon'=>'fa-fire-burner',   'label'=>'En preparación'],
      ['bg'=>'#D1FAE5','color'=>'#059669','icon'=>'fa-check-circle',  'label'=>'Entregados'],
      ['bg'=>'#FEE2E2','color'=>'#DC2626','icon'=>'fa-ban',           'label'=>'Cancelados'],
    ];
    $ki=0;
    foreach ($kpiEstados as $nombre => $conteo):
      if ($ki>=4) break; $cfg=$kpiCfg[$ki++];
    ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
           style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
        <i class="fas <?= $cfg['icon'] ?>"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500 font-body"><?= $cfg['label'] ?></p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $conteo ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- TABLA -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Tabs estado + filtro fecha -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 pt-5 pb-0 border-b border-gray-100">
      <div class="flex items-center gap-1 overflow-x-auto">
        <?php
        $tabsEst = array_merge([['id_estado_pedido'=>'','nombre_estado'=>'todos']], $estados);
        foreach ($tabsEst as $tab):
          $val = $tab['nombre_estado'];
          $lbl = $val==='todos' ? 'Todos' : ucfirst(str_replace('_',' ',$val));
          $act = ($filtro_estado===$val);
        ?>
        <a href="?estado=<?= urlencode($val) ?><?= $filtro_fecha?'&fecha='.urlencode($filtro_fecha):'' ?>"
           class="px-4 py-3 text-sm font-body whitespace-nowrap transition border-b-2
                  <?= $act?'border-retro-red text-retro-red font-bold':'border-transparent text-gray-500 hover:text-gray-700' ?>">
          <?= $lbl ?>
        </a>
        <?php endforeach; ?>
      </div>
      <div class="flex items-center gap-2 pb-3">
        <div class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50">
          <i class="fas fa-calendar text-gray-400"></i>
          <input type="date" value="<?= htmlspecialchars($filtro_fecha) ?>"
                 class="bg-transparent outline-none text-sm font-body"
                 onchange="aplicarFecha(this.value)">
        </div>
        <?php if ($filtro_fecha): ?>
        <a href="?estado=<?= urlencode($filtro_estado) ?>"
           class="text-xs text-gray-400 hover:text-red-500 px-2 py-2 rounded-lg hover:bg-red-50 transition">
          <i class="fas fa-times"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Pedido</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Cliente</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Contacto</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Fecha</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Total</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Estado</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider text-center">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 font-body">
          <?php if (empty($domicilios)): ?>
          <tr>
            <td colspan="7" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center gap-3 text-gray-400">
                <i class="fas fa-motorcycle text-5xl"></i>
                <p class="text-lg font-heading">Sin domicilios</p>
                <p class="text-sm">No hay pedidos a domicilio con los filtros seleccionados.</p>
                <button onclick="document.getElementById('modalCrear').classList.remove('hidden')"
                  class="mt-2 bg-retro-red hover:bg-red-700 text-white font-heading px-5 py-2 rounded-xl text-sm transition">
                  <i class="fas fa-plus mr-1"></i> Registrar domicilio
                </button>
              </div>
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($domicilios as $d):
            $b   = DomicilioController::badgeEstado($d['estado']);
            $num = str_pad($d['id_pedido'],5,'0',STR_PAD_LEFT);
            $esHoy = (date('Y-m-d') === $d['fecha_pedido']);
          ?>
          <tr class="hover:bg-gray-50 transition">
            <td class="px-5 py-4">
              <p class="font-bold text-gray-800 text-sm">#ORD-<?= $num ?></p>
              <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($d['fecha_pedido'])) ?></p>
            </td>
            <td class="px-5 py-4">
              <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($d['nombre_cliente'].' '.$d['apellidos_cliente']) ?></p>
            </td>
            <td class="px-5 py-4">
              <span class="text-sm text-gray-600 flex items-center gap-1">
                <i class="fas fa-phone text-gray-400 text-xs"></i>
                <?= htmlspecialchars($d['telefono_cliente']) ?>
              </span>
            </td>
            <td class="px-5 py-4">
              <p class="text-sm text-gray-700 flex items-center gap-1">
                <i class="fas fa-calendar text-gray-400 text-xs"></i>
                <?= date('d/m/Y', strtotime($d['fecha_pedido'])) ?>
              </p>
              <?php if ($esHoy): ?>
              <p class="text-xs text-green-500 mt-0.5">Hoy</p>
              <?php endif; ?>
            </td>
            <td class="px-5 py-4">
              <span class="font-bold text-green-600 text-sm">$<?= number_format($d['total'],0,',','.') ?></span>
            </td>
            <td class="px-5 py-4">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                    style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $b['dot'] ?>;"></span>
                <?= $b['label'] ?>
              </span>
            </td>
            <td class="px-5 py-4 text-center">
              <button onclick="abrirEstado(<?= $d['id_pedido'] ?>, <?= $d['id_estado_pedido'] ?>)"
                class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-orange-100 text-gray-500 hover:text-orange-600 transition flex items-center justify-center mx-auto" title="Cambiar estado">
                <i class="fas fa-pen text-xs"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div class="px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-3 border-t border-gray-100 bg-gray-50">
      <p class="text-sm text-gray-500 font-body">
        Mostrando <?= $total>0?min(($pagina-1)*$por_pagina+1,$total):0 ?>
        a <?= min($pagina*$por_pagina,$total) ?> de <?= $total ?> domicilios
      </p>
      <div class="flex items-center gap-1">
        <?php if ($pagina>1): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$pagina-1])) ?>"
           class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition">Anterior</a>
        <?php endif; ?>
        <?php for ($p2=max(1,$pagina-2);$p2<=min($totalPaginas,$pagina+2);$p2++): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$p2])) ?>"
           class="w-9 h-9 flex items-center justify-center text-sm rounded-lg border transition
                  <?= $p2===$pagina?'bg-retro-red text-white border-retro-red font-bold':'border-gray-200 hover:bg-white text-gray-600' ?>">
          <?= $p2 ?>
        </a>
        <?php endfor; ?>
        <?php if ($pagina<$totalPaginas): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$pagina+1])) ?>"
           class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition">Siguiente</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CREAR DOMICILIO -->
<div id="modalCrear" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
      <h3 class="text-xl font-heading font-bold text-gray-800">
        <i class="fas fa-motorcycle text-retro-red mr-2"></i>Nuevo Domicilio
      </h3>
      <button onclick="document.getElementById('modalCrear').classList.add('hidden')"
        class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-lg"></i></button>
    </div>
    <form action="admin_domicilios.php" method="POST" class="p-6 space-y-4">
      <input type="hidden" name="accion" value="crear">

      <div>
        <label class="block text-sm font-bold text-gray-700 mb-1">Cliente</label>
        <select name="id_cliente" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red bg-white font-body text-sm">
          <option value="">Sin cliente asignado</option>
          <?php foreach ($clientes as $c): ?>
          <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre'].' '.$c['apellidos']) ?> — <?= htmlspecialchars($c['telefono']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-bold text-gray-700 mb-1">
          Empleado / Mesero <span class="text-red-500">*</span>
        </label>
        <?php if (empty($meseros)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-sm text-yellow-700">
          <i class="fas fa-exclamation-triangle mr-1"></i>
          No hay empleados registrados. Registra un usuario con rol <strong>Empleado</strong> primero.
        </div>
        <input type="hidden" name="id_mesero" value="0">
        <?php else: ?>
        <select name="id_mesero" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red bg-white font-body text-sm">
          <option value="">Seleccione...</option>
          <?php foreach ($meseros as $m): ?>
          <option value="<?= $m['id_mesero'] ?>"><?= htmlspecialchars($m['nombre'].' '.$m['apellidos']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-bold text-gray-700 mb-1">Fecha del pedido</label>
        <input type="date" name="fecha_pedido" value="<?= date('Y-m-d') ?>" required
          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
      </div>

      <div>
        <label class="block text-sm font-bold text-gray-700 mb-1">Estado inicial</label>
        <select name="id_estado_pedido" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red bg-white font-body text-sm">
          <?php foreach ($estados as $e): ?>
          <option value="<?= $e['id_estado_pedido'] ?>" <?= $e['nombre_estado']==='pendiente'?'selected':'' ?>>
            <?= ucfirst(str_replace('_',' ',$e['nombre_estado'])) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalCrear').classList.add('hidden')"
          class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">Cancelar</button>
        <button type="submit" <?= empty($meseros)?'disabled title="Registra un empleado primero"':'' ?>
          class="px-5 py-2 bg-retro-red hover:bg-red-700 text-white rounded-xl font-heading text-sm shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
          Guardar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL CAMBIAR ESTADO -->
<div id="modalEstado" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <h3 class="text-lg font-heading font-bold text-gray-800">Cambiar Estado</h3>
      <button onclick="document.getElementById('modalEstado').classList.add('hidden')"
        class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
    </div>
    <form action="admin_domicilios.php" method="POST" class="p-6 space-y-4">
      <input type="hidden" name="accion" value="cambiar_estado">
      <input type="hidden" name="id_pedido" id="est_id">
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">Nuevo estado</label>
        <select name="id_estado_pedido" id="est_select"
          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red bg-white font-body text-sm">
          <?php foreach ($estados as $e): ?>
          <option value="<?= $e['id_estado_pedido'] ?>"><?= ucfirst(str_replace('_',' ',$e['nombre_estado'])) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalEstado').classList.add('hidden')"
          class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">Cancelar</button>
        <button type="submit"
          class="px-5 py-2 bg-retro-red hover:bg-red-700 text-white rounded-xl font-heading text-sm shadow transition">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
function aplicarFecha(val) {
  const url = new URL(window.location.href);
  val ? url.searchParams.set('fecha',val) : url.searchParams.delete('fecha');
  url.searchParams.set('pagina',1);
  window.location.href = url.toString();
}
function abrirEstado(id, estadoActual) {
  document.getElementById('est_id').value = id;
  const sel = document.getElementById('est_select');
  for (let i=0;i<sel.options.length;i++) {
    if (sel.options[i].value == estadoActual) { sel.selectedIndex=i; break; }
  }
  document.getElementById('modalEstado').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
