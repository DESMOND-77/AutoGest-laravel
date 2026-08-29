<x-app-layout>
    <x-slot name="header">Tableau de bord</x-slot>

    <div class="py-6 space-y-6 max-w-7xl mx-auto">
        <div>
            <h1 class="text-xl font-semibold text-content">Bonjour, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
            <p class="text-sm text-content-secondary">{{ now()->translatedFormat('l d F Y') }} - voici ce qui se passe dans votre établissement.</p>
        </div>

        {{-- KPI row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-kpi-card
                icon="users"
                label="Élèves actifs"
                :value="$studentsByStage->sum() - $studentsByStage->get('Ancien élève', 0)"
                :href="route('students.index')"
            />
            <x-kpi-card
                icon="academic-cap"
                label="Anciens élèves"
                :value="$studentsByStage->get('Ancien élève', 0)"
            />
            <x-kpi-card
                icon="document-check"
                label="Taux de réussite examens"
                :value="$examStats['rate'].'%'"
                :trend="$examStats['passed'].' réussis / '.$examStats['failed'].' échoués'"
                :trend-up="$examStats['rate'] >= 50"
            />
            <x-kpi-card
                icon="truck"
                label="Alertes flotte"
                :value="$fleetAlertCount"
                :href="route('fleet.index')"
            />
        </div>

        {{-- Quick actions --}}
        <x-card :padded="false" class="p-4">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('students.create') }}" class="inline-flex items-center gap-2 rounded-ui-md bg-surface-elevated px-3.5 py-2 text-sm font-medium text-content hover:shadow-soft-sm transition">
                    <x-icon name="plus" class="w-4 h-4 text-primary" /> Ajouter un élève
                </a>
                <a href="{{ route('scheduling.index') }}" class="inline-flex items-center gap-2 rounded-ui-md bg-surface-elevated px-3.5 py-2 text-sm font-medium text-content hover:shadow-soft-sm transition">
                    <x-icon name="calendar" class="w-4 h-4 text-primary" /> Planifier une séance
                </a>
                <a href="{{ route('finance.invoices.index') }}" class="inline-flex items-center gap-2 rounded-ui-md bg-surface-elevated px-3.5 py-2 text-sm font-medium text-content hover:shadow-soft-sm transition">
                    <x-icon name="receipt" class="w-4 h-4 text-primary" /> Créer une facture
                </a>
                <a href="{{ route('fleet.index') }}" class="inline-flex items-center gap-2 rounded-ui-md bg-surface-elevated px-3.5 py-2 text-sm font-medium text-content hover:shadow-soft-sm transition">
                    <x-icon name="truck" class="w-4 h-4 text-primary" /> Ajouter un véhicule
                </a>
            </div>
        </x-card>

        {{-- Revenue chart --}}
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm font-semibold text-content">Recettes des 6 derniers mois</div>
                <a href="{{ route('reports.revenue.csv') }}" class="text-xs text-primary hover:underline">Exporter en CSV</a>
            </div>
            @php $max = max(1, $revenueByMonth->max('total')); @endphp
            <div class="space-y-3">
                @foreach ($revenueByMonth as $row)
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-16 text-content-secondary shrink-0">{{ $row['month'] }}</div>
                        <div class="flex-1 bg-surface-inset rounded-full h-3 overflow-hidden">
                            <div class="bg-primary h-3 rounded-full" style="width: {{ $row['total'] > 0 ? max(2, $row['total'] / $max * 100) : 0 }}%"></div>
                        </div>
                        <div class="w-32 text-right text-content shrink-0">{{ number_format($row['total'], 0, ',', ' ') }} FCFA</div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Upcoming exams --}}
            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Examens à venir</div>
                @if ($upcomingExams->isEmpty())
                    <p class="text-sm text-content-muted">Aucun examen planifié.</p>
                @else
                    <ul class="divide-y divide-border/60">
                        @foreach ($upcomingExams as $exam)
                            <li class="py-2.5 flex items-center justify-between text-sm gap-2">
                                <div class="min-w-0">
                                    <p class="text-content truncate">{{ $exam->student->fullName() }}</p>
                                    <p class="text-content-muted text-xs">{{ $exam->type->label() }}</p>
                                </div>
                                <span class="text-content-secondary text-xs shrink-0">{{ $exam->exam_date->format('d/m/Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            {{-- Recent payments --}}
            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Paiements récents</div>
                @if ($recentPayments->isEmpty())
                    <p class="text-sm text-content-muted">Aucun paiement récent.</p>
                @else
                    <ul class="divide-y divide-border/60">
                        @foreach ($recentPayments as $payment)
                            <li class="py-2.5 flex items-center justify-between text-sm gap-2">
                                <div class="min-w-0">
                                    <p class="text-content truncate">{{ $payment->invoice->student->fullName() }}</p>
                                    <p class="text-content-muted text-xs">{{ $payment->paid_at->format('d/m/Y') }}</p>
                                </div>
                                <span class="text-success font-medium text-xs shrink-0">+{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            {{-- Vehicle status --}}
            <x-card>
                <div class="text-sm font-semibold text-content mb-3">État de la flotte</div>
                <ul class="space-y-2.5">
                    @foreach ($vehicleStatusCounts as $label => $count)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-content-secondary">{{ $label }}</span>
                            <span class="font-medium text-content">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('fleet.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs text-primary hover:underline">
                    Voir la flotte <x-icon name="chevron-right" class="w-3 h-3" />
                </a>
            </x-card>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Examens</div>
                <div class="grid grid-cols-3 gap-2 text-center text-sm">
                    <div>
                        <div class="text-2xl font-bold text-success">{{ $examStats['passed'] }}</div>
                        <div class="text-content-muted">Réussis</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-danger">{{ $examStats['failed'] }}</div>
                        <div class="text-content-muted">Échoués</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-content-secondary">{{ $examStats['pending'] }}</div>
                        <div class="text-content-muted">En attente</div>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Élèves par étape</div>
                <ul class="text-sm divide-y divide-border/60 max-h-48 overflow-y-auto">
                    @foreach ($studentsByStage as $stage => $count)
                        @if ($count > 0)
                            <li class="py-1.5 flex justify-between">
                                <span class="text-content-secondary">{{ $stage }}</span>
                                <span class="font-medium text-content">{{ $count }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </x-card>
        </div>
    </div>
</x-app-layout>
