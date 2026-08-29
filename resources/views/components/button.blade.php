@props([
    'variant' => 'primary',   // primary | secondary | ghost | danger
    'href' => null,
    'type' => 'submit',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-ui-sm text-sm font-semibold '
        .'focus:outline-none focus-visible:shadow-inset-focus disabled:opacity-50 disabled:pointer-events-none transition';

    $variants = [
        'primary' => 'bg-primary text-primary-content shadow-soft-sm hover:shadow-soft active:shadow-inset',
        'secondary' => 'bg-transparent border border-secondary text-secondary hover:bg-secondary/5 active:shadow-inset',
        'ghost' => 'bg-transparent font-medium text-content-secondary hover:text-content hover:bg-surface-elevated',
        'danger' => 'bg-danger text-white shadow-soft-sm hover:shadow-soft active:shadow-inset',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</{{ $tag }}>
