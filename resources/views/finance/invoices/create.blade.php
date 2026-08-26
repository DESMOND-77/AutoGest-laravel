<x-app-layout>
    <x-slot name="header">Nouvelle facture - {{ $student->fullName() }}</x-slot>

    <div class="py-6 max-w-2xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('finance.invoices.store', $student) }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="training_package_id" value="Forfait (optionnel)" />
                    <select id="training_package_id" name="training_package_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">- Montant libre -</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->name }} ({{ number_format($package->price, 0, ',', ' ') }} FCFA)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="label" value="Libellé" />
                    <x-text-input id="label" name="label" class="block mt-1 w-full" :value="old('label')" placeholder="Frais d'inscription" />
                </div>

                <div>
                    <x-input-label for="amount_due" value="Montant dû (si pas de forfait)" />
                    <x-text-input id="amount_due" type="number" step="0.01" name="amount_due" class="block mt-1 w-full" :value="old('amount_due')" />
                    <x-input-error :messages="$errors->get('amount_due')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="issued_at" value="Date d'émission" />
                    <x-text-input id="issued_at" type="date" name="issued_at" class="block mt-1 w-full" :value="old('issued_at', now()->toDateString())" />
                </div>

                <div class="flex justify-end pt-2">
                    <x-primary-button>Créer la facture</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
