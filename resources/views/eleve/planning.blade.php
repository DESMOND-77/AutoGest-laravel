<x-app-layout>
    <x-slot name="header">Mon planning — semaine du {{ $week->format('d/m/Y') }}</x-slot>

    <div class="py-6 space-y-5 max-w-5xl mx-auto">
        @if (! $student)
            <x-card>
                <p class="text-sm text-content-secondary">Votre dossier est en cours de traitement.</p>
            </x-card>
        @else
            <div class="flex gap-4 text-sm">
                <a href="{{ route('eleve.planning', ['week' => $week->copy()->subWeek()->toDateString()]) }}" class="inline-flex items-center gap-1 text-content-secondary hover:text-primary transition">
                    <x-icon name="chevron-left" class="w-4 h-4" /> Semaine précédente
                </a>
                <a href="{{ route('eleve.planning', ['week' => $week->copy()->addWeek()->toDateString()]) }}" class="inline-flex items-center gap-1 text-content-secondary hover:text-primary transition">
                    Semaine suivante <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            </div>

            @if ($sessions->isEmpty())
                <x-card>
                    <div class="text-center py-8">
                        <p class="text-sm font-medium text-content">Aucune séance cette semaine.</p>
                    </div>
                </x-card>
            @else
                <x-planning-grid :sessions="$sessions" :week="$week" />
            @endif
        @endif
    </div>
</x-app-layout>
