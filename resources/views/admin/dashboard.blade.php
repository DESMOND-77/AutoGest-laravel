@php
    $user = auth()->user();
    $monthTotal = (float) ($revenueByMonth->last()['total'] ?? 0);
    $previousTotal = (float) ($revenueByMonth->slice(-2, 1)->first()['total'] ?? 0);
    $delta = $previousTotal > 0 ? round(($monthTotal - $previousTotal) / $previousTotal * 100) : null;
    $sessionsToday = $todaysSessions->count();
    $unmarked = $todaysSessions->filter(fn ($s) => $s->presence?->value === 'planned')->count();
    $activeStudents = (int) $studentsByStage->sum();
    $phaseCounts = collect(config('ui-status.phases', []))->map(function ($phase) use ($studentsByStage) {
        $total = 0;
        foreach ($phase['stages'] as $stage) {
            $label = \App\Domain\Students\Enums\LifecycleStage::tryFrom($stage)?->label();
            $total += $label ? (int) ($studentsByStage[$label] ?? 0) : 0;
        }
        return ['label' => $phase['label'], 'count' => $total];
    });
    $spark = $revenueByMonth->pluck('total')->all();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
@endphp

<x-app-layout :title="'Tableau de bord'">
    <x-layout.page-header
        :title="$greeting.', '.explode(' ', $user->name)[0]"
        :description="now()->locale('fr')->translatedFormat('l j F Y').' · '.$sessionsToday.' séance(s) aujourd’hui, '.$unmarked.' présence(s) à marquer.'"
    >
        <x-slot:actions>
            <x-ui.button :href="route('scheduling.index')" variant="secondary" icon="calendar">Ouvrir le planning</x-ui.button>
            <x-ui.button :href="route('students.create')" icon="user-plus">Nouvel élève</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        {{-- À traiter : la prochaine meilleure action, toujours en premier --}}
        <x-ui.card title="À traiter" description="Les actions qui débloquent le plus de situations aujourd’hui." variant="solid" padding="p-0">
            <ul class="divide-y divide-line">
                <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 max-sm:px-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink">Dossiers d’élèves en attente de validation</p>
                        <p class="text-[12.5px] text-ink-2">Approuvez ou rejetez les pièces déposées, les plus anciennes d’abord.</p>
                    </div>
                    <x-ui.button :href="route('dossiers.index')" size="sm" variant="secondary" icon-right="chevron-right">Ouvrir la file</x-ui.button>
                </li>
                <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 max-sm:px-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink">Impayés à relancer</p>
                        <p class="text-[12.5px] text-ink-2">Solde restant dû sur les factures impayées ou partielles.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.money :amount="$outstandingBalance" class="text-sm font-semibold text-ink" />
                        <x-ui.button :href="route('finance.invoices.index')" size="sm" variant="secondary">Voir</x-ui.button>
                    </div>
                </li>
                <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 max-sm:px-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink">Alertes flotte (contrôle technique, assurance)</p>
                        <p class="text-[12.5px] text-ink-2">Échéances dans moins de 30 jours.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.badge :tone="$fleetAlertCount > 0 ? 'warning' : 'success'">{{ $fleetAlertCount }}</x-ui.badge>
                        <x-ui.button :href="route('fleet.index')" size="sm" variant="secondary">Voir la flotte</x-ui.button>
                    </div>
                </li>
                <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 max-sm:px-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink">Séances du jour sans présence marquée</p>
                        <p class="text-[12.5px] text-ink-2">Le pointage alimente l’évaluation continue et les statistiques.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.badge :tone="$unmarked > 0 ? 'warning' : 'success'">{{ $unmarked }}</x-ui.badge>
                        <x-ui.button :href="route('scheduling.index')" size="sm" variant="secondary">Pointer</x-ui.button>
                    </div>
                </li>
            </ul>
        </x-ui.card>

        {{-- KPI : métrique principale en haut à gauche --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-data.kpi label="Élèves actifs" :value="$activeStudents" icon="graduation-cap" :href="route('students.index')" hint="Toutes phases confondues" />
            <x-data.kpi label="Séances aujourd’hui" :value="$sessionsToday" icon="calendar-days" :hint="$unmarked.' à pointer'" :href="route('scheduling.index')" />
            <x-data.kpi label="Encaissements du mois" icon="wallet" :delta="$delta !== null ? ($delta >= 0 ? '+'.$delta.' %' : $delta.' %') : null" delta-label="vs mois précédent" :trend="$delta !== null && $delta < 0 ? 'down' : 'up'" :spark="$spark">
                <x-ui.money :amount="$monthTotal" />
            </x-data.kpi>
            <x-data.kpi label="Solde caisse" icon="landmark" :href="route('finance.ledger.index')" hint="Recettes moins dépenses">
                <x-ui.money :amount="$cashBalance" />
            </x-data.kpi>
            <x-data.kpi label="Reste à collecter" icon="receipt" :href="route('finance.invoices.index')" hint="Total restant dû">
                <x-ui.money :amount="$outstandingBalance" />
            </x-data.kpi>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            {{-- Parcours des élèves --}}
            <x-ui.card title="Parcours des élèves" description="Effectifs par phase du parcours." variant="solid" class="xl:col-span-1">
                @if ($activeStudents === 0)
                    <x-ui.empty-state icon="graduation-cap" title="Aucun élève enregistré" description="Créez votre premier élève pour suivre son parcours." class="py-6">
                        <x-slot:actions>
                            <x-ui.button :href="route('students.create')" size="sm" icon="user-plus">Nouvel élève</x-ui.button>
                        </x-slot:actions>
                    </x-ui.empty-state>
                @else
                    <x-data.funnel :steps="$phaseCounts->all()" label="Répartition des élèves par phase" />
                @endif
            </x-ui.card>

            {{-- Encaissements --}}
            <div class="xl:col-span-2">
                <x-data.chart-card
                    title="Recettes des 6 derniers mois"
                    description="Somme des recettes enregistrées au journal comptable."
                    :empty="$revenueByMonth->sum('total') <= 0"
                    empty-message="Aucun encaissement sur la période."
                >
                    <x-data.bar-chart
                        :series="$revenueByMonth->map(fn ($row) => ['label' => $row['month'], 'value' => round($row['total'])])->all()"
                        label="Encaissements mensuels"
                        height="h-44"
                    />
                    <x-slot:table>
                        <table class="data-table">
                            <caption class="sr-only">Encaissements par mois</caption>
                            <thead><tr><th scope="col">Mois</th><th scope="col" class="num">Montant</th></tr></thead>
                            <tbody>
                                @foreach ($revenueByMonth as $row)
                                    <tr>
                                        <td data-label="Mois">{{ $row['month'] }}</td>
                                        <td data-label="Montant" class="num"><x-ui.money :amount="$row['total']" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-slot:table>
                </x-data.chart-card>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            {{-- Planning du jour --}}
            <x-ui.card title="Planning du jour" description="Par moniteur, dans l’ordre des horaires." variant="solid" :padding="'p-0'">
                @if ($todaysSessions->isEmpty())
                    <x-ui.empty-state icon="calendar" title="Aucune séance aujourd’hui" description="Ouvrez le planning pour planifier la journée de vos moniteurs." class="py-8">
                        <x-slot:actions>
                            <x-ui.button :href="route('scheduling.index')" size="sm" variant="secondary" icon="calendar">Ouvrir le planning</x-ui.button>
                        </x-slot:actions>
                    </x-ui.empty-state>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($todaysSessions as $session)
                            <li class="flex items-center gap-3 px-5 py-2.5 max-sm:px-4">
                                <span class="w-12 shrink-0 text-[12.5px] font-semibold tabular-nums text-ink">{{ \Illuminate\Support\Carbon::parse($session->starts_at)->format('H:i') }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13.5px] font-medium text-ink">{{ $session->type?->label() }} · {{ $session->student?->fullName() }}</p>
                                    <p class="truncate text-[12px] text-ink-3">{{ $session->instructor?->name }}</p>
                                </div>
                                <x-ui.status :value="$session->presence" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            {{-- Activité récente --}}
            <div class="xl:col-span-2">
                <x-ui.card title="Dernières opérations financières" description="Paiements et écritures comptables." variant="solid">
                    @php
                        $activity = $recentPayments->map(fn ($payment) => [
                            'title' => 'Paiement reçu',
                            'description' => trim(($payment->invoice?->student?->fullName() ?? 'Élève inconnu').' · '.number_format((float) $payment->amount, 0, ',', ' ').' FCFA'),
                            'at' => $payment->paid_at,
                            'icon' => 'wallet',
                            'tone' => 'success',
                        ])->concat($recentLedgerEntries->map(fn ($entry) => [
                            'title' => $entry->type?->label() ?? 'Écriture',
                            'description' => $entry->memo,
                            'at' => $entry->occurred_on,
                            'icon' => 'landmark',
                            'tone' => ($entry->type?->isCredit() ?? true) ? 'success' : 'danger',
                        ]))->sortByDesc('at')->take(8)->values()->all();
                    @endphp
                    <x-data.timeline :items="$activity" empty="Aucune activité enregistrée pour le moment." />
                </x-ui.card>
            </div>
        </div>

        {{-- Examens à venir + parc de véhicules --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card title="Examens à venir" variant="solid" :padding="'p-0'">
                @if ($upcomingExams->isEmpty())
                    <p class="px-5 py-6 text-sm text-ink-3">Aucun examen planifié.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($upcomingExams as $exam)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-[13.5px] font-medium text-ink">{{ $exam->student?->fullName() }}</p>
                                    <p class="text-[12px] text-ink-3">{{ $exam->type?->label() }} · <x-ui.date :value="$exam->exam_date" /></p>
                                </div>
                                <x-ui.status :value="$exam->result" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Parc de véhicules" description="Répartition par statut." variant="solid">
                @if (collect($vehicleStatusCounts)->sum() === 0)
                    <x-ui.empty-state icon="car" title="Aucun véhicule" description="Ajoutez un véhicule pour suivre contrôle technique et assurance." class="py-6" />
                @else
                    <dl class="space-y-2.5">
                        @foreach ($vehicleStatusCounts as $label => $count)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-sm text-ink-2">{{ $label }}</dt>
                                <dd class="text-sm font-semibold tabular-nums text-ink">{{ $count }}</dd>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-3 border-t border-line pt-2.5">
                            <dt class="text-sm text-ink-2">Trésorerie disponible</dt>
                            <dd class="text-sm font-semibold text-ink"><x-ui.money :amount="$cashBalance" /></dd>
                        </div>
                    </dl>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
