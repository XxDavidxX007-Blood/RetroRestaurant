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
    <title>Retro Restaurant | Membresía Exclusiva</title>
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
            background-attachment: fixed;
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

        /* Customize select arrow */
        select.input-elegant {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23c5a059%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 0.2rem top 50%;
            background-size: 0.65rem auto;
            padding-right: 1.5rem;
        }
    </style>
</head>
<body class="bg-retro-dark min-h-screen flex items-center justify-center py-16 px-4 md:px-8 font-body bg-lux relative">
    <div class="absolute inset-0 bg-black/60"></div>

    <!-- Go Back Button -->
    <a href="login.php" class="absolute top-8 left-8 text-white/80 hover:text-retro-gold transition z-10 font-body text-sm tracking-widest uppercase flex items-center gap-3">
        <i class="fas fa-long-arrow-alt-left"></i> Volver al Acceso
    </a>

    <div class="glass-panel w-full max-w-4xl shadow-2xl relative z-10 mt-8 md:mt-0">
        
        <div class="p-10 md:p-14 border-b border-gray-100 text-center">
            <i class="fas fa-wine-glass text-retro-gold text-4xl mb-4"></i>
            <h1 class="text-3xl font-heading text-retro-dark mb-2 font-bold">Registro de Nuevo Cliente</h1>
            <p class="text-gray-700 font-body font-medium text-sm">Cree su cuenta para realizar reservas y disfrutar de nuestra propuesta gastronómica.</p>
        </div>

        <div class="p-10 md:p-14">
            <form action="../../Controllers/UsuarioController.php" method="POST" class="space-y-8">
                <input type="text" name="nombre" value="Cliente" hidden>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="relative">
                        <label class="block text-xs uppercase tracking-[0.2em] text-gray-800 mb-2 font-bold">Nombres</label>
                        <input type="text" name="nombre" required maxlength="100"
                            class="w-full py-2 input-elegant text-sm text-retro-dark"
                            placeholder="Ingrese su nombre">
                    </div>

                    <div class="relative">
                        <label class="block text-xs uppercase tracking-[0.2em] text-gray-800 mb-2 font-bold">Apellidos</label>
                        <input type="text" name="apellidos" required maxlength="100"
                            class="w-full py-2 input-elegant text-sm text-retro-dark"
                            placeholder="Ingrese sus apellidos">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="relative">
                        <label class="block text-xs uppercase tracking-[0.2em] text-gray-800 mb-2 font-bold">Correo Electrónico</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center text-gray-300 pointer-events-none">
                                <i class="fas fa-envelope text-sm"></i>
                            </span>
                            <input type="email" name="email" required maxlength="150"
                                class="w-full py-2 input-elegant text-sm text-retro-dark pr-8"
                                placeholder="ejemplo@correo.com">
                        </div>
                    </div>

                    <div class="relative">
                        <label class="block text-xs uppercase tracking-[0.2em] text-gray-800 mb-2 font-bold">Teléfono</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center text-gray-300 pointer-events-none">
                                <i class="fas fa-phone text-sm"></i>
                            </span>
                            <input type="text" name="telefono" required maxlength="30"
                                class="w-full py-2 input-elegant text-sm text-retro-dark pr-8"
                                placeholder="+1 234 567 8900">
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="relative">
                        <label class="block text-xs uppercase tracking-[0.2em] text-gray-800 mb-2 font-bold">Contraseña</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center text-gray-300 pointer-events-none">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input type="password" name="password" required
                                class="w-full py-2 input-elegant text-sm text-retro-dark pr-8"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div class="relative">
                        <label class="block text-xs uppercase tracking-[0.2em] text-gray-800 mb-2 font-bold">Confirmar Contraseña</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center text-gray-300 pointer-events-none">
                                <i class="fas fa-check-double text-sm"></i>
                            </span>
                            <input type="password" name="confirmar_password" required
                                class="w-full py-2 input-elegant text-sm text-retro-dark pr-8"
                                placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <input type="hidden" name="id_rol" value="3">

                <div class="flex items-start pt-4">
                    <input id="terms" type="checkbox" required class="w-4 h-4 mt-0.5 text-retro-gold focus:ring-retro-gold border-gray-300 rounded-sm cursor-pointer accent-retro-gold">
                    <label for="terms" class="ml-3 block text-xs text-gray-700 font-medium leading-relaxed">
                        Acepto los términos de exclusividad, políticas de privacidad y normativas de reserva de Retro Restaurant Fine Dining.
                    </label>
                </div>

                <div class="pt-6">
                    <button type="submit"
                        class="w-full bg-retro-dark text-white font-body text-sm font-bold tracking-[0.2em] uppercase py-4 hover:bg-retro-gold transition-all duration-500 shadow-sm">
                        Crear Cuenta
                    </button>
                </div>

                <div class="text-center pt-8 border-t border-gray-100">
                    <p class="text-gray-700 font-body text-sm font-medium tracking-wide">
                        ¿Ya tiene una cuenta?
                        <a href="login.php" class="inline-block ml-2 text-retro-dark border-b-2 border-retro-dark pb-0.5 text-xs uppercase tracking-[0.2em] font-bold hover:text-retro-gold hover:border-retro-gold transition">
                            Acceder
                        </a>
                    </p>
                </div>
            </form>
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
        }).then(() => {
            <?php if (!empty($alert['redirect'])): ?>
                window.location.href = '<?= htmlspecialchars($alert['redirect']) ?>';
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

</body>
</html>