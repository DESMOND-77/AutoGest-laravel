@props(['variant' => 'info'])

@php
    $variants = [
        'success' => ['classes' => 'bg-success/10 text-success', 'icon' => 'document-check'],
        'warning' => ['classes' => 'bg-warning/10 text-warning', 'icon' => 'exclamation-triangle'],
        'danger' => ['classes' => 'bg-danger/10 text-danger', 'icon' => 'exclamation-triangle'],
        'info' => ['classes' => 'bg-info/10 text-info', 'icon' => 'bell'],
    ];

    $config = $variants[$variant] ?? $variants['info'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-ui-md p-4 text-sm '.$config['classes']]) }}>
    <x-icon :name="$config['icon']" class="w-5 h-5 shrink-0 mt-0.5" />
    <div class="min-w-0">{{ $slot }}</div>
</div>
