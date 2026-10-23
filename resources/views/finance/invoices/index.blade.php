@php
    $all = $invoices->getCollection();
    $due = $all->sum(fn ($invoice) => max(0, $invoice->balanceDue()));
    $paid = $all->sum(fn ($invoice) => (float) $invoice->amount_paid);
    $unpaidCount = $all->filter(fn ($invoice) => $invoice->status?->value !== 'paid')->count();
@endphp

<x-app-layout :title="'Factures'">
    <x-layout.page-header
        title="Factures"
        description="Encaissez les paiements, suivez les impayés et imprimez les reçus."
        :breadcrumbs="[['label' => 'Finance', 'href' => route('finance.ledger.index')], ['label' => 'Factures']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('finance.ledger.index')" variant="secondary" icon="landmark">Journal de caisse</x-ui.button>
            <x-ui.button :href="route('finance.packages.index')" variant="secondary" icon="package">Forfaits</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-data.kpi label="Reste à encaisser" :value="number_format((float) $due, 0, ',', ' ').' FCFA'" icon="circle-alert" :hint="$unpaidCount.' facture(s) non soldée(s)'" />
            <x-data.kpi label="Encaissé sur cette page" :value="number_format((float) $paid, 0, ',', ' ').' FCFA'" icon="wallet" />
            <x-data.kpi label="Factures" :value="$invoices->total()" icon="receipt" :hint="'Page '.$invoices->currentPage().' sur '.$invoices->lastPage()" />
        </div>

        <x-ui.card variant="solid" padding="p-0">
            <x-ui.alert tone="info" class="m-4 mb-0">
                Les paiements se saisissent depuis la facture. Aucun opérateur mobile money n’est connecté : les montants saisis sont ceux que vous encaissez réellement.
            </x-ui.alert>

            @if ($all->isEmpty())
                <x-ui.empty-state icon="receipt" title="Aucune facture" description="Créez une facture depuis la fiche d’un élève pour commencer à encaisser." class="py-10">
                    <x-slot:actions>
                        <x-ui.button :href="route('students.index')" variant="secondary" icon="graduation-cap">Voir les élèves</x-ui.button>
                    </x-slot:actions>
                </x-ui.empty-state>
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Liste des factures">
                        <thead>
                            <tr>
                                <th scope="col">Facture</th>
                                <th scope="col">Élève</th>
                                <th scope="col">Émise</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="text-right">Montant</th>
                                <th scope="col" class="text-right">Solde</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($all as $invoice)
                                <tr>
                                    <td data-label="Facture">
                                        <a href="{{ route('finance.invoices.show', $invoice) }}" class="text-sm font-medium text-ink hover:text-accent">{{ $invoice->label ?: 'Facture #'.$invoice->id }}</a>
                                    </td>
                                    <td data-label="Élève">
                                        @if ($invoice->student)
                                            <a href="{{ route('students.show', $invoice->student) }}" class="text-sm text-ink-2 hover:text-accent">{{ $invoice->student->fullName() }}</a>
                                        @else
                                            <span class="text-sm text-ink-3">Hors élève</span>
                                        @endif
                                    </td>
                                    <td data-label="Émise"><x-ui.date :value="$invoice->issued_at" /></td>
                                    <td data-label="Statut"><x-ui.status :value="$invoice->status" /></td>
                                    <td data-label="Montant" class="text-right"><x-ui.money :amount="$invoice->amount_due" /></td>
                                    <td data-label="Solde" class="text-right">
                                        @if ($invoice->balanceDue() > 0)
                                            <span class="font-semibold text-danger"><x-ui.money :amount="$invoice->balanceDue()" /></span>
                                        @else
                                            <span class="text-ink-3">Soldée</span>
                                        @endif
                                    </td>
                                    <td data-label="">
                                        <div class="flex justify-end">
                                            <x-ui.button :href="route('finance.invoices.show', $invoice)" size="sm" variant="ghost" icon-right="chevron-right">Ouvrir</x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
                <x-ui.pagination :paginator="$invoices" label="Pagination des factures" />
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
