<x-app-layout :title="'Nouvelle facture'">
    <x-layout.page-header
        title="Nouvelle facture"
        :description="'Pour '.$student->fullName().' · '.(string) $student->license_category?->value"
        :breadcrumbs="[
            ['label' => 'Factures', 'href' => route('finance.invoices.index')],
            ['label' => $student->fullName(), 'href' => route('students.show', $student)],
            ['label' => 'Nouvelle facture'],
        ]"
    />

    <div class="mx-auto max-w-2xl">
        <x-form.errors class="mb-4" />

        <form method="POST" action="{{ route('finance.invoices.store', $student) }}" class="material-solid p-5 sm:p-6">
            @csrf

            <x-form.section title="Objet de la facture" description="Choisissez un forfait, ou saisissez un montant libre.">
                <div class="space-y-4">
                    <x-form.field name="training_package_id" label="Forfait" optional hint="Sélectionner un forfait remplit automatiquement le libellé et le montant.">
                        <x-form.select name="training_package_id" placeholder="Aucun forfait (montant libre)"
                            :options="$packages->mapWithKeys(fn ($p) => [$p->id => $p->name.' — '.number_format((float) $p->price, 0, ',', ' ').' FCFA'])->all()" />
                    </x-form.field>

                    <x-form.field name="label" label="Libellé" optional hint="Par défaut : le nom du forfait, sinon « Frais de formation ».">
                        <x-form.input name="label" placeholder="Frais de formation" />
                    </x-form.field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.field name="amount_due" label="Montant dû (FCFA)" required hint="Ignoré si un forfait est sélectionné.">
                            <x-form.input name="amount_due" type="number" min="0" step="1" inputmode="numeric" placeholder="0" />
                        </x-form.field>
                        <x-form.field name="issued_at" label="Date d’émission" optional>
                            <x-form.input name="issued_at" type="date" :value="now()->format('Y-m-d')" />
                        </x-form.field>
                    </div>
                </div>
            </x-form.section>

            <x-form.actions>
                <x-ui.button :href="route('students.show', $student)" variant="ghost">Annuler</x-ui.button>
                <x-ui.button type="submit" icon="receipt">Créer la facture</x-ui.button>
            </x-form.actions>
        </form>
    </div>
</x-app-layout>
