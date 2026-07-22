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

            <div class="flex gap-3 text-sm">
                <a href="{{ route('scheduling.index', ['week' => $week->copy()->subWeek()->toDateString()]) }}" class="underline">&larr; Semaine précédente</a>
                <a href="{{ route('scheduling.index', ['week' => $week->copy()->addWeek()->toDateString()]) }}" class="underline">Semaine suivante &rarr;</a>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Planifier une séance</div>
                <form method="POST" action="{{ route('scheduling.store') }}" class="grid grid-cols-6 gap-3 items-end">
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

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Horaire</th>
                            <th class="px-4 py-3">Élève</th>
                            <th class="px-4 py-3">Moniteur</th>
                            <th class="px-4 py-3">Véhicule</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Présence</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="px-4 py-3">{{ $session->scheduled_date->format('d/m') }}</td>
                                <td class="px-4 py-3">{{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}</td>
                                <td class="px-4 py-3">{{ $session->student->fullName() }}</td>
                                <td class="px-4 py-3">{{ $session->instructor->name }}</td>
                                <td class="px-4 py-3">{{ $session->vehicle->plate ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $session->type->label() }}</td>
                                <td class="px-4 py-3">{{ $session->presence->label() }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('scheduling.destroy', $session) }}" onsubmit="return confirm('Annuler cette séance ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 underline">Annuler</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">Aucune séance cette semaine.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
