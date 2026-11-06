{{-- Daftarkan service worker + tombol "Pasang Aplikasi" (Android/Chrome) --}}
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {});
        });
    }
    (function () {
        var deferred = null, btn = document.getElementById('btnInstall');
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault(); deferred = e;
            if (btn) btn.classList.remove('d-none');
        });
        if (btn) btn.addEventListener('click', function () {
            if (!deferred) return;
            deferred.prompt();
            deferred.userChoice.then(function () { deferred = null; btn.classList.add('d-none'); });
        });
        window.addEventListener('appinstalled', function () { if (btn) btn.classList.add('d-none'); });
    })();
</script>
