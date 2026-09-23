<div class="max-w-2xl mx-auto space-y-6">
    <!-- Botón de retorno -->
    <div>
        <a href="/dashboard" class="inline-flex items-center gap-2 text-brand-emerald hover:text-brand-deep font-semibold text-sm transition">
            ← Volver al Dashboard
        </a>
    </div>

    <!-- Contenedor / Card del Formulario -->
    <div class="bg-white p-8 rounded-xl border border-brand-mint shadow-sm space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-brand-dark tracking-tight">Crear un Nuevo Torneo</h1>
            <p class="text-slate-500 text-sm mt-1">Completa los datos iniciales para comenzar a cargar equipos y generar el fixture.</p>
        </div>

        <form action="/torneos/create" method="POST" class="space-y-5">
            <!-- Campo Nombre del Torneo -->
            <div>
                <label for="nombre" class="block text-sm font-bold text-brand-dark mb-2">
                    Nombre del Torneo
                </label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej: Torneo Relámpago 2026" required
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-emerald focus:bg-white transition">
            </div>

            <!-- Campo Formato del Torneo -->
            <div>
                <label for="formato" class="block text-sm font-bold text-brand-dark mb-2">
                    Formato del Torneo
                </label>
                <select id="formato" name="formato" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-emerald focus:bg-white transition">
                    <option value="" disabled selected>-- Selecciona un formato --</option>
                    <option value="liga">Liga (Todos contra todos)</option>
                    <option value="eliminatoria">Eliminatoria (Eliminación directa)</option>
                </select>
            </div>

            <!-- Botón Submit -->
            <div class="pt-2">
                <button type="submit" class="w-full bg-brand-deep hover:bg-brand-emerald text-brand-mint font-bold py-3 px-4 rounded-lg shadow transition flex justify-center items-center gap-2">
                    Crear Torneo y Continuar →
                </button>
            </div>
        </form>
    </div>
</div>