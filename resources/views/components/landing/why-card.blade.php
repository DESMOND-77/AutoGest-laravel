@props(['title', 'description'])

<div {{ $attributes->merge(['class' => 'flex items-start gap-4']) }}>
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-signal/10 text-signal-600">
        {{ $icon }}
    </div>
    <div>
        <h3 class="font-display text-lg font-semibold uppercase tracking-wide text-ink">{{ $title }}</h3>
        <p class="mt-1 text-sm leading-relaxed text-slate">{{ $description }}</p>
    </div>
</div>
