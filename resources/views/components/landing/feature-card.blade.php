@props(['title', 'description'])

<div {{ $attributes->merge(['class' => 'group relative rounded-2xl border border-line bg-paper p-6 transition hover:-translate-y-0.5 hover:shadow-[0_8px_30px_-12px_rgba(11,18,32,0.15)]']) }}>
    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-route-50 text-route transition group-hover:bg-route group-hover:text-white">
        {{ $icon }}
    </div>
    <h3 class="mt-5 font-display text-xl font-semibold uppercase tracking-wide text-ink">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-relaxed text-slate">{{ $description }}</p>
</div>
