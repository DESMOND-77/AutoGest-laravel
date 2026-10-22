@php
    $days = collect(range(0, 6))->map(fn ($i) => $week->copy()->startOfWeek()->addDays($i));
    $byDay = $sessions->groupBy(fn ($session) => $session->scheduled_date?->toDateString());
    $planned = $sessions->filter(fn ($s) => $s->presence?->value === 'planned')->count();
@endphp

<x-app-layout :title="'Mon agenda'">
    <x-layout.page-header
        title="Mon agenda"
        :description="'Semaine du '.$week->copy()->startOfWeek()->format('d/m').' au '.$week->copy()->endOfWeek()->format('d/m/Y').' · '.$sessions->count().' séance(s)'"
        :breadcrumbs="[['label' => 'Ma journée', 'href' => route('moniteur.dashboard')], ['label' => 'Mon agenda']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('moniteur.agenda', ['week' => $week->copy()->subWeek()->toDateString()])" variant="secondary" icon="chevron-left">Semaine précédente</x-ui.button>
            <x-ui.button :href="route('moniteur.agenda', ['week' => now()->startOfWeek()->toDateString()])" variant="secondary">Cette semaine</x-ui.button>
            <x-ui.button :href="route('moniteur.agenda', ['week' => $week->copy()->addWeek()->toDateString()])" variant="secondary" icon-right="chevron-right">Semaine suivante</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-3xl space-y-3">
        @if ($planned > 0)
            <x-ui.alert tone="info" title="{{ $planned }} présence(s) à marquer cette semaine">
                Utilisez le bouton de présence sur chaque séance passée. Une séance non marquée reste « prévue » et ne compte pas dans les heures de conduite.
            </x-ui.alert>
        @endif

        @foreach ($days as $day)
            @php $daySessions = collect($byDay[$day->toDateString()] ?? [])->sortBy('starts_at'); @endphp
            <section class="material-solid overflow-hidden">
                <header class="flex items-center justify-between gap-2 border-b border-line px-4 py-2.5">
                    <h2 class="text-[14px] font-semibold text-ink">
                        {{ ucfirst($day->locale('fr')->isoFormat('dddd D MMMM')) }}
                        @if ($day->isToday())
                            <x-ui.badge tone="accent" class="ml-1">Aujourd’hui</x-ui.badge>
                        @endif
                    </h2>
                    <span class="text-[12px] text-ink-3">{{ $daySessions->count() }} séance(s)</span>
                </header>

                @if ($daySessions->isEmpty())
                    <p class="px-4 py-5 text-[13px] text-ink-3">Aucune séance.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($daySessions as $session)
                            <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                                <span class="w-24 shrink-0 text-[13px] font-semibold tabular-nums text-ink">
                                    {{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13.5px] font-medium text-ink">{{ $session->student?->fullName() }}</p>
                                    <p class="truncate text-[12px] text-ink-3">
                                        {{ $session->type?->label() }} · {{ $session->vehicle?->plate ?? 'sans véhicule' }}{{ $session->location ? ' · '.$session->location : '' }}
                                    </p>
                                </div>
                                <x-domain.presence-toggle :session="$session" />
                                <x-ui.button :href="route('moniteur.eleves.feuille-route', $session->student)" size="sm" variant="ghost" icon-right="chevron-right">Feuille de route</x-ui.button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach

        @if ($sessions->isEmpty())
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="calendar" title="Aucune séance cette semaine" description="Votre planning apparaît ici dès que l’administration vous affecte des séances." />
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
