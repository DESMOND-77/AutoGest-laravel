{{-- Runs before first paint (loaded in <head>, before any CSS) so the page
     never flashes the wrong theme. Mirrors the read side of the toggle in
     components/theme-toggle.blade.php — both must agree on the same
     localStorage key and the same "system" fallback. --}}
<script>
    (function () {
        var theme = localStorage.getItem('theme') || 'system';
        var isDark = theme === 'dark'
            || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', isDark);
    })();
</script>
