@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-4 mb-6']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-content truncate">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-content-secondary">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</div>
