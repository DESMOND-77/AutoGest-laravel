@php
    $student = $student ?? null;
    $balance = $balance ?? 0;
    $totalDue = $totalDue ?? 0;
    $totalPaid = $totalPaid ?? 0;
    $progress = $totalDue > 0 ? round($totalPaid / $totalDue * 100) : 0;
@endphp

<x-app-layout :title="'Mon espace'">
    <x-layout.page-header
        :title="'Bonjour, '.explode(' ', auth()->user()->name)[0]"
        :description="now()->locale('fr')->translatedFormat('l j F Y')"
    >
        <x-slot:actions>
            <x-ui.button :href="route('quiz.play')" variant="secondary" icon="book-open">S’entraîner au code</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-2xl space-y-4">
        @if (! $student)
            <x-ui.alert tone="warning" title="Dossier introuvable">
                Votre compte n’est pas encore relié à une fiche élève. Contactez votre auto-école pour finaliser votre inscription.
            </x-ui.alert>
        @else
            {{-- Mon parcours --}}
            <section class="material-panel space-y-3 p-5">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="text-section-title text-ink">Mon parcours</h2>
                    <x-ui.badge tone="neutral">{{ $student->license_category?->label() ?? 'Permis' }}</x-ui.badge>
                </div>
                <x-domain.stage-stepper :stage="$student->lifecycle_stage" />
                <p class="text-[13px] leading-relaxed text-ink-2">
                    Étape actuelle : <strong class="text-ink">{{ $student->lifecycle_stage?->label() }}</strong>.
                    @if ($student->dossier_status)
                        Dossier : <strong class="text-ink">{{ $student->dossier_status->label() }}</strong>.
                    @endif
                    Les étapes sont mises à jour par votre auto-école : elles servent de repère de suivi.
                </p>
                <a href="{{ route('eleve.dossier.show') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-accent hover:underline">
                    Ce qu’il me reste à faire <x-ui.icon name="chevron-right" class="w-3.5 h-3.5" />
                </a>
            </section>

            {{-- Prochaine séance + planning --}}
            <section aria-labelledby="next-session-title">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h2 id="next-session-title" class="text-section-title text-ink">Mes prochaines séances</h2>
                    <a href="{{ route('eleve.planning') }}" class="text-[13px] font-medium text-accent hover:underline">Tout voir</a>
                </div>

                @if (($upcomingSessions ?? collect())->isEmpty())
                    <x-ui.card variant="solid">
                        <x-ui.empty-state icon="calendar" title="Aucune séance planifiée" description="Votre moniteur vous proposera un créneau. Vous le verrez apparaître ici." class="py-8" />
                    </x-ui.card>
                @else
                    <div class="space-y-3">
                        @foreach ($upcomingSessions as $session)
                            <article class="material-solid p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-ink">
                                            <x-ui.date :value="$session->scheduled_date" format="long" />
                                        </p>
                                        <p class="text-[13px] text-ink-2">
                                            {{ \Illuminate\Support\Carbon::parse($session->starts_at)->format('H:i') }} – {{ \Illuminate\Support\Carbon::parse($session->ends_at)->format('H:i') }}
                                            · {{ $session->type?->label() }}
                                        </p>
                                    </div>
                                    <x-ui.status :value="$session->presence" />
                                </div>
                                <dl class="mt-2.5 grid grid-cols-2 gap-2 text-[12.5px]">
                                    <div>
                                        <dt class="text-ink-3">Moniteur</dt>
                                        <dd class="font-medium text-ink">{{ $session->instructor?->name ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-ink-3">Lieu / véhicule</dt>
                                        <dd class="font-medium text-ink">{{ $session->location ?: ($session->vehicle?->plate ?? '—') }}</dd>
                                    </div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Paiements --}}
            <section class="material-solid p-5">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="text-section-title text-ink">Mes paiements</h2>
                    <x-ui.badge :tone="$balance > 0 ? 'warning' : 'success'">{{ $balance > 0 ? 'Solde à régler' : 'À jour' }}</x-ui.badge>
                </div>
                <p class="mt-2 text-[24px] font-bold leading-none text-ink"><x-ui.money :amount="$balance" /></p>
                <p class="mt-1 text-[12.5px] text-ink-2">Reste à payer sur <x-ui.money :amount="$totalDue" class="font-medium text-ink" /> facturés.</p>
                <x-ui.progress class="mt-3" :value="$totalPaid" :max="$totalDue ?: 1" :label="$progress.' % réglé'" :tone="$balance > 0 ? 'accent' : 'success'" />
                <a href="{{ route('eleve.paiements') }}" class="mt-3 inline-flex items-center gap-1 text-[13px] font-medium text-accent hover:underline">
                    Voir le détail <x-ui.icon name="chevron-right" class="w-3.5 h-3.5" />
                </a>
            </section>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('eleve.dossier.show') }}" class="material-solid flex flex-col gap-1 p-4 hover:bg-hover">
                    <x-ui.icon name="folder" class="w-5 h-5 text-accent" />
                    <span class="text-sm font-medium text-ink">Mon dossier</span>
                    <span class="text-[12.5px] text-ink-2">{{ ($documents ?? collect())->count() }} pièce(s) déposée(s)</span>
                </a>
                <a href="{{ route('eleve.progression') }}" class="material-solid flex flex-col gap-1 p-4 hover:bg-hover">
                    <x-ui.icon name="target" class="w-5 h-5 text-accent" />
                    <span class="text-sm font-medium text-ink">Ma progression</span>
                    <span class="text-[12.5px] text-ink-2">{{ $skillsAcquired ?? 0 }} / {{ $skillsTotal ?? 0 }} compétence(s) acquise(s)</span>
                </a>
                <a href="{{ route('quiz.play') }}" class="material-solid flex flex-col gap-1 p-4 hover:bg-hover">
                    <x-ui.icon name="book-open" class="w-5 h-5 text-accent" />
                    <span class="text-sm font-medium text-ink">Entraînement au code</span>
                    <span class="text-[12.5px] text-ink-2">
                        @if ($lastAttempt ?? null)
                            Dernier score : {{ $lastAttempt->score }} / {{ $lastAttempt->total_questions }}
                        @else
                            Aucun test passé
                        @endif
                    </span>
                </a>
                <a href="{{ route('eleve.planning') }}" class="material-solid flex flex-col gap-1 p-4 hover:bg-hover">
                    <x-ui.icon name="calendar-days" class="w-5 h-5 text-accent" />
                    <span class="text-sm font-medium text-ink">Mon planning</span>
                    <span class="text-[12.5px] text-ink-2">{{ ($upcomingSessions ?? collect())->count() }} séance(s) à venir</span>
                </a>
            </div>

            @if ($nextExam ?? null)
                <x-ui.alert tone="info" title="Examen à venir">
                    {{ $nextExam->type?->label() }} le <x-ui.date :value="$nextExam->exam_date" format="long" />{{ $nextExam->location ? ' à '.$nextExam->location : '' }}.
                </x-ui.alert>
            @endif
        @endif
    </div>
</x-app-layout>
