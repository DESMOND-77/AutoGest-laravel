<x-app-layout>
    <x-slot name="header">Factures</x-slot>

    <div class="py-6 space-y-5 max-w-7xl mx-auto">
        <div>
            <h1 class="text-xl font-semibold text-content">Factures</h1>
            <p class="text-sm text-content-secondary">{{ $invoices->total() }} facture(s) au total</p>
        </div>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Élève</th>
                            <th class="px-5 py-3 font-medium">Libellé</th>
                            <th class="px-5 py-3 font-medium">Montant dû</th>
                            <th class="px-5 py-3 font-medium">Réglé</th>
                            <th class="px-5 py-3 font-medium">Statut</th>
                            <th class="px-5 py-3 font-medium">Émise le</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($invoices as $invoice)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3">
                                    <a href="{{ route('finance.invoices.show', $invoice) }}" class="font-medium text-content hover:text-primary transition">
                                        {{ $invoice->student?->fullName() ?? 'Client comptoir' }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-content-secondary">{{ $invoice->label }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ number_format($invoice->amount_due, 0, ',', ' ') }} FCFA</td>
                                <td class="px-5 py-3 text-content-secondary">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</td>
                                <td class="px-5 py-3">
                                    <x-badge :variant="$invoice->status->value === 'paid' ? 'success' : ($invoice->status->value === 'unpaid' ? 'danger' : 'warning')">
                                        {{ $invoice->status->label() }}
                                    </x-badge>
                                </td>
                                <td class="px-5 py-3 text-content-secondary">{{ $invoice->issued_at->format('d/m/Y') }}</td>
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
        </x-card>

        {{ $invoices->links() }}
    </div>
</x-app-layout>
