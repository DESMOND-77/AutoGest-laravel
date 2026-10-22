<x-app-layout>
    <x-slot name="header">Établissements</x-slot>

    <div class="py-6 space-y-5 max-w-7xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kpi-card icon="building-office" label="Auto-écoles" :value="$statusCounts->sum()" />
            <x-kpi-card icon="shield-check" label="Tenants actifs" :value="$statusCounts->get('active', 0)" />
            <x-kpi-card icon="exclamation-triangle" label="Tenants suspendus" :value="$statusCounts->get('suspended', 0)" />
        </div>

        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('superadmin.structures.index') }}" @class([
                'px-3.5 py-1.5 rounded-ui-md font-medium transition',
                'bg-primary text-primary-content' => ! $currentStatus,
                'bg-surface-inset text-content-secondary hover:text-content' => $currentStatus,
            ])>
                Tous
            </a>
            @foreach ($statuses as $status)
                <a href="{{ route('superadmin.structures.index', ['status' => $status->value]) }}" @class([
                    'px-3.5 py-1.5 rounded-ui-md font-medium transition',
                    'bg-primary text-primary-content' => $currentStatus === $status->value,
                    'bg-surface-inset text-content-secondary hover:text-content' => $currentStatus !== $status->value,
                ])>
                    {{ $status->value }}
                </a>
            @endforeach
        </div>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nom</th>
                            <th class="px-5 py-3 font-medium">E-mail</th>
                            <th class="px-5 py-3 font-medium">Utilisateurs</th>
                            <th class="px-5 py-3 font-medium">Statut</th>
                            <th class="px-5 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($structures as $structure)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 font-medium text-content">{{ $structure->name }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $structure->email }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $structure->users_count }}</td>
                                <td class="px-5 py-3">
                                    <x-badge :variant="$structure->status->value === 'active' ? 'success' : ($structure->status->value === 'suspended' ? 'danger' : 'neutral')">
                                        {{ $structure->status->value }}
                                    </x-badge>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($statuses as $status)
                                            @unless ($status === $structure->status)
                                                <form method="POST" action="{{ route('superadmin.structures.status', $structure) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="{{ $status->value }}">
                                                    <button type="submit" class="text-xs text-primary hover:underline">
                                                        {{ $status->value }}
                                                    </button>
                                                </form>
                                            @endunless
                                        @endforeach
                                        <form method="POST" action="{{ route('superadmin.structures.destroy', $structure) }}"
                                              onsubmit="return confirm('Supprimer définitivement cet établissement et toutes ses données ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-danger hover:underline">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="5"
                                title="Aucun établissement inscrit."
                                message="Les nouveaux établissements apparaîtront ici après leur inscription publique, en attente de validation."
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{ $structures->links() }}
    </div>
</x-app-layout>
