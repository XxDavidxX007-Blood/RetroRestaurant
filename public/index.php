<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retro Restaurant | Fine Dining</title>
    <meta name="description" content="Bienvenido a Retro Restaurant, una experiencia culinaria de alta cocina con los ingredientes más exclusivos.">
    <link rel="shortcut icon" type="image/png" href="../img/rest.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        retro: {
                            gold: '#c5a059',
                            goldlight: '#d4b773',
                            dark: '#0a0a0a',
                            light: '#ffffff',
                            gray: '#1a1a1a',
                            subtle: '#f5f5f5'
                        }
                    },
                    fontFamily: {
                        cursive: ['"Playfair Display"', 'serif'],
                        heading: ['"Playfair Display"', 'serif'],
                        body: ['"Montserrat"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .elegant-text {
            letter-spacing: 0.1em;
        }
        
        .food-card {
            transition: all 0.5s ease;
            border: 1px solid #eaeaea;
        }
        
        .food-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border-color: #c5a059;
        }

        .btn-gold-outline {
            border: 1px solid #c5a059;
            color: #c5a059;
            transition: all 0.3s ease;
        }

        .btn-gold-outline:hover {
            background-color: #c5a059;
            color: #ffffff;
        }

        .bg-pattern {
            background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23c5a059" fill-opacity="0.05"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
        }

        html {
            scroll-behavior: smooth;
        }

        /* Fade in al cargar la página */
        body {
            animation: fadeIn 0.6s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Fade out al salir hacia otra página */
        body.fade-out {
            animation: fadeOut 0.4s ease forwards;
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-10px); }
        }
    </style>
</head>

