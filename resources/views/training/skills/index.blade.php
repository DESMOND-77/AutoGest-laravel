<x-app-layout>
    <x-slot name="header">Compétences</x-slot>

    <div class="py-6 space-y-5 max-w-2xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouvelle compétence</div>
            <form id="skills-create-form" method="POST" action="{{ route('training.skills.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                <div>
                    <x-input-label for="code" value="Code" />
                    <x-text-input id="code" name="code" class="block mt-1 w-full" required />
                </div>
                <div>
                    <x-input-label for="label" value="Libellé" />
                    <x-text-input id="label" name="label" class="block mt-1 w-full" required />
                </div>
                <div>
                    <x-input-label for="category" value="Catégorie" />
                    <x-text-input id="category" name="category" class="block mt-1 w-full" />
                </div>
                <div class="sm:col-span-3 flex justify-end">
                    <x-primary-button>Ajouter</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Code</th>
                            <th class="px-5 py-3 font-medium">Libellé</th>
                            <th class="px-5 py-3 font-medium">Catégorie</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($skills as $skill)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content font-medium">{{ $skill->code }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $skill->label }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $skill->category ?? '-' }}</td>
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('training.skills.destroy', $skill) }}" onsubmit="return confirm('Supprimer ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-danger hover:underline">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="4"
                                title="Aucune compétence définie."
                                message="Définissez les compétences évaluées pendant la formation pratique."
                                action="#skills-create-form"
                                action-label="Ajouter une compétence"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
