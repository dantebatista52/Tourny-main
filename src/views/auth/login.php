<div class="max-w-md mx-auto my-8">
    <!-- Cajita principal del Formulario -->
    <div class="bg-white p-8 rounded-xl border border-brand-mint shadow-md space-y-6">
        
        <!-- Encabezado y Título -->
        <div class="text-center">
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Iniciar Sesión</h1>
            <p class="text-brand-emerald text-sm mt-1 font-medium">Ingresá a tu cuenta para gestionar tus torneos</p>
        </div>

        <!-- Formulario -->
        <form action="/login" method="POST" class="space-y-5">
            <!-- Campo Email -->
            <div>
                <label for="email" class="block text-sm font-bold text-brand-dark mb-2">
                    Correo Electrónico
                </label>
                <input type="email" id="email" name="email" placeholder="tu@email.com" required
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-emerald focus:bg-white transition">
            </div>

            <!-- Campo Contraseña -->
            <div>
                <label for="password" class="block text-sm font-bold text-brand-dark mb-2">
                    Contraseña
                </label>
                <input type="password" id="password" name="password" placeholder="••••••••" required
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-emerald focus:bg-white transition">
            </div>

            <!-- Botón Submit -->
            <div class="pt-2">
                <button type="submit" class="w-full bg-brand-deep hover:bg-brand-emerald text-brand-mint font-bold py-3 px-4 rounded-lg shadow transition flex justify-center items-center gap-2">
                    Ingresar →
                </button>
            </div>
        </form>

        <!-- Enlace a Registro -->
        <div class="border-t border-slate-100 pt-4 text-center">
            <p class="text-slate-600 text-sm">
                ¿No tenés cuenta? 
                <a href="/registro" class="text-brand-emerald hover:text-brand-deep font-bold underline transition ml-1">
                    Registrate acá
                </a>
            </p>
        </div>

    </div>
</div>