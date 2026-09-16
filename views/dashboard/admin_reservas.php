<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_exception_handler(function($e) {
    die('<pre style="background:#111;color:#f66;padding:20px;font-size:13px">' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</pre>');
});

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [1, '1', 'administrador', 2, '2', 'empleado'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}
$usuario = $_SESSION['usuario'];
$titulo  = "GESTIÓN DE RESERVAS";
require_once __DIR__ . '/../../Controllers/ReservaController.php';
$controller = new ReservaController();
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
        <i class="fas fa-calendar-check text-retro-red"></i> Gestión de Reservas
      </h1>
      <p class="text-gray-500 font-body text-sm mt-1">Administra y supervisa todas las reservas del restaurante.</p>
    </div>
    <button onclick="document.getElementById('modalCrear').classList.remove('hidden')"
      class="bg-retro-red hover:bg-red-700 text-white font-heading px-5 py-3 rounded-xl shadow flex items-center gap-2 transition">
      <i class="fas fa-plus"></i> Nueva Reserva
    </button>
  </div>

  <?php if (isset($_GET['success'])): ?>
  <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2">
    <i class="fas fa-check-circle"></i>
    <span class="text-sm font-body">Operación realizada correctamente.</span>
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
      <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0" style="background:#EDE9FE;color:#7C3AED;">
        <i class="fas fa-calendar-day"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500 font-body">Reservas hoy</p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $reservasHoy ?></p>
        <p class="text-xs <?= $varHoy >= 0 ? 'text-green-600' : 'text-red-500' ?> font-body">
          <?= $varHoy >= 0 ? '+' : '' ?><?= $varHoy ?>% vs ayer
        </p>
      </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0" style="background:#FEF3C7;color:#D97706;">
        <i class="fas fa-calendar-plus"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500 font-body">Reservas mañana</p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $reservasManana ?></p>
        <p class="text-xs <?= $varManana >= 0 ? 'text-green-600' : 'text-red-500' ?> font-body">
          <?= $varManana >= 0 ? '+' : '' ?><?= $varManana ?>% vs hoy
        </p>
      </div>
    </div>
    <?php
    $kpiCfg = [
      ['bg'=>'#D1FAE5','color'=>'#059669','icon'=>'fa-check-circle','label'=>'Confirmadas','sub'=>'Hoy'],
      ['bg'=>'#FEF3C7','color'=>'#D97706','icon'=>'fa-hourglass-half','label'=>'Pendientes','sub'=>'Hoy'],
      ['bg'=>'#FEE2E2','color'=>'#DC2626','icon'=>'fa-ban','label'=>'Canceladas','sub'=>'Hoy'],
    ];
    $ki = 0;
    foreach ($kpiEstados as $nombre => $conteo):
      if ($ki >= 3) break;
      $cfg = $kpiCfg[$ki++];
    ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
           style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
        <i class="fas <?= $cfg['icon'] ?>"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500 font-body"><?= $cfg['label'] ?></p>
        <p class="text-2xl font-heading font-bold text-gray-800"><?= $conteo ?></p>
        <p class="text-xs text-gray-400 font-body"><?= $cfg['sub'] ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- TABLA -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Tabs + filtros -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 pt-5 border-b border-gray-100">
      <div class="flex items-center gap-1 overflow-x-auto">
        <?php
        $tabs = [
          'todos'  => 'Todas',
          'hoy'    => 'Hoy',
          'manana' => 'Mañana',
          'semana' => 'Esta semana',
        ];
        foreach ($tabs as $key => $label):
          $act = ($filtro_tab === $key);
        ?>
        <a href="?tab=<?= $key ?><?= $filtro_estado !== 'todos' ? '&estado='.urlencode($filtro_estado) : '' ?>"
           class="px-4 py-3 text-sm font-body whitespace-nowrap transition border-b-2
                  <?= $act ? 'border-retro-red text-retro-red font-bold' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
          <?= $label ?>
        </a>
        <?php endforeach; ?>
      </div>
      <div class="flex items-center gap-2 pb-3">
        <div class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-600 bg-gray-50">
          <i class="fas fa-calendar text-gray-400"></i>
          <input type="date" id="filtroFecha" value="<?= htmlspecialchars($filtro_fecha) ?>"
                 class="bg-transparent outline-none text-sm font-body"
                 onchange="aplicarFecha(this.value)">
        </div>
        <?php if ($filtro_fecha): ?>
        <a href="?tab=<?= $filtro_tab ?>"
           class="text-xs text-gray-400 hover:text-red-500 px-2 py-2 rounded-lg hover:bg-red-50 transition" title="Limpiar">
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
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Hora</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Cliente</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Contacto</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Personas</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Mesa</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Fecha</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider">Estado</th>
            <th class="px-5 py-4 text-xs font-heading text-gray-500 uppercase tracking-wider text-center">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 font-body">
          <?php if (empty($reservas)): ?>
          <tr>
            <td colspan="8" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center gap-3 text-gray-400">
                <i class="fas fa-calendar-xmark text-5xl"></i>
                <p class="text-lg font-heading">Sin reservas</p>
                <p class="text-sm">No hay reservas con los filtros seleccionados.</p>
              </div>
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($reservas as $r):
            $b    = ReservaController::badgeEstado($r['estado']);
            $hora = date('g:i A', strtotime($r['hora_reserva']));
            $fecha = date('d/m/Y', strtotime($r['fecha_reserva']));
            $esHoy = (date('Y-m-d') === $r['fecha_reserva']);
          ?>
          <tr class="hover:bg-gray-50 transition">
            <td class="px-5 py-4">
              <span class="font-bold text-gray-800 text-sm"><?= $hora ?></span>
            </td>
            <td class="px-5 py-4">
              <p class="font-semibold text-gray-800 text-sm">
                <?= htmlspecialchars($r['nombre_cliente'] . ' ' . $r['apellidos_cliente']) ?>
              </p>
            </td>
            <td class="px-5 py-4">
              <span class="text-sm text-gray-600 flex items-center gap-1">
                <i class="fas fa-phone text-gray-400 text-xs"></i>
                <?= htmlspecialchars($r['telefono_cliente']) ?>
              </span>
            </td>
            <td class="px-5 py-4">
              <span class="text-sm text-gray-700 flex items-center gap-1">
                <i class="fas fa-users text-gray-400 text-xs"></i>
                <?= $r['numero_personas'] ?>
              </span>
            </td>
            <td class="px-5 py-4">
              <p class="font-semibold text-gray-800 text-sm">Mesa <?= $r['numero_mesa'] ?></p>
              <p class="text-xs text-gray-400">Cap. <?= $r['capacidad_mesa'] ?></p>
            </td>
            <td class="px-5 py-4">
              <p class="text-sm text-gray-700 flex items-center gap-1">
                <i class="fas fa-calendar text-gray-400 text-xs"></i> <?= $fecha ?>
              </p>
              <p class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                <i class="fas fa-clock text-gray-300 text-xs"></i> <?= $hora ?><?= $esHoy ? ' · Hoy' : '' ?>
              </p>
            </td>
            <td class="px-5 py-4">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                    style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $b['dot'] ?>;"></span>
                <?= $b['label'] ?>
              </span>
            </td>
            <td class="px-5 py-4">
              <div class="flex items-center justify-center gap-2">
                <button onclick="abrirEditar(<?= htmlspecialchars(json_encode($r)) ?>)"
                  class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-blue-100 text-gray-500 hover:text-blue-600 transition flex items-center justify-center" title="Editar">
                  <i class="fas fa-pen text-xs"></i>
                </button>
                <button onclick="abrirEliminar(<?= $r['id_reserva'] ?>)"
                  class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-600 transition flex items-center justify-center" title="Eliminar">
                  <i class="fas fa-trash text-xs"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div class="px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-3 border-t border-gray-100 bg-gray-50">
      <p class="text-sm text-gray-500 font-body">
        Mostrando <?= $total > 0 ? min(($pagina-1)*$por_pagina+1, $total) : 0 ?>
        a <?= min($pagina*$por_pagina, $total) ?> de <?= $total ?> reservas
      </p>
      <div class="flex items-center gap-1">
        <?php if ($pagina > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina-1])) ?>"
           class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition font-body">Anterior</a>
        <?php endif; ?>
        <?php for ($p2 = max(1,$pagina-2); $p2 <= min($totalPaginas,$pagina+2); $p2++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $p2])) ?>"
           class="w-9 h-9 flex items-center justify-center text-sm rounded-lg border transition font-body
                  <?= $p2===$pagina ? 'bg-retro-red text-white border-retro-red font-bold' : 'border-gray-200 hover:bg-white text-gray-600' ?>">
          <?= $p2 ?>
        </a>
        <?php endfor; ?>
        <?php if ($pagina < $totalPaginas): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina+1])) ?>"
           class="px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-white text-gray-600 transition font-body">Siguiente</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CREAR -->
