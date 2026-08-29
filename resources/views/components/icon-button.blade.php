@props([
    'icon',
    'label',
    'variant' => 'ghost',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex h-10 w-10 items-center justify-center rounded-ui-sm '
        .'focus:outline-none focus-visible:shadow-inset-focus disabled:opacity-50 disabled:pointer-events-none transition';

    $variants = [
        'primary' => 'bg-primary text-primary-content shadow-soft-sm hover:shadow-soft',
        'secondary' => 'bg-surface text-content-secondary shadow-soft-sm hover:text-content hover:shadow-soft',
        'ghost' => 'bg-transparent text-content-secondary hover:text-content hover:bg-surface-elevated',
        'danger' => 'bg-danger text-white shadow-soft-sm hover:shadow-soft',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['ghost']);
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    aria-label="{{ $label }}"
    {{ $attributes->merge(['class' => $classes]) }}
>
    <x-icon :name="$icon" class="w-5 h-5" />
</{{ $tag }}>
