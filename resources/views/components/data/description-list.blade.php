@props(['items' => [], 'columns' => 2])

<dl {{ $attributes->class(['grid gap-x-6 gap-y-4', $columns === 2 ? 'sm:grid-cols-2' : '']) }}>
    @foreach ($items as $item)
        <div class="min-w-0">
            <dt class="text-[12px] font-semibold uppercase tracking-wide text-ink-3">{{ $item['label'] }}</dt>
            <dd class="mt-0.5 text-sm text-ink">{{ $item['value'] ?? '—' }}</dd>
        </div>
    @endforeach
    {{ $slot }}
</dl>
