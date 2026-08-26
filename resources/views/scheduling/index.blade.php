<x-app-layout>
    <x-slot name="header">Planning — semaine du {{ $week->format('d/m/Y') }}</x-slot>

    @php
        $editConflictSession = null;
        if (session('editingSessionId') && $errors->has('starts_at')) {
            $editConflictSession = $sessions->firstWhere('id', session('editingSessionId'));
        }
        $duplicateConflictSession = null;
        if (session('duplicatingSessionId') && $errors->has('duplicate')) {
            $duplicateConflictSession = $sessions->firstWhere('id', session('duplicatingSessionId'));
        }
    @endphp

    <div
        x-data="{
            showEditModal: {{ $editConflictSession ? 'true' : 'false' }},
            editingSession: {{ $editConflictSession ? Illuminate\Support\Js::from([
                'id' => $editConflictSession->id,
                'student_name' => $editConflictSession->student->fullName(),
                'instructor_id' => old('instructor_id', $editConflictSession->instructor_id),
                'vehicle_id' => old('vehicle_id', $editConflictSession->vehicle_id),
                'type' => old('type', $editConflictSession->type->value),
                'scheduled_date' => old('scheduled_date', $editConflictSession->scheduled_date->toDateString()),
                'starts_at' => old('starts_at', substr($editConflictSession->starts_at, 0, 5)),
                'ends_at' => old('ends_at', substr($editConflictSession->ends_at, 0, 5)),
            ]) : 'null' }},
            showDuplicateModal: {{ $duplicateConflictSession ? 'true' : 'false' }},
            duplicatingSession: {{ $duplicateConflictSession ? Illuminate\Support\Js::from([
                'id' => $duplicateConflictSession->id,
                'student_name' => $duplicateConflictSession->student->fullName(),
            ]) : 'null' }},
        }"
        class="py-6 space-y-5 max-w-6xl mx-auto"
    >
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any() && ! $editConflictSession && ! $duplicateConflictSession)
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-4 text-sm">
                <a href="{{ route('scheduling.index', array_merge($filters, ['week' => $week->copy()->subWeek()->toDateString()])) }}" class="inline-flex items-center gap-1 text-content-secondary hover:text-primary transition">
                    <x-icon name="chevron-left" class="w-4 h-4" /> Semaine précédente
                </a>
                <a href="{{ route('scheduling.index', array_merge($filters, ['week' => $week->copy()->addWeek()->toDateString()])) }}" class="inline-flex items-center gap-1 text-content-secondary hover:text-primary transition">
                    Semaine suivante <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
                <a href="{{ route('scheduling.export.csv', array_merge($filters, ['week' => $week->toDateString()])) }}" class="text-primary hover:underline">Exporter en CSV</a>
            </div>

            <form method="GET" action="{{ route('scheduling.index') }}" class="flex flex-wrap items-end gap-2 text-sm">
                <input type="hidden" name="week" value="{{ $week->toDateString() }}">
                <div>
                    <x-input-label for="filter_student_id" value="Élève" />
                    <select id="filter_student_id" name="student_id" onchange="this.form.submit()" class="mt-1 rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">Tous</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(($filters['student_id'] ?? null) == $student->id)>{{ $student->fullName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="filter_instructor_id" value="Moniteur" />
                    <select id="filter_instructor_id" name="instructor_id" onchange="this.form.submit()" class="mt-1 rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">Tous</option>
                        @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}" @selected(($filters['instructor_id'] ?? null) == $instructor->id)>{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="filter_vehicle_id" value="Véhicule" />
                    <select id="filter_vehicle_id" name="vehicle_id" onchange="this.form.submit()" class="mt-1 rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">Tous</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected(($filters['vehicle_id'] ?? null) == $vehicle->id)>{{ $vehicle->plate }}</option>
                        @endforeach
                    </select>
                </div>
                @if (array_filter($filters))
                    <a href="{{ route('scheduling.index', ['week' => $week->toDateString()]) }}" class="text-xs text-content-secondary hover:text-content transition pb-2">Réinitialiser</a>
                @endif
            </form>
        </div>

        <x-card>
            <div class="text-sm font-semibold text-content mb-1">Planifier une séance</div>
            <p class="text-xs text-content-muted mb-3">Cliquez directement dans une colonne du planning ci-dessous pour préremplir la date et l'heure. Cliquez sur une séance existante pour la modifier, la dupliquer ou l'annuler.</p>
            <form id="scheduling-create-form" method="POST" action="{{ route('scheduling.store') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
                @csrf
                <div class="lg:col-span-2">
                    <x-input-label for="student_id" value="Élève" />
                    <select id="student_id" name="student_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->fullName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="instructor_id" value="Moniteur" />
                    <select id="instructor_id" name="instructor_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="vehicle_id" value="Véhicule" />
                    <select id="vehicle_id" name="vehicle_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">—</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->plate }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="type" value="Type" />
                    <select id="type" name="type" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach (\App\Domain\Scheduling\Enums\SessionType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="scheduled_date" value="Date" />
                    <input id="scheduled_date" type="date" name="scheduled_date" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm" required>
                </div>
                <div>
                    <x-input-label for="starts_at" value="Début" />
                    <input id="starts_at" type="time" name="starts_at" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm" required>
                </div>
                <div>
                    <x-input-label for="ends_at" value="Fin" />
                    <input id="ends_at" type="time" name="ends_at" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm" required>
                </div>
                <div class="lg:col-span-2">
                    <x-primary-button>Planifier</x-primary-button>
                </div>
            </form>
        </x-card>

        @if ($sessions->isEmpty())
            <x-card>
                <div class="text-center py-8">
                    <p class="text-sm font-medium text-content">Aucune séance planifiée cette semaine.</p>
                    <p class="text-sm text-content-muted mt-1">Planifiez une séance pour un élève avec le formulaire ci-dessus.</p>
                </div>
            </x-card>
        @else
            <x-planning-grid :sessions="$sessions" :week="$week" />
        @endif

        {{-- Edit / conflict / duplicate modal --}}
        <x-dialog show="showEditModal" max-width="lg">
            <template x-if="editingSession">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-content" x-text="editingSession.student_name"></h2>
                        <button type="button" @click="showEditModal = false" class="text-content-muted hover:text-content">
                            <x-icon name="x-mark" class="w-5 h-5" />
                        </button>
                    </div>

                    @if ($editConflictSession)
                        <x-alert variant="danger" class="mb-4">
                            <span class="font-medium">⚠ Conflit de planning —</span> {{ $errors->first('starts_at') }}
                        </x-alert>
                    @endif

                    <form :action="'{{ url('planning') }}/' + editingSession.id" method="POST" class="grid grid-cols-2 gap-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <x-input-label value="Moniteur" />
                            <select name="instructor_id" x-model="editingSession.instructor_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                                @foreach ($instructors as $instructor)
                                    <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Véhicule" />
                            <select name="vehicle_id" x-model="editingSession.vehicle_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                                <option value="">—</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->plate }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Type" />
                            <select name="type" x-model="editingSession.type" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                                @foreach (\App\Domain\Scheduling\Enums\SessionType::cases() as $case)
                                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Date" />
                            <input type="date" name="scheduled_date" x-model="editingSession.scheduled_date" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        </div>
                        <div>
                            <x-input-label value="Début" />
                            <input type="time" name="starts_at" x-model="editingSession.starts_at" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        </div>
                        <div>
                            <x-input-label value="Fin" />
                            <input type="time" name="ends_at" x-model="editingSession.ends_at" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        </div>

                        <div class="col-span-2 flex items-center justify-between pt-3 mt-1 border-t border-border/60">
                            <button type="button" class="text-sm text-primary hover:underline" @click="duplicatingSession = editingSession; showEditModal = false; showDuplicateModal = true">
                                Dupliquer
                            </button>
                            <div class="flex gap-2">
                                <x-secondary-button type="button" @click="showEditModal = false">Fermer</x-secondary-button>
                                <x-primary-button type="submit">Enregistrer</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </template>
        </x-dialog>

        {{-- Duplicate modal --}}
        <x-dialog show="showDuplicateModal" max-width="sm">
            <template x-if="duplicatingSession">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-content">Dupliquer la séance</h2>
                        <button type="button" @click="showDuplicateModal = false" class="text-content-muted hover:text-content">
                            <x-icon name="x-mark" class="w-5 h-5" />
                        </button>
                    </div>

                    @if ($duplicateConflictSession)
                        <x-alert variant="danger" class="mb-4">
                            <span class="font-medium">⚠ Conflit de planning —</span> {{ $errors->first('duplicate') }}
                        </x-alert>
                    @endif

                    <p class="text-sm text-content-secondary mb-3">Nouvelle date et heure pour <span x-text="duplicatingSession.student_name" class="font-medium text-content"></span> :</p>

                    <form :action="'{{ url('planning') }}/' + duplicatingSession.id + '/duplicate'" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <x-input-label value="Date" />
                            <input type="date" name="scheduled_date" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm" required>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label value="Début" />
                                <input type="time" name="starts_at" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm" required>
                            </div>
                            <div>
                                <x-input-label value="Fin" />
                                <input type="time" name="ends_at" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm" required>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <x-secondary-button type="button" @click="showDuplicateModal = false">Annuler</x-secondary-button>
                            <x-primary-button type="submit">Dupliquer</x-primary-button>
                        </div>
                    </form>
                </div>
            </template>
        </x-dialog>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('[data-day-column]').forEach((column) => {
            column.addEventListener('click', (event) => {
                if (event.target.closest('form, button, a, select, [data-session-card]')) {
                    return;
                }

                const rect = column.getBoundingClientRect();
                const offsetY = event.clientY - rect.top;
                const dayStart = parseInt(column.dataset.dayStartHour, 10);
                const dayEnd = parseInt(column.dataset.dayEndHour, 10);
                const totalMinutes = (dayEnd - dayStart) * 60;

                let minutes = dayStart * 60 + (offsetY / rect.height) * totalMinutes;
                minutes = Math.round(minutes / 15) * 15; // snap to the nearest quarter hour
                minutes = Math.max(dayStart * 60, Math.min(minutes, dayEnd * 60 - 30));

                const format = (totalMin) => {
                    const h = Math.floor(totalMin / 60).toString().padStart(2, '0');
                    const m = (totalMin % 60).toString().padStart(2, '0');
                    return `${h}:${m}`;
                };

                document.getElementById('scheduled_date').value = column.dataset.date;
                document.getElementById('starts_at').value = format(minutes);
                document.getElementById('ends_at').value = format(minutes + 60);

                document.getElementById('scheduling-create-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
                document.getElementById('student_id').focus();
            });
        });
    </script>
    @endpush
</x-app-layout>
