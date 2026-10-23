<x-app-layout :title="'Moniteurs'">
    <x-layout.page-header
        title="Moniteurs"
        description="L’équipe pédagogique, ses disponibilités et les élèves affectés."
        :breadcrumbs="[['label' => 'Moniteurs']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Instructors\Models\Instructor::class)
                <x-ui.button icon="user-plus" x-on:click="$dispatch('open-dialog', { name: 'new-instructor' })">Nouveau moniteur</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        @if ($instructors->isEmpty())
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="users" title="Aucun moniteur" description="Ajoutez vos moniteurs pour leur affecter des élèves et des séances de conduite.">
                    <x-slot:actions>
                        @can('create', \App\Domain\Instructors\Models\Instructor::class)
                            <x-ui.button icon="user-plus" x-on:click="$dispatch('open-dialog', { name: 'new-instructor' })">Nouveau moniteur</x-ui.button>
                        @endcan
                    </x-slot:actions>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($instructors as $instructor)
                    <article class="material-solid flex flex-col gap-3 p-4">
                        <div class="flex items-start gap-3">
                            <x-ui.avatar :name="$instructor->user?->name ?? 'Moniteur'" size="md" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('instructors.show', $instructor) }}" class="block truncate text-[14px] font-semibold text-ink hover:text-accent">{{ $instructor->user?->name ?? 'Moniteur sans compte' }}</a>
                                <p class="truncate text-[12px] text-ink-3">{{ $instructor->user?->email }}</p>
                            </div>
                            <x-ui.status :value="$instructor->status" compact />
                        </div>

                        <dl class="grid grid-cols-2 gap-2 text-[12.5px]">
                            <div>
                                <dt class="text-ink-3">Permis</dt>
                                <dd class="truncate font-medium text-ink">{{ $instructor->license_number ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-3">Embauche</dt>
                                <dd class="font-medium text-ink">{{ $instructor->hire_date?->format('d/m/Y') ?? '—' }}</dd>
                            </div>
                        </dl>

                        @if (! empty($instructor->specialties))
                            <div class="flex flex-wrap gap-1">
                                @foreach ($instructor->specialties as $specialty)
                                    <x-ui.badge tone="neutral">{{ $specialty }}</x-ui.badge>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-auto flex items-center justify-between border-t border-line pt-2.5">
                            <span class="text-[12px] text-ink-3">{{ $instructor->availabilities_count ?? $instructor->availabilities?->count() ?? 0 }} créneau(x)</span>
                            <x-ui.button :href="route('instructors.show', $instructor)" size="sm" variant="ghost" icon-right="chevron-right">Ouvrir</x-ui.button>
                        </div>
                    </article>
                @endforeach
            </div>
            <x-ui.pagination :paginator="$instructors" label="Pagination des moniteurs" />
        @endif
    </div>

    @can('create', \App\Domain\Instructors\Models\Instructor::class)
        <x-ui.dialog name="new-instructor" title="Nouveau moniteur" description="Un compte lui sera créé et un lien de définition de mot de passe envoyé par e-mail.">
            <x-form.errors />
            <form method="POST" action="{{ route('instructors.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="name" label="Nom complet" required>
                        <x-form.input name="name" required autocomplete="name" />
                    </x-form.field>
                    <x-form.field name="email" label="Adresse e-mail" required>
                        <x-form.input name="email" type="email" required autocomplete="email" />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="license_number" label="Numéro de permis" optional>
                        <x-form.input name="license_number" />
                    </x-form.field>
                    <x-form.field name="hire_date" label="Date d’embauche" optional>
                        <x-form.input name="hire_date" type="date" :value="now()->format('Y-m-d')" />
                    </x-form.field>
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-instructor' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Créer le moniteur</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
