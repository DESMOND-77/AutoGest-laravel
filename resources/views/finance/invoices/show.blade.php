<x-app-layout>
    <x-slot name="header">Facture - {{ $invoice->student->fullName() }}</x-slot>

    <div class="py-6 space-y-5 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @error('payment')
            <x-alert variant="danger">{{ $message }}</x-alert>
        @enderror

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-lg font-semibold text-content">{{ $invoice->label }}</h1>
                    <p class="text-sm text-content-secondary">Émise le {{ $invoice->issued_at->format('d/m/Y') }}</p>
                </div>
                <x-badge :variant="$invoice->status->value === 'paid' ? 'success' : ($invoice->status->value === 'unpaid' ? 'danger' : 'warning')">
                    {{ $invoice->status->label() }}
                </x-badge>
            </div>

            <div class="grid grid-cols-3 gap-4 text-sm pt-4 border-t border-border/60">
                <div>
                    <p class="text-content-muted">Montant dû</p>
                    <p class="text-content font-semibold mt-1">{{ number_format($invoice->amount_due, 0, ',', ' ') }} FCFA</p>
                </div>
                <div>
                    <p class="text-content-muted">Réglé</p>
                    <p class="text-success font-semibold mt-1">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</p>
                </div>
                <div>
                    <p class="text-content-muted">Solde restant</p>
                    <p @class(['font-semibold mt-1', 'text-danger' => $invoice->balanceDue() > 0, 'text-content' => $invoice->balanceDue() <= 0])>
                        {{ number_format($invoice->balanceDue(), 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Paiements</div>
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="py-2 font-medium">Date</th>
                            <th class="py-2 font-medium">Montant</th>
                            <th class="py-2 font-medium">Moyen</th>
                            <th class="py-2 font-medium">Enregistré par</th>
                            <th class="py-2 font-medium">Statut</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($invoice->payments as $payment)
                            <tr class="{{ $payment->isCancelled() ? 'text-content-muted line-through' : '' }}">
                                <td class="py-2.5">{{ $payment->paid_at->format('d/m/Y') }}</td>
                                <td class="py-2.5 {{ $payment->isCancelled() ? '' : 'text-content font-medium' }}">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="py-2.5 text-content-secondary">{{ $payment->method->label() }}</td>
                                <td class="py-2.5 text-content-secondary">{{ $payment->recordedBy?->name ?? '-' }}</td>
                                <td class="py-2.5">
                                    <x-badge :variant="$payment->isCancelled() ? 'neutral' : 'success'">
                                        {{ $payment->isCancelled() ? 'Annulé' : 'Actif' }}
                                    </x-badge>
                                </td>
                                <td class="py-2.5 text-right">
                                    @can('cancel', $payment)
                                        @if (! $payment->isCancelled())
                                            <form method="POST" action="{{ route('finance.payments.cancel', $payment) }}" class="inline" onsubmit="return confirm('Annuler ce paiement ?');">
                                                @csrf
                                                <button type="submit" class="text-danger hover:underline text-xs">Annuler</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-content-muted">Aucun paiement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @can('recordPayment', $invoice)
                @if ($invoice->balanceDue() > 0)
                    <form method="POST" action="{{ route('finance.invoices.payments.store', $invoice) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end pt-3 border-t border-border/60">
                        @csrf
                        <div>
                            <x-input-label for="amount" value="Montant" />
                            <x-text-input id="amount" type="number" step="0.01" name="amount" class="block mt-1 w-full" required />
                        </div>
                        <div>
                            <x-input-label for="method" value="Moyen" />
                            <select id="method" name="method" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                                @foreach (\App\Domain\Finance\Enums\PaymentMethod::cases() as $case)
                                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button>Enregistrer</x-primary-button>
                    </form>
                    @error('amount') <p class="text-danger text-sm mt-2">{{ $message }}</p> @enderror
                @else
                    <p class="text-sm text-content-muted pt-3 border-t border-border/60">Facture soldée.</p>
                @endif
            @endcan
        </x-card>
    </div>
</x-app-layout>
