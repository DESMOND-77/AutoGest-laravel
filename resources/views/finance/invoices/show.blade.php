@php
    $student = $invoice->student;
    $paid = (float) $invoice->amount_paid;
    $due = (float) $invoice->amount_due;
    $balance = $invoice->balanceDue();
    $percent = $due > 0 ? min(100, round($paid / $due * 100)) : 0;
    $activePayments = $invoice->payments->whereNull('cancelled_at');
    $methods = collect(\App\Domain\Finance\Enums\PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all();
@endphp

<x-app-layout :title="$invoice->label ?: 'Facture #'.$invoice->id">
    <x-layout.page-header
        :title="$invoice->label ?: 'Facture #'.$invoice->id"
        :description="($student?->fullName() ?? 'Hors élève').' · émise le '.($invoice->issued_at?->format('d/m/Y') ?? '—')"
        :breadcrumbs="[
            ['label' => 'Factures', 'href' => route('finance.invoices.index')],
            ['label' => $student?->fullName() ?? 'Hors élève', 'href' => $student ? route('students.show', $student) : null],
            ['label' => $invoice->label ?: 'Facture #'.$invoice->id],
        ]"
    >
        <x-slot:actions>
            <x-ui.status :value="$invoice->status" />
            <x-ui.button variant="secondary" icon="printer" x-on:click="window.print()">Imprimer</x-ui.button>
            @if ($student)
                <x-ui.button :href="route('students.show', $student)" variant="ghost" icon="graduation-cap">Fiche élève</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-ui.card title="Encaissement" variant="solid">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <p class="text-2xl font-bold text-ink"><x-ui.money :amount="$paid" /> <span class="text-sm font-medium text-ink-3">/</span> <span class="text-base font-medium text-ink-2"><x-ui.money :amount="$due" /></span></p>
                        <p class="text-[13px] text-ink-2">{{ $percent }} % encaissé</p>
                    </div>
                    <x-ui.progress :value="$percent" label="Progression de l’encaissement" />
                    @if ($balance > 0)
                        <p class="flex items-center gap-1.5 text-[13px] font-medium text-danger">
                            <x-ui.icon name="circle-alert" class="w-4 h-4" />
                            Reste à encaisser : <x-ui.money :amount="$balance" />
                        </p>
                    @else
                        <p class="flex items-center gap-1.5 text-[13px] font-medium text-success">
                            <x-ui.icon name="circle-check" class="w-4 h-4" />
                            Facture soldée.
                        </p>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card title="Paiements" :description="$activePayments->count().' paiement(s) enregistré(s)'" variant="solid" padding="p-0">
                @if ($invoice->payments->isEmpty())
                    <x-ui.empty-state icon="wallet" title="Aucun paiement" description="Enregistrez le premier encaissement avec le formulaire ci-contre." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($invoice->payments->sortByDesc('paid_at') as $payment)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 max-sm:px-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink {{ $payment->cancelled_at ? 'line-through opacity-60' : '' }}"><x-ui.money :amount="$payment->amount" /></p>
                                    <p class="text-[12px] text-ink-3">
                                        {{ $payment->method?->label() }} · <x-ui.date :value="$payment->paid_at" /> · saisi par {{ $payment->recordedBy?->name ?? '—' }}
                                    </p>
                                    @if ($payment->cancelled_at)
                                        <p class="text-[12px] font-medium text-danger">Annulé le {{ $payment->cancelled_at->format('d/m/Y') }}{{ $payment->cancellation_reason ? ' — '.$payment->cancellation_reason : '' }}</p>
                                    @endif
                                </div>
                                @if (! $payment->cancelled_at)
                                    @can('cancel', $payment)
                                        <x-ui.button size="sm" variant="ghost" class="text-danger" icon="ban"
                                            x-on:click="$dispatch('open-dialog', { name: 'cancel-payment-{{ $payment->id }}' })">Annuler</x-ui.button>
                                        <x-ui.dialog name="cancel-payment-{{ $payment->id }}" title="Annuler ce paiement" description="Le montant sera retiré de la facture et du journal de caisse. L’opération est conservée dans l’historique.">
                                            <form method="POST" action="{{ route('finance.payments.cancel', $payment) }}" class="space-y-4">
                                                @csrf
                                                <x-form.field name="reason" label="Motif" optional hint="Par exemple : erreur de montant ou de moyen de paiement.">
                                                    <x-form.input name="reason" placeholder="Erreur de saisie" />
                                                </x-form.field>
                                                <div class="flex justify-end gap-2">
                                                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'cancel-payment-{{ $payment->id }}' })">Garder le paiement</x-ui.button>
                                                    <x-ui.button variant="danger" type="submit">Annuler le paiement</x-ui.button>
                                                </div>
                                            </form>
                                        </x-ui.dialog>
                                    @endcan
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        <aside class="space-y-4">
            @can('recordPayment', $invoice)
                @if ($balance > 0)
                    <x-ui.card title="Enregistrer un paiement" variant="panel">
                        <x-form.errors class="mb-3" />
                        <form method="POST" action="{{ route('finance.invoices.payments.store', $invoice) }}" class="space-y-3">
                            @csrf
                            <x-form.field name="amount" label="Montant (FCFA)" required :hint="'Maximum : '.number_format($balance, 0, ',', ' ').' FCFA'">
                                <x-form.input name="amount" type="number" min="1" step="1" inputmode="numeric" :value="old('amount', (int) $balance)" required />
                            </x-form.field>
                            <x-form.field name="method" label="Moyen de paiement" required>
                                <x-form.select name="method" :options="$methods" :selected="old('method', 'cash')" />
                            </x-form.field>
                            <x-form.field name="paid_at" label="Date du paiement" optional>
                                <x-form.input name="paid_at" type="date" :value="old('paid_at', now()->format('Y-m-d'))" />
                            </x-form.field>
                            <x-ui.button type="submit" icon="wallet" class="w-full">Encaisser</x-ui.button>
                        </form>
                    </x-ui.card>
                @endif
            @endcan

            <x-ui.card title="Détails" variant="solid">
                <x-data.description-list :columns="1" :items="[
                    ['label' => 'Élève', 'value' => $student?->fullName() ?? 'Hors élève'],
                    ['label' => 'Formule', 'value' => $invoice->trainingPackage?->name ?? '—'],
                    ['label' => 'Montant dû', 'value' => number_format($due, 0, ',', ' ').' FCFA'],
                    ['label' => 'Encaissé', 'value' => number_format($paid, 0, ',', ' ').' FCFA'],
                    ['label' => 'Solde', 'value' => number_format((float) $balance, 0, ',', ' ').' FCFA'],
                    ['label' => 'Émise le', 'value' => $invoice->issued_at?->format('d/m/Y') ?? '—'],
                ]" />
            </x-ui.card>
        </aside>
    </div>

    {{-- Version imprimable (reçus) --}}
    <section class="print-only" aria-hidden="true">
        <h1 style="font-size:20px;margin:0 0 4px">{{ config('app.name') }} — Reçu de facture</h1>
        <p style="margin:0 0 16px">{{ $invoice->label ?: 'Facture #'.$invoice->id }} · {{ $invoice->issued_at?->format('d/m/Y') }}</p>
        <p style="margin:0 0 4px"><strong>Élève :</strong> {{ $student?->fullName() ?? 'Hors élève' }}</p>
        <p style="margin:0 0 16px"><strong>Statut :</strong> {{ $invoice->status?->label() }}</p>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead>
                <tr>
                    <th style="text-align:left;border-bottom:1px solid #000;padding:6px 4px">Date</th>
                    <th style="text-align:left;border-bottom:1px solid #000;padding:6px 4px">Moyen</th>
                    <th style="text-align:right;border-bottom:1px solid #000;padding:6px 4px">Montant (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activePayments->sortBy('paid_at') as $payment)
                    <tr>
                        <td style="padding:6px 4px;border-bottom:1px solid #ccc">{{ $payment->paid_at?->format('d/m/Y') }}</td>
                        <td style="padding:6px 4px;border-bottom:1px solid #ccc">{{ $payment->method?->label() }}</td>
                        <td style="padding:6px 4px;border-bottom:1px solid #ccc;text-align:right">{{ number_format((float) $payment->amount, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2" style="padding:8px 4px;font-weight:bold">Total encaissé</td>
                    <td style="padding:8px 4px;text-align:right;font-weight:bold">{{ number_format($paid, 0, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding:4px">Solde restant</td>
                    <td style="padding:4px;text-align:right">{{ number_format((float) $balance, 0, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>
        <p style="margin-top:20px;font-size:10px">Document généré le {{ now()->format('d/m/Y H:i') }}.</p>
    </section>
</x-app-layout>
