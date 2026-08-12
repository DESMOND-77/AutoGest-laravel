@props(['number', 'title', 'description', 'last' => false])

<div class="relative flex flex-1 flex-col items-start">
    <div class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full border-2 border-signal bg-asphalt font-mono text-base font-medium text-signal">
        {{ $number }}
    </div>

    @unless ($last)
        <svg class="pointer-events-none absolute left-6 top-6 hidden h-0.5 w-full -translate-y-1/2 md:block" preserveAspectRatio="none" aria-hidden="true">
            <line x1="0" y1="0" x2="100%" y2="0" stroke="#F2790A" stroke-width="2" stroke-dasharray="6 6" class="motion-safe:animate-dash-drift" />
        </svg>
    @endunless

    <h3 class="mt-5 font-display text-lg font-semibold uppercase tracking-wide text-white">{{ $title }}</h3>
    <p class="mt-2 max-w-xs text-sm leading-relaxed text-white/60">{{ $description }}</p>
</div>
