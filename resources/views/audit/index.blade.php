<x-app-layout>
    <x-slot name="header">Journal d'audit</x-slot>

    <div class="py-6 max-w-4xl mx-auto">
        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Utilisateur</th>
                            <th class="px-5 py-3 font-medium">Action</th>
                            <th class="px-5 py-3 font-medium">Cible</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content-secondary">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3 text-content">{{ $log->user?->name ?? 'Système' }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $log->action }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ class_basename($log->auditable_type ?? '') }} #{{ $log->auditable_id }}</td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="4"
                                title="Aucune entrée d'audit."
                                message="Les actions sensibles (suppressions, changements d'étape, mises à jour d'établissement) apparaîtront ici automatiquement."
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
