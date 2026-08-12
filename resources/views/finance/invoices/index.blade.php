<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Factures
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Élève</th>
                            <th class="px-4 py-3">Libellé</th>
                            <th class="px-4 py-3">Montant dû</th>
                            <th class="px-4 py-3">Réglé</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Émise le</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('finance.invoices.show', $invoice) }}" class="font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ $invoice->student->fullName() }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $invoice->label }}</td>
                                <td class="px-4 py-3">{{ number_format($invoice->amount_due, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3">{{ $invoice->status->label() }}</td>
                                <td class="px-4 py-3">{{ $invoice->issued_at->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="6"
                                title="Aucune facture émise."
                                message="Les factures se créent depuis la fiche d'un élève."
                                :action="route('students.index')"
                                action-label="Voir les élèves"
                            />
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{ $invoices->links() }}
        </div>
    </div>
</x-app-layout>