<body class="bg-retro-light font-body text-gray-800 scroll-smooth selection:bg-retro-gold selection:text-white">

    <!-- Navigation -->
    <nav class="bg-retro-light/95 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 md:h-24 items-center">

                <!-- Logo -->
                <div class="flex items-center gap-2 md:gap-3">
                    <i class="fas fa-wine-glass text-retro-gold text-2xl md:text-3xl"></i>
                    <span class="font-heading text-xl md:text-3xl text-retro-dark tracking-wide font-semibold">Retro Restaurant</span>
                </div>

                <!-- Desktop links -->
                <div class="hidden md:flex space-x-10 font-body text-sm tracking-widest uppercase items-center">
                    <a href="#inicio" class="text-gray-600 hover:text-retro-gold transition duration-300">Inicio</a>
                    <a href="#menu" class="text-gray-600 hover:text-retro-gold transition duration-300">Menú</a>
                    <a href="#nosotros" class="text-gray-600 hover:text-retro-gold transition duration-300">Experiencia</a>
                    <a href="../views/usuarios/login.php"
                        class="btn-gold-outline px-8 py-3 font-medium hover:shadow-lg transition duration-300">
                        Reservaciones
                    </a>
                </div>

                <!-- Mobile: botones de acción + hamburguesa -->
                <div class="flex md:hidden items-center gap-2">
                    <a href="../views/usuarios/registre.php"
                       class="text-xs font-body font-semibold tracking-wider uppercase text-retro-dark border border-gray-300 px-3 py-2 rounded hover:border-retro-gold hover:text-retro-gold transition duration-200">
                        Registrarse
                    </a>
                    <a href="../views/usuarios/login.php"
                       class="text-xs font-body font-semibold tracking-wider uppercase bg-retro-gold text-white px-3 py-2 rounded hover:bg-retro-goldlight transition duration-200">
                        Reservar
                    </a>
                    <button id="menu-toggle" onclick="toggleMobileMenu()"
                            class="w-9 h-9 flex items-center justify-center text-gray-600 hover:text-retro-gold transition ml-1">
                        <i id="menu-icon" class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile dropdown menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 shadow-md">
            <div class="px-5 py-4 flex flex-col gap-1 font-body text-sm tracking-widest uppercase">
                <a href="#inicio" onclick="closeMobileMenu()"
                   class="py-3 px-2 text-gray-600 hover:text-retro-gold border-b border-gray-50 transition">
                    <i class="fas fa-home text-retro-gold mr-2 text-xs"></i> Inicio
                </a>
                <a href="#menu" onclick="closeMobileMenu()"
                   class="py-3 px-2 text-gray-600 hover:text-retro-gold border-b border-gray-50 transition">
                    <i class="fas fa-utensils text-retro-gold mr-2 text-xs"></i> Menú
                </a>
                <a href="#nosotros" onclick="closeMobileMenu()"
                   class="py-3 px-2 text-gray-600 hover:text-retro-gold border-b border-gray-50 transition">
                    <i class="fas fa-star text-retro-gold mr-2 text-xs"></i> Experiencia
                </a>
                <div class="pt-3 pb-1 flex flex-col gap-3">
                    <a href="../views/usuarios/login.php"
                       class="text-center py-3 font-semibold text-white tracking-widest uppercase text-xs transition"
                       style="background:#c5a059;">
                        <i class="fas fa-calendar-check mr-2"></i> Reservaciones
                    </a>
                    <a href="../views/usuarios/registre.php"
                       class="text-center py-3 font-semibold text-retro-dark border border-gray-300 tracking-widest uppercase text-xs hover:border-retro-gold hover:text-retro-gold transition">
                        <i class="fas fa-user-plus mr-2"></i> Crear cuenta
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="inicio" class="relative h-[80vh] overflow-hidden bg-retro-dark flex items-center justify-center">
        <!-- High-end dining image -->
        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1920&q=80" class="absolute inset-0 w-full h-full object-cover opacity-50" alt="Ambiente de Alta Cocina">
        <div class="absolute inset-0 bg-gradient-to-t from-retro-dark via-retro-dark/50 to-transparent"></div>
        <div class="relative z-10 flex flex-col items-center justify-center text-center px-4 max-w-4xl mx-auto mt-16">
            <span class="text-retro-gold font-body tracking-[0.3em] uppercase text-sm mb-4">Experiencia Gastronómica</span>
            <h1 class="text-5xl md:text-7xl font-heading text-white mb-6 font-medium leading-tight">El Arte de la <br>Alta Cocina</h1>
            <p class="text-lg md:text-xl text-gray-300 font-body font-light tracking-wide mb-10 max-w-2xl">
                Ingredientes exclusivos, técnicas de vanguardia y un ambiente de absoluta sofisticación.
            </p>
            <div class="flex gap-6">
                <a href="#menu" class="bg-retro-gold text-white px-10 py-4 font-body text-sm tracking-widest uppercase hover:bg-retro-goldlight transition duration-300">
                    Descubrir Menú
                </a>
            </div>
        </div>
    </section>

    <!-- Menu Highlights -->
    <section id="menu" class="py-24 bg-retro-subtle bg-pattern relative">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-20">
                <span class="text-retro-gold font-body tracking-[0.2em] uppercase text-sm mb-3 block">Nuestra Selección</span>
                <h2 class="text-4xl md:text-5xl font-heading font-semibold text-retro-dark">PLATOS SIGNATURE</h2>
                <div class="w-16 h-[1px] bg-retro-gold mx-auto mt-8"></div>
            </div>
            
            <div class="grid md:grid-cols-3 gap-12">
                <!-- Dish 1 -->
                <div class="bg-white overflow-hidden food-card group">
                    <div class="h-80 overflow-hidden relative">
                        <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80" alt="Plato Gourmet" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    </div>
                    <div class="p-10 text-center">
                        <h3 class="font-heading text-2xl mb-2 text-retro-dark">Lomo Filet Mignon</h3>
                        <div class="text-retro-gold font-body tracking-wider mb-4">$45.00</div>
                        <p class="text-gray-500 mb-8 font-body font-light text-sm leading-relaxed">Corte premium sellado a la perfección, acompañado de puré de trufa negra, espárragos glaseados y reducción de vino tinto.</p>
                        <button class="text-xs uppercase tracking-[0.2em] text-retro-dark border-b border-retro-dark pb-1 hover:text-retro-gold hover:border-retro-gold transition">Ordenar Ahora</button>
                    </div>
                </div>

                <!-- Dish 2 -->
                <div class="bg-white overflow-hidden food-card group">
                    <div class="h-80 overflow-hidden relative">
                        <img src="https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=800&q=80" alt="Salmón Fresco" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    </div>
                    <div class="p-10 text-center">
                        <h3 class="font-heading text-2xl mb-2 text-retro-dark">Salmón Glaseado</h3>
                        <div class="text-retro-gold font-body tracking-wider mb-4">$38.00</div>
                        <p class="text-gray-500 mb-8 font-body font-light text-sm leading-relaxed">Salmón del atlántico en costra de hierbas finas, sobre cama de quinoa cítrica y emulsión de maracuyá salvaje.</p>
                        <button class="text-xs uppercase tracking-[0.2em] text-retro-dark border-b border-retro-dark pb-1 hover:text-retro-gold hover:border-retro-gold transition">Ordenar Ahora</button>
                    </div>
                </div>

                <!-- Dish 3 -->
                <div class="bg-white overflow-hidden food-card group">
                    <div class="h-80 overflow-hidden relative">
                        <img src="https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=800&q=80" alt="Postre Gourmet" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    </div>
                    <div class="p-10 text-center">
                        <h3 class="font-heading text-2xl mb-2 text-retro-dark">Esfera de Chocolate</h3>
                        <div class="text-retro-gold font-body tracking-wider mb-4">$18.00</div>
                        <p class="text-gray-500 mb-8 font-body font-light text-sm leading-relaxed">Ganache de chocolate belga 70%, corazón de frutos rojos y oro comestible, servido con helado de vainilla de Madagascar.</p>
                        <button class="text-xs uppercase tracking-[0.2em] text-retro-dark border-b border-retro-dark pb-1 hover:text-retro-gold hover:border-retro-gold transition">Ordenar Ahora</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="nosotros" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-2 gap-16 items-center">
            <div class="order-2 md:order-1 relative">
                <div class="absolute inset-0 bg-retro-gold/10 transform translate-x-4 translate-y-4 -z-10"></div>
                <img src="https://images.unsplash.com/photo-1577219491135-ce391730fb2c?auto=format&fit=crop&w=800&q=80" alt="Chef preparando plato" class="w-full h-auto object-cover shadow-sm">
            </div>
            <div class="order-1 md:order-2 px-4 md:px-10">
                <span class="text-retro-gold font-body tracking-[0.2em] uppercase text-sm mb-3 block">Nuestra Filosofía</span>
                <h2 class="text-4xl md:text-5xl font-heading font-semibold mb-8 text-retro-dark leading-tight">Perfección en<br>cada detalle</h2>
                <p class="text-gray-600 font-light leading-relaxed text-base mb-6 font-body">
                    En <strong>Retro Restaurant</strong>, hemos elevado el concepto de la gastronomía clásica a los más altos estándares de la alta cocina internacional. Cada plato es una obra maestra diseñada para deleitar los sentidos.
                </p>
                <p class="text-gray-600 font-light leading-relaxed text-base mb-10 font-body">
                    Seleccionamos meticulosamente ingredientes de temporada de productores locales y técnicas vanguardistas para ofrecerle una experiencia inmersiva e inolvidable en un ambiente de absoluta sofisticación.
                </p>
                
                <a href="../views/usuarios/login.php" class="btn-gold-outline px-8 py-3 font-medium hover:shadow-lg transition duration-300 inline-block text-sm tracking-widest uppercase">
                    Reservar Mesa
                </a>
            </div>
        </div>
    </section>

    <!-- Features / Highlights -->
    <section class="py-20 bg-retro-gray text-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-12 text-center">
                <div class="p-6">
                    <i class="fas fa-award text-4xl mb-6 text-retro-gold"></i>
                    <h3 class="font-heading text-xl mb-3 tracking-wide">Excelencia Culinaria</h3>
                    <p class="text-gray-400 font-light text-sm">Galardonados por nuestra propuesta gastronómica innovadora.</p>
                </div>
                <div class="p-6 border-y md:border-y-0 md:border-x border-gray-800">
                    <i class="fas fa-glass-cheers text-4xl mb-6 text-retro-gold"></i>
                    <h3 class="font-heading text-xl mb-3 tracking-wide">Sommelier Experto</h3>
                    <p class="text-gray-400 font-light text-sm">Maridaje perfecto con nuestra exclusiva cava de vinos internacionales.</p>
                </div>
                <div class="p-6">
                    <i class="fas fa-concierge-bell text-4xl mb-6 text-retro-gold"></i>
                    <h3 class="font-heading text-xl mb-3 tracking-wide">Servicio Impecable</h3>
                    <p class="text-gray-400 font-light text-sm">Atención personalizada para garantizar una velada perfecta.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-retro-dark text-gray-400 pt-20 pb-10 border-t border-gray-900">
        <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-12">
            <div class="col-span-1 md:col-span-1">
                <div class="flex items-center gap-3 mb-6">
                    <i class="fas fa-wine-glass text-retro-gold text-2xl"></i>
                    <h4 class="text-white text-xl font-heading tracking-widest">RETRO RESTAURANT</h4>
                </div>
                <p class="text-sm font-body font-light leading-relaxed">La máxima expresión de la gastronomía contemporánea en un ambiente de lujo incomparable.</p>
                <div class="flex space-x-5 mt-8">
                    <a href="#" class="text-gray-500 hover:text-retro-gold transition"><i class="fab fa-instagram text-xl"></i></a>
                    <a href="#" class="text-gray-500 hover:text-retro-gold transition"><i class="fab fa-facebook-f text-xl"></i></a>
                    <a href="#" class="text-gray-500 hover:text-retro-gold transition"><i class="fab fa-twitter text-xl"></i></a>
                </div>
            </div>
            
            <div>
                <h4 class="text-white font-heading text-sm tracking-widest uppercase mb-6">Horarios de Reserva</h4>
                <ul class="space-y-4 text-sm font-body font-light">
                    <li class="flex justify-between border-b border-gray-800 pb-2"><span>Lunes - Jueves</span> <span>18:00 - 23:00</span></li>
                    <li class="flex justify-between border-b border-gray-800 pb-2"><span>Viernes - Sábado</span> <span>18:00 - 00:00</span></li>
                    <li class="flex justify-between border-b border-gray-800 pb-2 text-retro-gold"><span>Domingo</span> <span>Cerrado</span></li>
                </ul>
            </div>
            
            <div>
                <h4 class="text-white font-heading text-sm tracking-widest uppercase mb-6">Contacto</h4>
                <p class="text-sm font-body font-light mb-4 text-gray-400">Av. de la Elegancia 1234, <br>Distrito Financiero</p>
                <p class="text-sm font-body font-light mb-4 text-retro-gold">+1 (800) 555-FINE</p>
                <p class="text-sm font-body font-light">reservations@retrorestaurant.com</p>
            </div>

            <div>
                <h4 class="text-white font-heading text-sm tracking-widest uppercase mb-6">Membresía</h4>
                <ul class="space-y-3 text-sm font-body font-light">
                    <li><a href="../views/usuarios/login.php" class="hover:text-retro-gold transition">Acceso Exclusivo VIP</a></li>
                    <li><a href="#" class="hover:text-retro-gold transition">Eventos Privados</a></li>
                    <li><a href="#" class="hover:text-retro-gold transition">Política de Privacidad</a></li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-900 mt-16 pt-8 text-center text-xs font-body font-light tracking-wider">
            <p>&copy; 2026 Retro Restaurant Fine Dining. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // ── Menú móvil ────────────────────────────────────────────────
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const icon = document.getElementById('menu-icon');
            const open = menu.classList.toggle('hidden');
            icon.className = open ? 'fas fa-bars text-lg' : 'fas fa-times text-lg';
        }

        function closeMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const icon = document.getElementById('menu-icon');
            menu.classList.add('hidden');
            icon.className = 'fas fa-bars text-lg';
        }

        // Cerrar menú al hacer scroll
        window.addEventListener('scroll', closeMobileMenu, { passive: true });
        
        // ── Fade out antes de navegar ─────────────────────────────────
        document.querySelectorAll('a[href]').forEach(function(link) {
            const href = link.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript')) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeMobileMenu();
                    document.body.classList.add('fade-out');
                    setTimeout(function() {
                        window.location.href = href;
                    }, 400);
                });
            }
        });
    </script>

</body>
</html>