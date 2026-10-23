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
<body data-auth="1" class="app-canvas text-[15px] text-ink antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-solid focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-ink focus:shadow-[var(--focus-ring)]">
        Aller au contenu principal
    </a>

    <div x-data x-init="$store.shell.init()" class="min-h-dvh">
        <x-layout.sidebar />

        <div class="transition-[padding] duration-[var(--dur-2)] lg:pl-[var(--sidebar-w)]"
             :class="$store.shell.collapsed ? 'lg:pl-[var(--sidebar-w-collapsed)]' : 'lg:pl-[var(--sidebar-w)]'">
            <x-layout.topbar />

            <main id="main" tabindex="-1" class="mx-auto w-full max-w-[1480px] px-4 pb-28 pt-4 sm:px-6 lg:px-8 lg:pb-10">
                @if (session('status'))
                    <x-ui.alert tone="success" class="mb-4" dismissible>{{ session('status') }}</x-ui.alert>
                @endif
                {{ $slot }}
            </main>
        </div>

        <x-layout.dock />
    </div>

    <div id="overlays"></div>
    <x-layout.toasts />
    <x-layout.command-palette />
</body>
</html>
