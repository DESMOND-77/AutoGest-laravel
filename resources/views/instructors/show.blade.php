<x-app-layout>
    <x-slot name="header">{{ $instructor->user->name }}</x-slot>

    <div class="py-6 space-y-5 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div><span class="text-content-muted">Statut</span><p class="text-content font-medium mt-0.5">{{ $instructor->status->label() }}</p></div>
                <div><span class="text-content-muted">N° agrément</span><p class="text-content font-medium mt-0.5">{{ $instructor->license_number ?? '-' }}</p></div>
                <div><span class="text-content-muted">Embauche</span><p class="text-content font-medium mt-0.5">{{ optional($instructor->hire_date)->format('d/m/Y') ?? '-' }}</p></div>
            </div>
        </x-card>

        @can('update', $instructor)
            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Ajouter une disponibilité</div>
                <form method="POST" action="{{ route('instructors.availabilities.store', $instructor) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div>
                        <x-input-label for="day_of_week" value="Jour (0=dim…6=sam)" />
                        <x-text-input id="day_of_week" type="number" min="0" max="6" name="day_of_week" class="block mt-1 w-full" required />
                    </div>
                    <x-text-input type="time" name="starts_at" class="block w-full" required />
                    <x-text-input type="time" name="ends_at" class="block w-full" required />
                    <x-primary-button>Ajouter</x-primary-button>
                </form>
            </x-card>
        @endcan

        <x-card>
            <div class="text-sm font-semibold text-content mb-2">Disponibilités</div>
            <ul class="text-sm divide-y divide-border/60">
                @forelse ($instructor->availabilities as $availability)
                    <li class="py-2.5 flex justify-between items-center">
                        <span class="text-content">Jour {{ $availability->day_of_week }} - {{ $availability->starts_at }} à {{ $availability->ends_at }}</span>
                        @can('update', $instructor)
                            <form method="POST" action="{{ route('instructors.availabilities.destroy', [$instructor, $availability]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-danger hover:underline">Retirer</button>
                            </form>
                        @endcan
                    </li>
                @empty
                    <li class="py-2.5 text-content-muted">Aucune disponibilité renseignée.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-app-layout>
