@props([
    'action' => null,
    'search' => null,
    'searchName' => 'q',
    'placeholder' => 'Rechercher…',
    'chips' => [],
    'clearUrl' => null,
    'views' => [],
    'resultCount' => null,
    'label' => 'Filtres',
])

<div {{ $attributes->class(['space-y-3']) }}>
    <form method="GET" action="{{ $action ?? url()->current() }}" class="flex flex-wrap items-center gap-2" role="search">
        @foreach (request()->except([$searchName, 'page']) as $key => $value)
            @if (is_scalar($value) && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="relative min-w-0 flex-1 sm:max-w-xs">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-3">
                <x-ui.icon name="search" class="w-4 h-4" />
            </span>
            <label for="filter-search" class="sr-only">{{ $placeholder }}</label>
            <input
                id="filter-search"
                type="search"
                name="{{ $searchName }}"
                value="{{ $search }}"
                placeholder="{{ $placeholder }}"
                class="h-[var(--control-h)] w-full rounded-pill border border-control bg-inset pl-9 pr-3 text-sm text-ink placeholder:text-ink-3 focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
            >
        </div>

        {{ $slot }}

        <x-ui.button type="submit" variant="secondary" size="md" icon="filter">Filtrer</x-ui.button>

        @if ($resultCount !== null)
            <p class="ml-auto text-[13px] text-ink-2" aria-live="polite">{{ $resultCount }}</p>
        @endif
    </form>

    @if (count($views))
        <x-ui.tabs :items="$views" variant="underline" />
    @endif

    @if (count($chips))
        <div class="flex flex-wrap items-center gap-2" aria-label="{{ $label }} actifs">
            @foreach ($chips as $chip)
                <a href="{{ $chip['url'] ?? '#' }}" class="inline-flex items-center gap-1.5 rounded-pill border border-control/50 bg-solid px-2.5 py-1 text-[13px] text-ink hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                    <span class="text-ink-3">{{ $chip['label'] }} :</span>
                    <span class="font-medium">{{ $chip['value'] }}</span>
                    <x-ui.icon name="x" class="w-3.5 h-3.5 text-ink-3" />
                    <span class="sr-only">Retirer ce filtre</span>
                </a>
            @endforeach
            @if ($clearUrl)
                <a href="{{ $clearUrl }}" class="rounded-pill px-2 py-1 text-[13px] font-medium text-accent hover:underline focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                    Tout effacer
                </a>
            @endif
        </div>
    @endif
</div>
