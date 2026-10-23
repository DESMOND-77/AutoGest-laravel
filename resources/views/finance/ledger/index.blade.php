@php
    $types = collect(\App\Domain\Finance\Enums\LedgerEntryType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all();
    $pageEntries = $entries->getCollection();
    $in = $pageEntries->filter(fn ($e) => $e->type->isCredit())->sum(fn ($e) => (float) $e->amount);
    $out = $pageEntries->filter(fn ($e) => ! $e->type->isCredit())->sum(fn ($e) => (float) $e->amount);
@endphp

<x-app-layout :title="'Journal de caisse'">
    <x-layout.page-header
        title="Journal de caisse"
        description="Toutes les entrées et sorties d’argent, y compris les dépôts et retraits bancaires."
        :breadcrumbs="[['label' => 'Finance', 'href' => route('finance.invoices.index')], ['label' => 'Journal']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('finance.invoices.index')" variant="secondary" icon="receipt">Factures</x-ui.button>
            @if (auth()->user()->hasRole('admin'))
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-entry' })">Nouvelle écriture</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-data.kpi label="Solde de caisse" :value="number_format($balance, 0, ',', ' ').' FCFA'" icon="landmark" />
            <x-data.kpi label="Entrées (page)" :value="number_format($in, 0, ',', ' ').' FCFA'" icon="arrow-down-left" trend="up" />
            <x-data.kpi label="Sorties (page)" :value="number_format($out, 0, ',', ' ').' FCFA'" icon="arrow-up-right" trend="down" />
        </div>

        <x-ui.card variant="solid" padding="p-0">
            @if ($entries->isEmpty())
                <x-ui.empty-state icon="landmark" title="Aucune écriture" description="Les encaissements de factures sont ajoutés automatiquement. Vous pouvez aussi saisir une dépense ou un mouvement bancaire." class="py-10">
                    <x-slot:actions>
                        @if (auth()->user()->hasRole('admin'))
                            <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-entry' })">Nouvelle écriture</x-ui.button>
                        @endif
                    </x-slot:actions>
                </x-ui.empty-state>
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Écritures du journal">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Type</th>
                                <th scope="col">Libellé</th>
                                <th scope="col">Origine</th>
                                <th scope="col" class="text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr>
                                    <td data-label="Date"><x-ui.date :value="$entry->occurred_on" /></td>
                                    <td data-label="Type">
                                        <x-ui.badge :tone="$entry->type->isCredit() ? 'success' : 'neutral'">{{ $entry->type->label() }}</x-ui.badge>
                                    </td>
                                    <td data-label="Libellé">{{ $entry->memo ?: '—' }}</td>
                                    <td data-label="Origine">
                                        @if ($entry->payment?->invoice)
                                            <a href="{{ route('finance.invoices.show', $entry->payment->invoice) }}" class="text-[13px] text-accent hover:underline">
                                                {{ $entry->payment->invoice->label ?: 'Facture #'.$entry->payment->invoice->id }}
                                                — {{ $entry->payment->invoice->student?->fullName() }}
                                            </a>
                                        @else
                                            <span class="text-[13px] text-ink-3">Saisie manuelle · {{ $entry->createdBy?->name ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Montant" class="num">
                                        <span class="{{ $entry->type->isCredit() ? 'text-success' : 'text-ink' }}">
                                            {{ $entry->type->isCredit() ? '+' : '−' }} <x-ui.money :amount="$entry->amount" />
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
                <x-ui.pagination :paginator="$entries" label="Pagination du journal" />
            @endif
        </x-ui.card>
    </div>

    @if (auth()->user()->hasRole('admin'))
        <x-ui.dialog name="new-entry" title="Nouvelle écriture" description="Utilisez ce formulaire pour une dépense, un dépôt ou un retrait bancaire.">
            <x-form.errors />
            <form method="POST" action="{{ route('finance.ledger.store') }}" class="space-y-4">
                @csrf
                <x-form.field name="type" label="Type d’écriture" required>
                    <x-form.select name="type" :options="$types" :selected="old('type', 'expense')" />
                </x-form.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="amount" label="Montant (FCFA)" required>
                        <x-form.input name="amount" type="number" min="1" step="1" inputmode="numeric" required />
                    </x-form.field>
                    <x-form.field name="occurred_on" label="Date" optional>
                        <x-form.input name="occurred_on" type="date" :value="now()->format('Y-m-d')" />
                    </x-form.field>
                </div>
                <x-form.field name="memo" label="Libellé" optional>
                    <x-form.input name="memo" placeholder="Carburant, fournitures, dépôt…" />
                </x-form.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-entry' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Enregistrer l’écriture</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endif
</x-app-layout>
