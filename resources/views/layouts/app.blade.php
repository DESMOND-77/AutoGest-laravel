<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <x-theme-init-script />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-content">
        <div
            x-data="{
                mobileMenuOpen: false,
                collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            }"
            x-init="$watch('collapsed', value => localStorage.setItem('sidebarCollapsed', value))"
            class="min-h-screen bg-background"
        >
            @include('layouts.partials.sidebar')
            @include('layouts.partials.mobile-drawer')

            <div class="flex flex-col min-h-screen transition-[padding] duration-200" :class="collapsed ? 'lg:pl-28' : 'lg:pl-72'">
                @include('layouts.partials.topbar')

                <main class="flex-1 px-4 sm:px-6 pb-10">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
