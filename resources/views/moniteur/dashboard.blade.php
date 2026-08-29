<x-app-layout>
    <x-slot name="header">Espace moniteur</x-slot>

    <div class="py-6 space-y-4 max-w-7xl mx-auto">
        <x-card>
            <div class="flex flex-wrap gap-6 text-sm">
                <a href="{{ route('moniteur.agenda') }}" class="inline-flex items-center gap-1 text-primary hover:underline font-medium">
                    Mon agenda <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
                <a href="{{ route('students.index') }}" class="inline-flex items-center gap-1 text-primary hover:underline font-medium">
                    Mes élèves <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            </div>
        </x-card>

        <x-card>
            <p class="text-sm text-content-secondary">La feuille de route détaillée arrive avec une prochaine phase.</p>
        </x-card>
    </div>
</x-app-layout>
