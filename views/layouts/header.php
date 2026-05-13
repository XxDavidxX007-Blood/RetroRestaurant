<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    // Redirect absoluto — funciona en local y producción
    // SCRIPT_NAME apunta al archivo que fue solicitado (ej: /views/dashboard/cliente.php)
    // Necesitamos la raíz del proyecto, que es 2 niveles arriba de views/dashboard/
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    // Subir desde /views/dashboard/archivo.php → /views → raíz
    $script   = $_SERVER['SCRIPT_NAME'];
    $base     = rtrim(dirname(dirname(dirname($script))), '/');
    // Si base queda vacío (dominio apunta directo a la carpeta del proyecto), usar ''
    if ($base === '.') $base = '';
    header("Location: {$protocol}://{$host}{$base}/views/usuarios/login.php");
    exit;
}

$usuario = $_SESSION['usuario'];

// Sincronizar foto desde la BD en cada carga de página
if (!class_exists('Database')) require_once __DIR__ . '/../../config/database.php';
try {
    $dbTemp   = (new database())->conectar();
    $stmtFoto = $dbTemp->prepare("SELECT foto FROM usuario WHERE id_usuario = :id LIMIT 1");
    $stmtFoto->execute([':id' => $usuario['id_usuario']]);
    $rowFoto  = $stmtFoto->fetch(PDO::FETCH_ASSOC);
    if ($rowFoto !== false) {
        $_SESSION['usuario']['foto'] = $rowFoto['foto'];
        $usuario['foto']             = $rowFoto['foto'];
    }
} catch (Exception $e) { /* silencioso */ }

$titulo = $titulo ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retro Restaurant | <?= htmlspecialchars($titulo) ?></title>
    <link rel="shortcut icon" type="image/png" href="../../img/rest.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                            subtle: '#f5f5f5',
                            // Compatibilidad con vistas anteriores
                            red: '#0a0a0a',      // Convierte botones rojos en oscuros elegantes
                            yellow: '#c5a059'    // Convierte acentos amarillos en dorados
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
        .bg-lux {
            background-color: #fcfcfc;
            background-image: url('https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .main-overlay {
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="bg-lux min-h-screen font-body text-gray-800 relative">
    <div class="absolute inset-0 bg-white/90 z-[-1]"></div>
<div class="flex min-h-screen relative z-0">