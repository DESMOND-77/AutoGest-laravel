<x-app-layout>
    <x-slot name="header">Inscription publique des élèves</x-slot>

    <div class="py-6 space-y-5 max-w-2xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <h1 class="text-lg font-semibold text-content mb-1">Inscription publique des élèves</h1>
            <p class="text-sm text-content-secondary mb-5">
                Permettez aux futurs élèves de s'inscrire directement auprès de votre auto-école grâce à un lien sécurisé.
                Chaque demande arrive dans votre liste d'élèves au statut « Prospect », prête à être validée par votre équipe.
            </p>

            @if (! $link)
                <div class="rounded-ui-md bg-surface-inset p-5 text-center">
                    <p class="text-sm text-content-secondary mb-3">Aucun lien d'inscription actif.</p>
                    <form method="POST" action="{{ route('settings.student-registration.generate') }}">
                        @csrf
                        <x-primary-button>Générer un lien</x-primary-button>
                    </form>
                </div>
            @else
                <div class="rounded-ui-md bg-surface-inset p-5" x-data="{ copied: false }">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-content">Lien d'inscription</span>
                        <x-badge variant="success">Actif</x-badge>
                    </div>

                    @if ($revealedToken)
                        @php $publicUrl = route('public-registration.show', ['token' => $revealedToken]); @endphp
                        <x-alert variant="warning" class="mb-3">
                            Ce lien ne sera affiché qu'une seule fois. Copiez-le maintenant — vous ne pourrez plus le consulter en clair ensuite (vous pourrez toujours le régénérer).
                        </x-alert>
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                value="{{ $publicUrl }}"
                                x-ref="linkInput"
                                onclick="this.select()"
                                class="flex-1 rounded-ui-md border-0 bg-surface text-content shadow-inset text-sm font-mono px-3 py-2.5 truncate"
                            >
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText($refs.linkInput.value).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                class="shrink-0 rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition"
                            >
                                <span x-show="! copied">Copier</span>
                                <span x-show="copied" x-cloak>✓ Copié</span>
                            </button>
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                value="{{ url('register/student') }}?token=••••••••••••••••••••••••"
                                class="flex-1 rounded-ui-md border-0 bg-surface text-content-muted shadow-inset text-sm font-mono px-3 py-2.5 truncate"
                            >
                        </div>
                        <p class="text-xs text-content-muted mt-2">
                            Le lien complet n'est affiché qu'au moment de sa génération, pour des raisons de sécurité. Régénérez-le pour en obtenir un nouveau à copier.
                        </p>
                    @endif

                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-4 border-t border-border/60 text-xs">
                        <div>
                            <dt class="text-content-muted">Créé le</dt>
                            <dd class="text-content font-medium mt-0.5">{{ $link->created_at->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-content-muted">Expire le</dt>
                            <dd class="text-content font-medium mt-0.5">{{ $link->expires_at?->format('d/m/Y') ?? 'Jamais' }}</dd>
                        </div>
                        <div>
                            <dt class="text-content-muted">Utilisations</dt>
                            <dd class="text-content font-medium mt-0.5">{{ $link->usage_count }}{{ $link->max_uses ? ' / '.$link->max_uses : '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-content-muted">Dernière utilisation</dt>
                            <dd class="text-content font-medium mt-0.5">{{ $link->last_used_at?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="flex gap-2 mt-5 pt-4 border-t border-border/60">
                        <form method="POST" action="{{ route('settings.student-registration.regenerate') }}">
                            @csrf
                            <x-secondary-button type="submit">Régénérer</x-secondary-button>
                        </form>
                        <form method="POST" action="{{ route('settings.student-registration.revoke') }}" onsubmit="return confirm('Révoquer ce lien ? Il cessera immédiatement de fonctionner.');">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-ui-md font-medium text-sm text-danger shadow-soft-sm hover:shadow-soft transition">
                                Révoquer
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
