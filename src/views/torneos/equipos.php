<div class="space-y-6">
    <!-- Encabezado y Navegación -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl border border-brand-mint shadow-sm">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="/dashboard" class="text-brand-emerald hover:text-brand-deep text-xs font-bold transition">← Volver al Dashboard</a>
                <span class="text-slate-300">•</span>
                <span class="bg-brand-mint/40 text-brand-dark font-bold text-xs uppercase px-2 py-0.5 rounded border border-brand-medium/30">
                    <?= htmlspecialchars($torneo['formato']) ?>
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Gestión de Equipos</h1>
            <p class="text-slate-500 font-medium text-sm">Torneo: <strong><?= htmlspecialchars($torneo['nombre']) ?></strong></p>
        </div>
    </div>

    <!-- FORMULARIO: Añadir Nuevo Equipo -->
    <div class="bg-white p-6 rounded-xl border border-brand-mint shadow-sm">
        <h2 class="text-lg font-bold text-brand-dark mb-3">Añadir Nuevo Equipo</h2>
        
        <form action="/torneos/<?= $torneo['id'] ?>/equipos" method="POST" class="flex flex-col sm:flex-row gap-3">
            <input type="text" 
                   id="nombre_equipo" 
                   name="nombre_equipo" 
                   placeholder="Nombre del equipo (Ej: Tupanqui)" 
                   required
                   class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-emerald focus:bg-white transition">
            
            <button type="submit" class="bg-brand-deep hover:bg-brand-emerald text-white font-bold px-6 py-2.5 rounded-lg shadow transition flex items-center justify-center gap-2">
                ➕ Agregar Equipo
            </button>
        </form>
    </div>

    <!-- LISTADO DE EQUIPOS REGISTRADOS -->
<div class="bg-white p-6 rounded-xl border border-brand-mint shadow-sm">
    <h2 class="text-lg font-bold text-brand-dark mb-4">Equipos Registrados (<?= count($equipos) ?>)</h2>

    <?php if (empty($equipos)): ?>
        <div class="text-center py-8 border border-dashed border-slate-300 rounded-lg bg-slate-50">
            <p class="text-slate-500 text-sm font-medium">No hay equipos registrados en este torneo aún.</p>
            <p class="text-slate-400 text-xs mt-1">Usa el formulario de arriba para ingresar el primer equipo.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($equipos as $equipo): ?>
                <div class="flex items-center justify-between p-3.5 bg-slate-50 border border-slate-200 rounded-lg hover:border-brand-mint transition gap-3">
                    <!-- Nombre del equipo -->
                    <span class="font-bold text-brand-dark text-sm flex items-center gap-1.5 shrink-0">
                        🛡️ <?= htmlspecialchars($equipo['nombre']) ?>
                    </span>

                    <div class="flex items-center gap-3">
                        <!-- Insignia del Código de Invitación -->
                        <div class="flex items-center gap-1.5 bg-white px-2.5 py-1 rounded border border-slate-200 shadow-sm">
                            <span class="text-[11px] font-semibold text-slate-500">Código:</span>
                            <code class="text-indigo-600 font-mono font-bold text-xs">
                                <?= htmlspecialchars($equipo['codigo_invitacion'] ?? 'SIN-CODIGO') ?>
                            </code>
                        </div>

                        <!-- Botón de Eliminar -->
                        <form action="/torneos/<?= $torneo['id'] ?>/equipos/<?= $equipo['id'] ?>/delete" method="POST" onsubmit="return confirm('¿Deseas eliminar este equipo?');">
                            <button type="submit" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded transition text-xs font-bold" title="Eliminar equipo">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

    <!-- TARJETA: Generar Fixture -->
    <div class="bg-brand-dark p-6 rounded-xl shadow-md border border-brand-deep flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-brand-mint font-bold text-lg">Generar Fixture / Llaves</h3>
            <p class="text-slate-400 text-xs mt-0.5">
                <?php if ($torneo['formato'] === 'eliminatoria'): ?>
                    Para eliminación directa necesitas registrar exactamente <strong>2, 4, 8, 16 o 32</strong> equipos.
                <?php else: ?>
                    Asegúrate de haber cargado todos los equipos antes de generar el fixture.
                <?php endif; ?>
            </p>
        </div>
        
        <div class="flex flex-wrap gap-3 w-full sm:w-auto">
            <a href="/torneos/<?= $torneo['id'] ?>/fixture" class="flex-1 sm:flex-none text-center bg-brand-emerald hover:bg-brand-medium text-white font-bold px-5 py-2.5 rounded-lg text-sm transition shadow">
                👁️ Ver Fixture
            </a>
            
            <form action="/torneos/<?= $torneo['id'] ?>/fixture/generar" method="POST" class="flex-1 sm:flex-none">
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold px-5 py-2.5 rounded-lg text-sm transition shadow">
                    🔄 Generar Fixture
                </button>
            </form>
        </div>
    </div>
</div>