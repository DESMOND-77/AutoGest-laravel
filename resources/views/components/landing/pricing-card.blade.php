@props(['name', 'price', 'period', 'description', 'features', 'cta', 'href', 'featured' => false])

<div {{ $attributes->merge(['class' => 'flex flex-col rounded-2xl p-8 ' . ($featured ? 'border-2 border-route bg-paper shadow-[0_20px_60px_-20px_rgba(30,64,175,0.35)] lg:-translate-y-4' : 'border border-line bg-paper')]) }}>
    @if ($featured)
        <span class="mb-4 inline-flex w-fit items-center rounded-full bg-signal-600 px-3 py-1 font-display text-xs font-semibold uppercase tracking-widest text-white">
            Le plus choisi
        </span>
    @endif

    <h3 class="font-display text-2xl font-semibold uppercase tracking-wide text-ink">{{ $name }}</h3>
    <p class="mt-2 text-sm leading-relaxed text-slate">{{ $description }}</p>

    <p class="mt-6 flex items-baseline gap-1">
        <span class="font-mono text-4xl font-medium text-ink">{{ $price }}</span>
        <span class="text-sm text-slate">{{ $period }}</span>
    </p>

    <a href="{{ $href }}" class="mt-6 inline-flex items-center justify-center rounded-lg px-5 py-3 font-display text-sm font-semibold uppercase tracking-wide transition {{ $featured ? 'bg-route text-white hover:bg-route-700' : 'border border-ink text-ink hover:bg-ink hover:text-white' }}">
        {{ $cta }}
    </a>

    <ul class="mt-8 space-y-3 text-sm text-slate">
        @foreach ($features as $feature)
            <li class="flex items-start gap-2">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-route" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span>{{ $feature }}</span>
            </li>
        @endforeach
    </ul>
</div>
