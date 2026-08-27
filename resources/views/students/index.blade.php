<x-app-layout>
    <x-slot name="header">Élèves</x-slot>

    <div class="py-6 space-y-5 max-w-7xl mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-content">Élèves</h1>
                <p class="text-sm text-content-secondary">{{ $students->total() }} élève(s) au total</p>
            </div>
            @can('create', \App\Domain\Students\Models\Student::class)
                <a href="{{ route('students.create') }}" class="inline-flex items-center gap-2 rounded-ui-md bg-primary px-4 py-2 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
                    <x-icon name="plus" class="w-4 h-4" /> Nouvel élève
                </a>
            @endcan
        </div>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="lg:col-span-2">
                    <x-input-label for="search" value="Recherche" />
                    <div class="relative mt-1">
                        <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-content-muted" />
                        <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, téléphone, e-mail..."
                            class="w-full pl-9 rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                    </div>
                </div>

                <div>
                    <x-input-label for="stage" value="Statut" />
                    <select name="stage" id="stage" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">Tous</option>
                        @foreach ($stages as $stage)
                            <option value="{{ $stage->value }}" @selected(($filters['stage'] ?? null) === $stage->value)>{{ $stage->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="license_category" value="Type de permis" />
                    <select name="license_category" id="license_category" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">Tous</option>
                        @foreach ($licenseCategories as $category)
                            <option value="{{ $category->value }}" @selected(($filters['license_category'] ?? null) === $category->value)>{{ $category->value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="course_type" value="Formation" />
                    <select name="course_type" id="course_type" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">Toutes</option>
                        @foreach ($courseTypes as $type)
                            <option value="{{ $type->value }}" @selected(($filters['course_type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="instructor_id" value="Moniteur" />
                    <select name="instructor_id" id="instructor_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">Tous</option>
                        @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}" @selected((string) ($filters['instructor_id'] ?? '') === (string) $instructor->id)>{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="registered_from" value="Inscrit depuis le" />
                    <input type="date" name="registered_from" id="registered_from" value="{{ $filters['registered_from'] ?? '' }}"
                        class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                </div>

                <div>
                    <x-input-label for="registered_to" value="Jusqu'au" />
                    <input type="date" name="registered_to" id="registered_to" value="{{ $filters['registered_to'] ?? '' }}"
                        class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-ui-md bg-primary px-4 py-2 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
                        Filtrer
                    </button>
                    @if (array_filter($filters))
                        <a href="{{ route('students.index') }}" class="rounded-ui-md bg-surface-inset px-4 py-2 text-sm font-medium text-content-secondary hover:text-content transition">
                            Réinitialiser
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Élève</th>
                            <th class="px-5 py-3 font-medium">Catégorie</th>
                            <th class="px-5 py-3 font-medium">Étape</th>
                            <th class="px-5 py-3 font-medium">Dossier</th>
                            <th class="px-5 py-3 font-medium">Moniteur</th>
                            @if (auth()->user()->hasRole('moniteur'))
                                <th class="px-5 py-3 font-medium"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($students as $student)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3">
                                    <a href="{{ route('students.show', $student) }}" class="flex items-center gap-3 group">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary text-xs font-semibold">
                                            {{ mb_substr($student->first_name, 0, 1) }}{{ mb_substr($student->last_name, 0, 1) }}
                                        </span>
                                        <span class="font-medium text-content group-hover:text-primary transition">{{ $student->fullName() }}</span>
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-content-secondary">{{ $student->license_category->value }}</td>
                                <td class="px-5 py-3"><x-badge variant="info">{{ $student->lifecycle_stage->label() }}</x-badge></td>
                                <td class="px-5 py-3 text-content-secondary">{{ $student->dossier_status->label() }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $student->instructor?->name ?? '-' }}</td>
                                @if (auth()->user()->hasRole('moniteur'))
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('moniteur.eleves.feuille-route', $student) }}" class="text-xs text-primary hover:underline">Feuille de route</a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="{{ auth()->user()->hasRole('moniteur') ? 6 : 5 }}"
                                title="Aucun élève trouvé."
                                message="Commencez par inscrire votre premier élève."
                                :action="route('students.create')"
                                action-label="Ajouter un élève"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{ $students->links() }}
    </div>
</x-app-layout>
