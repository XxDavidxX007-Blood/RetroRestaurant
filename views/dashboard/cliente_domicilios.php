<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['id_rol'], [3,'3','cliente'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}
$usuario = $_SESSION['usuario'];
$titulo  = "DOMICILIOS";

require_once __DIR__ . '/../../config/database.php';
$db = (new database())->conectar();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
@keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
.animate-fade-up { animation: fadeUp 0.5s ease both; }
.delay-100 { animation-delay: 0.1s; }
.delay-200 { animation-delay: 0.2s; }
.delay-300 { animation-delay: 0.3s; }

/* Custom Scrollbar for inner elements */
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div class="space-y-6 animate-fade-up">

    <!-- Encabezado de Página -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
                <i class="fas fa-motorcycle text-retro-dark"></i> Domicilios
            </h1>
            <p class="text-gray-500 font-body text-sm mt-1">Pide tus platos favoritos en la comodidad de tu hogar</p>
        </div>
        <div class="relative w-full md:w-96">
            <input type="text" placeholder="Buscar platos, bebidas..."
                class="w-full pl-10 pr-4 py-3 bg-white border border-gray-100 rounded-2xl shadow-sm font-body text-sm outline-none focus:border-gray-300 transition">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
    </div>

    <!-- Contenido Principal Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- COLUMNA IZQUIERDA (Formularios y Pedidos) -->
        <div class="lg:col-span-2 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nueva Dirección de Entrega -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 relative overflow-hidden">
                    <h3 class="font-heading font-bold text-retro-dark flex items-center gap-2 mb-4">
                        <i class="fas fa-map-marker-alt text-gray-500"></i> Nueva dirección de entrega
                    </h3>
                    <form class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Dirección</label>
                            <input type="text" placeholder="Ingresa tu dirección"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-body focus:outline-none focus:border-gray-400 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Apartamento / Casa / Oficina (opcional)</label>
                            <input type="text" placeholder="Ej: Apto 101, Casa 5, Oficina 302"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-body focus:outline-none focus:border-gray-400 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Referencias (opcional)</label>
                            <input type="text" placeholder="Ej: Casa blanca, portón negro, al lado de la farmacia"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-body focus:outline-none focus:border-gray-400 transition">
                        </div>
                        <button type="button" class="w-full py-3 bg-retro-dark hover:bg-gray-800 text-white font-heading font-bold text-sm rounded-xl transition shadow-md mt-2">
                            Usar esta dirección
                        </button>
                    </form>
                </div>

                <!-- Direcciones Guardadas -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 relative overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-heading font-bold text-retro-dark">Direcciones guardadas</h3>
                        <a href="#" class="text-xs font-bold text-gray-500 hover:text-retro-dark">Gestionar</a>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar space-y-3">
                        <!-- Casa -->
                        <div class="border border-yellow-200 bg-[#FFFBEB] rounded-xl p-3 flex items-start gap-3 cursor-pointer relative">
                            <i class="fas fa-map-marker-alt text-yellow-600 mt-1"></i>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-800">Casa</p>
                                <p class="text-xs text-gray-500">Calle 45 #23-10, Barrio Palermo</p>
                            </div>
                            <span class="text-[10px] font-bold bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full mt-1">Predeterminada</span>
                        </div>
                        <!-- Trabajo -->
                        <div class="border border-gray-100 bg-white hover:bg-gray-50 rounded-xl p-3 flex items-start gap-3 cursor-pointer transition">
                            <i class="far fa-calendar text-gray-400 mt-1"></i>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-800">Trabajo</p>
                                <p class="text-xs text-gray-500">Carrera 12 #98-76, Oficina 302</p>
                            </div>
                            <button class="text-gray-400 hover:text-gray-600"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                        <!-- Casa de mis padres -->
                        <div class="border border-gray-100 bg-white hover:bg-gray-50 rounded-xl p-3 flex items-start gap-3 cursor-pointer transition">
                            <i class="fas fa-map-marker-alt text-gray-400 mt-1"></i>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-800">Casa de mis padres</p>
                                <p class="text-xs text-gray-500">Calle 10 #5-23, Barrio Centro</p>
                            </div>
                            <button class="text-gray-400 hover:text-gray-600"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </div>

                    <button type="button" class="mt-4 w-full py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 font-body text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i> Agregar nueva dirección
                    </button>
                </div>
            </div>

            <!-- Mis pedidos a domicilio -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 animate-fade-up delay-100">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <h3 class="font-heading font-bold text-retro-dark flex items-center gap-2">
                        <i class="fas fa-truck text-gray-500"></i> Mis pedidos a domicilio
                    </h3>
                    <select class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-body text-gray-600 outline-none focus:border-gray-400 bg-white">
                        <option>Todos los estados</option>
                    </select>
                </div>

                <!-- Tabs -->
                <div class="flex items-center gap-6 border-b border-gray-100 mb-6">
                    <a href="#" class="pb-3 text-sm font-bold border-b-2 border-retro-dark text-retro-dark">Todos</a>
                    <a href="#" class="pb-3 text-sm text-gray-500 hover:text-gray-700">En camino</a>
                    <a href="#" class="pb-3 text-sm text-gray-500 hover:text-gray-700">Entregado</a>
                    <a href="#" class="pb-3 text-sm text-gray-500 hover:text-gray-700">Cancelados</a>
                </div>

                <!-- Listado de Pedidos Estático (Mockup) -->
                <div class="space-y-4">
                    <!-- Pedido 1025 -->
                    <div class="flex flex-col md:flex-row items-center gap-4 py-4 border-b border-gray-50">
                        <img src="../../img/productos/hamburguesa_clasica.jpg" onerror="this.src='https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=150&h=150&fit=crop'" alt="Burger" class="w-20 h-20 rounded-xl object-cover shadow-sm">
                        <div class="flex-1 w-full">
                            <h4 class="font-bold text-gray-800">Pedido #1025</h4>
                            <p class="text-xs text-gray-500">31 Mayo, 2026 • 7:45 PM</p>
                            <p class="text-xs text-gray-500 mt-1">2 productos</p>
                        </div>
                        <div class="flex-1 w-full">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-sm"><i class="fas fa-motorcycle"></i></div>
                                <div>
                                    <p class="text-sm font-bold text-green-700">En camino</p>
                                    <p class="text-xs text-gray-500">Tu pedido va en camino</p>
                                </div>
                            </div>
                            <div class="flex items-center mt-3 gap-1">
                                <i class="fas fa-store text-gray-300 text-xs"></i>
                                <div class="flex-1 h-1 bg-green-500 rounded-full"></div>
                                <i class="fas fa-motorcycle text-green-500 text-xs"></i>
                                <div class="flex-1 h-1 bg-gray-200 rounded-full"></div>
                                <i class="fas fa-home text-gray-300 text-xs"></i>
                            </div>
                        </div>
                        <div class="text-right w-full md:w-auto">
                            <p class="text-xs text-gray-500">Total</p>
                            <p class="font-bold text-lg text-gray-800">$45.000</p>
                            <button class="mt-2 px-4 py-1.5 border border-gray-200 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50 transition">Ver detalle</button>
                        </div>
                    </div>

                    <!-- Pedido 1024 -->
                    <div class="flex flex-col md:flex-row items-center gap-4 py-4 border-b border-gray-50">
                        <img src="../../img/productos/pizza_margarita.jpg" onerror="this.src='https://images.unsplash.com/photo-1513104890138-7c749659a591?w=150&h=150&fit=crop'" alt="Pizza" class="w-20 h-20 rounded-xl object-cover shadow-sm">
                        <div class="flex-1 w-full">
                            <h4 class="font-bold text-gray-800">Pedido #1024</h4>
                            <p class="text-xs text-gray-500">30 Mayo, 2026 • 8:10 PM</p>
                            <p class="text-xs text-gray-500 mt-1">3 productos</p>
                        </div>
                        <div class="flex-1 w-full">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-sm"><i class="fas fa-fire-burner"></i></div>
                                <div>
                                    <p class="text-sm font-bold text-yellow-700">Preparando</p>
                                    <p class="text-xs text-gray-500">Estamos preparando tu pedido</p>
                                </div>
                            </div>
                            <div class="flex items-center mt-3 gap-1">
                                <i class="fas fa-store text-yellow-500 text-xs"></i>
                                <div class="w-1/3 h-1 bg-yellow-500 rounded-full"></div>
                                <i class="fas fa-box text-gray-300 text-xs"></i>
                                <div class="flex-1 h-1 bg-gray-200 rounded-full"></div>
                                <i class="fas fa-home text-gray-300 text-xs"></i>
                            </div>
                        </div>
                        <div class="text-right w-full md:w-auto">
                            <p class="text-xs text-gray-500">Total</p>
                            <p class="font-bold text-lg text-gray-800">$63.000</p>
                            <button class="mt-2 px-4 py-1.5 border border-gray-200 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50 transition">Ver detalle</button>
                        </div>
                    </div>

                    <!-- Pedido 1023 -->
                    <div class="flex flex-col md:flex-row items-center gap-4 py-4">
                        <img src="../../img/productos/brownie_helado.jpg" onerror="this.src='https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=150&h=150&fit=crop'" alt="Dessert" class="w-20 h-20 rounded-xl object-cover shadow-sm">
                        <div class="flex-1 w-full">
                            <h4 class="font-bold text-gray-800">Pedido #1023</h4>
                            <p class="text-xs text-gray-500">29 Mayo, 2026 • 2:30 PM</p>
                            <p class="text-xs text-gray-500 mt-1">1 producto</p>
                        </div>
                        <div class="flex-1 w-full">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-sm"><i class="fas fa-check"></i></div>
                                <div>
                                    <p class="text-sm font-bold text-green-700">Entregado</p>
                                    <p class="text-xs text-gray-500">Entregado el 29 Mayo, 2026 a las 3:05 PM</p>
                                </div>
                            </div>
                            <!-- No progress bar for delivered -->
                        </div>
                        <div class="text-right w-full md:w-auto">
                            <p class="text-xs text-gray-500">Total</p>
                            <p class="font-bold text-lg text-gray-800">$18.000</p>
                            <button class="mt-2 px-4 py-1.5 border border-gray-200 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50 transition">Ver detalle</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA (Mapa y Rastreo) -->
        <div class="space-y-6">
            <!-- Mapa -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden relative" style="height: 280px;">
                <!-- Google Maps Embed -->
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127246.68972583842!2d-74.15549014605994!3d4.648283717144702!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e3f9bfd2da6cb29%3A0x239d635520a33914!2zQm9nb3TDoSwgQ29sb21iaWE!5e0!3m2!1ses!2sus!4v1715200000000!5m2!1ses!2sus" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    class="absolute inset-0 z-0">
                </iframe>

                <!-- Info Box en Mapa -->
                <div class="absolute bottom-4 right-4 bg-white/95 backdrop-blur rounded-xl p-4 shadow-lg border border-gray-100 text-center animate-fade-up delay-200 z-10">
                    <p class="text-xs text-gray-500 mb-1">Tiempo estimado</p>
                    <p class="text-lg font-bold text-retro-dark">30 - 40 min</p>
                    <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wide">Envío a domicilio</p>
                </div>
            </div>

            <!-- Rastrea tu pedido -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 animate-fade-up delay-200">
                <h3 class="font-heading font-bold text-retro-dark mb-1">Rastrea tu pedido en tiempo real</h3>
                <p class="text-xs text-gray-500 mb-6">Conoce el estado exacto y la ubicación de tu pedido en el mapa.</p>
                
                <div class="relative pt-4 pb-2">
                    <!-- Scooter Illustration placeholder -->
                    <div class="absolute right-0 top-0 text-4xl transform -translate-y-2 translate-x-2">🛵</div>
                    <!-- Path line -->
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full bg-red-500 z-10"></div>
                        <div class="flex-1 h-[2px] bg-gray-200 mx-1"></div>
                        <div class="w-2 h-2 rounded-full bg-gray-300 z-10"></div>
                        <div class="flex-1 h-[2px] bg-gray-200 mx-1"></div>
                        <div class="w-2 h-2 rounded-full bg-gray-300 z-10"></div>
                        <div class="flex-1 h-[2px] bg-gray-200 mx-1 border-t-2 border-dashed border-gray-300 bg-transparent"></div>
                    </div>
                </div>
            </div>

            <!-- Información de Envío -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 animate-fade-up delay-300">
                <h3 class="font-heading font-bold text-retro-dark mb-4">Información de envío</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600 flex items-center gap-2"><i class="fas fa-money-bill-wave w-4 text-gray-400"></i> Costo de envío</span>
                        <span class="font-bold text-gray-800">$6.000</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600 flex items-center gap-2"><i class="far fa-clock w-4 text-gray-400"></i> Tiempo estimado</span>
                        <span class="font-bold text-gray-800">30 - 40 min</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600 flex items-center gap-2"><i class="far fa-calendar-alt w-4 text-gray-400"></i> Horario de domicilios</span>
                        <span class="font-bold text-gray-800 text-xs">11:00 AM - 10:00 PM</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600 flex items-center gap-2"><i class="fas fa-map-marker-alt w-4 text-gray-400"></i> Zonas de cobertura</span>
                        <a href="#" class="font-bold text-orange-500 hover:text-orange-600 text-xs">Ver zonas</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner Promocional -->
    <div class="bg-retro-dark rounded-2xl overflow-hidden shadow-lg relative flex items-center justify-between p-6 animate-fade-up delay-300 border border-gray-800">
        <!-- Background image with overlay -->
        <div class="absolute inset-0 opacity-40 mix-blend-overlay pointer-events-none" style="background-image: url('https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&q=80'); background-size: cover; background-position: center;"></div>
        
        <div class="relative z-10 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full border-2 border-retro-gold flex items-center justify-center text-retro-gold text-xl">
                <i class="fas fa-motorcycle"></i>
            </div>
            <div>
                <h3 class="text-xl font-heading font-bold text-white mb-1">¡Domicilio gratis!</h3>
                <p class="text-gray-300 text-sm">Por compras superiores a $40.000</p>
            </div>
        </div>
        
        <button class="relative z-10 bg-[#eab308] hover:bg-[#ca8a04] text-retro-dark font-heading font-bold px-6 py-3 rounded-xl transition shadow-lg whitespace-nowrap">
            Pedir ahora
        </button>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
