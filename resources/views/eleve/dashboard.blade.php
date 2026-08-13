<x-app-layout>
    <x-slot name="header">Mon espace</x-slot>

    <div class="py-6 space-y-4 max-w-7xl mx-auto">
        <x-card>
            <div class="flex flex-wrap gap-6 text-sm">
                <a href="{{ route('eleve.planning') }}" class="text-primary hover:underline font-medium">
                    Mon planning &rarr;
                </a>
                <a href="{{ route('quiz.play') }}" class="text-primary hover:underline font-medium">
                    Entraînement au code &rarr;
                </a>
            </div>
        </x-card>

        <x-card>
            <p class="text-sm text-content-secondary">Votre progression et vos paiements arrivent avec une prochaine phase.</p>
        </x-card>
    </div>
</x-app-layout>
