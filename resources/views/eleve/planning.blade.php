@php
    $days = collect(range(0, 6))->map(fn ($i) => $week->copy()->startOfWeek()->addDays($i));
    $byDay = $sessions->groupBy(fn ($session) => $session->scheduled_date?->toDateString());
    $next = $sessions->filter(fn ($s) => $s->scheduled_date?->isFuture() ?? false)->sortBy('starts_at')->first();
@endphp

<x-app-layout :title="'Mon planning'">
    <x-layout.page-header
        title="Mon planning"
        :description="'Semaine du '.$week->copy()->startOfWeek()->format('d/m').' au '.$week->copy()->endOfWeek()->format('d/m/Y')"
        :breadcrumbs="[['label' => 'Accueil', 'href' => route('eleve.dashboard')], ['label' => 'Planning']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('eleve.planning', ['week' => $week->copy()->subWeek()->toDateString()])" variant="secondary" size="sm" icon="chevron-left" label="Semaine précédente" />
            <x-ui.button :href="route('eleve.planning', ['week' => now()->startOfWeek()->toDateString()])" variant="secondary" size="sm">Cette semaine</x-ui.button>
            <x-ui.button :href="route('eleve.planning', ['week' => $week->copy()->addWeek()->toDateString()])" variant="secondary" size="sm" icon-right="chevron-right" label="Semaine suivante" />
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-2xl space-y-3">
        @if ($student === null)
            <x-ui.alert tone="warning" title="Profil élève introuvable">
                Votre compte n’est pas encore rattaché à une fiche élève. Contactez le secrétariat de votre auto-école.
            </x-ui.alert>
        @endif

        @if ($next)
            <x-ui.card title="Prochaine séance" variant="panel">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-lg font-bold text-ink"><x-ui.date :value="$next->scheduled_date" format="long" /></p>
                        <p class="mt-0.5 text-[14px] text-ink-2">
                            {{ substr($next->starts_at, 0, 5) }}–{{ substr($next->ends_at, 0, 5) }} · {{ $next->type?->label() }}
                        </p>
                        <p class="mt-0.5 text-[13px] text-ink-3">
                            Moniteur : {{ $next->instructor?->name ?? '—' }}{{ $next->location ? ' · '.$next->location : '' }}
                        </p>
                    </div>
                    <x-ui.status :value="$next->presence" />
                </div>
            </x-ui.card>
        @endif

        @foreach ($days as $day)
            @php $daySessions = collect($byDay[$day->toDateString()] ?? [])->sortBy('starts_at'); @endphp
            <section class="material-solid overflow-hidden">
                <header class="flex items-center justify-between gap-2 border-b border-line px-4 py-2.5">
                    <h2 class="text-[13.5px] font-semibold text-ink">
                        {{ ucfirst($day->locale('fr')->isoFormat('dddd D MMMM')) }}
                        @if ($day->isToday())
                            <x-ui.badge tone="accent" class="ml-1">Aujourd’hui</x-ui.badge>
                        @endif
                    </h2>
                    @if ($daySessions->isNotEmpty())
                        <span class="text-[12px] text-ink-3">{{ $daySessions->count() }} séance(s)</span>
                    @endif
                </header>
                @if ($daySessions->isEmpty())
                    <p class="px-4 py-4 text-[13px] text-ink-3">Aucune séance.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($daySessions as $session)
                            <li class="flex items-center gap-3 px-4 py-3">
                                <span class="w-24 shrink-0 text-[13px] font-semibold tabular-nums text-ink">
                                    {{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13.5px] text-ink">{{ $session->type?->label() }}</p>
                                    <p class="truncate text-[12px] text-ink-3">{{ $session->instructor?->name ?? '—' }}{{ $session->location ? ' · '.$session->location : '' }}</p>
                                </div>
                                <x-ui.status :value="$session->presence" compact />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach

        <p class="px-1 text-[12px] text-ink-3">
            Une séance annulée apparaît avec le statut « Annulée ». Contactez votre auto-école pour la replanifier.
        </p>
    </div>
</x-app-layout>
