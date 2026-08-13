<x-app-layout>
    <x-slot name="header">Forfaits</x-slot>

    <div class="py-6 space-y-5 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouveau forfait</div>
            <form id="packages-create-form" method="POST" action="{{ route('finance.packages.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                <div class="sm:col-span-2 flex justify-end">
                    <x-primary-button>Créer</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nom</th>
                            <th class="px-5 py-3 font-medium">Catégorie</th>
                            <th class="px-5 py-3 font-medium">Heures</th>
                            <th class="px-5 py-3 font-medium">Prix</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($packages as $package)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content font-medium">{{ $package->name }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $package->license_category }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $package->hours ?? '—' }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ number_format($package->price, 0, ',', ' ') }} FCFA</td>
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('finance.packages.destroy', $package) }}" onsubmit="return confirm('Supprimer ce forfait ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-danger hover:underline">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="5"
                                title="Aucun forfait de formation."
                                message="Créez un forfait pour pouvoir facturer rapidement vos élèves."
                                action="#packages-create-form"
                                action-label="Ajouter un forfait"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
