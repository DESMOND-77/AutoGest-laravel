@props(['items' => [], 'empty' => 'Aucune pièce requise pour le moment.'])

@if (count($items) === 0)
    <p class="py-4 text-sm text-ink-3">{{ $empty }}</p>
@else
    <ul {{ $attributes->class(['divide-y divide-line']) }}>
        @foreach ($items as $item)
            <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-ink">{{ $item['label'] }}</p>
                    @if (! empty($item['description']))
                        <p class="text-[12.5px] text-ink-2">{{ $item['description'] }}</p>
                    @endif
                    @if (! empty($item['reason']))
                        <p class="mt-1 flex items-center gap-1 text-[12px] font-medium text-danger">
                            <x-ui.icon name="circle-alert" class="w-3.5 h-3.5" /> Motif : {{ $item['reason'] }}
                        </p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if (isset($item['status']))
                        <x-ui.status :value="$item['status']" />
                    @endif
                    @if (! empty($item['action_url']))
                        <x-ui.button :href="$item['action_url']" variant="secondary" size="sm">{{ $item['action_label'] ?? 'Ouvrir' }}</x-ui.button>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
