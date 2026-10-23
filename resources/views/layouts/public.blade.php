@php
    $htmlAttrs = collect($uiAttributes ?? [])
        ->map(fn ($value, $key) => $key.'="'.e($value).'"')
        ->implode(' ');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! $htmlAttrs !!}>
<head>
    @include('layouts.partials.head')
</head>
<body class="min-h-dvh text-[15px] text-ink antialiased">
    <div class="app-canvas" aria-hidden="true"></div>
    @php $user = auth()->user(); @endphp
    <header class="sticky top-0 z-40 border-b border-line/70 bg-[var(--surface-panel)]/85 backdrop-blur-sm">
        <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 rounded-sm focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                <x-brand.logo size="sm" />
                <span class="text-[15px] font-bold tracking-tight text-ink">Auto-GestBoard</span>
            </a>

            <nav aria-label="Navigation principale" class="flex items-center gap-2">
                <a href="{{ url('/') }}#modules" class="hidden rounded-pill px-3 py-2 text-[13.5px] font-medium text-ink-2 hover:text-ink sm:inline-flex">Fonctionnalités</a>
                <a href="{{ url('/') }}#tarifs" class="hidden rounded-pill px-3 py-2 text-[13.5px] font-medium text-ink-2 hover:text-ink sm:inline-flex">Tarifs</a>
                @auth
                    <x-ui.button :href="route('dashboard')" size="sm" icon-right="arrow-right">Mon espace</x-ui.button>
                @else
                    <x-ui.button :href="route('login')" size="sm" variant="ghost">Connexion</x-ui.button>
                    <x-ui.button :href="route('register')" size="sm">Créer mon auto-école</x-ui.button>
                @endauth
            </nav>
        </div>
    </header>

    <main id="main" class="min-h-[60dvh]">{{ $slot }}</main>

    <footer class="border-t border-line/70 bg-[var(--surface-panel)]/60">
        <div class="mx-auto grid w-full max-w-6xl gap-6 px-4 py-10 sm:grid-cols-3 sm:px-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <x-brand.logo size="sm" />
                    <span class="text-sm font-bold text-ink">Auto-GestBoard</span>
                </div>
                <p class="mt-2 max-w-xs text-[13px] text-ink-2">Gestion d’auto-école au Gabon : élèves, planning, finance, flotte et boutique dans un seul outil.</p>
            </div>
            <nav aria-label="Liens du site" class="text-[13px]">
                <p class="text-eyebrow">Produit</p>
                <ul class="mt-2 space-y-1.5 text-ink-2">
                    <li><a href="{{ url('/') }}#modules" class="hover:text-ink">Fonctionnalités</a></li>
                    <li><a href="{{ url('/') }}#parcours" class="hover:text-ink">Parcours élève</a></li>
                    <li><a href="{{ url('/') }}#tarifs" class="hover:text-ink">Tarifs</a></li>
                    <li><a href="{{ url('/') }}#faq" class="hover:text-ink">Questions fréquentes</a></li>
                </ul>
            </nav>
            <div class="text-[13px]">
                <p class="text-eyebrow">Compte</p>
                <ul class="mt-2 space-y-1.5 text-ink-2">
                    <li><a href="{{ route('login') }}" class="hover:text-ink">Se connecter</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-ink">Créer un établissement</a></li>
                    <li><a href="{{ route('password.request') }}" class="hover:text-ink">Mot de passe oublié</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-line/70 px-4 py-4 sm:px-6">
            <p class="mx-auto w-full max-w-6xl text-[12px] text-ink-3">
                © {{ now()->year }} Auto-GestBoard · Fait pour les auto-écoles du Gabon · Montants en FCFA
            </p>
        </div>
    </footer>
</body>
</html>
