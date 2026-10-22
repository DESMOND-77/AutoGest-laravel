@props(['steps' => [], 'label' => 'Entonnoir'])

@php
    $max = collect($steps)->max('count') ?: 1;
@endphp

<ol {{ $attributes->class(['space-y-2.5']) }} aria-label="{{ $label }}">
    @foreach ($steps as $step)
        <li class="flex items-center gap-3">
            <span class="w-24 shrink-0 text-[13px] font-medium text-ink-2">{{ $step['label'] }}</span>
            <div class="h-7 flex-1 overflow-hidden rounded-sm bg-hover">
                <div class="flex h-full items-center justify-end rounded-sm bg-accent px-2 text-[11px] font-semibold text-on-accent"
                     style="width: {{ max(8, round(($step['count'] ?? 0) / $max * 100)) }}%">
                    {{ $step['count'] ?? 0 }}
                </div>
            </div>
            <span class="w-14 shrink-0 text-right text-[12px] text-ink-3">{{ $step['sub'] ?? '' }}</span>
        </li>
    @endforeach
</ol>
