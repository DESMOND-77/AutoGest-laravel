<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Planning — semaine du {{ $week->format('d/m/Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-100 text-red-800 text-sm rounded-md p-3">{{ $errors->first() }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-3 text-sm">
                    <a href="{{ route('scheduling.index', array_merge($filters, ['week' => $week->copy()->subWeek()->toDateString()])) }}" class="underline">&larr; Semaine précédente</a>
                    <a href="{{ route('scheduling.index', array_merge($filters, ['week' => $week->copy()->addWeek()->toDateString()])) }}" class="underline">Semaine suivante &rarr;</a>
                </div>

                <form method="GET" action="{{ route('scheduling.index') }}" class="flex flex-wrap items-end gap-2 text-sm">
                    <input type="hidden" name="week" value="{{ $week->toDateString() }}">
                    <div>
                        <label for="filter_student_id" class="block text-xs text-gray-500">Élève</label>
                        <select id="filter_student_id" name="student_id" onchange="this.form.submit()" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                            <option value="">Tous</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected(($filters['student_id'] ?? null) == $student->id)>{{ $student->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_instructor_id" class="block text-xs text-gray-500">Moniteur</label>
                        <select id="filter_instructor_id" name="instructor_id" onchange="this.form.submit()" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                            <option value="">Tous</option>
                            @foreach ($instructors as $instructor)
                                <option value="{{ $instructor->id }}" @selected(($filters['instructor_id'] ?? null) == $instructor->id)>{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_vehicle_id" class="block text-xs text-gray-500">Véhicule</label>
                        <select id="filter_vehicle_id" name="vehicle_id" onchange="this.form.submit()" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                            <option value="">Tous</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(($filters['vehicle_id'] ?? null) == $vehicle->id)>{{ $vehicle->plate }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (array_filter($filters))
                        <a href="{{ route('scheduling.index', ['week' => $week->toDateString()]) }}" class="text-xs underline text-gray-500">Réinitialiser</a>
                    @endif
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Planifier une séance</div>
                <p class="text-xs text-gray-500 mb-3">Cliquez directement dans une colonne du planning ci-dessous pour préremplir la date et l'heure.</p>
                <form id="scheduling-create-form" method="POST" action="{{ route('scheduling.store') }}" class="grid grid-cols-6 gap-3 items-end">
                    @csrf
                    <div class="col-span-2">
                        <x-input-label for="student_id" value="Élève" />
                        <select id="student_id" name="student_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="instructor_id" value="Moniteur" />
                        <select id="instructor_id" name="instructor_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                            @foreach ($instructors as $instructor)
                                <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="vehicle_id" value="Véhicule" />
                        <select id="vehicle_id" name="vehicle_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">—</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->plate }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                            @foreach (\App\Domain\Scheduling\Enums\SessionType::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="scheduled_date" value="Date" />
                        <x-text-input id="scheduled_date" type="date" name="scheduled_date" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="starts_at" value="Début" />
                        <x-text-input id="starts_at" type="time" name="starts_at" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="ends_at" value="Fin" />
                        <x-text-input id="ends_at" type="time" name="ends_at" class="block mt-1 w-full" required />
                    </div>
                    <div class="col-span-2">
                        <x-primary-button>Planifier</x-primary-button>
                    </div>
                </form>
            </div>

            @if ($sessions->isEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-10 text-center">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Aucune séance planifiée cette semaine.</p>
                    <p class="text-sm text-gray-500 mt-1">Planifiez une séance pour un élève avec le formulaire ci-dessus.</p>
                </div>
            @else
                <x-planning-grid :sessions="$sessions" :week="$week" />
            @endif
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-day-column]').forEach((column) => {
            column.addEventListener('click', (event) => {
                if (event.target.closest('form, button, a, select')) {
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
</x-app-layout>
