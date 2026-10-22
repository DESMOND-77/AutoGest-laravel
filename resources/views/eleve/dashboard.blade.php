<x-app-layout>
    <x-slot name="header">Mon espace</x-slot>

    <div class="py-6 space-y-4 max-w-7xl mx-auto">
        <x-card>
            <div class="flex flex-wrap gap-6 text-sm">
                <a href="{{ route('eleve.planning') }}"
                    class="inline-flex items-center gap-1 text-primary hover:underline font-medium">
                    Mon planning <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
                <a href="{{ route('quiz.play') }}"
                    class="inline-flex items-center gap-1 text-primary hover:underline font-medium">
                    Entraînement au code <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
                <a href="{{ route('eleve.progression') }}"
                    class="inline-flex items-center gap-1 text-primary hover:underline font-medium">
                    Ma progression <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
                <a href="{{ route('eleve.paiements') }}"
                    class="inline-flex items-center gap-1 text-primary hover:underline font-medium">
                    Mes paiements <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
                <a href="{{ route('eleve.dossier.show') }}"
                    class="inline-flex items-center gap-1 text-primary hover:underline font-medium">
                    Mon dossier <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            </div>
        </x-card>
    </div>
</x-app-layout>
