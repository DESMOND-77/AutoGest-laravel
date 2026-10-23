@props([
    'variant' => 'full',   // full | icon | mono
    'tone' => 'auto',      // auto | light | navy | green
    'size' => 'md',        // sm | md | lg
])

@php
    $name = config('app.name', 'Auto-GestBoard');
    $heights = ['sm' => 'h-7', 'md' => 'h-8', 'lg' => 'h-11'];
    $h = $heights[$size] ?? $heights['md'];
    $assets = [
        'icon' => 'images/brand/icon.jpg',
        'mono' => 'images/brand/logo-mono-blue.jpg',
        'light' => 'images/brand/logo-horizontal-light.jpg',
        'navy' => 'images/brand/logo-horizontal-on-navy.jpg',
        'green' => 'images/brand/logo-horizontal-on-green.jpg',
    ];
@endphp

@if ($variant === 'icon')
    <span {{ $attributes->class(['inline-flex items-center justify-center overflow-hidden rounded-sm bg-solid p-0.5', $h, 'aspect-square']) }}>
        <img src="{{ asset($assets['icon']) }}" alt="{{ $name }}" width="32" height="32" class="h-full w-full object-contain" decoding="async">
    </span>
@elseif ($variant === 'mono')
    <img src="{{ asset($assets['mono']) }}" alt="{{ $name }}" {{ $attributes->class(['w-auto object-contain', $h]) }} decoding="async">
@elseif ($tone === 'light')
    <img src="{{ asset($assets['light']) }}" alt="{{ $name }}" {{ $attributes->class(['w-auto object-contain', $h]) }} decoding="async">
@elseif ($tone === 'navy')
    <img src="{{ asset($assets['navy']) }}" alt="{{ $name }}" {{ $attributes->class(['w-auto object-contain', $h]) }} decoding="async">
@elseif ($tone === 'green')
    <img src="{{ asset($assets['green']) }}" alt="{{ $name }}" {{ $attributes->class(['w-auto object-contain', $h]) }} decoding="async">
@else
    {{-- Sur surface thémée : le lockup clair en mode clair, le lockup navy en mode sombre. --}}
    <img src="{{ asset($assets['light']) }}" alt="{{ $name }}" {{ $attributes->class(['brand-light w-auto object-contain', $h]) }} decoding="async">
    <img src="{{ asset($assets['navy']) }}" alt="{{ $name }}" {{ $attributes->class(['brand-dark w-auto object-contain', $h]) }} decoding="async">
@endif
