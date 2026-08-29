<x-app-layout>
    <x-slot name="header">Journal (caisse / banque)</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-kpi-card icon="currency" label="Solde" :value="number_format($balance, 0, ',', ' ').' FCFA'" class="max-w-xs" />

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouvelle écriture manuelle</div>
            <form id="ledger-create-form" method="POST" action="{{ route('finance.ledger.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @csrf
                <div>
                    <x-input-label for="type" value="Type" />
                    <select id="type" name="type" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach (\App\Domain\Finance\Enums\LedgerEntryType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="amount" value="Montant" />
                    <x-text-input id="amount" type="number" step="0.01" name="amount" class="block mt-1 w-full" required />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="memo" value="Motif" />
                    <x-text-input id="memo" name="memo" class="block mt-1 w-full" />
                </div>
                <div class="sm:col-span-2 flex justify-end">
                    <x-primary-button>Enregistrer</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-5 py-3 font-medium">Motif</th>
                            <th class="px-5 py-3 font-medium">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($entries as $entry)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->occurred_on->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->type->label() }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->memo ?? '-' }}</td>
                                <td @class(['px-5 py-3 font-medium', 'text-success' => $entry->type->isCredit(), 'text-danger' => ! $entry->type->isCredit()])>
                                    {{ $entry->type->isCredit() ? '+' : '-' }}{{ number_format($entry->amount, 0, ',', ' ') }} FCFA
                                </td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="4"
                                title="Aucune écriture dans le journal."
                                message="Les paiements enregistrés et les écritures manuelles apparaîtront ici."
                                action="#ledger-create-form"
                                action-label="Ajouter une écriture"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{ $entries->links() }}
    </div>
</x-app-layout>
