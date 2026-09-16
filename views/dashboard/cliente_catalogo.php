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

$cols = $db->query("SHOW COLUMNS FROM producto")->fetchAll(PDO::FETCH_COLUMN);
$tieneEsMenu = in_array('es_menu', $cols);
$whereMenu   = $tieneEsMenu ? "WHERE p.es_menu = 1 AND p.disponible = 1" : "WHERE p.disponible = 1";

$menu = $db->query("
    SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.imagen,
           cp.nombre_categoria AS categoria
    FROM producto p
    JOIN categoria_producto cp ON p.id_categoria = cp.id_categoria
    {$whereMenu}
    ORDER BY cp.nombre_categoria, p.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$categorias = $db->query("
    SELECT DISTINCT cp.nombre_categoria
    FROM categoria_producto cp
    JOIN producto p ON p.id_categoria = cp.id_categoria
    " . ($tieneEsMenu ? "WHERE p.es_menu = 1 AND p.disponible = 1" : "WHERE p.disponible = 1") . "
    ORDER BY cp.nombre_categoria
")->fetchAll(PDO::FETCH_ASSOC);

// Mesas para selector
try {
    $mesasCat = $db->query("SELECT id_mesa, numero_mesa, capacidad FROM mesa ORDER BY numero_mesa")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $mesasCat = []; }

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
@keyframes fadeUp  { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes zoomIn  { from{opacity:0;transform:scale(.95)} to{opacity:1;transform:scale(1)} }
@keyframes slideIn { from{opacity:0;transform:translateX(100%)} to{opacity:1;transform:translateX(0)} }

/* ── Tarjetas de producto ── */
.prod-card {
  background:#fff; border-radius:16px; overflow:hidden; border:1px solid #f0f0f0;
  transition:transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s;
  animation:fadeUp .4s ease both;
}
.prod-card:hover { transform:translateY(-5px); box-shadow:0 12px 28px rgba(0,0,0,.1); }
.prod-card .img-area {
  height:160px; display:flex; align-items:center; justify-content:center;
  font-size:52px; background:linear-gradient(135deg,#1a1f2e,#2d3748);
  background-size:cover; background-position:center; transition:transform .3s; position:relative;
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

/* ── Modal de detalle de producto ── */
.modal-bg { display:none; position:fixed; inset:0; z-index:500; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:1rem; }
.modal-bg.show { display:flex; }
.modal-box { background:#fff; border-radius:24px; max-width:680px; width:100%; max-height:95vh; overflow-y:auto; animation:zoomIn .3s cubic-bezier(.34,1.56,.64,1); position:relative; }
.modal-box::-webkit-scrollbar { width:6px; }
.modal-box::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:10px; }

/* ── Tipo de pedido cards ── */
.tipo-card { flex:1; padding:12px 8px; border-radius:12px; border:2px solid #f1f5f9; background:#fff; text-align:center; cursor:pointer; transition:all .2s; display:flex; flex-direction:column; align-items:center; gap:6px; }
.tipo-card.active { border-color:#eab308; background:#fefce8; }
.tipo-card i { font-size:20px; color:#64748b; transition:color .2s; }
.tipo-card.active i { color:#ca8a04; }
.tipo-card span.title { font-weight:700; color:#334155; font-size:13px; }
.tipo-card span.desc  { font-size:10px; color:#94a3b8; line-height:1.2; }
.tipo-card .check-icon { width:18px; height:18px; background:#eab308; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:9px; opacity:0; transform:scale(.5); transition:all .2s; }
.tipo-card.active .check-icon { opacity:1; transform:scale(1); }

/* ── Qty buttons ── */
.qty-btn { width:36px; height:36px; border:1px solid #f1f5f9; background:#fff; color:#eab308; border-radius:10px; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all .2s; }
.qty-btn:hover { background:#fefce8; border-color:#eab308; }

/* ── Carrito lateral (drawer) ── */
#cart-drawer {
  position:fixed; top:0; right:0; height:100vh; width:420px; max-width:100vw;
  background:#fff; box-shadow:-4px 0 32px rgba(0,0,0,.15);
  z-index:700; display:flex; flex-direction:column;
  transform:translateX(100%); transition:transform .35s cubic-bezier(.4,0,.2,1);
}
#cart-drawer.open { transform:translateX(0); }
#cart-overlay { display:none; position:fixed; inset:0; z-index:699; background:rgba(0,0,0,.4); backdrop-filter:blur(2px); }
#cart-overlay.open { display:block; }

/* ── Badge del carrito flotante ── */
#cart-fab {
  position:fixed; bottom:28px; right:28px; z-index:650;
  width:60px; height:60px; border-radius:50%; background:#d4a017;
  box-shadow:0 6px 24px rgba(212,160,23,.5);
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; transition:transform .2s, box-shadow .2s;
  border:none;
}
#cart-fab:hover { transform:scale(1.08); box-shadow:0 10px 30px rgba(212,160,23,.6); }
#cart-fab.hidden-fab { display:none; }
#cart-count {
  position:absolute; top:-4px; right:-4px;
  background:#dc2626; color:#fff; font-size:11px; font-weight:800;
  width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center;
  border:2px solid #fff;
}

/* ── Mesa pick ── */
.mesa-pick-btn { transition:all .18s; }
.mesa-pick-btn.selected { border-color:#d4a017 !important; background:#fefce8 !important; }
.mesa-pick-btn.selected i { color:#d4a017 !important; }

/* ── Línea del carrito ── */
.cart-line { display:flex; align-items:center; gap:12px; padding:14px 0; border-bottom:1px solid #f3f4f6; }
.cart-line:last-child { border-bottom:none; }
</style>

<div class="space-y-5 relative z-10">
  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
        <i class="fas fa-utensils text-retro-red"></i> Catálogo
      </h1>
      <p class="text-gray-500 font-body text-sm mt-1">Selecciona los productos que deseas pedir.</p>
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
      <!-- Botón ver carrito en header -->
      <button onclick="abrirCarrito()" class="relative flex items-center gap-2 bg-[#d4a017] hover:bg-[#b5850b] text-white px-4 py-2 rounded-xl font-bold text-sm transition shadow" id="btn-header-cart" style="display:none;">
        <i class="fas fa-shopping-cart"></i>
        <span>Carrito</span>
        <span id="header-cart-count" class="bg-white text-[#d4a017] text-xs font-black w-5 h-5 rounded-full flex items-center justify-center">0</span>
      </button>
    </div>
  </div>

  <!-- Grid de productos -->
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
      $bgStyle = !empty($item['imagen']) ? "background-image: url('../../img/menu/".htmlspecialchars($item['imagen'])."');" : "";
    ?>
    <div class="prod-card item group"
         style="animation-delay:<?= $i * 0.04 ?>s"
         data-nombre="<?= strtolower(htmlspecialchars($item['nombre'])) ?>"
         data-categoria="<?= htmlspecialchars($item['categoria']) ?>">
      <div class="overflow-hidden rounded-t-2xl">
        <div class="img-area w-full relative" style="<?= $bgStyle ?>">
          <?php if (empty($item['imagen'])): ?><span class="emoji"><?= $emoji ?></span><?php endif; ?>
        </div>
      </div>
      <div class="p-4 bg-white relative z-10">
        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider bg-retro-red/10 text-retro-red">
          <?= htmlspecialchars($item['categoria']) ?>
        </span>
        <h3 class="font-heading font-bold text-gray-800 mt-3 text-base truncate" title="<?= htmlspecialchars($item['nombre']) ?>">
          <?= htmlspecialchars($item['nombre']) ?>
        </h3>
        <p class="text-xs text-gray-500 mt-1 line-clamp-2 h-8 leading-tight"><?= htmlspecialchars($item['descripcion'] ?? '') ?></p>
        <div class="flex items-center justify-between mt-4">
          <span class="font-heading font-bold text-lg text-gray-900">$<?= number_format($item['precio'],0,',','.') ?></span>
          <button class="buy-btn shadow-sm"
            onclick="abrirModal(<?= $item['id_producto'] ?>, '<?= addslashes(htmlspecialchars($item['nombre'])) ?>', <?= $item['precio'] ?>, '<?= $emoji ?>', '<?= addslashes(htmlspecialchars($item['categoria'])) ?>', '<?= addslashes(htmlspecialchars($item['descripcion'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($item['imagen'] ?? '')) ?>')"
            title="Agregar al pedido"><i class="fas fa-plus text-sm"></i></button>
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

<!-- ══════════════════════════════════════════════════════
     MODAL DETALLE PRODUCTO — solo para configurar el ítem
════════════════════════════════════════════════════════ -->
<div id="modal-confirm" class="modal-bg" onclick="cerrarSiAfuera(event)">
  <div class="modal-box p-0 flex flex-col md:flex-row relative" style="max-width:620px;">

    <button onclick="cerrarModal()" class="md:hidden absolute top-4 right-4 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-gray-500 z-10">
      <i class="fas fa-times"></i>
    </button>

    <!-- Columna izquierda: imagen + cantidad + observación -->
    <div class="w-full md:w-5/12 bg-gray-50 p-6 flex flex-col md:border-r border-gray-100">
      <div class="relative w-full aspect-square rounded-2xl overflow-hidden shadow-sm bg-gray-200 mb-5 flex items-center justify-center" id="m-img-container">
        <div id="m-emoji" class="text-6xl hidden"></div>
      </div>
      <div class="flex items-start gap-2 mb-1">
        <h2 id="m-nombre" class="text-xl font-heading font-bold text-gray-900 flex-1"></h2>
        <span id="m-categoria" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-600 uppercase tracking-wide flex-shrink-0 mt-1"></span>
      </div>
      <p id="m-desc" class="text-xs text-gray-500 leading-relaxed mb-5"></p>
      <div class="mt-auto space-y-4">
        <div>
          <label class="block text-sm font-bold text-gray-800 mb-2">Cantidad</label>
          <div class="flex items-center gap-4 bg-white border border-gray-100 rounded-xl p-2 shadow-sm w-fit">
            <button class="qty-btn" onclick="cambiarQty(-1)"><i class="fas fa-minus text-xs"></i></button>
            <span id="m-qty" class="text-xl font-bold text-gray-900 w-6 text-center">1</span>
            <button class="qty-btn" onclick="cambiarQty(1)"><i class="fas fa-plus text-xs"></i></button>
          </div>
        </div>
        <div class="bg-amber-50 border border-amber-100 rounded-xl p-3">
          <label class="flex items-center gap-1 text-xs font-bold text-amber-800 mb-1"><i class="fas fa-info-circle text-amber-500"></i> Observación</label>
          <input type="text" id="m-observacion" placeholder="Ej: sin azúcar, extra frío…"
            class="w-full px-3 py-2 bg-white border border-amber-100 rounded-lg text-xs outline-none focus:border-amber-300">
        </div>
      </div>
    </div>

    <!-- Columna derecha: precio + botones -->
    <div class="w-full md:w-7/12 p-6 flex flex-col">
      <div class="hidden md:flex justify-end mb-3">
        <button onclick="cerrarModal()" class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:bg-gray-50 transition">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Resumen del ítem actual -->
      <div class="flex-1 flex flex-col justify-center">
        <div class="bg-gray-50 rounded-2xl p-5 mb-5">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-500">Producto</span>
            <span id="m-res-prod" class="font-bold text-gray-800 text-sm text-right max-w-[160px] truncate"></span>
          </div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-500">Precio unitario</span>
            <span id="m-res-pu" class="font-bold text-gray-800 text-sm"></span>
          </div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-500">Cantidad</span>
            <span id="m-res-qty" class="font-bold text-gray-800 text-sm"></span>
          </div>
          <div class="border-t border-gray-200 pt-3 mt-3 flex items-center justify-between">
            <span class="font-bold text-gray-800">Subtotal</span>
            <span id="m-subtotal" class="font-heading font-bold text-2xl text-[#d4a017]"></span>
          </div>
        </div>

        <!-- Carrito actual (previa) -->
        <div id="m-carrito-prev" class="hidden mb-4">
          <p class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Ya en tu carrito</p>
          <div id="m-carrito-items" class="space-y-1 max-h-28 overflow-y-auto"></div>
          <div class="flex items-center justify-between pt-2 border-t border-gray-100 mt-2">
            <span class="text-xs font-bold text-gray-500">Total acumulado</span>
            <span id="m-total-acum" class="text-sm font-bold text-[#d4a017]"></span>
          </div>
        </div>
      </div>

      <!-- Botones -->
      <div class="space-y-3 mt-4">
        <button id="btn-agregar-carrito" onclick="agregarAlCarrito()"
          class="w-full bg-[#d4a017] hover:bg-[#b5850b] text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 transition shadow-md text-sm">
          <i class="fas fa-cart-plus"></i> Agregar al carrito
        </button>
        <button onclick="agregarYVerCarrito()"
          class="w-full bg-gray-900 hover:bg-gray-700 text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 transition text-sm">
          <i class="fas fa-shopping-bag"></i> Agregar y ver pedido
        </button>
        <button onclick="cerrarModal()"
          class="w-full text-center text-sm text-gray-400 hover:text-gray-600 py-2 transition">
          Cancelar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════
     CARRITO LATERAL (DRAWER)
════════════════════════════════════════════════════════ -->
<div id="cart-overlay" onclick="cerrarCarrito()"></div>

<div id="cart-drawer">
  <!-- Header del carrito -->
  <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50 flex-shrink-0">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#d4a017] flex items-center justify-center">
        <i class="fas fa-shopping-cart text-white text-sm"></i>
      </div>
      <div>
        <h3 class="font-heading font-bold text-gray-900 text-base">Tu pedido</h3>
        <p id="cart-subtitle" class="text-xs text-gray-400"></p>
      </div>
    </div>
    <button onclick="cerrarCarrito()" class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:bg-gray-100 transition">
      <i class="fas fa-times text-xs"></i>
    </button>
  </div>

  <!-- Configuración: tipo + mesa -->
  <div class="px-5 py-4 border-b border-gray-100 flex-shrink-0 space-y-4">
    <!-- Tipo de pedido -->
    <div>
      <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tipo de pedido</p>
      <div class="flex gap-2">
        <div class="tipo-card active" id="t-mesa" onclick="selTipo('mesa')" style="padding:10px 6px;">
          <i class="fas fa-chair" style="font-size:16px;"></i>
          <span class="title" style="font-size:11px;">Mesa</span>
          <div class="check-icon"><i class="fas fa-check"></i></div>
        </div>
        <div class="tipo-card" id="t-domicilio" onclick="selTipo('domicilio')" style="padding:10px 6px;">
          <i class="fas fa-motorcycle" style="font-size:16px;"></i>
          <span class="title" style="font-size:11px;">Domicilio</span>
          <div class="check-icon"><i class="fas fa-check"></i></div>
        </div>
        <div class="tipo-card" id="t-llevar" onclick="selTipo('para llevar')" style="padding:10px 6px;">
          <i class="fas fa-shopping-bag" style="font-size:16px;"></i>
          <span class="title" style="font-size:11px;">Llevar</span>
          <div class="check-icon"><i class="fas fa-check"></i></div>
        </div>
      </div>
    </div>

    <!-- Selector de mesa -->
    <div id="cart-mesa-box">
      <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Mesa <span class="text-red-500">*</span></p>
      <div class="grid grid-cols-5 gap-1.5">
        <?php foreach ($mesasCat as $mc): ?>
        <button type="button" data-id="<?= $mc['id_mesa'] ?>" onclick="selMesa(this)"
          class="mesa-pick-btn flex flex-col items-center justify-center gap-0.5 p-2 rounded-lg border-2 border-gray-200 bg-white hover:border-[#d4a017] text-center cursor-pointer">
          <i class="fas fa-chair text-gray-400 text-xs"></i>
          <span class="font-bold text-gray-700 text-[10px]"><?= $mc['numero_mesa'] ?></span>
          <span class="text-[9px] text-gray-400">Cap.<?= $mc['capacidad'] ?></span>
        </button>
        <?php endforeach; ?>
      </div>
      <p id="cart-mesa-error" class="text-red-500 text-xs mt-1 hidden">Selecciona una mesa.</p>
    </div>

    <!-- Dirección (domicilio) -->
    <div id="cart-dir-box" class="hidden space-y-2">
      <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">
        Dirección de entrega <span class="text-red-500">*</span>
      </p>
      <div class="relative">
        <i class="fas fa-map-marker-alt absolute left-3 top-1/2 -translate-y-1/2 text-amber-500 text-sm"></i>
        <input type="text" id="cart-direccion"
          placeholder="Ej: Calle 45 #12-30, Apto 201…"
          class="w-full pl-9 pr-4 py-2.5 border-2 border-amber-200 bg-amber-50 rounded-xl text-sm outline-none focus:border-amber-400 transition font-body placeholder-gray-400">
      </div>
      <p id="cart-dir-error" class="text-red-500 text-xs hidden">Ingresa la dirección de entrega.</p>
    </div>
  </div>

  <!-- Lista de ítems del carrito -->
  <div class="flex-1 overflow-y-auto px-5 py-3" id="cart-items-list">
    <!-- se llena con JS -->
    <div id="cart-empty" class="flex flex-col items-center justify-center h-full py-10 text-gray-300">
      <i class="fas fa-shopping-cart text-5xl mb-3"></i>
      <p class="text-sm font-bold">Tu carrito está vacío</p>
      <p class="text-xs mt-1">Agrega productos desde el catálogo</p>
    </div>
  </div>

  <!-- Footer del carrito: totales + confirmar -->
  <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0 space-y-3">
    <!-- Desglose -->
    <div id="cart-resumen" class="hidden space-y-1.5">
      <div class="flex justify-between text-sm text-gray-500">
        <span>Subtotal</span><span id="cart-subtotal-val" class="font-semibold text-gray-700"></span>
      </div>
      <div class="flex justify-between text-sm text-gray-500">
        <span>Ítems</span><span id="cart-items-count" class="font-semibold text-gray-700"></span>
      </div>
      <div class="flex justify-between font-heading font-bold text-base text-gray-900 border-t border-gray-200 pt-2 mt-1">
        <span>Total</span><span id="cart-total-val" class="text-[#d4a017] text-xl"></span>
      </div>
    </div>
    <button id="btn-confirmar-pedido" onclick="confirmarCompra()"
      class="w-full bg-[#d4a017] hover:bg-[#b5850b] text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
      <i class="fas fa-lock text-sm"></i> Confirmar pedido
    </button>
    <button onclick="vaciarCarrito()"
      class="w-full text-center text-xs text-gray-400 hover:text-red-500 transition py-1">
      <i class="fas fa-trash-alt mr-1"></i> Vaciar carrito
    </button>
  </div>
</div>

<!-- FAB carrito flotante -->
<button id="cart-fab" class="hidden-fab" onclick="abrirCarrito()" title="Ver carrito">
  <i class="fas fa-shopping-cart text-white text-xl"></i>
  <span id="cart-count">0</span>
</button>

<!-- ── MODAL ÉXITO ── -->
<div id="modal-ok" class="modal-bg z-[800]">
  <div class="bg-white rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl">
    <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-5">
      <i class="fas fa-check"></i>
    </div>
    <h3 class="text-2xl font-heading font-bold text-gray-900 mb-2">¡Pedido realizado!</h3>
    <p id="ok-msg" class="text-sm text-gray-500 leading-relaxed mb-3"></p>
    <!-- Factura del pedido -->
    <div id="ok-factura" class="text-left bg-gray-50 rounded-xl p-4 mb-5 text-sm hidden">
      <p class="font-bold text-gray-700 mb-2 flex items-center gap-1"><i class="fas fa-receipt text-[#d4a017]"></i> Detalle del pedido</p>
      <div id="ok-factura-items" class="space-y-1 text-xs text-gray-600 mb-2"></div>
      <div class="border-t border-gray-200 pt-2 flex justify-between font-bold text-gray-900">
        <span>Total</span><span id="ok-factura-total" class="text-[#d4a017]"></span>
      </div>
    </div>
    <button onclick="cerrarOk()" class="w-full bg-[#d4a017] hover:bg-[#b5850b] text-white py-3 rounded-xl font-bold transition shadow">
      ¡Perfecto!
    </button>
  </div>
</div>

<script>
// ── Mover elementos fixed al body para evitar clipping por overflow del layout
document.addEventListener('DOMContentLoaded', function () {
  ['cart-drawer','cart-overlay','cart-fab','modal-ok','modal-confirm'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el && el.parentNode !== document.body) document.body.appendChild(el);
  });
});

// ── Helper seguro ─────────────────────────────────────────────
function $(id) { return document.getElementById(id); }

// ── Estado global ─────────────────────────────────────────────
let productoActual = {};
let qtyActual      = 1;
let tipoActual     = 'mesa';
let mesaActual     = null;
let carrito        = [];

// ── Abrir modal de detalle del producto ──────────────────────
function abrirModal(id, nombre, precio, emoji, categoria, desc, img) {
  productoActual = { id_producto: id, nombre, precio, emoji, img };
  qtyActual = 1;

  const imgC = $('m-img-container');
  const emj  = $('m-emoji');
  if (img) {
    imgC.style.backgroundImage    = `url('../../img/menu/${img}')`;
    imgC.style.backgroundSize     = 'cover';
    imgC.style.backgroundPosition = 'center';
    emj.classList.add('hidden');
  } else {
    imgC.style.backgroundImage = 'none';
    emj.textContent = emoji;
    emj.classList.remove('hidden');
  }

  $('m-nombre').textContent    = nombre;
  $('m-categoria').textContent = categoria;
  $('m-desc').textContent      = desc || 'Delicioso plato preparado con los mejores ingredientes.';
  $('m-qty').textContent       = 1;
  $('m-observacion').value     = '';
  $('m-res-prod').textContent  = nombre;
  $('m-res-pu').textContent    = '$' + precio.toLocaleString('es-CO');
  $('m-res-qty').textContent   = 1;
  $('m-subtotal').textContent  = '$' + precio.toLocaleString('es-CO');

  renderPrevCarrito();
  $('modal-confirm').classList.add('show');
}

function cerrarModal() {
  $('modal-confirm').classList.remove('show');
}

function cerrarSiAfuera(e) {
  if (e.target === $('modal-confirm')) cerrarModal();
}

function cambiarQty(delta) {
  qtyActual = Math.max(1, qtyActual + delta);
  $('m-qty').textContent      = qtyActual;
  $('m-res-qty').textContent  = qtyActual;
  $('m-subtotal').textContent = '$' + (productoActual.precio * qtyActual).toLocaleString('es-CO');
}

// Preview del carrito dentro del modal
function renderPrevCarrito() {
  const prev = $('m-carrito-prev');
  const list = $('m-carrito-items');
  if (!prev || !list) return;
  if (carrito.length === 0) { prev.classList.add('hidden'); return; }
  prev.classList.remove('hidden');
  list.innerHTML = carrito.map(it =>
    `<div class="flex justify-between text-xs text-gray-600">
       <span class="truncate max-w-[180px]">${it.nombre} ×${it.cantidad}</span>
       <span class="font-bold ml-2 flex-shrink-0">$${(it.precio * it.cantidad).toLocaleString('es-CO')}</span>
     </div>`
  ).join('');
  const acum = carrito.reduce((s, it) => s + it.precio * it.cantidad, 0);
  const el = $('m-total-acum');
  if (el) el.textContent = '$' + acum.toLocaleString('es-CO');
}

// ── Agregar al carrito ────────────────────────────────────────
function agregarAlCarrito() {
  const obs = $('m-observacion').value.trim();
  const idx = carrito.findIndex(it =>
    it.id_producto === productoActual.id_producto && it.observacion === obs
  );
  if (idx >= 0) {
    carrito[idx].cantidad += qtyActual;
  } else {
    carrito.push({ ...productoActual, cantidad: qtyActual, observacion: obs });
  }
  cerrarModal();
  actualizarFAB();

  // Animación del FAB
  const fab = $('cart-fab');
  if (fab) {
    fab.style.transform = 'scale(1.3)';
    setTimeout(() => { fab.style.transform = ''; }, 300);
  }
}

function agregarYVerCarrito() {
  agregarAlCarrito();
  setTimeout(() => abrirCarrito(), 150);
}

// ── FAB y badge ───────────────────────────────────────────────
function actualizarFAB() {
  const totalUds  = carrito.reduce((s, it) => s + it.cantidad, 0);
  const fab       = $('cart-fab');
  const badge     = $('cart-count');
  const btnHdr    = $('btn-header-cart');
  const badgeHdr  = $('header-cart-count');

  if (badge)    badge.textContent    = totalUds;
  if (badgeHdr) badgeHdr.textContent = totalUds;

  if (totalUds > 0) {
    if (fab)    fab.classList.remove('hidden-fab');
    if (btnHdr) btnHdr.style.display = 'flex';
  } else {
    if (fab)    fab.classList.add('hidden-fab');
    if (btnHdr) btnHdr.style.display = 'none';
  }
}

// ── Carrito drawer ────────────────────────────────────────────
function abrirCarrito() {
  const drawer  = $('cart-drawer');
  const overlay = $('cart-overlay');
  if (drawer)  drawer.classList.add('open');
  if (overlay) overlay.classList.add('open');
  renderCartDrawer();
}

function cerrarCarrito() {
  const drawer  = $('cart-drawer');
  const overlay = $('cart-overlay');
  if (drawer)  drawer.classList.remove('open');
  if (overlay) overlay.classList.remove('open');
}

function renderCartDrawer() {
  const list     = $('cart-items-list');
  const resumen  = $('cart-resumen');
  const subtitle = $('cart-subtitle');
  if (!list) return;

  // Limpiar y renderizar desde cero (nunca mover nodos del DOM)
  list.innerHTML = '';

  if (carrito.length === 0) {
    list.innerHTML = `
      <div class="flex flex-col items-center justify-center h-full py-10 text-gray-300">
        <i class="fas fa-shopping-cart text-5xl mb-3"></i>
        <p class="text-sm font-bold">Tu carrito está vacío</p>
        <p class="text-xs mt-1">Agrega productos desde el catálogo</p>
      </div>`;
    if (resumen)  resumen.classList.add('hidden');
    if (subtitle) subtitle.textContent = 'Vacío';
    return;
  }

  carrito.forEach((it, idx) => {
    const div = document.createElement('div');
    div.className = 'cart-line';
    const imgHtml = it.img
      ? `<img src="../../img/menu/${it.img}" class="w-full h-full object-cover rounded-xl" onerror="this.parentNode.innerHTML='${it.emoji}'">`
      : it.emoji;
    div.innerHTML = `
      <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-lg flex-shrink-0 overflow-hidden">${imgHtml}</div>
      <div class="flex-1 min-w-0">
        <p class="font-bold text-gray-800 text-sm truncate">${it.nombre}</p>
        ${it.observacion ? `<p class="text-[10px] text-amber-600 truncate italic">${it.observacion}</p>` : ''}
        <p class="text-[#d4a017] font-bold text-sm">$${(it.precio * it.cantidad).toLocaleString('es-CO')}</p>
      </div>
      <div class="flex items-center gap-1 flex-shrink-0">
        <button onclick="cambiarCantCarrito(${idx},-1)"
          class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-red-50 hover:text-red-500 transition text-xs">
          <i class="fas fa-minus"></i>
        </button>
        <span class="w-6 text-center font-bold text-sm text-gray-800">${it.cantidad}</span>
        <button onclick="cambiarCantCarrito(${idx},1)"
          class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-green-50 hover:text-green-500 transition text-xs">
          <i class="fas fa-plus"></i>
        </button>
        <button onclick="eliminarDelCarrito(${idx})"
          class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition text-xs ml-1">
          <i class="fas fa-trash"></i>
        </button>
      </div>`;
    list.appendChild(div);
  });

  const totalUds = carrito.reduce((s, it) => s + it.cantidad, 0);
  const totalVal = carrito.reduce((s, it) => s + it.precio * it.cantidad, 0);

  if (resumen) {
    resumen.classList.remove('hidden');
    const sv = $('cart-subtotal-val');
    const ic = $('cart-items-count');
    const tv = $('cart-total-val');
    if (sv) sv.textContent = '$' + totalVal.toLocaleString('es-CO');
    if (ic) ic.textContent = totalUds + ' unidad' + (totalUds > 1 ? 'es' : '');
    if (tv) tv.textContent = '$' + totalVal.toLocaleString('es-CO');
  }
  if (subtitle) {
    subtitle.textContent = `${totalUds} ítem${totalUds > 1 ? 's' : ''} · $${totalVal.toLocaleString('es-CO')}`;
  }
}

function cambiarCantCarrito(idx, delta) {
  if (carrito[idx].cantidad + delta < 1) { eliminarDelCarrito(idx); return; }
  carrito[idx].cantidad += delta;
  actualizarFAB();
  renderCartDrawer();
}

function eliminarDelCarrito(idx) {
  carrito.splice(idx, 1);
  actualizarFAB();
  renderCartDrawer();
}

function vaciarCarrito() {
  if (carrito.length === 0) return;
  if (!confirm('¿Vaciar el carrito?')) return;
  carrito = [];
  actualizarFAB();
  renderCartDrawer();
}

// ── Tipo de pedido ────────────────────────────────────────────
function selTipo(t) {
  tipoActual = t;
  const map  = { mesa: 't-mesa', domicilio: 't-domicilio', 'para llevar': 't-llevar' };
  document.querySelectorAll('.tipo-card').forEach(b => b.classList.remove('active'));
  const card = $(map[t]);
  if (card) card.classList.add('active');

  const mesaBox = $('cart-mesa-box');
  const dirBox  = $('cart-dir-box');
  if (mesaBox) mesaBox.style.display = t === 'mesa' ? '' : 'none';
  if (dirBox)  dirBox.classList.toggle('hidden', t !== 'domicilio');

  if (t !== 'mesa') {
    mesaActual = null;
    document.querySelectorAll('.mesa-pick-btn').forEach(b => b.classList.remove('selected'));
  }
}

// ── Selector de mesa ─────────────────────────────────────────
function selMesa(btn) {
  document.querySelectorAll('.mesa-pick-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  mesaActual = btn.dataset.id;
  const err = $('cart-mesa-error');
  if (err) err.classList.add('hidden');
}

// ── Confirmar pedido ─────────────────────────────────────────
async function confirmarCompra() {
  if (carrito.length === 0) { alert('Agrega al menos un producto al carrito.'); return; }
  if (tipoActual === 'mesa' && !mesaActual) {
    const err = $('cart-mesa-error');
    if (err) err.classList.remove('hidden');
    return;
  }
  // Validar dirección en domicilio
  const direccionInput = $('cart-direccion');
  const direccion = direccionInput ? direccionInput.value.trim() : '';
  if (tipoActual === 'domicilio' && !direccion) {
    const err = $('cart-dir-error');
    if (err) err.classList.remove('hidden');
    if (direccionInput) direccionInput.focus();
    return;
  }

  const btn = $('btn-confirmar-pedido');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando…';

  // Snapshot antes de limpiar
  const carritoSnap = carrito.map(it => ({
    id_producto: it.id_producto,
    nombre:      it.nombre,
    precio:      it.precio,
    cantidad:    it.cantidad,
    observacion: it.observacion || ''
  }));

  try {
    const res = await fetch('../../Controllers/ProcesarCompra.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        carrito:   carritoSnap,
        tipo:      tipoActual,
        id_mesa:   mesaActual ? parseInt(mesaActual) : null,
        direccion: tipoActual === 'domicilio' ? direccion : null
      })
    });

    const data = await res.json();

    if (data.ok) {
      cerrarCarrito();
      const num = String(data.id_pedido).padStart(5, '0');
      $('ok-msg').innerHTML = `Pedido <strong>#ORD-${num}</strong> procesado correctamente.`;

      // Factura en modal de éxito
      const factItems = $('ok-factura-items');
      factItems.innerHTML = carritoSnap.map(it =>
        `<div class="flex justify-between">
           <span>${it.nombre} ×${it.cantidad}${it.observacion ? ` <em>(${it.observacion})</em>` : ''}</span>
           <span class="font-semibold">$${(it.precio * it.cantidad).toLocaleString('es-CO')}</span>
         </div>`
      ).join('');
      const total = carritoSnap.reduce((s, it) => s + it.precio * it.cantidad, 0);
      $('ok-factura-total').textContent = '$' + total.toLocaleString('es-CO');
      $('ok-factura').classList.remove('hidden');
      $('modal-ok').classList.add('show');

      carrito = []; mesaActual = null;
      actualizarFAB();
    } else {
      alert('Error: ' + (data.error || 'No se pudo procesar el pedido.'));
    }
  } catch (e) {
    console.error(e);
    alert('Error de conexión. Intenta de nuevo.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-lock text-sm mr-2"></i> Confirmar pedido';
  }
}

function cerrarOk() {
  $('modal-ok').classList.remove('show');
  $('ok-factura').classList.add('hidden');
}

// ── Filtro de productos ───────────────────────────────────────
function filtrar() {
  const t = $('buscar').value.toLowerCase();
  const c = $('filtro').value;
  let v = 0;
  document.querySelectorAll('.item').forEach(el => {
    const ok = el.dataset.nombre.includes(t) && (c === '' || el.dataset.categoria === c);
    el.style.display = ok ? '' : 'none';
    if (ok) v++;
  });
  $('sin').classList.toggle('hidden', v > 0);
}
</script>


<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
