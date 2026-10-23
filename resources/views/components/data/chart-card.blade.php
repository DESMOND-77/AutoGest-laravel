@props(['title', 'description' => null, 'table' => null, 'empty' => false, 'emptyMessage' => 'Aucune donnée sur la période.'])

<x-ui.card :title="$title" :description="$description" variant="solid" padding="p-4">
    @if ($empty)
        <x-ui.empty-state icon="chart-bar" :title="$emptyMessage" description="Les données apparaîtront dès le premier enregistrement de la période." class="py-6" />
    @else
        {{ $slot }}
        @if ($table)
            <details class="mt-4 rounded-sm border border-line">
                <summary class="cursor-pointer px-3 py-2 text-[13px] font-medium text-ink-2 focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                    Voir les données sous forme de tableau
                </summary>
                <div class="border-t border-line">{{ $table }}</div>
            </details>
        @endif
    @endif
</x-ui.card>
