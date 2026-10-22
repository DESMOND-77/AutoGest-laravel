@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="mb-4 grid size-14 place-items-center rounded-full bg-accent-soft text-accent" aria-hidden="true">
        <x-ui.icon :name="$icon" class="w-7 h-7" />
    </span>
    <h3 class="text-section-title text-ink">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1.5 max-w-md text-sm text-ink-2">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
