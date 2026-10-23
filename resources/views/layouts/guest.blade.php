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
<body class="app-canvas min-h-dvh text-[15px] text-ink antialiased">
    <div class="grid min-h-dvh lg:grid-cols-[1fr_minmax(420px,520px)]">
        {{-- Volet de gauche : promesse produit, masqué sur mobile --}}
        <aside class="relative hidden overflow-hidden border-r border-line/60 p-10 lg:flex lg:flex-col lg:justify-between">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5 rounded-sm focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                <x-brand.logo size="sm" />
                <span class="text-[15px] font-bold tracking-tight text-ink">Auto-GestBoard</span>
            </a>

            <div class="max-w-md">
                <h1 class="text-3xl font-bold leading-tight tracking-tight text-ink">Toute votre auto-école,<br>de l’inscription au permis.</h1>
                <p class="mt-3 text-[14.5px] text-ink-2">
                    Dossiers élèves, planning des moniteurs, encaissements, flotte et boutique — sans tableur ni cahier.
                </p>
                <ul class="mt-6 space-y-2.5 text-[13.5px] text-ink-2">
                    @foreach (['Parcours élève en 15 étapes suivies', 'Planning anti-conflit moniteur / véhicule', 'Factures et journal de caisse en FCFA', 'Accès élève : planning, dossier, entraînement au code'] as $line)
                        <li class="flex items-start gap-2">
                            <x-ui.icon name="circle-check" class="mt-0.5 w-4 h-4 shrink-0 text-success" />
                            <span>{{ $line }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="text-[12px] text-ink-3">© {{ now()->year }} Auto-GestBoard · Libreville, Gabon</p>
        </aside>

        {{-- Formulaire --}}
        <main id="main" class="flex flex-col justify-center px-5 py-10 sm:px-8">
            <div class="mx-auto w-full max-w-sm">
                <a href="{{ url('/') }}" class="mb-8 inline-flex items-center gap-2 lg:hidden">
                    <x-brand.logo size="sm" />
                    <span class="text-sm font-bold text-ink">Auto-GestBoard</span>
                </a>
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
