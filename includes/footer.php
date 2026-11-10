</main>
<footer class="border-t border-slate-200/80 bg-white/60 py-6 text-center text-xs text-slate-500 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="font-medium text-slate-700">
            <?= h(liga()['nama_liga']) ?> <span class="text-slate-400 font-normal">&middot; Musim <?= h(liga()['musim']) ?></span>
        </div>
        <div class="text-slate-400">
            Sistem Manajemen Turnamen Dam Batu
        </div>
    </div>
</footer>
<script src="<?= h(url('assets/js/app.js')) ?>"></script>
</body>
</html>
