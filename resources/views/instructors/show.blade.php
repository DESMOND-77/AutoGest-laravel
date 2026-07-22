<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $instructor->user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 grid grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">Statut</span><br>{{ $instructor->status->label() }}</div>
                <div><span class="text-gray-500">N° agrément</span><br>{{ $instructor->license_number ?? '—' }}</div>
                <div><span class="text-gray-500">Embauche</span><br>{{ optional($instructor->hire_date)->format('d/m/Y') ?? '—' }}</div>
            </div>

            @can('update', $instructor)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Ajouter une disponibilité</div>
                    <form method="POST" action="{{ route('instructors.availabilities.store', $instructor) }}" class="grid grid-cols-4 gap-3 items-end">
                        @csrf
                        <div>
                            <x-input-label for="day_of_week" value="Jour (0=dim…6=sam)" />
                            <x-text-input id="day_of_week" type="number" min="0" max="6" name="day_of_week" class="block mt-1 w-full" required />
                        </div>
                        <x-text-input type="time" name="starts_at" class="block w-full" required />
                        <x-text-input type="time" name="ends_at" class="block w-full" required />
                        <x-primary-button>Ajouter</x-primary-button>
                    </form>
                </div>
            @endcan

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Disponibilités</div>
                <ul class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($instructor->availabilities as $availability)
                        <li class="py-2 flex justify-between items-center">
                            <span>Jour {{ $availability->day_of_week }} — {{ $availability->starts_at }} à {{ $availability->ends_at }}</span>
                            @can('update', $instructor)
                                <form method="POST" action="{{ route('instructors.availabilities.destroy', [$instructor, $availability]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 underline">Retirer</button>
                                </form>
                            @endcan
                        </li>
                    @empty
                        <li class="py-2 text-gray-500">Aucune disponibilité renseignée.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
