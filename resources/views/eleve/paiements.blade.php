@php
    $totalDue = $invoices->sum(fn ($invoice) => (float) $invoice->amount_due);
    $totalPaid = $invoices->sum(fn ($invoice) => (float) $invoice->amount_paid);
    $percent = $totalDue > 0 ? min(100, (int) round($totalPaid / $totalDue * 100)) : 0;
@endphp

<x-app-layout :title="'Mes paiements'">
    <x-layout.page-header
        title="Mes paiements"
        description="Le détail de vos factures et de vos versements."
        :breadcrumbs="[['label' => 'Accueil', 'href' => route('eleve.dashboard')], ['label' => 'Paiements']]"
    />

    <div class="mx-auto max-w-2xl space-y-4">
        <x-ui.card variant="panel">
            <div class="space-y-3">
                <div class="flex items-end justify-between gap-2">
                    <div>
                        <p class="text-[12px] uppercase tracking-wide text-ink-3">Reste à payer</p>
                        <p class="text-2xl font-bold text-ink"><x-ui.money :amount="$balanceDue" /></p>
                    </div>
                    <p class="text-[13px] text-ink-2"><x-ui.money :amount="$totalPaid" /> sur <x-ui.money :amount="$totalDue" /></p>
                </div>
                <x-ui.progress :value="$percent" label="Part déjà réglée" />
                @if ($balanceDue <= 0)
                    <p class="flex items-center gap-1.5 text-[13px] font-medium text-success">
                        <x-ui.icon name="circle-check" class="w-4 h-4" /> Vous êtes à jour de vos paiements.
                    </p>
                @else
                    <p class="text-[12.5px] text-ink-3">
                        Les paiements se règlent au secrétariat (espèces, Airtel Money, Moov Money, virement ou chèque). Aucun paiement en ligne n’est disponible.
                    </p>
                @endif
            </div>
        </x-ui.card>

        @if ($invoices->isEmpty())
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="receipt" title="Aucune facture" description="Vos factures apparaîtront ici dès qu’elles seront émises par votre auto-école." />
            </x-ui.card>
        @else
            @foreach ($invoices as $invoice)
                <x-ui.card :title="$invoice->label ?: 'Facture #'.$invoice->id" :description="'Émise le '.($invoice->issued_at?->format('d/m/Y') ?? '—')" variant="solid" padding="p-0">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                        <div>
                            <p class="text-[13px] text-ink-2">Montant dû : <x-ui.money :amount="$invoice->amount_due" /></p>
                            <p class="text-[13px] text-ink-2">Déjà réglé : <x-ui.money :amount="$invoice->amount_paid" /></p>
                        </div>
                        <x-ui.status :value="$invoice->status" />
                    </div>

                    @php $active = $invoice->payments->whereNull('cancelled_at')->sortByDesc('paid_at'); @endphp
                    @if ($active->isEmpty())
                        <p class="border-t border-line px-5 py-3 text-[12.5px] text-ink-3">Aucun versement enregistré pour cette facture.</p>
                    @else
                        <ul class="divide-y divide-line border-t border-line">
                            @foreach ($active as $payment)
                                <li class="flex items-center justify-between gap-2 px-5 py-2.5">
                                    <span class="text-[12.5px] text-ink-2"><x-ui.date :value="$payment->paid_at" /> · {{ $payment->method?->label() }}</span>
                                    <span class="text-[13px] font-medium text-ink"><x-ui.money :amount="$payment->amount" /></span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($invoice->balanceDue() > 0)
                        <p class="border-t border-line px-5 py-3 text-[12.5px] font-medium text-danger">
                            Reste à régler : <x-ui.money :amount="$invoice->balanceDue()" />
                        </p>
                    @endif
                </x-ui.card>
            @endforeach
        @endif
    </div>
</x-app-layout>
