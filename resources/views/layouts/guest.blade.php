<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('images/brand/icon.jpg') }}">

        <x-theme-init-script />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-content antialiased bg-background">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <a href="/" class="flex items-center gap-2">
                <x-brand-logo variant="full" class="h-9 w-auto" />
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-surface shadow-soft overflow-hidden rounded-ui-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
