<div class="space-y-6">
    <!-- Encabezado de Sección -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl border border-brand-mint shadow-sm">
        <div>
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Mis Torneos</h1>
            <p class="text-brand-emerald font-medium text-sm mt-1">Bienvenido/a, <?= htmlspecialchars($nombre) ?></p>
        </div>
        <a href="/torneos/create" class="inline-flex items-center gap-2 bg-brand-deep hover:bg-brand-emerald text-white px-5 py-2.5 rounded-lg font-bold shadow-md hover:shadow-lg transition">
            ➕ Crear Nuevo Torneo
        </a>
    </div>

    <!-- Lista de Torneos en Cajitas (Grid de Cards) -->
    <?php if (empty($torneos)): ?>
        <div class="bg-white p-8 rounded-xl border border-dashed border-brand-medium text-center">
            <p class="text-slate-600 font-medium">Aún no has creado ningún torneo.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($torneos as $torneo): ?>
                <!-- CAJITA DE TORNEO (DIV) -->
                <div class="bg-white rounded-xl border border-brand-mint shadow-sm hover:shadow-md transition flex flex-col justify-between overflow-hidden">
                    <div class="p-5">
                        <div class="flex justify-between items-start gap-2 mb-3">
                            <span class="bg-brand-mint/40 text-brand-dark font-bold text-xs uppercase px-2.5 py-1 rounded-md border border-brand-medium/30">
                                <?= htmlspecialchars($torneo['formato']) ?>
                            </span>

                            <!-- Formulario / Botón de Eliminar Torneo -->
                            <form action="/torneos/<?= $torneo['id'] ?>/delete" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este torneo? Esta acción no se puede deshacer.');">
                                <button type="submit" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded transition font-bold text-xs flex items-center gap-1" title="Eliminar torneo">
                                    🗑️ <span class="hidden sm:inline">Eliminar</span>
                                </button>
                            </form>
                        </div>

                        <!-- Título del Torneo -->
                        <h2 class="text-xl font-bold text-brand-dark tracking-tight mb-2">
                            <?= htmlspecialchars($torneo['nombre']) ?>
                        </h2>
                    </div>

                    <!-- Botones de Acción (Acciones claras) -->
                    <div class="bg-slate-50 p-4 border-t border-slate-100 flex flex-wrap gap-2">
                        <a href="/torneos/<?= $torneo['id'] ?>/equipos" class="flex-1 text-center bg-brand-deep hover:bg-brand-emerald text-white font-semibold py-2 px-3 rounded-md text-xs transition">
                            👥 Equipos
                        </a>
                        <a href="/torneos/<?= $torneo['id'] ?>/fixture" class="flex-1 text-center bg-brand-emerald hover:bg-brand-medium text-white font-semibold py-2 px-3 rounded-md text-xs transition">
                            📅 Fixture
                        </a>
                        <!-- Solo mostramos el acceso a la Tabla si el torneo es una Liga -->
                        <?php if ($torneo['formato'] === 'liga'): ?>
                            <a href="/torneos/<?= $torneo['id'] ?>/tabla" class="flex-1 text-center bg-brand-dark hover:bg-slate-800 text-brand-mint font-semibold py-2 px-3 rounded-md text-xs transition">
                                📊 Tabla
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>