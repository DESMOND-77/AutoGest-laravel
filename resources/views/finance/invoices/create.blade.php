<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Nouvelle facture — {{ $student->fullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('finance.invoices.store', $student) }}">
                    @csrf

                    <div>
                        <x-input-label for="training_package_id" value="Forfait (optionnel)" />
                        <select id="training_package_id" name="training_package_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">— Montant libre —</option>
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}">{{ $package->name }} ({{ number_format($package->price, 0, ',', ' ') }} FCFA)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="label" value="Libellé" />
                        <x-text-input id="label" name="label" class="block mt-1 w-full" :value="old('label')" placeholder="Frais d'inscription" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="amount_due" value="Montant dû (si pas de forfait)" />
                        <x-text-input id="amount_due" type="number" step="0.01" name="amount_due" class="block mt-1 w-full" :value="old('amount_due')" />
                        <x-input-error :messages="$errors->get('amount_due')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="issued_at" value="Date d'émission" />
                        <x-text-input id="issued_at" type="date" name="issued_at" class="block mt-1 w-full" :value="old('issued_at', now()->toDateString())" />
                    </div>

                    <div class="flex justify-end mt-6">
                        <x-primary-button>Créer la facture</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
