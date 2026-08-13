<x-app-layout>
    <x-slot name="header">Mon agenda — semaine du {{ $week->format('d/m/Y') }}</x-slot>

    <div class="py-6 space-y-5 max-w-5xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <div class="flex gap-4 text-sm">
            <a href="{{ route('moniteur.agenda', ['week' => $week->copy()->subWeek()->toDateString()]) }}" class="text-content-secondary hover:text-primary transition">&larr; Semaine précédente</a>
            <a href="{{ route('moniteur.agenda', ['week' => $week->copy()->addWeek()->toDateString()]) }}" class="text-content-secondary hover:text-primary transition">Semaine suivante &rarr;</a>
        </div>

        @if ($sessions->isEmpty())
            <x-card>
                <div class="text-center py-8">
                    <p class="text-sm font-medium text-content">Aucune séance cette semaine.</p>
                </div>
            </x-card>
        @else
            <x-planning-grid :sessions="$sessions" :week="$week" :show-instructor="false" />
        @endif
    </div>
</x-app-layout>
