<x-app-layout>
    <x-slot name="header">Mes paiements</x-slot>

    <div class="py-6 max-w-3xl mx-auto space-y-5">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-content-muted text-sm">Solde restant dû</p>
                    <p @class([
                        'text-2xl font-semibold mt-1',
                        'text-danger' => $balanceDue > 0,
                        'text-content' => $balanceDue <= 0,
                    ])>{{ number_format($balanceDue, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>
        </x-card>

        @forelse ($invoices as $invoice)
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-content">{{ $invoice->label }}</h2>
                        <p class="text-xs text-content-secondary">Émise le {{ $invoice->issued_at->format('d/m/Y') }}</p>
                    </div>
                    <x-badge :variant="$invoice->status->value === 'paid' ? 'success' : ($invoice->status->value === 'unpaid' ? 'danger' : 'warning')">
                        {{ $invoice->status->label() }}
                    </x-badge>
                </div>

                <div class="grid grid-cols-3 gap-4 text-sm pt-3 border-t border-border/60 mb-4">
                    <div>
                        <p class="text-content-muted">Montant dû</p>
                        <p class="text-content font-semibold mt-1">{{ number_format($invoice->amount_due, 0, ',', ' ') }} FCFA</p>
                    </div>
                    <div>
                        <p class="text-content-muted">Réglé</p>
                        <p class="text-success font-semibold mt-1">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</p>
                    </div>
                    <div>
                        <p class="text-content-muted">Solde</p>
                        <p @class(['font-semibold mt-1', 'text-danger' => $invoice->balanceDue() > 0, 'text-content' => $invoice->balanceDue() <= 0])>
                            {{ number_format($invoice->balanceDue(), 0, ',', ' ') }} FCFA
                        </p>
                    </div>
                </div>

                @if ($invoice->payments->isNotEmpty())
                    <div class="overflow-x-auto pt-3 border-t border-border/60">
                        <table class="w-full text-sm text-left">
                            <thead class="text-content-muted">
                                <tr>
                                    <th class="py-2 font-medium">Date</th>
                                    <th class="py-2 font-medium">Montant</th>
                                    <th class="py-2 font-medium">Moyen</th>
                                    <th class="py-2 font-medium">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                @foreach ($invoice->payments as $payment)
                                    <tr class="{{ $payment->isCancelled() ? 'text-content-muted line-through' : '' }}">
                                        <td class="py-2.5">{{ $payment->paid_at->format('d/m/Y') }}</td>
                                        <td class="py-2.5 {{ $payment->isCancelled() ? '' : 'text-content font-medium' }}">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                        <td class="py-2.5 text-content-secondary">{{ $payment->method->label() }}</td>
                                        <td class="py-2.5">
                                            <x-badge :variant="$payment->isCancelled() ? 'neutral' : 'success'">
                                                {{ $payment->isCancelled() ? 'Annulé' : 'Actif' }}
                                            </x-badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        @empty
            <x-card>
                <p class="text-sm text-content-secondary text-center py-4">Aucune facture pour le moment.</p>
            </x-card>
        @endforelse
    </div>
</x-app-layout>