<div id="modalCrear" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
      <h3 class="text-xl font-heading font-bold text-gray-800"><i class="fas fa-plus text-retro-red mr-2"></i>Nueva Reserva</h3>
      <button onclick="document.getElementById('modalCrear').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-lg"></i></button>
    </div>
    <form action="admin_reservas.php" method="POST" id="formAdminCrear" class="p-6 space-y-4">
      <input type="hidden" name="accion" value="crear">
      <input type="hidden" name="id_mesa" id="admin_crear_id_mesa">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="block text-sm font-bold text-gray-700 mb-1">Cliente</label>
          <select name="id_cliente" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red bg-white font-body text-sm">
            <option value="">Sin cliente asignado</option>
            <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre'].' '.$c['apellidos']) ?> — <?= htmlspecialchars($c['telefono']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Fecha</label>
          <input type="date" name="fecha_reserva" id="admin_crear_fecha" required value="<?= date('Y-m-d') ?>"
                 onchange="actualizarDisponibilidadAdmin('crear')"
                 class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Hora</label>
          <input type="time" name="hora_reserva" id="admin_crear_hora" required value="12:00"
                 onchange="actualizarDisponibilidadAdmin('crear')"
                 class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Personas</label>
          <input type="number" name="numero_personas" min="1" value="2" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
          <select name="id_estado_reserva" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red bg-white font-body text-sm">
            <?php foreach ($estados as $e): ?>
            <option value="<?= $e['id_estado_reserva'] ?>"><?= ucfirst($e['nombre_estado']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Selector visual de mesas -->
        <div class="col-span-2">
          <label class="block text-sm font-bold text-gray-700 mb-2">
            Mesa <span class="text-red-500">*</span>
            <span class="text-xs font-normal text-gray-400 ml-1">— las rojas ya tienen reserva en ese horario</span>
          </label>
          <div class="flex items-center gap-4 mb-2 text-xs font-body text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span> Disponible</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span> Ocupada</span>
          </div>
          <div id="mesas_admin_crear" class="grid grid-cols-4 gap-2 max-h-44 overflow-y-auto pr-1">
            <?php foreach ($mesas as $m): ?>
            <button type="button"
              data-id="<?= $m['id_mesa'] ?>"
              data-cap="<?= $m['capacidad'] ?>"
              data-num="<?= $m['numero_mesa'] ?>"
              onclick="seleccionarMesaAdmin(this,'crear')"
              class="admin-mesa-btn relative flex flex-col items-center justify-center gap-1 p-3 rounded-xl border-2 border-gray-200 bg-white hover:border-retro-red transition text-sm font-body cursor-pointer text-center">
              <i class="fas fa-chair text-gray-400 text-base"></i>
              <span class="font-bold text-gray-700 text-xs">Mesa <?= $m['numero_mesa'] ?></span>
              <span class="text-xs text-gray-400">Cap. <?= $m['capacidad'] ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <div id="admin_crear_mesa_error" class="text-red-500 text-xs mt-1 hidden">Selecciona una mesa disponible.</div>
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalCrear').classList.add('hidden')"
          class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">Cancelar</button>
        <button type="submit" class="px-5 py-2 bg-retro-red hover:bg-red-700 text-white rounded-xl font-heading text-sm shadow transition">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
      <h3 class="text-xl font-heading font-bold text-gray-800"><i class="fas fa-pen text-blue-500 mr-2"></i>Editar Reserva</h3>
      <button onclick="document.getElementById('modalEditar').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-lg"></i></button>
    </div>
    <form action="admin_reservas.php" method="POST" id="formAdminEditar" class="p-6 space-y-4">
      <input type="hidden" name="accion" value="editar">
      <input type="hidden" name="id_reserva" id="edit_id">
      <input type="hidden" name="id_mesa" id="admin_edit_id_mesa">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Personas</label>
          <input type="number" name="numero_personas" id="edit_personas" min="1" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
          <select name="id_estado_reserva" id="edit_estado" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red bg-white font-body text-sm">
            <?php foreach ($estados as $e): ?>
            <option value="<?= $e['id_estado_reserva'] ?>"><?= ucfirst($e['nombre_estado']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Fecha</label>
          <input type="date" name="fecha_reserva" id="edit_fecha" required
                 onchange="actualizarDisponibilidadAdmin('editar')"
                 class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Hora</label>
          <input type="time" name="hora_reserva" id="edit_hora" required
                 onchange="actualizarDisponibilidadAdmin('editar')"
                 class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm">
        </div>

        <!-- Selector visual de mesas (editar admin) -->
        <div class="col-span-2">
          <label class="block text-sm font-bold text-gray-700 mb-2">
            Mesa <span class="text-red-500">*</span>
            <span class="text-xs font-normal text-gray-400 ml-1">— las rojas ya están reservadas en ese horario</span>
          </label>
          <div class="flex items-center gap-4 mb-2 text-xs font-body text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span> Disponible</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span> Ocupada</span>
          </div>
          <div id="mesas_admin_editar" class="grid grid-cols-4 gap-2 max-h-44 overflow-y-auto pr-1">
            <?php foreach ($mesas as $m): ?>
            <button type="button"
              data-id="<?= $m['id_mesa'] ?>"
              data-cap="<?= $m['capacidad'] ?>"
              data-num="<?= $m['numero_mesa'] ?>"
              onclick="seleccionarMesaAdmin(this,'editar')"
              class="admin-mesa-btn relative flex flex-col items-center justify-center gap-1 p-3 rounded-xl border-2 border-gray-200 bg-white hover:border-retro-red transition text-sm font-body cursor-pointer text-center">
              <i class="fas fa-chair text-gray-400 text-base"></i>
              <span class="font-bold text-gray-700 text-xs">Mesa <?= $m['numero_mesa'] ?></span>
              <span class="text-xs text-gray-400">Cap. <?= $m['capacidad'] ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <div id="admin_editar_mesa_error" class="text-red-500 text-xs mt-1 hidden">Selecciona una mesa disponible.</div>
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalEditar').classList.add('hidden')"
          class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">Cancelar</button>
        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-heading text-sm shadow transition">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ELIMINAR -->
<div id="modalEliminar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="p-8 text-center">
      <div class="w-20 h-20 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-4xl mx-auto mb-4">
        <i class="fas fa-trash-alt"></i>
      </div>
      <h3 class="text-2xl font-heading font-bold text-gray-800 mb-2">¿Eliminar reserva?</h3>
      <p class="text-gray-500 text-sm mb-6">Esta acción no se puede deshacer.</p>
      <form action="admin_reservas.php" method="POST" class="flex justify-center gap-3">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id_reserva" id="del_id">
        <button type="button" onclick="document.getElementById('modalEliminar').classList.add('hidden')"
          class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">Cancelar</button>
        <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-heading text-sm shadow transition">Eliminar</button>
      </form>
    </div>
  </div>
</div>

<script>
function aplicarFecha(val) {
  const url = new URL(window.location.href);
  val ? url.searchParams.set('fecha', val) : url.searchParams.delete('fecha');
  url.searchParams.set('pagina', 1);
  window.location.href = url.toString();
}

// ── Selección visual de mesa (admin) ─────────────────────────────────────────
function seleccionarMesaAdmin(btn, ctx) {
  if (btn.dataset.ocupada === '1') return;
  const container = document.getElementById('mesas_admin_' + ctx);
  container.querySelectorAll('.admin-mesa-btn').forEach(b => {
    b.classList.remove('border-retro-red', 'bg-red-50', 'admin-mesa-seleccionada');
  });
  btn.classList.add('border-retro-red', 'bg-red-50', 'admin-mesa-seleccionada');
  document.getElementById(ctx === 'crear' ? 'admin_crear_id_mesa' : 'admin_edit_id_mesa').value = btn.dataset.id;
  document.getElementById('admin_' + ctx + '_mesa_error').classList.add('hidden');
}

// ── Consultar disponibilidad vía AJAX (admin) ─────────────────────────────────
function actualizarDisponibilidadAdmin(ctx) {
  const fecha     = document.getElementById(ctx === 'crear' ? 'admin_crear_fecha' : 'edit_fecha').value;
  const hora      = document.getElementById(ctx === 'crear' ? 'admin_crear_hora'  : 'edit_hora').value;
  const reservaId  = ctx === 'editar' ? document.getElementById('edit_id').value : null;
  const mesaActual = ctx === 'editar' ? document.getElementById('admin_edit_id_mesa').value : null;

  if (!fecha || !hora) return;

  const container = document.getElementById('mesas_admin_' + ctx);

  const base = window.location.pathname.split('/views/')[0];
  fetch(`${base}/Controllers/ReservaDisponibilidadController.php?fecha=${encodeURIComponent(fecha)}&hora=${encodeURIComponent(hora)}&excluir=${reservaId || ''}`)
    .then(r => r.json())
    .then(data => {
      container.querySelectorAll('.admin-mesa-btn').forEach(btn => {
        const id   = btn.dataset.id;
        const info = data[id];
        const ocupada = info && info.ocupada == '1';
        btn.dataset.ocupada = ocupada ? '1' : '0';

        if (ocupada) {
          btn.classList.remove('border-gray-200','border-retro-red','bg-white','bg-red-50','admin-mesa-seleccionada','hover:border-retro-red','cursor-pointer');
          btn.classList.add('border-red-300','bg-red-50','cursor-not-allowed','opacity-70');
          btn.querySelector('i').className = 'fas fa-lock text-red-400 text-base';
          if (!btn.querySelector('.admin-ocupada-label')) {
            const sp = document.createElement('span');
            sp.className = 'admin-ocupada-label text-xs font-bold text-red-500';
            sp.textContent = 'Ocupada';
            btn.appendChild(sp);
          }
          if (ctx === 'editar' && mesaActual === id) document.getElementById('admin_edit_id_mesa').value = '';
          if (ctx === 'crear'  && document.getElementById('admin_crear_id_mesa').value === id) document.getElementById('admin_crear_id_mesa').value = '';
        } else {
          btn.classList.remove('border-red-300','cursor-not-allowed','opacity-70');
          btn.classList.add('border-gray-200','hover:border-retro-red','cursor-pointer');
          btn.querySelector('i').className = 'fas fa-chair text-gray-400 text-base';
          const lbl = btn.querySelector('.admin-ocupada-label');
          if (lbl) lbl.remove();
          btn.dataset.ocupada = '0';
          if (ctx === 'editar' && mesaActual === id) seleccionarMesaAdmin(btn, ctx);
        }
      });
    })
    .catch(() => {});
}

// ── Validación al enviar ──────────────────────────────────────────────────────
document.getElementById('formAdminCrear').addEventListener('submit', function(e) {
  if (!document.getElementById('admin_crear_id_mesa').value) {
    e.preventDefault();
    document.getElementById('admin_crear_mesa_error').classList.remove('hidden');
  }
});
document.getElementById('formAdminEditar').addEventListener('submit', function(e) {
  if (!document.getElementById('admin_edit_id_mesa').value) {
    e.preventDefault();
    document.getElementById('admin_editar_mesa_error').classList.remove('hidden');
  }
});

// ── Abrir modal editar ────────────────────────────────────────────────────────
function abrirEditar(r) {
  document.getElementById('edit_id').value           = r.id_reserva;
  document.getElementById('edit_personas').value     = r.numero_personas;
  document.getElementById('edit_fecha').value        = r.fecha_reserva;
  document.getElementById('edit_hora').value         = r.hora_reserva.substring(0,5);
  document.getElementById('admin_edit_id_mesa').value = r.id_mesa || '';

  const se = document.getElementById('edit_estado');
  for (let i = 0; i < se.options.length; i++) {
    if (se.options[i].value == r.id_estado_reserva) { se.selectedIndex = i; break; }
  }

  document.getElementById('modalEditar').classList.remove('hidden');

  // Cargar disponibilidad y marcar mesa actual
  const base = window.location.pathname.split('/views/')[0];
  fetch(`${base}/Controllers/ReservaDisponibilidadController.php?fecha=${encodeURIComponent(r.fecha_reserva)}&hora=${encodeURIComponent(r.hora_reserva.substring(0,5))}&excluir=${r.id_reserva}`)
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById('mesas_admin_editar');
      container.querySelectorAll('.admin-mesa-btn').forEach(btn => {
        const id   = btn.dataset.id;
        const info = data[id];
        const ocupada = info && info.ocupada == '1';
        btn.dataset.ocupada = ocupada ? '1' : '0';

        if (ocupada) {
          btn.classList.remove('border-gray-200','border-retro-red','bg-white','bg-red-50','admin-mesa-seleccionada','hover:border-retro-red','cursor-pointer');
          btn.classList.add('border-red-300','bg-red-50','cursor-not-allowed','opacity-70');
          btn.querySelector('i').className = 'fas fa-lock text-red-400 text-base';
          if (!btn.querySelector('.admin-ocupada-label')) {
            const sp = document.createElement('span');
            sp.className = 'admin-ocupada-label text-xs font-bold text-red-500';
            sp.textContent = 'Ocupada';
            btn.appendChild(sp);
          }
        } else {
          btn.classList.remove('border-red-300','cursor-not-allowed','opacity-70');
          btn.classList.add('border-gray-200','hover:border-retro-red','cursor-pointer');
          btn.querySelector('i').className = 'fas fa-chair text-gray-400 text-base';
          const lbl = btn.querySelector('.admin-ocupada-label');
          if (lbl) lbl.remove();
          if (id == r.id_mesa) seleccionarMesaAdmin(btn, 'editar');
        }
      });
    })
    .catch(() => {
      const container = document.getElementById('mesas_admin_editar');
      container.querySelectorAll('.admin-mesa-btn').forEach(btn => {
        if (btn.dataset.id == r.id_mesa) seleccionarMesaAdmin(btn, 'editar');
      });
    });
}

function abrirEliminar(id) {
  document.getElementById('del_id').value = id;
  document.getElementById('modalEliminar').classList.remove('hidden');
}

// Cargar disponibilidad al abrir el modal crear
document.querySelector('[onclick*="modalCrear"]')?.addEventListener('click', function() {
  setTimeout(() => actualizarDisponibilidadAdmin('crear'), 100);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
