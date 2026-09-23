<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Tourny - Gestor de Torneos' ?></title>

    <!-- Tailwind CSS CDN + Configuración de Paleta -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              brand: {
                dark: '#0c1410',
                deep: '#1b4332',
                emerald: '#2d6a4f',
                medium: '#40916c',
                mint: '#b7e4c7',
                light: '#f2f9f4'
              }
            }
          }
        }
      }
    </script>

    <!-- Fuente Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-brand-light text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Header Global -->
    <header class="bg-brand-dark text-white shadow-lg border-b border-brand-deep">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/dashboard" class="text-2xl font-extrabold tracking-wider text-brand-mint flex items-center gap-2 hover:opacity-90 transition">
                🏆 TOURNY
            </a>
            <nav class="flex gap-4 items-center">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="/dashboard" class="text-brand-light hover:text-brand-mint font-semibold transition text-sm">Dashboard</a>
                    <a href="/logout" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="/login" class="text-brand-light hover:text-brand-mint font-semibold transition text-sm">Ingresar</a>
                    <a href="/registro" class="bg-brand-deep hover:bg-brand-emerald text-brand-mint px-4 py-2 rounded-lg text-sm font-bold shadow transition border border-brand-medium">Registrarse</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 md:p-8">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-brand-dark text-slate-400 text-center py-4 text-xs border-t border-brand-deep mt-auto">
        <p>&copy; <?= date('Y') ?> Tourny. Todos los derechos reservados.</p>
    </footer>

</body>
</html>