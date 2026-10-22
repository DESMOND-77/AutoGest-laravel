<x-app-layout :title="'Feuille de route — '.$student->fullName()">
    <x-layout.page-header
        :title="'Feuille de route'"
        :description="$student->fullName().' · '.($student->license_category?->value ? 'permis '.$student->license_category->value : '').($student->course_type?->label() ? ' · '.$student->course_type->label() : '')"
        :breadcrumbs="[['label' => 'Mon agenda', 'href' => route('moniteur.agenda')], ['label' => $student->fullName()]]"
    >
        <x-slot:actions>
            <x-ui.status :value="$student->lifecycle_stage" />
            <x-ui.button :href="route('training.evaluation.show', $student)" variant="secondary" icon="target">Évaluer les compétences</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-3xl space-y-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <x-data.kpi label="Séances" :value="$summary['total']" icon="calendar" hint="Avec vous" />
            <x-data.kpi label="Présences" :value="$summary['present']" icon="circle-check" />
            <x-data.kpi label="Absences" :value="$summary['absent']" icon="circle-x" />
            <x-data.kpi label="Heures de conduite" :value="$summary['practicalHoursCompleted'].' h'" icon="car" />
        </div>

        <x-ui.card title="Compétences acquises" :description="$skillSummary['acquired'].' sur '.$skillSummary['total'].' compétences validées'">
            <div class="space-y-2">
                <div class="flex items-center justify-between text-[13px]">
                    <span class="text-ink-2">Progression globale</span>
                    <span class="font-semibold text-ink">{{ $skillSummary['percent'] }} %</span>
                </div>
                <x-ui.progress :value="$skillSummary['percent']" label="Compétences acquises" />
                @if ($skillSummary['total'] === 0)
                    <p class="text-[12.5px] text-ink-3">Aucune compétence n’est encore configurée dans votre établissement.</p>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card title="Historique des séances" :description="$summary['sessions']->count().' séance(s) enregistrée(s) avec vous'" variant="solid" padding="p-0">
            @if ($summary['sessions']->isEmpty())
                <x-ui.empty-state icon="calendar" title="Aucune séance" description="Les séances planifiées avec cet élève apparaîtront ici." class="py-8" />
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Historique des séances">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Type</th>
                                <th scope="col">Horaire</th>
                                <th scope="col">Lieu</th>
                                <th scope="col">Présence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['sessions'] as $session)
                                <tr>
                                    <td data-label="Date"><x-ui.date :value="$session->scheduled_date" /></td>
                                    <td data-label="Type">{{ $session->type?->label() }}</td>
                                    <td data-label="Horaire" class="num">{{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}</td>
                                    <td data-label="Lieu">{{ $session->location ?: '—' }}</td>
                                    <td data-label="Présence"><x-ui.status :value="$session->presence" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.alert tone="info">
            Seules les séances marquées « présent » comptent dans les heures de conduite. Le statut d’une séance passée se corrige depuis votre agenda.
        </x-ui.alert>
    </div>
</x-app-layout>
