<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="color-scheme" content="{{ ($uiAttributes['data-mode'] ?? 'light') === 'dark' ? 'dark light' : 'light dark' }}">
<meta name="theme-color" content="{{ ($uiAttributes['data-mode'] ?? 'light') === 'dark' ? '#0B0F1A' : '#F6F5FB' }}">
<title>{{ isset($title) && $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
<meta name="description" content="{{ $description ?? config('landing.meta.description', 'Gestion complète d’auto-école') }}">

{{-- Pré-peinture : applique le thème mémorisé avant le premier rendu (évite le flash). --}}
<script>
    (function () {
        try {
            var root = document.documentElement;
            var stored = JSON.parse(localStorage.getItem('ui_prefs') || '{}');
            if (!stored.theme && root.dataset.uiPrefs) stored = JSON.parse(root.dataset.uiPrefs);
            if (stored.theme && stored.theme !== 'auto') {
                var light = { aurore: 1, ocean: 1, sable: 1 };
                root.dataset.theme = stored.theme;
                root.dataset.mode = light[stored.theme] ? 'light' : 'dark';
            }
            if (stored.accent) root.dataset.accent = stored.accent;
            if (stored.density) root.dataset.density = stored.density === 'compact' ? 'compact' : 'comfortable';
            if (stored.perf === 'lite') root.dataset.perf = 'lite';
        } catch (e) {}
    })();
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
