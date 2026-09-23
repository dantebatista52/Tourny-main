<div class="max-w-md mx-auto my-10 p-6 bg-white rounded-xl border border-slate-200 shadow-sm">
    <h1 class="text-xl font-bold text-brand-dark mb-1">Unirse a un Equipo</h1>
    <p class="text-xs text-slate-500 mb-6">Ingresá el código de 6 caracteres que te compartió el delegado o capitán.</p>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg">
            <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <form action="/equipos/unirse" method="POST" class="space-y-4">
        <div>
            <label for="codigo_invitacion" class="block text-xs font-semibold text-slate-700 uppercase mb-1">Código de Fichaje</label>
            <input type="text" 
                   id="codigo_invitacion" 
                   name="codigo_invitacion" 
                   maxlength="6" 
                   placeholder="EJ: X7K9P2" 
                   required 
                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-center font-mono font-bold text-lg uppercase tracking-widest focus:outline-none focus:border-brand-mint">
        </div>

        <button type="submit" class="w-full py-2.5 bg-brand-mint text-slate-900 font-bold text-sm rounded-lg hover:bg-opacity-90 transition">
            Unirme al Equipo
        </button>
    </form>
</div>