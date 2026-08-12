<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Forfaits
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouveau forfait</div>
                <form method="POST" action="{{ route('finance.packages.store') }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Nom" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="license_category" value="Catégorie" />
                        <x-text-input id="license_category" name="license_category" class="block mt-1 w-full" value="B" required />
                    </div>
                    <div>
                        <x-input-label for="hours" value="Heures" />
                        <x-text-input id="hours" type="number" name="hours" class="block mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="price" value="Prix (FCFA)" />
                        <x-text-input id="price" type="number" step="0.01" name="price" class="block mt-1 w-full" required />
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <x-primary-button>Créer</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Heures</th>
                            <th class="px-4 py-3">Prix</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($packages as $package)
                            <tr>
                                <td class="px-4 py-3">{{ $package->name }}</td>
                                <td class="px-4 py-3">{{ $package->license_category }}</td>
                                <td class="px-4 py-3">{{ $package->hours ?? '—' }}</td>
                                <td class="px-4 py-3">{{ number_format($package->price, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('finance.packages.destroy', $package) }}" onsubmit="return confirm('Supprimer ce forfait ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 underline">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun forfait.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
