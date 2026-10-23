<x-app-layout :title="'Lien d’inscription élève'">
    <x-layout.page-header
        title="Lien d’inscription élève"
        description="Partagez ce lien avec vos prospects : ils remplissent eux-mêmes leur fiche, sans créer de doublon."
        :breadcrumbs="[['label' => 'Paramètres', 'href' => route('settings.show')], ['label' => 'Inscription élève']]"
    />

    <div class="mx-auto max-w-2xl space-y-4">
        @php
            $token = $revealedToken ?? null;
            $url = $token ? route('public-registration.show', ['token' => $token]) : null;
        @endphp

        @if ($link)
            <x-ui.card title="Lien actif" variant="solid">
                <x-data.description-list :columns="1" :items="[
                    ['label' => 'Libellé', 'value' => $link->label ?: '—'],
                    ['label' => 'Créé le', 'value' => $link->created_at?->format('d/m/Y') ?? '—'],
                    ['label' => 'Expire le', 'value' => $link->expires_at?->format('d/m/Y') ?? 'Sans expiration'],
                    ['label' => 'Inscriptions', 'value' => ($link->usage_count ?? 0).' / '.($link->max_uses ?? '∞')],
                ]" />

                @if ($url)
                    <div class="mt-3 rounded-md border border-line bg-[var(--surface-inset)] p-3">
                        <p class="text-[11.5px] uppercase tracking-wide text-ink-3">Lien complet (affiché une seule fois)</p>
                        <p class="mt-1 break-all text-[13px] text-ink">{{ $url }}</p>
                        <x-ui.button :href="$url" size="sm" variant="secondary" class="mt-2" icon="external-link" target="_blank" rel="noopener">Ouvrir</x-ui.button>
                    </div>
                @else
                    <p class="mt-3 break-all rounded-md border border-line bg-[var(--surface-inset)] p-3 text-[13px] text-ink-3">{{ url('register/student') }}?token=••••••••••••••••••••••••</p>
                    <x-ui.alert tone="info" class="mt-3">
                        Pour des raisons de sécurité, le lien complet n’est affiché qu’une seule fois, juste après sa création. Régénérez-le si vous l’avez perdu.
                    </x-ui.alert>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('settings.student-registration.regenerate') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" icon="refresh-cw">Régénérer le lien</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('settings.student-registration.revoke') }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" class="text-danger" icon="ban">Révoquer</x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        @else
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="link" title="Aucun lien actif" description="Générez un lien d’inscription pour laisser vos prospects remplir leur fiche en ligne.">
                    <x-slot:actions>
                        <form method="POST" action="{{ route('settings.student-registration.generate') }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div class="text-left">
                                <label for="label" class="mb-1 block text-[12.5px] font-medium text-ink">Libellé (facultatif)</label>
                                <input id="label" name="label" type="text" maxlength="100" class="h-[var(--control-h)] rounded-md border border-control bg-inset px-3 text-[14px] text-ink" placeholder="Rentrée octobre">
                            </div>
                            <x-ui.button type="submit" icon="link">Générer un lien</x-ui.button>
                        </form>
                    </x-slot:actions>
                </x-ui.empty-state>
            </x-ui.card>
        @endif

        <x-ui.card title="Bon à savoir" variant="panel">
            <ul class="space-y-2 text-[13px] text-ink-2">
                <li class="flex gap-2"><x-ui.icon name="circle-check" class="mt-0.5 w-4 h-4 shrink-0 text-success" /> Le lien est propre à votre établissement : aucune donnée n’est partagée avec une autre auto-école.</li>
                <li class="flex gap-2"><x-ui.icon name="circle-check" class="mt-0.5 w-4 h-4 shrink-0 text-success" /> Le prospect crée son compte et reçoit un code de vérification par e-mail.</li>
                <li class="flex gap-2"><x-ui.icon name="circle-check" class="mt-0.5 w-4 h-4 shrink-0 text-success" /> Vous validez ensuite son dossier depuis la file de validation.</li>
            </ul>
        </x-ui.card>
    </div>
</x-app-layout>
