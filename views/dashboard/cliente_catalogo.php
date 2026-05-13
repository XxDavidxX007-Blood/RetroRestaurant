<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [3,'3','cliente', 2, '2', 'empleado'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}
$usuario = $_SESSION['usuario'];
$titulo  = "CATÁLOGO";

require_once __DIR__ . '/../../config/database.php';
$db = (new database())->conectar();

$menu = $db->query("
    SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.imagen,
           cp.nombre_categoria AS categoria
    FROM producto p
    JOIN categoria_producto cp ON p.id_categoria = cp.id_categoria
    ORDER BY cp.nombre_categoria, p.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$categorias = $db->query("SELECT nombre_categoria FROM categoria_producto ORDER BY nombre_categoria")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
@keyframes fadeUp  { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes zoomIn  { from{opacity:0;transform:scale(.95)} to{opacity:1;transform:scale(1)} }

.prod-card {
  background:#fff; border-radius:16px; overflow:hidden;
  border:1px solid #f0f0f0;
  transition:transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s;
  animation:fadeUp .4s ease both;
}
.prod-card:hover { transform:translateY(-5px); box-shadow:0 12px 28px rgba(0,0,0,.1); }
.prod-card .img-area {
  height:160px; display:flex; align-items:center; justify-content:center;
  font-size:52px; background:linear-gradient(135deg,#1a1f2e,#2d3748);
  background-size: cover; background-position: center;
  transition:transform .3s;
  position: relative;
}
.prod-card:hover .img-area { transform:scale(1.05); }

.buy-btn {
  width:32px; height:32px; background:#eab308; border:none; border-radius:9px;
  font-size:18px; font-weight:700; color:#1a202c; cursor:pointer;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
  transition:transform .2s cubic-bezier(.34,1.56,.64,1), background .15s;
}
.buy-btn:hover  { background:#ca8a04; transform:scale(1.1); }
.buy-btn:active { transform:scale(.9); }

/* Modal de confirmación (Nuevo Diseño) */
.modal-bg {
  display:none; position:fixed; inset:0; z-index:500;
  background:rgba(0,0,0,.6); backdrop-filter: blur(4px);
  align-items:center; justify-content:center;
  padding: 1rem;
}
.modal-bg.show { display:flex; }
.modal-box {
  background:#fff; border-radius:24px; max-width:900px; width:100%; max-height:95vh; overflow-y:auto;
  animation:zoomIn .3s cubic-bezier(.34,1.56,.64,1);
  position: relative;
}

/* Custom Scrollbar */
.modal-box::-webkit-scrollbar { width: 6px; }
.modal-box::-webkit-scrollbar-track { background: transparent; }
.modal-box::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

/* Tipo de pedido cards */
.tipo-card {
  flex:1; padding:16px 8px; border-radius:12px; border:2px solid #f1f5f9;
  background:#fff; text-align:center; cursor:pointer; transition:all .2s;
  display:flex; flex-direction:column; align-items:center; gap:8px;
}
.tipo-card.active {
  border-color:#eab308; background:#fefce8;
}
.tipo-card i { font-size: 24px; color:#64748b; transition:color .2s; }
.tipo-card.active i { color:#ca8a04; }
.tipo-card span.title { font-weight:700; color:#334155; font-size:14px; }
.tipo-card span.desc { font-size:11px; color:#94a3b8; line-height:1.2; }
.tipo-card .check-icon { 
    width:20px; height:20px; background:#eab308; color:white; border-radius:50%; 
    display:flex; align-items:center; justify-content:center; font-size:10px; 
    opacity:0; transform:scale(0.5); transition:all .2s;
    margin-top: 4px;
}
.tipo-card.active .check-icon { opacity:1; transform:scale(1); }

/* Input fields */
.custom-input {
  width:100%; px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-body outline-none transition
}
.custom-input:focus { border-color:#eab308; box-shadow:0 0 0 3px rgba(234,179,8,0.1); }

/* Quantity Selector */
.qty-btn {
  width:40px; height:40px; border:1px solid #f1f5f9; background:#fff; color:#eab308;
  border-radius:12px; cursor:pointer; font-size:20px;
  display:flex; align-items:center; justify-content:center;
  transition:all .2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.qty-btn:hover { background:#fefce8; border-color:#eab308; }
.qty-btn:active { transform:scale(0.95); }

/* Summary Table */
.summary-table { width: 100%; text-align: left; font-size: 13px; }
.summary-table th { padding-bottom: 8px; color: #94a3b8; font-weight: 600; border-bottom: 1px solid #f1f5f9; }
.summary-table td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #334155; }
</style>

<div class="space-y-5 relative z-10">
  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
        <i class="fas fa-utensils text-retro-red"></i> Catálogo
      </h1>
      <p class="text-gray-500 font-body text-sm mt-1">Selecciona un producto para pedirlo.</p>
    </div>
    <div class="flex items-center gap-3">
      <div class="relative">
        <input type="text" id="buscar" placeholder="Buscar plato..."
          class="pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm font-body focus:outline-none focus:border-retro-red w-52"
          oninput="filtrar()">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
      </div>
      <select id="filtro" onchange="filtrar()"
        class="px-3 py-2 border border-gray-200 rounded-xl text-sm font-body focus:outline-none focus:border-retro-red bg-white">
        <option value="">Todas</option>
        <?php foreach ($categorias as $c): ?>
        <option value="<?= htmlspecialchars($c['nombre_categoria']) ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Grid -->
  <?php if (empty($menu)): ?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center text-gray-400">
    <i class="fas fa-utensils text-5xl mb-3"></i>
    <p class="font-heading text-lg">El menú está vacío por ahora.</p>
  </div>
  <?php else: ?>
  <div id="grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
    <?php
    $emojis = ['🍔','🍕','🥩','🍗','🥗','🍜','🥤','🍰','🍟','🥪','🌮','🍣','🥘','🍱','🧆'];
    foreach ($menu as $i => $item):
      $emoji = $emojis[$i % count($emojis)];
      $bgStyle = !empty($item['imagen']) ? "background-image: url('../../img/productos/".htmlspecialchars($item['imagen'])."');" : "";
    ?>
    <div class="prod-card item group"
         style="animation-delay:<?= $i * 0.04 ?>s"
         data-nombre="<?= strtolower(htmlspecialchars($item['nombre'])) ?>"
         data-categoria="<?= htmlspecialchars($item['categoria']) ?>">
      <div class="overflow-hidden rounded-t-2xl">
        <div class="img-area w-full relative" style="<?= $bgStyle ?>">
            <?php if (empty($item['imagen'])): ?>
                <span class="emoji"><?= $emoji ?></span>
            <?php endif; ?>
        </div>
      </div>
      <div class="p-4 bg-white relative z-10">
        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider bg-retro-red/10 text-retro-red">
          <?= htmlspecialchars($item['categoria']) ?>
        </span>
        <h3 class="font-heading font-bold text-gray-800 mt-3 text-base truncate" title="<?= htmlspecialchars($item['nombre']) ?>">
            <?= htmlspecialchars($item['nombre']) ?>
        </h3>
        <p class="text-xs text-gray-500 mt-1 line-clamp-2 h-8 leading-tight"><?= htmlspecialchars($item['descripcion'] ?? 'Delicioso plato preparado con los mejores ingredientes.') ?></p>
        <div class="flex items-center justify-between mt-4">
          <span class="font-heading font-bold text-lg text-gray-900">$<?= number_format($item['precio'],0,',','.') ?></span>
          <button class="buy-btn shadow-sm"
            onclick="abrirModal(<?= $item['id_producto'] ?>, '<?= addslashes(htmlspecialchars($item['nombre'])) ?>', <?= $item['precio'] ?>, '<?= $emoji ?>', '<?= addslashes(htmlspecialchars($item['categoria'])) ?>', '<?= addslashes(htmlspecialchars($item['descripcion'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($item['imagen'] ?? '')) ?>')"
            title="Pedir ahora"><i class="fas fa-plus text-sm"></i></button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div id="sin" class="hidden text-center py-12 text-gray-400">
    <i class="fas fa-search text-4xl mb-3"></i><p class="font-heading">Sin resultados</p>
  </div>
  <?php endif; ?>
</div>

<!-- ── NUEVO MODAL DE CONFIRMACIÓN (DISEÑO DOS COLUMNAS) ─────────────────────────────────── -->
<div id="modal-confirm" class="modal-bg" onclick="cerrarSiAfuera(event)">
  <div class="modal-box p-0 flex flex-col md:flex-row relative">
    
    <!-- Botón Cerrar Flotante (Móvil) -->
    <button onclick="cerrarModal()" class="md:hidden absolute top-4 right-4 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-gray-500 z-10">
        <i class="fas fa-times"></i>
    </button>

    <!-- COLUMNA IZQUIERDA -->
    <div class="w-full md:w-5/12 bg-gray-50/50 p-6 md:p-8 md:border-r border-gray-100 flex flex-col">
        <!-- Imagen -->
        <div class="relative w-full aspect-square rounded-2xl overflow-hidden shadow-sm bg-gray-200 mb-6 flex items-center justify-center" id="m-img-container">
            <!-- Corazón flotante -->
            <button class="absolute top-4 right-4 w-9 h-9 bg-white rounded-full shadow flex items-center justify-center text-gray-400 hover:text-red-500 transition z-10">
                <i class="far fa-heart"></i>
            </button>
            <div id="m-emoji" class="text-6xl hidden"></div>
        </div>

        <!-- Info Producto -->
        <div class="flex items-center gap-3 mb-2">
            <h2 id="m-nombre" class="text-2xl font-heading font-bold text-gray-900"></h2>
            <span id="m-categoria" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-600 uppercase tracking-wide"></span>
        </div>
        <p id="m-desc" class="text-sm text-gray-500 leading-relaxed mb-6"></p>

        <!-- Controles -->
        <div class="mt-auto space-y-6">
            <!-- Cantidad -->
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-3">Cantidad</label>
                <div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl p-2 shadow-sm max-w-[200px]">
                    <button class="qty-btn" onclick="cambiarQty(-1)"><i class="fas fa-minus text-sm"></i></button>
                    <div class="flex flex-col items-center">
                        <span id="m-qty" class="text-xl font-bold text-gray-900">1</span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase">Unidad</span>
                    </div>
                    <button class="qty-btn" onclick="cambiarQty(1)"><i class="fas fa-plus text-sm"></i></button>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="bg-[#FFF8F1] border border-[#FEEBC8] rounded-xl p-4">
                <label class="flex items-center gap-2 text-sm font-bold text-amber-800 mb-2">
                    <i class="fas fa-info-circle text-amber-500"></i> ¿Alguna observación?
                </label>
                <input type="text" id="m-observacion" placeholder="Ej: sin azúcar, extra frío, etc." 
                       class="w-full px-3 py-2 bg-white border border-amber-100 rounded-lg text-sm outline-none focus:border-amber-300">
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA -->
    <div class="w-full md:w-7/12 p-6 md:p-8 flex flex-col">
        <!-- Botón Cerrar (Desktop) -->
        <div class="hidden md:flex justify-end mb-4">
            <button onclick="cerrarModal()" class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:bg-gray-50 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="space-y-6 flex-1">
            <!-- Cliente -->
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-2">Cliente <span class="text-gray-400 font-normal">(opcional)</span></label>
                <div class="relative">
                    <i class="far fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="m-cliente" placeholder="Nombre del cliente" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm outline-none focus:border-retro-gold transition">
                </div>
            </div>

            <!-- Tipo de pedido -->
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-3">Tipo de pedido</label>
                <div class="flex gap-3 flex-wrap sm:flex-nowrap">
                    <!-- Mesa -->
                    <div class="tipo-card active" id="t-mesa" onclick="selTipo('mesa')">
                        <i class="fas fa-chair"></i>
                        <span class="title">Mesa</span>
                        <span class="desc">Para consumir en el restaurante</span>
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                    </div>
                    <!-- Domicilio -->
                    <div class="tipo-card" id="t-domicilio" onclick="selTipo('domicilio')">
                        <i class="fas fa-motorcycle"></i>
                        <span class="title">Domicilio</span>
                        <span class="desc">Enviado a la dirección del cliente</span>
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                    </div>
                    <!-- Llevar -->
                    <div class="tipo-card" id="t-llevar" onclick="selTipo('para llevar')">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="title">Llevar</span>
                        <span class="desc">El cliente recogerá el pedido</span>
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Dirección de entrega (Oculto por defecto) -->
            <div id="m-direccion-box" class="hidden bg-[#FFFBF0] border border-[#FDE68A] rounded-xl p-4 flex items-center justify-between cursor-pointer hover:bg-[#FEF9C3] transition">
                <div class="flex items-center gap-3">
                    <i class="fas fa-map-marker-alt text-amber-500 text-lg"></i>
                    <div>
                        <p class="text-sm font-bold text-gray-800">Dirección de entrega</p>
                        <p class="text-xs text-gray-500">Se solicitará al confirmar el pedido</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>

            <!-- Resumen del pedido -->
            <div class="border border-gray-100 rounded-xl p-5 bg-white shadow-sm">
                <h4 class="text-sm font-bold text-gray-800 mb-4">Resumen del pedido</h4>
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Precio unitario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="m-res-prod" class="font-medium text-gray-800"></td>
                            <td id="m-res-qty" class="text-center"></td>
                            <td id="m-res-pu" class="text-right"></td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                    <span class="font-bold text-gray-800 text-lg">Total</span>
                    <span id="m-total" class="font-bold text-2xl text-retro-gold"></span>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="mt-6 space-y-3">
            <button id="btn-confirmar" onclick="confirmarCompra()" class="w-full bg-[#d4a017] hover:bg-[#b5850b] text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 transition shadow-md">
                <i class="fas fa-shopping-bag"></i> Agregar y confirmar pedido
            </button>
            <button onclick="cerrarModal()" class="w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 transition">
                <i class="fas fa-plus text-xs"></i> Agregar más productos
            </button>
            <div class="text-center pt-2">
                <button onclick="cerrarModal()" class="text-sm text-gray-500 hover:text-gray-800 font-medium">Cancelar</button>
            </div>
        </div>

    </div>

    <!-- FOOTER INFO -->
    <div class="absolute -bottom-16 left-0 right-0 flex justify-center gap-8 hidden lg:flex">
        <div class="flex items-center gap-2 text-white">
            <i class="fas fa-shield-alt opacity-70"></i>
            <div>
                <p class="text-xs font-bold leading-tight">Pedido seguro</p>
                <p class="text-[10px] opacity-70">Tus pedidos están protegidos</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-white">
            <i class="far fa-clock opacity-70"></i>
            <div>
                <p class="text-xs font-bold leading-tight">Rápido y fácil</p>
                <p class="text-[10px] opacity-70">Atendemos tu pedido al instante</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-white">
            <i class="fas fa-headset opacity-70"></i>
            <div>
                <p class="text-xs font-bold leading-tight">¿Necesitas ayuda?</p>
                <p class="text-[10px] opacity-70">Pregunta al administrador</p>
            </div>
        </div>
    </div>

  </div>
</div>

<!-- ── MODAL ÉXITO ────────────────────────────────────────── -->
<div id="modal-ok" class="modal-bg z-[600]">
  <div class="bg-white rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl relative">
    <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
        <i class="fas fa-check"></i>
    </div>
    <h3 class="text-2xl font-heading font-bold text-gray-900 mb-2">¡Pedido realizado!</h3>
    <p id="ok-msg" class="text-sm text-gray-500 leading-relaxed mb-6"></p>
    <button onclick="cerrarOk()" class="w-full bg-[#d4a017] hover:bg-[#b5850b] text-white py-3 rounded-xl font-bold transition shadow">
      ¡Perfecto!
    </button>
  </div>
</div>

<script>
let productoActual = {};
let qtyActual      = 1;
let tipoActual     = 'mesa';

function abrirModal(id, nombre, precio, emoji, categoria, desc, img) {
  productoActual = { id_producto:id, nombre, precio, emoji, img };
  qtyActual      = 1;
  tipoActual     = 'mesa';

  // UI Updates Izquierda
  const imgContainer = document.getElementById('m-img-container');
  const emjEl = document.getElementById('m-emoji');
  if (img) {
      imgContainer.style.backgroundImage = `url('../../img/productos/${img}')`;
      imgContainer.style.backgroundSize = 'cover';
      imgContainer.style.backgroundPosition = 'center';
      emjEl.classList.add('hidden');
  } else {
      imgContainer.style.backgroundImage = 'none';
      emjEl.textContent = emoji;
      emjEl.classList.remove('hidden');
  }

  document.getElementById('m-nombre').textContent = nombre;
  document.getElementById('m-categoria').textContent = categoria;
  document.getElementById('m-desc').textContent = desc || 'Delicioso plato preparado con los mejores ingredientes.';
  document.getElementById('m-qty').textContent = 1;
  document.getElementById('m-observacion').value = '';
  document.getElementById('m-cliente').value = '';

  // UI Updates Derecha
  document.getElementById('m-res-prod').textContent = nombre;
  document.getElementById('m-res-qty').textContent = 1;
  document.getElementById('m-res-pu').textContent = '$' + precio.toLocaleString('es-CO');
  
  selTipo('mesa');
  actualizarTotal();

  document.getElementById('modal-confirm').classList.add('show');
}

function cerrarModal() {
  document.getElementById('modal-confirm').classList.remove('show');
}

function cerrarSiAfuera(e) {
  if (e.target === document.getElementById('modal-confirm')) cerrarModal();
}

function cambiarQty(delta) {
  qtyActual = Math.max(1, qtyActual + delta);
  document.getElementById('m-qty').textContent = qtyActual;
  document.getElementById('m-res-qty').textContent = qtyActual;
  actualizarTotal();
}

function actualizarTotal() {
  const t = productoActual.precio * qtyActual;
  document.getElementById('m-total').textContent = '$' + t.toLocaleString('es-CO');
}

function selTipo(t) {
  tipoActual = t;
  const map = { mesa:'t-mesa', domicilio:'t-domicilio', 'para llevar':'t-llevar' };
  
  document.querySelectorAll('.tipo-card').forEach(b => b.classList.remove('active'));
  document.getElementById(map[t]).classList.add('active');

  const dirBox = document.getElementById('m-direccion-box');
  if (t === 'domicilio') {
      dirBox.classList.remove('hidden');
  } else {
      dirBox.classList.add('hidden');
  }
}

async function confirmarCompra() {
  const btn = document.getElementById('btn-confirmar');
  const obs = document.getElementById('m-observacion').value;
  const cli = document.getElementById('m-cliente').value;

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

  try {
    const res = await fetch('../../Controllers/ProcesarCompra.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        carrito: [{
          id_producto: productoActual.id_producto,
          nombre:      productoActual.nombre,
          precio:      productoActual.precio,
          cantidad:    qtyActual,
          observacion: obs
        }],
        tipo: tipoActual,
        cliente_manual: cli
      })
    });
    const data = await res.json();

    cerrarModal();

    if (data.ok) {
      const num = String(data.id_pedido).padStart(5, '0');
      document.getElementById('ok-msg').innerHTML =
        `Tu pedido <strong class="text-gray-800">#ORD-${num}</strong> fue procesado.<br>
         Total a pagar: <strong class="text-retro-gold">$${Number(data.total).toLocaleString('es-CO')}</strong><br>
         ¡Pronto estará listo!`;
      document.getElementById('modal-ok').classList.add('show');
    } else {
      alert('Error: ' + (data.error || 'No se pudo procesar el pedido.'));
    }
  } catch(e) {
    alert('Error de conexión. Intenta de nuevo.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-shopping-bag"></i> Agregar y confirmar pedido';
  }
}

function cerrarOk() {
  document.getElementById('modal-ok').classList.remove('show');
}

function filtrar() {
  const t = document.getElementById('buscar').value.toLowerCase();
  const c = document.getElementById('filtro').value;
  let v = 0;
  document.querySelectorAll('.item').forEach(el => {
    const ok = el.dataset.nombre.includes(t) && (c === '' || el.dataset.categoria === c);
    el.style.display = ok ? '' : 'none';
    if (ok) v++;
  });
  document.getElementById('sin').classList.toggle('hidden', v > 0);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
