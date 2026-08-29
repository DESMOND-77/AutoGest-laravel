@props(['name' => '', 'src' => null, 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'h-7 w-7 text-xs',
        'md' => 'h-9 w-9 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'rounded-full object-cover '.$sizeClass]) }} />
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-primary font-semibold text-primary-content '.$sizeClass]) }}>
        {{ $initial }}
    </span>
@endif
