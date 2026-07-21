<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Journal (caisse / banque)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm text-gray-500">Solde</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($balance, 0, ',', ' ') }} FCFA</div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouvelle écriture manuelle</div>
                <form method="POST" action="{{ route('finance.ledger.store') }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                            @foreach (\App\Domain\Finance\Enums\LedgerEntryType::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="amount" value="Montant" />
                        <x-text-input id="amount" type="number" step="0.01" name="amount" class="block mt-1 w-full" required />
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="memo" value="Motif" />
                        <x-text-input id="memo" name="memo" class="block mt-1 w-full" />
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <x-primary-button>Enregistrer</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Motif</th>
                            <th class="px-4 py-3">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="px-4 py-3">{{ $entry->occurred_on->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ $entry->type->label() }}</td>
                                <td class="px-4 py-3">{{ $entry->memo ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $entry->type->isCredit() ? '+' : '-' }}{{ number_format($entry->amount, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucune écriture.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $entries->links() }}
        </div>
    </div>
</x-app-layout>
