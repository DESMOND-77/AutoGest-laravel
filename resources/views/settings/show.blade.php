<x-app-layout :title="'Paramètres'">
    <x-layout.page-header
        title="Paramètres"
        description="Identité de l’établissement et réglages par défaut utilisés sur vos documents."
        :breadcrumbs="[['label' => 'Administration'], ['label' => 'Paramètres']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('settings.student-registration.show')" variant="secondary" icon="link">Inscription élève</x-ui.button>
            <x-ui.button :href="route('settings.document-types.index')" variant="secondary" icon="folder">Pièces du dossier</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-3xl">
        <x-form.errors class="mb-4" />

        <form method="POST" action="{{ route('settings.update') }}" class="material-solid p-5 sm:p-6">
            @csrf
            @method('PATCH')

            <x-form.section title="Identité de l’établissement" description="Ces informations apparaissent sur les factures et reçus imprimés.">
                <div class="space-y-4">
                    <x-form.field name="display_name" label="Nom affiché" optional hint="Par défaut, le nom de votre structure.">
                        <x-form.input name="display_name" :value="$setting->display_name" placeholder="{{ auth()->user()->structure?->name }}" />
                    </x-form.field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.field name="phone" label="Téléphone" optional>
                            <x-form.input name="phone" type="tel" inputmode="tel" icon="phone" :value="$setting->phone" />
                        </x-form.field>
                        <x-form.field name="support_email" label="E-mail de contact" optional>
                            <x-form.input name="support_email" type="email" icon="envelope" :value="$setting->support_email" />
                        </x-form.field>
                    </div>
                    <x-form.field name="address" label="Adresse" optional>
                        <x-form.textarea name="address" :value="$setting->address" rows="2" />
                    </x-form.field>
                </div>
            </x-form.section>

            <x-form.section title="Format et thème par défaut" description="La devise est fixée au franc CFA. Le fuseau est celui de Libreville.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="currency" label="Devise" optional hint="Valeur informative : les montants restent en FCFA.">
                        <x-form.input name="currency" :value="$setting->currency ?? 'XAF'" />
                    </x-form.field>
                    <x-form.field name="timezone" label="Fuseau horaire" optional hint="Par défaut : Africa/Libreville.">
                        <x-form.input name="timezone" :value="$setting->timezone ?? 'Africa/Libreville'" />
                    </x-form.field>
                    <x-form.field name="default_theme" label="Mode d’affichage par défaut" optional hint="Chaque utilisateur peut ensuite choisir son thème complet.">
                        <x-form.select name="default_theme" :selected="$setting->default_theme"
                            :options="['light' => 'Clair', 'dark' => 'Sombre']" placeholder="Selon l’appareil" />
                    </x-form.field>
                </div>
                <p class="mt-2 text-[12.5px] text-ink-3">
                    Cette préférence s’applique aux utilisateurs qui n’ont rien choisi. Chacun peut ensuite sélectionner son thème depuis le sélecteur d’apparence.
                </p>
            </x-form.section>

            <x-form.actions>
                <x-ui.button :href="route('admin.dashboard')" variant="ghost">Retour</x-ui.button>
                <x-ui.button type="submit" icon="check">Enregistrer les paramètres</x-ui.button>
            </x-form.actions>
        </form>
    </div>
</x-app-layout>
