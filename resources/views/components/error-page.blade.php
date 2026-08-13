@props(['code', 'title', 'message'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $code }} — {{ $title }}</title>

        <x-theme-init-script />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-content antialiased bg-background">
        <div class="min-h-screen flex flex-col items-center justify-center px-6 text-center">
            <a href="/" class="flex items-center gap-2 mb-8">
                <x-application-logo class="w-10 h-10 fill-current text-primary" />
                <span class="text-lg font-semibold text-content">AutoGest</span>
            </a>

            <div class="bg-surface shadow-soft rounded-ui-xl p-10 max-w-md w-full">
                <p class="text-sm font-semibold text-primary tracking-wide">Erreur {{ $code }}</p>
                <h1 class="mt-2 text-2xl font-bold text-content">{{ $title }}</h1>
                <p class="mt-3 text-sm text-content-secondary">{{ $message }}</p>

                <div class="mt-8 flex items-center justify-center gap-3">
                    {{ $actions ?? '' }}
                </div>
            </div>
        </div>
    </body>
</html>
