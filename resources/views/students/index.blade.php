@php
    $activeFilters = array_filter($filters ?? [], fn ($value) => $value !== null && $value !== '');
    $chips = [];
    $labels = [
        'search' => 'Recherche', 'stage' => 'Étape', 'license_category' => 'Permis',
        'course_type' => 'Formule', 'instructor_id' => 'Moniteur',
        'registered_from' => 'Inscrit depuis', 'registered_to' => 'Inscrit jusqu’au',
    ];
    foreach ($activeFilters as $key => $value) {
        $display = $value;
        if ($key === 'stage') {
            $display = \App\Domain\Students\Enums\LifecycleStage::tryFrom((string) $value)?->label() ?? $value;
        } elseif ($key === 'license_category') {
            $display = 'Permis '.$value;
        } elseif ($key === 'course_type') {
            $display = \App\Domain\Students\Enums\CourseType::tryFrom((string) $value)?->label() ?? $value;
        } elseif ($key === 'instructor_id') {
            $display = collect($instructors)->firstWhere('id', (int) $value)?->name ?? $value;
        }
        $chips[] = [
            'label' => $labels[$key] ?? $key,
            'value' => $display,
            'url' => route('students.index', array_merge(request()->except(['page']), [$key => null])),
        ];
    }
    $clearUrl = route('students.index');
    $views = [
        ['label' => 'Tous', 'href' => route('students.index'), 'count' => $students->total(), 'key' => 'all', 'active' => ! isset($filters['stage'])],
        ['label' => 'Prospects', 'href' => route('students.index', ['stage' => 'prospect']), 'key' => 'prospect', 'active' => ($filters['stage'] ?? null) === 'prospect'],
        ['label' => 'En formation', 'href' => route('students.index', ['stage' => 'practical_course']), 'key' => 'practical', 'active' => ($filters['stage'] ?? null) === 'practical_course'],
        ['label' => 'Anciens élèves', 'href' => route('students.index', ['stage' => 'former_student']), 'key' => 'former', 'active' => ($filters['stage'] ?? null) === 'former_student'],
    ];
@endphp

<x-app-layout :title="'Élèves'">
    <x-layout.page-header
        title="Élèves"
        :description="$students->total().' élève(s) dans votre établissement. Filtrez par étape, permis ou moniteur.'"
    >
        <x-slot:actions>
            <x-ui.button :href="route('dossiers.index')" variant="secondary" icon="clipboard-check">Dossiers à valider</x-ui.button>
            @can('create', \App\Domain\Students\Models\Student::class)
                <x-ui.button :href="route('students.create')" icon="user-plus">Nouvel élève</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <x-data.filter-bar
            :action="route('students.index')"
            :search="$filters['search'] ?? null"
            placeholder="Nom, téléphone, e-mail…"
            :chips="$chips"
            :clear-url="count($chips) ? $clearUrl : null"
            :views="$views"
            :result-count="$students->total().' résultat(s)'"
        >
            <x-form.select name="stage" :selected="$filters['stage'] ?? null" placeholder="Toutes les étapes"
                :options="collect($stages)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" class="w-auto min-w-44" />
            <x-form.select name="instructor_id" :selected="$filters['instructor_id'] ?? null" placeholder="Tous les moniteurs"
                :options="collect($instructors)->pluck('name', 'id')->all()" class="w-auto min-w-40" />
            <x-form.select name="license_category" :selected="$filters['license_category'] ?? null" placeholder="Tous les permis"
                :options="collect($licenseCategories)->mapWithKeys(fn ($c) => [$c->value => 'Permis '.$c->value])->all()" class="w-auto min-w-36" />
        </x-data.filter-bar>

        @if ($students->isEmpty())
            @if (count($chips))
                <x-ui.card variant="solid">
                    <x-ui.empty-state icon="search" title="Aucun élève ne correspond à ces filtres"
                        description="Élargissez la recherche ou retirez un filtre pour retrouver vos élèves.">
                        <x-slot:actions>
                            <x-ui.button :href="$clearUrl" variant="secondary" icon="x">Tout effacer</x-ui.button>
                        </x-slot:actions>
                    </x-ui.empty-state>
                </x-ui.card>
            @else
                <x-ui.card variant="solid">
                    <x-ui.empty-state icon="graduation-cap" title="Aucun élève trouvé."
                        description="Créez le premier élève pour suivre son dossier, son planning et ses paiements.">
                        <x-slot:actions>
                            @can('create', \App\Domain\Students\Models\Student::class)
                                <x-ui.button :href="route('students.create')" icon="user-plus">Nouvel élève</x-ui.button>
                            @endcan
                        </x-slot:actions>
                    </x-ui.empty-state>
                </x-ui.card>
            @endif
        @else
            <x-data.table caption="Liste des élèves">
                <thead>
                    <tr>
                        <th scope="col">Élève</th>
                        <th scope="col">Étape</th>
                        <th scope="col">Permis</th>
                        <th scope="col">Moniteur</th>
                        <th scope="col">Inscription</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $student)
                        <tr>
                            <td data-label="Élève">
                                <div class="flex items-center gap-2.5">
                                    <x-ui.avatar :name="$student->fullName()" size="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('students.show', $student) }}" class="block truncate text-sm font-medium text-ink hover:text-accent">{{ $student->fullName() }}</a>
                                        <span class="block truncate text-[12px] text-ink-3"><x-ui.phone :value="$student->phone" :link="false" /></span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Étape"><x-ui.status :value="$student->lifecycle_stage" /></td>
                            <td data-label="Permis">{{ $student->license_category?->value ?? '—' }}</td>
                            <td data-label="Moniteur">{{ $student->instructor?->name ?? '—' }}</td>
                            <td data-label="Inscription"><x-ui.date :value="$student->registered_at" /></td>
                            <td data-label="">
                                <div class="flex justify-end">
                                    @if (auth()->user()->hasRole('moniteur') && $student->instructor_id === auth()->id())
                                        <x-ui.button :href="route('moniteur.eleves.feuille-route', $student)" variant="ghost" size="sm" icon="route">Feuille de route</x-ui.button>
                                    @endif
                                    <x-ui.button :href="route('students.show', $student)" variant="ghost" size="sm" icon-right="chevron-right">Fiche</x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data.table>

            <x-ui.pagination :paginator="$students" label="Pagination des élèves" />
        @endif
    </div>
</x-app-layout>
