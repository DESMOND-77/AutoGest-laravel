<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Journal d'audit
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Utilisateur</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Cible</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-3">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $log->user?->name ?? 'Système' }}</td>
                                <td class="px-4 py-3">{{ $log->action }}</td>
                                <td class="px-4 py-3">{{ class_basename($log->auditable_type ?? '') }} #{{ $log->auditable_id }}</td>
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
            </div>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
