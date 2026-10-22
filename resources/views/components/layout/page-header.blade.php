@props(['title', 'description' => null, 'badge' => null, 'breadcrumbs' => []])

<header {{ $attributes->class(['mb-5 space-y-3']) }}>
    @if (count($breadcrumbs))
        <x-ui.breadcrumbs :items="$breadcrumbs" />
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-page-title text-ink">{{ $title }}</h1>
                @if ($badge)
                    <x-ui.badge tone="neutral">{{ $badge }}</x-ui.badge>
                @endif
            </div>
            @if ($description)
                <p class="mt-1 max-w-2xl text-sm text-ink-2">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    @isset($tabs)
        <div>{{ $tabs }}</div>
    @endisset
</header>
