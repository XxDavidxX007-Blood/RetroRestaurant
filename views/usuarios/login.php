<?php
session_start();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retro Restaurant | Acceso Exclusivo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" type="image/png" href="../../img/rest.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .input-elegant {
            background-color: transparent;
            border: none;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 0;
            padding-left: 0;
            padding-right: 0;
            transition: all 0.3s ease;
        }
        .input-elegant:focus {
            outline: none;
            box-shadow: none;
            border-bottom-color: #c5a059;
        }
        
        .bg-lux {
            background-image: url('https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        body {
            animation: fadeIn 0.6s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-retro-dark min-h-screen flex items-center justify-center p-4 md:p-8 font-body bg-lux relative">
    <div class="absolute inset-0 bg-black/60"></div>

    <!-- Go Back Button -->
    <a href="../../public/index.php" class="absolute top-8 left-8 text-white/80 hover:text-retro-gold transition z-10 font-body text-sm tracking-widest uppercase flex items-center gap-3">
        <i class="fas fa-long-arrow-alt-left"></i> Volver al Inicio
    </a>

    <div class="glass-panel w-full max-w-6xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[650px] relative z-10">
        
        <!-- Left Panel with Image -->
        <div class="hidden md:flex md:w-5/12 relative overflow-hidden bg-retro-dark items-center justify-center">
            <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=800&q=80" alt="Fine Dining" class="absolute inset-0 w-full h-full object-cover opacity-40 mix-blend-overlay">
            <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent"></div>
            
            <div class="relative z-10 p-12 text-center text-white flex flex-col items-center">
                <i class="fas fa-wine-glass text-retro-gold text-5xl mb-6"></i>
                <h2 class="text-3xl font-heading tracking-widest uppercase mb-4 text-retro-gold font-bold">Bienvenido</h2>
                <div class="w-12 h-[1px] bg-retro-gold/50 mb-6"></div>
                <p class="text-sm font-body text-gray-200 font-medium leading-relaxed max-w-xs">
                    Inicie sesión para gestionar reservas, pedidos y asegurar la más alta calidad en nuestro servicio.
                </p>
            </div>
        </div>
        
        <!-- Right Panel Login -->
        <div class="w-full md:w-7/12 p-10 md:p-16 lg:p-20 flex flex-col justify-center bg-white">
            
            <div class="text-center mb-12">
                <h1 class="text-3xl font-heading text-retro-dark mb-2 font-bold">Acceso Exclusivo</h1>
                <p class="text-gray-700 font-body font-medium text-sm">Ingrese sus credenciales para continuar</p>
            </div>

            <form action="../../Controllers/AuthController.php" method="POST" class="space-y-8 max-w-md mx-auto w-full">
                <div class="relative">
                    <label class="block text-xs uppercase tracking-[0.2em] text-gray-800 mb-2 font-bold">Correo Electrónico</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center text-gray-300">
                            <i class="fas fa-envelope text-sm"></i>
                        </span>
                        <input
                            type="email"
                            name="email"
                            placeholder="ejemplo@retrorestaurant.com"
                            required
                            class="w-full py-2 input-elegant text-sm text-retro-dark"
                        >
                    </div>
                </div>

                <div class="relative">
                    <label class="block text-xs uppercase tracking-[0.2em] text-gray-800 mb-2 font-bold">Contraseña</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center text-gray-300">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input
                            type="password"
                            name="password"
                            placeholder="••••••••"
                            required
                            class="w-full py-2 input-elegant text-sm text-retro-dark"
                        >
                    </div>
                    <div class="text-right mt-3">
                        <a href="#" class="text-gray-400 hover:text-retro-gold transition text-xs tracking-wider">¿Olvidó su contraseña?</a>
                    </div>
                </div>

                <div class="flex items-center pt-2">
                    <input type="checkbox" id="remember" class="w-4 h-4 text-retro-gold focus:ring-retro-gold border-gray-300 rounded-sm cursor-pointer accent-retro-gold">
                    <label for="remember" class="ml-3 block text-xs text-gray-700 font-medium">
                        Recordar mis datos en este dispositivo
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full bg-retro-dark text-white font-body text-sm font-bold tracking-[0.2em] uppercase py-4 mt-4 hover:bg-retro-gold transition-all duration-500 shadow-sm">
                    Ingresar al Sistema
                </button>
            </form>

            <div class="mt-12 text-center pt-8 border-t border-gray-100">
                <p class="text-gray-700 font-body text-sm font-medium tracking-wide">
                    ¿No tiene una cuenta? <br>
                    <a href="registre.php" class="inline-block mt-4 text-retro-dark border-b-2 border-retro-dark pb-1 text-xs uppercase tracking-[0.2em] font-bold hover:text-retro-gold hover:border-retro-gold transition">
                        Registro de Nuevo Cliente
                    </a>
                </p>
            </div>
        </div>
    </div>

    <?php if ($alert): ?>
    <script>
        Swal.fire({
            icon: '<?= htmlspecialchars($alert['icon']) ?>',
            title: '<?= htmlspecialchars($alert['title']) ?>',
            text: '<?= htmlspecialchars($alert['text']) ?>',
            confirmButtonText: 'Continuar',
            confirmButtonColor: '#0a0a0a',
            background: '#ffffff',
            customClass: {
                title: 'font-heading text-xl text-gray-900',
                htmlContainer: 'font-body font-light text-sm text-gray-600',
                confirmButton: 'font-body text-xs tracking-widest uppercase rounded-none px-8 py-3'
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>