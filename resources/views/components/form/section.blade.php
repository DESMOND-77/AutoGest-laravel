@props(['title', 'description' => null, 'id' => null])

<section {{ $attributes->class(['grid gap-4 border-b border-line py-6 first:pt-0 last:border-0 sm:grid-cols-3 sm:gap-8']) }} @if ($id) id="{{ $id }}" @endif>
    <div class="sm:col-span-1">
        <h2 class="text-section-title text-ink">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 max-w-md text-[13px] leading-relaxed text-ink-2">{{ $description }}</p>
        @endif
    </div>
    <div class="space-y-4 sm:col-span-2">{{ $slot }}</div>
</section>
