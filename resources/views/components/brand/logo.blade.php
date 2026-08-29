@props([
    'variant' => 'full',       // full | icon | mono
    'on' => 'light',           // light | navy | green  (only used for variant="full")
])

@php
    $name = config('app.name', 'Auto-GestBoard');

    // JPEG assets are opaque: each variant only reads well on its own
    // background. For variant="full" on a themeable surface we ship both
    // the light-bg and navy-bg lockups and let the .dark class pick.
    $icon = 'images/brand/icon.jpg';
    $mono = 'images/brand/logo-mono-blue.jpg';
    $onGreen = 'images/brand/logo-horizontal-on-green.jpg';
    $light = 'images/brand/logo-horizontal-light.jpg';
    $navy = 'images/brand/logo-horizontal-on-navy.jpg';
@endphp

@if ($variant === 'icon')
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center bg-white rounded-ui-sm p-0.5']) }}>
        <img src="{{ asset($icon) }}" alt="{{ $name }}" class="h-full w-full object-contain" />
    </span>
@elseif ($variant === 'mono')
    <img src="{{ asset($mono) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }} />
@elseif ($on === 'green')
    <img src="{{ asset($onGreen) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }} />
@elseif ($on === 'navy')
    <img src="{{ asset($navy) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }} />
@else
    {{-- Themed surface: light-bg lockup in light mode, navy-bg lockup in dark mode --}}
    <img src="{{ asset($light) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain block dark:hidden']) }} />
    <img src="{{ asset($navy) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain hidden dark:block']) }} />
@endif
