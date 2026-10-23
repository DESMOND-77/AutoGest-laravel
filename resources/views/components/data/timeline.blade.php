@props(['items' => [], 'empty' => 'Aucune activité pour le moment.'])

@if (count($items) === 0)
    <p class="py-6 text-center text-sm text-ink-3">{{ $empty }}</p>
@else
    <ol {{ $attributes->class(['space-y-0']) }}>
        @foreach ($items as $item)
            <li class="relative flex gap-3 pb-4 last:pb-0">
                <div class="flex flex-col items-center">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $item['tone'] === 'danger' ? 'bg-danger-soft text-danger' : ($item['tone'] === 'success' ? 'bg-success-soft text-success' : 'bg-accent-soft text-accent') }}" aria-hidden="true">
                        <x-ui.icon :name="$item['icon'] ?? 'circle-dot'" class="w-4 h-4" />
                    </span>
                    @unless ($loop->last)
                        <span class="mt-1 w-px flex-1 bg-line" aria-hidden="true"></span>
                    @endunless
                </div>
                <div class="min-w-0 flex-1 pt-1">
                    <div class="flex flex-wrap items-baseline gap-x-2">
                        <p class="text-sm font-medium text-ink">{{ $item['title'] }}</p>
                        @if (! empty($item['at']))
                            <p class="text-[12px] text-ink-3"><x-ui.date :value="$item['at']" format="datetime" /></p>
                        @endif
                    </div>
                    @if (! empty($item['description']))
                        <p class="mt-0.5 text-[13px] text-ink-2">{{ $item['description'] }}</p>
                    @endif
                    @if (! empty($item['actor']))
                        <p class="mt-0.5 text-[12px] text-ink-3">Par {{ $item['actor'] }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
