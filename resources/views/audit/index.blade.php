<x-app-layout :title="'Journal d’audit'">
    <x-layout.page-header
        title="Journal d’audit"
        description="Trace des actions sensibles : validations, changements d’étape, suppressions et modifications de comptes."
        :breadcrumbs="[['label' => 'Administration'], ['label' => 'Journal d’audit']]"
    />

    <div class="mx-auto max-w-4xl space-y-4">
        <x-ui.alert tone="info">
            Le journal est en lecture seule et ne peut pas être modifié depuis l’application. Chaque entrée conserve l’auteur, l’adresse IP et les valeurs avant/après.
        </x-ui.alert>

        @if ($logs->isEmpty())
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="shield" title="Aucune entrée" description="Les actions sensibles apparaîtront ici au fur et à mesure." />
            </x-ui.card>
        @else
            <div class="space-y-2">
                @foreach ($logs as $log)
                    <article class="material-solid p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-[13.5px] font-semibold text-ink">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $log->action)) }}</p>
                                <p class="mt-0.5 text-[12px] text-ink-3">
                                    {{ $log->user?->name ?? 'Système' }}
                                    · <x-ui.date :value="$log->created_at" format="datetime" />
                                    @if ($log->ip_address)
                                        · {{ $log->ip_address }}
                                    @endif
                                </p>
                            </div>
                            <x-ui.badge tone="neutral">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</x-ui.badge>
                        </div>

                        @if (! empty($log->old_values) || ! empty($log->new_values))
                            <details class="mt-2">
                                <summary class="cursor-pointer text-[12.5px] font-medium text-accent focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">Voir les changements</summary>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    <div class="rounded-sm bg-[var(--surface-inset)] p-2.5">
                                        <p class="text-[11px] uppercase tracking-wide text-ink-3">Avant</p>
                                        <dl class="mt-1 space-y-0.5 text-[12px]">
                                            @forelse (($log->old_values ?? []) as $key => $value)
                                                <div class="flex gap-1"><dt class="text-ink-3">{{ $key }} :</dt><dd class="min-w-0 break-all text-ink">{{ is_scalar($value) ? $value : json_encode($value) }}</dd></div>
                                            @empty
                                                <p class="text-ink-3">—</p>
                                            @endforelse
                                        </dl>
                                    </div>
                                    <div class="rounded-sm bg-[var(--surface-inset)] p-2.5">
                                        <p class="text-[11px] uppercase tracking-wide text-ink-3">Après</p>
                                        <dl class="mt-1 space-y-0.5 text-[12px]">
                                            @forelse (($log->new_values ?? []) as $key => $value)
                                                <div class="flex gap-1"><dt class="text-ink-3">{{ $key }} :</dt><dd class="min-w-0 break-all text-ink">{{ is_scalar($value) ? $value : json_encode($value) }}</dd></div>
                                            @empty
                                                <p class="text-ink-3">—</p>
                                            @endforelse
                                        </dl>
                                    </div>
                                </div>
                            </details>
                        @endif
                    </article>
                @endforeach
            </div>
            <x-ui.pagination :paginator="$logs" label="Pagination du journal d’audit" />
        @endif
    </div>
</x-app-layout>
