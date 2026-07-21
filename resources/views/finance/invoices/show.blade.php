<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Facture — {{ $invoice->student->fullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Libellé</span><br>{{ $invoice->label }}</div>
                <div><span class="text-gray-500">Statut</span><br>{{ $invoice->status->label() }}</div>
                <div><span class="text-gray-500">Montant dû</span><br>{{ number_format($invoice->amount_due, 0, ',', ' ') }} FCFA</div>
                <div><span class="text-gray-500">Réglé</span><br>{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</div>
                <div><span class="text-gray-500">Solde restant</span><br>{{ number_format($invoice->balanceDue(), 0, ',', ' ') }} FCFA</div>
                <div><span class="text-gray-500">Émise le</span><br>{{ $invoice->issued_at->format('d/m/Y') }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Paiements</div>
                <table class="w-full text-sm text-left mb-4">
                    <thead class="text-gray-500">
                        <tr><th class="py-1">Date</th><th class="py-1">Montant</th><th class="py-1">Moyen</th><th class="py-1">Enregistré par</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($invoice->payments as $payment)
                            <tr>
                                <td class="py-1">{{ $payment->paid_at->format('d/m/Y') }}</td>
                                <td class="py-1">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="py-1">{{ $payment->method->label() }}</td>
                                <td class="py-1">{{ $payment->recordedBy?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-center text-gray-500">Aucun paiement.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                @can('recordPayment', $invoice)
                    @if ($invoice->balanceDue() > 0)
                        <form method="POST" action="{{ route('finance.invoices.payments.store', $invoice) }}" class="grid grid-cols-3 gap-3 items-end">
                            @csrf
                            <div>
                                <x-input-label for="amount" value="Montant" />
                                <x-text-input id="amount" type="number" step="0.01" name="amount" class="block mt-1 w-full" required />
                            </div>
                            <div>
                                <x-input-label for="method" value="Moyen" />
                                <select id="method" name="method" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                                    @foreach (\App\Domain\Finance\Enums\PaymentMethod::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-primary-button>Enregistrer</x-primary-button>
                        </form>
                        @error('amount') <p class="text-red-600 text-sm mt-2">{{ $message }}</p> @enderror
                    @else
                        <p class="text-sm text-gray-500">Facture soldée.</p>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
