<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Compétences
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouvelle compétence</div>
                <form method="POST" action="{{ route('training.skills.store') }}" class="grid grid-cols-3 gap-3 items-end">
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
                    <div class="col-span-3 flex justify-end">
                        <x-primary-button>Ajouter</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Libellé</th><th class="px-4 py-3">Catégorie</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($skills as $skill)
                            <tr>
                                <td class="px-4 py-3">{{ $skill->code }}</td>
                                <td class="px-4 py-3">{{ $skill->label }}</td>
                                <td class="px-4 py-3">{{ $skill->category ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('training.skills.destroy', $skill) }}" onsubmit="return confirm('Supprimer ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 underline">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucune compétence.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
