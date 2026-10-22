@props(['name' => null, 'id' => null, 'checked' => false, 'label', 'description' => null, 'value' => '1'])

@php
    $fieldId = $id ?? ($name ?? 'switch').'-'.uniqid();
@endphp

<div {{ $attributes->class(['flex items-start justify-between gap-4 py-1']) }}>
    <div class="min-w-0">
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-ink">{{ $label }}</label>
        @if ($description)
            <p class="mt-0.5 text-[12.5px] text-ink-2">{{ $description }}</p>
        @endif
    </div>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="relative inline-flex shrink-0 cursor-pointer items-center">
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="checkbox"
            value="{{ $value }}"
            role="switch"
            @checked($checked)
            class="peer size-6 cursor-pointer appearance-none rounded-pill border border-control bg-inset checked:border-accent checked:bg-accent focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
        >
        <span class="pointer-events-none absolute left-1 top-1/2 size-4 -translate-y-1/2 rounded-full bg-ink-3 transition-transform duration-[var(--dur-1)] peer-checked:translate-x-2 peer-checked:bg-[var(--on-accent)]" aria-hidden="true"></span>
        <span class="sr-only">{{ $label }}</span>
    </label>
</div>
