@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
    $next = $nextSession ?? null;
    $countdown = null;
    if ($next) {
        $start = \Illuminate\Support\Carbon::parse($next->scheduled_date->toDateString().' '.$next->starts_at);
        $countdown = now()->diffInMinutes($start, false);
    }
@endphp

<x-app-layout :title="'Ma journée'">
    <x-layout.page-header
        :title="$greeting.', '.explode(' ', auth()->user()->name)[0]"
        :description="now()->locale('fr')->translatedFormat('l j F Y').' · '.count($todaysSessions ?? []).' séance(s) aujourd’hui · '.($unmarkedCount ?? 0).' présence(s) à marquer.'"
    >
        <x-slot:actions>
            <x-ui.button :href="route('moniteur.agenda')" variant="secondary" icon="calendar">Mon agenda</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-2xl space-y-4">
        @if (! ($instructor ?? null))
            <x-ui.alert tone="warning" title="Profil moniteur incomplet">
                Votre compte n’est pas encore rattaché à une fiche moniteur. Contactez la direction de votre établissement pour recevoir vos élèves et votre planning.
            </x-ui.alert>
        @endif

        {{-- Prochaine séance --}}
        @if ($next)
            <section class="material-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-eyebrow">Prochaine séance</p>
                        <p class="mt-1 text-[22px] font-bold leading-tight text-ink">
                            <x-ui.date :value="$next->scheduled_date" format="short" /> · {{ \Illuminate\Support\Carbon::parse($next->starts_at)->format('H:i') }}
                        </p>
                    </div>
                    @if ($countdown !== null)
                        <x-ui.badge :tone="$countdown <= 60 ? 'warning' : 'accent'" :dot="true">
                            {{ $countdown <= 0 ? 'En cours' : 'Dans '.$countdown.' min' }}
                        </x-ui.badge>
                    @endif
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-[12px] uppercase tracking-wide text-ink-3">Élève</dt>
                        <dd class="font-medium text-ink">{{ $next->student?->fullName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[12px] uppercase tracking-wide text-ink-3">Type</dt>
                        <dd class="font-medium text-ink">{{ $next->type?->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-[12px] uppercase tracking-wide text-ink-3">Lieu</dt>
                        <dd class="font-medium text-ink">{{ $next->location ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[12px] uppercase tracking-wide text-ink-3">Véhicule</dt>
                        <dd class="font-medium text-ink">{{ $next->vehicle?->plate ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($next->student)
                    <x-ui.button :href="route('moniteur.eleves.feuille-route', $next->student)" class="mt-4 w-full" icon="clipboard-list" size="lg">
                        Feuille de route de l’élève
                    </x-ui.button>
                @endif
            </section>
        @endif

        {{-- Séances du jour --}}
        <section aria-labelledby="today-title">
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 id="today-title" class="text-section-title text-ink">Séances du jour</h2>
                <span class="text-[12.5px] text-ink-3">{{ count($todaysSessions ?? []) }} séance(s)</span>
            </div>

            @if (count($todaysSessions ?? []) === 0)
                <x-ui.card variant="solid">
                    <x-ui.empty-state icon="calendar" title="Aucune séance aujourd’hui" description="Votre agenda est libre. Vos disponibilités aident la direction à planifier." class="py-8">
                        <x-slot:actions>
                            <x-ui.button :href="route('moniteur.agenda')" variant="secondary" size="sm" icon="calendar">Voir mon agenda</x-ui.button>
                        </x-slot:actions>
                    </x-ui.empty-state>
                </x-ui.card>
            @else
                <div class="space-y-3">
                    @foreach ($todaysSessions as $session)
                        <div class="material-solid p-4">
                            <x-domain.session-card variant="row" :session="$session" :show-student="true" class="border-0 p-0 shadow-none">
                                <div class="mt-3 flex items-center justify-between gap-2 border-t border-line pt-3">
                                    <x-domain.presence-toggle :session="$session" size="lg" />
                                    @if ($session->student)
                                        <x-ui.button :href="route('moniteur.eleves.feuille-route', $session->student)" size="sm" variant="ghost" icon-right="chevron-right">Feuille de route</x-ui.button>
                                    @endif
                                </div>
                            </x-domain.session-card>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Alertes véhicules assignés --}}
        @if (count($assignedVehicles ?? []) > 0)
            <section class="material-solid p-4">
                <h2 class="text-section-title text-ink">Véhicules assignés</h2>
                <ul class="mt-2 space-y-2">
                    @foreach ($assignedVehicles as $vehicle)
                        <li class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-medium text-ink">{{ $vehicle->plate }}</span>
                            <x-domain.expiry-chip :date="$vehicle->technical_inspection_expires_at" label="Contrôle technique" icon="wrench" />
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('students.index') }}" class="material-solid flex flex-col gap-1 p-4 hover:bg-hover">
                <x-ui.icon name="graduation-cap" class="w-5 h-5 text-accent" />
                <span class="text-sm font-medium text-ink">Mes élèves</span>
                <span class="text-[12.5px] text-ink-2">{{ $assignedStudentsCount ?? 0 }} élève(s) suivis</span>
            </a>
            <a href="{{ route('instructors.index') }}" class="material-solid flex flex-col gap-1 p-4 hover:bg-hover">
                <x-ui.icon name="calendar-clock" class="w-5 h-5 text-accent" />
                <span class="text-sm font-medium text-ink">Disponibilités</span>
                <span class="text-[12.5px] text-ink-2">Déclarer mes plages horaires</span>
            </a>
        </div>
    </div>
</x-app-layout>
