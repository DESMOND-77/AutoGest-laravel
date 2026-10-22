@php
    $labels = collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->value])->all();
@endphp

<x-app-layout :title="'Établissements'">
    <x-layout.page-header
        title="Établissements"
        description="Supervision de la plateforme : validez les nouvelles auto-écoles, suspendez ou désactivez un accès."
        :breadcrumbs="[['label' => 'Plateforme'], ['label' => 'Établissements']]"
    />

    <div class="space-y-4">
        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('superadmin.structures.index') }}"
                class="rounded-pill border px-3 py-1.5 text-[12.5px] font-medium {{ ! $currentStatus ? 'border-accent bg-accent-soft text-accent' : 'border-line text-ink-2 hover:bg-hover' }}">Tous</a>
            @foreach ($statuses as $status)
                <a href="{{ route('superadmin.structures.index', ['status' => $status->value]) }}"
                    class="rounded-pill border px-3 py-1.5 text-[12.5px] font-medium {{ $currentStatus === $status->value ? 'border-accent bg-accent-soft text-accent' : 'border-line text-ink-2 hover:bg-hover' }}">
                    <x-ui.status :value="$status" compact />
                    <span class="ml-1 text-ink-3">{{ (int) ($statusCounts[$status->value] ?? 0) }}</span>
                </a>
            @endforeach
        </div>

        <x-ui.card variant="solid" padding="p-0">
            @if ($structures->isEmpty())
                <x-ui.empty-state icon="building-office" title="Aucun établissement" description="Les inscriptions d’auto-écoles apparaîtront ici pour validation." class="py-10" />
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Liste des établissements">
                        <thead>
                            <tr>
                                <th scope="col">Établissement</th>
                                <th scope="col">Contact</th>
                                <th scope="col">Comptes</th>
                                <th scope="col">Créé le</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($structures as $structure)
                                <tr>
                                    <td data-label="Établissement">
                                        <span class="block text-sm font-medium text-ink">{{ $structure->name }}</span>
                                        <span class="block text-[12px] text-ink-3">{{ $structure->address ?: '—' }}</span>
                                    </td>
                                    <td data-label="Contact">
                                        <span class="block text-[13px] text-ink-2">{{ $structure->email ?: '—' }}</span>
                                        <span class="block text-[12px] text-ink-3">{{ $structure->phone ?: '—' }}</span>
                                    </td>
                                    <td data-label="Comptes" class="num">{{ $structure->users_count }}</td>
                                    <td data-label="Créé le"><x-ui.date :value="$structure->created_at" /></td>
                                    <td data-label="Statut"><x-ui.status :value="$structure->status" /></td>
                                    <td data-label="Actions">
                                        <div class="flex flex-wrap justify-end gap-1">
                                            @can('update', $structure)
                                                <form method="POST" action="{{ route('superadmin.structures.status', $structure) }}" class="flex items-center gap-1.5">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label for="status-{{ $structure->id }}" class="sr-only">Nouveau statut</label>
                                                    <select id="status-{{ $structure->id }}" name="status" class="h-8 rounded-pill border border-control bg-inset px-2 text-[12px] text-ink">
                                                        @foreach ($statuses as $status)
                                                            <option value="{{ $status->value }}" @selected($structure->status === $status)>{{ $status->value }}</option>
                                                        @endforeach
                                                    </select>
                                                    <x-ui.button type="submit" size="sm" variant="ghost">Appliquer</x-ui.button>
                                                </form>
                                            @endcan
                                            @can('delete', $structure)
                                                <x-ui.button size="sm" variant="ghost" class="text-danger" icon="trash-2"
                                                    x-on:click="$dispatch('open-confirm', { name: 'delete-structure-{{ $structure->id }}' })">Supprimer</x-ui.button>
                                                <x-ui.confirm
                                                    name="delete-structure-{{ $structure->id }}"
                                                    title="Supprimer cet établissement ?"
                                                    :consequence="'Toutes les données de « '.$structure->name.' » seront supprimées, ainsi que ses comptes. Cette action est définitive.'"
                                                    :typed-confirmation="$structure->name"
                                                    confirm-label="Supprimer définitivement"
                                                    :action="route('superadmin.structures.destroy', $structure)"
                                                />
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
                <x-ui.pagination :paginator="$structures" label="Pagination des établissements" />
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
