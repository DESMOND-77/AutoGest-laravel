@php
    $sizes = ['sm' => 'w-4 h-4', 'md' => 'w-5 h-5', 'lg' => 'w-6 h-6', 'xl' => 'w-8 h-8'];
    $dimension = $sizes[$size] ?? $sizes['md'];
@endphp

<svg
    {{ $attributes->class(['shrink-0', $dimension]) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif
>
    @foreach ($paths() as $path)
        <path d="{{ $path }}" />
    @endforeach
</svg>
