<x-app-layout>
    <x-slot name="header">Feuille de route - {{ $student->fullName() }}</x-slot>

    <div class="py-6 max-w-3xl mx-auto space-y-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-card>
                <p class="text-content-muted text-xs">Séances totales</p>
                <p class="text-2xl font-semibold text-content mt-1">{{ $summary['total'] }}</p>
            </x-card>
            <x-card>
                <p class="text-content-muted text-xs">Présences</p>
                <p class="text-2xl font-semibold text-success mt-1">{{ $summary['present'] }}</p>
            </x-card>
            <x-card>
                <p class="text-content-muted text-xs">Absences</p>
                <p class="text-2xl font-semibold text-danger mt-1">{{ $summary['absent'] }}</p>
            </x-card>
            <x-card>
                <p class="text-content-muted text-xs">Heures de conduite</p>
                <p class="text-2xl font-semibold text-content mt-1">{{ $summary['practicalHoursCompleted'] }}h</p>
            </x-card>
        </div>

        <x-card>
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-semibold text-content">Compétences</div>
                <a href="{{ route('training.evaluation.show', $student) }}" class="text-xs text-primary hover:underline">
                    Voir l'évaluation détaillée
                </a>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-content-secondary">{{ $skillSummary['acquired'] }}/{{ $skillSummary['total'] }} acquises</span>
                <span class="text-content font-medium">{{ $skillSummary['percent'] }}%</span>
            </div>
            <div class="bg-surface-inset rounded-full h-2 overflow-hidden mt-2">
                <div class="bg-primary h-2 rounded-full" style="width: {{ max(2, $skillSummary['percent']) }}%"></div>
            </div>
        </x-card>

        <x-card :padded="false">
            <div class="px-4 py-3 border-b border-border/60 text-sm font-semibold text-content">Historique des séances</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-4 py-2 font-medium">Date</th>
                            <th class="px-4 py-2 font-medium">Type</th>
                            <th class="px-4 py-2 font-medium">Horaire</th>
                            <th class="px-4 py-2 font-medium">Lieu</th>
                            <th class="px-4 py-2 font-medium">Présence</th>
                            <th class="px-4 py-2 font-medium">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($summary['sessions'] as $session)
                            <tr>
                                <td class="px-4 py-2.5">{{ $session->scheduled_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 text-content-secondary">{{ $session->type->label() }}</td>
                                <td class="px-4 py-2.5 text-content-secondary">{{ $session->starts_at }}–{{ $session->ends_at }}</td>
                                <td class="px-4 py-2.5 text-content-secondary">{{ $session->location ?? '-' }}</td>
                                <td class="px-4 py-2.5">
                                    <x-badge :variant="$session->presence->value === 'present' ? 'success' : ($session->presence->value === 'absent' ? 'danger' : 'neutral')">
                                        {{ $session->presence->label() }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-2.5 text-content-secondary">{{ $session->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-content-muted">Aucune séance pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
